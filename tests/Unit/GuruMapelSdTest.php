<?php

namespace Tests\Unit;

use App\Http\Controllers\GuruMapel\DashboardController as GuruMapelDashboardController;
use App\Http\Controllers\GuruMapel\JadwalPelajaranController as GuruMapelJadwalController;
use App\Http\Controllers\GuruMapel\PenilaianEkstrakurikulerController as GuruMapelEkskulController;
use App\Http\Controllers\GuruMapel\PenilaianTpController as GuruMapelPenilaianTpController;
use App\Http\Controllers\GuruMapel\PerkembanganSiswaController as GuruMapelPerkembanganController;
use App\Http\Controllers\HomeController;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Informasi\AgendaSekolah;
use App\Models\Admin\Informasi\Pengumuman;
use App\Models\Admin\Pelajaran\JadwalPelajaran;
use App\Models\Admin\Pelajaran\JamPelajaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Admin\Penilaian\PenilaianFinalisasi;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Models\Admin\Penilaian\RekapMapel;
use App\Models\User;
use App\Services\Admin\Penilaian\PenilaianFinalisasiService;
use App\Services\GuruMapel\GuruMapelDashboardService;
use App\Services\GuruMapel\GuruMapelJadwalPelajaranService;
use App\Services\WaliKelas\WaliKelasDashboardService;
use App\Services\WaliKelas\WaliKelasEkstrakurikulerService;
use App\Services\WaliKelas\WaliKelasPenilaianService;
use App\Services\WaliKelas\WaliKelasPerkembanganService;
use App\Services\WaliKelas\WaliKelasScopeService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class GuruMapelSdTest extends TestCase
{
    private function makeUserWithRole(string $name, string $email, ?string $noInduk, string $roleName): User
    {
        $role = new Role;
        $role->setRawAttributes(['name' => $roleName, 'guard_name' => 'web']);
        $role->setRelation('permissions', new EloquentCollection);

        $user = new User;
        $user->setRawAttributes([
            'id' => 25,
            'name' => $name,
            'email' => $email,
            'no_induk' => $noInduk,
        ]);
        $user->setRelation('roles', new EloquentCollection([$role]));
        $user->setRelation('permissions', new EloquentCollection);

        return $user;
    }

    public function test_home_controller_and_controllers_delegate_guru_mapel_sd_requests(): void
    {
        $user = $this->makeUserWithRole('Ustadz Farhan', 'farhan@primainsani.sch.id', 'NIPY-GM-01', 'guru mapel sd');
        $waliService = $this->createMock(WaliKelasDashboardService::class);
        $waliService->expects($this->never())->method('getDashboardData');

        $gmDashService = $this->createMock(GuruMapelDashboardService::class);
        $gmDashService->expects($this->exactly(2))
            ->method('getDashboardData')
            ->with($user)
            ->willReturn(['isEligible' => false, 'ineligibleReason' => 'not_assigned_active_year', 'nipy' => 'NIPY-GM-01']);

        $request = Request::create('/', 'GET');
        $request->setUserResolver(static fn (): User => $user);

        $homeController = new HomeController($waliService, $gmDashService);
        $homeView = $homeController->index($request);
        $this->assertSame('guruMapel.dashboard', $homeView->name());

        $gmController = new GuruMapelDashboardController($gmDashService);
        $this->assertSame('guruMapel.dashboard', $gmController->index($request)->name());

        $gmJadwalService = $this->createMock(GuruMapelJadwalPelajaranService::class);
        $gmJadwalService->expects($this->once())
            ->method('getIndexData')
            ->with($user, [])
            ->willReturn(['isEligible' => false]);
        $gmJadwalController = new GuruMapelJadwalController($gmJadwalService);
        $this->assertSame('guruMapel.jadwalPelajaran.index', $gmJadwalController->index($request)->name());

        $wkPerkembanganService = $this->createMock(WaliKelasPerkembanganService::class);
        $wkPerkembanganService->expects($this->once())
            ->method('getIndexData')
            ->with($user, null, 'mid', true)
            ->willReturn(['isEligible' => false]);
        $gmPerkembanganController = new GuruMapelPerkembanganController($wkPerkembanganService);
        $this->assertSame('waliKelas.perkembanganSiswa.index', $gmPerkembanganController->index($request)->name());

        $wkPenilaianService = $this->createMock(WaliKelasPenilaianService::class);
        $wkPenilaianService->expects($this->once())
            ->method('getPageData')
            ->with($user, $this->anything(), false, 'guru-mapel')
            ->willReturn(['isEligible' => false]);
        $gmPenilaianController = new GuruMapelPenilaianTpController($wkPenilaianService);
        $this->assertSame('waliKelas.penilaian.ineligible', $gmPenilaianController->index($request)->name());

        $wkEkskulService = $this->createMock(WaliKelasEkstrakurikulerService::class);
        $wkEkskulService->expects($this->once())
            ->method('getPageData')
            ->with($user, null, null, null, null, true, 'guru-mapel')
            ->willReturn(['isEligible' => false]);
        $gmEkskulController = new GuruMapelEkskulController($wkEkskulService);
        $this->assertSame('waliKelas.ekstrakurikuler.index', $gmEkskulController->index($request)->name());
    }

    public function test_policies_authorize_and_isolate_guru_mapel_sd_permissions(): void
    {
        $guruMapel = $this->makeUserWithRole('Ustadz Farhan', 'farhan@primainsani.sch.id', 'NIPY-GM-01', 'guru mapel sd');

        $ta = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);
        $ta->id = 1;

        $kelasTaught = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'karyawan_nipy' => 'NIPY-WALI-99',
            'tingkatan_kelas' => '5',
            'nama_kelas' => '5 Ibnu Sina',
            'jenjang_kelas' => 'SD',
        ]);
        $kelasTaught->id = 10;

        $kelasOther = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'karyawan_nipy' => 'NIPY-WALI-98',
            'tingkatan_kelas' => '6',
            'nama_kelas' => '6 Al-Khawarizmi',
            'jenjang_kelas' => 'SD',
        ]);
        $kelasOther->id = 11;

        $mapelPjok = MataPelajaran::make(['singkatan' => 'PJOK', 'nama_mata_pelajaran' => 'PJOK', 'unit' => 'SD', 'status' => true]);
        $mapelPjok->id = 30;
        $mapelPai = MataPelajaran::make(['singkatan' => 'PAI', 'nama_mata_pelajaran' => 'PAI', 'unit' => 'SD', 'status' => true]);
        $mapelPai->id = 31;

        $anggotaTaught = AnggotaKelas::make(['akademik_master_kelas_id' => 10, 'siswa_nis' => '20260001']);
        $anggotaTaught->id = 101;
        $anggotaOther = AnggotaKelas::make(['akademik_master_kelas_id' => 11, 'siswa_nis' => '20260002']);
        $anggotaOther->id = 102;

        $jadwalOwn = JadwalPelajaran::make(['akademik_master_kelas_id' => 10, 'karyawan_nipy' => 'NIPY-GM-01']);
        $jadwalOther = JadwalPelajaran::make(['akademik_master_kelas_id' => 10, 'karyawan_nipy' => 'NIPY-LAIN']);

        $ekskulOwn = Ekstrakurikuler::make(['akademik_master_tahun_ajaran_id' => 1, 'karyawan_nipy' => 'NIPY-GM-01', 'nama_ekstrakurikuler' => 'Futsal', 'sifat' => 'Pilihan']);
        $ekskulOwn->id = 50;
        $ekskulOther = Ekstrakurikuler::make(['akademik_master_tahun_ajaran_id' => 1, 'karyawan_nipy' => 'NIPY-LAIN', 'nama_ekstrakurikuler' => 'Robotik', 'sifat' => 'Pilihan']);
        $ekskulOther->id = 51;

        $scopeMock = $this->createMock(WaliKelasScopeService::class);
        $scopeMock->method('authorizeTaughtSdKelasIdForUser')->willReturnCallback(
            static function (User $u, int $kelasId) use ($ta, $kelasTaught, $mapelPjok): array {
                if ($kelasId !== 10) {
                    abort(403, 'Unauthorized class');
                }

                return [
                    'isEligible' => true,
                    'ineligibleReason' => null,
                    'nipy' => (string) $u->no_induk,
                    'karyawanProfile' => null,
                    'activeTahunAjaran' => $ta,
                    'taughtClasses' => new EloquentCollection([$kelasTaught]),
                    'taughtMapelsByClass' => collect([10 => new EloquentCollection([$mapelPjok])]),
                    'selectedKelas' => $kelasTaught,
                    'taughtMapels' => new EloquentCollection([$mapelPjok]),
                ];
            }
        );
        $scopeMock->method('authorizeSdMapelScope')->willReturnCallback(
            static function (User $u, ?int $kelasId, ?int $mapelId) use ($ta, $kelasTaught, $mapelPjok): array {
                if ($kelasId !== 10 || $mapelId !== 30) {
                    abort(403, 'Unauthorized mapel');
                }

                return [
                    'scope' => ['isEligible' => true],
                    'activeTahunAjaran' => $ta,
                    'taughtClasses' => new EloquentCollection([$kelasTaught]),
                    'sdClasses' => new EloquentCollection([$kelasTaught]),
                    'selectedKelas' => $kelasTaught,
                    'taughtMapelsByClass' => collect([10 => new EloquentCollection([$mapelPjok])]),
                    'allowedMapels' => new EloquentCollection([$mapelPjok]),
                ];
            }
        );
        $this->app->instance(WaliKelasScopeService::class, $scopeMock);

        $ekskulServiceMock = $this->createMock(WaliKelasEkstrakurikulerService::class);
        $ekskulServiceMock->method('authorizeEkstrakurikulerForUser')->willReturnCallback(
            static function (User $u, Ekstrakurikuler $eks): void {
                if ($eks->karyawan_nipy !== $u->no_induk) {
                    abort(403, 'Unauthorized ekskul');
                }
            }
        );
        $this->app->instance(WaliKelasEkstrakurikulerService::class, $ekskulServiceMock);

        $finalisasiServiceMock = $this->createMock(PenilaianFinalisasiService::class);
        $finalisasiServiceMock->method('authorizeActorForComponent')->willReturnCallback(
            static function (User $u, array $ctx): void {
                if (($ctx['jenis_komponen'] ?? '') === PenilaianFinalisasi::JENIS_MAPEL && (int) ($ctx['mata_pelajaran_id'] ?? 0) === 30) {
                    return;
                }
                if (($ctx['jenis_komponen'] ?? '') === PenilaianFinalisasi::JENIS_EKSKUL_PILIHAN && (int) ($ctx['ekstrakurikuler_id'] ?? 0) === 50) {
                    return;
                }
                abort(403, 'Forbidden finalisasi component');
            }
        );
        $this->app->instance(PenilaianFinalisasiService::class, $finalisasiServiceMock);

        $registrar = app(PermissionRegistrar::class);
        $permProp = new \ReflectionProperty($registrar, 'permissions');
        $permProp->setAccessible(true);
        $permProp->setValue($registrar, new EloquentCollection);

        $this->assertTrue(Gate::forUser($guruMapel)->allows('viewAny', Kelas::class));
        $this->assertTrue(Gate::forUser($guruMapel)->denies('view', $kelasTaught));
        $this->assertTrue(Gate::forUser($guruMapel)->denies('viewT2qStatistik', $kelasTaught));

        $this->assertTrue(Gate::forUser($guruMapel)->allows('viewAny', JadwalPelajaran::class));
        $this->assertTrue(Gate::forUser($guruMapel)->allows('view', $jadwalOwn));
        $this->assertTrue(Gate::forUser($guruMapel)->denies('view', $jadwalOther));

        $this->assertTrue(Gate::forUser($guruMapel)->allows('viewAny', AnggotaKelas::class));
        $this->assertTrue(Gate::forUser($guruMapel)->allows('view', $anggotaTaught));
        $this->assertTrue(Gate::forUser($guruMapel)->denies('view', $anggotaOther));

        $this->assertTrue(Gate::forUser($guruMapel)->allows('viewPenilaian', Ekstrakurikuler::class));
        $this->assertTrue(Gate::forUser($guruMapel)->allows('inputNilai', $ekskulOwn));
        $this->assertTrue(Gate::forUser($guruMapel)->denies('inputNilai', $ekskulOther));

        $this->assertTrue(Gate::forUser($guruMapel)->allows('viewAny', RekapMapel::class));
        $this->assertTrue(Gate::forUser($guruMapel)->allows('regenerateDeskripsi', [RekapMapel::class, 10, $mapelPjok->id]));
        $this->assertTrue(Gate::forUser($guruMapel)->denies('regenerateDeskripsi', [RekapMapel::class, 10, $mapelPai->id]));

        $this->assertTrue(Gate::forUser($guruMapel)->allows('manageComponent', [PenilaianFinalisasi::class, [
            'jenis_komponen' => PenilaianFinalisasi::JENIS_MAPEL,
            'tahun_ajaran_id' => 1,
            'kelas_id' => 10,
            'mata_pelajaran_id' => 30,
            'periode' => 'mid',
        ]]));
        $this->assertTrue(Gate::forUser($guruMapel)->denies('manageComponent', [PenilaianFinalisasi::class, [
            'jenis_komponen' => PenilaianFinalisasi::JENIS_PRESENSI,
            'tahun_ajaran_id' => 1,
            'kelas_id' => 10,
            'periode' => 'mid',
        ]]));
        $this->assertTrue(Gate::forUser($guruMapel)->denies('manageComponent', [PenilaianFinalisasi::class, [
            'jenis_komponen' => PenilaianFinalisasi::JENIS_EKSKUL_WAJIB,
            'tahun_ajaran_id' => 1,
            'kelas_id' => 10,
            'periode' => 'mid',
        ]]));
    }

    public function test_guru_mapel_views_and_sidebar_render_cleanly_without_lazy_loading_or_rapor_actions(): void
    {
        $ta = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);
        $ta->id = 1;

        $guruKaryawan = Karyawan::make(['nipy' => 'NIPY-GM-01', 'nama_lengkap' => 'Farhan Maulana', 'gelar' => 'S.Or']);
        $user = $this->makeUserWithRole('Farhan Maulana', 'farhan@primainsani.sch.id', 'NIPY-GM-01', 'guru mapel sd');
        $user->setRelation('karyawan', $guruKaryawan);

        $kelas = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'karyawan_nipy' => 'NIPY-WALI-99',
            'tingkatan_kelas' => '5',
            'nama_kelas' => '5 Al-Fatih',
            'jenjang_kelas' => 'SD',
        ]);
        $kelas->id = 10;
        $kelas->setRelation('tahunAjaran', $ta);
        $kelas->setRelation('waliKelas', null);
        $kelas->setRelation('pendamping', null);

        $mapel = MataPelajaran::make(['singkatan' => 'PJOK', 'nama_mata_pelajaran' => 'Pendidikan Jasmani', 'unit' => 'SD', 'status' => true]);
        $mapel->id = 30;

        $jam = JamPelajaran::make(['jam_ke' => 2, 'unit' => 'SD', 'jam_mulai' => '08:05:00', 'jam_selesai' => '08:40:00']);
        $jam->id = 2;

        $slot = JadwalPelajaran::make(['akademik_master_kelas_id' => 10, 'akademik_pelajaran_jam_pelajaran_id' => 2, 'akademik_pelajaran_mata_pelajaran_id' => 30, 'hari' => 1, 'ruang' => 'Lapangan', 'status' => true]);
        $slot->id = 77;
        $slot->setRelation('kelas', $kelas);
        $slot->setRelation('jamPelajaran', $jam);
        $slot->setRelation('mataPelajaran', $mapel);

        $ekskul = Ekstrakurikuler::make(['akademik_master_tahun_ajaran_id' => 1, 'karyawan_nipy' => 'NIPY-GM-01', 'nama_ekstrakurikuler' => 'Futsal SD', 'unit' => 'SD', 'sifat' => 'Pilihan']);
        $ekskul->setRelation('pembina', $guruKaryawan);
        $ekskul->id = 50;
        $ekskul->anggota_ekstrakurikuler_count = 14;

        $agenda = AgendaSekolah::make(['akademik_master_tahun_ajaran_id' => 1, 'nama_agenda' => 'Classmeeting SD', 'tanggal' => '2026-11-25', 'jam' => '08:00:00', 'unit' => 'SD']);
        $pengumuman = Pengumuman::make(['akademik_master_tahun_ajaran_id' => 1, 'judul' => 'Jadwal Input Nilai TP', 'isi' => 'Input nilai paling lambat hari Jumat.']);

        $siswa = Siswa::make(['nis' => '20260001', 'nama_lengkap' => 'Nayla Putri', 'jenis_kelamin' => 'P']);
        $anggota = AnggotaKelas::make(['siswa_nis' => '20260001', 'akademik_master_kelas_id' => 10]);
        $anggota->id = 101;
        $anggota->setRelation('siswa', $siswa);

        Auth::shouldReceive('user')->andReturn($user);
        Model::preventLazyLoading(true);

        try {
            $sidebarHtml = view('layouts.sidebar')->render();
            $dashHtml = view('guruMapel.dashboard', [
                'isEligible' => true,
                'ineligibleReason' => null,
                'nipy' => 'NIPY-GM-01',
                'karyawanProfile' => $guruKaryawan,
                'activeTahunAjaran' => $ta,
                'todayDate' => Carbon::create(2026, 11, 16, 9, 0, 0),
                'todayDayLabel' => 'Senin',
                'isWeekday' => true,
                'periode' => PeriodeTp::PERIODE_MID,
                'periodeLabel' => 'Mid Semester',
                'taughtClasses' => new EloquentCollection([$kelas]),
                'taughtAssignments' => collect([[
                    'kelas' => $kelas,
                    'mapel' => $mapel,
                    'total_siswa' => 24,
                    'tp_count' => 4,
                    'dinilai_count' => 18,
                    'tuntas_count' => 16,
                    'avg_rapor' => 86.5,
                    'progress_pct' => 75,
                    'is_final' => false,
                ]]),
                'assignedEkskuls' => new EloquentCollection([$ekskul]),
                'jadwalHariIni' => new EloquentCollection([$slot]),
                'jadwalMingguanCount' => 1,
                'stats' => [
                    'total_classes' => 1,
                    'total_mapels' => 1,
                    'total_students' => 24,
                    'total_assignments' => 1,
                    'final_assignments' => 0,
                    'draft_assignments' => 1,
                    'ekskul_count' => 1,
                ],
                'upcomingAgendas' => new EloquentCollection([$agenda]),
                'latestPengumuman' => new EloquentCollection([$pengumuman]),
            ])->render();

            $jadwalHtml = view('guruMapel.jadwalPelajaran.index', [
                'isEligible' => true,
                'nipy' => 'NIPY-GM-01',
                'karyawanProfile' => $guruKaryawan,
                'activeTahunAjaran' => $ta,
                'taughtClasses' => new EloquentCollection([$kelas]),
                'jadwalPelajaran' => new EloquentCollection([$slot]),
                'weeklySlots' => [2 => [1 => [$slot]]],
                'jamSlots' => new EloquentCollection([$jam]),
                'hariOptions' => JadwalPelajaran::HARI_OPTIONS,
                'summary' => ['total' => 1, 'classes' => 1, 'per_hari' => [1 => 1, 2 => 0, 3 => 0, 4 => 0, 5 => 0]],
                'filters' => ['kelas_id' => '', 'hari' => '', 'search' => ''],
            ])->render();

            $perkembanganHtml = view('waliKelas.perkembanganSiswa.index', [
                'isEligible' => true,
                'isGuruMapelScope' => true,
                'canPrintRapor' => false,
                'perkembanganRoutePrefix' => 'guru-mapel.perkembangan-siswa',
                'dashboardRouteName' => 'guru-mapel.dashboard',
                'tpRoutePrefix' => 'guru-mapel.penilaian-tp',
                'peranDiKelas' => 'Guru Mapel SD',
                'activeTahunAjaran' => $ta,
                'assignedClasses' => new EloquentCollection([$kelas]),
                'selectedKelas' => $kelas,
                'periode' => PeriodeTp::PERIODE_MID,
                'isSd' => true,
                'students' => new EloquentCollection([$anggota]),
                'studentStats' => ['total' => 1, 'laki' => 0, 'perempuan' => 1, 'aktif' => 1],
                'mapels' => new EloquentCollection([$mapel]),
                'mapelSummaries' => collect([[
                    'mapel' => $mapel,
                    'kkm' => 75,
                    'teachers' => ['Farhan Maulana, S.Or'],
                    'is_taught_by_user' => true,
                    'dinilai_count' => 1,
                    'tuntas_count' => 1,
                    'belum_tuntas_count' => 0,
                    'avg_rapor' => 88.0,
                    'is_final' => false,
                    'finalized_by_name' => null,
                    'is_required_for_rapor' => false,
                ]]),
                'studentRows' => collect([[
                    'anggota' => $anggota,
                    'presensi' => ['total' => 1, 'hadir' => 1, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0, 'hadir_rate' => 100.0],
                    'mapel_cells' => [30 => ['nilai' => 88.0, 'predikat' => 'B', 'tuntas' => true]],
                    'dinilai_count' => 1,
                    'tuntas_count' => 1,
                    'belum_tuntas_count' => 0,
                    'rata_rapor' => 88.0,
                ]]),
                'overviewStats' => ['avg_rapor' => 88.0, 'tuntas_rate' => 100, 'scored_entries' => 1, 'tuntas_entries' => 1, 'need_attention_students' => 0, 'avg_kehadiran' => 100],
                'readiness' => null,
            ])->render();

            $ekskulHtml = view('waliKelas.ekstrakurikuler.index', [
                'isEligible' => true,
                'isGuruMapelScope' => true,
                'ekskulRoutePrefix' => 'guru-mapel.ekstrakurikuler',
                'finalisasiRoutePrefix' => 'guru-mapel.penilaian-finalisasi',
                'dashboardRouteName' => 'guru-mapel.dashboard',
                'mode' => 'pilihan',
                'periode' => PeriodeTp::PERIODE_MID,
                'nipy' => 'NIPY-GM-01',
                'karyawanProfile' => $guruKaryawan,
                'activeTahunAjaran' => $ta,
                'assignedClasses' => new EloquentCollection,
                'selectedKelas' => null,
                'peranDiKelas' => null,
                'assignedEkskuls' => new EloquentCollection([$ekskul]),
                'selectedEkskul' => $ekskul,
                'members' => new EloquentCollection,
                'predikatRanges' => $ekskul->predikatRanges(),
                'finalisasi' => null,
                'stats' => ['total' => 0, 'dinilai' => 0, 'belum_dinilai' => 0],
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        // Sidebar: 5 fitur Guru Mapel tampil, modul Wali Kelas / Admin tidak tampil
        $this->assertStringContainsString('Jadwal Mengajar Saya', $sidebarHtml);
        $this->assertStringContainsString('Perkembangan Siswa', $sidebarHtml);
        $this->assertStringContainsString('Penilaian Ekstrakurikuler', $sidebarHtml);
        $this->assertStringContainsString('Input Nilai TP', $sidebarHtml);
        $this->assertStringNotContainsString('Yaumiyah T2Q', $sidebarHtml);
        $this->assertStringNotContainsString('Data Pelengkap', $sidebarHtml);

        // Dashboard Guru Mapel SD
        $this->assertStringContainsString('Farhan Maulana', $dashHtml);
        $this->assertStringContainsString('Pendidikan Jasmani', $dashHtml);
        $this->assertStringContainsString('Futsal SD', $dashHtml);

        // Jadwal Mengajar Pribadi
        $this->assertStringContainsString('5 Al-Fatih', $jadwalHtml);
        $this->assertStringContainsString('Lapangan', $jadwalHtml);

        // Perkembangan Siswa (fokus mapel sendiri, tanpa Kesiapan Rapor / Cetak PDF Rapor)
        $this->assertStringContainsString('Pendidikan Jasmani', $perkembanganHtml);
        $this->assertStringContainsString('Nayla Putri', $perkembanganHtml);
        $this->assertStringNotContainsString('id="wkTabPanelKesiapan"', $perkembanganHtml);
        $this->assertStringNotContainsString('Nilai Rapor (1 Kelas)', $perkembanganHtml);
        $this->assertStringNotContainsString('Identitas (1 Kelas)', $perkembanganHtml);

        // Ekstrakurikuler Pilihan (tanpa Pramuka Wajib)
        $this->assertStringContainsString('Futsal SD', $ekskulHtml);
        $this->assertStringNotContainsString('Pramuka (Wajib)', $ekskulHtml);
    }

    public function test_guru_mapel_class_switchers_stay_clean_for_two_and_all_sd_classes(): void
    {
        $ta = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);
        $ta->id = 1;

        $wali = Karyawan::make(['nipy' => 'NIPY-WL-01', 'nama_lengkap' => 'Siti Aminah', 'gelar' => 'S.Pd']);
        $guruKaryawan = Karyawan::make(['nipy' => 'NIPY-GM-01', 'nama_lengkap' => 'Farhan Maulana', 'gelar' => 'S.Or']);
        $user = $this->makeUserWithRole('Farhan', 'farhan@example.com', 'NIPY-GM-01', 'guru mapel sd');
        $user->setRelation('karyawan', $guruKaryawan);

        $mapel = MataPelajaran::make(['singkatan' => 'PJOK', 'nama_mata_pelajaran' => 'Pendidikan Jasmani', 'unit' => 'SD']);
        $mapel->id = 30;

        $classes = new EloquentCollection;
        $idx = 100;
        foreach (['1', '2', '3', '4', '5', '6'] as $tingkat) {
            foreach (['A', 'B'] as $paralel) {
                $k = Kelas::make([
                    'akademik_master_tahun_ajaran_id' => 1,
                    'karyawan_nipy' => 'NIPY-WL-01',
                    'tingkatan_kelas' => $tingkat,
                    'nama_kelas' => $tingkat.$paralel,
                    'jenjang_kelas' => 'SD',
                ]);
                $k->id = $idx++;
                $k->setRelation('tahunAjaran', $ta);
                $k->setRelation('waliKelas', $wali);
                $k->setRelation('pendamping', null);
                $classes->push($k);
            }
        }

        $twoClasses = new EloquentCollection($classes->take(2)->all());
        $selectedKelas = $classes->first();

        $baseViewData = [
            'isEligible' => true,
            'isGuruMapelScope' => true,
            'canPrintRapor' => false,
            'perkembanganRoutePrefix' => 'guru-mapel.perkembangan-siswa',
            'dashboardRouteName' => 'guru-mapel.dashboard',
            'tpRoutePrefix' => 'guru-mapel.penilaian-tp',
            'peranDiKelas' => 'Guru Mapel SD',
            'nipy' => 'NIPY-GM-01',
            'activeTahunAjaran' => $ta,
            'selectedKelas' => $selectedKelas,
            'periode' => PeriodeTp::PERIODE_MID,
            'isSd' => true,
            'students' => new EloquentCollection,
            'studentStats' => ['total' => 0, 'laki' => 0, 'perempuan' => 0, 'aktif' => 0],
            'mapels' => new EloquentCollection([$mapel]),
            'mapelSummaries' => collect(),
            'studentRows' => collect(),
            'overviewStats' => ['avg_rapor' => null, 'tuntas_rate' => null, 'scored_entries' => 0, 'tuntas_entries' => 0, 'need_attention_students' => 0, 'avg_kehadiran' => null],
            'readiness' => null,
        ];

        Auth::shouldReceive('user')->andReturn($user);
        Model::preventLazyLoading(true);

        try {
            $twoClassHtml = view('waliKelas.perkembanganSiswa.index', [
                ...$baseViewData,
                'assignedClasses' => $twoClasses,
            ])->render();

            $allClassHtml = view('waliKelas.perkembanganSiswa.index', [
                ...$baseViewData,
                'assignedClasses' => $classes,
            ])->render();

            $allJadwalHtml = view('guruMapel.jadwalPelajaran.index', [
                'isEligible' => true,
                'nipy' => 'NIPY-GM-01',
                'karyawanProfile' => $guruKaryawan,
                'activeTahunAjaran' => $ta,
                'taughtClasses' => $classes,
                'jadwalPelajaran' => new EloquentCollection,
                'weeklySlots' => [],
                'jamSlots' => new EloquentCollection,
                'hariOptions' => JadwalPelajaran::HARI_OPTIONS,
                'summary' => ['total' => 0, 'classes' => 12, 'per_hari' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0]],
                'filters' => ['kelas_id' => '', 'hari' => '', 'search' => ''],
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        // Mode 2 kelas: label & pill rapi tanpa embel-embel (Pendamping)
        $this->assertStringContainsString('Kelas Diajar:', $twoClassHtml);
        $this->assertStringContainsString('Kelas 1A', $twoClassHtml);
        $this->assertStringContainsString('Kelas 1B', $twoClassHtml);
        $this->assertStringNotContainsString('(Pendamping)', $twoClassHtml);
        $this->assertStringNotContainsString('Kelas Binaan Aktif:', $twoClassHtml);

        // Mode semua kelas (12 kelas): beralih otomatis ke compact picker per tingkat
        $this->assertStringContainsString('data-wk-class-picker', $allClassHtml);
        $this->assertStringContainsString('1/12 Kelas', $allClassHtml);
        $this->assertStringContainsString('Tingkat 1', $allClassHtml);
        $this->assertStringContainsString('Tingkat 6', $allClassHtml);
        $this->assertStringNotContainsString('(Pendamping)', $allClassHtml);

        // Jadwal mengajar untuk banyak kelas menggunakan grouped <select>
        $this->assertStringContainsString('Semua Kelas (12)', $allJadwalHtml);
        $this->assertStringContainsString('<optgroup label="Tingkat 6">', $allJadwalHtml);
    }
}
