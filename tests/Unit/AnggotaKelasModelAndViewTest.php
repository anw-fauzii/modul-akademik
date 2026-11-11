<?php

namespace Tests\Unit;

use App\Http\Requests\Admin\DataMaster\AnggotaKelasRequest;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Services\Admin\DataMaster\AnggotaKelasService;
use DOMDocument;
use DOMXPath;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\Rule;
use PHPUnit\Framework\Attributes\TestWith;
use ReflectionMethod;
use Tests\TestCase;

class AnggotaKelasModelAndViewTest extends TestCase
{
    public function test_model_metadata_casts_and_relationship_keys_are_configured(): void
    {
        $anggotaKelas = new AnggotaKelas;

        $this->assertSame('akademik_master_anggota_kelas', $anggotaKelas->getTable());
        $this->assertSame([
            'siswa_nis',
            'akademik_master_kelas_id',
            'pendaftaran',
        ], $anggotaKelas->getFillable());

        $casts = $anggotaKelas->getCasts();
        $this->assertSame('integer', $casts['akademik_master_kelas_id']);

        $siswaRelation = $anggotaKelas->siswa();
        $this->assertSame('siswa_nis', $siswaRelation->getForeignKeyName());
        $this->assertSame('nis', $siswaRelation->getOwnerKeyName());

        $kelasRelation = $anggotaKelas->kelas();
        $this->assertSame('akademik_master_kelas_id', $kelasRelation->getForeignKeyName());
        $this->assertSame('id', $kelasRelation->getOwnerKeyName());

        $inverseSiswaRelation = (new Siswa)->anggotaKelas();
        $this->assertSame('siswa_nis', $inverseSiswaRelation->getForeignKeyName());
        $this->assertSame('nis', $inverseSiswaRelation->getLocalKeyName());

        $inverseKelasRelation = (new Kelas)->anggotaKelas();
        $this->assertSame('akademik_master_kelas_id', $inverseKelasRelation->getForeignKeyName());
        $this->assertSame('id', $inverseKelasRelation->getLocalKeyName());
    }

    #[TestWith(['Siswa Baru', 'badge-success'])]
    #[TestWith(['Naik Kelas', 'badge-primary'])]
    #[TestWith(['Pindahan', 'badge-warning text-dark'])]
    #[TestWith(['Mengulang', 'badge-danger'])]
    #[TestWith(['Khusus', 'badge-secondary'])]
    public function test_pendaftaran_badge_class_maps_registration_types(string $pendaftaran, string $expectedClass): void
    {
        $anggotaKelas = AnggotaKelas::make(['pendaftaran' => $pendaftaran]);

        $this->assertSame($expectedClass, $anggotaKelas->pendaftaran_badge_class);
    }

    public function test_service_index_query_configures_minimal_columns_eager_loads_and_filters(): void
    {
        $service = new AnggotaKelasService;
        $builder = $service->buildIndexQuery(5, 12);
        $query = $builder->getQuery();

        $this->assertSame([
            'id',
            'siswa_nis',
            'akademik_master_kelas_id',
            'pendaftaran',
        ], $query->columns);

        $sql = $builder->toSql();
        $this->assertStringContainsString('akademik_master_kelas', $sql);
        $this->assertStringContainsString('akademik_master_tahun_ajaran_id', $sql);
        $this->assertStringContainsString('akademik_master_kelas_id', $sql);
        $this->assertSame([5, 12], $builder->getBindings());

        $eagerLoads = $builder->getEagerLoads();
        $this->assertArrayHasKey('siswa', $eagerLoads);
        $this->assertArrayHasKey('kelas', $eagerLoads);
        $this->assertArrayHasKey('kelas.tahunAjaran', $eagerLoads);
        $this->assertArrayHasKey('kelas.waliKelas', $eagerLoads);

        $siswaQuery = Siswa::query();
        ($eagerLoads['siswa'])($siswaQuery);
        $this->assertSame(
            ['nis', 'nama_lengkap', 'nisn', 'jenis_kelamin', 'status'],
            $siswaQuery->getQuery()->columns
        );

        $kelasQuery = Kelas::query();
        ($eagerLoads['kelas'])($kelasQuery);
        $this->assertSame(
            ['id', 'akademik_master_tahun_ajaran_id', 'karyawan_nipy', 'nama_kelas', 'jenjang_kelas', 'tingkatan_kelas'],
            $kelasQuery->getQuery()->columns
        );

        $tahunAjaranQuery = TahunAjaran::query();
        ($eagerLoads['kelas.tahunAjaran'])($tahunAjaranQuery);
        $this->assertSame(
            ['id', 'nama_tahun_ajaran', 'semester', 'is_active'],
            $tahunAjaranQuery->getQuery()->columns
        );

        $waliKelasQuery = Karyawan::query();
        ($eagerLoads['kelas.waliKelas'])($waliKelasQuery);
        $this->assertSame(
            ['nipy', 'nama_lengkap', 'gelar'],
            $waliKelasQuery->getQuery()->columns
        );
    }

    public function test_request_validation_rules_support_scalar_and_batch_payloads(): void
    {
        $batchRequest = AnggotaKelasRequest::create('/data-master/anggota-kelas', 'POST', [
            'siswa_nis' => ['2026001', '2026002'],
            'akademik_master_kelas_id' => 1,
            'pendaftaran' => 'Siswa Baru',
        ]);
        $batchRules = $batchRequest->rules();

        $this->assertTrue($batchRequest->authorize());
        $this->assertSame(['required', 'array', 'min:1'], $batchRules['siswa_nis']);
        $this->assertSame([
            'required',
            'string',
            'max:50',
            'distinct',
            'exists:akademik_master_siswa,nis',
        ], $batchRules['siswa_nis.*']);
        $this->assertSame([
            'required',
            'integer',
            'exists:akademik_master_kelas,id',
        ], $batchRules['akademik_master_kelas_id']);
        $this->assertEquals([
            'required',
            'string',
            'max:100',
            Rule::in(AnggotaKelas::PENDAFTARAN_OPTIONS),
        ], $batchRules['pendaftaran']);

        $singleRequest = AnggotaKelasRequest::create('/data-master/anggota-kelas/1', 'PUT', [
            'siswa_nis' => '2026001',
            'akademik_master_kelas_id' => 1,
            'pendaftaran' => 'Naik Kelas',
        ]);
        $singleRules = $singleRequest->rules();

        $this->assertSame([
            'required',
            'string',
            'max:50',
            'exists:akademik_master_siswa,nis',
        ], $singleRules['siswa_nis']);
        $this->assertArrayNotHasKey('siswa_nis.*', $singleRules);

        $messages = $singleRequest->messages();
        $this->assertArrayHasKey('siswa_nis.required', $messages);
        $this->assertArrayHasKey('akademik_master_kelas_id.exists', $messages);
        $this->assertArrayHasKey('pendaftaran.in', $messages);
    }

    public function test_index_view_renders_preloaded_relations_without_lazy_loading(): void
    {
        $tahunAjaran = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);
        $tahunAjaran->id = 10;

        $waliKelas = Karyawan::make([
            'nipy' => '19900101-1',
            'nama_lengkap' => 'Budi Santoso',
            'gelar' => 'S.Pd',
        ]);

        $kelas = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 10,
            'karyawan_nipy' => '19900101-1',
            'nama_kelas' => '1 Al-Fatihah',
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => '1',
        ]);
        $kelas->id = 20;
        $kelas->setRelation('tahunAjaran', $tahunAjaran);
        $kelas->setRelation('waliKelas', $waliKelas);

        $siswa = Siswa::make([
            'nis' => '20260001',
            'nama_lengkap' => 'Ahmad Fauzi',
            'nisn' => '0012345678',
            'jenis_kelamin' => 'L',
            'status' => 'Aktif',
        ]);

        $anggotaKelas = AnggotaKelas::make([
            'siswa_nis' => '20260001',
            'akademik_master_kelas_id' => 20,
            'pendaftaran' => 'Siswa Baru',
        ]);
        $anggotaKelas->id = 30;
        $anggotaKelas->setRelation('siswa', $siswa);
        $anggotaKelas->setRelation('kelas', $kelas);

        Model::preventLazyLoading(true);

        try {
            $rendered = view('admin.dataMaster.anggotaKelas.index', [
                'anggotaKelas' => new Collection([$anggotaKelas]),
                'selectedTahunAjaranId' => 10,
                'selectedKelasId' => 20,
                'tahunAjaran' => new Collection([$tahunAjaran]),
                'kelas' => new Collection([$kelas]),
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('Ahmad Fauzi', $rendered);
        $this->assertStringContainsString('20260001', $rendered);
        $this->assertStringContainsString('0012345678', $rendered);
        $this->assertStringContainsString('1 Al-Fatihah', $rendered);
        $this->assertStringContainsString('2026/2027', $rendered);
        $this->assertStringContainsString('Budi Santoso, S.Pd', $rendered);
        $this->assertStringContainsString('badge-success', $rendered);
        $this->assertStringContainsString(route('anggota-kelas.edit', 30), $rendered);
        $this->assertStringContainsString(route('anggota-kelas.destroy', 30), $rendered);
        $this->assertStringContainsString(route('anggota-kelas.index'), view('layouts.sidebar')->render());
    }

    public function test_index_view_does_not_render_colspan_row_when_empty_for_datatables_compatibility(): void
    {
        $html = view('admin.dataMaster.anggotaKelas.index', [
            'anggotaKelas' => new Collection,
            'selectedTahunAjaranId' => null,
            'selectedKelasId' => null,
            'tahunAjaran' => new Collection,
            'kelas' => new Collection,
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

    public function test_create_and_edit_views_render_expected_form_fields_without_lazy_loading(): void
    {
        view()->share('errors', new ViewErrorBag);

        $tahunAjaranLama = TahunAjaran::make([
            'nama_tahun_ajaran' => '2025/2026',
            'semester' => '2',
            'is_active' => false,
        ]);
        $tahunAjaranLama->id = 9;

        $tahunAjaran = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);
        $tahunAjaran->id = 10;

        $kelasLama = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 9,
            'nama_kelas' => '1 Abu Bakar',
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => '1',
        ]);
        $kelasLama->id = 19;
        $kelasLama->setRelation('tahunAjaran', $tahunAjaranLama);

        $kelas = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 10,
            'nama_kelas' => '2 Al-Fatihah',
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => '2',
        ]);
        $kelas->id = 20;
        $kelas->setRelation('tahunAjaran', $tahunAjaran);

        $riwayatLama = AnggotaKelas::make([
            'siswa_nis' => '20260001',
            'akademik_master_kelas_id' => 19,
            'pendaftaran' => 'Siswa Baru',
        ]);
        $riwayatLama->id = 4;
        $riwayatLama->setRelation('kelas', $kelasLama);

        $siswaDenganRiwayat = Siswa::make([
            'nis' => '20260001',
            'nama_lengkap' => 'Ahmad Fauzi',
            'jenis_kelamin' => 'L',
            'nisn' => '0012345678',
            'status' => 'Aktif',
        ]);
        $siswaDenganRiwayat->setRelation('anggotaKelas', new Collection([$riwayatLama]));
        $siswaDenganRiwayat->setRelation('kelas', null);

        $siswaBaru = Siswa::make([
            'nis' => '20260002',
            'nama_lengkap' => 'Aisyah Putri',
            'jenis_kelamin' => 'P',
            'nisn' => '0012345679',
            'status' => 'Aktif',
        ]);
        $siswaBaru->setRelation('anggotaKelas', new Collection);
        $siswaBaru->setRelation('kelas', null);

        $this->assertSame([9], $siswaDenganRiwayat->registeredTahunAjaranIds());
        $this->assertSame('1 Abu Bakar', $siswaDenganRiwayat->resolvePreviousKelasSummary()['nama_kelas'] ?? null);
        $this->assertNull($siswaBaru->resolvePreviousKelasSummary());

        Model::preventLazyLoading(true);

        try {
            $createHtml = view('admin.dataMaster.anggotaKelas.create', [
                'kelas' => new Collection([$kelas]),
                'siswa' => new Collection([$siswaDenganRiwayat, $siswaBaru]),
                'excludedTahunAjaranIds' => [10],
                'hiddenRegisteredCount' => 4,
                'hiddenInactiveCount' => 3,
            ])->render();

            $allEnrolledCreateHtml = view('admin.dataMaster.anggotaKelas.create', [
                'kelas' => new Collection([$kelas]),
                'siswa' => new Collection,
                'excludedTahunAjaranIds' => [10],
                'hiddenRegisteredCount' => 5,
                'hiddenInactiveCount' => 2,
            ])->render();

            $onlyInactiveCreateHtml = view('admin.dataMaster.anggotaKelas.create', [
                'kelas' => new Collection([$kelas]),
                'siswa' => new Collection,
                'excludedTahunAjaranIds' => [10],
                'hiddenRegisteredCount' => 0,
                'hiddenInactiveCount' => 4,
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString(route('anggota-kelas.store'), $createHtml);
        $this->assertStringContainsString('name="siswa_nis[]"', $createHtml);
        $this->assertStringContainsString('name="akademik_master_kelas_id"', $createHtml);
        $this->assertStringContainsString('name="pendaftaran"', $createHtml);
        $this->assertStringContainsString('Ahmad Fauzi', $createHtml);
        $this->assertStringContainsString('NIS: 20260001', $createHtml);
        $this->assertStringContainsString('1 Abu Bakar', $createHtml);
        $this->assertStringContainsString('2025/2026', $createHtml);
        $this->assertStringContainsString('Belum ada kelas sebelumnya', $createHtml);
        $this->assertStringContainsString('2 Al-Fatihah', $createHtml);
        $this->assertStringContainsString('4 sudah masuk anggota kelas (disembunyikan)', $createHtml);
        $this->assertStringContainsString('3 non-aktif/pindah/lulus (disembunyikan)', $createHtml);
        $this->assertStringContainsString('Seluruh siswa aktif (5) sudah masuk daftar Anggota Kelas', $allEnrolledCreateHtml);
        $this->assertStringContainsString('Tidak ada siswa berstatus Aktif yang tersedia (4 siswa berstatus Nonaktif/Pindah/Lulus/Keluar disembunyikan).', $onlyInactiveCreateHtml);

        $service = new AnggotaKelasService;
        $createQueryBuilder = $service->buildCreateSiswaQuery([10]);
        $this->assertStringContainsString('status', $createQueryBuilder->toSql());
        $this->assertSame(['Aktif', 'aktif', 'AKTIF', 10], $createQueryBuilder->getBindings());
        $this->assertArrayHasKey('kelas.tahunAjaran', $createQueryBuilder->getEagerLoads());
        $this->assertArrayHasKey('anggotaKelas.kelas.tahunAjaran', $createQueryBuilder->getEagerLoads());

        $inactiveQueryBuilder = Siswa::query()->inactive();
        $this->assertStringContainsString('status', $inactiveQueryBuilder->toSql());
        $this->assertSame(['Aktif', 'aktif', 'AKTIF'], $inactiveQueryBuilder->getBindings());

        $resolveExcluded = new ReflectionMethod($service, 'resolveExcludedTahunAjaranIds');
        $this->assertSame([10], $resolveExcluded->invoke($service, new Collection([$kelasLama, $kelas]), null));
        $this->assertSame([9], $resolveExcluded->invoke($service, new Collection([$kelasLama, $kelas]), 19));

        $kelas->tahunAjaran->is_active = false;
        $this->assertSame([10], $resolveExcluded->invoke($service, new Collection([$kelas, $kelasLama]), null));

        $anggotaKelas = AnggotaKelas::make([
            'siswa_nis' => '20260001',
            'akademik_master_kelas_id' => 20,
            'pendaftaran' => 'Naik Kelas',
        ]);
        $anggotaKelas->id = 7;

        Model::preventLazyLoading(true);

        try {
            $editHtml = view('admin.dataMaster.anggotaKelas.edit', [
                'anggotaKelas' => $anggotaKelas,
                'kelas' => new Collection([$kelas]),
                'siswa' => new Collection([$siswaDenganRiwayat, $siswaBaru]),
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString(route('anggota-kelas.update', 7), $editHtml);
        $this->assertStringContainsString('name="_method" value="PUT"', $editHtml);
        $this->assertStringContainsString('name="siswa_nis"', $editHtml);
        $this->assertStringContainsString('Ahmad Fauzi — NIS: 20260001', $editHtml);
        $this->assertStringContainsString('Kelas Sebelumnya: 1 Abu Bakar', $editHtml);
    }
}
