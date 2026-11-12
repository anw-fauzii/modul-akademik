<?php

namespace Tests\Unit;

use App\Http\Requests\Admin\DataMaster\JemputanRequest;
use App\Models\Admin\DataMaster\AnggotaJemputan;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Jemputan;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Services\Admin\DataMaster\JemputanService;
use DOMDocument;
use DOMXPath;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\MessageBag;
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

        $anggotaJemputanRelation = $jemputan->anggotaJemputan();
        $this->assertInstanceOf(HasMany::class, $anggotaJemputanRelation);
        $this->assertSame('akademik_master_jemputan_id', $anggotaJemputanRelation->getForeignKeyName());
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
        ], array_slice($query->columns, 0, 5));
        $this->assertStringContainsString('akademik_master_anggota_jemputan', $builder->toSql());
        $this->assertStringContainsString('anggota_jemputan_count', $builder->toSql());

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
        $paid->setAttribute('anggota_jemputan_count', 12);
        $paid->setRelation('tahunAjaran', $tahunAjaran);
        $paid->setRelation('petugas', $petugas);

        $free = Jemputan::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'karyawan_nipy' => '19900101-1',
            'harga_pp' => 0,
            'harga_setengah' => 0,
        ]);
        $free->id = 2;
        $free->setAttribute('anggota_jemputan_count', 0);
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
        $this->assertStringContainsString('12 Siswa', $rendered);
        $this->assertStringContainsString('0 Siswa', $rendered);
        $this->assertStringContainsString('Gratis', $rendered);
        $this->assertStringContainsString(route('jemputan.show', 1), $rendered);
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
        $this->assertSame(7, $headerCells->length);
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

    public function test_show_view_renders_members_and_modal_picker_without_lazy_loading(): void
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

        $jemputan = Jemputan::make([
            'akademik_master_tahun_ajaran_id' => 10,
            'karyawan_nipy' => '19900101-1',
            'harga_pp' => 250000,
            'harga_setengah' => 150000,
        ]);
        $jemputan->id = 5;
        $jemputan->setRelation('tahunAjaran', $tahunAjaran);
        $jemputan->setRelation('petugas', $petugas);

        $kelasMember = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 10,
            'nama_kelas' => '7 Al-Khawarizmi',
            'jenjang_kelas' => 'SMP',
            'tingkatan_kelas' => '7',
        ]);
        $kelasMember->id = 21;

        $memberSiswa = Siswa::make([
            'nis' => '20260011',
            'nisn' => '0011223344',
            'nama_lengkap' => 'Aisyah Putri',
            'jenis_kelamin' => 'P',
            'status' => 'Aktif',
        ]);
        $memberAk = AnggotaKelas::make([
            'siswa_nis' => '20260011',
            'akademik_master_kelas_id' => 21,
            'pendaftaran' => 'Siswa Baru',
        ]);
        $memberAk->id = 70;
        $memberAk->setRelation('siswa', $memberSiswa);
        $memberAk->setRelation('kelas', $kelasMember);

        $member = AnggotaJemputan::make([
            'anggota_kelas_id' => 70,
            'akademik_master_jemputan_id' => 5,
            'pendaftaran' => 'Baru',
            'keterangan' => 'Antar Jemput PP',
        ]);
        $member->id = 55;
        $member->setRelation('anggotaKelas', $memberAk);
        $member->setRelation('jemputan', $jemputan);

        $candidateSiswa = Siswa::make([
            'nis' => '20260022',
            'nisn' => '0099887766',
            'nama_lengkap' => 'Bagas Pratama',
            'jenis_kelamin' => 'L',
            'status' => 'Aktif',
        ]);
        $candidateSiswa->setRelation('anggotaJemputan', new Collection);

        $candidateAk = AnggotaKelas::make([
            'siswa_nis' => '20260022',
            'akademik_master_kelas_id' => 21,
            'pendaftaran' => 'Naik Kelas',
        ]);
        $candidateAk->id = 71;
        $candidateAk->setRelation('siswa', $candidateSiswa);
        $candidateAk->setRelation('kelas', $kelasMember);

        $payload = [
            'jemputan' => $jemputan,
            'anggotaJemputan' => new Collection([$member]),
            'stats' => [
                'total' => 1,
                'laki_laki' => 0,
                'perempuan' => 1,
            ],
            'kandidatAnggotaKelas' => new Collection([$candidateAk]),
            'excludedTahunAjaranIds' => [10],
            'hiddenRegisteredCount' => 2,
            'hiddenInactiveCount' => 1,
            'hiddenWithoutKelasCount' => 3,
        ];

        Model::preventLazyLoading(true);

        try {
            $html = view('admin.dataMaster.jemputan.show', $payload)->render();

            $errorBag = new ViewErrorBag;
            $errorBag->put('default', new MessageBag(['anggota_kelas_id' => 'Pilih minimal satu siswa anggota kelas.']));
            view()->share('errors', $errorBag);

            $htmlWithErrors = view('admin.dataMaster.jemputan.show', $payload)->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('Agus Setiawan, S.Pd', $html);
        $this->assertStringContainsString('Rp 250.000', $html);
        $this->assertStringContainsString('Rp 150.000', $html);
        $this->assertStringContainsString('Aisyah Putri', $html);
        $this->assertStringContainsString('7 Al-Khawarizmi', $html);
        $this->assertStringContainsString('Antar Jemput PP', $html);
        $this->assertStringContainsString('Bagas Pratama', $html);
        $this->assertStringContainsString('name="anggota_kelas_id[]" value="71"', $html);
        $this->assertStringContainsString(route('anggota-jemputan.store'), $html);
        $this->assertStringContainsString('name="redirect_to_jemputan" value="5"', $html);
        $this->assertStringContainsString(route('anggota-jemputan.edit', ['anggota_jemputan' => 55, 'from_jemputan' => 5]), $html);
        $this->assertStringContainsString(route('anggota-jemputan.destroy', 55), $html);
        $this->assertStringContainsString('id="openTambahAnggotaModalBtn"', $html);
        $this->assertStringContainsString('id="tambahAnggotaModal"', $html);
        $this->assertStringNotContainsString('ak-modal-overlay is-open', $html);
        $this->assertStringNotContainsString('id="filterKelasSebelumnya"', $html);
        $this->assertStringNotContainsString('<select name="akademik_master_jemputan_id"', $html);

        $this->assertStringContainsString('ak-modal-overlay is-open', $htmlWithErrors);
        $this->assertStringContainsString('Pilih minimal satu siswa anggota kelas.', $htmlWithErrors);
    }

    public function test_show_view_does_not_render_colspan_row_when_empty_for_datatables_compatibility(): void
    {
        view()->share('errors', new ViewErrorBag);

        $jemputan = Jemputan::make([
            'akademik_master_tahun_ajaran_id' => 10,
            'karyawan_nipy' => '19900101-1',
            'harga_pp' => 0,
            'harga_setengah' => 0,
        ]);
        $jemputan->id = 5;
        $jemputan->setRelation('tahunAjaran', null);
        $jemputan->setRelation('petugas', null);

        $html = view('admin.dataMaster.jemputan.show', [
            'jemputan' => $jemputan,
            'anggotaJemputan' => new Collection,
            'stats' => [
                'total' => 0,
                'laki_laki' => 0,
                'perempuan' => 0,
            ],
            'kandidatAnggotaKelas' => new Collection,
            'excludedTahunAjaranIds' => [10],
            'hiddenRegisteredCount' => 0,
            'hiddenInactiveCount' => 0,
            'hiddenWithoutKelasCount' => 0,
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
        $this->assertSame(8, $headerCells->length);
        $this->assertSame(0, $bodyRows->length);
        $this->assertSame(0, $colspanCells->length);
    }
}
