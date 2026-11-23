<?php

namespace Tests\Unit;

use App\Http\Controllers\GuruT2q\DashboardController as GuruT2qDashboardController;
use App\Http\Controllers\GuruT2q\FinalisasiT2qController;
use App\Http\Controllers\GuruT2q\PenilaianEkstrakurikulerController as GuruT2qPenilaianEkstrakurikulerController;
use App\Http\Controllers\GuruT2q\YaumiyahTahfizhController as GuruT2qYaumiyahTahfizhController;
use App\Http\Controllers\GuruT2q\YaumiyahTahsinController as GuruT2qYaumiyahTahsinController;
use App\Http\Controllers\HomeController;
use App\Http\Requests\Admin\T2q\YaumiyahTahsinRequest;
use App\Models\Admin\DataMaster\AnggotaEkstrakurikuler;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\AnggotaT2q;
use App\Models\Admin\DataMaster\AngkaArab;
use App\Models\Admin\DataMaster\DaftarJilid;
use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\Surah;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\DataMaster\TargetCapaian;
use App\Models\Admin\Informasi\AgendaSekolah;
use App\Models\Admin\Informasi\Pengumuman;
use App\Models\Admin\Pelajaran\JadwalPelajaran;
use App\Models\Admin\Penilaian\AspekTp;
use App\Models\Admin\Penilaian\PenilaianFinalisasi;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Models\Admin\Penilaian\RekapMapel;
use App\Models\Admin\Penilaian\SumatifBab;
use App\Models\Admin\T2q\YaumiyahTahfizh;
use App\Models\Admin\T2q\YaumiyahTahsin;
use App\Models\User;
use App\Services\Admin\Penilaian\PenilaianFinalisasiService;
use App\Services\Admin\T2q\YaumiyahTahfizhService;
use App\Services\Admin\T2q\YaumiyahTahsinService;
use App\Services\GuruMapel\GuruMapelDashboardService;
use App\Services\GuruT2q\GuruT2qDashboardService;
use App\Services\GuruT2q\GuruT2qScopeService;
use App\Services\GuruT2q\GuruT2qYaumiyahService;
use App\Services\WaliKelas\WaliKelasDashboardService;
use App\Services\WaliKelas\WaliKelasEkstrakurikulerService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class GuruT2qTest extends TestCase
{
    private function makeUserWithRole(string $name, string $email, ?string $noInduk, string $roleName, ?Karyawan $karyawan = null): User
    {
        $role = new Role;
        $role->setRawAttributes(['name' => $roleName, 'guard_name' => 'web']);
        $role->setRelation('permissions', new EloquentCollection);

        $user = new User;
        $user->setRawAttributes([
            'id' => 40,
            'name' => $name,
            'email' => $email,
            'no_induk' => $noInduk,
        ]);
        $user->setRelation('roles', new EloquentCollection([$role]));
        $user->setRelation('permissions', new EloquentCollection);
        $user->setRelation('karyawan', $karyawan);

        return $user;
    }

    public function test_home_and_guru_t2q_controllers_delegate_correctly(): void
    {
        $guruKaryawan = Karyawan::make(['nipy' => 'NIPY-T2Q-01', 'nama_lengkap' => 'Ustadz Abdullah', 'gelar' => 'S.Pd.I']);
        $user = $this->makeUserWithRole('Abdullah', 'abdullah@primainsani.sch.id', 'NIPY-T2Q-01', 'guru t2q', $guruKaryawan);

        $waliService = $this->createMock(WaliKelasDashboardService::class);
        $waliService->expects($this->never())->method('getDashboardData');

        $guruMapelService = $this->createMock(GuruMapelDashboardService::class);
        $guruMapelService->expects($this->never())->method('getDashboardData');

        $expectedDash = ['isEligible' => false, 'ineligibleReason' => 'not_assigned_active_year'];
        $guruT2qService = $this->createMock(GuruT2qDashboardService::class);
        $guruT2qService->expects($this->exactly(2))
            ->method('getDashboardData')
            ->with($user)
            ->willReturn($expectedDash);

        $homeRequest = Request::create('/home', 'GET');
        $homeRequest->setUserResolver(static fn (): User => $user);

        $homeController = new HomeController($waliService, $guruMapelService, $guruT2qService);
        $homeView = $homeController->index($homeRequest);
        $this->assertSame('guruT2q.dashboard', $homeView->name());

        $dashController = new GuruT2qDashboardController($guruT2qService);
        $dashView = $dashController->index($homeRequest);
        $this->assertSame('guruT2q.dashboard', $dashView->name());
    }

    public function test_guru_t2q_controllers_scope_yaumiyah_and_finalisasi_requests(): void
    {
        $guruKaryawan = Karyawan::make(['nipy' => 'NIPY-T2Q-01', 'nama_lengkap' => 'Ustadz Abdullah', 'gelar' => 'S.Pd.I']);
        $user = $this->makeUserWithRole('Abdullah', 'abdullah@primainsani.sch.id', 'NIPY-T2Q-01', 'guru t2q', $guruKaryawan);

        $facade = $this->createMock(GuruT2qYaumiyahService::class);
        $facade->expects($this->once())
            ->method('getWorkspacePageData')
            ->with($user, 'tahsin', ['tanggal' => '2026-11-23', 'tingkat' => 'PG'])
            ->willReturn(['isEligible' => false, 'ineligibleReason' => 'not_assigned_active_year']);
        $facade->expects($this->once())
            ->method('getMatriksPageData')
            ->with($user, 'tahfizh', ['tanggal' => '2026-11-23', 'tingkat' => '1'])
            ->willReturn(['isEligible' => false, 'ineligibleReason' => 'not_assigned_active_year']);
        $facade->expects($this->once())
            ->method('getStatistikPageData')
            ->with($user, 'tahsin', ['tingkat' => '7'])
            ->willReturn(['isEligible' => false, 'ineligibleReason' => 'not_assigned_active_year']);

        $tahsinWrite = $this->createMock(YaumiyahTahsinService::class);
        $tahsinWrite->expects($this->once())
            ->method('saveBatch')
            ->with('2026-11-23', [101 => ['nilai' => '90']], 'NIPY-T2Q-01')
            ->willReturn(1);

        $entryToDelete = new YaumiyahTahsin;
        $entryToDelete->id = 77;
        $tahsinWrite->expects($this->once())
            ->method('delete')
            ->with($entryToDelete)
            ->willReturn(true);

        $tahfizhWrite = $this->createMock(YaumiyahTahfizhService::class);

        $tahsinController = new GuruT2qYaumiyahTahsinController($facade, $tahsinWrite);
        $tahfizhController = new GuruT2qYaumiyahTahfizhController($facade, $tahfizhWrite);

        $reqWs = Request::create('/guru-t2q/yaumiyah-tahsin?tanggal=2026-11-23&tingkat=PG&karyawan_nipy=ORANG_LAIN', 'GET');
        $reqWs->setUserResolver(static fn (): User => $user);
        $this->assertSame('guruT2q.ineligible', $tahsinController->index($reqWs)->name());

        $reqMx = Request::create('/guru-t2q/yaumiyah-tahfizh/matriks?tanggal=2026-11-23&tingkat=1', 'GET');
        $reqMx->setUserResolver(static fn (): User => $user);
        $this->assertSame('guruT2q.ineligible', $tahfizhController->matriks($reqMx)->name());

        $reqSt = Request::create('/guru-t2q/yaumiyah-tahsin/statistik?tingkat=7', 'GET');
        $reqSt->setUserResolver(static fn (): User => $user);
        $this->assertSame('guruT2q.ineligible', $tahsinController->statistik($reqSt)->name());

        $storeRequest = new class($user) extends YaumiyahTahsinRequest
        {
            private User $actor;

            public function __construct(User $actor)
            {
                parent::__construct();
                $this->actor = $actor;
            }

            public function validated($key = null, $default = null): array
            {
                return [
                    'tanggal' => '2026-11-23',
                    'tingkat' => '1',
                    'rows' => [101 => ['nilai' => '90']],
                ];
            }

            public function input($key = null, $default = null): mixed
            {
                return match ($key) {
                    'tanggal' => '2026-11-23',
                    'tingkat' => '1',
                    default => $default,
                };
            }

            public function user($guard = null): User
            {
                return $this->actor;
            }
        };
        $storeResponse = $tahsinController->store($storeRequest);
        $this->assertSame(302, $storeResponse->getStatusCode());

        $deleteRequest = Request::create('/guru-t2q/yaumiyah-tahsin/77', 'DELETE', ['tanggal' => '2026-11-23', 'tingkat' => '1']);
        $deleteRequest->setUserResolver(static fn (): User => $user);
        $deleteResponse = $tahsinController->destroy($deleteRequest, $entryToDelete);
        $this->assertSame(302, $deleteResponse->getStatusCode());

        $scopeService = $this->createMock(GuruT2qScopeService::class);
        $scopeService->expects($this->once())
            ->method('finalizeBatchForUser')
            ->with($user, PeriodeTp::PERIODE_MID)
            ->willReturn(3);
        $scopeService->expects($this->once())
            ->method('unlockBatchForUser')
            ->with($user, PeriodeTp::PERIODE_MID, 'Revisi setoran harian santri')
            ->willReturn(3);

        $registrar = app(PermissionRegistrar::class);
        $permProp = new \ReflectionProperty($registrar, 'permissions');
        $permProp->setAccessible(true);
        $permProp->setValue($registrar, new EloquentCollection);

        $finalController = new FinalisasiT2qController($scopeService);

        $reqFin = Request::create('/guru-t2q/finalisasi', 'POST', ['periode' => PeriodeTp::PERIODE_MID]);
        $reqFin->setUserResolver(static fn (): User => $user);
        $this->assertSame(302, $finalController->finalize($reqFin)->getStatusCode());

        $reqUnl = Request::create('/guru-t2q/finalisasi/unlock', 'POST', [
            'periode' => PeriodeTp::PERIODE_MID,
            'unlock_reason' => 'Revisi setoran harian santri',
        ]);
        $reqUnl->setUserResolver(static fn (): User => $user);
        $this->assertSame(302, $finalController->unlock($reqUnl)->getStatusCode());

        $reqBadUnl = Request::create('/guru-t2q/finalisasi/unlock', 'POST', [
            'periode' => PeriodeTp::PERIODE_MID,
            'unlock_reason' => 'abc',
        ]);
        $reqBadUnl->setUserResolver(static fn (): User => $user);
        $this->expectException(ValidationException::class);
        $finalController->unlock($reqBadUnl);
    }

    public function test_guru_t2q_policies_enforce_strict_t2q_scope_and_own_delete_only(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guruKaryawan = Karyawan::make(['nipy' => 'NIPY-T2Q-01', 'nama_lengkap' => 'Ustadz Abdullah', 'gelar' => 'S.Pd.I']);
        $user = $this->makeUserWithRole('Abdullah', 'abdullah@primainsani.sch.id', 'NIPY-T2Q-01', 'guru t2q', $guruKaryawan);

        $activeTa = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);
        $activeTa->id = 1;
        $archivedTa = TahunAjaran::make(['nama_tahun_ajaran' => '2025/2026', 'semester' => '2', 'is_active' => false]);
        $archivedTa->id = 2;

        $kelasActive = Kelas::make(['akademik_master_tahun_ajaran_id' => 1, 'nama_kelas' => '1A']);
        $kelasActive->id = 10;
        $kelasActive->setRelation('tahunAjaran', $activeTa);

        $kelasArchived = Kelas::make(['akademik_master_tahun_ajaran_id' => 2, 'nama_kelas' => '1A']);
        $kelasArchived->id = 11;
        $kelasArchived->setRelation('tahunAjaran', $archivedTa);

        $akActive = AnggotaKelas::make(['akademik_master_kelas_id' => 10]);
        $akActive->id = 100;
        $akActive->setRelation('kelas', $kelasActive);

        $akArchived = AnggotaKelas::make(['akademik_master_kelas_id' => 11]);
        $akArchived->id = 101;
        $akArchived->setRelation('kelas', $kelasArchived);

        $ownAnggotaT2q = AnggotaT2q::make(['anggota_kelas_id' => 100, 'karyawan_nipy' => 'NIPY-T2Q-01', 'tingkat' => '1']);
        $ownAnggotaT2q->id = 200;
        $ownAnggotaT2q->setRelation('anggotaKelas', $akActive);

        $otherAnggotaT2q = AnggotaT2q::make(['anggota_kelas_id' => 100, 'karyawan_nipy' => 'NIPY-T2Q-99', 'tingkat' => '1']);
        $otherAnggotaT2q->id = 201;
        $otherAnggotaT2q->setRelation('anggotaKelas', $akActive);

        $archivedOwnAnggotaT2q = AnggotaT2q::make(['anggota_kelas_id' => 101, 'karyawan_nipy' => 'NIPY-T2Q-01', 'tingkat' => '1']);
        $archivedOwnAnggotaT2q->id = 202;
        $archivedOwnAnggotaT2q->setRelation('anggotaKelas', $akArchived);

        $ownEntry = YaumiyahTahsin::make(['anggota_t2q_id' => 200, 'nilai' => 88]);
        $ownEntry->id = 300;
        $ownEntry->setRelation('anggotaT2q', $ownAnggotaT2q);

        $otherEntry = YaumiyahTahsin::make(['anggota_t2q_id' => 201, 'nilai' => 88]);
        $otherEntry->id = 301;
        $otherEntry->setRelation('anggotaT2q', $otherAnggotaT2q);

        $archivedEntry = YaumiyahTahfizh::make(['anggota_t2q_id' => 202, 'nilai' => 85]);
        $archivedEntry->id = 302;
        $archivedEntry->setRelation('anggotaT2q', $archivedOwnAnggotaT2q);

        $registrar = app(PermissionRegistrar::class);
        $permProp = new \ReflectionProperty($registrar, 'permissions');
        $permProp->setAccessible(true);
        $permProp->setValue($registrar, new EloquentCollection);

        $ekskulOwn = Ekstrakurikuler::make(['akademik_master_tahun_ajaran_id' => 1, 'karyawan_nipy' => 'NIPY-T2Q-01', 'nama_ekstrakurikuler' => 'Panahan', 'sifat' => 'Pilihan']);
        $ekskulOwn->id = 50;
        $ekskulWajib = Ekstrakurikuler::make(['akademik_master_tahun_ajaran_id' => 1, 'karyawan_nipy' => 'NIPY-T2Q-01', 'nama_ekstrakurikuler' => 'Pramuka', 'sifat' => 'Wajib']);
        $ekskulWajib->id = 51;
        $ekskulOther = Ekstrakurikuler::make(['akademik_master_tahun_ajaran_id' => 1, 'karyawan_nipy' => 'NIPY-T2Q-99', 'nama_ekstrakurikuler' => 'Robotik', 'sifat' => 'Pilihan']);
        $ekskulOther->id = 52;

        $ekskulServiceMock = $this->createMock(WaliKelasEkstrakurikulerService::class);
        $ekskulServiceMock->method('authorizeEkstrakurikulerForUser')->willReturnCallback(
            static function (User $u, Ekstrakurikuler $eks): void {
                if (trim((string) $eks->karyawan_nipy) !== trim((string) $u->no_induk)) {
                    abort(403, 'Unauthorized ekskul');
                }
            }
        );
        $this->app->instance(WaliKelasEkstrakurikulerService::class, $ekskulServiceMock);

        $finalisasiServiceMock = $this->createMock(PenilaianFinalisasiService::class);
        $finalisasiServiceMock->method('authorizeActorForComponent')->willReturnCallback(
            static function (User $u, array $ctx): void {
                $jenis = (string) ($ctx['jenis_komponen'] ?? '');
                if ($jenis === PenilaianFinalisasi::JENIS_T2Q && (int) ($ctx['kelas_id'] ?? 0) === 10) {
                    return;
                }
                if ($jenis === PenilaianFinalisasi::JENIS_EKSKUL_PILIHAN && (int) ($ctx['ekstrakurikuler_id'] ?? 0) === 50) {
                    return;
                }
                abort(403, 'Unauthorized finalisasi component');
            }
        );
        $this->app->instance(PenilaianFinalisasiService::class, $finalisasiServiceMock);

        $gate = Gate::forUser($user);

        // Diizinkan untuk Guru T2Q
        $this->assertTrue($gate->allows('viewAny', Kelas::class));
        $this->assertTrue($gate->allows('viewAny', YaumiyahTahsin::class));
        $this->assertTrue($gate->allows('viewMatriks', YaumiyahTahsin::class));
        $this->assertTrue($gate->allows('viewStatistik', YaumiyahTahsin::class));
        $this->assertTrue($gate->allows('create', YaumiyahTahsin::class));
        $this->assertTrue($gate->allows('viewAny', YaumiyahTahfizh::class));
        $this->assertTrue($gate->allows('create', YaumiyahTahfizh::class));
        $this->assertTrue($gate->allows('delete', $ownEntry));
        $this->assertTrue($gate->allows('viewPenilaian', Ekstrakurikuler::class));
        $this->assertTrue($gate->allows('inputNilai', $ekskulOwn));
        $this->assertTrue($gate->allows('viewAny', PenilaianFinalisasi::class));
        $this->assertTrue($gate->allows('manageComponent', [PenilaianFinalisasi::class, [
            'jenis_komponen' => PenilaianFinalisasi::JENIS_T2Q,
            'tahun_ajaran_id' => 1,
            'kelas_id' => 10,
            'periode' => PeriodeTp::PERIODE_MID,
        ]]));
        $this->assertTrue($gate->allows('manageComponent', [PenilaianFinalisasi::class, [
            'jenis_komponen' => PenilaianFinalisasi::JENIS_EKSKUL_PILIHAN,
            'tahun_ajaran_id' => 1,
            'ekstrakurikuler_id' => 50,
            'periode' => PeriodeTp::PERIODE_MID,
        ]]));

        // Ditolak: catatan milik guru lain, tahun arsip, ekskul wajib, atau ekskul pilihan guru lain
        $this->assertTrue($gate->denies('delete', $otherEntry));
        $this->assertTrue($gate->denies('delete', $archivedEntry));
        $this->assertTrue($gate->denies('inputNilai', $ekskulWajib));
        $this->assertTrue($gate->denies('inputNilai', $ekskulOther));

        // Ditolak: finalisasi non-T2Q / non-Ekskul Pilihan & seluruh modul kelas/mapel
        $this->assertTrue($gate->denies('manageComponent', [PenilaianFinalisasi::class, [
            'jenis_komponen' => PenilaianFinalisasi::JENIS_MAPEL,
            'tahun_ajaran_id' => 1,
            'kelas_id' => 10,
            'mata_pelajaran_id' => 1,
            'periode' => PeriodeTp::PERIODE_MID,
        ]]));
        $this->assertTrue($gate->denies('view', $kelasActive));
        $this->assertTrue($gate->denies('viewAny', JadwalPelajaran::class));
        $this->assertTrue($gate->denies('viewAny', AnggotaKelas::class));
        $this->assertTrue($gate->denies('viewAny', RekapMapel::class));
        $this->assertTrue($gate->denies('viewAny', Ekstrakurikuler::class));
        $this->assertTrue($gate->denies('viewAny', AnggotaEkstrakurikuler::class));
        $this->assertTrue($gate->denies('viewAny', SumatifBab::class));
        $this->assertTrue($gate->denies('viewAny', AspekTp::class));
    }

    public function test_guru_t2q_views_and_sidebar_render_cleanly_without_lazy_loading_or_kelas_menus(): void
    {
        $ta = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);
        $ta->id = 1;

        $guruKaryawan = Karyawan::make(['nipy' => 'NIPY-T2Q-01', 'nama_lengkap' => 'Abdullah Malik', 'gelar' => 'S.Pd.I']);
        $user = $this->makeUserWithRole('Abdullah', 'abdullah@primainsani.sch.id', 'NIPY-T2Q-01', 'guru t2q', $guruKaryawan);

        $kelasPg = Kelas::make(['akademik_master_tahun_ajaran_id' => 1, 'nama_kelas' => 'PG Ceria', 'jenjang_kelas' => 'TK', 'tingkatan_kelas' => 'PG']);
        $kelasPg->id = 10;
        $kelasPg->setRelation('tahunAjaran', $ta);

        $kelasSmp = Kelas::make(['akademik_master_tahun_ajaran_id' => 1, 'nama_kelas' => '7 Al-Farabi', 'jenjang_kelas' => 'SMP', 'tingkatan_kelas' => '7']);
        $kelasSmp->id = 11;
        $kelasSmp->setRelation('tahunAjaran', $ta);

        $siswaPg = Siswa::make(['nis' => '20260010', 'nama_lengkap' => 'Zayd Ahmad', 'jenis_kelamin' => 'L']);
        $akPg = AnggotaKelas::make(['siswa_nis' => '20260010', 'akademik_master_kelas_id' => 10]);
        $akPg->id = 100;
        $akPg->setRelation('siswa', $siswaPg);
        $akPg->setRelation('kelas', $kelasPg);

        $siswaSmp = Siswa::make(['nis' => '20260020', 'nama_lengkap' => 'Hana Khairunnisa', 'jenis_kelamin' => 'P']);
        $akSmp = AnggotaKelas::make(['siswa_nis' => '20260020', 'akademik_master_kelas_id' => 11]);
        $akSmp->id = 101;
        $akSmp->setRelation('siswa', $siswaSmp);
        $akSmp->setRelation('kelas', $kelasSmp);

        $anggotaPg = AnggotaT2q::make(['anggota_kelas_id' => 100, 'karyawan_nipy' => 'NIPY-T2Q-01', 'tingkat' => 'PG', 'pendaftaran' => 'Baru']);
        $anggotaPg->id = 200;
        $anggotaPg->setRelation('anggotaKelas', $akPg);
        $anggotaPg->setRelation('guruT2q', $guruKaryawan);

        $anggotaSmp = AnggotaT2q::make(['anggota_kelas_id' => 101, 'karyawan_nipy' => 'NIPY-T2Q-01', 'tingkat' => '7', 'pendaftaran' => 'Lanjutan']);
        $anggotaSmp->id = 201;
        $anggotaSmp->setRelation('anggotaKelas', $akSmp);
        $anggotaSmp->setRelation('guruT2q', $guruKaryawan);

        $jilid = DaftarJilid::make(['jilid_latin' => 'Jilid 2', 'jilid_arab' => '٢', 'jenis' => 'halaman', 'urutan' => 2]);
        $jilid->id = 5;
        $surah = Surah::make(['nama_surah' => 'An-Naba', 'nama_surah_arab' => 'النبأ', 'jumlah_ayat' => 40]);
        $surah->id = 78;
        $angka = AngkaArab::make(['angka_latin' => '15', 'angka_arab' => '١٥']);
        $angka->id = 15;

        $tahsinToday = YaumiyahTahsin::make([
            'anggota_t2q_id' => 200,
            'akademik_master_t2q_daftar_jilid_id' => 5,
            'akademik_master_t2q_angka_arab_id' => 15,
            'tanggal' => '2026-11-23',
            'nilai' => 92,
        ]);
        $tahsinToday->id = 500;
        $tahsinToday->setRelation('daftarJilid', $jilid);
        $tahsinToday->setRelation('surah', null);
        $tahsinToday->setRelation('angkaArab', $angka);

        $targetPg = TargetCapaian::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'tingkat' => 'PG',
            'akademik_master_t2q_daftar_jilid_id' => 5,
            'akademik_master_t2q_surah_id' => 78,
        ]);
        $targetPg->id = 1;
        $targetPg->setRelation('daftarJilid', $jilid);
        $targetPg->setRelation('surah', $surah);

        $t2qFinalisasi = [
            'mid' => [
                'periode' => 'mid',
                'periode_label' => 'Mid Semester',
                'total_count' => 2,
                'final_count' => 0,
                'draft_count' => 2,
                'is_final' => false,
                'is_partial' => false,
                'status_label' => 'DRAFT (BELUM FINAL)',
                'can_finalize' => true,
                'can_unlock' => false,
            ],
            'semester' => [
                'periode' => 'semester',
                'periode_label' => 'Semester',
                'total_count' => 2,
                'final_count' => 0,
                'draft_count' => 2,
                'is_final' => false,
                'is_partial' => false,
                'status_label' => 'DRAFT (BELUM FINAL)',
                'can_finalize' => true,
                'can_unlock' => false,
            ],
        ];

        $agenda = AgendaSekolah::make(['akademik_master_tahun_ajaran_id' => 1, 'nama_agenda' => 'Munaqasyah T2Q', 'tanggal' => '2026-12-01', 'jam' => '08:00:00', 'unit' => 'Semua Unit']);
        $pengumuman = Pengumuman::make(['akademik_master_tahun_ajaran_id' => 1, 'judul' => 'Target Munaqasyah', 'isi' => 'Harap input setoran tepat waktu.']);
        $ekskulPilihan = Ekstrakurikuler::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'karyawan_nipy' => 'NIPY-T2Q-01',
            'nama_ekstrakurikuler' => 'Panahan',
            'unit' => 'SD',
            'sifat' => 'Pilihan',
            'kkm' => 75,
        ]);
        $ekskulPilihan->id = 50;
        $ekskulPilihan->setRelation('pembina', $guruKaryawan);
        $ekskulPilihan->setRelation('tahunAjaran', $ta);
        $ekskulPilihan->setAttribute('anggota_ekstrakurikuler_count', 14);

        Auth::shouldReceive('user')->andReturn($user);
        Model::preventLazyLoading(true);

        try {
            $sidebarHtml = view('layouts.sidebar')->render();

            $dashHtml = view('guruT2q.dashboard', [
                'isEligible' => true,
                'ineligibleReason' => null,
                'nipy' => 'NIPY-T2Q-01',
                'karyawanProfile' => $guruKaryawan,
                'activeTahunAjaran' => $ta,
                'todayDate' => Carbon::create(2026, 11, 23, 9, 0, 0),
                'weekRangeLabel' => '23 – 26 Nov 2026',
                'activePeriode' => PeriodeTp::PERIODE_MID,
                'tingkatSummaries' => collect([
                    ['tingkat' => 'PG', 'student_count' => 1, 'tahsin_today' => 1, 'tahfizh_today' => 0, 'targets' => ['Jilid 2 • An-Naba']],
                    ['tingkat' => '7', 'student_count' => 1, 'tahsin_today' => 0, 'tahfizh_today' => 0, 'targets' => []],
                ]),
                'studentRows' => collect([
                    ['anggota' => $anggotaPg, 'nama_siswa' => 'Zayd Ahmad', 'nis' => '20260010', 'tingkat' => 'PG', 'pendaftaran' => 'Baru', 'tahsin_today' => $tahsinToday, 'tahfizh_today' => null, 'pending_today' => true],
                    ['anggota' => $anggotaSmp, 'nama_siswa' => 'Hana Khairunnisa', 'nis' => '20260020', 'tingkat' => '7', 'pendaftaran' => 'Lanjutan', 'tahsin_today' => null, 'tahfizh_today' => null, 'pending_today' => true],
                ]),
                'assignedEkskuls' => new EloquentCollection([$ekskulPilihan]),
                't2qFinalisasi' => $t2qFinalisasi,
                'stats' => [
                    'total_students' => 2,
                    'baru_count' => 1,
                    'lanjutan_count' => 1,
                    'tingkat_count' => 2,
                    'tahsin_today_count' => 1,
                    'tahfizh_today_count' => 0,
                    'tahsin_today_pct' => 50,
                    'tahfizh_today_pct' => 0,
                    'week_recorded_count' => 1,
                    'week_avg_score' => 92.0,
                    'ekskul_count' => 1,
                ],
                'upcomingAgendas' => new EloquentCollection([$agenda]),
                'latestPengumuman' => new EloquentCollection([$pengumuman]),
            ])->render();

            $ekskulHtml = view('waliKelas.ekstrakurikuler.index', [
                'isEligible' => true,
                'isGuruMapelScope' => true,
                'ekskulRoutePrefix' => 'guru-t2q.ekstrakurikuler',
                'finalisasiRoutePrefix' => 'guru-t2q.penilaian-finalisasi',
                'dashboardRouteName' => 'guru-t2q.dashboard',
                'mode' => 'pilihan',
                'periode' => PeriodeTp::PERIODE_MID,
                'nipy' => 'NIPY-T2Q-01',
                'karyawanProfile' => $guruKaryawan,
                'activeTahunAjaran' => $ta,
                'assignedClasses' => new EloquentCollection,
                'selectedKelas' => null,
                'peranDiKelas' => null,
                'assignedEkskuls' => new EloquentCollection([$ekskulPilihan]),
                'selectedEkskul' => $ekskulPilihan,
                'members' => new EloquentCollection,
                'predikatRanges' => $ekskulPilihan->predikatRanges(),
                'finalisasi' => null,
                'stats' => ['total' => 0, 'dinilai' => 0, 'belum_dinilai' => 0],
            ])->render();

            $tahsinWorkspaceHtml = view('guruT2q.yaumiyahTahsin.index', [
                'mode' => 'tahsin',
                'usesDaftarJilid' => true,
                'isEligible' => true,
                'isGuruT2qScope' => true,
                'isWaliKelasScope' => false,
                'isReadOnly' => false,
                'routePrefix' => 'guru-t2q',
                'nipy' => 'NIPY-T2Q-01',
                'karyawanProfile' => $guruKaryawan,
                'activeTahunAjaran' => $ta,
                'tanggal' => '2026-11-23',
                'tanggalFormatted' => 'Senin, 23 November 2026',
                'guruOptions' => new EloquentCollection([$guruKaryawan]),
                'selectedGuruNipy' => 'NIPY-T2Q-01',
                'tingkatTabs' => ['PG' => 1, '7' => 1],
                'selectedTingkat' => 'PG',
                'students' => new EloquentCollection([$anggotaPg]),
                'todayEntries' => new EloquentCollection([200 => $tahsinToday]),
                'previousEntries' => new EloquentCollection,
                'savedCount' => 1,
                'targetCapaian' => new EloquentCollection([$targetPg]),
                'daftarJilidList' => new EloquentCollection([$jilid]),
                'surahList' => new EloquentCollection([$surah]),
                'angkaArabList' => new EloquentCollection([$angka]),
                't2qFinalisasi' => $t2qFinalisasi,
            ])->render();

            $matriksHtml = view('guruT2q.yaumiyahTahfizh.matriks', [
                'mode' => 'tahfizh',
                'usesDaftarJilid' => false,
                'isEligible' => true,
                'isGuruT2qScope' => true,
                'isWaliKelasScope' => false,
                'isReadOnly' => false,
                'routePrefix' => 'guru-t2q',
                'nipy' => 'NIPY-T2Q-01',
                'karyawanProfile' => $guruKaryawan,
                'activeTahunAjaran' => $ta,
                'tanggal' => '2026-11-23',
                'guruOptions' => new EloquentCollection([$guruKaryawan]),
                'selectedGuruNipy' => 'NIPY-T2Q-01',
                'tingkatTabs' => ['PG' => 1, '7' => 1],
                'selectedTingkat' => 'PG',
                'targetCapaian' => new EloquentCollection([$targetPg]),
                'period' => [
                    'anchor_date' => '2026-11-23',
                    'prev_week_date' => '2026-11-16',
                    'next_week_date' => '2026-11-30',
                    'current_week_date' => '2026-11-23',
                    'is_current_week' => true,
                    'range_label' => '23 – 26 Nov 2026',
                    'days' => [[
                        'date' => '2026-11-23',
                        'day_name' => 'Senin',
                        'short_day' => 'Sen',
                        'date_label' => '23 Nov',
                        'full_label' => 'Senin, 23 Nov 2026',
                        'is_today' => true,
                    ]],
                ],
                'matrix' => [
                    'days' => [[
                        'date' => '2026-11-23',
                        'day_name' => 'Senin',
                        'short_day' => 'Sen',
                        'date_label' => '23 Nov',
                        'full_label' => 'Senin, 23 Nov 2026',
                        'is_today' => true,
                    ]],
                    'rows' => [[
                        'anggota' => $anggotaPg,
                        'cells' => ['2026-11-23' => null],
                        'recorded_count' => 0,
                        'avg_nilai' => null,
                        'avg_formatted' => '-',
                        'avg_tier' => 'neutral',
                    ]],
                    'daily_totals' => ['2026-11-23' => ['recorded_count' => 0, 'avg_formatted' => '-', 'completion_pct' => 0]],
                    'summary' => ['total_students' => 1, 'total_recorded' => 0, 'possible_slots' => 1, 'completion_pct' => 0, 'overall_avg_formatted' => '-', 'overall_avg_tier' => 'neutral'],
                ],
            ])->render();

            $statistikHtml = view('guruT2q.yaumiyahTahsin.statistik', [
                'mode' => 'tahsin',
                'usesDaftarJilid' => true,
                'isEligible' => true,
                'isGuruT2qScope' => true,
                'isWaliKelasScope' => false,
                'isReadOnly' => false,
                'routePrefix' => 'guru-t2q',
                'nipy' => 'NIPY-T2Q-01',
                'karyawanProfile' => $guruKaryawan,
                'tahunAjaranOptions' => new EloquentCollection([$ta]),
                'selectedTahunAjaran' => $ta,
                'selectedTahunAjaranId' => 1,
                'guruOptions' => new EloquentCollection([$guruKaryawan]),
                'selectedGuruNipy' => 'NIPY-T2Q-01',
                'tingkatOptions' => ['PG' => 1, '7' => 1],
                'selectedTingkat' => '',
                'stats' => [
                    'summary' => ['total_students' => 2, 'active_students' => 1, 'scored_students' => 1, 'inactive_students' => 1, 'student_activity_pct' => 50, 'total_recorded' => 1, 'active_days_count' => 1, 'avg_per_student' => 0.5, 'overall_avg_formatted' => '92', 'overall_avg_tier' => 'excellent', 'overall_avg_label' => 'Sangat Berkembang', 'excellent_count' => 1, 'excellent_pct' => 100, 'best_month_label' => 'Nov 2026', 'best_month_count' => 1],
                    'target_achievement' => ['achieved_count' => 0, 'unachieved_count' => 1, 'no_target_count' => 1, 'evaluated_count' => 1, 'achieved_pct' => 0, 'evaluated_achieved_pct' => 0, 'donut_segments' => [], 'achieved_students' => [], 'unachieved_students' => []],
                    'tier_rows' => [],
                    'monthly_rows' => [],
                    'material_rows' => [],
                    'tingkat_rows' => [],
                    'guru_rows' => [],
                    'student_rows' => [],
                ],
            ])->render();

            $ineligibleHtml = view('guruT2q.ineligible', [
                'ineligibleReason' => 'not_assigned_active_year',
                'nipy' => 'NIPY-T2Q-01',
                'karyawanProfile' => $guruKaryawan,
                'activeTahunAjaran' => $ta,
                'pageTitle' => 'Yaumiyah Tahsin Guru T2Q',
                'heroTitle' => 'Yaumiyah Tahsin Bimbingan T2Q',
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        // Sidebar khusus Guru T2Q: ada Yaumiyah & Penilaian Ekskul, tanpa menu kelas/mapel/admin
        $this->assertStringContainsString('Yaumiyah Tahsin', $sidebarHtml);
        $this->assertStringContainsString('Yaumiyah Tahfizh', $sidebarHtml);
        $this->assertStringContainsString('Matriks (Sen–Kam)', $sidebarHtml);
        $this->assertStringContainsString('Statistik T2Q', $sidebarHtml);
        $this->assertStringContainsString('Rekap Rapor T2Q', $sidebarHtml);
        $this->assertStringContainsString('/guru-t2q/rapor', $sidebarHtml);
        $this->assertStringContainsString('Penilaian Ekstrakurikuler', $sidebarHtml);
        $this->assertStringContainsString('/guru-t2q/ekstrakurikuler', $sidebarHtml);
        $this->assertStringNotContainsString('Anggota Kelas', $sidebarHtml);
        $this->assertStringNotContainsString('Jadwal Pelajaran', $sidebarHtml);
        $this->assertStringNotContainsString('Perkembangan Siswa', $sidebarHtml);
        $this->assertStringNotContainsString('Data Pelengkap', $sidebarHtml);

        // Dashboard lintas jenjang + tombol finalisasi T2Q + kartu Ekstrakurikuler Pilihan + tanpa info rombel kelas
        $this->assertStringContainsString('Abdullah Malik', $dashHtml);
        $this->assertStringContainsString('Tingkat PG', $dashHtml);
        $this->assertStringContainsString('Tingkat 7', $dashHtml);
        $this->assertStringContainsString('Finalkan Mid Semester', $dashHtml);
        $this->assertStringContainsString('Zayd Ahmad', $dashHtml);
        $this->assertStringContainsString('Panahan', $dashHtml);
        $this->assertStringNotContainsString('PG Ceria', $dashHtml);
        $this->assertStringNotContainsString('7 Al-Farabi', $dashHtml);

        // Ekstrakurikuler Pilihan: tampil Panahan (Pilihan), Pramuka Wajib tersembunyi, route guru-t2q
        $this->assertStringContainsString('Panahan', $ekskulHtml);
        $this->assertStringContainsString('/guru-t2q/ekstrakurikuler', $ekskulHtml);
        $this->assertStringNotContainsString('Pramuka (Wajib)', $ekskulHtml);

        // Workspace input: terkunci ke guru bersangkutan, route guru-t2q, ada tombol hapus & finalisasi, tanpa rombel kelas
        $this->assertStringContainsString('/guru-t2q/yaumiyah-tahsin', $tahsinWorkspaceHtml);
        $this->assertStringContainsString('Finalkan Mid Semester', $tahsinWorkspaceHtml);
        $this->assertStringContainsString('js-t2q-delete', $tahsinWorkspaceHtml);
        $this->assertStringNotContainsString('Semua Guru T2Q', $tahsinWorkspaceHtml);
        $this->assertStringNotContainsString('PG Ceria', $tahsinWorkspaceHtml);

        // Matriks: tetap bisa masuk Input Harian (tidak read-only) & tanpa dropdown semua guru/kelas
        $this->assertStringContainsString('Input Harian', $matriksHtml);
        $this->assertStringNotContainsString('Lihat Saja', $matriksHtml);
        $this->assertStringNotContainsString('Semua Guru T2Q', $matriksHtml);
        $this->assertStringNotContainsString('PG Ceria', $matriksHtml);

        // Statistik: menyembunyikan tab komparasi antar guru ("Per Guru T2Q")
        $this->assertStringNotContainsString('data-st-tab="guru"', $statistikHtml);
        $this->assertStringContainsString('data-st-tab="tingkat"', $statistikHtml);
        $this->assertStringContainsString('data-st-tab="siswa"', $statistikHtml);

        // Guard view
        $this->assertStringContainsString('Belum Ada Daftar Bimbingan T2Q Aktif', $ineligibleHtml);
    }

    public function test_guru_t2q_ekstrakurikuler_controller_delegates_pilihan_only_scope(): void
    {
        $guruKaryawan = Karyawan::make(['nipy' => 'NIPY-T2Q-01', 'nama_lengkap' => 'Abdullah Malik', 'gelar' => 'S.Pd.I']);
        $user = $this->makeUserWithRole('Abdullah', 'abdullah@primainsani.sch.id', 'NIPY-T2Q-01', 'guru t2q', $guruKaryawan);

        $ekskul = Ekstrakurikuler::make(['akademik_master_tahun_ajaran_id' => 1, 'karyawan_nipy' => 'NIPY-T2Q-01', 'nama_ekstrakurikuler' => 'Panahan', 'sifat' => 'Pilihan']);
        $ekskul->id = 50;

        $ekskulService = $this->createMock(WaliKelasEkstrakurikulerService::class);
        $ekskulService->expects($this->once())
            ->method('getPageData')
            ->with($user, null, null, null, null, true, 'guru-t2q')
            ->willReturn(['isEligible' => false, 'guardReason' => 'no_assigned_ekskul', 'nipy' => 'NIPY-T2Q-01', 'karyawanProfile' => $guruKaryawan, 'activeTahunAjaran' => null, 'isGuruMapelScope' => true, 'ekskulRoutePrefix' => 'guru-t2q.ekstrakurikuler', 'finalisasiRoutePrefix' => 'guru-t2q.penilaian-finalisasi', 'dashboardRouteName' => 'guru-t2q.dashboard']);
        $ekskulService->expects($this->once())
            ->method('authorizeEkstrakurikulerForUser')
            ->with($user, $ekskul);
        $ekskulService->expects($this->once())
            ->method('savePenilaian');

        $controller = new GuruT2qPenilaianEkstrakurikulerController($ekskulService);

        $reqIdx = Request::create('/guru-t2q/ekstrakurikuler', 'GET');
        $reqIdx->setUserResolver(static fn (): User => $user);
        $view = $controller->index($reqIdx);
        $this->assertSame('waliKelas.ekstrakurikuler.index', $view->name());

        $reqStore = Request::create('/guru-t2q/ekstrakurikuler/50/penilaian', 'POST', [
            'periode' => PeriodeTp::PERIODE_MID,
            'nilai' => [1 => 88],
        ]);
        $reqStore->setUserResolver(static fn (): User => $user);
        $resp = $controller->store($reqStore, $ekskul);
        $this->assertSame(302, $resp->getStatusCode());
        $this->assertStringContainsString('/guru-t2q/ekstrakurikuler', (string) $resp->headers->get('Location'));
    }
}
