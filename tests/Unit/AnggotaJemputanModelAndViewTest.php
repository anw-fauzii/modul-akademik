<?php

namespace Tests\Unit;

use App\Http\Requests\Admin\DataMaster\AnggotaJemputanRequest;
use App\Models\Admin\DataMaster\AnggotaJemputan;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Jemputan;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Services\Admin\DataMaster\AnggotaJemputanService;
use DOMDocument;
use DOMXPath;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\TestWith;
use ReflectionMethod;
use Tests\TestCase;

class AnggotaJemputanModelAndViewTest extends TestCase
{
    public function test_model_metadata_casts_and_relationship_keys_are_configured(): void
    {
        $model = new AnggotaJemputan;

        $this->assertSame('akademik_master_anggota_jemputan', $model->getTable());
        $this->assertSame([
            'anggota_kelas_id',
            'akademik_master_jemputan_id',
            'pendaftaran',
            'keterangan',
        ], $model->getFillable());
        $this->assertSame(['Baru', 'Lanjutan'], AnggotaJemputan::PENDAFTARAN_OPTIONS);
        $this->assertSame([
            'id' => 'int',
            'anggota_kelas_id' => 'integer',
            'akademik_master_jemputan_id' => 'integer',
        ], $model->getCasts());

        $anggotaKelasRelation = $model->anggotaKelas();
        $jemputanRelation = $model->jemputan();
        $siswaThroughRelation = (new Siswa)->anggotaJemputan();

        $this->assertInstanceOf(BelongsTo::class, $anggotaKelasRelation);
        $this->assertSame('anggota_kelas_id', $anggotaKelasRelation->getForeignKeyName());

        $this->assertInstanceOf(BelongsTo::class, $jemputanRelation);
        $this->assertSame('akademik_master_jemputan_id', $jemputanRelation->getForeignKeyName());

        $this->assertInstanceOf(HasManyThrough::class, $siswaThroughRelation);
        $this->assertSame('siswa_nis', $siswaThroughRelation->getFirstKeyName());
        $this->assertSame('anggota_kelas_id', $siswaThroughRelation->getForeignKeyName());
    }

    #[TestWith(['Baru', 'badge-success'])]
    #[TestWith(['Lanjutan', 'badge-primary'])]
    #[TestWith(['Lainnya', 'badge-secondary'])]
    public function test_pendaftaran_badge_class_maps_registration_types(string $pendaftaran, string $expectedClass): void
    {
        $anggota = AnggotaJemputan::make(['pendaftaran' => $pendaftaran]);

        $this->assertSame($expectedClass, $anggota->pendaftaran_badge_class);
    }

    public function test_service_index_and_create_queries_configure_relations_and_filters(): void
    {
        $service = app(AnggotaJemputanService::class);
        $indexQuery = $service->buildIndexQuery(12, 34, 56);
        $indexSql = $indexQuery->toSql();

        $this->assertStringContainsString('akademik_master_jemputan', $indexSql);
        $this->assertStringContainsString('akademik_master_tahun_ajaran_id', $indexSql);
        $this->assertStringContainsString('akademik_master_jemputan_id', $indexSql);
        $this->assertStringContainsString('akademik_master_kelas', $indexSql);
        $this->assertArrayHasKey('anggotaKelas', $indexQuery->getEagerLoads());
        $this->assertArrayHasKey('anggotaKelas.siswa', $indexQuery->getEagerLoads());
        $this->assertArrayHasKey('anggotaKelas.kelas', $indexQuery->getEagerLoads());
        $this->assertArrayHasKey('jemputan', $indexQuery->getEagerLoads());
        $this->assertArrayHasKey('jemputan.tahunAjaran', $indexQuery->getEagerLoads());
        $this->assertArrayHasKey('jemputan.petugas', $indexQuery->getEagerLoads());

        $createQuery = $service->buildCreateAnggotaKelasQuery([12]);
        $createSql = $createQuery->toSql();

        $this->assertStringContainsString('akademik_master_siswa', $createSql);
        $this->assertStringContainsString('akademik_master_kelas', $createSql);
        $this->assertStringContainsString('akademik_master_anggota_jemputan', $createSql);
        $this->assertArrayHasKey('siswa', $createQuery->getEagerLoads());
        $this->assertArrayHasKey('siswa.anggotaJemputan', $createQuery->getEagerLoads());
        $this->assertArrayHasKey('kelas', $createQuery->getEagerLoads());

        $this->assertSame([], $service->findMismatchedTahunAjaranAnggotaKelas([], 12));
        $this->assertSame([], $service->findConflictingAnggotaKelasInTahunAjaran([], 12));
    }

    public function test_request_validation_rules_support_scalar_and_batch_payloads(): void
    {
        $batchRequest = AnggotaJemputanRequest::create('/data-master/anggota-jemputan', 'POST', [
            'anggota_kelas_id' => [10, 11],
            'akademik_master_jemputan_id' => 5,
            'pendaftaran' => 'Baru',
            'keterangan' => 'PP Titik A',
        ]);
        $batchRules = $batchRequest->rules();

        $this->assertIsArray($batchRules['anggota_kelas_id']);
        $this->assertContains('array', $batchRules['anggota_kelas_id']);
        $this->assertContains('min:1', $batchRules['anggota_kelas_id']);
        $this->assertArrayHasKey('anggota_kelas_id.*', $batchRules);
        $this->assertContains('distinct', $batchRules['anggota_kelas_id.*']);
        $this->assertContains('exists:akademik_master_anggota_kelas,id', $batchRules['anggota_kelas_id.*']);

        $emptyBatchValidator = Validator::make(
            ['anggota_kelas_id' => []],
            ['anggota_kelas_id' => $batchRules['anggota_kelas_id']],
            $batchRequest->messages()
        );
        $this->assertTrue($emptyBatchValidator->fails());
        $this->assertSame('Anggota kelas siswa wajib dipilih.', $emptyBatchValidator->errors()->first('anggota_kelas_id'));

        $duplicateBatchValidator = Validator::make(
            ['anggota_kelas_id' => [10, 10]],
            [
                'anggota_kelas_id' => ['required', 'array', 'min:1'],
                'anggota_kelas_id.*' => ['required', 'integer', 'distinct'],
            ],
            $batchRequest->messages()
        );
        $this->assertTrue($duplicateBatchValidator->fails());
        $this->assertSame('Terdapat duplikasi siswa dalam pilihan.', $duplicateBatchValidator->errors()->first('anggota_kelas_id.1'));

        $updateRequest = AnggotaJemputanRequest::create('/data-master/anggota-jemputan/1', 'PUT', [
            'anggota_kelas_id' => 10,
            'akademik_master_jemputan_id' => 5,
            'pendaftaran' => 'Lanjutan',
            'keterangan' => str_repeat('a', 260),
        ]);
        $updateRules = $updateRequest->rules();

        $this->assertContains('integer', $updateRules['anggota_kelas_id']);
        $this->assertContains('exists:akademik_master_anggota_kelas,id', $updateRules['anggota_kelas_id']);
        $this->assertArrayNotHasKey('anggota_kelas_id.*', $updateRules);

        $longKeteranganValidator = Validator::make(
            [
                'anggota_kelas_id' => 10,
                'akademik_master_jemputan_id' => 5,
                'pendaftaran' => 'Salah',
                'keterangan' => str_repeat('a', 260),
            ],
            [
                'pendaftaran' => $updateRules['pendaftaran'],
                'keterangan' => $updateRules['keterangan'],
            ],
            $updateRequest->messages()
        );
        $this->assertTrue($longKeteranganValidator->fails());
        $this->assertSame('Status pendaftaran tidak valid.', $longKeteranganValidator->errors()->first('pendaftaran'));
        $this->assertSame('Keterangan maksimal 255 karakter.', $longKeteranganValidator->errors()->first('keterangan'));
    }

    public function test_index_view_renders_preloaded_relations_and_sidebar_keeps_jemputan_active(): void
    {
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
        $jemputan->id = 7;
        $jemputan->setRelation('tahunAjaran', $tahunAjaran);
        $jemputan->setRelation('petugas', $petugas);

        $kelas = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 10,
            'nama_kelas' => '4 Umar',
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => '4',
        ]);
        $kelas->id = 12;
        $kelas->setRelation('tahunAjaran', $tahunAjaran);

        $siswa = Siswa::make([
            'nis' => '20260001',
            'nisn' => '0012345678',
            'nama_lengkap' => 'Ahmad Fauzi',
            'jenis_kelamin' => 'L',
            'status' => 'Aktif',
        ]);

        $anggotaKelas = AnggotaKelas::make([
            'siswa_nis' => '20260001',
            'akademik_master_kelas_id' => 12,
            'pendaftaran' => 'Siswa Baru',
        ]);
        $anggotaKelas->id = 50;
        $anggotaKelas->setRelation('siswa', $siswa);
        $anggotaKelas->setRelation('kelas', $kelas);

        $anggota = AnggotaJemputan::make([
            'anggota_kelas_id' => 50,
            'akademik_master_jemputan_id' => 7,
            'pendaftaran' => 'Baru',
            'keterangan' => 'Antar Jemput PP',
        ]);
        $anggota->id = 30;
        $anggota->setRelation('anggotaKelas', $anggotaKelas);
        $anggota->setRelation('jemputan', $jemputan);

        Model::preventLazyLoading(true);

        try {
            $rendered = view('admin.dataMaster.anggotaJemputan.index', [
                'anggotaJemputan' => new Collection([$anggota]),
                'tahunAjaran' => new Collection([$tahunAjaran]),
                'jemputan' => new Collection([$jemputan]),
                'kelas' => new Collection([$kelas]),
                'selectedTahunAjaranId' => null,
                'selectedJemputanId' => null,
                'selectedKelasId' => null,
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $sidebarHtml = view('layouts.sidebar')->render();

        $this->assertStringContainsString('Ahmad Fauzi', $rendered);
        $this->assertStringContainsString('20260001', $rendered);
        $this->assertStringContainsString('0012345678', $rendered);
        $this->assertStringContainsString('4 Umar', $rendered);
        $this->assertStringContainsString('Agus Setiawan, S.Pd', $rendered);
        $this->assertStringContainsString('Rp 250.000', $rendered);
        $this->assertStringContainsString('Rp 150.000', $rendered);
        $this->assertStringContainsString('2026/2027', $rendered);
        $this->assertStringContainsString('Antar Jemput PP', $rendered);
        $this->assertStringContainsString('badge-success', $rendered);
        $this->assertStringContainsString(route('anggota-jemputan.edit', 30), $rendered);
        $this->assertStringContainsString(route('anggota-jemputan.destroy', 30), $rendered);

        $this->assertStringNotContainsString(route('anggota-jemputan.index'), $sidebarHtml);
        $this->assertStringContainsString(route('jemputan.index'), $sidebarHtml);
    }

    public function test_index_view_does_not_render_colspan_row_when_empty_for_datatables_compatibility(): void
    {
        $rendered = view('admin.dataMaster.anggotaJemputan.index', [
            'anggotaJemputan' => new Collection,
            'tahunAjaran' => new Collection,
            'jemputan' => new Collection,
            'kelas' => new Collection,
            'selectedTahunAjaranId' => null,
            'selectedJemputanId' => null,
            'selectedKelasId' => null,
        ])->render();

        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML($rendered);
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

    public function test_create_and_edit_views_render_anggota_kelas_picker_and_redirect_without_lazy_loading(): void
    {
        view()->share('errors', new ViewErrorBag);

        $prevTa = TahunAjaran::make([
            'nama_tahun_ajaran' => '2025/2026',
            'semester' => '2',
            'is_active' => false,
        ]);
        $prevTa->id = 9;

        $activeTa = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);
        $activeTa->id = 10;

        $prevPetugas = Karyawan::make([
            'nipy' => 'DRV-001',
            'nama_lengkap' => 'Slamet Riyadi',
            'gelar' => null,
        ]);
        $targetPetugas = Karyawan::make([
            'nipy' => 'DRV-002',
            'nama_lengkap' => 'Agus Setiawan',
            'gelar' => 'S.Pd',
        ]);

        $prevJemputan = Jemputan::make([
            'akademik_master_tahun_ajaran_id' => 9,
            'karyawan_nipy' => 'DRV-001',
            'harga_pp' => 200000,
            'harga_setengah' => 120000,
        ]);
        $prevJemputan->id = 3;
        $prevJemputan->setRelation('tahunAjaran', $prevTa);
        $prevJemputan->setRelation('petugas', $prevPetugas);

        $targetJemputan = Jemputan::make([
            'akademik_master_tahun_ajaran_id' => 10,
            'karyawan_nipy' => 'DRV-002',
            'harga_pp' => 250000,
            'harga_setengah' => 150000,
        ]);
        $targetJemputan->id = 7;
        $targetJemputan->setRelation('tahunAjaran', $activeTa);
        $targetJemputan->setRelation('petugas', $targetPetugas);

        $kelas = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 10,
            'nama_kelas' => '4 Umar',
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => '4',
        ]);
        $kelas->id = 12;
        $kelas->setRelation('tahunAjaran', $activeTa);

        $historyAk = AnggotaKelas::make([
            'siswa_nis' => '20260001',
            'akademik_master_kelas_id' => 11,
        ]);
        $historyAk->id = 41;

        $historyMembership = AnggotaJemputan::make([
            'anggota_kelas_id' => 41,
            'akademik_master_jemputan_id' => 3,
            'pendaftaran' => 'Baru',
        ]);
        $historyMembership->id = 19;
        $historyMembership->setRelation('jemputan', $prevJemputan);

        $siswaWithHistory = Siswa::make([
            'nis' => '20260001',
            'nisn' => '0011223344',
            'nama_lengkap' => 'Ahmad Fauzi',
            'jenis_kelamin' => 'L',
            'status' => 'Aktif',
        ]);
        $siswaWithHistory->setRelation('anggotaJemputan', new Collection([$historyMembership]));

        $akWithHistory = AnggotaKelas::make([
            'siswa_nis' => '20260001',
            'akademik_master_kelas_id' => 12,
            'pendaftaran' => 'Naik Kelas',
        ]);
        $akWithHistory->id = 51;
        $akWithHistory->setRelation('siswa', $siswaWithHistory);
        $akWithHistory->setRelation('kelas', $kelas);

        $siswaWithoutHistory = Siswa::make([
            'nis' => '20260002',
            'nisn' => '0055667788',
            'nama_lengkap' => 'Zahra Aulia',
            'jenis_kelamin' => 'P',
            'status' => 'Aktif',
        ]);
        $siswaWithoutHistory->setRelation('anggotaJemputan', new Collection);

        $akWithoutHistory = AnggotaKelas::make([
            'siswa_nis' => '20260002',
            'akademik_master_kelas_id' => 12,
            'pendaftaran' => 'Siswa Baru',
        ]);
        $akWithoutHistory->id = 52;
        $akWithoutHistory->setRelation('siswa', $siswaWithoutHistory);
        $akWithoutHistory->setRelation('kelas', $kelas);

        $existing = AnggotaJemputan::make([
            'anggota_kelas_id' => 51,
            'akademik_master_jemputan_id' => 7,
            'pendaftaran' => 'Lanjutan',
            'keterangan' => 'Pulang saja',
        ]);
        $existing->id = 30;
        $existing->setRelation('anggotaKelas', $akWithHistory);
        $existing->setRelation('jemputan', $targetJemputan);

        Model::preventLazyLoading(true);

        try {
            $createHtml = view('admin.dataMaster.anggotaJemputan.create', [
                'jemputan' => new Collection([$targetJemputan]),
                'anggotaKelas' => new Collection([$akWithHistory, $akWithoutHistory]),
                'excludedTahunAjaranIds' => [10],
                'hiddenRegisteredCount' => 4,
                'hiddenInactiveCount' => 3,
                'hiddenWithoutKelasCount' => 2,
                'preselectedJemputanId' => 7,
                'preselectedPendaftaran' => 'Baru',
            ])->render();

            $allEnrolledCreateHtml = view('admin.dataMaster.anggotaJemputan.create', [
                'jemputan' => new Collection([$targetJemputan]),
                'anggotaKelas' => new Collection,
                'excludedTahunAjaranIds' => [10],
                'hiddenRegisteredCount' => 5,
                'hiddenInactiveCount' => 0,
                'hiddenWithoutKelasCount' => 0,
            ])->render();

            $withoutKelasCreateHtml = view('admin.dataMaster.anggotaJemputan.create', [
                'jemputan' => new Collection([$targetJemputan]),
                'anggotaKelas' => new Collection,
                'excludedTahunAjaranIds' => [10],
                'hiddenRegisteredCount' => 0,
                'hiddenInactiveCount' => 0,
                'hiddenWithoutKelasCount' => 6,
            ])->render();

            $editHtml = view('admin.dataMaster.anggotaJemputan.edit', [
                'anggotaJemputan' => $existing,
                'fromJemputanId' => 7,
                'jemputan' => new Collection([$targetJemputan]),
                'anggotaKelas' => new Collection([$akWithHistory, $akWithoutHistory]),
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString(route('anggota-jemputan.store'), $createHtml);
        $this->assertStringContainsString('name="anggota_kelas_id[]"', $createHtml);
        $this->assertStringContainsString('name="akademik_master_jemputan_id"', $createHtml);
        $this->assertStringContainsString('name="pendaftaran"', $createHtml);
        $this->assertStringContainsString('name="keterangan"', $createHtml);
        $this->assertStringContainsString('Ahmad Fauzi', $createHtml);
        $this->assertStringContainsString('4 Umar', $createHtml);
        $this->assertStringContainsString('Slamet Riyadi', $createHtml);
        $this->assertStringContainsString('2025/2026', $createHtml);
        $this->assertStringContainsString('Belum ada jemputan sebelumnya', $createHtml);
        $this->assertStringContainsString('4 sudah punya jemputan (disembunyikan)', $createHtml);
        $this->assertStringContainsString('2 belum punya anggota kelas (disembunyikan)', $createHtml);
        $this->assertStringContainsString('3 non-aktif/pindah/lulus (disembunyikan)', $createHtml);
        $this->assertStringContainsString('Seluruh siswa aktif dengan kelas aktif (5)', $allEnrolledCreateHtml);
        $this->assertStringContainsString('6 siswa aktif belum memiliki Anggota Kelas', $withoutKelasCreateHtml);

        $this->assertStringContainsString(route('anggota-jemputan.update', 30), $editHtml);
        $this->assertStringContainsString('name="_method" value="PUT"', $editHtml);
        $this->assertStringContainsString('name="anggota_kelas_id"', $editHtml);
        $this->assertStringContainsString('name="redirect_to_jemputan" value="7"', $editHtml);
        $this->assertStringContainsString(route('jemputan.show', 7), $editHtml);
        $this->assertStringContainsString('Pulang saja', $editHtml);
    }

    public function test_resolve_excluded_tahun_ajaran_ids_prioritizes_selected_then_active_then_latest(): void
    {
        $service = app(AnggotaJemputanService::class);
        $reflection = new ReflectionMethod(AnggotaJemputanService::class, 'resolveExcludedTahunAjaranIds');

        $activeTa = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);
        $activeTa->id = 10;

        $inactiveTa = TahunAjaran::make(['nama_tahun_ajaran' => '2025/2026', 'semester' => '2', 'is_active' => false]);
        $inactiveTa->id = 9;

        $jemputanAktif = Jemputan::make(['akademik_master_tahun_ajaran_id' => 10, 'karyawan_nipy' => 'DRV-1']);
        $jemputanAktif->id = 1;
        $jemputanAktif->setRelation('tahunAjaran', $activeTa);

        $jemputanLama = Jemputan::make(['akademik_master_tahun_ajaran_id' => 9, 'karyawan_nipy' => 'DRV-2']);
        $jemputanLama->id = 2;
        $jemputanLama->setRelation('tahunAjaran', $inactiveTa);

        $collection = new Collection([$jemputanLama, $jemputanAktif]);

        $this->assertSame([9], $reflection->invoke($service, $collection, 2));
        $this->assertSame([10], $reflection->invoke($service, $collection, null));
        $this->assertSame([9], $reflection->invoke($service, new Collection([$jemputanLama]), null));
        $this->assertSame([], $reflection->invoke($service, new Collection, null));
    }
}

