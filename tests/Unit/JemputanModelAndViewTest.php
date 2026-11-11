<?php

namespace Tests\Unit;

use App\Http\Requests\Admin\DataMaster\JemputanRequest;
use App\Models\Admin\DataMaster\Jemputan;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Services\Admin\DataMaster\JemputanService;
use DOMDocument;
use DOMXPath;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class JemputanModelAndViewTest extends TestCase
{
    public function test_model_metadata_casts_and_relationship_keys_are_configured(): void
    {
        $jemputan = new Jemputan;

        $this->assertSame('akademik_master_jemputan', $jemputan->getTable());
        $this->assertSame([
            'akademik_master_tahun_ajaran_id',
            'karyawan_nipy',
            'harga_pp',
            'harga_setengah',
        ], $jemputan->getFillable());

        $casts = $jemputan->getCasts();
        $this->assertSame('integer', $casts['akademik_master_tahun_ajaran_id']);
        $this->assertSame('integer', $casts['harga_pp']);
        $this->assertSame('integer', $casts['harga_setengah']);

        $tahunAjaranRelation = $jemputan->tahunAjaran();
        $this->assertSame('akademik_master_tahun_ajaran_id', $tahunAjaranRelation->getForeignKeyName());
        $this->assertSame('id', $tahunAjaranRelation->getOwnerKeyName());

        $petugasRelation = $jemputan->petugas();
        $this->assertSame('karyawan_nipy', $petugasRelation->getForeignKeyName());
        $this->assertSame('nipy', $petugasRelation->getOwnerKeyName());

        $karyawanAliasRelation = $jemputan->karyawan();
        $this->assertSame('karyawan_nipy', $karyawanAliasRelation->getForeignKeyName());
        $this->assertSame('nipy', $karyawanAliasRelation->getOwnerKeyName());
    }

    #[TestWith([0, 'Gratis'])]
    #[TestWith([-1000, 'Gratis'])]
    #[TestWith([150000, 'Rp 150.000'])]
    #[TestWith([1250000, 'Rp 1.250.000'])]
    public function test_harga_formatted_accessors_format_zero_and_positive_amounts(int $nominal, string $expected): void
    {
        $jemputan = Jemputan::make([
            'harga_pp' => $nominal,
            'harga_setengah' => $nominal,
        ]);

        $this->assertSame($expected, $jemputan->harga_pp_formatted);
        $this->assertSame($expected, $jemputan->harga_setengah_formatted);
    }

    public function test_service_index_query_configures_minimal_columns_and_eager_loads(): void
    {
        $service = new JemputanService;
        $builder = $service->buildIndexQuery();
        $query = $builder->getQuery();

        $this->assertSame([
            'id',
            'akademik_master_tahun_ajaran_id',
            'karyawan_nipy',
            'harga_pp',
            'harga_setengah',
        ], $query->columns);

        $eagerLoads = $builder->getEagerLoads();
        $this->assertArrayHasKey('tahunAjaran', $eagerLoads);
        $this->assertArrayHasKey('petugas', $eagerLoads);

        $tahunAjaranRelationQuery = TahunAjaran::query();
        ($eagerLoads['tahunAjaran'])($tahunAjaranRelationQuery);
        $this->assertSame(
            ['id', 'nama_tahun_ajaran', 'semester', 'is_active'],
            $tahunAjaranRelationQuery->getQuery()->columns
        );

        $petugasRelationQuery = Karyawan::query();
        ($eagerLoads['petugas'])($petugasRelationQuery);
        $this->assertSame(
            ['nipy', 'nama_lengkap', 'gelar'],
            $petugasRelationQuery->getQuery()->columns
        );
    }

    public function test_request_validation_rules_require_all_four_columns(): void
    {
        $request = new JemputanRequest;
        $rules = $request->rules();
        $messages = $request->messages();

        $this->assertTrue($request->authorize());
        $this->assertSame([
            'required',
            'integer',
            'exists:akademik_master_tahun_ajaran,id',
        ], $rules['akademik_master_tahun_ajaran_id']);
        $this->assertSame([
            'required',
            'string',
            'max:255',
            'exists:kepegawaian_master_karyawan,nipy',
        ], $rules['karyawan_nipy']);
        $this->assertSame(['required', 'integer', 'min:0'], $rules['harga_pp']);
        $this->assertSame(['required', 'integer', 'min:0'], $rules['harga_setengah']);
        $this->assertArrayHasKey('karyawan_nipy.required', $messages);
        $this->assertArrayHasKey('harga_pp.min', $messages);
        $this->assertArrayHasKey('harga_setengah.min', $messages);
    }

    public function test_index_view_renders_preloaded_relations_without_lazy_loading(): void
    {
        $tahunAjaran = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);

        $petugas = Karyawan::make([
            'nipy' => '19900101-1',
            'nama_lengkap' => 'Agus Setiawan',
            'gelar' => 'S.Pd',
        ]);

        $paid = Jemputan::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'karyawan_nipy' => '19900101-1',
            'harga_pp' => 250000,
            'harga_setengah' => 150000,
        ]);
        $paid->id = 1;
        $paid->setRelation('tahunAjaran', $tahunAjaran);
        $paid->setRelation('petugas', $petugas);

        $free = Jemputan::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'karyawan_nipy' => '19900101-1',
            'harga_pp' => 0,
            'harga_setengah' => 0,
        ]);
        $free->id = 2;
        $free->setRelation('tahunAjaran', $tahunAjaran);
        $free->setRelation('petugas', $petugas);

        Model::preventLazyLoading(true);

        try {
            $rendered = view('admin.dataMaster.jemputan.index', [
                'jemputan' => new Collection([$paid, $free]),
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('Agus Setiawan, S.Pd', $rendered);
        $this->assertStringContainsString('NIPY: 19900101-1', $rendered);
        $this->assertStringContainsString('2026/2027', $rendered);
        $this->assertStringContainsString('Rp 250.000', $rendered);
        $this->assertStringContainsString('Rp 150.000', $rendered);
        $this->assertStringContainsString('Gratis', $rendered);
        $this->assertStringContainsString(route('jemputan.index'), view('layouts.sidebar')->render());
    }

    public function test_index_view_does_not_render_colspan_row_when_empty_for_datatables_compatibility(): void
    {
        $html = view('admin.dataMaster.jemputan.index', [
            'jemputan' => new Collection,
        ])->render();

        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($document);
        $headerCells = $xpath->query('//table[@id="myTable3"]/thead/tr/th');
        $bodyRows = $xpath->query('//table[@id="myTable3"]/tbody/tr');
        $colspanCells = $xpath->query('//table[@id="myTable3"]/tbody//*[@colspan]');

        $this->assertNotFalse($headerCells);
        $this->assertNotFalse($bodyRows);
        $this->assertNotFalse($colspanCells);
        $this->assertSame(6, $headerCells->length);
        $this->assertSame(0, $bodyRows->length);
        $this->assertSame(0, $colspanCells->length);
    }

    public function test_create_and_edit_views_render_expected_form_fields_and_actions(): void
    {
        view()->share('errors', new ViewErrorBag);

        $tahunAjaran = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);
        $tahunAjaran->id = 10;

        $petugas = Karyawan::make([
            'nipy' => '19900101-1',
            'nama_lengkap' => 'Agus Setiawan',
            'gelar' => 'S.Pd',
        ]);

        $createHtml = view('admin.dataMaster.jemputan.create', [
            'tahunAjaran' => new Collection([$tahunAjaran]),
            'karyawan' => new Collection([$petugas]),
        ])->render();

        $this->assertStringContainsString(route('jemputan.store'), $createHtml);
        $this->assertStringContainsString('Agus Setiawan, S.Pd (19900101-1)', $createHtml);
        $this->assertStringContainsString('name="harga_pp"', $createHtml);
        $this->assertStringContainsString('name="harga_setengah"', $createHtml);

        $jemputan = Jemputan::make([
            'akademik_master_tahun_ajaran_id' => 10,
            'karyawan_nipy' => '19900101-1',
            'harga_pp' => 250000,
            'harga_setengah' => 150000,
        ]);
        $jemputan->id = 5;

        Model::preventLazyLoading(true);

        try {
            $editHtml = view('admin.dataMaster.jemputan.edit', [
                'jemputan' => $jemputan,
                'tahunAjaran' => new Collection([$tahunAjaran]),
                'karyawan' => new Collection([$petugas]),
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString(route('jemputan.update', 5), $editHtml);
        $this->assertStringContainsString('name="_method" value="PUT"', $editHtml);
        $this->assertStringContainsString('250000', $editHtml);
        $this->assertStringContainsString('150000', $editHtml);
    }
}
