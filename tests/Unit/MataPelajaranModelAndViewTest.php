<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\Pelajaran\MataPelajaranController;
use App\Http\Requests\Admin\Pelajaran\MataPelajaranRequest;
use App\Models\Admin\Pelajaran\KelompokPelajaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Services\Admin\Pelajaran\MataPelajaranService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\TestWith;
use ReflectionMethod;
use Tests\TestCase;

class MataPelajaranModelAndViewTest extends TestCase
{
    public function test_model_metadata_fillable_casts_and_relations_are_configured(): void
    {
        $mapel = new MataPelajaran;

        $this->assertSame('akademik_pelajaran_mata_pelajaran', $mapel->getTable());
        $this->assertSame([
            'akademik_pelajaran_kelompok_pelajaran_id',
            'singkatan',
            'nama_mata_pelajaran',
            'sifat',
            'unit',
            'urutan',
            'keterangan',
            'status',
        ], $mapel->getFillable());
        $this->assertSame(MataPelajaran::SIFAT_WAJIB, $mapel->sifat);
        $this->assertTrue($mapel->status);
        $this->assertSame(['wajib', 'tambahan'], MataPelajaran::SIFAT_OPTIONS);
        $this->assertSame(['PG', 'TK', 'SD'], MataPelajaran::UNIT_OPTIONS);

        $casts = $mapel->getCasts();
        $this->assertSame('integer', $casts['akademik_pelajaran_kelompok_pelajaran_id']);
        $this->assertSame('integer', $casts['urutan']);
        $this->assertSame('boolean', $casts['status']);

        $relation = $mapel->kelompokPelajaran();
        $this->assertSame('akademik_pelajaran_kelompok_pelajaran_id', $relation->getForeignKeyName());
        $this->assertSame('id', $relation->getOwnerKeyName());

        $kelompok = new KelompokPelajaran;
        $hasMany = $kelompok->mataPelajaran();
        $this->assertSame('akademik_pelajaran_kelompok_pelajaran_id', $hasMany->getForeignKeyName());
    }

    #[TestWith(['wajib', true, 'SD', 'Wajib', 'badge-primary', 'Aktif', 'badge-success', 'badge-success'])]
    #[TestWith(['tambahan', false, 'PG', 'Tambahan', 'badge-secondary', 'Nonaktif', 'badge-secondary', 'badge-info'])]
    public function test_accessors_format_sifat_status_and_unit_badges(
        string $sifat,
        bool $status,
        string $unit,
        string $expectedSifatLabel,
        string $expectedSifatClass,
        string $expectedStatusLabel,
        string $expectedStatusClass,
        string $expectedUnitClass
    ): void {
        $mapel = MataPelajaran::make([
            'sifat' => $sifat,
            'status' => $status,
            'unit' => $unit,
        ]);

        $this->assertSame($expectedSifatLabel, $mapel->sifat_label);
        $this->assertSame($expectedSifatClass, $mapel->sifat_badge_class);
        $this->assertSame($expectedStatusLabel, $mapel->status_label);
        $this->assertSame($expectedStatusClass, $mapel->status_badge_class);
        $this->assertSame($expectedUnitClass, $mapel->unit_badge_class);
    }

    public function test_service_index_query_selects_minimal_columns_applies_filters_and_eager_loads_kelompok_without_n_plus_one(): void
    {
        $service = new MataPelajaranService;
        $builder = $service->buildIndexQuery([
            'unit' => 'sd',
            'kelompok_pelajaran_id' => '4',
            'sifat' => 'WAJIB',
            'status' => '1',
            'search' => 'MTK_100%',
        ]);

        $query = $builder->getQuery();
        $this->assertSame([
            'id',
            'akademik_pelajaran_kelompok_pelajaran_id',
            'singkatan',
            'nama_mata_pelajaran',
            'sifat',
            'unit',
            'urutan',
            'keterangan',
            'status',
            'created_at',
        ], $query->columns);

        $sql = strtolower($builder->toSql());
        $this->assertStringContainsString('unit', $sql);
        $this->assertStringContainsString('akademik_pelajaran_kelompok_pelajaran_id', $sql);
        $this->assertStringContainsString('sifat', $sql);
        $this->assertStringContainsString('status', $sql);
        $this->assertStringContainsString('order by', $sql);
        $this->assertSame(['SD', 4, 'wajib', true, '%MTK\_100\%%', '%MTK\_100\%%', '%MTK\_100\%%'], $builder->getBindings());

        $eagerLoads = $builder->getEagerLoads();
        $this->assertArrayHasKey('kelompokPelajaran', $eagerLoads);

        $kelompokQuery = KelompokPelajaran::query();
        ($eagerLoads['kelompokPelajaran'])($kelompokQuery);
        $this->assertSame(['id', 'kode', 'kelompok', 'urutan'], $kelompokQuery->getQuery()->columns);
    }

    public function test_request_normalizes_inputs_and_parses_boolean_status(): void
    {
        $request = MataPelajaranRequest::create('/pelajaran/mata-pelajaran', 'POST', [
            'akademik_pelajaran_kelompok_pelajaran_id' => '2',
            'singkatan' => '  b.   ind  ',
            'nama_mata_pelajaran' => '  Bahasa   Indonesia  ',
            'sifat' => ' Wajib ',
            'unit' => ' sd ',
            'urutan' => ' 3 ',
            'keterangan' => '   ',
            'status' => '0',
        ]);

        $prepareMethod = new ReflectionMethod(MataPelajaranRequest::class, 'prepareForValidation');
        $prepareMethod->invoke($request);

        $this->assertSame('B. IND', $request->input('singkatan'));
        $this->assertSame('Bahasa Indonesia', $request->input('nama_mata_pelajaran'));
        $this->assertSame('wajib', $request->input('sifat'));
        $this->assertSame('SD', $request->input('unit'));
        $this->assertSame('3', $request->input('urutan'));
        $this->assertNull($request->input('keterangan'));
        $this->assertFalse($request->input('status'));

        $rules = $request->rules();
        $this->assertArrayHasKey('akademik_pelajaran_kelompok_pelajaran_id', $rules);
        $this->assertArrayHasKey('singkatan', $rules);
        $this->assertArrayHasKey('nama_mata_pelajaran', $rules);
        $this->assertArrayHasKey('sifat', $rules);
        $this->assertArrayHasKey('unit', $rules);
        $this->assertArrayHasKey('urutan', $rules);
        $this->assertArrayHasKey('keterangan', $rules);
        $this->assertArrayHasKey('status', $rules);
        $this->assertContains('boolean', $rules['status']);
    }

    public function test_thin_controller_delegates_crud_and_toggle_status_to_service(): void
    {
        $kelompok = KelompokPelajaran::make([
            'kode' => 'A',
            'kelompok' => 'Kelompok A (Wajib)',
            'urutan' => 1,
        ]);
        $kelompok->id = 2;

        $mapel = MataPelajaran::make([
            'akademik_pelajaran_kelompok_pelajaran_id' => 2,
            'singkatan' => 'MTK',
            'nama_mata_pelajaran' => 'Matematika',
            'sifat' => MataPelajaran::SIFAT_WAJIB,
            'unit' => 'SD',
            'urutan' => 1,
            'status' => true,
        ]);
        $mapel->id = 11;

        $service = $this->createMock(MataPelajaranService::class);
        $service->expects($this->once())
            ->method('getIndexData')
            ->with(['unit' => 'SD'])
            ->willReturn([
                'mataPelajaran' => new Collection([$mapel]),
                'summary' => ['total' => 1, 'aktif' => 1, 'nonaktif' => 0, 'per_unit' => ['SD' => ['total' => 1, 'aktif' => 1]]],
                'filters' => ['unit' => 'SD', 'kelompok_pelajaran_id' => '', 'sifat' => '', 'status' => 'all', 'search' => ''],
                'kelompokPelajaranOptions' => new Collection([$kelompok]),
                'unitOptions' => MataPelajaran::UNIT_OPTIONS,
                'sifatOptions' => MataPelajaran::SIFAT_OPTIONS,
            ]);
        $service->expects($this->exactly(2))
            ->method('getFormOptions')
            ->willReturn([
                'kelompokPelajaran' => new Collection([$kelompok]),
                'unitOptions' => MataPelajaran::UNIT_OPTIONS,
                'sifatOptions' => MataPelajaran::SIFAT_OPTIONS,
                'nextUrutan' => 2,
            ]);
        $service->expects($this->once())
            ->method('store')
            ->willReturn($mapel);
        $service->expects($this->once())
            ->method('update')
            ->with($mapel, $this->isArray())
            ->willReturn(true);
        $service->expects($this->once())
            ->method('delete')
            ->with($mapel)
            ->willReturn(true);
        $service->expects($this->once())
            ->method('toggleStatus')
            ->with($mapel)
            ->willReturn(true);

        $controller = new MataPelajaranController($service);

        $indexView = $controller->index(Request::create('/pelajaran/mata-pelajaran?unit=SD', 'GET'));
        $this->assertSame('admin.pelajaran.mataPelajaran.index', $indexView->name());

        $createView = $controller->create();
        $this->assertSame('admin.pelajaran.mataPelajaran.create', $createView->name());

        $editView = $controller->edit($mapel);
        $this->assertSame('admin.pelajaran.mataPelajaran.edit', $editView->name());

        $formRequest = MataPelajaranRequest::create('/pelajaran/mata-pelajaran', 'POST', [
            'akademik_pelajaran_kelompok_pelajaran_id' => 2,
            'singkatan' => 'MTK',
            'nama_mata_pelajaran' => 'Matematika',
            'sifat' => 'wajib',
            'unit' => 'SD',
            'urutan' => 1,
            'status' => true,
        ]);
        $validator = app('validator')->make($formRequest->all(), [
            'akademik_pelajaran_kelompok_pelajaran_id' => 'required|integer',
            'singkatan' => 'required|string|max:30',
            'nama_mata_pelajaran' => 'required|string|max:255',
            'sifat' => 'required|string',
            'unit' => 'required|string',
            'urutan' => 'required|integer|min:1',
            'status' => 'required|boolean',
        ]);
        $validator->validate();
        $formRequest->setValidator($validator);

        $this->assertTrue($controller->store($formRequest)->isRedirect(route('mata-pelajaran.index')));
        $this->assertTrue($controller->update($formRequest, $mapel)->isRedirect(route('mata-pelajaran.index')));
        $this->assertTrue($controller->destroy($mapel)->isRedirect(route('mata-pelajaran.index')));
        $this->assertTrue($controller->toggleStatus($mapel)->isRedirect(route('mata-pelajaran.index')));
    }

    public function test_index_and_form_views_render_without_lazy_loading_and_with_formatted_values(): void
    {
        view()->share('errors', new ViewErrorBag);

        $kelompok = KelompokPelajaran::make([
            'kode' => 'A',
            'kelompok' => 'Kelompok A (Wajib)',
            'urutan' => 1,
        ]);
        $kelompok->id = 2;

        $mapel = MataPelajaran::make([
            'akademik_pelajaran_kelompok_pelajaran_id' => 2,
            'singkatan' => 'MTK',
            'nama_mata_pelajaran' => 'Matematika Wajib',
            'sifat' => MataPelajaran::SIFAT_WAJIB,
            'unit' => 'SD',
            'urutan' => 1,
            'keterangan' => 'Mapel inti berhitung',
            'status' => true,
        ]);
        $mapel->id = 33;
        $mapel->setRelation('kelompokPelajaran', $kelompok);

        Model::preventLazyLoading(true);

        try {
            $indexHtml = view('admin.pelajaran.mataPelajaran.index', [
                'mataPelajaran' => new Collection([$mapel]),
                'summary' => ['total' => 1, 'aktif' => 1, 'nonaktif' => 0, 'per_unit' => ['SD' => ['total' => 1, 'aktif' => 1]]],
                'filters' => ['unit' => '', 'kelompok_pelajaran_id' => '', 'sifat' => '', 'status' => 'all', 'search' => ''],
                'kelompokPelajaranOptions' => new Collection([$kelompok]),
                'unitOptions' => MataPelajaran::UNIT_OPTIONS,
                'sifatOptions' => MataPelajaran::SIFAT_OPTIONS,
            ])->render();

            $createHtml = view('admin.pelajaran.mataPelajaran.create', [
                'kelompokPelajaran' => new Collection([$kelompok]),
                'unitOptions' => MataPelajaran::UNIT_OPTIONS,
                'sifatOptions' => MataPelajaran::SIFAT_OPTIONS,
                'nextUrutan' => 5,
            ])->render();

            $editHtml = view('admin.pelajaran.mataPelajaran.edit', [
                'mataPelajaran' => $mapel,
                'kelompokPelajaran' => new Collection([$kelompok]),
                'unitOptions' => MataPelajaran::UNIT_OPTIONS,
                'sifatOptions' => MataPelajaran::SIFAT_OPTIONS,
                'nextUrutan' => 5,
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('MTK', $indexHtml);
        $this->assertStringContainsString('Matematika Wajib', $indexHtml);
        $this->assertStringContainsString('Kelompok A (Wajib)', $indexHtml);
        $this->assertStringContainsString('Wajib', $indexHtml);
        $this->assertStringContainsString('Aktif', $indexHtml);
        $this->assertStringContainsString(route('mata-pelajaran.toggle-status', 33), $indexHtml);
        $this->assertStringContainsString(route('mata-pelajaran.edit', 33), $indexHtml);
        $this->assertStringContainsString(route('mata-pelajaran.destroy', 33), $indexHtml);

        $this->assertStringContainsString(route('mata-pelajaran.store'), $createHtml);
        $this->assertStringContainsString('name="singkatan"', $createHtml);
        $this->assertStringContainsString('name="nama_mata_pelajaran"', $createHtml);
        $this->assertStringContainsString('name="sifat"', $createHtml);
        $this->assertStringContainsString('name="unit"', $createHtml);
        $this->assertStringContainsString('name="akademik_pelajaran_kelompok_pelajaran_id"', $createHtml);
        $this->assertStringContainsString('name="urutan"', $createHtml);
        $this->assertStringContainsString('name="status"', $createHtml);
        $this->assertStringContainsString('value="5"', $createHtml);

        $this->assertStringContainsString(route('mata-pelajaran.update', 33), $editHtml);
        $this->assertStringContainsString('Matematika Wajib', $editHtml);
        $this->assertStringContainsString(route('mata-pelajaran.index'), view('layouts.sidebar')->render());
    }
}
