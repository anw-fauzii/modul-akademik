<?php

namespace Tests\Unit;

use App\Http\Middleware\DecodeSqidParameters;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\DaftarJilid;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Pelajaran\JadwalPelajaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Admin\Penilaian\AspekTp;
use App\Models\Admin\Penilaian\RekapMapel;
use App\Models\Admin\Penilaian\SumatifBab;
use App\Models\Admin\Presensi\PresensiSiswa;
use App\Models\Admin\T2q\YaumiyahTahfizh;
use App\Models\Admin\T2q\YaumiyahTahsin;
use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use App\Models\User;
use App\Services\WaliKelas\WaliKelasScopeService;
use App\Support\Sqid;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class SqidAndPolicyTest extends TestCase
{
    private function makeUser(string $roleName, string $noInduk = 'NIPY-001'): User
    {
        $role = new Role;
        $role->setRawAttributes(['name' => $roleName, 'guard_name' => 'web']);
        $role->setRelation('permissions', new EloquentCollection);

        $user = new User;
        $user->setRawAttributes([
            'id' => 1,
            'name' => 'Test User',
            'email' => 'user@primainsani.sch.id',
            'no_induk' => $noInduk,
        ]);
        $user->setRelation('roles', new EloquentCollection([$role]));
        $user->setRelation('permissions', new EloquentCollection);

        return $user;
    }

    public function test_every_routable_model_roundtrips_and_is_type_isolated(): void
    {
        $this->assertCount(33, Sqid::TYPE_MAP);

        foreach (Sqid::TYPE_MAP as $class) {
            $key = Sqid::usesStringKey($class) ? 'AB-001' : 42;
            $token = Sqid::encodeFor($class, $key);

            $this->assertNotNull($token);
            $this->assertGreaterThanOrEqual(8, strlen($token));
            $this->assertSame($key, Sqid::decodeFor($class, $token), $class);
            $this->assertNull(Sqid::decodeFor($class, (string) $key), "Raw key harus ditolak untuk {$class}");
        }

        $kelasToken = Sqid::encodeFor(Kelas::class, 42);
        $this->assertNull(Sqid::decodeFor(Siswa::class, $kelasToken));
        $this->assertNull(Sqid::decodeFor(AnggotaKelas::class, $kelasToken));
        $this->assertNull(Sqid::decodeFor(Kelas::class, $kelasToken.'x'));
    }

    public function test_string_pk_with_leading_zero_and_punctuation_is_lossless(): void
    {
        foreach (['00123', '202601001', '0', 'NIS/2026-01.A'] as $nis) {
            $token = Sqid::encodeFor(Siswa::class, $nis);
            $this->assertSame($nis, Sqid::decodeFor(Siswa::class, $token));
        }

        foreach (['NIPY-007', '009911', '19900101 202001 1 001'] as $nipy) {
            $token = Sqid::encodeFor(Karyawan::class, $nipy);
            $this->assertSame($nipy, Sqid::decodeFor(Karyawan::class, $token));
        }
    }

    public function test_route_binding_and_encode_query_params_work_consistently(): void
    {
        $this->assertNull((new Kelas)->resolveRouteBinding('10'));
        $this->assertNull((new Kelas)->resolveRouteBinding('bukan-sqid'));
        $this->assertNull((new Siswa)->resolveRouteBinding('202601001'));

        $kelas = new Kelas;
        $kelas->id = 7;
        $this->assertSame(Sqid::encodeFor(Kelas::class, 7), $kelas->getRouteKey());
        $this->assertSame($kelas->getRouteKey(), $kelas->sqid);

        $encodedQuery = Sqid::encodeQueryParams([
            'kelas_id' => 7,
            'tahun_ajaran_id' => 'all',
            'periode' => 'mid',
        ]);

        $this->assertSame($kelas->sqid, $encodedQuery['kelas_id']);
        $this->assertSame('all', $encodedQuery['tahun_ajaran_id']);
        $this->assertSame('mid', $encodedQuery['periode']);
    }

    public function test_middleware_decodes_query_and_post_params_keeps_sentinels_and_rejects_raw_ids(): void
    {
        $middleware = new DecodeSqidParameters;
        $kelasSqid = Sqid::encodeFor(Kelas::class, 10);
        $taSqid = Sqid::encodeFor(TahunAjaran::class, 3);
        $nipySqid = Sqid::encodeFor(Karyawan::class, 'NIPY-001');

        $getRequest = Request::create('/wali-kelas/anggota-kelas', 'GET', [
            'kelas_id' => $kelasSqid,
            'tahun_ajaran_id' => 'all',
            'karyawan_nipy' => $nipySqid,
        ]);
        $middleware->handle($getRequest, fn (Request $r) => response('ok'));
        $this->assertSame('10', $getRequest->query('kelas_id'));
        $this->assertSame(10, $getRequest->integer('kelas_id'));
        $this->assertSame('all', $getRequest->query('tahun_ajaran_id'));
        $this->assertSame('NIPY-001', $getRequest->query('karyawan_nipy'));

        $postRequest = Request::create('/penilaian/nilai-tp', 'POST', [
            'tahun_ajaran_id' => $taSqid,
            'kelas_id' => $kelasSqid,
        ]);
        $middleware->handle($postRequest, fn (Request $r) => response('ok'));
        $this->assertSame('3', $postRequest->input('tahun_ajaran_id'));
        $this->assertSame('10', $postRequest->input('kelas_id'));

        $this->expectException(NotFoundHttpException::class);
        $middleware->handle(Request::create('/wali-kelas/anggota-kelas', 'GET', ['kelas_id' => '10']), fn () => response('ok'));
    }

    public function test_all_registered_policies_enforce_super_admin_and_wali_kelas_scopes_without_lazy_loading(): void
    {
        $admin = $this->makeUser('super admin');
        $wali = $this->makeUser('wali kelas', 'NIPY-001');

        foreach (array_merge(array_values(Sqid::TYPE_MAP), [DaftarJilid::class]) as $modelClass) {
            $this->assertNotNull(Gate::getPolicyFor($modelClass), "Policy wajib terdaftar untuk {$modelClass}");
        }

        $ta = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);
        $ta->id = 1;

        $ownKelas = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'karyawan_nipy' => 'NIPY-001',
            'nama_kelas' => '1 Al-Fatihah',
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => '1',
        ]);
        $ownKelas->id = 10;

        $foreignKelas = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'karyawan_nipy' => 'NIPY-999',
            'nama_kelas' => '2 An-Naba',
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => '2',
        ]);
        $foreignKelas->id = 99;

        $ownMapel = MataPelajaran::make(['nama_mata_pelajaran' => 'Matematika', 'unit' => 'SD', 'status' => true]);
        $ownMapel->id = 5;

        $foreignMapel = MataPelajaran::make(['nama_mata_pelajaran' => 'IPA', 'unit' => 'SD', 'status' => true]);
        $foreignMapel->id = 6;

        $ownAnggota = AnggotaKelas::make(['siswa_nis' => '202601', 'akademik_master_kelas_id' => 10]);
        $ownAnggota->id = 100;
        $foreignAnggota = AnggotaKelas::make(['siswa_nis' => '202602', 'akademik_master_kelas_id' => 99]);
        $foreignAnggota->id = 200;

        $ownPresensi = PresensiSiswa::make(['anggota_kelas_id' => 100, 'tanggal' => '2026-11-19', 'status' => 'hadir']);
        $ownPresensi->id = 300;
        $ownPresensi->setRelation('anggotaKelas', $ownAnggota);

        $foreignPresensi = PresensiSiswa::make(['anggota_kelas_id' => 200, 'tanggal' => '2026-11-19', 'status' => 'hadir']);
        $foreignPresensi->id = 301;
        $foreignPresensi->setRelation('anggotaKelas', $foreignAnggota);

        $ownTp = TujuanPembelajaran::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'akademik_pelajaran_mata_pelajaran_id' => 5,
            'tingkatan_kelas' => '1',
            'kode_tp' => 'TP.1',
        ]);
        $ownTp->id = 400;

        $foreignTp = TujuanPembelajaran::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'akademik_pelajaran_mata_pelajaran_id' => 6,
            'tingkatan_kelas' => '2',
            'kode_tp' => 'TP.2',
        ]);
        $foreignTp->id = 401;

        $ownAspek = AspekTp::make(['akademik_tujuan_pembelajaran_id' => 400, 'periode' => 'mid', 'nama_aspek' => 'Lisan']);
        $ownAspek->id = 500;
        $ownAspek->setRelation('tujuanPembelajaran', $ownTp);

        $foreignAspek = AspekTp::make(['akademik_tujuan_pembelajaran_id' => 401, 'periode' => 'mid', 'nama_aspek' => 'Tulis']);
        $foreignAspek->id = 501;
        $foreignAspek->setRelation('tujuanPembelajaran', $foreignTp);

        $ownSumatif = SumatifBab::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'akademik_pelajaran_mata_pelajaran_id' => 5,
            'tingkatan_kelas' => '1',
            'periode' => 'mid',
            'nama_bab' => 'Bab 1',
        ]);
        $ownSumatif->id = 600;

        $foreignSumatif = SumatifBab::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'akademik_pelajaran_mata_pelajaran_id' => 6,
            'tingkatan_kelas' => '2',
            'periode' => 'mid',
            'nama_bab' => 'Bab 2',
        ]);
        $foreignSumatif->id = 601;

        $ownRekap = RekapMapel::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'akademik_pelajaran_mata_pelajaran_id' => 5,
            'anggota_kelas_id' => 100,
            'periode' => 'mid',
        ]);
        $ownRekap->id = 700;
        $ownRekap->setRelation('anggotaKelas', $ownAnggota);

        $foreignRekap = RekapMapel::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'akademik_pelajaran_mata_pelajaran_id' => 6,
            'anggota_kelas_id' => 200,
            'periode' => 'mid',
        ]);
        $foreignRekap->id = 701;
        $foreignRekap->setRelation('anggotaKelas', $foreignAnggota);

        $scopeMock = $this->getMockBuilder(WaliKelasScopeService::class)
            ->onlyMethods(['resolveScopeContext', 'getAllAssignedClassesForUser', 'getTaughtSdScopeForUser'])
            ->getMock();

        $scopeMock->method('resolveScopeContext')->willReturnCallback(
            function (User $u, ?int $reqKelasId = null) use ($ta, $ownKelas): array {
                $assigned = new EloquentCollection([$ownKelas]);
                $selected = $reqKelasId !== null
                    ? ($assigned->firstWhere('id', $reqKelasId) ?? $assigned->first())
                    : $assigned->first();

                return [
                    'isEligible' => true,
                    'ineligibleReason' => null,
                    'nipy' => 'NIPY-001',
                    'karyawanProfile' => null,
                    'activeTahunAjaran' => $ta,
                    'assignedClasses' => $assigned,
                    'selectedKelas' => $selected,
                    'peranDiKelas' => 'Wali Kelas',
                ];
            }
        );

        $scopeMock->method('getAllAssignedClassesForUser')->willReturn(new EloquentCollection([$ownKelas]));
        $scopeMock->method('getTaughtSdScopeForUser')->willReturn([
            'isEligible' => true,
            'ineligibleReason' => null,
            'nipy' => 'NIPY-001',
            'karyawanProfile' => null,
            'activeTahunAjaran' => $ta,
            'taughtClasses' => new EloquentCollection([$ownKelas]),
            'taughtMapelsByClass' => collect([10 => new EloquentCollection([$ownMapel])]),
        ]);

        $this->app->instance(WaliKelasScopeService::class, $scopeMock);

        $registrar = app(PermissionRegistrar::class);
        $permProp = new \ReflectionProperty($registrar, 'permissions');
        $permProp->setAccessible(true);
        $permProp->setValue($registrar, new EloquentCollection);

        Model::preventLazyLoading(true);
        try {
            // Super Admin diizinkan penuh
            $this->assertTrue(Gate::forUser($admin)->allows('create', Siswa::class));
            $this->assertTrue(Gate::forUser($admin)->allows('delete', $foreignKelas));
            $this->assertTrue(Gate::forUser($admin)->allows('delete', new YaumiyahTahsin));
            $this->assertTrue(Gate::forUser($admin)->allows('delete', new YaumiyahTahfizh));

            // Wali Kelas ditolak pada resource Admin-only
            $this->assertTrue(Gate::forUser($wali)->denies('create', Siswa::class));
            $this->assertTrue(Gate::forUser($wali)->denies('update', $ownKelas));
            $this->assertTrue(Gate::forUser($wali)->denies('create', JadwalPelajaran::class));
            $this->assertTrue(Gate::forUser($wali)->denies('create', TujuanPembelajaran::class));
            $this->assertTrue(Gate::forUser($wali)->denies('create', YaumiyahTahsin::class));

            // Wali Kelas diizinkan untuk kelas, siswa, presensi, & mapel SD binaannya sendiri
            $this->assertTrue(Gate::forUser($wali)->allows('view', $ownKelas));
            $this->assertTrue(Gate::forUser($wali)->allows('viewT2qStatistik', $ownKelas));
            $this->assertTrue(Gate::forUser($wali)->allows('view', $ownAnggota));
            $this->assertTrue(Gate::forUser($wali)->allows('update', $ownPresensi));
            $this->assertTrue(Gate::forUser($wali)->allows('delete', $ownPresensi));
            $this->assertTrue(Gate::forUser($wali)->allows('toggleMasukRapor', $ownTp));
            $this->assertTrue(Gate::forUser($wali)->allows('update', $ownAspek));
            $this->assertTrue(Gate::forUser($wali)->allows('update', $ownSumatif));
            $this->assertTrue(Gate::forUser($wali)->allows('updateDeskripsi', $ownRekap));
            $this->assertTrue(Gate::forUser($wali)->allows('viewMatriks', YaumiyahTahsin::class));
            $this->assertTrue(Gate::forUser($wali)->allows('viewStatistik', YaumiyahTahfizh::class));

            // Wali Kelas ditolak (403) untuk kelas, siswa, presensi, & mapel milik guru/kelas lain
            $this->assertTrue(Gate::forUser($wali)->denies('view', $foreignKelas));
            $this->assertTrue(Gate::forUser($wali)->denies('viewT2qStatistik', $foreignKelas));
            $this->assertTrue(Gate::forUser($wali)->denies('view', $foreignAnggota));
            $this->assertTrue(Gate::forUser($wali)->denies('update', $foreignPresensi));
            $this->assertTrue(Gate::forUser($wali)->denies('delete', $foreignPresensi));
            $this->assertTrue(Gate::forUser($wali)->denies('toggleMasukRapor', $foreignTp));
            $this->assertTrue(Gate::forUser($wali)->denies('update', $foreignAspek));
            $this->assertTrue(Gate::forUser($wali)->denies('update', $foreignSumatif));
            $this->assertTrue(Gate::forUser($wali)->denies('updateDeskripsi', $foreignRekap));
        } finally {
            Model::preventLazyLoading(false);
        }
    }
}
