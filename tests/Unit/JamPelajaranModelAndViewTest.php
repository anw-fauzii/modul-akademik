<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\Pelajaran\JamPelajaranController;
use App\Http\Requests\Admin\Pelajaran\JamPelajaranRequest;
use App\Models\Admin\Pelajaran\JamPelajaran;
use App\Services\Admin\Pelajaran\JamPelajaranService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use PHPUnit\Framework\Attributes\TestWith;
use ReflectionMethod;
use Tests\TestCase;

class JamPelajaranModelAndViewTest extends TestCase
{
    public function test_model_metadata_fillable_casts_and_unit_options_are_configured(): void
    {
        $jamPelajaran = new JamPelajaran;

        $this->assertSame('akademik_pelajaran_jam_pelajaran', $jamPelajaran->getTable());
        $this->assertSame([
            'jam_ke',
            'unit',
            'jam_mulai',
            'jam_selesai',
            'keterangan',
        ], $jamPelajaran->getFillable());
        $this->assertSame(['PG', 'TK', 'SD'], JamPelajaran::UNIT_OPTIONS);
        $this->assertSame([], JamPelajaran::INDEX_RELATIONS);
        $this->assertSame('integer', $jamPelajaran->getCasts()['jam_ke'] ?? null);
    }

    #[TestWith(['PG', '07:30:00', '08:00:00', '07:30', '08:00', '07:30 - 08:00', 30, '30 menit', 'badge-info'])]
    #[TestWith(['TK', '08:00', '08:45', '08:00', '08:45', '08:00 - 08:45', 45, '45 menit', 'badge-warning text-dark'])]
    #[TestWith(['SD', '09:30:00', '09:30:00', '09:30', '09:30', '09:30 - 09:30', null, '-', 'badge-success'])]
    public function test_accessors_format_waktu_durasi_and_unit_badge(
        string $unit,
        string $jamMulai,
        string $jamSelesai,
        string $expectedMulai,
        string $expectedSelesai,
        string $expectedRentang,
        ?int $expectedDurasiMenit,
        string $expectedDurasiLabel,
        string $expectedUnitClass
    ): void {
        $jam = JamPelajaran::make([
            'jam_ke' => 1,
            'unit' => $unit,
            'jam_mulai' => $jamMulai,
            'jam_selesai' => $jamSelesai,
            'keterangan' => 'Pembelajaran Inti',
        ]);

        $this->assertSame($expectedMulai, $jam->jam_mulai_formatted);
        $this->assertSame($expectedSelesai, $jam->jam_selesai_formatted);
        $this->assertSame($expectedRentang, $jam->rentang_waktu);
        $this->assertSame($expectedDurasiMenit, $jam->durasi_menit);
        $this->assertSame($expectedDurasiLabel, $jam->durasi_label);
        $this->assertSame($expectedUnitClass, $jam->unit_badge_class);
    }

    public function test_service_index_query_selects_minimal_columns_applies_filters_and_escapes_search(): void
    {
        $service = new JamPelajaranService;

        $builder = $service->buildIndexQuery([
            'unit' => 'sd',
            'search' => '100%_istirahat',
        ]);

        $query = $builder->getQuery();
        $this->assertSame([
            'id',
            'jam_ke',
            'unit',
            'jam_mulai',
            'jam_selesai',
            'keterangan',
            'created_at',
        ], $query->columns);

        $sql = strtolower($builder->toSql());
        $this->assertStringContainsString('unit', $sql);
        $this->assertStringContainsString('order by', $sql);
        $this->assertSame([
            'SD',
            '%100\%\_istirahat%',
            '%100\%\_istirahat%',
            '%100\%\_istirahat%',
            '%100\%\_istirahat%',
        ], $builder->getBindings());

        $numericSearchBuilder = $service->buildIndexQuery([
            'search' => '2',
        ]);
        $this->assertSame([2, '%2%'], $numericSearchBuilder->getBindings());
    }

    public function test_request_normalizes_inputs_and_enforces_time_order_and_unique_rule(): void
    {
        $request = JamPelajaranRequest::create('/pelajaran/jam-pelajaran', 'POST', [
            'jam_ke' => ' 2 ',
            'unit' => ' sd ',
            'jam_mulai' => '7:30',
            'jam_selesai' => '08:05:00',
            'keterangan' => '  Pembelajaran   Inti  ',
        ]);

        $prepareMethod = new ReflectionMethod(JamPelajaranRequest::class, 'prepareForValidation');
        $prepareMethod->invoke($request);

        $this->assertSame('2', $request->input('jam_ke'));
        $this->assertSame('SD', $request->input('unit'));
        $this->assertSame('07:30', $request->input('jam_mulai'));
        $this->assertSame('08:05', $request->input('jam_selesai'));
        $this->assertSame('Pembelajaran Inti', $request->input('keterangan'));

        $emptyKeteranganRequest = JamPelajaranRequest::create('/pelajaran/jam-pelajaran', 'POST', [
            'jam_ke' => '1',
            'unit' => 'pg',
            'jam_mulai' => '07:30',
            'jam_selesai' => '08:00',
            'keterangan' => '   ',
        ]);
        $prepareMethod->invoke($emptyKeteranganRequest);
        $this->assertNull($emptyKeteranganRequest->input('keterangan'));

        $rules = $request->rules();
        $this->assertArrayHasKey('jam_ke', $rules);
        $this->assertArrayHasKey('unit', $rules);
        $this->assertArrayHasKey('jam_mulai', $rules);
        $this->assertArrayHasKey('jam_selesai', $rules);
        $this->assertArrayHasKey('keterangan', $rules);
        $this->assertContains('after:jam_mulai', $rules['jam_selesai']);

        $hasUniqueRule = false;
        foreach ($rules['jam_ke'] as $rule) {
            if ($rule instanceof Unique) {
                $hasUniqueRule = true;
            }
        }
        $this->assertTrue($hasUniqueRule, 'jam_ke rule should enforce uniqueness per unit.');

        // Pastikan validasi waktu menolak jam_selesai <= jam_mulai tanpa memicu query unique DB
        $timeRules = [
            'unit' => ['required', 'string', Rule::in(JamPelajaran::UNIT_OPTIONS)],
            'jam_ke' => ['required', 'integer', 'min:1', 'max:99'],
            'jam_mulai' => $rules['jam_mulai'],
            'jam_selesai' => $rules['jam_selesai'],
            'keterangan' => $rules['keterangan'],
        ];

        $invalidValidator = app('validator')->make([
            'unit' => 'SD',
            'jam_ke' => 1,
            'jam_mulai' => '08:00',
            'jam_selesai' => '07:45',
            'keterangan' => null,
        ], $timeRules, $request->messages());

        $this->assertTrue($invalidValidator->fails());
        $this->assertArrayHasKey('jam_selesai', $invalidValidator->errors()->toArray());
    }

    public function test_thin_controller_delegates_crud_methods_to_service(): void
    {
        $jamPelajaran = JamPelajaran::make([
            'jam_ke' => 1,
            'unit' => 'SD',
            'jam_mulai' => '07:00',
            'jam_selesai' => '07:35',
            'keterangan' => 'Jam Pertama',
        ]);
        $jamPelajaran->id = 12;

        $service = $this->createMock(JamPelajaranService::class);
        $service->expects($this->once())
            ->method('getIndexData')
            ->with(['unit' => 'SD'])
            ->willReturn([
                'jamPelajaran' => new Collection([$jamPelajaran]),
                'summary' => [
                    'total' => 1,
                    'per_unit' => [
                        'SD' => [
                            'total' => 1,
                            'jam_pertama' => '07:00',
                            'jam_terakhir' => '07:35',
                        ],
                    ],
                ],
                'filters' => ['unit' => 'SD', 'search' => ''],
                'unitOptions' => JamPelajaran::UNIT_OPTIONS,
            ]);
        $service->expects($this->exactly(2))
            ->method('getFormOptions')
            ->willReturn([
                'unitOptions' => JamPelajaran::UNIT_OPTIONS,
                'nextJamKeByUnit' => ['PG' => 1, 'TK' => 1, 'SD' => 2],
                'defaultNextJamKe' => 1,
            ]);
        $service->expects($this->once())
            ->method('store')
            ->willReturn($jamPelajaran);
        $service->expects($this->once())
            ->method('update')
            ->with($jamPelajaran, $this->isArray())
            ->willReturn(true);
        $service->expects($this->once())
            ->method('delete')
            ->with($jamPelajaran)
            ->willReturn(true);

        $controller = new JamPelajaranController($service);

        $indexView = $controller->index(Request::create('/pelajaran/jam-pelajaran?unit=SD', 'GET'));
        $this->assertSame('admin.pelajaran.jamPelajaran.index', $indexView->name());

        $createView = $controller->create();
        $this->assertSame('admin.pelajaran.jamPelajaran.create', $createView->name());

        $editView = $controller->edit($jamPelajaran);
        $this->assertSame('admin.pelajaran.jamPelajaran.edit', $editView->name());

        $formRequest = JamPelajaranRequest::create('/pelajaran/jam-pelajaran', 'POST', [
            'jam_ke' => 1,
            'unit' => 'SD',
            'jam_mulai' => '07:00',
            'jam_selesai' => '07:35',
            'keterangan' => 'Jam Pertama',
        ]);
        $validator = app('validator')->make($formRequest->all(), [
            'jam_ke' => 'required|integer|min:1|max:99',
            'unit' => 'required|string',
            'jam_mulai' => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
            'keterangan' => 'nullable|string|max:1000',
        ]);
        $validator->validate();
        $formRequest->setValidator($validator);

        $this->assertTrue($controller->store($formRequest)->isRedirect(route('jam-pelajaran.index')));
        $this->assertTrue($controller->update($formRequest, $jamPelajaran)->isRedirect(route('jam-pelajaran.index')));
        $this->assertTrue($controller->destroy($jamPelajaran)->isRedirect(route('jam-pelajaran.index')));
    }

    public function test_index_and_form_views_render_without_lazy_loading_and_with_formatted_values(): void
    {
        view()->share('errors', new ViewErrorBag);

        $jamPelajaran = JamPelajaran::make([
            'jam_ke' => 2,
            'unit' => 'SD',
            'jam_mulai' => '07:35:00',
            'jam_selesai' => '08:10:00',
            'keterangan' => 'Matematika & Literasi',
        ]);
        $jamPelajaran->id = 44;

        Model::preventLazyLoading(true);

        try {
            $indexHtml = view('admin.pelajaran.jamPelajaran.index', [
                'jamPelajaran' => new Collection([$jamPelajaran]),
                'summary' => [
                    'total' => 1,
                    'per_unit' => [
                        'SD' => [
                            'total' => 1,
                            'jam_pertama' => '07:35',
                            'jam_terakhir' => '08:10',
                        ],
                    ],
                ],
                'filters' => ['unit' => '', 'search' => ''],
                'unitOptions' => JamPelajaran::UNIT_OPTIONS,
            ])->render();

            $createHtml = view('admin.pelajaran.jamPelajaran.create', [
                'unitOptions' => JamPelajaran::UNIT_OPTIONS,
                'nextJamKeByUnit' => ['PG' => 1, 'TK' => 2, 'SD' => 3],
                'defaultNextJamKe' => 1,
            ])->render();

            $editHtml = view('admin.pelajaran.jamPelajaran.edit', [
                'jamPelajaran' => $jamPelajaran,
                'unitOptions' => JamPelajaran::UNIT_OPTIONS,
                'nextJamKeByUnit' => ['PG' => 1, 'TK' => 2, 'SD' => 3],
                'defaultNextJamKe' => 1,
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('Jam Ke-2', $indexHtml);
        $this->assertStringContainsString('SD', $indexHtml);
        $this->assertStringContainsString('07:35', $indexHtml);
        $this->assertStringContainsString('08:10', $indexHtml);
        $this->assertStringContainsString('35 menit', $indexHtml);
        $this->assertStringContainsString('Matematika &amp; Literasi', $indexHtml);
        $this->assertStringContainsString(route('jam-pelajaran.edit', 44), $indexHtml);
        $this->assertStringContainsString(route('jam-pelajaran.destroy', 44), $indexHtml);

        $this->assertStringContainsString(route('jam-pelajaran.store'), $createHtml);
        $this->assertStringContainsString('name="unit"', $createHtml);
        $this->assertStringContainsString('name="jam_ke"', $createHtml);
        $this->assertStringContainsString('name="jam_mulai"', $createHtml);
        $this->assertStringContainsString('name="jam_selesai"', $createHtml);
        $this->assertStringContainsString('data-time24="true"', $createHtml);
        $this->assertStringContainsString('pattern="^([01]\\d|2[0-3]):[0-5]\\d$"', $createHtml);
        $this->assertStringNotContainsString('type="time"', $createHtml);
        $this->assertStringContainsString('name="keterangan"', $createHtml);
        $this->assertStringContainsString('value="1"', $createHtml);

        $this->assertStringContainsString(route('jam-pelajaran.update', 44), $editHtml);
        $this->assertStringContainsString('value="07:35"', $editHtml);
        $this->assertStringContainsString('value="08:10"', $editHtml);
        $this->assertStringNotContainsString('type="time"', $editHtml);
        $this->assertStringContainsString('Matematika &amp; Literasi', $editHtml);
        $this->assertStringContainsString(route('jam-pelajaran.index'), view('layouts.sidebar')->render());
    }
}
