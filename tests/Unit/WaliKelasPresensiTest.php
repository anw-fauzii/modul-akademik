<?php

namespace Tests\Unit;

use App\Http\Controllers\WaliKelas\PresensiSiswaController;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Presensi\PresensiSiswa;
use App\Models\User;
use App\Services\Admin\Presensi\PresensiSiswaService;
use App\Services\WaliKelas\WaliKelasPresensiService;
use App\Services\WaliKelas\WaliKelasScopeService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ViewErrorBag;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class WaliKelasPresensiTest extends TestCase
{
    private function makeUserWithRole(string $name, string $email, ?string $noInduk, string $roleName, ?Karyawan $karyawan = null): User
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
        $user->setRelation('karyawan', $karyawan);

        return $user;
    }

    /**
     * @return array{ta: TahunAjaran, wali: Karyawan, kelas: Kelas, anggota: AnggotaKelas, presensi: PresensiSiswa}
     */
    private function makeClassPresensiFixtures(): array
    {
        $ta = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);
        $ta->id = 1;

        $wali = Karyawan::make(['nipy' => 'NIPY-001', 'nama_lengkap' => 'Sari Wulandari', 'gelar' => 'S.Pd']);
        $kelas = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'karyawan_nipy' => 'NIPY-001',
            'pendamping_nipy' => 'NIPY-002',
            'tingkatan_kelas' => '1',
            'nama_kelas' => '1 Al-Fatihah',
            'jenjang_kelas' => 'SD',
        ]);
        $kelas->id = 10;
        $kelas->setRelation('tahunAjaran', $ta);
        $kelas->setRelation('waliKelas', $wali);

        $siswa = Siswa::make([
            'nis' => '202601001',
            'nama_lengkap' => 'Ahmad Fauzi',
            'nisn' => '0123456789',
            'jenis_kelamin' => 'L',
            'status' => 'Aktif',
        ]);

        $anggota = AnggotaKelas::make([
            'siswa_nis' => '202601001',
            'akademik_master_kelas_id' => 10,
            'pendaftaran' => 'Siswa Baru',
        ]);
        $anggota->id = 55;
        $anggota->setRelation('siswa', $siswa);
        $anggota->setRelation('kelas', $kelas);

        $presensi = PresensiSiswa::make([
            'anggota_kelas_id' => 55,
            'tanggal' => '2026-11-16',
            'jam_masuk' => '07:20',
            'status' => 'hadir',
            'keterlambatan_menit' => 0,
        ]);
        $presensi->id = 701;
        $presensi->exists = true;
        $presensi->setRelation('anggotaKelas', $anggota);

        return compact('ta', 'wali', 'kelas', 'anggota', 'presensi');
    }

    public function test_authorize_presensi_siswa_blocks_foreign_class_and_allows_assigned_class_without_lazy_loading(): void
    {
        ['ta' => $ta, 'kelas' => $kelas, 'presensi' => $ownedPresensi] = $this->makeClassPresensiFixtures();
        $user = $this->makeUserWithRole('Ustadzah Sari', 'sari@primainsani.sch.id', 'NIPY-001', 'wali kelas');

        $foreignAnggota = AnggotaKelas::make(['siswa_nis' => '202609999', 'akademik_master_kelas_id' => 99]);
        $foreignAnggota->id = 999;
        $foreignPresensi = PresensiSiswa::make(['anggota_kelas_id' => 999, 'status' => 'hadir']);
        $foreignPresensi->id = 888;
        $foreignPresensi->setRelation('anggotaKelas', $foreignAnggota);

        $mockScope = $this->getMockBuilder(WaliKelasScopeService::class)
            ->onlyMethods(['resolveScopeContext'])
            ->getMock();

        $mockScope->expects($this->exactly(2))
            ->method('resolveScopeContext')
            ->willReturnCallback(static fn (User $u, ?int $kelasId): array => [
                'isEligible' => true,
                'ineligibleReason' => null,
                'nipy' => 'NIPY-001',
                'karyawanProfile' => null,
                'activeTahunAjaran' => $ta,
                'assignedClasses' => new EloquentCollection([$kelas]),
                'selectedKelas' => $kelas,
                'peranDiKelas' => 'Wali Kelas',
            ]);

        Model::preventLazyLoading(true);
        try {
            $scope = $mockScope->authorizePresensiSiswaForUser($user, $ownedPresensi, true);
            $this->assertSame($kelas, $scope['selectedKelas']);

            try {
                $mockScope->authorizePresensiSiswaForUser($user, $foreignPresensi);
                $this->fail('Seharusnya melempar 403 saat mengakses catatan presensi kelas lain.');
            } catch (HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
            }
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    public function test_service_blocks_foreign_kelas_queries_and_delegates_scoped_classes_to_admin_presensi_service(): void
    {
        ['ta' => $ta, 'wali' => $wali, 'kelas' => $kelas] = $this->makeClassPresensiFixtures();
        $assignedClasses = new EloquentCollection([$kelas]);
        $user = $this->makeUserWithRole('Ustadzah Sari', 'sari@primainsani.sch.id', 'NIPY-001', 'wali kelas', $wali);

        $scopeService = $this->createMock(WaliKelasScopeService::class);
        $scopeService->method('resolveScopeContext')
            ->willReturn([
                'isEligible' => true,
                'ineligibleReason' => null,
                'nipy' => 'NIPY-001',
                'karyawanProfile' => $wali,
                'activeTahunAjaran' => $ta,
                'assignedClasses' => $assignedClasses,
                'selectedKelas' => $kelas,
                'peranDiKelas' => 'Wali Kelas',
            ]);

        $scopeService->expects($this->once())
            ->method('authorizeKelasIdForUser')
            ->with($user, 99, $this->anything())
            ->willThrowException(new HttpException(403, 'Forbidden'));

        $adminService = $this->createMock(PresensiSiswaService::class);
        $adminService->expects($this->once())
            ->method('getIndexData')
            ->with(['kelas_id' => 10], $assignedClasses)
            ->willReturn(['matrix' => ['kelas' => $kelas], 'summary' => [], 'filters' => []]);

        $adminService->expects($this->once())
            ->method('getRekapData')
            ->with(['kelas_id' => 10], $assignedClasses)
            ->willReturn(['mode' => 'class_detail']);

        $adminService->expects($this->once())
            ->method('getTahunanData')
            ->with(['kelas_id' => 10], $assignedClasses)
            ->willReturn(['mode' => 'class_detail']);

        $service = new WaliKelasPresensiService($scopeService, $adminService);

        $indexResult = $service->getIndexPageData($user, ['kelas_id' => 10]);
        $this->assertSame('wali-kelas.presensi-siswa', $indexResult['presensiRoutePrefix']);
        $this->assertTrue($indexResult['isWaliKelasScope']);

        // Single-class teacher defaults kelas_id cleanly on rekap & tahunan when omitted
        $rekapResult = $service->getRekapPageData($user, [], false);
        $this->assertSame('class_detail', $rekapResult['mode']);

        $tahunanResult = $service->getTahunanPageData($user, [], false);
        $this->assertSame('class_detail', $tahunanResult['mode']);

        try {
            $service->getIndexPageData($user, ['kelas_id' => 99]);
            $this->fail('Seharusnya melempar 403 saat meminta kelas_id di luar kelas binaan.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        try {
            $service->storeBulkForUser($user, ['kelas_id' => 99, 'tanggal' => '2026-11-16', 'items' => []]);
            $this->fail('Seharusnya melempar 403 saat storeBulk untuk kelas di luar kelas binaan.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_controller_reuses_all_admin_presensi_views_with_scoped_routes_and_ineligible_guard(): void
    {
        ['ta' => $ta, 'wali' => $wali, 'kelas' => $kelas, 'anggota' => $anggota, 'presensi' => $presensi] = $this->makeClassPresensiFixtures();
        $assignedClasses = new EloquentCollection([$kelas]);
        $user = $this->makeUserWithRole('Ustadzah Sari', 'sari@primainsani.sch.id', 'NIPY-001', 'wali kelas', $wali);

        Auth::shouldUse('web');
        Auth::setUser($user);

        $matrix = [
            'kelas' => $kelas,
            'bulan' => '2026-11',
            'bulan_label' => 'November 2026',
            'days' => [[
                'day' => 16,
                'label' => '16',
                'day_name' => 'Sen',
                'date' => '2026-11-16',
                'is_weekend' => false,
                'is_today' => false,
            ]],
            'rows' => [[
                'anggota' => $anggota,
                'cells' => [16 => $presensi],
                'counts' => [
                    'hadir' => 1,
                    'terlambat' => 0,
                    'izin' => 0,
                    'sakit' => 0,
                    'alpa' => 0,
                    'hadir_efektif' => 1,
                    'total_catatan' => 1,
                    'total_telat_menit' => 0,
                    'persentase_hadir' => 100.0,
                    'persentase_tepat_waktu' => 100.0,
                ],
            ]],
            'daily_totals' => [16 => 1],
            'summary' => [
                'siswa' => 1,
                'hadir' => 1,
                'terlambat' => 0,
                'izin' => 0,
                'sakit' => 0,
                'alpa' => 0,
                'hadir_efektif' => 1,
                'total_catatan' => 1,
                'total_telat_menit' => 0,
                'persentase_hadir' => 100.0,
                'persentase_tepat_waktu' => 100.0,
            ],
        ];

        $sharedCounts = [
            'hadir' => 1,
            'terlambat' => 0,
            'izin' => 0,
            'sakit' => 0,
            'alpa' => 0,
            'hadir_efektif' => 1,
            'total_catatan' => 1,
            'total_telat_menit' => 0,
            'persentase_hadir' => 100.0,
            'persentase_tepat_waktu' => 100.0,
            'persentase_terlambat' => 0.0,
            'persentase_alpa' => 0.0,
            'persentase_kehadiran_efektif' => 100.0,
            'predikat' => ['label' => 'Sangat Baik', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
        ];

        $waliService = $this->createMock(WaliKelasPresensiService::class);
        $waliService->expects($this->once())
            ->method('getIndexPageData')
            ->willReturn([
                'isEligible' => true,
                'presensiRoutePrefix' => 'wali-kelas.presensi-siswa',
                'isWaliKelasScope' => true,
                'hasFilter' => true,
                'hasExplicitFilter' => true,
                'summary' => ['total' => 1, 'hadir' => 1, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0],
                'matrix' => $matrix,
                'kelasOptions' => $assignedClasses,
                'tahunAjaranOptions' => collect([$ta]),
                'statusOptions' => PresensiSiswa::STATUS_OPTIONS,
                'filters' => ['tanggal' => '', 'status' => '', 'kelas_id' => 10, 'tahun_ajaran_id' => 1, 'bulan' => '2026-11'],
            ]);

        $waliService->expects($this->once())
            ->method('getBulkPageData')
            ->willReturn([
                'isEligible' => true,
                'presensiRoutePrefix' => 'wali-kelas.presensi-siswa',
                'isWaliKelasScope' => true,
                'kelasOptions' => $assignedClasses,
                'selectedKelas' => $kelas,
                'selectedKelasId' => 10,
                'tanggal' => '2026-11-16',
                'tanggalLabel' => 'Senin, 16 November 2026',
                'rows' => [['anggota' => $anggota, 'existing' => $presensi]],
                'existingCount' => 1,
                'statusOptions' => PresensiSiswa::STATUS_OPTIONS,
            ]);

        $waliService->expects($this->once())
            ->method('getEditPageData')
            ->willReturn([
                'presensiRoutePrefix' => 'wali-kelas.presensi-siswa',
                'isWaliKelasScope' => true,
                'presensiSiswa' => $presensi,
                'anggotaKelasOptions' => collect([$anggota]),
                'statusOptions' => PresensiSiswa::STATUS_OPTIONS,
            ]);

        $waliService->expects($this->once())
            ->method('getRekapPageData')
            ->willReturn([
                'isEligible' => false,
                'ineligibleReason' => 'not_assigned_active_year',
                'nipy' => 'NIPY-001',
                'karyawanProfile' => $wali,
                'activeTahunAjaran' => $ta,
                'assignedClasses' => new EloquentCollection,
                'selectedKelas' => null,
                'peranDiKelas' => null,
            ]);

        $waliService->expects($this->once())
            ->method('getTahunanPageData')
            ->willReturn([
                'isEligible' => true,
                'presensiRoutePrefix' => 'wali-kelas.presensi-siswa',
                'isWaliKelasScope' => true,
                'hasFilter' => true,
                'hasExplicitFilter' => true,
                'mode' => 'class_detail',
                'selectedKelas' => $kelas,
                'namaTahunAjaranOptions' => collect(['2026/2027']),
                'activeTahunAjaran' => $ta,
                'semesterOptions' => TahunAjaran::SEMESTER_OPTIONS,
                'tahunAjaranOptions' => collect([$ta]),
                'jenjangOptions' => collect(['SD']),
                'kelasOptions' => $assignedClasses,
                'filters' => [
                    'nama_tahun_ajaran' => '2026/2027',
                    'semester' => '1',
                    'jenjang' => 'SD',
                    'kelas_id' => 10,
                    'include_weekend' => false,
                ],
                'period' => ['label' => 'TA 2026/2027 - Semester 1', 'weekdays_count' => 100],
                'summary' => array_merge(['siswa' => 1, 'jumlah_kelas' => 1], $sharedCounts),
                'semesterRows' => [],
                'jenjangRows' => [],
                'monthlyRows' => [],
                'classRows' => [],
                'studentRows' => [['anggota' => $anggota, 'counts' => $sharedCounts]],
                'highlights' => ['bulan_terbaik' => null, 'bulan_telat_tertinggi' => null],
            ]);

        $controller = new PresensiSiswaController($waliService);

        $indexRequest = Request::create('/wali-kelas/presensi-siswa', 'GET');
        $indexRequest->setUserResolver(static fn (): User => $user);

        $bulkRequest = Request::create('/wali-kelas/presensi-siswa/bulk?kelas_id=10&tanggal=2026-11-16', 'GET');
        $bulkRequest->setUserResolver(static fn (): User => $user);

        $editRequest = Request::create('/wali-kelas/presensi-siswa/701/edit', 'GET');
        $editRequest->setUserResolver(static fn (): User => $user);

        $rekapRequest = Request::create('/wali-kelas/presensi-siswa/rekap', 'GET');
        $rekapRequest->setUserResolver(static fn (): User => $user);

        $tahunanRequest = Request::create('/wali-kelas/presensi-siswa/tahunan', 'GET');
        $tahunanRequest->setUserResolver(static fn (): User => $user);

        $indexView = $controller->index($indexRequest);
        $bulkView = $controller->bulk($bulkRequest);
        $editView = $controller->edit($editRequest, $presensi);
        $rekapIneligibleView = $controller->rekap($rekapRequest);
        $tahunanView = $controller->tahunan($tahunanRequest);

        $this->assertSame('admin.presensi.presensiSiswa.index', $indexView->name());
        $this->assertSame('admin.presensi.presensiSiswa.bulk', $bulkView->name());
        $this->assertSame('admin.presensi.presensiSiswa.edit', $editView->name());
        $this->assertSame('waliKelas.presensi.ineligible', $rekapIneligibleView->name());
        $this->assertSame('admin.presensi.presensiSiswa.tahunan', $tahunanView->name());

        Model::preventLazyLoading(true);
        try {
            $indexHtml = $indexView->render();
            $bulkHtml = $bulkView->with('errors', new ViewErrorBag)->render();
            $editHtml = $editView->with('errors', new ViewErrorBag)->render();
            $rekapHtml = view('admin.presensi.presensiSiswa.rekap', [
                'presensiRoutePrefix' => 'wali-kelas.presensi-siswa',
                'isWaliKelasScope' => true,
                'hasFilter' => true,
                'hasExplicitFilter' => true,
                'mode' => 'class_detail',
                'selectedKelas' => $kelas,
                'period' => [
                    'bulan' => '2026-11',
                    'label' => 'November 2026',
                    'start_date' => '2026-11-01',
                    'end_date' => '2026-11-30',
                    'weekdays_count' => 21,
                ],
                'kelasOptions' => $assignedClasses,
                'tahunAjaranOptions' => collect([$ta]),
                'filters' => [
                    'tahun_ajaran_id' => 1,
                    'kelas_id' => 10,
                    'bulan' => '2026-11',
                    'include_weekend' => false,
                ],
                'studentRows' => [['anggota' => $anggota, 'counts' => $sharedCounts]],
                'classRows' => [],
                'summary' => array_merge(['siswa' => 1, 'jumlah_kelas' => 1], $sharedCounts),
            ])->render();
            $ineligibleHtml = $rekapIneligibleView->render();
            $tahunanHtml = $tahunanView->render();
            $waliSidebarHtml = view('layouts.sidebar')->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        // Memastikan seluruh action/link menunjuk route wali-kelas.presensi-siswa.*, bukan admin presensi-siswa.*
        $this->assertStringContainsString('Kelas Binaan Wali Kelas', $indexHtml);
        $this->assertStringContainsString(route('wali-kelas.presensi-siswa.bulk', ['kelas_id' => 10]), $indexHtml);
        $this->assertStringContainsString(route('wali-kelas.presensi-siswa.bulk.destroy'), $indexHtml);
        $this->assertStringContainsString(route('wali-kelas.presensi-siswa.edit', $presensi), $indexHtml);
        $this->assertStringContainsString(e(route('wali-kelas.presensi-siswa.rekap', ['kelas_id' => 10, 'bulan' => '2026-11'])), $indexHtml);
        $this->assertStringNotContainsString(route('presensi-siswa.bulk'), $indexHtml);

        $this->assertStringContainsString(route('wali-kelas.presensi-siswa.bulk.store'), $bulkHtml);
        $this->assertStringContainsString(route('wali-kelas.presensi-siswa.bulk.destroy'), $bulkHtml);
        $this->assertStringNotContainsString(route('presensi-siswa.bulk.store'), $bulkHtml);

        $this->assertStringContainsString(route('wali-kelas.presensi-siswa.update', $presensi), $editHtml);
        $this->assertStringNotContainsString(route('presensi-siswa.update', $presensi), $editHtml);

        $this->assertStringContainsString('Belum Ada Penugasan Wali Kelas / Pendamping Aktif', $ineligibleHtml);

        $this->assertStringContainsString('Rekap Kehadiran', $rekapHtml);
        $this->assertStringContainsString(route('wali-kelas.presensi-siswa.bulk', ['kelas_id' => 10]), $rekapHtml);
        $this->assertStringNotContainsString(route('presensi-siswa.rekap'), $rekapHtml);

        $this->assertStringContainsString('Statistik Presensi Tahunan', $tahunanHtml);
        $this->assertStringContainsString(e(route('wali-kelas.presensi-siswa.rekap', ['tahun_ajaran_id' => 1, 'kelas_id' => 10])), $tahunanHtml);
        $this->assertStringNotContainsString(route('presensi-siswa.tahunan'), $tahunanHtml);

        // Sidebar role wali kelas menampilkan dropdown presensi wali kelas
        $this->assertStringContainsString('submenu-wali-presensi', $waliSidebarHtml);
        $this->assertStringContainsString(route('wali-kelas.presensi-siswa.index'), $waliSidebarHtml);
        $this->assertStringContainsString(route('wali-kelas.presensi-siswa.bulk'), $waliSidebarHtml);
        $this->assertStringContainsString(route('wali-kelas.presensi-siswa.rekap'), $waliSidebarHtml);
        $this->assertStringContainsString(route('wali-kelas.presensi-siswa.tahunan'), $waliSidebarHtml);
    }
}
