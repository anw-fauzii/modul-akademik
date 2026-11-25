<?php

namespace Tests\Unit;

use App\Http\Requests\Admin\DataMaster\BatalkanMutasiRequest;
use App\Http\Requests\Admin\DataMaster\KelulusanBatchRequest;
use App\Http\Requests\Admin\DataMaster\MutasiKeluarRequest;
use App\Http\Requests\Admin\DataMaster\MutasiMasukRequest;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\SiswaMutasi;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\User;
use App\Policies\AdminOnlyPolicy;
use App\Services\Admin\DataMaster\AnggotaKelasService;
use App\Services\Admin\DataMaster\SiswaMutasiService;
use App\Services\Admin\DataMaster\SiswaService;
use App\Support\Sqid;
use DOMDocument;
use DOMXPath;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;
use ReflectionMethod;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SiswaMutasiModelAndViewTest extends TestCase
{
    public function test_siswa_mutasi_model_constants_accessors_and_sqid_isolation(): void
    {
        $mutasi = new SiswaMutasi;
        $mutasi->setRawAttributes([
            'id' => 77,
            'siswa_nis' => '202601001',
            'kategori' => SiswaMutasi::KATEGORI_LULUS,
            'status_sebelum' => Siswa::STATUS_AKTIF,
            'status_sesudah' => Siswa::STATUS_LULUS,
            'tanggal_efektif' => '2026-06-20',
            'dibatalkan_at' => null,
        ]);

        $this->assertSame('Kelulusan / Alumni', $mutasi->kategori_label);
        $this->assertSame('badge-info', $mutasi->kategori_badge_class);
        $this->assertFalse($mutasi->is_dibatalkan);
        $this->assertSame(Siswa::STATUS_LULUS, SiswaMutasi::KATEGORI_TARGET_STATUS_MAP[SiswaMutasi::KATEGORI_LULUS]);
        $this->assertArrayNotHasKey(SiswaMutasi::KATEGORI_LULUS, SiswaMutasi::KATEGORI_KELUAR_OPTIONS);
        $this->assertArrayHasKey(SiswaMutasi::KATEGORI_PINDAH, SiswaMutasi::KATEGORI_KELUAR_OPTIONS);

        $token = Sqid::encodeFor(SiswaMutasi::class, 77);
        $this->assertNotNull($token);
        $this->assertSame(77, Sqid::decodeFor(SiswaMutasi::class, $token));
        $this->assertNull(Sqid::decodeFor(Kelas::class, $token));
        $this->assertNull(Sqid::decodeFor(Siswa::class, $token));

        $mutasi->dibatalkan_at = now();
        $this->assertTrue($mutasi->is_dibatalkan);
    }

    public function test_admin_only_policy_restricts_siswa_mutasi_to_super_admin(): void
    {
        $policy = new AdminOnlyPolicy;
        $mutasi = new SiswaMutasi;

        $superAdmin = $this->makeUserWithRole('super admin');
        $waliKelas = $this->makeUserWithRole('wali kelas');

        $this->assertTrue($policy->before($superAdmin, 'viewAny'));
        $this->assertTrue($policy->viewAny($waliKelas)->denied());
        $this->assertTrue($policy->create($waliKelas)->denied());
        $this->assertTrue($policy->update($waliKelas, $mutasi)->denied());
        $this->assertTrue($policy->delete($waliKelas, $mutasi)->denied());
    }

    public function test_service_normalizes_filters_including_sqid_tahun_ajaran_and_active_flag(): void
    {
        $service = new SiswaMutasiService(new SiswaService, new AnggotaKelasService);

        $emptyFilters = $service->normalizeFilters([]);
        $this->assertFalse($emptyFilters['hasActiveFilter']);
        $this->assertSame('aktif', $emptyFilters['status_log']);

        $taToken = Sqid::encodeFor(TahunAjaran::class, 12);
        $activeFilters = $service->normalizeFilters([
            'unit' => ' sd ',
            'tahun_ajaran_id' => $taToken,
            'kategori' => SiswaMutasi::KATEGORI_PINDAH,
            'status_log' => 'dibatalkan',
            'search' => '  202601  ',
        ]);

        $this->assertTrue($activeFilters['hasActiveFilter']);
        $this->assertSame('SD', $activeFilters['unit']);
        $this->assertSame('12', $activeFilters['tahun_ajaran_id']);
        $this->assertSame(SiswaMutasi::KATEGORI_PINDAH, $activeFilters['kategori']);
        $this->assertSame('dibatalkan', $activeFilters['status_log']);
        $this->assertSame('202601', $activeFilters['search']);

        $showAllFilters = $service->normalizeFilters(['show_all' => '1']);
        $this->assertTrue($showAllFilters['show_all']);
        $this->assertTrue($showAllFilters['hasActiveFilter']);
    }

    public function test_mutasi_requests_normalize_sqid_inputs_and_enforce_validation_rules(): void
    {
        $taToken = Sqid::encodeFor(TahunAjaran::class, 9);
        $kelasToken = Sqid::encodeFor(Kelas::class, 14);
        $nisTokenA = Sqid::encodeFor(Siswa::class, '202601001');
        $nisTokenB = Sqid::encodeFor(Siswa::class, '202601002');

        // 1. MutasiKeluarRequest: normalisasi & wajib sekolah tujuan saat pindah
        $keluarReq = MutasiKeluarRequest::create('/data-master/siswa-mutasi/mutasi-keluar', 'POST', [
            'siswa_nis' => [$nisTokenA, $nisTokenA, $nisTokenB],
            'kategori' => SiswaMutasi::KATEGORI_PINDAH,
            'akademik_master_tahun_ajaran_id' => $taToken,
            'tanggal_efektif' => '2026-06-15',
            'sekolah_asal_tujuan' => '',
            'alasan' => '  Pindah ikut orang tua  ',
        ]);
        $this->invokePrepareForValidation($keluarReq);

        $this->assertSame(['202601001', '202601002'], $keluarReq->input('siswa_nis'));
        $this->assertSame(9, $keluarReq->input('akademik_master_tahun_ajaran_id'));

        $keluarValidator = Validator::make($keluarReq->all(), $this->stripDbRules($keluarReq->rules()));
        $this->assertTrue($keluarValidator->fails());
        $this->assertArrayHasKey('sekolah_asal_tujuan', $keluarValidator->errors()->toArray());

        // Saat kategori 'keluar', sekolah_asal_tujuan boleh kosong
        $keluarNonPindahReq = MutasiKeluarRequest::create('/data-master/siswa-mutasi/mutasi-keluar', 'POST', [
            'siswa_nis' => [$nisTokenA],
            'kategori' => SiswaMutasi::KATEGORI_KELUAR,
            'akademik_master_tahun_ajaran_id' => $taToken,
            'tanggal_efektif' => '2026-06-15',
            'alasan' => 'Mengundurkan diri resmi',
        ]);
        $this->invokePrepareForValidation($keluarNonPindahReq);
        $this->assertFalse(
            Validator::make($keluarNonPindahReq->all(), $this->stripDbRules($keluarNonPindahReq->rules()))->fails()
        );

        // 2. KelulusanBatchRequest: normalisasi SQID & nomor_ijazah[nis]
        $lulusReq = KelulusanBatchRequest::create('/data-master/siswa-mutasi/kelulusan', 'POST', [
            'akademik_master_tahun_ajaran_id' => $taToken,
            'kelas_id' => $kelasToken,
            'siswa_nis' => [$nisTokenA],
            'tanggal_efektif' => '2026-06-20',
            'nomor_surat' => '  421/SK-LULUS/2026  ',
            'nomor_ijazah' => [$nisTokenA => '  DN-01/001  '],
        ]);
        $this->invokePrepareForValidation($lulusReq);

        $this->assertSame(9, $lulusReq->input('akademik_master_tahun_ajaran_id'));
        $this->assertSame(14, $lulusReq->input('kelas_id'));
        $this->assertSame(['202601001' => 'DN-01/001'], $lulusReq->input('nomor_ijazah'));
        $this->assertFalse(Validator::make($lulusReq->all(), $this->stripDbRules($lulusReq->rules()))->fails());

        // 3. MutasiMasukRequest: mewarisi biodata SiswaRequest & memaksa status Aktif
        $masukReq = MutasiMasukRequest::create('/data-master/siswa-mutasi/mutasi-masuk', 'POST', [
            'akademik_master_tahun_ajaran_id' => $taToken,
            'kelas_tujuan_id' => $kelasToken,
            'tanggal_efektif' => '2026-07-15',
            'sekolah_asal_tujuan' => '  SDIT Harapan Bangsa  ',
            'nis' => ' 20260999 ',
            'nama_lengkap' => '  Rizky   Pratama ',
            'jenis_kelamin' => 'L',
            'status' => Siswa::STATUS_PINDAH,
        ]);
        $this->invokePrepareForValidation($masukReq);

        $this->assertSame(Siswa::STATUS_AKTIF, $masukReq->input('status'));
        $this->assertSame('Pindahan', $masukReq->input('jenis_pendaftaran'));
        $this->assertSame(14, $masukReq->input('akademik_master_kelas_id'));
        $this->assertSame('Rizky Pratama', $masukReq->input('nama_lengkap'));
        $this->assertFalse(Validator::make($masukReq->all(), $this->stripDbRules($masukReq->rules()))->fails());

        // 4. BatalkanMutasiRequest: alasan_batal minimal 5 karakter
        $batalReq = BatalkanMutasiRequest::create('/data-master/siswa-mutasi/abc/batalkan', 'POST', [
            'alasan_batal' => 'abc',
        ]);
        $this->invokePrepareForValidation($batalReq);
        $this->assertTrue(Validator::make($batalReq->all(), $batalReq->rules())->fails());
    }

    public function test_all_siswa_mutasi_views_and_siswa_show_mutasi_tab_render_without_lazy_loading(): void
    {
        $ta = new TahunAjaran;
        $ta->setRawAttributes([
            'id' => 5,
            'nama_tahun_ajaran' => '2025/2026',
            'semester' => '2',
            'is_active' => true,
        ]);

        $kelas = new Kelas;
        $kelas->setRawAttributes([
            'id' => 11,
            'akademik_master_tahun_ajaran_id' => 5,
            'nama_kelas' => '6 Al-Fatih',
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => 6,
        ]);
        $kelas->setRelation('tahunAjaran', $ta);

        $siswa = new Siswa;
        $siswa->setRawAttributes([
            'nis' => '202601001',
            'akademik_master_kelas_id' => 11,
            'nama_lengkap' => 'Ahmad Fauzi',
            'jenis_kelamin' => 'L',
            'nisn' => '0123456789',
            'status' => Siswa::STATUS_LULUS,
        ]);
        $siswa->setRelation('kelas', $kelas);
        $siswa->setRelation('karyawan', null);
        $siswa->setRelation('ekstrakurikuler', null);
        $siswa->setRelation('jemputan', null);
        $siswa->setRelation('berkebutuhanKhusus', null);
        $siswa->setRelation('transportasi', null);
        $siswa->setRelation('jenjangPendidikanAyah', null);
        $siswa->setRelation('pekerjaanAyah', null);
        $siswa->setRelation('penghasilanAyah', null);
        $siswa->setRelation('berkebutuhanKhususAyah', null);
        $siswa->setRelation('jenjangPendidikanIbu', null);
        $siswa->setRelation('pekerjaanIbu', null);
        $siswa->setRelation('penghasilanIbu', null);
        $siswa->setRelation('berkebutuhanKhususIbu', null);
        $siswa->setRelation('jenjangPendidikanWali', null);
        $siswa->setRelation('pekerjaanWali', null);
        $siswa->setRelation('penghasilanWali', null);
        $siswa->setRelation('berkebutuhanKhususWali', null);
        $siswa->setRelation('prestasi', new Collection);

        $mutasi = new SiswaMutasi;
        $mutasi->setRawAttributes([
            'id' => 31,
            'siswa_nis' => '202601001',
            'akademik_master_tahun_ajaran_id' => 5,
            'kelas_asal_id' => 11,
            'kelas_tujuan_id' => null,
            'unit' => 'SD',
            'kategori' => SiswaMutasi::KATEGORI_LULUS,
            'status_sebelum' => Siswa::STATUS_AKTIF,
            'status_sesudah' => Siswa::STATUS_LULUS,
            'tanggal_efektif' => '2026-06-20',
            'nomor_surat' => '421/SK-LULUS/2026',
            'nomor_ijazah' => 'DN-01/001',
            'sekolah_asal_tujuan' => null,
            'alasan' => 'Lulus Tahun Pelajaran 2025/2026',
            'dibatalkan_at' => null,
            'created_by_name' => 'Super Admin',
        ]);
        $mutasi->setRelation('siswa', $siswa);
        $mutasi->setRelation('tahunAjaran', $ta);
        $mutasi->setRelation('kelasAsal', $kelas);
        $mutasi->setRelation('kelasTujuan', null);

        $siswa->setRelation('mutasiRiwayat', new Collection([$mutasi]));

        $defaultFilters = [
            'search' => '',
            'unit' => '',
            'tahun_ajaran_id' => '',
            'kategori' => '',
            'status_log' => 'aktif',
            'show_all' => false,
            'hasActiveFilter' => false,
        ];
        $stats = [
            'total_aktif' => 12,
            'total_lulus' => 8,
            'total_pindah' => 2,
            'total_keluar' => 1,
            'total_nonaktif' => 0,
            'total_masuk' => 1,
            'total_dibatalkan' => 1,
            'by_unit' => ['PG' => 2, 'TK' => 3, 'SD' => 7],
        ];

        View::share('errors', new ViewErrorBag);
        Model::preventLazyLoading(true);

        try {
            $unfilteredHtml = view('admin.dataMaster.siswaMutasi.index', [
                'filters' => $defaultFilters,
                'hasActiveFilter' => false,
                'mutasiList' => new Collection,
                'statistics' => $stats,
                'tahunAjaranOptions' => new Collection([$ta]),
                'unitOptions' => ['PG', 'TK', 'SD'],
                'kategoriOptions' => SiswaMutasi::KATEGORI_OPTIONS,
            ])->render();

            $activeEmptyHtml = view('admin.dataMaster.siswaMutasi.index', [
                'filters' => array_merge($defaultFilters, ['hasActiveFilter' => true, 'show_all' => true]),
                'hasActiveFilter' => true,
                'mutasiList' => new Collection,
                'statistics' => $stats,
                'tahunAjaranOptions' => new Collection([$ta]),
                'unitOptions' => ['PG', 'TK', 'SD'],
                'kategoriOptions' => SiswaMutasi::KATEGORI_OPTIONS,
            ])->render();

            $activePopulatedHtml = view('admin.dataMaster.siswaMutasi.index', [
                'filters' => array_merge($defaultFilters, ['hasActiveFilter' => true, 'show_all' => true]),
                'hasActiveFilter' => true,
                'mutasiList' => new Collection([$mutasi]),
                'statistics' => $stats,
                'tahunAjaranOptions' => new Collection([$ta]),
                'unitOptions' => ['PG', 'TK', 'SD'],
                'kategoriOptions' => SiswaMutasi::KATEGORI_OPTIONS,
            ])->render();

            $kelulusanHtml = view('admin.dataMaster.siswaMutasi.kelulusan', [
                'tahunAjaranOptions' => new Collection([$ta]),
                'selectedTahunAjaranId' => 5,
                'kelasOptions' => new Collection([$kelas]),
                'selectedKelas' => $kelas,
                'candidates' => new Collection([$siswa]),
                'finalisasiSummary' => ['is_sd' => true, 'mapel_final_count' => 0],
            ])->render();

            $mutasiKeluarHtml = view('admin.dataMaster.siswaMutasi.mutasi-keluar', [
                'tahunAjaranOptions' => new Collection([$ta]),
                'activeTahunAjaranId' => 5,
                'kelasOptions' => new Collection([$kelas]),
                'kategoriOptions' => SiswaMutasi::KATEGORI_KELUAR_OPTIONS,
                'activeStudents' => new Collection([$siswa]),
            ])->render();

            $mutasiMasukHtml = view('admin.dataMaster.siswaMutasi.mutasi-masuk', [
                'tahunAjaranOptions' => new Collection([$ta]),
                'activeTahunAjaranId' => 5,
                'kelas' => new Collection([$kelas]),
                'karyawan' => new Collection,
                'ekstrakurikuler' => new Collection,
                'jemputan' => new Collection,
                'berkebutuhanKhusus' => new Collection,
                'transportasi' => new Collection,
                'jenjangPendidikan' => new Collection,
                'pekerjaan' => new Collection,
                'penghasilan' => new Collection,
            ])->render();

            $siswaShowHtml = view('admin.dataMaster.siswa.show', [
                'siswa' => $siswa,
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        // 1. Filter-first index does not render #myTable3 until active filter
        $this->assertStringNotContainsString('id="myTable3"', $unfilteredHtml);
        $this->assertStringContainsString('Pilih Filter atau Tampilkan Semua Catatan', $unfilteredHtml);

        // 2. Active empty index obeys DataTables contract (8 th, 0 tr, 0 colspan in tbody)
        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML($activeEmptyHtml);
        libxml_clear_errors();
        $xpath = new DOMXPath($document);
        $this->assertSame(8, $xpath->query('//table[@id="myTable3"]/thead/tr/th')->length);
        $this->assertSame(0, $xpath->query('//table[@id="myTable3"]/tbody/tr')->length);
        $this->assertSame(0, $xpath->query('//table[@id="myTable3"]/tbody//*[@colspan]')->length);

        // 3. Populated index & sub-pages render expected content
        $this->assertStringContainsString('Ahmad Fauzi', $activePopulatedHtml);
        $this->assertStringContainsString('421/SK-LULUS/2026', $activePopulatedHtml);
        $this->assertStringContainsString('Perhatian Guardrail Rapor SD', $kelulusanHtml);
        $this->assertStringContainsString('6 Al-Fatih', $kelulusanHtml);
        $this->assertStringContainsString('Simpan Mutasi Keluar', $mutasiKeluarHtml);
        $this->assertStringContainsString('Simpan Mutasi Masuk &amp; Biodata Siswa', $mutasiMasukHtml);
        $this->assertStringContainsString('name="kelas_tujuan_id"', $mutasiMasukHtml);
        $this->assertStringNotContainsString('name="akademik_master_kelas_id"', $mutasiMasukHtml);
        $this->assertStringContainsString('data-siswa-tab="mutasi"', $siswaShowHtml);
        $this->assertStringContainsString('panel-mutasi', $siswaShowHtml);
        $this->assertStringContainsString('DN-01/001', $siswaShowHtml);
    }

    private function makeUserWithRole(string $roleName): User
    {
        $role = new Role;
        $role->setRawAttributes(['id' => 1, 'name' => $roleName, 'guard_name' => 'web']);

        $user = new User;
        $user->setRawAttributes(['id' => 1, 'name' => 'Test User', 'email' => 'admin@primainsani.sch.id']);
        $user->setRelation('roles', new Collection([$role]));
        $user->setRelation('permissions', new Collection);

        return $user;
    }

    private function invokePrepareForValidation(object $request): void
    {
        $method = new ReflectionMethod($request, 'prepareForValidation');
        $method->invoke($request);
    }

    /**
     * Menghapus rule berbasis query DB (exists/unique) agar validasi struktur tetap bisa diuji di Unit Test tanpa MySQL.
     *
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    private function stripDbRules(array $rules): array
    {
        $cleaned = [];

        foreach ($rules as $field => $fieldRules) {
            $list = is_array($fieldRules) ? $fieldRules : explode('|', (string) $fieldRules);
            $cleaned[$field] = array_values(array_filter($list, static function (mixed $rule): bool {
                if (is_string($rule)) {
                    return ! str_starts_with($rule, 'exists:') && ! str_starts_with($rule, 'unique:');
                }

                if ($rule instanceof Unique || $rule instanceof Exists) {
                    return false;
                }

                return true;
            }));
        }

        return $cleaned;
    }
}
