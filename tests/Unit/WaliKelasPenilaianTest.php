<?php

namespace Tests\Unit;

use App\Http\Controllers\WaliKelas\PenilaianTpController;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Admin\Penilaian\AspekTp;
use App\Models\Admin\Penilaian\Kkm;
use App\Models\Admin\Penilaian\KkmPredikat;
use App\Models\Admin\Penilaian\NilaiAspek;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Models\Admin\Penilaian\RekapMapel;
use App\Models\Admin\Penilaian\RekapTp;
use App\Models\Admin\Penilaian\SumatifBab;
use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use App\Models\User;
use App\Services\Admin\Penilaian\PenilaianTpService;
use App\Services\WaliKelas\WaliKelasPenilaianService;
use App\Services\WaliKelas\WaliKelasScopeService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ViewErrorBag;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class WaliKelasPenilaianTest extends TestCase
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
    private function makeSdPenilaianFixtures(): array
    {
        $ta = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);
        $ta->id = 1;

        $wali = Karyawan::make(['nipy' => 'NIPY-001', 'nama_lengkap' => 'Sari Wulandari', 'gelar' => 'S.Pd']);
        $kelasSd = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'karyawan_nipy' => 'NIPY-001',
            'pendamping_nipy' => 'NIPY-002',
            'tingkatan_kelas' => '5',
            'nama_kelas' => '5 Al-Fatihah',
            'jenjang_kelas' => 'SD',
        ]);
        $kelasSd->id = 11;
        $kelasSd->setRelation('tahunAjaran', $ta);
        $kelasSd->setRelation('waliKelas', $wali);

        $kelasSdLain = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'karyawan_nipy' => 'NIPY-LAIN',
            'pendamping_nipy' => null,
            'tingkatan_kelas' => '5',
            'nama_kelas' => '5 An-Naba',
            'jenjang_kelas' => 'SD',
        ]);
        $kelasSdLain->id = 13;
        $kelasSdLain->setRelation('tahunAjaran', $ta);

        $kelasSmp = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'karyawan_nipy' => 'NIPY-001',
            'tingkatan_kelas' => '7',
            'nama_kelas' => '7 Khadijah',
            'jenjang_kelas' => 'SMP',
        ]);
        $kelasSmp->id = 12;
        $kelasSmp->setRelation('tahunAjaran', $ta);

        $mapel = MataPelajaran::make(['singkatan' => 'PAI', 'nama_mata_pelajaran' => 'Pendidikan Agama Islam', 'unit' => 'SD']);
        $mapel->id = 21;

        $mapelMtk = MataPelajaran::make(['singkatan' => 'MTK', 'nama_mata_pelajaran' => 'Matematika', 'unit' => 'SD']);
        $mapelMtk->id = 22;

        $kkm = Kkm::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'akademik_pelajaran_mata_pelajaran_id' => 21,
            'tingkatan_kelas' => '5',
            'nilai_kkm' => 75,
            'bobot_tp' => 50,
            'bobot_sumatif' => 25,
            'bobot_ujian' => 25,
        ]);
        $kkm->id = 31;

        $predikatA = KkmPredikat::make(['predikat' => 'A', 'keterangan' => 'SAB', 'nilai_min' => 92, 'nilai_max' => 100, 'is_tuntas' => true]);

        $tp = TujuanPembelajaran::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'akademik_pelajaran_mata_pelajaran_id' => 21,
            'tingkatan_kelas' => '5',
            'kode_tp' => 'TP 5.1',
            'tujuan_pembelajaran' => 'Membaca Al-Quran dengan tartil',
            'lingkup_materi' => 'Tajwid',
            'status' => true,
        ]);
        $tp->id = 101;

        $aspek = AspekTp::make([
            'tujuan_pembelajaran_id' => 101,
            'periode' => PeriodeTp::PERIODE_MID,
            'nama_aspek' => 'Makhroj',
            'teknik_penilaian' => 'praktik',
            'bobot' => 100,
            'masuk_hitung_na_tp' => true,
            'urutan' => 1,
        ]);
        $aspek->id = 201;

        $sumatifBab = SumatifBab::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'akademik_pelajaran_mata_pelajaran_id' => 21,
            'tingkatan_kelas' => '5',
            'periode' => PeriodeTp::PERIODE_MID,
            'nama_bab' => 'Bab 1',
            'urutan' => 1,
        ]);
        $sumatifBab->id = 401;

        $siswa = Siswa::make(['nis' => '202601001', 'nama_lengkap' => 'Ahmad Fauzi', 'jenis_kelamin' => 'L', 'status' => 'Aktif']);
        $anggota = AnggotaKelas::make(['siswa_nis' => '202601001', 'akademik_master_kelas_id' => 11, 'pendaftaran' => 'Naik Kelas']);
        $anggota->id = 301;
        $anggota->setRelation('siswa', $siswa);
        $anggota->setRelation('kelas', $kelasSd);

        $nilai = NilaiAspek::make(['aspek_tp_id' => 201, 'anggota_kelas_id' => 301, 'nilai' => 88]);
        $rekapTp = RekapTp::make(['tujuan_pembelajaran_id' => 101, 'anggota_kelas_id' => 301, 'periode' => 'mid', 'nilai_akhir_tp' => 88, 'predikat' => 'B', 'tuntas_kkm' => true]);
        $rekapMapel = RekapMapel::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'akademik_pelajaran_mata_pelajaran_id' => 21,
            'anggota_kelas_id' => 301,
            'periode' => 'mid',
            'rata_rata_tp' => 88,
            'nilai_ujian' => 90,
            'nilai_akhir_rapor' => 89,
            'predikat_rapor' => 'B',
            'tuntas_kkm' => true,
            'deskripsi_capaian' => 'Menunjukkan penguasaan sangat baik pada Tajwid.',
        ]);
        $rekapMapel->id = 501;
        $rekapMapel->setRelation('anggotaKelas', $anggota);

        return compact('ta', 'wali', 'kelasSd', 'kelasSdLain', 'kelasSmp', 'mapel', 'mapelMtk', 'kkm', 'predikatA', 'tp', 'aspek', 'sumatifBab', 'anggota', 'nilai', 'rekapTp', 'rekapMapel');
    }

    public function test_scope_service_authorizes_sd_scope_and_blocks_cross_class_grade_and_year(): void
    {
        $f = $this->makeSdPenilaianFixtures();
        $user = $this->makeUserWithRole('Ustadzah Sari', 'sari@primainsani.sch.id', 'NIPY-001', 'wali kelas', $f['wali']);

        $taughtClasses = new EloquentCollection([$f['kelasSd'], $f['kelasSdLain']]);
        $taughtMapelsByClass = collect([
            11 => new EloquentCollection([$f['mapel']]),
            13 => new EloquentCollection([$f['mapelMtk']]),
        ]);

        $mockScope = $this->getMockBuilder(WaliKelasScopeService::class)
            ->onlyMethods(['resolveScopeContext', 'getTaughtSdScopeForUser'])
            ->getMock();

        $mockScope->method('resolveScopeContext')
            ->willReturn([
                'isEligible' => true,
                'ineligibleReason' => null,
                'nipy' => 'NIPY-001',
                'karyawanProfile' => $f['wali'],
                'activeTahunAjaran' => $f['ta'],
                'assignedClasses' => new EloquentCollection([$f['kelasSd'], $f['kelasSmp']]),
                'selectedKelas' => $f['kelasSd'],
                'peranDiKelas' => 'Wali Kelas',
            ]);
        $mockScope->method('getTaughtSdScopeForUser')
            ->willReturn([
                'isEligible' => true,
                'ineligibleReason' => null,
                'nipy' => 'NIPY-001',
                'karyawanProfile' => $f['wali'],
                'activeTahunAjaran' => $f['ta'],
                'taughtClasses' => $taughtClasses,
                'taughtMapelsByClass' => $taughtMapelsByClass,
            ]);

        Model::preventLazyLoading(true);
        try {
            $auth = $mockScope->authorizeSdMapelScope($user, 11, 21, 1, '5');
            $this->assertSame($f['kelasSd'], $auth['selectedKelas']);
            $this->assertCount(1, $auth['allowedMapels']);

            // Kelas SD lain yang tidak diampu sebagai Wali Kelas/Pendamping tetap diizinkan bila mapel diampu di jadwal
            $otherClassAuth = $mockScope->authorizeSdMapelScope($user, 13, 22, 1, '5');
            $this->assertSame($f['kelasSdLain'], $otherClassAuth['selectedKelas']);
            $this->assertSame('MTK', $otherClassAuth['allowedMapels']->first()?->singkatan);

            $rekapAuth = $mockScope->authorizeRekapMapelForUser($user, $f['rekapMapel']);
            $this->assertSame($f['kelasSd'], $rekapAuth['selectedKelas']);

            $otherClassAnggota = AnggotaKelas::make(['siswa_nis' => '202602001', 'akademik_master_kelas_id' => 13]);
            $otherClassRekap = RekapMapel::make([
                'akademik_master_tahun_ajaran_id' => 1,
                'akademik_pelajaran_mata_pelajaran_id' => 22,
                'anggota_kelas_id' => 302,
            ]);
            $otherClassRekap->setRelation('anggotaKelas', $otherClassAnggota);
            $otherRekapAuth = $mockScope->authorizeRekapMapelForUser($user, $otherClassRekap);
            $this->assertSame($f['kelasSdLain'], $otherRekapAuth['selectedKelas']);

            // Mapel lain di kelas yang sama (atau mapel yang diampu di kelas berbeda) harus ditolak 403
            try {
                $mockScope->authorizeSdMapelScope($user, 13, 21, 1, '5');
                $this->fail('Seharusnya menolak mapel PAI pada kelas 13 karena user hanya mengampu MTK di kelas 13.');
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }

            try {
                $mockScope->authorizeSdMapelScope($user, 11, 999, 1, '5');
                $this->fail('Seharusnya menolak mapel yang tidak diampu di kelas ini.');
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }

            // RekapMapel mapel yang tidak diampu di kelasnya harus ditolak 403
            $untaughtRekap = RekapMapel::make([
                'akademik_master_tahun_ajaran_id' => 1,
                'akademik_pelajaran_mata_pelajaran_id' => 999,
                'anggota_kelas_id' => 301,
            ]);
            $untaughtRekap->setRelation('anggotaKelas', $f['anggota']);
            try {
                $mockScope->authorizeRekapMapelForUser($user, $untaughtRekap);
                $this->fail('Seharusnya menolak deskripsi pada mapel yang tidak diampu.');
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }

            // Kelas yang tidak memiliki jadwal ajar user harus ditolak 403
            try {
                $mockScope->authorizeSdMapelScope($user, 99, 21, 1);
                $this->fail('Seharusnya menolak kelas yang tidak dijadwalkan untuk user.');
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }

            // Tahun ajaran tidak aktif pada authorizeSdMapelScope harus ditolak 403
            try {
                $mockScope->authorizeSdMapelScope($user, 13, 22, 999);
                $this->fail('Seharusnya menolak tahun ajaran di luar TA aktif.');
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }

            // Kelas SMP (non-SD) harus ditolak pada modul Penilaian TP SD
            try {
                $mockScope->authorizeSdClassScope($user, 12, 1);
                $this->fail('Seharusnya menolak kelas non-SD.');
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }

            // Kelas SD milik wali kelas lain harus ditolak pada scope kelas binaan saja
            try {
                $mockScope->authorizeSdClassScope($user, 99, 1);
                $this->fail('Seharusnya menolak kelas yang tidak diampu.');
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }

            // Tingkatan kelas berbeda dari kelas SD yang diampu harus ditolak
            try {
                $mockScope->authorizeSdClassScope($user, null, 1, '6');
                $this->fail('Seharusnya menolak tingkatan yang tidak diampu.');
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }

            // Tahun ajaran tidak aktif harus ditolak
            try {
                $mockScope->authorizeSdClassScope($user, 11, 999);
                $this->fail('Seharusnya menolak tahun ajaran di luar TA aktif.');
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }

            // RekapMapel siswa kelas lain harus ditolak 403
            $foreignAnggota = AnggotaKelas::make(['siswa_nis' => '202699', 'akademik_master_kelas_id' => 99]);
            $foreignRekap = RekapMapel::make(['akademik_master_tahun_ajaran_id' => 1, 'anggota_kelas_id' => 999]);
            $foreignRekap->setRelation('anggotaKelas', $foreignAnggota);
            try {
                $mockScope->authorizeRekapMapelForUser($user, $foreignRekap);
                $this->fail('Seharusnya menolak update deskripsi siswa kelas lain.');
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    public function test_penilaian_service_returns_non_sd_guard_and_delegates_scoped_sd_matrix(): void
    {
        $f = $this->makeSdPenilaianFixtures();
        $user = $this->makeUserWithRole('Ustadzah Sari', 'sari@primainsani.sch.id', 'NIPY-001', 'wali kelas', $f['wali']);

        // Guru hanya mengampu kelas SMP (tidak punya jadwal ajar mapel SD aktif)
        $smpOnlyScope = $this->createMock(WaliKelasScopeService::class);
        $smpOnlyScope->method('getTaughtSdScopeForUser')->willReturn([
            'isEligible' => false,
            'ineligibleReason' => 'not_assigned_active_year',
            'nipy' => 'NIPY-001',
            'karyawanProfile' => $f['wali'],
            'activeTahunAjaran' => $f['ta'],
            'taughtClasses' => new EloquentCollection,
            'taughtMapelsByClass' => collect(),
        ]);
        $smpOnlyScope->method('getAssignedClassesForUser')
            ->willReturn(new EloquentCollection([$f['kelasSmp']]));
        $smpOnlyScope->method('filterSdClasses')->willReturn(new EloquentCollection);

        $unusedAdminService = $this->createMock(PenilaianTpService::class);
        $unusedAdminService->expects($this->never())->method('getMatrixData');

        $smpService = new WaliKelasPenilaianService($smpOnlyScope, $unusedAdminService);
        $guardResult = $smpService->getPageData($user, []);
        $this->assertFalse($guardResult['isEligible']);
        $this->assertSame('non_sd_only', $guardResult['guardReason']);

        // Guru mengampu mapel di kelas binaan (11 -> PAI) sekaligus di kelas lain (13 -> MTK)
        $sdClasses = new EloquentCollection([$f['kelasSd'], $f['kelasSdLain']]);
        $taughtPai = new EloquentCollection([$f['mapel']]);
        $taughtMtk = new EloquentCollection([$f['mapelMtk']]);
        $sdScope = $this->createMock(WaliKelasScopeService::class);
        $sdScope->method('getTaughtSdScopeForUser')->willReturn([
            'isEligible' => true,
            'ineligibleReason' => null,
            'nipy' => 'NIPY-001',
            'karyawanProfile' => $f['wali'],
            'activeTahunAjaran' => $f['ta'],
            'taughtClasses' => $sdClasses,
            'taughtMapelsByClass' => collect([
                11 => $taughtPai,
                13 => $taughtMtk,
            ]),
        ]);
        $sdScope->method('resolveRoleInKelas')
            ->willReturnCallback(static fn (Kelas $kelas): string => $kelas->id === 11 ? 'Wali Kelas' : 'Guru Pengampu');

        $adminService = $this->createMock(PenilaianTpService::class);
        $adminService->expects($this->once())
            ->method('getMatrixData')
            ->with(['tahun_ajaran_id' => 1, 'kelas_id' => 11], $sdClasses, $taughtPai)
            ->willReturn(['selectedKelas' => $f['kelasSd']]);
        $adminService->expects($this->once())
            ->method('getRekapData')
            ->with(['tahun_ajaran_id' => 1, 'kelas_id' => 13], $sdClasses, $taughtMtk)
            ->willReturn(['selectedKelas' => $f['kelasSdLain']]);

        $sdService = new WaliKelasPenilaianService($sdScope, $adminService);
        $matrix = $sdService->getPageData($user, ['kelas_id' => 11], false);
        $this->assertTrue($matrix['isEligible']);
        $this->assertSame('wali-kelas.penilaian-tp', $matrix['tpRoutePrefix']);
        $this->assertSame('wali-kelas.penilaian-rekap', $matrix['rekapRoutePrefix']);
        $this->assertSame('Wali Kelas', $matrix['peranDiKelas']);

        $rekap = $sdService->getPageData($user, ['kelas_id' => 13], true);
        $this->assertTrue($rekap['isEligible']);
        $this->assertSame('Guru Pengampu', $rekap['peranDiKelas']);

        // Mengakses mata pelajaran yang tidak diampu di kelas tersebut harus ditolak 403
        try {
            $sdService->getPageData($user, ['kelas_id' => 13, 'mata_pelajaran_id' => 21], false);
            $this->fail('Seharusnya menolak mata_pelajaran_id PAI yang tidak diampu pada kelas 13.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        // Mengakses kelas SD yang tidak ada jadwal ajarnya harus ditolak 403
        try {
            $sdService->getPageData($user, ['kelas_id' => 99], false);
            $this->fail('Seharusnya menolak kelas_id yang tidak ada pada jadwal ajar user.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        // Wali kelas SD tanpa penugasan mapel di jadwal pelajaran menampilkan guard no_taught_mapel
        $homeroomSdOnly = new EloquentCollection([$f['kelasSd']]);
        $noMapelScope = $this->createMock(WaliKelasScopeService::class);
        $noMapelScope->method('getTaughtSdScopeForUser')->willReturn([
            'isEligible' => false,
            'ineligibleReason' => 'not_assigned_active_year',
            'nipy' => 'NIPY-001',
            'karyawanProfile' => $f['wali'],
            'activeTahunAjaran' => $f['ta'],
            'taughtClasses' => new EloquentCollection,
            'taughtMapelsByClass' => collect(),
        ]);
        $noMapelScope->method('getAssignedClassesForUser')->willReturn($homeroomSdOnly);
        $noMapelScope->method('filterSdClasses')->willReturn($homeroomSdOnly);

        $noMapelService = new WaliKelasPenilaianService($noMapelScope, $this->createMock(PenilaianTpService::class));
        $noMapelResult = $noMapelService->getPageData($user, []);
        $this->assertFalse($noMapelResult['isEligible']);
        $this->assertSame('no_taught_mapel', $noMapelResult['guardReason']);
    }

    public function test_controller_reuses_penilaian_views_without_admin_only_links_and_renders_sidebar(): void
    {
        $f = $this->makeSdPenilaianFixtures();
        $user = $this->makeUserWithRole('Ustadzah Sari', 'sari@primainsani.sch.id', 'NIPY-001', 'wali kelas', $f['wali']);
        Auth::shouldUse('web');
        Auth::setUser($user);

        $sharedPayload = [
            'isEligible' => true,
            'hasSdClasses' => true,
            'tpRoutePrefix' => 'wali-kelas.penilaian-tp',
            'rekapRoutePrefix' => 'wali-kelas.penilaian-rekap',
            'isWaliKelasScope' => true,
            'tahunAjaranOptions' => new EloquentCollection([$f['ta']]),
            'kelasOptions' => new EloquentCollection([$f['kelasSd']]),
            'mataPelajaranOptions' => new EloquentCollection([$f['mapel']]),
            'selectedTahunAjaranId' => 1,
            'selectedTahunAjaran' => $f['ta'],
            'selectedKelas' => $f['kelasSd'],
            'selectedMapel' => $f['mapel'],
            'periode' => PeriodeTp::PERIODE_MID,
            'periodeOptions' => PeriodeTp::PERIODE_OPTIONS,
            'teknikOptions' => AspekTp::TEKNIK_OPTIONS,
            'tpList' => new EloquentCollection([$f['tp']]),
            'periodeSettingsByTp' => collect([101 => PeriodeTp::make(['tujuan_pembelajaran_id' => 101, 'periode' => 'mid', 'masuk_rapor' => true])]),
            'aspeksByTp' => collect([101 => new EloquentCollection([$f['aspek']])]),
            'midSumatifBabs' => new EloquentCollection([$f['sumatifBab']]),
            'carriedMidSumatifBabs' => new EloquentCollection,
            'activeSumatifBabs' => new EloquentCollection([$f['sumatifBab']]),
            'sumatifNilaiLookup' => [],
            'students' => new EloquentCollection([$f['anggota']]),
            'nilaiLookup' => ['301:201' => $f['nilai']],
            'rekapTpLookup' => ['301:101:mid' => $f['rekapTp']],
            'rekapMapelLookup' => ['301:mid' => $f['rekapMapel']],
            'kkmNilai' => 75.0,
            'kkmPredikats' => new EloquentCollection([$f['predikatA']]),
            'bobotTp' => 50,
            'bobotSumatif' => 25,
            'bobotUjian' => 25,
            'ujianLabel' => 'PSTS',
            'ujianFullLabel' => 'PSTS (Mid Semester)',
            'hasKkmConfig' => true,
            'kkmModel' => $f['kkm'],
            'includedTpCount' => 1,
            'rekapSumatifCols' => new EloquentCollection([$f['sumatifBab']]),
            'combinedTpSummaries' => [
                301 => [101 => ['included' => true, 'nilai' => 88.0, 'predikat' => 'B', 'tuntas' => true, 'source' => 'Mid']],
            ],
            'combinedSumatifSummaries' => [
                301 => [401 => ['nilai' => 85.0, 'source' => 'Mid']],
            ],
        ];

        $waliService = $this->createMock(WaliKelasPenilaianService::class);
        $waliService->expects($this->exactly(2))
            ->method('getPageData')
            ->willReturnOnConsecutiveCalls(
                $sharedPayload,
                [
                    'isEligible' => false,
                    'guardReason' => 'non_sd_only',
                    'nipy' => 'NIPY-001',
                    'karyawanProfile' => $f['wali'],
                    'activeTahunAjaran' => $f['ta'],
                    'assignedClasses' => new EloquentCollection([$f['kelasSmp']]),
                    'selectedKelas' => $f['kelasSmp'],
                    'peranDiKelas' => 'Wali Kelas',
                ]
            );

        $controller = new PenilaianTpController($waliService);

        $indexRequest = Request::create('/wali-kelas/penilaian/nilai-tp', 'GET');
        $indexRequest->setUserResolver(static fn (): User => $user);

        $rekapGuardRequest = Request::create('/wali-kelas/penilaian/rekap', 'GET');
        $rekapGuardRequest->setUserResolver(static fn (): User => $user);

        $indexView = $controller->index($indexRequest);
        $guardView = $controller->rekap($rekapGuardRequest);

        $this->assertSame('admin.penilaian.nilaiTp.index', $indexView->name());
        $this->assertSame('waliKelas.penilaian.ineligible', $guardView->name());

        Model::preventLazyLoading(true);
        try {
            $matrixHtml = $indexView->with('errors', new ViewErrorBag)->render();
            $rekapHtml = view('admin.penilaian.rekap.index', $sharedPayload)->render();
            $guardHtml = $guardView->render();
            $waliSidebarHtml = view('layouts.sidebar')->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        // Reuse view Nilai TP dengan seluruh action menunjuk route wali-kelas.*, bukan route admin/kkm/tp-create
        $this->assertStringContainsString('Mapel Diampu', $matrixHtml);
        $this->assertStringContainsString(route('wali-kelas.perkembangan-siswa.index', ['periode' => PeriodeTp::PERIODE_MID]), $matrixHtml);
        $this->assertStringNotContainsString(route('wali-kelas.perkembangan-siswa.index', ['kelas_id' => 11, 'periode' => PeriodeTp::PERIODE_MID]), $matrixHtml);
        $this->assertStringContainsString(route('wali-kelas.penilaian-tp.store'), $matrixHtml);
        $this->assertStringContainsString(route('wali-kelas.penilaian-tp.bobot.update'), $matrixHtml);
        $this->assertStringContainsString(route('wali-kelas.penilaian-tp.aspek.update', 201), $matrixHtml);
        $this->assertStringContainsString(route('wali-kelas.penilaian-tp.sumatif-bab.update', 401), $matrixHtml);
        $this->assertStringContainsString(route('wali-kelas.penilaian-tp.import'), $matrixHtml);
        $this->assertStringContainsString(route('wali-kelas.penilaian-rekap.index'), $matrixHtml);
        $this->assertStringNotContainsString(route('penilaian-tp.store'), $matrixHtml);
        $this->assertStringNotContainsString(route('penilaian-kkm.edit', 31), $matrixHtml);

        // Reuse view Rekap dengan route wali-kelas.*
        $this->assertStringContainsString('Mapel Diampu', $rekapHtml);
        $this->assertStringContainsString(route('wali-kelas.perkembangan-siswa.index', ['periode' => PeriodeTp::PERIODE_MID]), $rekapHtml);
        $this->assertStringContainsString(route('wali-kelas.penilaian-tp.index'), $rekapHtml);
        $this->assertStringContainsString(route('wali-kelas.penilaian-rekap.deskripsi.update', 501), $rekapHtml);
        $this->assertStringContainsString(route('wali-kelas.penilaian-rekap.deskripsi.regenerate'), $rekapHtml);
        $this->assertStringNotContainsString(route('penilaian-rekap.deskripsi.regenerate'), $rekapHtml);

        // Guard non-SD
        $this->assertStringContainsString('Modul Penilaian TP Khusus Jenjang SD', $guardHtml);
        $this->assertStringContainsString('7 Khadijah (SMP)', $guardHtml);

        // Sidebar role wali kelas menampilkan dropdown Penilaian (SD)
        $this->assertStringContainsString('submenu-wali-penilaian', $waliSidebarHtml);
        $this->assertStringContainsString(route('wali-kelas.penilaian-tp.index'), $waliSidebarHtml);
        $this->assertStringContainsString(route('wali-kelas.penilaian-rekap.index'), $waliSidebarHtml);
    }
}
