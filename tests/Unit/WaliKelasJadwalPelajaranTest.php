<?php

namespace Tests\Unit;

use App\Http\Controllers\WaliKelas\JadwalPelajaranController;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Pelajaran\JadwalPelajaran;
use App\Models\Admin\Pelajaran\JamPelajaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\User;
use App\Services\Admin\Pelajaran\JadwalPelajaranService;
use App\Services\WaliKelas\WaliKelasJadwalPelajaranService;
use App\Services\WaliKelas\WaliKelasScopeService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class WaliKelasJadwalPelajaranTest extends TestCase
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
     * @return array<string, mixed>
     */
    private function makeFixtures(): array
    {
        $ta = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);
        $ta->id = 1;

        $wali = Karyawan::make(['nipy' => 'NIPY-001', 'nama_lengkap' => 'Sari Wulandari', 'gelar' => 'S.Pd']);
        $guruLain = Karyawan::make(['nipy' => 'NIPY-009', 'nama_lengkap' => 'Ahmad Fauzi', 'gelar' => 'M.Pd']);

        $kelasPrimary = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'karyawan_nipy' => 'NIPY-001',
            'pendamping_nipy' => 'NIPY-002',
            'tingkatan_kelas' => '5',
            'nama_kelas' => '5 Al-Fatihah',
            'jenjang_kelas' => 'SD',
        ]);
        $kelasPrimary->id = 10;
        $kelasPrimary->setRelation('tahunAjaran', $ta);
        $kelasPrimary->setRelation('waliKelas', $wali);

        $kelasSecondary = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'karyawan_nipy' => 'NIPY-009',
            'pendamping_nipy' => 'NIPY-001',
            'tingkatan_kelas' => '5',
            'nama_kelas' => '5 An-Naba',
            'jenjang_kelas' => 'SD',
        ]);
        $kelasSecondary->id = 11;
        $kelasSecondary->setRelation('tahunAjaran', $ta);

        $jam1 = JamPelajaran::make(['jam_ke' => 1, 'unit' => 'SD', 'jam_mulai' => '07:30:00', 'jam_selesai' => '08:05:00']);
        $jam1->id = 201;

        $mapelPai = MataPelajaran::make(['singkatan' => 'PAI', 'nama_mata_pelajaran' => 'Pendidikan Agama Islam', 'unit' => 'SD']);
        $mapelPai->id = 301;

        $mapelMtk = MataPelajaran::make(['singkatan' => 'MTK', 'nama_mata_pelajaran' => 'Matematika', 'unit' => 'SD']);
        $mapelMtk->id = 302;

        $jadwalMapel = JadwalPelajaran::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'akademik_master_kelas_id' => 10,
            'akademik_pelajaran_jam_pelajaran_id' => 201,
            'akademik_pelajaran_mata_pelajaran_id' => 301,
            'karyawan_nipy' => 'NIPY-001',
            'hari' => JadwalPelajaran::HARI_SENIN,
            'ruang' => 'R.501',
            'status' => true,
        ]);
        $jadwalMapel->id = 501;
        $jadwalMapel->setRelation('tahunAjaran', $ta);
        $jadwalMapel->setRelation('kelas', $kelasPrimary);
        $jadwalMapel->setRelation('jamPelajaran', $jam1);
        $jadwalMapel->setRelation('mataPelajaran', $mapelPai);
        $jadwalMapel->setRelation('guru', $wali);

        $jadwalOtherTeacher = JadwalPelajaran::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'akademik_master_kelas_id' => 10,
            'akademik_pelajaran_jam_pelajaran_id' => 201,
            'akademik_pelajaran_mata_pelajaran_id' => 302,
            'karyawan_nipy' => 'NIPY-009',
            'hari' => JadwalPelajaran::HARI_SELASA,
            'ruang' => 'Lab Matematika',
            'status' => true,
        ]);
        $jadwalOtherTeacher->id = 502;
        $jadwalOtherTeacher->setRelation('tahunAjaran', $ta);
        $jadwalOtherTeacher->setRelation('kelas', $kelasPrimary);
        $jadwalOtherTeacher->setRelation('jamPelajaran', $jam1);
        $jadwalOtherTeacher->setRelation('mataPelajaran', $mapelMtk);
        $jadwalOtherTeacher->setRelation('guru', $guruLain);

        return compact('ta', 'wali', 'guruLain', 'kelasPrimary', 'kelasSecondary', 'jam1', 'jadwalMapel', 'jadwalOtherTeacher');
    }

    public function test_only_read_only_wali_kelas_jadwal_route_is_registered(): void
    {
        $jadwalRoutes = collect(Route::getRoutes()->getRoutes())
            ->filter(static fn ($route): bool => str_starts_with($route->uri(), 'wali-kelas/jadwal-pelajaran'))
            ->values();

        $this->assertCount(1, $jadwalRoutes);
        $route = $jadwalRoutes->first();
        $this->assertSame('wali-kelas.jadwal-pelajaran.index', $route->getName());
        $this->assertSame(['GET', 'HEAD'], $route->methods());
        $this->assertSame(JadwalPelajaranController::class.'@index', $route->getActionName());
    }

    public function test_service_authorizes_homeroom_classes_and_blocks_foreign_class_or_non_active_year(): void
    {
        $f = $this->makeFixtures();
        $user = $this->makeUserWithRole('Ustadzah Sari', 'sari@primainsani.sch.id', 'NIPY-001', 'wali kelas', $f['wali']);
        $assignedClasses = new EloquentCollection([$f['kelasPrimary'], $f['kelasSecondary']]);

        // User belum memiliki penugasan -> guard ineligible
        $ineligibleScope = $this->createMock(WaliKelasScopeService::class);
        $ineligibleScope->method('resolveScopeContext')->willReturn([
            'isEligible' => false,
            'ineligibleReason' => 'not_assigned_active_year',
            'nipy' => 'NIPY-001',
            'karyawanProfile' => $f['wali'],
            'activeTahunAjaran' => $f['ta'],
            'assignedClasses' => new EloquentCollection,
            'selectedKelas' => null,
            'peranDiKelas' => null,
        ]);

        $unusedAdminService = $this->createMock(JadwalPelajaranService::class);
        $unusedAdminService->expects($this->never())->method('getIndexData');

        $ineligibleService = new WaliKelasJadwalPelajaranService($ineligibleScope, $unusedAdminService);
        $ineligibleResult = $ineligibleService->getIndexData($user, []);
        $this->assertFalse($ineligibleResult['isEligible']);

        // User eligible -> mendelegasikan ke JadwalPelajaranService::getIndexData dengan kelas binaan aktif
        $validScope = $this->createMock(WaliKelasScopeService::class);
        $validScope->method('resolveScopeContext')
            ->willReturnCallback(static fn (User $u, ?int $reqKelasId): array => [
                'isEligible' => true,
                'ineligibleReason' => null,
                'nipy' => 'NIPY-001',
                'karyawanProfile' => $f['wali'],
                'activeTahunAjaran' => $f['ta'],
                'assignedClasses' => $assignedClasses,
                'selectedKelas' => $reqKelasId === 11 ? $f['kelasSecondary'] : $f['kelasPrimary'],
                'peranDiKelas' => $reqKelasId === 11 ? 'Guru Pendamping' : 'Wali Kelas',
            ]);

        $adminService = $this->createMock(JadwalPelajaranService::class);
        $adminService->expects($this->once())
            ->method('getIndexData')
            ->with(
                [
                    'tahun_ajaran_id' => '1',
                    'unit' => '',
                    'kelas_id' => '10',
                    'hari' => '1',
                    'status' => '1',
                    'search' => 'PAI',
                ],
                $assignedClasses,
                $this->callback(static fn ($tas): bool => $tas->count() === 1 && $tas->first()?->id === 1)
            )
            ->willReturn([
                'jadwalPelajaran' => new EloquentCollection([$f['jadwalMapel']]),
                'summary' => ['total' => 1, 'aktif' => 1, 'nonaktif' => 0, 'per_hari' => [1 => 1, 2 => 0, 3 => 0, 4 => 0, 5 => 0]],
                'filters' => ['tahun_ajaran_id' => '1', 'unit' => '', 'kelas_id' => '10', 'hari' => '1', 'status' => '1', 'search' => 'PAI'],
                'tahunAjaranOptions' => new EloquentCollection([$f['ta']]),
                'kelasOptions' => $assignedClasses,
                'unitOptions' => JamPelajaran::UNIT_OPTIONS,
                'hariOptions' => JadwalPelajaran::HARI_OPTIONS,
                'selectedKelas' => $f['kelasPrimary'],
                'weeklyMatrix' => null,
            ]);

        $service = new WaliKelasJadwalPelajaranService($validScope, $adminService);
        $result = $service->getIndexData($user, ['hari' => '1', 'status' => '1', 'search' => 'PAI']);

        $this->assertTrue($result['isEligible']);
        $this->assertTrue($result['isWaliKelasScope']);
        $this->assertSame('wali-kelas.jadwal-pelajaran', $result['routePrefix']);

        // Mengakses kelas lain yang bukan kelas binaannya harus ditolak 403
        try {
            $service->getIndexData($user, ['kelas_id' => '99']);
            $this->fail('Seharusnya menolak kelas_id yang bukan kelas binaan.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        // Mengakses tahun ajaran di luar TA aktif harus ditolak 403
        foreach (['999', JadwalPelajaranService::FILTER_ALL] as $badTa) {
            try {
                $service->getIndexData($user, ['tahun_ajaran_id' => $badTa]);
                $this->fail("Seharusnya menolak tahun_ajaran_id={$badTa}.");
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }
        }
    }

    public function test_controller_reuses_jadwal_view_in_readonly_mode_and_updates_sidebar(): void
    {
        $f = $this->makeFixtures();
        $user = $this->makeUserWithRole('Ustadzah Sari', 'sari@primainsani.sch.id', 'NIPY-001', 'wali kelas', $f['wali']);
        Auth::shouldUse('web');
        Auth::setUser($user);

        $weeklyMatrix = [
            'jamSlots' => new EloquentCollection([$f['jam1']]),
            'grid' => [
                201 => [
                    JadwalPelajaran::HARI_SENIN => $f['jadwalMapel'],
                    JadwalPelajaran::HARI_SELASA => $f['jadwalOtherTeacher'],
                ],
            ],
        ];

        $eligiblePayload = [
            'isEligible' => true,
            'ineligibleReason' => null,
            'nipy' => 'NIPY-001',
            'karyawanProfile' => $f['wali'],
            'activeTahunAjaran' => $f['ta'],
            'assignedClasses' => new EloquentCollection([$f['kelasPrimary']]),
            'peranDiKelas' => 'Wali Kelas',
            'isWaliKelasScope' => true,
            'routePrefix' => 'wali-kelas.jadwal-pelajaran',
            'jadwalPelajaran' => new EloquentCollection([$f['jadwalMapel'], $f['jadwalOtherTeacher']]),
            'summary' => ['total' => 2, 'aktif' => 2, 'nonaktif' => 0, 'per_hari' => [1 => 1, 2 => 1, 3 => 0, 4 => 0, 5 => 0]],
            'filters' => ['tahun_ajaran_id' => '1', 'unit' => '', 'kelas_id' => '10', 'hari' => '', 'status' => 'all', 'search' => ''],
            'tahunAjaranOptions' => new EloquentCollection([$f['ta']]),
            'kelasOptions' => new EloquentCollection([$f['kelasPrimary']]),
            'unitOptions' => JamPelajaran::UNIT_OPTIONS,
            'hariOptions' => JadwalPelajaran::HARI_OPTIONS,
            'selectedKelas' => $f['kelasPrimary'],
            'weeklyMatrix' => $weeklyMatrix,
        ];

        $ineligiblePayload = [
            'isEligible' => false,
            'ineligibleReason' => 'not_assigned_active_year',
            'nipy' => 'NIPY-001',
            'karyawanProfile' => $f['wali'],
            'activeTahunAjaran' => $f['ta'],
            'assignedClasses' => new EloquentCollection,
            'selectedKelas' => null,
            'peranDiKelas' => null,
        ];

        $waliService = $this->createMock(WaliKelasJadwalPelajaranService::class);
        $waliService->expects($this->exactly(2))
            ->method('getIndexData')
            ->willReturnOnConsecutiveCalls($eligiblePayload, $ineligiblePayload);

        $controller = new JadwalPelajaranController($waliService);

        $request = Request::create('/wali-kelas/jadwal-pelajaran', 'GET');
        $request->setUserResolver(static fn (): User => $user);

        $eligibleView = $controller->index($request);
        $ineligibleView = $controller->index($request);

        $this->assertSame('waliKelas.jadwalPelajaran.index', $eligibleView->name());
        $this->assertSame('waliKelas.jadwalPelajaran.ineligible', $ineligibleView->name());

        Model::preventLazyLoading(true);
        try {
            $html = $eligibleView->render();
            $ineligibleHtml = $ineligibleView->render();
            $waliSidebarHtml = view('layouts.sidebar')->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        // Tampilan modern minimalist: satu kartu terpadu (Matriks & Per Hari) tanpa tabel duplikat di bawahnya
        $this->assertStringContainsString('5 Al-Fatihah', $html);
        $this->assertStringContainsString('Hanya Lihat', $html);
        $this->assertStringContainsString('id="wkjpMatrixPanel"', $html);
        $this->assertStringContainsString('id="wkjpAgendaPanel"', $html);
        $this->assertStringContainsString('id="wkjpSearch"', $html);
        $this->assertStringContainsString('Pendidikan Agama Islam', $html);
        $this->assertStringContainsString('Matematika', $html);
        $this->assertStringContainsString('Ahmad Fauzi, M.Pd', $html);
        $this->assertStringContainsString('R.501', $html);
        $this->assertStringContainsString(route('wali-kelas.dashboard', ['kelas_id' => 10]), $html);
        $this->assertStringNotContainsString('id="myTable3"', $html);
        $this->assertStringNotContainsString(route('jadwal-pelajaran.create'), $html);
        $this->assertStringNotContainsString(route('jadwal-pelajaran.edit', 501), $html);
        $this->assertStringNotContainsString(route('jadwal-pelajaran.destroy', 501), $html);
        $this->assertStringNotContainsString(route('jadwal-pelajaran.toggle-status', 501), $html);
        $this->assertStringNotContainsString(route('jam-pelajaran.create'), $html);

        $this->assertStringContainsString('Belum Ada Penugasan Wali Kelas / Pendamping Aktif', $ineligibleHtml);

        // Sidebar role wali kelas menampilkan Jadwal Pelajaran; super admin tidak menampilkannya
        $this->assertStringContainsString('Jadwal Pelajaran', $waliSidebarHtml);
        $this->assertStringContainsString(route('wali-kelas.jadwal-pelajaran.index'), $waliSidebarHtml);

        $superAdmin = $this->makeUserWithRole('Super Admin', 'admin@primainsani.sch.id', null, 'super admin');
        Auth::setUser($superAdmin);
        $adminSidebarHtml = view('layouts.sidebar')->render();
        $this->assertStringNotContainsString(route('wali-kelas.jadwal-pelajaran.index'), $adminSidebarHtml);
    }
}
