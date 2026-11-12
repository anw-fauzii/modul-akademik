<?php

namespace Tests\Unit;

use App\Http\Requests\Admin\DataMaster\AnggotaEkstrakurikulerRequest;
use App\Models\Admin\DataMaster\AnggotaEkstrakurikuler;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Services\Admin\DataMaster\AnggotaEkstrakurikulerService;
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

class AnggotaEkstrakurikulerModelAndViewTest extends TestCase
{
    public function test_model_metadata_casts_and_relationship_keys_are_configured(): void
    {
        $model = new AnggotaEkstrakurikuler;

        $this->assertSame('akademik_master_anggota_ekstrakuler', $model->getTable());
        $this->assertSame([
            'anggota_kelas_id',
            'akademik_master_ekstrakuler_id',
            'pendaftaran',
            'keterangan',
        ], $model->getFillable());
        $this->assertSame(['Baru', 'Lanjutan'], AnggotaEkstrakurikuler::PENDAFTARAN_OPTIONS);
        $this->assertSame([
            'id' => 'int',
            'anggota_kelas_id' => 'integer',
            'akademik_master_ekstrakuler_id' => 'integer',
        ], $model->getCasts());

        $anggotaKelasRelation = $model->anggotaKelas();
        $ekskulRelation = $model->ekstrakurikuler();
        $siswaThroughRelation = (new Siswa)->anggotaEkstrakurikuler();

        $this->assertInstanceOf(BelongsTo::class, $anggotaKelasRelation);
        $this->assertSame('anggota_kelas_id', $anggotaKelasRelation->getForeignKeyName());

        $this->assertInstanceOf(BelongsTo::class, $ekskulRelation);
        $this->assertSame('akademik_master_ekstrakuler_id', $ekskulRelation->getForeignKeyName());

        $this->assertInstanceOf(HasManyThrough::class, $siswaThroughRelation);
        $this->assertSame('siswa_nis', $siswaThroughRelation->getFirstKeyName());
        $this->assertSame('anggota_kelas_id', $siswaThroughRelation->getForeignKeyName());
    }

    #[TestWith(['Baru', 'badge-success'])]
    #[TestWith(['Lanjutan', 'badge-primary'])]
    #[TestWith(['Lainnya', 'badge-secondary'])]
    public function test_pendaftaran_badge_class_maps_registration_types(string $pendaftaran, string $expectedClass): void
    {
        $anggota = AnggotaEkstrakurikuler::make(['pendaftaran' => $pendaftaran]);

        $this->assertSame($expectedClass, $anggota->pendaftaran_badge_class);
    }

    public function test_service_index_and_create_queries_configure_relations_and_filters(): void
    {
        $service = app(AnggotaEkstrakurikulerService::class);
        $indexQuery = $service->buildIndexQuery(12, 34, 56);
        $indexSql = $indexQuery->toSql();

        $this->assertStringContainsString('akademik_master_ekstrakuler', $indexSql);
        $this->assertStringContainsString('akademik_master_tahun_ajaran_id', $indexSql);
        $this->assertStringContainsString('akademik_master_ekstrakuler_id', $indexSql);
        $this->assertStringContainsString('akademik_master_kelas', $indexSql);
        $this->assertArrayHasKey('anggotaKelas', $indexQuery->getEagerLoads());
        $this->assertArrayHasKey('anggotaKelas.siswa', $indexQuery->getEagerLoads());
        $this->assertArrayHasKey('anggotaKelas.kelas', $indexQuery->getEagerLoads());
        $this->assertArrayHasKey('ekstrakurikuler', $indexQuery->getEagerLoads());
        $this->assertArrayHasKey('ekstrakurikuler.tahunAjaran', $indexQuery->getEagerLoads());
        $this->assertArrayHasKey('ekstrakurikuler.pembina', $indexQuery->getEagerLoads());

        $createQuery = $service->buildCreateAnggotaKelasQuery([12]);
        $createSql = $createQuery->toSql();

        $this->assertStringContainsString('akademik_master_siswa', $createSql);
        $this->assertStringContainsString('akademik_master_kelas', $createSql);
        $this->assertStringContainsString('akademik_master_anggota_ekstrakuler', $createSql);
        $this->assertArrayHasKey('siswa', $createQuery->getEagerLoads());
        $this->assertArrayHasKey('siswa.anggotaEkstrakurikuler', $createQuery->getEagerLoads());
        $this->assertArrayHasKey('kelas', $createQuery->getEagerLoads());

        $this->assertSame([], $service->findMismatchedTahunAjaranAnggotaKelas([], 12));
        $this->assertSame([], $service->findConflictingAnggotaKelasInTahunAjaran([], 12));
    }

    public function test_request_validation_rules_support_scalar_and_batch_payloads(): void
    {
        $batchRequest = AnggotaEkstrakurikulerRequest::create('/data-master/anggota-ekstrakurikuler', 'POST', [
            'anggota_kelas_id' => [10, 11],
            'akademik_master_ekstrakuler_id' => 5,
            'pendaftaran' => 'Baru',
            'keterangan' => 'Regu Inti',
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

        $updateRequest = AnggotaEkstrakurikulerRequest::create('/data-master/anggota-ekstrakurikuler/1', 'PUT', [
            'anggota_kelas_id' => 10,
            'akademik_master_ekstrakuler_id' => 5,
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
                'akademik_master_ekstrakuler_id' => 5,
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

    public function test_index_view_renders_preloaded_relations_and_sidebar_keeps_ekstrakurikuler_active(): void
    {
        $tahunAjaran = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);
        $tahunAjaran->id = 10;

        $pembina = Karyawan::make([
            'nipy' => '19900101-1',
            'nama_lengkap' => 'Budi Santoso',
            'gelar' => 'S.Pd',
        ]);

        $ekstrakurikuler = Ekstrakurikuler::make([
            'akademik_master_tahun_ajaran_id' => 10,
            'karyawan_nipy' => '19900101-1',
            'nama_ekstrakurikuler' => 'Panahan',
            'biaya' => 75000,
            'unit' => 'SD',
        ]);
        $ekstrakurikuler->id = 7;
        $ekstrakurikuler->setRelation('tahunAjaran', $tahunAjaran);
        $ekstrakurikuler->setRelation('pembina', $pembina);

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

        $anggota = AnggotaEkstrakurikuler::make([
            'anggota_kelas_id' => 50,
            'akademik_master_ekstrakuler_id' => 7,
            'pendaftaran' => 'Baru',
            'keterangan' => 'Kelompok A',
        ]);
        $anggota->id = 30;
        $anggota->setRelation('anggotaKelas', $anggotaKelas);
        $anggota->setRelation('ekstrakurikuler', $ekstrakurikuler);

        Model::preventLazyLoading(true);

        try {
            $rendered = view('admin.dataMaster.anggotaEkstrakurikuler.index', [
                'anggotaEkstrakurikuler' => new Collection([$anggota]),
                'tahunAjaran' => new Collection([$tahunAjaran]),
                'ekstrakurikuler' => new Collection([$ekstrakurikuler]),
                'kelas' => new Collection([$kelas]),
                'selectedTahunAjaranId' => null,
                'selectedEkstrakurikulerId' => null,
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
        $this->assertStringContainsString('Panahan', $rendered);
        $this->assertStringContainsString('2026/2027', $rendered);
        $this->assertStringContainsString('Budi Santoso, S.Pd', $rendered);
        $this->assertStringContainsString('Kelompok A', $rendered);
        $this->assertStringContainsString('badge-success', $rendered);
        $this->assertStringContainsString(route('anggota-ekstrakurikuler.edit', 30), $rendered);
        $this->assertStringContainsString(route('anggota-ekstrakurikuler.destroy', 30), $rendered);

        $this->assertStringNotContainsString(route('anggota-ekstrakurikuler.index'), $sidebarHtml);
        $this->assertStringContainsString(route('ekstrakurikuler.index'), $sidebarHtml);
    }

    public function test_index_view_does_not_render_colspan_row_when_empty_for_datatables_compatibility(): void
    {
        $rendered = view('admin.dataMaster.anggotaEkstrakurikuler.index', [
            'anggotaEkstrakurikuler' => new Collection,
            'tahunAjaran' => new Collection,
            'ekstrakurikuler' => new Collection,
            'kelas' => new Collection,
            'selectedTahunAjaranId' => null,
            'selectedEkstrakurikulerId' => null,
            'selectedKelasId' => null,
        ])->render();

        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML($html = $rendered);
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

        $prevEkskul = Ekstrakurikuler::make([
            'akademik_master_tahun_ajaran_id' => 9,
            'nama_ekstrakurikuler' => 'Futsal',
            'unit' => 'SD',
        ]);
        $prevEkskul->id = 3;
        $prevEkskul->setRelation('tahunAjaran', $prevTa);

        $targetEkskul = Ekstrakurikuler::make([
            'akademik_master_tahun_ajaran_id' => 10,
            'nama_ekstrakurikuler' => 'Panahan',
            'unit' => 'SD',
        ]);
        $targetEkskul->id = 7;
        $targetEkskul->setRelation('tahunAjaran', $activeTa);

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

        $historyMembership = AnggotaEkstrakurikuler::make([
            'anggota_kelas_id' => 41,
            'akademik_master_ekstrakuler_id' => 3,
            'pendaftaran' => 'Baru',
        ]);
        $historyMembership->id = 19;
        $historyMembership->setRelation('ekstrakurikuler', $prevEkskul);

        $siswaWithHistory = Siswa::make([
            'nis' => '20260001',
            'nisn' => '0011223344',
            'nama_lengkap' => 'Ahmad Fauzi',
            'jenis_kelamin' => 'L',
            'status' => 'Aktif',
        ]);
        $siswaWithHistory->setRelation('anggotaEkstrakurikuler', new Collection([$historyMembership]));

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
        $siswaWithoutHistory->setRelation('anggotaEkstrakurikuler', new Collection);

        $akWithoutHistory = AnggotaKelas::make([
            'siswa_nis' => '20260002',
            'akademik_master_kelas_id' => 12,
            'pendaftaran' => 'Siswa Baru',
        ]);
        $akWithoutHistory->id = 52;
        $akWithoutHistory->setRelation('siswa', $siswaWithoutHistory);
        $akWithoutHistory->setRelation('kelas', $kelas);

        $existing = AnggotaEkstrakurikuler::make([
            'anggota_kelas_id' => 51,
            'akademik_master_ekstrakuler_id' => 7,
            'pendaftaran' => 'Lanjutan',
            'keterangan' => 'Cadangan',
        ]);
        $existing->id = 30;
        $existing->setRelation('anggotaKelas', $akWithHistory);
        $existing->setRelation('ekstrakurikuler', $targetEkskul);

        Model::preventLazyLoading(true);

        try {
            $createHtml = view('admin.dataMaster.anggotaEkstrakurikuler.create', [
                'ekstrakurikuler' => new Collection([$targetEkskul]),
                'anggotaKelas' => new Collection([$akWithHistory, $akWithoutHistory]),
                'excludedTahunAjaranIds' => [10],
                'hiddenRegisteredCount' => 4,
                'hiddenInactiveCount' => 3,
                'hiddenWithoutKelasCount' => 2,
                'preselectedEkstrakurikulerId' => 7,
                'preselectedPendaftaran' => 'Baru',
            ])->render();

            $allEnrolledCreateHtml = view('admin.dataMaster.anggotaEkstrakurikuler.create', [
                'ekstrakurikuler' => new Collection([$targetEkskul]),
                'anggotaKelas' => new Collection,
                'excludedTahunAjaranIds' => [10],
                'hiddenRegisteredCount' => 5,
                'hiddenInactiveCount' => 0,
                'hiddenWithoutKelasCount' => 0,
            ])->render();

            $withoutKelasCreateHtml = view('admin.dataMaster.anggotaEkstrakurikuler.create', [
                'ekstrakurikuler' => new Collection([$targetEkskul]),
                'anggotaKelas' => new Collection,
                'excludedTahunAjaranIds' => [10],
                'hiddenRegisteredCount' => 0,
                'hiddenInactiveCount' => 0,
                'hiddenWithoutKelasCount' => 6,
            ])->render();

            $editHtml = view('admin.dataMaster.anggotaEkstrakurikuler.edit', [
                'anggotaEkstrakurikuler' => $existing,
                'fromEkstrakurikulerId' => 7,
                'ekstrakurikuler' => new Collection([$targetEkskul]),
                'anggotaKelas' => new Collection([$akWithHistory, $akWithoutHistory]),
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString(route('anggota-ekstrakurikuler.store'), $createHtml);
        $this->assertStringContainsString('name="anggota_kelas_id[]"', $createHtml);
        $this->assertStringContainsString('name="akademik_master_ekstrakuler_id"', $createHtml);
        $this->assertStringContainsString('name="pendaftaran"', $createHtml);
        $this->assertStringContainsString('name="keterangan"', $createHtml);
        $this->assertStringContainsString('Ahmad Fauzi', $createHtml);
        $this->assertStringContainsString('4 Umar', $createHtml);
        $this->assertStringContainsString('Futsal', $createHtml);
        $this->assertStringContainsString('2025/2026', $createHtml);
        $this->assertStringContainsString('Belum ada ekskul sebelumnya', $createHtml);
        $this->assertStringContainsString('4 sudah punya ekskul (disembunyikan)', $createHtml);
        $this->assertStringContainsString('2 belum punya anggota kelas (disembunyikan)', $createHtml);
        $this->assertStringContainsString('3 non-aktif/pindah/lulus (disembunyikan)', $createHtml);
        $this->assertStringContainsString('Seluruh siswa aktif dengan kelas aktif (5)', $allEnrolledCreateHtml);
        $this->assertStringContainsString('6 siswa aktif belum memiliki Anggota Kelas', $withoutKelasCreateHtml);

        $this->assertStringContainsString(route('anggota-ekstrakurikuler.update', 30), $editHtml);
        $this->assertStringContainsString('name="_method" value="PUT"', $editHtml);
        $this->assertStringContainsString('name="anggota_kelas_id"', $editHtml);
        $this->assertStringContainsString('name="redirect_to_ekstrakurikuler" value="7"', $editHtml);
        $this->assertStringContainsString(route('ekstrakurikuler.show', 7), $editHtml);
        $this->assertStringContainsString('Cadangan', $editHtml);
    }

    public function test_resolve_excluded_tahun_ajaran_ids_prioritizes_selected_then_active_then_latest(): void
    {
        $service = app(AnggotaEkstrakurikulerService::class);
        $reflection = new ReflectionMethod(AnggotaEkstrakurikulerService::class, 'resolveExcludedTahunAjaranIds');

        $activeTa = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);
        $activeTa->id = 10;

        $inactiveTa = TahunAjaran::make(['nama_tahun_ajaran' => '2025/2026', 'semester' => '2', 'is_active' => false]);
        $inactiveTa->id = 9;

        $ekskulAktif = Ekstrakurikuler::make(['akademik_master_tahun_ajaran_id' => 10, 'nama_ekstrakurikuler' => 'Panahan']);
        $ekskulAktif->id = 1;
        $ekskulAktif->setRelation('tahunAjaran', $activeTa);

        $ekskulLama = Ekstrakurikuler::make(['akademik_master_tahun_ajaran_id' => 9, 'nama_ekstrakurikuler' => 'Futsal']);
        $ekskulLama->id = 2;
        $ekskulLama->setRelation('tahunAjaran', $inactiveTa);

        $collection = new Collection([$ekskulLama, $ekskulAktif]);

        $this->assertSame([9], $reflection->invoke($service, $collection, 2));
        $this->assertSame([10], $reflection->invoke($service, $collection, null));
        $this->assertSame([9], $reflection->invoke($service, new Collection([$ekskulLama]), null));
        $this->assertSame([], $reflection->invoke($service, new Collection, null));
    }
}
