<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\Presensi\PresensiSiswaController;
use App\Http\Requests\Admin\Presensi\FingerspotSyncRequest;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Presensi\PresensiSiswa;
use App\Models\User;
use App\Services\Admin\Presensi\FingerspotSyncService;
use App\Services\Admin\Presensi\PresensiSiswaService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use ReflectionProperty;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FingerspotSyncTest extends TestCase
{
    private function makeUser(string $roleName): User
    {
        $role = new Role;
        $role->setRawAttributes(['name' => $roleName, 'guard_name' => 'web']);
        $role->setRelation('permissions', new EloquentCollection);

        $user = new User;
        $user->setRawAttributes([
            'id' => 1,
            'name' => 'Test User',
            'email' => 'user@primainsani.sch.id',
            'no_induk' => 'NIPY-001',
        ]);
        $user->setRelation('roles', new EloquentCollection([$role]));
        $user->setRelation('permissions', new EloquentCollection);

        return $user;
    }

    public function test_normalize_nis_removes_spaces_nbsp_zero_width_and_bom(): void
    {
        $dirty = " \u{FEFF}2026\u{00A0}01\u{200B}009\u{200D} \t ";
        $this->assertSame('202601009', FingerspotSyncService::normalizeNis($dirty));
        $this->assertSame('', FingerspotSyncService::normalizeNis(" \u{00A0}\u{200B} "));
        $this->assertSame('', FingerspotSyncService::normalizeNis(null));
    }

    public function test_config_rejects_missing_credentials_and_sanitizes_cloud_ids(): void
    {
        $service = new FingerspotSyncService;

        config()->set('services.fingerspot', [
            'base_url' => 'https://developer.fingerspot.io/api',
            'endpoint_path' => '/get_attlog',
            'api_token' => '',
            'cloud_ids' => ['C2630450C30A1D24'],
        ]);

        $statusEmpty = $service->getConfigurationStatus();
        $this->assertFalse($statusEmpty['is_configured']);
        $this->assertFalse($statusEmpty['has_token']);
        $this->assertSame(1, $statusEmpty['cloud_count']);

        $resolveConfig = new ReflectionMethod($service, 'resolveConfiguration');
        $resolveConfig->setAccessible(true);

        try {
            $resolveConfig->invoke($service);
            $this->fail('Harus melempar ValidationException ketika token kosong.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('fingerspot', $e->errors());
        }

        // Pastikan cloud ID invalid / payload injeksi tersaring ketat
        config()->set('services.fingerspot', [
            'base_url' => 'https://developer.fingerspot.io/api',
            'endpoint_path' => '/get_attlog',
            'api_token' => 'secret-token-123',
            'cloud_ids' => ['C2630450C30A1D24', "C26'; DROP TABLE--", 'ab', 'C2630450C30A1D24', 'C262C44523180D2B'],
            'timeout' => 999,
            'retry_times' => 0,
            'retry_sleep_ms' => 10,
        ]);

        $resolved = $resolveConfig->invoke($service);
        $this->assertSame(['C2630450C30A1D24', 'C262C44523180D2B'], $resolved['cloud_ids']);
        $this->assertSame(120, $resolved['timeout']);
        $this->assertSame(1, $resolved['retry_times']);
        $this->assertSame(100, $resolved['retry_sleep_ms']);
    }

    public function test_fetch_earliest_scan_logs_picks_earliest_scan_and_tolerates_failed_machine(): void
    {
        $capturedAuthHeaders = [];

        Http::fake([
            'https://developer.fingerspot.io/api/get_attlog' => static function (ClientRequest $request) use (&$capturedAuthHeaders) {
                $capturedAuthHeaders[] = $request->header('Authorization')[0] ?? null;
                $cloudId = $request->data()['cloud_id'] ?? '';

                if ($cloudId === 'CLOUD_ERR') {
                    return Http::response(['message' => 'Device offline'], 503);
                }

                if ($cloudId === 'CLOUD_A') {
                    return Http::response([
                        'data' => [
                            ['pin' => " 2026\u{00A0}001 ", 'scan_date' => '2026-11-24 07:42:15'],
                            ['pin' => '2026001', 'scan_date' => '2026-11-24 07:18:05'],
                            ['pin' => '2026002', 'scan_date' => '2026-11-24 07:50:10'],
                        ],
                    ], 200);
                }

                return Http::response([
                    'data' => [
                        ['pin' => '2026001', 'scan_date' => '2026-11-24 07:12:30'],
                        ['pin' => '', 'scan_date' => '2026-11-24 07:00:00'],
                        ['pin' => '2026003', 'scan_date' => 'bukan-tanggal'],
                    ],
                ], 200);
            },
        ]);

        $service = new FingerspotSyncService;
        $fetchMethod = new ReflectionMethod($service, 'fetchEarliestScanLogs');
        $fetchMethod->setAccessible(true);

        $result = $fetchMethod->invoke($service, [
            'endpoint_url' => 'https://developer.fingerspot.io/api/get_attlog',
            'api_token' => 'token-rahasia',
            'cloud_ids' => ['CLOUD_A', 'CLOUD_ERR', 'CLOUD_B'],
            'timeout' => 15,
            'retry_times' => 1,
            'retry_sleep_ms' => 100,
        ], '2026-11-24', '2026-11-24');

        $this->assertSame(['Bearer token-rahasia', 'Bearer token-rahasia', 'Bearer token-rahasia'], $capturedAuthHeaders);
        $this->assertSame(3, $result['clouds_total']);
        $this->assertSame(2, $result['clouds_success']);
        $this->assertSame([['cloud_id' => 'CLOUD_ERR', 'status' => 503]], $result['failed_clouds']);
        $this->assertSame(4, $result['logs_fetched']);
        $this->assertSame(2, $result['unique_student_days']);

        /** @var Carbon $scanSiswa1 */
        $scanSiswa1 = $result['earliest_logs']['2026001']['2026-11-24'];
        $this->assertSame('07:12:30', $scanSiswa1->format('H:i:s'));

        /** @var Carbon $scanSiswa2 */
        $scanSiswa2 = $result['earliest_logs']['2026002']['2026-11-24'];
        $this->assertSame('07:50:10', $scanSiswa2->format('H:i:s'));
    }

    public function test_service_uses_single_source_of_truth_lateness_and_date_locks_without_raw_sql(): void
    {
        $source = file_get_contents(app_path('Services/Admin/Presensi/FingerspotSyncService.php'));
        $this->assertIsString($source);
        $this->assertStringNotContainsString('->whereRaw(', $source);
        $this->assertStringNotContainsString('DB::raw(', $source);
        $this->assertStringContainsString('PresensiSiswa::calculateKeterlambatanMenit', $source);
        $this->assertStringContainsString('insertOrIgnore', $source);

        $this->assertSame(0, PresensiSiswa::calculateKeterlambatanMenit('07:12:30'));
        $this->assertSame(0, PresensiSiswa::calculateKeterlambatanMenit('07:35:59'));
        $this->assertSame(1, PresensiSiswa::calculateKeterlambatanMenit('07:36:00'));
        $this->assertSame(15, PresensiSiswa::calculateKeterlambatanMenit('07:50:10'));

        $service = new FingerspotSyncService;
        $lockCheck = new ReflectionMethod($service, 'isDateLockedForClass');
        $lockCheck->setAccessible(true);

        $semesterLocked = [10 => true];
        $midCutoffs = [20 => '2026-10-15'];

        $this->assertTrue($lockCheck->invoke($service, 10, '2026-11-24', $semesterLocked, $midCutoffs));
        $this->assertTrue($lockCheck->invoke($service, 20, '2026-10-15', $semesterLocked, $midCutoffs));
        $this->assertTrue($lockCheck->invoke($service, 20, '2026-10-01', $semesterLocked, $midCutoffs));
        $this->assertFalse($lockCheck->invoke($service, 20, '2026-10-16', $semesterLocked, $midCutoffs));
        $this->assertFalse($lockCheck->invoke($service, 30, '2026-11-24', $semesterLocked, $midCutoffs));
    }

    public function test_sync_request_and_policy_enforce_super_admin_only_and_date_range_limits(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $permProp = new ReflectionProperty($registrar, 'permissions');
        $permProp->setAccessible(true);
        $permProp->setValue($registrar, new EloquentCollection);

        $admin = $this->makeUser('super admin');
        $wali = $this->makeUser('wali kelas');
        $guruMapel = $this->makeUser('guru mapel sd');
        $guruT2q = $this->makeUser('guru t2q');

        $this->assertTrue(Gate::forUser($admin)->allows('syncFingerspot', PresensiSiswa::class));
        $this->assertFalse(Gate::forUser($wali)->allows('syncFingerspot', PresensiSiswa::class));
        $this->assertFalse(Gate::forUser($guruMapel)->allows('syncFingerspot', PresensiSiswa::class));
        $this->assertFalse(Gate::forUser($guruT2q)->allows('syncFingerspot', PresensiSiswa::class));

        $request = FingerspotSyncRequest::create('/presensi/presensi-siswa/sync-fingerspot', 'POST', [
            'start_date' => '2026-11-01',
            'end_date' => '2026-12-15',
        ]);
        $prepareMethod = new ReflectionMethod(FingerspotSyncRequest::class, 'prepareForValidation');
        $prepareMethod->invoke($request);

        $validator = Validator::make($request->all(), $request->rules(), $request->messages());
        foreach ($request->after() as $afterCallback) {
            $validator->after($afterCallback);
        }
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('end_date', $validator->errors()->toArray());
    }

    public function test_controller_delegates_sync_to_service_preserves_redirect_filters_and_command_runs(): void
    {
        $presensiService = $this->createMock(PresensiSiswaService::class);
        $fingerspotService = $this->createMock(FingerspotSyncService::class);
        $fingerspotService->expects($this->exactly(2))
            ->method('syncDateRange')
            ->with('2026-11-24', '2026-11-24')
            ->willReturn([
                'start_date' => '2026-11-24',
                'end_date' => '2026-11-24',
                'clouds_total' => 7,
                'clouds_success' => 7,
                'failed_clouds' => [],
                'logs_fetched' => 30,
                'unique_student_days' => 25,
                'inserted' => 23,
                'hadir' => 20,
                'terlambat' => 3,
                'skipped_existing' => 2,
                'skipped_locked' => 0,
                'unmatched_pins' => [],
            ]);

        $controller = new PresensiSiswaController($presensiService, $fingerspotService);

        $middlewares = collect($controller->getMiddleware());
        $this->assertTrue($middlewares->contains(
            static fn (array $m): bool => ($m['middleware'] ?? '') === 'role:super admin'
        ));
        $this->assertTrue($middlewares->contains(
            static fn (array $m): bool => ($m['middleware'] ?? '') === 'can:syncFingerspot,'.PresensiSiswa::class
        ));

        $req = FingerspotSyncRequest::create('/presensi/presensi-siswa/sync-fingerspot', 'POST', [
            'start_date' => '2026-11-24',
            'end_date' => '2026-11-24',
            'redirect_kelas_id' => 10,
            'redirect_bulan' => '2026-11',
        ]);
        $validator = Validator::make($req->all(), $req->rules());
        $validator->validate();
        $req->setValidator($validator);

        $response = $controller->syncFingerspot($req);
        $this->assertTrue($response->isRedirect(route('presensi-siswa.index', [
            'kelas_id' => sqidParam('kelas_id', 10),
            'bulan' => '2026-11',
        ])));
        $this->assertStringContainsString('23 presensi baru disimpan', (string) $response->getSession()->get('success'));
        $this->assertStringContainsString('20 hadir, 3 terlambat', (string) $response->getSession()->get('success'));

        $this->app->instance(FingerspotSyncService::class, $fingerspotService);
        $exitCode = Artisan::call('presensi:sync-fingerspot', [
            '--start-date' => '2026-11-24',
            '--end-date' => '2026-11-24',
        ]);
        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('23 disimpan', Artisan::output());
    }

    public function test_admin_view_shows_fingerspot_sync_modal_while_wali_kelas_view_hides_it(): void
    {
        view()->share('errors', new ViewErrorBag);

        $ta = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);
        $ta->id = 1;
        $kelas = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'nama_kelas' => '1A',
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => 1,
        ]);
        $kelas->id = 10;
        $kelas->setRelation('tahunAjaran', $ta);
        $kelas->setRelation('waliKelas', null);

        $siswa = Siswa::make(['nis' => '2026001', 'nama_lengkap' => 'Ahmad Fauzan']);
        $anggota = AnggotaKelas::make(['id' => 5, 'siswa_nis' => '2026001', 'akademik_master_kelas_id' => 10]);
        $anggota->setRelation('siswa', $siswa);

        $baseData = [
            'hasFilter' => true,
            'hasExplicitFilter' => false,
            'summary' => ['total' => 0, 'hadir' => 0, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0],
            'matrix' => [
                'kelas' => $kelas,
                'bulan' => '2026-11',
                'bulan_label' => 'November 2026',
                'days' => [],
                'rows' => [['anggota' => $anggota, 'cells' => [], 'counts' => [
                    'hadir' => 0, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0,
                    'total_catatan' => 0, 'persentase_hadir' => 0, 'persentase_terlambat' => 0,
                ]]],
                'daily_totals' => [],
                'summary' => [
                    'siswa' => 1, 'total_catatan' => 0, 'hadir' => 0, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0,
                    'persentase_hadir' => 0, 'persentase_terlambat' => 0,
                ],
            ],
            'selectedKelas' => $kelas,
            'kelasOptions' => collect([$kelas]),
            'tahunAjaranOptions' => collect([$ta]),
            'statusOptions' => PresensiSiswa::STATUS_OPTIONS,
            'filters' => ['tanggal' => '', 'status' => '', 'kelas_id' => 10, 'tahun_ajaran_id' => 1, 'bulan' => '2026-11'],
            'fingerspotStatus' => ['is_configured' => true, 'has_token' => true, 'cloud_count' => 7, 'endpoint_url' => 'https://developer.fingerspot.io/api/get_attlog'],
        ];

        Model::preventLazyLoading(true);
        try {
            $adminHtml = view('admin.presensi.presensiSiswa.index', $baseData)->render();
            $waliHtml = view('admin.presensi.presensiSiswa.index', array_merge($baseData, [
                'presensiRoutePrefix' => 'wali-kelas.presensi-siswa',
                'isWaliKelasScope' => true,
            ]))->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('id="openFingerspotSyncModalBtn"', $adminHtml);
        $this->assertStringContainsString('id="presensiFingerspotModal"', $adminHtml);
        $this->assertStringContainsString(route('presensi-siswa.sync-fingerspot'), $adminHtml);
        $this->assertStringContainsString('<strong>7</strong> mesin Cloud ID', $adminHtml);

        $this->assertStringNotContainsString('id="openFingerspotSyncModalBtn"', $waliHtml);
        $this->assertStringNotContainsString('id="presensiFingerspotModal"', $waliHtml);
    }
}
