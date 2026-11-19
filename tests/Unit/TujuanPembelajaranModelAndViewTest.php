<?php

namespace Tests\Unit;

use App\Exports\Admin\TujuanPembelajaran\Sheets\ReferensiTpSheet;
use App\Exports\Admin\TujuanPembelajaran\Sheets\TemplateTpSheet;
use App\Exports\Admin\TujuanPembelajaran\TujuanPembelajaranExport;
use App\Exports\Admin\TujuanPembelajaran\TujuanPembelajaranTemplateExport;
use App\Http\Controllers\Admin\TujuanPembelajaran\TujuanPembelajaranController;
use App\Http\Requests\Admin\TujuanPembelajaran\ImportTujuanPembelajaranRequest;
use App\Http\Requests\Admin\TujuanPembelajaran\TujuanPembelajaranRequest;
use App\Imports\Admin\TujuanPembelajaran\TujuanPembelajaranImport;
use App\Imports\Admin\TujuanPembelajaran\TujuanPembelajaranSheetImport;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use App\Services\Admin\TujuanPembelajaran\TujuanPembelajaranService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\TestWith;
use ReflectionMethod;
use Tests\TestCase;

class TujuanPembelajaranModelAndViewTest extends TestCase
{
    public function test_model_metadata_fillable_casts_and_relations_are_configured(): void
    {
        $tp = new TujuanPembelajaran;

        $this->assertSame('akademik_pelajaran_tujuan_pembelajaran', $tp->getTable());
        $this->assertSame([
            'akademik_master_tahun_ajaran_id',
            'akademik_pelajaran_mata_pelajaran_id',
            'tingkatan_kelas',
            'fase',
            'elemen',
            'kode_tp',
            'tujuan_pembelajaran',
            'lingkup_materi',
            'urutan',
            'status',
        ], $tp->getFillable());
        $this->assertSame(1, $tp->urutan);
        $this->assertTrue($tp->status);

        $casts = $tp->getCasts();
        $this->assertSame('integer', $casts['akademik_master_tahun_ajaran_id']);
        $this->assertSame('integer', $casts['akademik_pelajaran_mata_pelajaran_id']);
        $this->assertSame('integer', $casts['urutan']);
        $this->assertSame('boolean', $casts['status']);

        $this->assertSame('akademik_master_tahun_ajaran_id', $tp->tahunAjaran()->getForeignKeyName());
        $this->assertSame('akademik_pelajaran_mata_pelajaran_id', $tp->mataPelajaran()->getForeignKeyName());
        $this->assertSame('akademik_pelajaran_mata_pelajaran_id', (new MataPelajaran)->tujuanPembelajaran()->getForeignKeyName());
    }

    #[TestWith(['PG', 'PG', 'Fondasi'])]
    #[TestWith(['TK', 'TK A', 'Fondasi'])]
    #[TestWith(['SD', '1', 'A'])]
    #[TestWith(['SD', '2', 'A'])]
    #[TestWith(['SD', '3', 'B'])]
    #[TestWith(['SD', '4', 'B'])]
    #[TestWith(['SD', '5', 'C'])]
    #[TestWith(['SD', '6', 'C'])]
    public function test_resolve_default_fase_maps_unit_and_tingkatan(string $unit, string $tingkat, string $expectedFase): void
    {
        $this->assertSame($expectedFase, TujuanPembelajaran::resolveDefaultFase($unit, $tingkat));
    }

    #[TestWith(['Fondasi', 'PG', 'Fase Fondasi', 'badge-warning text-dark', 'PG', true, 'Aktif', 'badge-success'])]
    #[TestWith(['B', '3', 'Fase B', 'badge-info', 'Kelas 3', false, 'Nonaktif', 'badge-secondary'])]
    public function test_accessors_format_fase_tingkatan_and_status_without_lazy_loading(
        string $fase,
        string $tingkatan,
        string $expectedFaseLabel,
        string $expectedFaseBadge,
        string $expectedTingkatanLabel,
        bool $status,
        string $expectedStatusLabel,
        string $expectedStatusBadge
    ): void {
        Model::preventLazyLoading(true);

        try {
            $tp = TujuanPembelajaran::make([
                'fase' => $fase,
                'tingkatan_kelas' => $tingkatan,
                'status' => $status,
            ]);

            $this->assertSame($expectedFaseLabel, $tp->fase_label);
            $this->assertSame($expectedFaseBadge, $tp->fase_badge_class);
            $this->assertSame($expectedTingkatanLabel, $tp->tingkatan_label);
            $this->assertSame($expectedStatusLabel, $tp->status_label);
            $this->assertSame($expectedStatusBadge, $tp->status_badge_class);
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    public function test_service_index_query_selects_minimal_columns_and_eager_loads_master_relations(): void
    {
        $service = new TujuanPembelajaranService;
        $builder = $service->buildIndexQuery([
            'tahun_ajaran_id' => '2',
            'unit' => 'sd',
            'mata_pelajaran_id' => '7',
            'tingkatan_kelas' => '4',
            'fase' => 'B',
            'status' => '1',
            'search' => '100%_Bilangan',
        ]);

        $query = $builder->getQuery();
        $this->assertSame([
            'id',
            'akademik_master_tahun_ajaran_id',
            'akademik_pelajaran_mata_pelajaran_id',
            'tingkatan_kelas',
            'fase',
            'elemen',
            'kode_tp',
            'tujuan_pembelajaran',
            'lingkup_materi',
            'urutan',
            'status',
            'created_at',
        ], $query->columns);

        $sql = strtolower($builder->toSql());
        $this->assertStringContainsString('akademik_master_tahun_ajaran_id', $sql);
        $this->assertStringContainsString('akademik_pelajaran_mata_pelajaran_id', $sql);
        $this->assertStringContainsString('tingkatan_kelas', $sql);
        $this->assertStringContainsString('fase', $sql);
        $this->assertStringContainsString('status', $sql);
        $this->assertStringContainsString('order by', $sql);

        $eagerLoads = $builder->getEagerLoads();
        $this->assertArrayHasKey('tahunAjaran', $eagerLoads);
        $this->assertArrayHasKey('mataPelajaran', $eagerLoads);
    }

    public function test_request_normalizes_inputs_and_enforces_scoped_unique_kode_tp(): void
    {
        $request = TujuanPembelajaranRequest::create('/tujuan-pembelajaran/tp', 'POST', [
            'akademik_master_tahun_ajaran_id' => ' 3 ',
            'akademik_pelajaran_mata_pelajaran_id' => ' 5 ',
            'tingkatan_kelas' => ' 1 ',
            'fase' => ' a ',
            'elemen' => '  Bilangan   Cacah ',
            'kode_tp' => '  tp-01 ',
            'tujuan_pembelajaran' => '  Peserta didik mampu membilang bilangan cacah. ',
            'lingkup_materi' => '   ',
            'urutan' => '',
            'status' => '0',
        ]);

        $prepareMethod = new ReflectionMethod(TujuanPembelajaranRequest::class, 'prepareForValidation');
        $prepareMethod->invoke($request);

        $this->assertSame('3', $request->input('akademik_master_tahun_ajaran_id'));
        $this->assertSame('5', $request->input('akademik_pelajaran_mata_pelajaran_id'));
        $this->assertSame('1', $request->input('tingkatan_kelas'));
        $this->assertSame('A', $request->input('fase'));
        $this->assertSame('Bilangan Cacah', $request->input('elemen'));
        $this->assertSame('TP-01', $request->input('kode_tp'));
        $this->assertSame('Peserta didik mampu membilang bilangan cacah.', $request->input('tujuan_pembelajaran'));
        $this->assertNull($request->input('lingkup_materi'));
        $this->assertSame(1, $request->input('urutan'));
        $this->assertFalse($request->input('status'));

        $rules = $request->rules();
        $hasScopedUniqueKode = false;
        foreach ($rules['kode_tp'] as $rule) {
            if ($rule instanceof Unique) {
                $hasScopedUniqueKode = true;
            }
        }
        $this->assertTrue($hasScopedUniqueKode);
    }

    public function test_thin_controller_delegates_crud_and_toggle_status_to_service(): void
    {
        $ta = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);
        $ta->id = 1;

        $mapel = MataPelajaran::make(['singkatan' => 'MTK', 'nama_mata_pelajaran' => 'Matematika', 'unit' => 'SD']);
        $mapel->id = 2;

        $tp = TujuanPembelajaran::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'akademik_pelajaran_mata_pelajaran_id' => 2,
            'tingkatan_kelas' => '1',
            'fase' => 'A',
            'kode_tp' => 'TP-01',
            'tujuan_pembelajaran' => 'Peserta didik mampu membilang bilangan sampai 20.',
            'urutan' => 1,
            'status' => true,
        ]);
        $tp->id = 9;

        $service = $this->createMock(TujuanPembelajaranService::class);
        $service->expects($this->once())
            ->method('getIndexData')
            ->willReturn([
                'tujuanPembelajaran' => new Collection([$tp]),
                'summary' => ['total' => 1, 'aktif' => 1, 'nonaktif' => 0, 'per_unit' => ['PG' => 0, 'TK' => 0, 'SD' => 1]],
                'filters' => ['tahun_ajaran_id' => '1', 'unit' => '', 'mata_pelajaran_id' => '', 'tingkatan_kelas' => '', 'fase' => '', 'status' => 'all', 'search' => ''],
                'tahunAjaranOptions' => new Collection([$ta]),
                'mataPelajaranOptions' => new Collection([$mapel]),
                'tingkatanByUnit' => TujuanPembelajaran::TINGKATAN_DEFAULTS_BY_UNIT,
                'tingkatanOptions' => ['1', '2'],
                'unitOptions' => MataPelajaran::UNIT_OPTIONS,
                'faseOptions' => TujuanPembelajaran::FASE_OPTIONS,
            ]);
        $service->expects($this->exactly(2))
            ->method('getFormOptions')
            ->willReturn([
                'tahunAjaranOptions' => new Collection([$ta]),
                'mataPelajaranOptions' => new Collection([$mapel]),
                'tingkatanByUnit' => TujuanPembelajaran::TINGKATAN_DEFAULTS_BY_UNIT,
                'faseOptions' => TujuanPembelajaran::FASE_OPTIONS,
                'defaults' => ['tahun_ajaran_id' => '1', 'mata_pelajaran_id' => '', 'tingkatan_kelas' => ''],
            ]);
        $service->expects($this->once())->method('store')->willReturn($tp);
        $service->expects($this->once())->method('update')->with($tp, $this->isArray())->willReturn(true);
        $service->expects($this->once())->method('delete')->with($tp)->willReturn(true);
        $service->expects($this->once())->method('toggleStatus')->with($tp)->willReturn(true);

        $controller = new TujuanPembelajaranController($service);

        $this->assertSame('admin.tujuanPembelajaran.tujuanPembelajaran.index', $controller->index(Request::create('/tujuan-pembelajaran/tp', 'GET'))->name());
        $this->assertSame('admin.tujuanPembelajaran.tujuanPembelajaran.create', $controller->create(Request::create('/tujuan-pembelajaran/tp/create', 'GET'))->name());
        $this->assertSame('admin.tujuanPembelajaran.tujuanPembelajaran.edit', $controller->edit($tp)->name());

        $formRequest = TujuanPembelajaranRequest::create('/tujuan-pembelajaran/tp', 'POST', [
            'akademik_master_tahun_ajaran_id' => 1,
            'akademik_pelajaran_mata_pelajaran_id' => 2,
            'tingkatan_kelas' => '1',
            'kode_tp' => 'TP-01',
            'tujuan_pembelajaran' => 'Peserta didik mampu membilang bilangan sampai 20.',
            'urutan' => 1,
            'status' => true,
        ]);
        $validator = app('validator')->make($formRequest->all(), [
            'akademik_master_tahun_ajaran_id' => 'required|integer',
            'akademik_pelajaran_mata_pelajaran_id' => 'required|integer',
            'tingkatan_kelas' => 'required|string',
            'kode_tp' => 'required|string',
            'tujuan_pembelajaran' => 'required|string',
            'urutan' => 'required|integer',
            'status' => 'required|boolean',
        ]);
        $validator->validate();
        $formRequest->setValidator($validator);

        $this->assertStringContainsString(
            route('tujuan-pembelajaran.index'),
            $controller->store($formRequest)->getTargetUrl()
        );
        $this->assertStringContainsString(
            route('tujuan-pembelajaran.index'),
            $controller->update($formRequest, $tp)->getTargetUrl()
        );
        $this->assertTrue($controller->destroy($tp)->isRedirect(route('tujuan-pembelajaran.index')));
        $this->assertTrue($controller->toggleStatus($tp)->isRedirect(route('tujuan-pembelajaran.index')));
    }

    public function test_views_render_without_lazy_loading(): void
    {
        view()->share('errors', new ViewErrorBag);

        $ta = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);
        $ta->id = 1;

        $mapel = MataPelajaran::make([
            'singkatan' => 'MTK',
            'nama_mata_pelajaran' => 'Matematika',
            'unit' => 'SD',
            'status' => true,
        ]);
        $mapel->id = 2;

        $tp = TujuanPembelajaran::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'akademik_pelajaran_mata_pelajaran_id' => 2,
            'tingkatan_kelas' => '1',
            'fase' => 'A',
            'elemen' => 'Bilangan',
            'kode_tp' => 'MTK.1.1',
            'tujuan_pembelajaran' => 'Peserta didik mampu membilang bilangan cacah sampai 20.',
            'lingkup_materi' => 'Bilangan Cacah 1-20',
            'urutan' => 1,
            'status' => true,
        ]);
        $tp->id = 25;
        $tp->setRelation('tahunAjaran', $ta);
        $tp->setRelation('mataPelajaran', $mapel);

        Model::preventLazyLoading(true);

        try {
            $indexHtml = view('admin.tujuanPembelajaran.tujuanPembelajaran.index', [
                'tujuanPembelajaran' => new Collection([$tp]),
                'summary' => ['total' => 1, 'aktif' => 1, 'nonaktif' => 0, 'per_unit' => ['PG' => 0, 'TK' => 0, 'SD' => 1]],
                'filters' => ['tahun_ajaran_id' => '1', 'unit' => '', 'mata_pelajaran_id' => '', 'tingkatan_kelas' => '', 'fase' => '', 'status' => 'all', 'search' => ''],
                'tahunAjaranOptions' => new Collection([$ta]),
                'mataPelajaranOptions' => new Collection([$mapel]),
                'tingkatanByUnit' => TujuanPembelajaran::TINGKATAN_DEFAULTS_BY_UNIT,
                'tingkatanOptions' => ['1', '2'],
                'unitOptions' => MataPelajaran::UNIT_OPTIONS,
                'faseOptions' => TujuanPembelajaran::FASE_OPTIONS,
            ])->render();

            $createHtml = view('admin.tujuanPembelajaran.tujuanPembelajaran.create', [
                'tahunAjaranOptions' => new Collection([$ta]),
                'mataPelajaranOptions' => new Collection([$mapel]),
                'tingkatanByUnit' => TujuanPembelajaran::TINGKATAN_DEFAULTS_BY_UNIT,
                'faseOptions' => TujuanPembelajaran::FASE_OPTIONS,
                'defaults' => ['tahun_ajaran_id' => '1', 'mata_pelajaran_id' => '2', 'tingkatan_kelas' => '1'],
            ])->render();

            $editHtml = view('admin.tujuanPembelajaran.tujuanPembelajaran.edit', [
                'tujuanPembelajaran' => $tp,
                'tahunAjaranOptions' => new Collection([$ta]),
                'mataPelajaranOptions' => new Collection([$mapel]),
                'tingkatanByUnit' => TujuanPembelajaran::TINGKATAN_DEFAULTS_BY_UNIT,
                'faseOptions' => TujuanPembelajaran::FASE_OPTIONS,
                'defaults' => ['tahun_ajaran_id' => '', 'mata_pelajaran_id' => '', 'tingkatan_kelas' => ''],
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('MTK.1.1', $indexHtml);
        $this->assertStringContainsString('Matematika', $indexHtml);
        $this->assertStringContainsString('Bilangan', $indexHtml);
        $this->assertStringContainsString('Bilangan Cacah 1-20', $indexHtml);
        $this->assertStringContainsString(route('tujuan-pembelajaran.toggle-status', sqidFor('tujuan-pembelajaran.toggle-status', 25)), $indexHtml);
        $this->assertStringContainsString(route('tujuan-pembelajaran.edit', sqidFor('tujuan-pembelajaran.edit', 25)), $indexHtml);
        $this->assertStringContainsString(route('tujuan-pembelajaran.destroy', sqidFor('tujuan-pembelajaran.destroy', 25)), $indexHtml);
        $this->assertStringContainsString(route('tujuan-pembelajaran.export'), $indexHtml);
        $this->assertStringContainsString(route('tujuan-pembelajaran.template'), $indexHtml);
        $this->assertStringContainsString(route('tujuan-pembelajaran.import'), $indexHtml);
        $this->assertStringContainsString('tpImportModal', $indexHtml);

        $this->assertStringContainsString(route('tujuan-pembelajaran.store'), $createHtml);
        $this->assertStringContainsString('.tp-form-card {', $createHtml);
        $this->assertStringContainsString('tp-form-shell', $createHtml);
        $this->assertStringContainsString('tp-grid-12', $createHtml);
        $this->assertStringNotContainsString('col-md-1', $createHtml);
        $this->assertStringContainsString('name="akademik_master_tahun_ajaran_id"', $createHtml);
        $this->assertStringContainsString('name="akademik_pelajaran_mata_pelajaran_id"', $createHtml);
        $this->assertStringContainsString('name="tingkatan_kelas"', $createHtml);
        $this->assertStringContainsString('name="kode_tp"', $createHtml);
        $this->assertStringContainsString('name="tujuan_pembelajaran"', $createHtml);

        $this->assertStringContainsString(route('tujuan-pembelajaran.update', sqidFor('tujuan-pembelajaran.update', 25)), $editHtml);
        $this->assertStringContainsString('tp-form-shell', $editHtml);
        $this->assertStringContainsString('tp-grid-12', $editHtml);
        $this->assertStringNotContainsString('col-md-1', $editHtml);
        $this->assertStringContainsString('Bilangan Cacah 1-20', $editHtml);
        $this->assertStringContainsString(route('tujuan-pembelajaran.index'), view('layouts.sidebar')->render());
    }

    public function test_export_and_template_classes_map_rows_without_lazy_loading_and_expose_expected_sheets(): void
    {
        $ta = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);
        $ta->id = 1;

        $mapel = MataPelajaran::make([
            'singkatan' => 'MTK',
            'nama_mata_pelajaran' => 'Matematika',
            'unit' => 'SD',
            'status' => true,
        ]);
        $mapel->id = 2;

        $tp = TujuanPembelajaran::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'akademik_pelajaran_mata_pelajaran_id' => 2,
            'tingkatan_kelas' => '1',
            'fase' => 'A',
            'elemen' => 'Bilangan',
            'kode_tp' => 'MTK.1.1',
            'tujuan_pembelajaran' => 'Peserta didik mampu membilang bilangan cacah sampai 20.',
            'lingkup_materi' => 'Bilangan Cacah 1-20',
            'urutan' => 1,
            'status' => true,
        ]);
        $tp->setRelation('tahunAjaran', $ta);
        $tp->setRelation('mataPelajaran', $mapel);

        $export = new TujuanPembelajaranExport(['unit' => 'SD']);

        Model::preventLazyLoading(true);
        try {
            $mapped = $export->map($tp);
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertSame(TujuanPembelajaranExport::HEADINGS, $export->headings());
        $this->assertCount(13, $export->headings());
        $this->assertSame('Tujuan Pembelajaran', $export->title());
        $this->assertArrayHasKey(1, TujuanPembelajaranExport::headerStyles());
        $this->assertSame([
            '2026/2027',
            '1',
            'SD',
            'MTK',
            'Matematika',
            '1',
            'A',
            'Bilangan',
            'MTK.1.1',
            'Peserta didik mampu membilang bilangan cacah sampai 20.',
            'Bilangan Cacah 1-20',
            1,
            'Aktif',
        ], $mapped);

        $template = new TujuanPembelajaranTemplateExport;
        $sheets = $template->sheets();

        $this->assertSame([0, 1], array_keys($sheets));
        $this->assertInstanceOf(TemplateTpSheet::class, $sheets[0]);
        $this->assertInstanceOf(ReferensiTpSheet::class, $sheets[1]);
        $this->assertSame(TujuanPembelajaranExport::HEADINGS, $sheets[0]->headings());
        $this->assertSame('Template TP', $sheets[0]->title());
    }

    public function test_import_wrapper_targets_first_sheet_only_and_validates_request_rules(): void
    {
        $import = new TujuanPembelajaranImport(5);
        $sheets = $import->sheets();

        $this->assertSame([0], array_keys($sheets));
        $this->assertInstanceOf(TujuanPembelajaranSheetImport::class, $sheets[0]);
        $this->assertSame(0, $import->getCreatedCount());
        $this->assertSame(0, $import->getUpdatedCount());
        $this->assertSame([], $import->getErrors());

        $request = new ImportTujuanPembelajaranRequest;
        $this->assertTrue($request->authorize());

        $rules = $request->rules();
        $this->assertSame(['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'], $rules['file']);

        $hasExistsRule = false;
        foreach ($rules['default_tahun_ajaran_id'] as $rule) {
            if ($rule instanceof Exists) {
                $hasExistsRule = true;
            }
        }
        $this->assertTrue($hasExistsRule);
    }

    public function test_controller_export_template_and_import_delegate_to_maatwebsite_excel(): void
    {
        Excel::fake();
        CarbonImmutable::setTestNow('2026-11-14 12:00:00');

        try {
            $service = $this->createMock(TujuanPembelajaranService::class);
            $controller = new TujuanPembelajaranController($service);

            $controller->export(Request::create('/tujuan-pembelajaran/tp/export', 'GET', ['unit' => 'SD']));
            Excel::assertDownloaded('tujuan-pembelajaran-20261114-120000.xlsx', function ($export): bool {
                return $export instanceof TujuanPembelajaranExport;
            });

            $controller->template();
            Excel::assertDownloaded('template-import-tujuan-pembelajaran.xlsx', function ($export): bool {
                return $export instanceof TujuanPembelajaranTemplateExport;
            });

            $uploadedFile = UploadedFile::fake()->create(
                'tp-sd.xlsx',
                8,
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            );

            $importRequest = ImportTujuanPembelajaranRequest::create('/tujuan-pembelajaran/tp/import', 'POST', [
                'default_tahun_ajaran_id' => 1,
            ], [], ['file' => $uploadedFile]);
            $validator = app('validator')->make([
                'file' => $uploadedFile,
                'default_tahun_ajaran_id' => 1,
            ], [
                'file' => ['required', 'file'],
                'default_tahun_ajaran_id' => ['nullable', 'integer'],
            ]);
            $validator->validate();
            $importRequest->setValidator($validator);

            $response = $controller->import($importRequest);

            Excel::assertImported('tp-sd.xlsx', function ($import): bool {
                return $import instanceof TujuanPembelajaranImport;
            });
            $this->assertTrue($response->isRedirect(route('tujuan-pembelajaran.index')));
        } finally {
            CarbonImmutable::setTestNow();
        }
    }
}
