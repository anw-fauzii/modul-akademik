<?php

namespace Tests\Unit;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\WaliKelas\DashboardController;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Informasi\AgendaSekolah;
use App\Models\Admin\Informasi\Pengumuman;
use App\Models\Admin\Pelajaran\JadwalPelajaran;
use App\Models\Admin\Pelajaran\JamPelajaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Admin\Presensi\PresensiSiswa;
use App\Models\User;
use App\Services\WaliKelas\WaliKelasDashboardService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WaliKelasDashboardTest extends TestCase
{
    public function test_resolve_role_in_kelas_distinguishes_wali_kelas_and_pendamping(): void
    {
        $service = new WaliKelasDashboardService;
        $kelas = Kelas::make([
            'karyawan_nipy' => 'NIPY-001',
            'pendamping_nipy' => 'NIPY-002',
        ]);

        $this->assertSame('Wali Kelas', $service->resolveRoleInKelas($kelas, 'NIPY-001'));
        $this->assertSame('Guru Pendamping', $service->resolveRoleInKelas($kelas, 'NIPY-002'));
        $this->assertSame('Tidak Terdaftar', $service->resolveRoleInKelas($kelas, 'NIPY-999'));
    }

    private function makeUserWithRole(string $name, string $email, ?string $noInduk, string $roleName): User
    {
        $role = new Role;
        $role->setRawAttributes(['name' => $roleName, 'guard_name' => 'web']);

        $user = new User;
        $user->setRawAttributes([
            'id' => 1,
            'name' => $name,
            'email' => $email,
            'no_induk' => $noInduk,
        ]);
        $user->setRelation('roles', new EloquentCollection([$role]));

        return $user;
    }

    public function test_controllers_delegate_wali_kelas_dashboard_requests(): void
    {
        $ta = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);
        $ta->id = 1;

        $user = $this->makeUserWithRole('Ustadzah Sari', 'sari@primainsani.sch.id', 'NIPY-001', 'wali kelas');
        $user->setRelation('karyawan', Karyawan::make(['nipy' => 'NIPY-001', 'nama_lengkap' => 'Sari Wulandari']));

        $expectedPayload = [
            'isEligible' => false,
            'ineligibleReason' => 'not_assigned_active_year',
            'nipy' => 'NIPY-001',
            'karyawanProfile' => $user->karyawan,
            'activeTahunAjaran' => $ta,
            'assignedClasses' => new EloquentCollection,
            'selectedKelas' => null,
            'peranDiKelas' => null,
        ];

        $service = $this->createMock(WaliKelasDashboardService::class);
        $service->expects($this->exactly(2))
            ->method('getDashboardData')
            ->with($user, 12)
            ->willReturn($expectedPayload);

        $request = Request::create('/wali-kelas/dashboard?kelas_id=12', 'GET');
        $request->setUserResolver(static fn (): User => $user);

        $waliController = new DashboardController($service);
        $waliView = $waliController->index($request);
        $this->assertSame('waliKelas.dashboard', $waliView->name());

        $homeController = new HomeController($service);
        $homeView = $homeController->index($request);
        $this->assertSame('waliKelas.dashboard', $homeView->name());
    }

    public function test_wali_kelas_dashboard_views_render_eligible_and_ineligible_states_without_lazy_loading(): void
    {
        $ta = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);
        $ta->id = 1;

        $wali = Karyawan::make(['nipy' => 'NIPY-101', 'nama_lengkap' => 'Ahmad Fauzi', 'gelar' => 'S.Pd']);
        $pendamping = Karyawan::make(['nipy' => 'NIPY-102', 'nama_lengkap' => 'Rina Kartika', 'gelar' => 'S.Pd.I']);

        $kelas1 = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'karyawan_nipy' => 'NIPY-101',
            'pendamping_nipy' => 'NIPY-102',
            'tingkatan_kelas' => '5',
            'nama_kelas' => '5 Al-Fatih',
            'jenjang_kelas' => 'SD',
            'romawi' => 'V',
        ]);
        $kelas1->id = 10;
        $kelas1->setRelation('tahunAjaran', $ta);
        $kelas1->setRelation('waliKelas', $wali);
        $kelas1->setRelation('pendamping', $pendamping);

        $kelas2 = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'karyawan_nipy' => 'NIPY-109',
            'pendamping_nipy' => 'NIPY-101',
            'tingkatan_kelas' => '5',
            'nama_kelas' => '5 Ali',
            'jenjang_kelas' => 'SD',
            'romawi' => 'V',
        ]);
        $kelas2->id = 11;

        $siswa = Siswa::make(['nis' => '20260501', 'nama_lengkap' => 'Budi Pratama', 'jenis_kelamin' => 'L', 'nisn' => '0123456789']);
        $anggota = AnggotaKelas::make(['siswa_nis' => '20260501', 'akademik_master_kelas_id' => 10, 'pendaftaran' => 'Naik Kelas']);
        $anggota->id = 41;
        $anggota->setRelation('siswa', $siswa);

        $presensi = PresensiSiswa::make(['anggota_kelas_id' => 41, 'tanggal' => '2026-11-17', 'jam_masuk' => '07:15:00', 'status' => 'hadir']);

        $jam = JamPelajaran::make(['jam_ke' => 1, 'unit' => 'SD', 'jam_mulai' => '07:30:00', 'jam_selesai' => '08:05:00']);
        $mapel = MataPelajaran::make(['singkatan' => 'MTK', 'nama_mata_pelajaran' => 'Matematika', 'unit' => 'SD', 'sifat' => 'wajib']);
        $jadwal = JadwalPelajaran::make(['akademik_pelajaran_mata_pelajaran_id' => 2, 'hari' => 2, 'ruang' => 'Ruang 5A', 'status' => true]);
        $jadwal->setRelation('jamPelajaran', $jam);
        $jadwal->setRelation('mataPelajaran', $mapel);
        $jadwal->setRelation('guru', $wali);

        $agenda = AgendaSekolah::make(['akademik_master_tahun_ajaran_id' => 1, 'nama_agenda' => 'Pekan P5 Kelas 5', 'tanggal' => '2026-11-20', 'jam' => '08:00:00', 'unit' => 'SD']);
        $pengumuman = Pengumuman::make(['akademik_master_tahun_ajaran_id' => 1, 'judul' => 'Jadwal PSTS Semester 1', 'isi' => 'Pelaksanaan PSTS dimulai Senin depan.']);

        $userWali = $this->makeUserWithRole('Ahmad Fauzi', 'ahmad@primainsani.sch.id', 'NIPY-101', 'wali kelas');
        $userWali->setRelation('karyawan', $wali);

        Auth::shouldReceive('user')->andReturn($userWali);
        Model::preventLazyLoading(true);

        try {
            $eligibleHtml = view('waliKelas.dashboard', [
                'isEligible' => true,
                'ineligibleReason' => null,
                'nipy' => 'NIPY-101',
                'karyawanProfile' => $wali,
                'activeTahunAjaran' => $ta,
                'assignedClasses' => new EloquentCollection([$kelas1, $kelas2]),
                'selectedKelas' => $kelas1,
                'peranDiKelas' => 'Wali Kelas',
                'students' => new EloquentCollection([$anggota]),
                'studentStats' => ['total' => 1, 'laki' => 1, 'perempuan' => 0],
                'todayDate' => Carbon::create(2026, 11, 17, 9, 0, 0),
                'todayPresensiByAnggota' => [41 => $presensi],
                'presensiTodaySummary' => ['recorded' => 1, 'hadir' => 1, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0, 'belum' => 0],
                'presensiMonthSummary' => ['total' => 12, 'hadir_rate' => 91.7, 'hadir_dan_terlambat' => 11],
                'todayDayNumber' => 2,
                'todayDayLabel' => 'Selasa',
                'isWeekday' => true,
                'jadwalHariIni' => new EloquentCollection([$jadwal]),
                'jadwalMingguanCount' => 20,
                'penilaianSummary' => [
                    'periode' => 'mid',
                    'periode_label' => 'Mid Semester',
                    'mapels' => collect([[
                        'id' => 2,
                        'singkatan' => 'MTK',
                        'nama' => 'Matematika',
                        'sifat' => 'wajib',
                        'tp_count' => 6,
                        'dinilai_count' => 1,
                        'tuntas_count' => 1,
                        'avg_rapor' => 88.0,
                        'progress_pct' => 100,
                    ]]),
                    'total_mapel' => 1,
                    'assessed_mapel' => 1,
                ],
                'raporReadiness' => [
                    'is_sd' => true,
                    'periode' => 'mid',
                    'is_ready' => false,
                    'finalized_count' => 1,
                    'total_components' => 4,
                    'pending_count' => 3,
                    'progress_pct' => 25,
                    'components' => [],
                    'pending_components' => [[
                        'jenis_komponen' => 'ekskul_wajib',
                        'jenis_label' => 'Ekskul Wajib',
                        'nama' => 'Pramuka (Wajib)',
                        'owner_label' => 'Ahmad Fauzi, S.Pd',
                        'is_owned_by_user' => true,
                    ]],
                ],
                'upcomingAgendas' => new EloquentCollection([$agenda]),
                'latestPengumuman' => new EloquentCollection([$pengumuman]),
            ])->render();

            $this->assertStringContainsString('5 Al-Fatih', $eligibleHtml);
            $this->assertStringContainsString('5 Ali', $eligibleHtml);
            $this->assertStringContainsString('Rina Kartika, S.Pd.I', $eligibleHtml);
            $this->assertStringContainsString('Budi Pratama', $eligibleHtml);
            $this->assertStringContainsString('Matematika', $eligibleHtml);
            $this->assertStringContainsString('Ekstrakurikuler', $eligibleHtml);
            $this->assertStringContainsString('Yaumiyah T2Q', $eligibleHtml);
            $this->assertStringContainsString('wkDashPanelSiswa', $eligibleHtml);
            $this->assertStringContainsString('wkDashPanelMapel', $eligibleHtml);
            $this->assertStringContainsString('Kesiapan &amp; Finalisasi Rapor', $eligibleHtml);
            $this->assertStringContainsString('Pramuka (Wajib)', $eligibleHtml);
            $this->assertStringContainsString('Pekan P5 Kelas 5', $eligibleHtml);
            $this->assertStringContainsString('Jadwal PSTS Semester 1', $eligibleHtml);
            $this->assertStringContainsString('Dashboard Wali Kelas', $eligibleHtml);
            $this->assertStringNotContainsString('Data Pelengkap', $eligibleHtml);

            $ineligibleHtml = view('waliKelas.dashboard', [
                'isEligible' => false,
                'ineligibleReason' => 'not_assigned_active_year',
                'nipy' => 'NIPY-999',
                'karyawanProfile' => null,
                'activeTahunAjaran' => $ta,
                'assignedClasses' => new EloquentCollection,
                'selectedKelas' => null,
                'peranDiKelas' => null,
            ])->render();

            $this->assertStringContainsString('Belum Ada Penugasan Wali Kelas / Pendamping Aktif', $ineligibleHtml);
            $this->assertStringContainsString('NIPY-999', $ineligibleHtml);
            $this->assertStringNotContainsString('wkStudentsTable', $ineligibleHtml);
        } finally {
            Model::preventLazyLoading(false);
        }
    }
}
