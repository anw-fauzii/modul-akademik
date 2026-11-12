<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\Informasi\AgendaSekolahController;
use App\Http\Requests\Admin\Informasi\AgendaSekolahRequest;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Informasi\AgendaSekolah;
use App\Services\Admin\Informasi\AgendaSekolahService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\TestWith;
use ReflectionMethod;
use Tests\TestCase;

class AgendaSekolahModelAndViewTest extends TestCase
{
    public function test_model_metadata_fillable_casts_and_relation_are_configured(): void
    {
        $agenda = new AgendaSekolah;

        $this->assertSame('akademik_informasi_agenda_sekolah', $agenda->getTable());
        $this->assertSame([
            'akademik_master_tahun_ajaran_id',
            'nama_agenda',
            'tanggal',
            'jam',
            'unit',
            'jenis',
        ], $agenda->getFillable());
        $this->assertSame(AgendaSekolah::JENIS_SISWA, $agenda->jenis);
        $this->assertSame(['siswa', 'pegawai'], AgendaSekolah::JENIS_OPTIONS);

        $casts = $agenda->getCasts();
        $this->assertSame('integer', $casts['akademik_master_tahun_ajaran_id']);
        $this->assertSame('date', $casts['tanggal']);

        $relation = $agenda->tahunAjaran();
        $this->assertSame('akademik_master_tahun_ajaran_id', $relation->getForeignKeyName());
        $this->assertSame('id', $relation->getOwnerKeyName());
    }

    #[TestWith(['07:30:00', '07:30'])]
    #[TestWith(['08:15', '08:15'])]
    #[TestWith(['', '-'])]
    public function test_jam_formatted_normalizes_time_strings(string $rawJam, string $expected): void
    {
        $agenda = AgendaSekolah::make(['jam' => $rawJam]);

        $this->assertSame($expected, $agenda->jam_formatted);
    }

    #[TestWith(['Semua Unit', 'badge-dark'])]
    #[TestWith(['SD', 'badge-success'])]
    #[TestWith(['smp', 'badge-primary'])]
    #[TestWith(['TK', 'badge-warning text-dark'])]
    #[TestWith(['SMA', 'badge-info'])]
    #[TestWith(['Umum', 'badge-dark'])]
    public function test_unit_badge_class_maps_unit_labels(string $unit, string $expectedClass): void
    {
        $agenda = AgendaSekolah::make(['unit' => $unit]);

        $this->assertSame($expectedClass, $agenda->unit_badge_class);
    }

    public function test_tanggal_and_hari_accessors_format_indonesian_date_strings(): void
    {
        $agenda = AgendaSekolah::make(['tanggal' => '2026-11-12']);

        $this->assertNotSame('-', $agenda->tanggal_formatted);
        $this->assertStringContainsString('2026', $agenda->tanggal_formatted);
        $this->assertNotSame('-', $agenda->hari_formatted);

        $emptyDateAgenda = AgendaSekolah::make(['tanggal' => null]);
        $this->assertSame('-', $emptyDateAgenda->tanggal_formatted);
        $this->assertSame('-', $emptyDateAgenda->hari_formatted);
    }

    public function test_service_index_query_selects_minimal_columns_and_eager_loads_tahun_ajaran_to_prevent_n_plus_one(): void
    {
        $service = new AgendaSekolahService;
        $builder = $service->buildIndexQuery([
            'tahun_ajaran_id' => 10,
            'unit' => 'SD',
        ]);

        $query = $builder->getQuery();
        $this->assertSame([
            'id',
            'akademik_master_tahun_ajaran_id',
            'nama_agenda',
            'tanggal',
            'jam',
            'unit',
            'jenis',
        ], $query->columns);

        $sql = $builder->toSql();
        $this->assertStringContainsString('jenis', $sql);
        $this->assertStringContainsString('akademik_master_tahun_ajaran_id', $sql);
        $this->assertStringContainsString('unit', $sql);
        $this->assertStringContainsString('order by', strtolower($sql));
        $this->assertSame('siswa', $builder->getBindings()[0] ?? null);

        $eagerLoads = $builder->getEagerLoads();
        $this->assertArrayHasKey('tahunAjaran', $eagerLoads);

        $tahunAjaranQuery = TahunAjaran::query();
        ($eagerLoads['tahunAjaran'])($tahunAjaranQuery);
        $this->assertSame(['id', 'nama_tahun_ajaran', 'semester', 'is_active'], $tahunAjaranQuery->getQuery()->columns);
    }

    public function test_request_prepares_trimmed_inputs_and_normalizes_seconds_from_jam(): void
    {
        $request = AgendaSekolahRequest::create('/informasi/agenda-sekolah', 'POST', [
            'akademik_master_tahun_ajaran_id' => '5',
            'nama_agenda' => '  Rapat Komite Sekolah  ',
            'tanggal' => '2026-12-01',
            'jam' => '08:30:00',
            'unit' => ' SD ',
            'jenis' => 'pegawai',
        ]);

        $prepareMethod = new ReflectionMethod(AgendaSekolahRequest::class, 'prepareForValidation');
        $prepareMethod->invoke($request);

        $this->assertSame('Rapat Komite Sekolah', $request->input('nama_agenda'));
        $this->assertSame('08:30', $request->input('jam'));
        $this->assertSame('SD', $request->input('unit'));
        $this->assertSame(AgendaSekolah::JENIS_SISWA, $request->input('jenis'));

        $rules = $request->rules();
        $this->assertArrayHasKey('akademik_master_tahun_ajaran_id', $rules);
        $this->assertArrayHasKey('nama_agenda', $rules);
        $this->assertArrayHasKey('tanggal', $rules);
        $this->assertArrayHasKey('jam', $rules);
        $this->assertArrayHasKey('unit', $rules);
        $this->assertArrayHasKey('jenis', $rules);
        $this->assertContains('date_format:H:i', $rules['jam']);
    }

    public function test_thin_controller_delegates_crud_methods_to_agenda_sekolah_service(): void
    {
        $tahunAjaran = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);
        $tahunAjaran->id = 3;

        $agenda = AgendaSekolah::make([
            'akademik_master_tahun_ajaran_id' => 3,
            'nama_agenda' => 'Upacara Bendera',
            'tanggal' => '2026-11-16',
            'jam' => '07:15',
            'unit' => 'Semua Unit',
        ]);
        $agenda->id = 9;

        $service = $this->createMock(AgendaSekolahService::class);
        $service->expects($this->once())
            ->method('getIndexData')
            ->with(['unit' => 'SD'])
            ->willReturn([
                'agendaSekolah' => new Collection,
                'unitSummary' => ['total_agenda' => 0, 'per_unit' => []],
                'filters' => ['tahun_ajaran_id' => '3', 'unit' => 'SD'],
                'tahunAjaranOptions' => new Collection([$tahunAjaran]),
                'activeTahunAjaran' => $tahunAjaran,
                'unitOptions' => AgendaSekolah::UNIT_OPTIONS,
            ]);
        $service->expects($this->exactly(2))
            ->method('getFormOptions')
            ->willReturn([
                'tahunAjaran' => new Collection([$tahunAjaran]),
                'unitOptions' => AgendaSekolah::UNIT_OPTIONS,
            ]);
        $service->expects($this->once())
            ->method('store')
            ->willReturn($agenda);
        $service->expects($this->once())
            ->method('update')
            ->with($agenda, $this->isArray())
            ->willReturn(true);
        $service->expects($this->once())
            ->method('delete')
            ->with($agenda)
            ->willReturn(true);

        $controller = new AgendaSekolahController($service);

        $indexView = $controller->index(Request::create('/informasi/agenda-sekolah?unit=SD', 'GET'));
        $this->assertSame('admin.informasi.agendaSekolah.index', $indexView->name());

        $createView = $controller->create();
        $this->assertSame('admin.informasi.agendaSekolah.create', $createView->name());

        $editView = $controller->edit($agenda);
        $this->assertSame('admin.informasi.agendaSekolah.edit', $editView->name());

        $formRequest = AgendaSekolahRequest::create('/informasi/agenda-sekolah', 'POST', [
            'akademik_master_tahun_ajaran_id' => 3,
            'nama_agenda' => 'Upacara Bendera',
            'tanggal' => '2026-11-16',
            'jam' => '07:15',
            'unit' => 'Semua Unit',
        ]);
        $validator = app('validator')->make($formRequest->all(), [
            'akademik_master_tahun_ajaran_id' => 'required|integer',
            'nama_agenda' => 'required|string',
            'tanggal' => 'required|date',
            'jam' => 'required|date_format:H:i',
            'unit' => 'required|string',
        ]);
        $validator->validate();
        $formRequest->setValidator($validator);

        $this->assertTrue($controller->store($formRequest)->isRedirect(route('agenda-sekolah.index')));
        $this->assertTrue($controller->update($formRequest, $agenda)->isRedirect(route('agenda-sekolah.index')));
        $this->assertTrue($controller->destroy($agenda)->isRedirect(route('agenda-sekolah.index')));
    }

    public function test_index_and_form_views_render_without_lazy_loading_and_with_formatted_values(): void
    {
        view()->share('errors', new ViewErrorBag);

        $tahunAjaran = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);
        $tahunAjaran->id = 1;

        $agenda = AgendaSekolah::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'nama_agenda' => 'MPLS Tahun Ajaran Baru',
            'tanggal' => '2026-07-13',
            'jam' => '07:30:00',
            'unit' => 'SD',
        ]);
        $agenda->id = 42;
        $agenda->setRelation('tahunAjaran', $tahunAjaran);

        Model::preventLazyLoading(true);

        try {
            $indexHtml = view('admin.informasi.agendaSekolah.index', [
                'agendaSekolah' => new Collection([$agenda]),
                'unitSummary' => ['total_agenda' => 1, 'per_unit' => ['SD' => 1]],
                'filters' => ['tahun_ajaran_id' => '1', 'unit' => ''],
                'tahunAjaranOptions' => new Collection([$tahunAjaran]),
                'activeTahunAjaran' => $tahunAjaran,
                'unitOptions' => AgendaSekolah::UNIT_OPTIONS,
            ])->render();

            $createHtml = view('admin.informasi.agendaSekolah.create', [
                'tahunAjaran' => new Collection([$tahunAjaran]),
                'unitOptions' => AgendaSekolah::UNIT_OPTIONS,
            ])->render();

            $editHtml = view('admin.informasi.agendaSekolah.edit', [
                'agendaSekolah' => $agenda,
                'tahunAjaran' => new Collection([$tahunAjaran]),
                'unitOptions' => AgendaSekolah::UNIT_OPTIONS,
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('MPLS Tahun Ajaran Baru', $indexHtml);
        $this->assertStringContainsString('07:30 WIB', $indexHtml);
        $this->assertStringContainsString('2026/2027', $indexHtml);
        $this->assertStringContainsString(route('agenda-sekolah.edit', 42), $indexHtml);
        $this->assertStringContainsString(route('agenda-sekolah.destroy', 42), $indexHtml);

        $this->assertStringContainsString(route('agenda-sekolah.store'), $createHtml);
        $this->assertStringContainsString('name="nama_agenda"', $createHtml);
        $this->assertStringContainsString('name="tanggal"', $createHtml);
        $this->assertStringContainsString('name="jam"', $createHtml);
        $this->assertStringContainsString('name="unit"', $createHtml);

        $this->assertStringContainsString(route('agenda-sekolah.update', 42), $editHtml);
        $this->assertStringContainsString('value="2026-07-13"', $editHtml);
        $this->assertStringContainsString('value="07:30"', $editHtml);
        $this->assertStringContainsString(route('agenda-sekolah.index'), view('layouts.sidebar')->render());
    }
}
