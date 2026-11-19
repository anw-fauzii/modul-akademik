<?php

namespace Tests\Unit;

use App\Http\Controllers\WaliKelas\YaumiyahT2qController;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\AnggotaT2q;
use App\Models\Admin\DataMaster\AngkaArab;
use App\Models\Admin\DataMaster\DaftarJilid;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\Surah;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\T2q\YaumiyahTahfizh;
use App\Models\Admin\T2q\YaumiyahTahsin;
use App\Models\User;
use App\Services\Admin\T2q\YaumiyahTahfizhService;
use App\Services\Admin\T2q\YaumiyahTahsinService;
use App\Services\WaliKelas\WaliKelasScopeService;
use App\Services\WaliKelas\WaliKelasT2qService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ViewErrorBag;
use ReflectionMethod;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class WaliKelasT2qTest extends TestCase
{
    public function test_routes_are_strictly_read_only_and_protected_by_wali_kelas_middleware(): void
    {
        $expectedRoutes = [
            'wali-kelas.t2q.yaumiyah-tahsin.matriks' => 'wali-kelas/t2q/yaumiyah-tahsin/matriks',
            'wali-kelas.t2q.yaumiyah-tahfizh.matriks' => 'wali-kelas/t2q/yaumiyah-tahfizh/matriks',
            'wali-kelas.t2q.yaumiyah-tahsin.statistik' => 'wali-kelas/t2q/yaumiyah-tahsin/statistik',
            'wali-kelas.t2q.yaumiyah-tahfizh.statistik' => 'wali-kelas/t2q/yaumiyah-tahfizh/statistik',
        ];

        foreach ($expectedRoutes as $name => $uri) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "Route {$name} harus terdaftar.");
            $this->assertSame($uri, $route->uri());
            $this->assertSame(['GET', 'HEAD'], $route->methods());
            $this->assertContains('auth', $route->gatherMiddleware());
        }

        $writeMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];
        foreach (Route::getRoutes() as $route) {
            if (str_starts_with($route->uri(), 'wali-kelas/t2q')) {
                $this->assertSame([], array_values(array_intersect($writeMethods, $route->methods())),
                    "Route {$route->uri()} tidak boleh menerima method tulis.");
            }
        }

        $controller = new YaumiyahT2qController(new WaliKelasT2qService);
        $middlewares = collect($controller->getMiddleware())->pluck('middleware')->all();
        $this->assertContains('role:super admin|wali kelas', $middlewares);
    }

    public function test_anggota_t2q_for_kelas_ids_scope_handles_empty_and_normalized_ids(): void
    {
        $emptyQuery = AnggotaT2q::query()->forKelasIds([]);
        $this->assertStringContainsString('1 = 0', $emptyQuery->toSql());
        $this->assertSame([], $emptyQuery->getBindings());

        $scopedQuery = AnggotaT2q::query()->forKelasIds([12, '12', 0, -5, 18]);
        $this->assertStringContainsString('akademik_master_kelas_id', $scopedQuery->toSql());
        $this->assertSame([12, 18], $scopedQuery->getBindings());

        $singleQuery = AnggotaT2q::query()->forKelas(9);
        $this->assertStringContainsString('akademik_master_kelas_id', $singleQuery->toSql());
        $this->assertSame([9], $singleQuery->getBindings());
    }

    public function test_matriks_service_returns_ineligible_and_rejects_unauthorized_kelas_id(): void
    {
        $user = $this->makeFakeUser('2024001');
        $ta = $this->makeTahunAjaran(10, '2026/2027', 'Ganjil', true);
        $kelasA = $this->makeKelas(101, 10, '4 Ibnu Sina', '2024001', null, $ta);

        $scopeStub = $this->createMock(WaliKelasScopeService::class);
        $scopeStub->method('resolveScopeContext')
            ->willReturn([
                'isEligible' => false,
                'ineligibleReason' => 'not_assigned_active_year',
                'nipy' => '2024001',
                'karyawanProfile' => null,
                'activeTahunAjaran' => $ta,
                'assignedClasses' => new EloquentCollection,
                'selectedKelas' => null,
                'peranDiKelas' => null,
            ]);

        $service = new WaliKelasT2qService($scopeStub, new YaumiyahTahsinService, new YaumiyahTahfizhService);
        $ineligible = $service->getMatriksPageData($user, 'tahsin');
        $this->assertFalse($ineligible['isEligible']);
        $this->assertSame('not_assigned_active_year', $ineligible['ineligibleReason']);

        $scopeAuthorized = $this->createMock(WaliKelasScopeService::class);
        $scopeAuthorized->method('resolveScopeContext')
            ->willReturn([
                'isEligible' => true,
                'ineligibleReason' => null,
                'nipy' => '2024001',
                'karyawanProfile' => null,
                'activeTahunAjaran' => $ta,
                'assignedClasses' => new EloquentCollection([$kelasA]),
                'selectedKelas' => $kelasA,
                'peranDiKelas' => 'Wali Kelas',
            ]);

        $serviceAuthorized = new WaliKelasT2qService($scopeAuthorized, new YaumiyahTahsinService, new YaumiyahTahfizhService);

        $this->expectException(HttpException::class);
        try {
            $serviceAuthorized->getMatriksPageData($user, 'tahsin', ['kelas_id' => 999]);
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
            throw $e;
        }
    }

    public function test_matriks_service_passes_exact_scoped_kelas_id_and_readonly_flags(): void
    {
        $user = $this->makeFakeUser('2024001');
        $ta = $this->makeTahunAjaran(10, '2026/2027', 'Ganjil', true);
        $kelasA = $this->makeKelas(101, 10, '4 Ibnu Sina', '2024001', null, $ta);
        $kelasB = $this->makeKelas(102, 10, '4 Al-Khawarizmi', null, '2024001', $ta);

        $scopeMock = $this->createMock(WaliKelasScopeService::class);
        $scopeMock->expects($this->once())
            ->method('resolveScopeContext')
            ->with($user, 102)
            ->willReturn([
                'isEligible' => true,
                'ineligibleReason' => null,
                'nipy' => '2024001',
                'karyawanProfile' => null,
                'activeTahunAjaran' => $ta,
                'assignedClasses' => new EloquentCollection([$kelasA, $kelasB]),
                'selectedKelas' => $kelasB,
                'peranDiKelas' => 'Guru Pendamping',
            ]);

        $tahfizhMock = $this->createMock(YaumiyahTahfizhService::class);
        $tahfizhMock->expects($this->once())
            ->method('getMatrixData')
            ->with(['kelas_id' => 102, 'tanggal' => '2026-11-18'], [102])
            ->willReturn(['mode' => 'tahfizh', 'activeTahunAjaran' => $ta]);

        $service = new WaliKelasT2qService($scopeMock, new YaumiyahTahsinService, $tahfizhMock);
        $result = $service->getMatriksPageData($user, 'tahfizh', ['kelas_id' => 102, 'tanggal' => '2026-11-18']);

        $this->assertTrue($result['isEligible']);
        $this->assertTrue($result['isReadOnly']);
        $this->assertTrue($result['isWaliKelasScope']);
        $this->assertSame('wali-kelas.t2q', $result['routePrefix']);
        $this->assertSame(102, $result['selectedKelasId']);
        $this->assertSame('Guru Pendamping', $result['peranDiKelas']);
    }

    public function test_statistik_service_enforces_cross_year_scope_and_blocks_unauthorized_filters(): void
    {
        $user = $this->makeFakeUser('2024001');
        $taActive = $this->makeTahunAjaran(20, '2026/2027', 'Ganjil', true);
        $taArchive = $this->makeTahunAjaran(19, '2025/2026', 'Genap', false);
        $kelas1 = $this->makeKelas(201, 20, '5A', '2024001', null, $taActive);
        $kelas2 = $this->makeKelas(202, 20, '5B', null, '2024001', $taActive);
        $kelasOld = $this->makeKelas(191, 19, '4A', '2024001', null, $taArchive);

        $allAssigned = new EloquentCollection([$kelas1, $kelas2, $kelasOld]);

        $scopeMock = $this->createMock(WaliKelasScopeService::class);
        $scopeMock->method('getAllAssignedClassesForUser')->with($user)->willReturn($allAssigned);
        $scopeMock->method('resolveRoleInKelas')->willReturn('Wali Kelas');

        // 1. Default TA aktif dengan 2 kelas -> semua kelas di TA aktif masuk scope
        $tahsinMock = $this->createMock(YaumiyahTahsinService::class);
        $tahsinMock->expects($this->once())
            ->method('getStatistikData')
            ->with(
                ['tahun_ajaran_id' => 20],
                [201, 202],
                $this->callback(static fn (EloquentCollection $tas): bool => $tas->pluck('id')->all() === [20, 19])
            )
            ->willReturn(['mode' => 'tahsin', 'selectedTahunAjaran' => $taActive, 'selectedTahunAjaranId' => 20]);

        $service = new WaliKelasT2qService($scopeMock, $tahsinMock, new YaumiyahTahfizhService);
        $payload = $service->getStatistikPageData($user, 'tahsin');

        $this->assertTrue($payload['isEligible']);
        $this->assertTrue($payload['isReadOnly']);
        $this->assertTrue($payload['isWaliKelasScope']);
        $this->assertSame('wali-kelas.t2q', $payload['routePrefix']);
        $this->assertSame(0, $payload['selectedKelasId']);
        $this->assertCount(2, $payload['assignedClasses']);

        // 2. Pindah ke TA arsip sambil masih membawa kelas_id milik sendiri dari TA aktif -> reset ke kelas tunggal TA arsip tanpa 403
        $tahsinArchiveMock = $this->createMock(YaumiyahTahsinService::class);
        $tahsinArchiveMock->expects($this->once())
            ->method('getStatistikData')
            ->with(['tahun_ajaran_id' => 19, 'kelas_id' => 201], [191], $this->isInstanceOf(EloquentCollection::class))
            ->willReturn(['mode' => 'tahsin', 'selectedTahunAjaran' => $taArchive, 'selectedTahunAjaranId' => 19]);

        $archiveService = new WaliKelasT2qService($scopeMock, $tahsinArchiveMock, new YaumiyahTahfizhService);
        $archivePayload = $archiveService->getStatistikPageData($user, 'tahsin', ['tahun_ajaran_id' => 19, 'kelas_id' => 201]);
        $this->assertSame(191, $archivePayload['selectedKelasId']);

        // 3. Tahun ajaran tidak dimiliki -> 403
        try {
            $archiveService->getStatistikPageData($user, 'tahsin', ['tahun_ajaran_id' => 999]);
            $this->fail('Seharusnya melempar 403 untuk tahun_ajaran_id di luar penugasan.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        // 4. Kelas milik wali kelas lain -> 403
        try {
            $archiveService->getStatistikPageData($user, 'tahsin', ['tahun_ajaran_id' => 20, 'kelas_id' => 888]);
            $this->fail('Seharusnya melempar 403 untuk kelas_id di luar penugasan.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_shared_matriks_and_statistik_views_render_strictly_readonly_without_lazy_loading(): void
    {
        $ta = $this->makeTahunAjaran(20, '2026/2027', 'Ganjil', true);
        $kelas1 = $this->makeKelas(201, 20, '5 Ibnu Sina', '2024001', null, $ta);
        $kelas2 = $this->makeKelas(202, 20, '5 Al-Farabi', null, '2024001', $ta);
        $assignedClasses = new EloquentCollection([$kelas1, $kelas2]);

        $guru = Karyawan::make(['nipy' => 'G001', 'nama_lengkap' => 'Ustadz Ahmad', 'gelar' => 'S.Pd.I']);
        $siswa = Siswa::make(['nis' => '26001', 'nama_lengkap' => 'Aisyah Zahra']);
        $anggotaKelas = AnggotaKelas::make(['siswa_nis' => '26001', 'akademik_master_kelas_id' => 201]);
        $anggotaKelas->setRelation('siswa', $siswa);
        $anggotaKelas->setRelation('kelas', $kelas1);

        $anggota = AnggotaT2q::make(['anggota_kelas_id' => 1, 'karyawan_nipy' => 'G001', 'tingkat' => '4']);
        $anggota->setAttribute('id', 501);
        $anggota->setRelation('anggotaKelas', $anggotaKelas);
        $anggota->setRelation('guruT2q', $guru);

        $jilid = DaftarJilid::make(['jilid_latin' => 'Jilid 4', 'jilid_arab' => '٤', 'jenis' => 'halaman', 'urutan' => 4]);
        $jilid->setAttribute('id', 4);
        $surah = Surah::make(['nama_surah' => 'An-Naba', 'nama_surah_arab' => 'النبأ', 'jumlah_ayat' => 40]);
        $surah->setAttribute('id', 78);
        $angka = AngkaArab::make(['angka_latin' => '12', 'angka_arab' => '١٢']);
        $angka->setAttribute('id', 12);

        $tahsinEntry = YaumiyahTahsin::make(['anggota_t2q_id' => 501, 'tanggal' => '2026-11-16', 'nilai' => 90]);
        $tahsinEntry->setRelation('daftarJilid', $jilid);
        $tahsinEntry->setRelation('angkaArab', $angka);

        $tahfizhEntry = YaumiyahTahfizh::make(['anggota_t2q_id' => 501, 'tanggal' => '2026-11-16', 'nilai' => 88]);
        $tahfizhEntry->setRelation('surah', $surah);
        $tahfizhEntry->setRelation('angkaArab', $angka);

        $tahsinService = new YaumiyahTahsinService;
        $tahfizhService = new YaumiyahTahfizhService;

        $resolveWeek = new ReflectionMethod($tahsinService, 'resolveWeekPeriod');
        $buildMatrix = new ReflectionMethod($tahsinService, 'buildWeeklyMatrix');
        $buildStats = new ReflectionMethod($tahsinService, 'buildAnnualStatistik');
        $buildTahfizhMatrix = new ReflectionMethod($tahfizhService, 'buildWeeklyMatrix');
        $buildTahfizhStats = new ReflectionMethod($tahfizhService, 'buildAnnualStatistik');

        $period = $resolveWeek->invoke($tahsinService, '2026-11-18');
        $students = new EloquentCollection([$anggota]);

        $tahsinMatrix = $buildMatrix->invoke($tahsinService, $students, [501 => ['2026-11-16' => $tahsinEntry]], $period);
        $tahfizhMatrix = $buildTahfizhMatrix->invoke($tahfizhService, $students, [501 => ['2026-11-16' => $tahfizhEntry]], $period);
        $tahsinStats = $buildStats->invoke($tahsinService, $ta, $students, new EloquentCollection([$tahsinEntry]), collect());
        $tahfizhStats = $buildTahfizhStats->invoke($tahfizhService, $ta, $students, new EloquentCollection([$tahfizhEntry]), collect());

        $baseWaliMeta = [
            'errors' => new ViewErrorBag,
            'isReadOnly' => true,
            'isWaliKelasScope' => true,
            'routePrefix' => 'wali-kelas.t2q',
            'assignedClasses' => $assignedClasses,
            'selectedKelas' => $kelas1,
            'selectedKelasId' => 201,
            'peranDiKelas' => 'Wali Kelas',
            'guruOptions' => new EloquentCollection([$guru]),
            'selectedGuruNipy' => '',
        ];

        $waliUser = $this->makeWaliKelasRoleUser();
        $waliUser->setRelation('karyawan', $guru);
        Auth::setUser($waliUser);
        Model::preventLazyLoading(true);
        try {
            $matriksTahsinHtml = view('admin.t2q.yaumiyahTahsin.matriks', [
                ...$baseWaliMeta,
                'mode' => 'tahsin',
                'activeTahunAjaran' => $ta,
                'tanggal' => '2026-11-18',
                'tingkatTabs' => ['4' => 1],
                'selectedTingkat' => '4',
                'targetCapaian' => new EloquentCollection,
                'period' => $period,
                'matrix' => $tahsinMatrix,
            ])->render();

            $this->assertStringContainsString('Aisyah Zahra', $matriksTahsinHtml);
            $this->assertStringContainsString('5 Ibnu Sina', $matriksTahsinHtml);
            $this->assertStringContainsString('Lihat Saja', $matriksTahsinHtml);
            $this->assertStringContainsString('t2q-mx-empty is-readonly', $matriksTahsinHtml);
            $this->assertStringContainsString(e(route('wali-kelas.t2q.yaumiyah-tahfizh.matriks', ['kelas_id' => sqidParam('kelas_id', 201), 'tanggal' => $period['anchor_date'], 'tingkat' => '4'])), $matriksTahsinHtml);
            $this->assertStringNotContainsString('Input Harian', $matriksTahsinHtml);
            $this->assertStringNotContainsString('Ubah di Input Harian', $matriksTahsinHtml);
            $this->assertStringNotContainsString('id="mxModalEditLink"', $matriksTahsinHtml);
            $this->assertStringNotContainsString('fa-plus-circle', $matriksTahsinHtml);

            $matriksTahfizhHtml = view('admin.t2q.yaumiyahTahfizh.matriks', [
                ...$baseWaliMeta,
                'mode' => 'tahfizh',
                'activeTahunAjaran' => $ta,
                'tanggal' => '2026-11-18',
                'tingkatTabs' => ['4' => 1],
                'selectedTingkat' => '4',
                'targetCapaian' => new EloquentCollection,
                'period' => $period,
                'matrix' => $tahfizhMatrix,
            ])->render();

            $this->assertStringContainsString('An-Naba', $matriksTahfizhHtml);
            $this->assertStringNotContainsString('Input Harian', $matriksTahfizhHtml);
            $this->assertStringNotContainsString('Ubah di Input Harian', $matriksTahfizhHtml);

            $statistikTahsinHtml = view('admin.t2q.yaumiyahTahsin.statistik', [
                ...$baseWaliMeta,
                'mode' => 'tahsin',
                'tahunAjaranOptions' => new EloquentCollection([$ta]),
                'selectedTahunAjaran' => $ta,
                'selectedTahunAjaranId' => 20,
                'tingkatOptions' => ['4' => 1],
                'selectedTingkat' => '',
                'stats' => $tahsinStats,
            ])->render();

            $this->assertStringContainsString('Aisyah Zahra', $statistikTahsinHtml);
            $this->assertStringContainsString('Kelas Binaan', $statistikTahsinHtml);
            $this->assertStringContainsString('Lihat Saja', $statistikTahsinHtml);
            $this->assertStringContainsString(e(route('wali-kelas.t2q.yaumiyah-tahsin.matriks', ['kelas_id' => sqidParam('kelas_id', 201)])), $statistikTahsinHtml);
            $this->assertStringNotContainsString('Input Harian', $statistikTahsinHtml);
            $this->assertStringNotContainsString('/t2q/yaumiyah-tahsin"', $statistikTahsinHtml);

            $statistikTahfizhHtml = view('admin.t2q.yaumiyahTahfizh.statistik', [
                ...$baseWaliMeta,
                'mode' => 'tahfizh',
                'tahunAjaranOptions' => new EloquentCollection([$ta]),
                'selectedTahunAjaran' => $ta,
                'selectedTahunAjaranId' => 20,
                'tingkatOptions' => ['4' => 1],
                'selectedTingkat' => '',
                'stats' => $tahfizhStats,
            ])->render();

            $this->assertStringContainsString('An-Naba', $statistikTahfizhHtml);
            $this->assertStringNotContainsString('Input Harian', $statistikTahfizhHtml);

            foreach (['no_active_tahun_ajaran', 'missing_nipy', 'not_assigned_active_year'] as $reason) {
                $ineligibleHtml = view('waliKelas.t2q.ineligible', [
                    'errors' => new ViewErrorBag,
                    'isEligible' => false,
                    'pageTitle' => 'Matriks Catatan Tahsin Wali Kelas',
                    'heroTitle' => 'Matriks Catatan Tahsin Kelas Binaan',
                    'ineligibleReason' => $reason,
                    'activeTahunAjaran' => $reason === 'no_active_tahun_ajaran' ? null : $ta,
                    'nipy' => $reason === 'missing_nipy' ? '' : '2024001',
                    'karyawanProfile' => $guru,
                ])->render();

                $this->assertStringContainsString('YAUMIYAH T2Q WALI KELAS', $ineligibleHtml);
            }
        } finally {
            Model::preventLazyLoading(false);
            Auth::logout();
        }
    }

    public function test_wali_kelas_sidebar_displays_and_activates_yaumiyah_t2q_menu(): void
    {
        $waliUser = $this->makeWaliKelasRoleUser();

        $previousRequest = $this->app['request'];
        Auth::setUser($waliUser);

        try {
            $request = Request::create('/wali-kelas/t2q/yaumiyah-tahsin/matriks', 'GET');
            $route = Route::getRoutes()->match($request);
            $request->setRouteResolver(static fn () => $route);
            $this->app->instance('request', $request);
            Facade::clearResolvedInstance('request');

            $html = view('layouts.sidebar')->render();

            $this->assertStringContainsString('submenu-wali-t2q', $html);
            $this->assertStringContainsString('Yaumiyah T2Q', $html);
            $this->assertStringContainsString('Matriks (Sen–Kam)', $html);
            $this->assertStringContainsString('Statistik Tahun Ajaran', $html);
            $this->assertStringContainsString(route('wali-kelas.t2q.yaumiyah-tahsin.matriks'), $html);
            $this->assertStringContainsString(route('wali-kelas.t2q.yaumiyah-tahsin.statistik'), $html);
            $this->assertStringContainsString('data-sidebar-toggle="submenu-wali-t2q"', $html);
            $this->assertStringContainsString('aria-expanded="true"', $html);
        } finally {
            Auth::logout();
            $this->app->instance('request', $previousRequest);
            Facade::clearResolvedInstance('request');
        }
    }

    private function makeWaliKelasRoleUser(): User
    {
        $user = new class extends User
        {
            public function hasRole($roles, ?string $guard = null): bool
            {
                return $roles === 'wali kelas';
            }
        };
        $user->setAttribute('id', 1);
        $user->setAttribute('name', 'Wali Kelas Demo');
        $user->setAttribute('no_induk', '2024001');

        return $user;
    }

    private function makeFakeUser(string $nipy): User
    {
        $user = new User;
        $user->setAttribute('id', 1);
        $user->setAttribute('name', 'Ustadzah Fatimah');
        $user->setAttribute('no_induk', $nipy);

        return $user;
    }

    private function makeTahunAjaran(int $id, string $nama, string $semester, bool $isActive): TahunAjaran
    {
        $ta = TahunAjaran::make([
            'nama_tahun_ajaran' => $nama,
            'semester' => $semester,
            'is_active' => $isActive,
        ]);
        $ta->setAttribute('id', $id);

        return $ta;
    }

    private function makeKelas(
        int $id,
        int $taId,
        string $namaKelas,
        ?string $waliNipy,
        ?string $pendampingNipy,
        TahunAjaran $ta
    ): Kelas {
        $kelas = Kelas::make([
            'akademik_master_tahun_ajaran_id' => $taId,
            'karyawan_nipy' => $waliNipy,
            'pendamping_nipy' => $pendampingNipy,
            'nama_kelas' => $namaKelas,
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => '5',
        ]);
        $kelas->setAttribute('id', $id);
        $kelas->setRelation('tahunAjaran', $ta);

        return $kelas;
    }
}
