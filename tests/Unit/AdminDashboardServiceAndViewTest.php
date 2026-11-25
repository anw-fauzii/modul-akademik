<?php

namespace Tests\Unit;

use App\Http\Controllers\HomeController;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Informasi\AgendaSekolah;
use App\Models\Admin\Informasi\Pengumuman;
use App\Models\Admin\Prestasi\PrestasiSiswa;
use App\Models\User;
use App\Services\Admin\Dashboard\AdminDashboardService;
use App\Services\GuruMapel\GuruMapelDashboardService;
use App\Services\GuruT2q\GuruT2qDashboardService;
use App\Services\WaliKelas\WaliKelasDashboardService;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use ReflectionMethod;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminDashboardServiceAndViewTest extends TestCase
{
    public function test_admin_dashboard_service_summarizes_classes_and_excludes_data_pelengkap_dependencies(): void
    {
        $service = new AdminDashboardService;

        $kelasSd = Kelas::make([
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => '5',
            'nama_kelas' => '5 Al-Kautsar',
        ]);
        $kelasSd->setAttribute('anggota_kelas_count', 26);

        $kelasTk = Kelas::make([
            'jenjang_kelas' => 'TK',
            'tingkatan_kelas' => 'TK B',
            'nama_kelas' => 'TK B Ar-Rahman',
        ]);
        $kelasTk->setAttribute('anggota_kelas_count', 18);

        $kelasPg = Kelas::make([
            'jenjang_kelas' => 'PG',
            'tingkatan_kelas' => 'PG',
            'nama_kelas' => 'PG Bintang',
        ]);
        $kelasPg->setAttribute('anggota_kelas_count', 12);

        $method = new ReflectionMethod(AdminDashboardService::class, 'summarizeClasses');
        /** @var array{total_kelas: int, total_siswa_rombel: int, per_unit: array<string, array{kelas: int, siswa: int}>} $summary */
        $summary = $method->invoke($service, new Collection([$kelasSd, $kelasTk, $kelasPg]));

        $this->assertSame(3, $summary['total_kelas']);
        $this->assertSame(56, $summary['total_siswa_rombel']);
        $this->assertSame(['PG', 'TK', 'SD'], array_keys($summary['per_unit']));
        $this->assertSame(['kelas' => 1, 'siswa' => 12], $summary['per_unit']['PG']);
        $this->assertSame(['kelas' => 1, 'siswa' => 18], $summary['per_unit']['TK']);
        $this->assertSame(['kelas' => 1, 'siswa' => 26], $summary['per_unit']['SD']);

        $serviceSource = (string) file_get_contents(app_path('Services/Admin/Dashboard/AdminDashboardService.php'));
        $controllerSource = (string) file_get_contents(app_path('Http/Controllers/HomeController.php'));

        $this->assertStringNotContainsString('DataPelengkap', $serviceSource);
        $this->assertStringNotContainsString('DataPelengkap', $controllerSource);
    }

    public function test_home_controller_delegates_admin_dashboard_to_admin_dashboard_service(): void
    {
        $adminRole = new Role(['name' => 'super admin', 'guard_name' => 'web']);
        $adminUser = User::make(['name' => 'Admin Akademik', 'email' => 'admin@example.test']);
        $adminUser->setRelation('roles', new Collection([$adminRole]));

        $expectedPayload = [
            'todayDate' => Carbon::parse('2026-11-25 08:00:00', 'Asia/Jakarta'),
            'activeTahunAjaran' => null,
            'totalTahunAjaran' => 2,
            'tahunAjaranList' => new Collection,
            'studentStats' => [
                'total' => 120,
                'aktif' => 115,
                'aktif_laki_laki' => 60,
                'aktif_perempuan' => 55,
                'punya_kelas' => 110,
                'belum_punya_kelas' => 5,
                'jenjang_aktif' => ['PG' => 15, 'TK' => 30, 'SD' => 70],
            ],
            'totalKaryawan' => 28,
            'totalSekolahAktif' => 3,
            'activeClasses' => new Collection,
            'kelasSummary' => ['total_kelas' => 9, 'total_siswa_rombel' => 110, 'per_unit' => []],
            'presensiTodaySummary' => ['total' => 0, 'hadir' => 0, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0, 'belum' => 110, 'hadir_rate' => 0.0],
            'operationalSummary' => ['mapel_aktif' => 12, 'tp_aktif' => 48, 'jadwal_aktif' => 60, 'ekskul_aktif' => 8, 'anggota_t2q' => 95, 't2q_tahsin_hari_ini' => 14, 't2q_tahfizh_hari_ini' => 9, 'finalisasi_final' => 5],
            'prestasiSummary' => ['table_ready' => false, 'total' => 0, 'akademik' => 0, 'non_akademik' => 0, 'beregu' => 0, 'dengan_foto' => 0, 'per_unit' => []],
            'recentPrestasi' => new Collection,
            'upcomingAgendas' => new Collection,
            'latestPengumuman' => new Collection,
        ];

        $adminService = $this->createMock(AdminDashboardService::class);
        $adminService->expects($this->once())
            ->method('getDashboardData')
            ->willReturn($expectedPayload);

        $controller = new HomeController(
            $this->createMock(WaliKelasDashboardService::class),
            $this->createMock(GuruMapelDashboardService::class),
            $this->createMock(GuruT2qDashboardService::class),
            $adminService
        );

        $request = Request::create('/home', 'GET');
        $request->setUserResolver(static fn (): User => $adminUser);

        $view = $controller->index($request);
        $this->assertSame('home', $view->name());
        $this->assertSame($expectedPayload, $view->getData());

        $middlewares = collect(app()->make(HomeController::class)->getMiddleware())->pluck('middleware')->all();
        $this->assertContains('auth', $middlewares);
        $this->assertContains('role:super admin|wali kelas|guru mapel sd|guru t2q', $middlewares);
    }

    public function test_home_view_renders_modern_minimalist_dashboard_without_lazy_loading_or_data_pelengkap(): void
    {
        $tahunAjaran = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);
        $tahunAjaran->id = 1;

        $wali = Karyawan::make(['nipy' => '19890101', 'nama_lengkap' => 'Ustadz Ahmad', 'gelar' => 'S.Pd']);
        $kelas = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'tingkatan_kelas' => '5',
            'nama_kelas' => '5 Al-Kautsar',
            'jenjang_kelas' => 'SD',
        ]);
        $kelas->id = 12;
        $kelas->setRelation('waliKelas', $wali);
        $kelas->setRelation('pendamping', null);
        $kelas->setAttribute('anggota_kelas_count', 24);

        $siswa1 = Siswa::make(['nis' => '202601001', 'nama_lengkap' => 'Aisyah Humaira']);
        $siswa2 = Siswa::make(['nis' => '202601002', 'nama_lengkap' => 'Zaid Al-Faruq']);

        $prestasi = PrestasiSiswa::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'nama_prestasi' => 'Lomba Sains Nasional SD',
            'kategori' => PrestasiSiswa::KATEGORI_AKADEMIK,
            'tingkat' => 'Nasional',
            'peringkat' => 'Juara 1',
            'penyelenggara' => 'Kemendikdasmen',
            'tanggal' => '2026-11-24',
            'keterangan' => 'Tim Olimpiade Garuda SD',
        ]);
        $prestasi->id = 44;
        $prestasi->setRelation('siswa', new Collection([$siswa1, $siswa2]));
        $prestasi->setRelation('tahunAjaran', $tahunAjaran);

        $agenda = AgendaSekolah::make([
            'nama_agenda' => 'Penilaian Tengah Semester',
            'tanggal' => '2026-11-30',
            'jam' => '08:00',
            'unit' => 'SD',
            'jenis' => 'siswa',
        ]);

        $pengumuman = Pengumuman::make([
            'judul' => 'Libur Kegiatan Sekolah',
            'isi' => '<p>Kegiatan belajar mengajar diliburkan pada hari Sabtu.</p>',
            'jenis' => 'siswa',
        ]);

        Model::preventLazyLoading(true);
        try {
            $htmlReady = view('home', [
                'todayDate' => Carbon::parse('2026-11-25 09:00:00', 'Asia/Jakarta'),
                'activeTahunAjaran' => $tahunAjaran,
                'totalTahunAjaran' => 4,
                'tahunAjaranList' => new Collection([$tahunAjaran]),
                'studentStats' => [
                    'total' => 250,
                    'aktif' => 240,
                    'aktif_laki_laki' => 122,
                    'aktif_perempuan' => 118,
                    'punya_kelas' => 235,
                    'belum_punya_kelas' => 5,
                    'jenjang_aktif' => ['PG' => 20, 'TK' => 60, 'SD' => 160],
                ],
                'totalKaryawan' => 35,
                'totalSekolahAktif' => 3,
                'activeClasses' => new Collection([$kelas]),
                'kelasSummary' => [
                    'total_kelas' => 12,
                    'total_siswa_rombel' => 235,
                    'per_unit' => [
                        'PG' => ['kelas' => 2, 'siswa' => 20],
                        'TK' => ['kelas' => 4, 'siswa' => 55],
                        'SD' => ['kelas' => 6, 'siswa' => 160],
                    ],
                ],
                'presensiTodaySummary' => [
                    'total' => 228,
                    'hadir' => 210,
                    'terlambat' => 10,
                    'izin' => 3,
                    'sakit' => 4,
                    'alpa' => 1,
                    'belum' => 7,
                    'hadir_rate' => 93.6,
                ],
                'operationalSummary' => [
                    'mapel_aktif' => 14,
                    'tp_aktif' => 86,
                    'jadwal_aktif' => 72,
                    'ekskul_aktif' => 9,
                    'anggota_t2q' => 180,
                    't2q_tahsin_hari_ini' => 45,
                    't2q_tahfizh_hari_ini' => 32,
                    'finalisasi_final' => 8,
                ],
                'prestasiSummary' => [
                    'table_ready' => true,
                    'total' => 18,
                    'akademik' => 10,
                    'non_akademik' => 8,
                    'beregu' => 5,
                    'dengan_foto' => 14,
                    'per_unit' => ['PG' => 1, 'TK' => 4, 'SD' => 13],
                ],
                'recentPrestasi' => new Collection([$prestasi]),
                'upcomingAgendas' => new Collection([$agenda]),
                'latestPengumuman' => new Collection([$pengumuman]),
            ])->render();

            $htmlDefault = view('home')->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $mainReady = $this->extractMainInnerHtml($htmlReady);
        $mainDefault = $this->extractMainInnerHtml($htmlDefault);
        $homeSource = (string) file_get_contents(resource_path('views/home.blade.php'));

        $this->assertStringContainsString('PANEL ADMIN AKADEMIK', $mainReady);
        $this->assertStringContainsString('Sebaran Unit &amp; Kelas Tahun Ajaran Aktif', $mainReady);
        $this->assertStringContainsString('5 Al-Kautsar', $mainReady);
        $this->assertStringContainsString('Ustadz Ahmad, S.Pd', $mainReady);
        $this->assertStringContainsString('Presensi Hari Ini', $mainReady);
        $this->assertStringContainsString('Setoran T2Q Hari Ini', $mainReady);
        $this->assertStringContainsString('Lomba Sains Nasional SD', $mainReady);
        $this->assertStringContainsString('Tim Olimpiade Garuda SD', $mainReady);
        $this->assertStringContainsString('Penilaian Tengah Semester', $mainReady);
        $this->assertStringContainsString('Libur Kegiatan Sekolah', $mainReady);
        $this->assertStringContainsString('admClassSearch', $htmlReady);

        $excludedTerms = [
            'berkebutuhan-khusus',
            'jenjang-pendidikan',
            'pekerjaan',
            'penghasilan',
            'transportasi',
            'Modul Data Pelengkap',
            'Data Penunjang Siswa',
            'Kelengkapan Data Pelengkap',
        ];
        foreach ($excludedTerms as $excluded) {
            $this->assertStringNotContainsString($excluded, $mainReady);
            $this->assertStringNotContainsString($excluded, $mainDefault);
            $this->assertStringNotContainsString($excluded, $homeSource);
        }

        $this->assertStringContainsString('PANEL ADMIN AKADEMIK', $mainDefault);
    }

    private function extractMainInnerHtml(string $fullHtml): string
    {
        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML($fullHtml);
        libxml_clear_errors();

        $xpath = new DOMXPath($document);
        $nodes = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " app-main__inner ")]');
        $this->assertNotFalse($nodes);
        $this->assertGreaterThan(0, $nodes->length);

        $node = $nodes->item(0);
        $this->assertNotNull($node);

        return (string) $document->saveHTML($node);
    }
}
