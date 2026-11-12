<?php

namespace Tests\Unit;

use App\Http\Requests\Admin\DataMaster\AnggotaT2qRequest;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\AnggotaT2q;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Services\Admin\DataMaster\AnggotaT2qService;
use DOMDocument;
use DOMXPath;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\TestWith;
use ReflectionMethod;
use Tests\TestCase;

class AnggotaT2qModelAndViewTest extends TestCase
{
    public function test_model_metadata_casts_and_relationship_keys_are_configured(): void
    {
        $model = new AnggotaT2q;

        $this->assertSame('akademik_master_anggota_t2q', $model->getTable());
        $this->assertSame([
            'anggota_kelas_id',
            'karyawan_nipy',
            'tingkat',
            'pendaftaran',
            'keterangan',
        ], $model->getFillable());
        $this->assertSame(['Baru', 'Lanjutan'], AnggotaT2q::PENDAFTARAN_OPTIONS);
        $this->assertSame([
            'PG', 'A', 'B', '1', '2', '3', '4', '5', '6', '7', '8', '9',
        ], AnggotaT2q::DEFAULT_TINGKAT_OPTIONS);
        $this->assertSame([
            'id' => 'int',
            'anggota_kelas_id' => 'integer',
        ], $model->getCasts());

        $anggotaKelasRelation = $model->anggotaKelas();
        $guruRelation = $model->guruT2q();
        $karyawanAlias = $model->karyawan();
        $siswaThroughRelation = (new Siswa)->anggotaT2q();
        $anggotaKelasHasMany = (new AnggotaKelas)->anggotaT2q();
        $karyawanHasMany = (new Karyawan)->anggotaT2q();

        $this->assertInstanceOf(BelongsTo::class, $anggotaKelasRelation);
        $this->assertSame('anggota_kelas_id', $anggotaKelasRelation->getForeignKeyName());

        $this->assertInstanceOf(BelongsTo::class, $guruRelation);
        $this->assertSame('karyawan_nipy', $guruRelation->getForeignKeyName());
        $this->assertSame('nipy', $guruRelation->getOwnerKeyName());

        $this->assertInstanceOf(BelongsTo::class, $karyawanAlias);
        $this->assertSame('karyawan_nipy', $karyawanAlias->getForeignKeyName());

        $this->assertInstanceOf(HasManyThrough::class, $siswaThroughRelation);
        $this->assertSame('siswa_nis', $siswaThroughRelation->getFirstKeyName());
        $this->assertSame('anggota_kelas_id', $siswaThroughRelation->getForeignKeyName());

        $this->assertInstanceOf(HasMany::class, $anggotaKelasHasMany);
        $this->assertSame('anggota_kelas_id', $anggotaKelasHasMany->getForeignKeyName());

        $this->assertInstanceOf(HasMany::class, $karyawanHasMany);
        $this->assertSame('karyawan_nipy', $karyawanHasMany->getForeignKeyName());
    }

    #[TestWith(['Baru', 'badge-success'])]
    #[TestWith(['Lanjutan', 'badge-primary'])]
    #[TestWith(['Lainnya', 'badge-secondary'])]
    public function test_pendaftaran_badge_class_maps_registration_types(string $pendaftaran, string $expectedClass): void
    {
        $anggota = AnggotaT2q::make(['pendaftaran' => $pendaftaran]);

        $this->assertSame($expectedClass, $anggota->pendaftaran_badge_class);
    }

    #[TestWith(['PG', 'badge-warning text-dark'])]
    #[TestWith(['A', 'badge-warning text-dark'])]
    #[TestWith(['TK-B', 'badge-warning text-dark'])]
    #[TestWith(['1', 'badge-success'])]
    #[TestWith(['6', 'badge-success'])]
    #[TestWith(['7', 'badge-primary'])]
    #[TestWith(['10', 'badge-info'])]
    #[TestWith(['Tahfidz', 'badge-info'])]
    public function test_tingkat_badge_class_and_build_tingkat_options(string $tingkat, string $expectedClass): void
    {
        $anggota = AnggotaT2q::make(['tingkat' => $tingkat]);
        $this->assertSame($expectedClass, $anggota->tingkat_badge_class);

        $options = AnggotaT2q::buildTingkatOptions([
            ' 1 ',
            'TK-A',
            '',
            AnggotaT2q::TINGKAT_AUTO,
            AnggotaT2q::TINGKAT_CUSTOM,
            'pg',
        ]);

        $this->assertSame(
            ['PG', 'A', 'B', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'TK-A'],
            $options
        );
    }

    public function test_siswa_history_helpers_resolve_t2q_without_lazy_loading(): void
    {
        $tahunAjaranLama = TahunAjaran::make([
            'nama_tahun_ajaran' => '2025/2026',
            'semester' => '2',
            'is_active' => false,
        ]);
        $tahunAjaranLama->id = 5;

        $kelasLama = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 5,
            'nama_kelas' => 'PG Mawar',
            'jenjang_kelas' => 'TK',
            'tingkatan_kelas' => 'PG',
        ]);
        $kelasLama->id = 15;
        $kelasLama->setRelation('tahunAjaran', $tahunAjaranLama);

        $akLama = AnggotaKelas::make([
            'siswa_nis' => '20260001',
            'akademik_master_kelas_id' => 15,
            'pendaftaran' => 'Siswa Baru',
        ]);
        $akLama->id = 25;
        $akLama->setRelation('kelas', $kelasLama);

        $guruLama = Karyawan::make([
            'nipy' => '19900101-1',
            'nama_lengkap' => 'Ustadz Ahmad',
            'gelar' => 'S.Pd.I',
        ]);

        $t2qLama = AnggotaT2q::make([
            'anggota_kelas_id' => 25,
            'karyawan_nipy' => '19900101-1',
            'tingkat' => 'PG',
            'pendaftaran' => 'Baru',
        ]);
        $t2qLama->id = 35;
        $t2qLama->setRelation('anggotaKelas', $akLama);
        $t2qLama->setRelation('guruT2q', $guruLama);

        $siswa = Siswa::make([
            'nis' => '20260001',
            'nama_lengkap' => 'Ahmad Fauzi',
        ]);
        $siswa->setRelation('anggotaT2q', new Collection([$t2qLama]));

        $this->assertSame([5], $siswa->registeredT2qTahunAjaranIds());
        $this->assertSame([], $siswa->registeredT2qTahunAjaranIds(35));
        $this->assertSame([
            'guru' => 'Ustadz Ahmad, S.Pd.I',
            'tingkat' => 'PG',
            'tahun_ajaran' => '2025/2026',
            'semester' => '2',
            'tahun_ajaran_id' => 5,
        ], $siswa->resolvePreviousT2qSummary());
        $this->assertNull($siswa->resolvePreviousT2qSummary(35));
    }

    public function test_service_index_and_create_queries_configure_relations_and_filters(): void
    {
        $service = app(AnggotaT2qService::class);
        $indexQuery = $service->buildIndexQuery(12, '19900101-1', 'PG', 56);
        $indexSql = $indexQuery->toSql();

        $this->assertStringContainsString('akademik_master_anggota_t2q', $indexSql);
        $this->assertStringContainsString('akademik_master_anggota_kelas', $indexSql);
        $this->assertStringContainsString('akademik_master_kelas', $indexSql);
        $this->assertStringContainsString('akademik_master_tahun_ajaran_id', $indexSql);
        $this->assertStringContainsString('karyawan_nipy', $indexSql);
        $this->assertStringContainsString('tingkat', $indexSql);
        $this->assertArrayHasKey('anggotaKelas', $indexQuery->getEagerLoads());
        $this->assertArrayHasKey('anggotaKelas.siswa', $indexQuery->getEagerLoads());
        $this->assertArrayHasKey('anggotaKelas.kelas', $indexQuery->getEagerLoads());
        $this->assertArrayHasKey('anggotaKelas.kelas.tahunAjaran', $indexQuery->getEagerLoads());
        $this->assertArrayHasKey('guruT2q', $indexQuery->getEagerLoads());

        $createQuery = $service->buildCreateAnggotaKelasQuery([12]);
        $createSql = $createQuery->toSql();

        $this->assertStringContainsString('akademik_master_siswa', $createSql);
        $this->assertStringContainsString('akademik_master_kelas', $createSql);
        $this->assertStringContainsString('akademik_master_anggota_t2q', $createSql);
        $this->assertArrayHasKey('siswa', $createQuery->getEagerLoads());
        $this->assertArrayHasKey('siswa.anggotaT2q', $createQuery->getEagerLoads());
        $this->assertArrayHasKey('kelas', $createQuery->getEagerLoads());

        $this->assertSame([], $service->findMismatchedTahunAjaranAnggotaKelas([], 12));
        $this->assertSame([], $service->findConflictingAnggotaKelasInTahunAjaran([], 12));
    }

    public function test_request_validation_rules_support_batch_custom_tingkat_and_scalar_updates(): void
    {
        $batchRequest = AnggotaT2qRequest::create('/data-master/anggota-t2q', 'POST', [
            'tahun_ajaran_id' => 10,
            'karyawan_nipy' => '19900101-1',
            'tingkat' => AnggotaT2q::TINGKAT_CUSTOM,
            'tingkat_custom' => '  TK-A  ',
            'pendaftaran' => 'Baru',
            'keterangan' => 'Kelompok Pagi',
            'anggota_kelas_id' => [10, 11],
        ]);

        $prepareMethod = new ReflectionMethod($batchRequest, 'prepareForValidation');
        $prepareMethod->setAccessible(true);
        $prepareMethod->invoke($batchRequest);

        $this->assertSame('TK-A', $batchRequest->input('tingkat'));

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

        $updateRequest = AnggotaT2qRequest::create('/data-master/anggota-t2q/1', 'PUT', [
            'anggota_kelas_id' => 10,
            'karyawan_nipy' => '19900101-1',
            'tingkat' => str_repeat('X', 55),
            'pendaftaran' => 'Salah',
            'keterangan' => str_repeat('a', 260),
        ]);
        $updateRules = $updateRequest->rules();

        $this->assertContains('integer', $updateRules['anggota_kelas_id']);
        $this->assertContains('exists:akademik_master_anggota_kelas,id', $updateRules['anggota_kelas_id']);
        $this->assertArrayNotHasKey('anggota_kelas_id.*', $updateRules);

        $invalidValidator = Validator::make(
            [
                'tingkat' => str_repeat('X', 55),
                'pendaftaran' => 'Salah',
                'keterangan' => str_repeat('a', 260),
            ],
            [
                'tingkat' => $updateRules['tingkat'],
                'pendaftaran' => $updateRules['pendaftaran'],
                'keterangan' => $updateRules['keterangan'],
            ],
            $updateRequest->messages()
        );
        $this->assertTrue($invalidValidator->fails());
        $this->assertSame('Tingkat T2Q maksimal 50 karakter.', $invalidValidator->errors()->first('tingkat'));
        $this->assertSame('Status pendaftaran tidak valid.', $invalidValidator->errors()->first('pendaftaran'));
        $this->assertSame('Keterangan maksimal 255 karakter.', $invalidValidator->errors()->first('keterangan'));
    }

    public function test_index_create_and_edit_views_render_cleanly_without_n_plus_one(): void
    {
        $tahunAjaran = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);
        $tahunAjaran->id = 10;

        $tahunAjaranLama = TahunAjaran::make([
            'nama_tahun_ajaran' => '2025/2026',
            'semester' => '2',
            'is_active' => false,
        ]);
        $tahunAjaranLama->id = 9;

        $guru = Karyawan::make([
            'nipy' => '19900101-1',
            'nama_lengkap' => 'Ustadzah Fatimah',
            'gelar' => 'S.Pd',
        ]);

        $kelas = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 10,
            'nama_kelas' => '1 Al-Fatihah',
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => '1',
        ]);
        $kelas->id = 12;
        $kelas->setRelation('tahunAjaran', $tahunAjaran);

        $kelasLama = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 9,
            'nama_kelas' => 'B Melati',
            'jenjang_kelas' => 'TK',
            'tingkatan_kelas' => 'B',
        ]);
        $kelasLama->id = 11;
        $kelasLama->setRelation('tahunAjaran', $tahunAjaranLama);

        $akLama = AnggotaKelas::make([
            'siswa_nis' => '20260001',
            'akademik_master_kelas_id' => 11,
            'pendaftaran' => 'Siswa Baru',
        ]);
        $akLama->id = 20;
        $akLama->setRelation('kelas', $kelasLama);

        $prevT2q = AnggotaT2q::make([
            'anggota_kelas_id' => 20,
            'karyawan_nipy' => '19900101-1',
            'tingkat' => 'B',
            'pendaftaran' => 'Baru',
        ]);
        $prevT2q->id = 50;
        $prevT2q->setRelation('anggotaKelas', $akLama);
        $prevT2q->setRelation('guruT2q', $guru);

        $siswa = Siswa::make([
            'nis' => '20260001',
            'nama_lengkap' => 'Siti Rahma',
            'jenis_kelamin' => 'P',
            'nisn' => '0011223344',
            'status' => 'Aktif',
        ]);
        $siswa->setRelation('anggotaT2q', new Collection([$prevT2q]));

        $anggotaKelas = AnggotaKelas::make([
            'siswa_nis' => '20260001',
            'akademik_master_kelas_id' => 12,
            'pendaftaran' => 'Naik Kelas',
        ]);
        $anggotaKelas->id = 30;
        $anggotaKelas->setRelation('siswa', $siswa);
        $anggotaKelas->setRelation('kelas', $kelas);

        $anggotaT2q = AnggotaT2q::make([
            'anggota_kelas_id' => 30,
            'karyawan_nipy' => '19900101-1',
            'tingkat' => '1',
            'pendaftaran' => 'Lanjutan',
            'keterangan' => 'Jilid 3',
        ]);
        $anggotaT2q->id = 99;
        $anggotaT2q->setRelation('anggotaKelas', $anggotaKelas);
        $anggotaT2q->setRelation('guruT2q', $guru);

        view()->share('errors', new ViewErrorBag);

        $indexHtml = view('admin.dataMaster.anggotaT2q.index', [
            'anggotaT2q' => new Collection([$anggotaT2q]),
            'selectedTahunAjaranId' => null,
            'selectedKaryawanNipy' => null,
            'selectedTingkat' => null,
            'selectedKelasId' => null,
            'tahunAjaran' => new Collection([$tahunAjaran]),
            'karyawan' => new Collection([$guru]),
            'tingkatOptions' => AnggotaT2q::DEFAULT_TINGKAT_OPTIONS,
            'kelas' => new Collection([$kelas]),
        ])->render();

        $this->assertStringContainsString('Data Anggota T2Q', $indexHtml);
        $this->assertStringContainsString('Siti Rahma', $indexHtml);
        $this->assertStringContainsString('1 Al-Fatihah', $indexHtml);
        $this->assertStringContainsString('Tingkat 1', $indexHtml);
        $this->assertStringContainsString('Ustadzah Fatimah, S.Pd', $indexHtml);
        $this->assertStringContainsString('Jilid 3', $indexHtml);

        $sidebarHtml = view('layouts.sidebar')->render();
        $this->assertStringContainsString(route('anggota-t2q.index'), $sidebarHtml);
        $this->assertStringContainsString('Anggota T2Q', $sidebarHtml);

        $createHtml = view('admin.dataMaster.anggotaT2q.create', [
            'tahunAjaran' => new Collection([$tahunAjaran]),
            'selectedTahunAjaranId' => 10,
            'karyawan' => new Collection([$guru]),
            'tingkatOptions' => AnggotaT2q::DEFAULT_TINGKAT_OPTIONS,
            'anggotaKelas' => new Collection([$anggotaKelas]),
            'excludedTahunAjaranIds' => [10],
            'hiddenRegisteredCount' => 2,
            'hiddenInactiveCount' => 1,
            'hiddenWithoutKelasCount' => 3,
            'preselectedKaryawanNipy' => '19900101-1',
            'preselectedTingkat' => AnggotaT2q::TINGKAT_AUTO,
            'preselectedPendaftaran' => 'Lanjutan',
            'preselectedKeterangan' => null,
        ])->render();

        $this->assertStringContainsString('Tambah Anggota T2Q', $createHtml);
        $this->assertStringContainsString('Otomatis (Ikuti Tingkatan Kelas Siswa)', $createHtml);
        $this->assertStringContainsString('name="anggota_kelas_id[]"', $createHtml);
        $this->assertStringContainsString('T2Q Sebelumnya', $createHtml);
        $this->assertStringContainsString('Tingkat B', $createHtml);
        $this->assertStringContainsString('2 sudah terdaftar T2Q (disembunyikan)', $createHtml);

        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML($createHtml);
        libxml_clear_errors();
        $xpath = new DOMXPath($document);
        $pickerItems = $xpath->query('//label[contains(@class, "siswa-option-item")]');
        $this->assertNotFalse($pickerItems);
        $this->assertSame(1, $pickerItems->length);

        $editHtml = view('admin.dataMaster.anggotaT2q.edit', [
            'anggotaT2q' => $anggotaT2q,
            'karyawan' => new Collection([$guru]),
            'tingkatOptions' => AnggotaT2q::DEFAULT_TINGKAT_OPTIONS,
            'anggotaKelas' => new Collection([$anggotaKelas]),
        ])->render();

        $this->assertStringContainsString('Edit Anggota T2Q', $editHtml);
        $this->assertStringContainsString('Samakan dg Kelas', $editHtml);
        $this->assertStringContainsString('value="1" selected', preg_replace('/\s+/', ' ', $editHtml) ?? '');
    }
}
