<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\Penilaian\FinalisasiController;
use App\Http\Controllers\Admin\Penilaian\RaporController as AdminRaporController;
use App\Http\Controllers\WaliKelas\RaporController as WaliKelasRaporController;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Admin\Penilaian\PenilaianFinalisasi;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use App\Models\User;
use App\Services\Admin\Penilaian\PenilaianFinalisasiService;
use App\Services\Admin\Penilaian\PenilaianTpService;
use App\Services\Admin\Penilaian\RaporPdfService;
use App\Services\WaliKelas\WaliKelasScopeService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PenilaianFinalisasiModelAndViewTest extends TestCase
{
    private function makeUserWithRole(string $name, string $email, ?string $noInduk, string $roleName): User
    {
        $role = new Role;
        $role->setRawAttributes(['name' => $roleName, 'guard_name' => 'web']);
        $role->setRelation('permissions', new EloquentCollection);

        $user = new User;
        $user->setRawAttributes([
            'id' => 10,
            'name' => $name,
            'email' => $email,
            'no_induk' => $noInduk,
        ]);
        $user->setRelation('roles', new EloquentCollection([$role]));
        $user->setRelation('permissions', new EloquentCollection);

        return $user;
    }

    public function test_scope_key_generation_is_deterministic_and_separates_mid_vs_semester(): void
    {
        $midKey = PenilaianFinalisasi::buildScopeKey(1, PenilaianFinalisasi::JENIS_MAPEL, 10, 5, null, 'mid');
        $semKey = PenilaianFinalisasi::buildScopeKey(1, PenilaianFinalisasi::JENIS_MAPEL, 10, 5, null, 'semester');
        $ekskulKey = PenilaianFinalisasi::buildScopeKey(1, PenilaianFinalisasi::JENIS_EKSKUL_PILIHAN, null, null, 8, 'MID');

        $this->assertSame('ta:1:mapel:k:10:m:5:e:0:p:mid', $midKey);
        $this->assertSame('ta:1:mapel:k:10:m:5:e:0:p:semester', $semKey);
        $this->assertNotSame($midKey, $semKey);
        $this->assertSame('ta:1:ekskul_pilihan:k:0:m:0:e:8:p:mid', $ekskulKey);

        $rec = PenilaianFinalisasi::make(['status' => PenilaianFinalisasi::STATUS_FINAL]);
        $this->assertTrue($rec->isFinal());

        $draft = PenilaianFinalisasi::make(['status' => PenilaianFinalisasi::STATUS_DRAFT]);
        $this->assertFalse($draft->isFinal());
    }

    public function test_rapor_pdf_service_blocks_stream_when_class_is_not_ready(): void
    {
        $ta = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);
        $ta->id = 1;

        $kelas = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'nama_kelas' => '5 Al-Fatihah',
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => '5',
        ]);
        $kelas->id = 10;
        $kelas->setRelation('tahunAjaran', $ta);

        $mockFinalService = $this->createMock(PenilaianFinalisasiService::class);
        $mockFinalService->expects($this->once())
            ->method('assertClassReadyForPrint')
            ->with($kelas, PeriodeTp::PERIODE_MID)
            ->willThrowException(new HttpException(422, 'Cetak Nilai Rapor diblokir: masih ada komponen belum final.'));

        $pdfService = new RaporPdfService(null, null, null, null, $mockFinalService);

        try {
            $pdfService->streamNilaiPdf($kelas, PeriodeTp::PERIODE_MID);
            $this->fail('Seharusnya melempar HttpException 422 saat komponen belum final.');
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
            $this->assertStringContainsString('diblokir', $e->getMessage());
        }
    }

    public function test_penilaian_tp_service_blocks_grid_and_structure_edits_when_finalized(): void
    {
        $kelas = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'tingkatan_kelas' => '5',
            'nama_kelas' => '5 Al-Fatihah',
            'jenjang_kelas' => 'SD',
        ]);
        $kelas->id = 10;

        $mapel = MataPelajaran::make([
            'singkatan' => 'PAI',
            'nama_mata_pelajaran' => 'Pendidikan Agama Islam',
            'unit' => 'SD',
        ]);
        $mapel->id = 5;

        $tp = TujuanPembelajaran::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'akademik_pelajaran_mata_pelajaran_id' => 5,
            'tingkatan_kelas' => '5',
            'kode_tp' => '5.1',
        ]);
        $tp->id = 20;

        $finalMock = $this->createMock(PenilaianFinalisasiService::class);
        $finalMock->expects($this->once())
            ->method('assertMapelGridEditable')
            ->with($kelas, $mapel, PeriodeTp::PERIODE_MID)
            ->willThrowException(ValidationException::withMessages(['finalisasi' => 'Terkunci']));
        $finalMock->expects($this->once())
            ->method('assertMapelStructureEditable')
            ->with(1, 5, '5', PeriodeTp::PERIODE_MID)
            ->willThrowException(ValidationException::withMessages(['finalisasi' => 'Struktur terkunci']));

        $tpService = new PenilaianTpService($finalMock);

        try {
            $tpService->saveNilaiGrid($kelas, $mapel, PeriodeTp::PERIODE_MID, [1 => [2 => 90]]);
            $this->fail('Seharusnya memblokir saveNilaiGrid saat mapel final.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('finalisasi', $e->errors());
        }

        try {
            $tpService->saveAspek($tp, ['periode' => 'mid', 'nama_aspek' => 'Quiz']);
            $this->fail('Seharusnya memblokir saveAspek saat struktur mapel final.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('finalisasi', $e->errors());
        }
    }

    public function test_finalisasi_banner_and_readiness_views_render_cleanly_in_all_states_without_lazy_loading(): void
    {
        $ta = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);
        $ta->id = 1;

        $wali = Karyawan::make(['nipy' => 'NIPY-001', 'nama_lengkap' => 'Sari Wulandari', 'gelar' => 'S.Pd']);
        $kelas = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'karyawan_nipy' => 'NIPY-001',
            'tingkatan_kelas' => '5',
            'nama_kelas' => '5 Al-Fatihah',
            'jenjang_kelas' => 'SD',
        ]);
        $kelas->id = 10;
        $kelas->setRelation('tahunAjaran', $ta);
        $kelas->setRelation('waliKelas', $wali);
        $kelas->setRelation('pendamping', null);

        $mapel = MataPelajaran::make(['singkatan' => 'PAI', 'nama_mata_pelajaran' => 'Pendidikan Agama Islam', 'unit' => 'SD']);
        $mapel->id = 5;

        $siswa = Siswa::make(['nis' => '202601', 'nama_lengkap' => 'Nayla Azzahra', 'nisn' => '012345', 'jenis_kelamin' => 'P', 'status' => 'Aktif']);
        $anggota = AnggotaKelas::make(['siswa_nis' => '202601', 'akademik_master_kelas_id' => 10, 'pendaftaran' => 'Naik Kelas']);
        $anggota->id = 301;
        $anggota->setRelation('siswa', $siswa);
        $anggota->setRelation('kelas', $kelas);

        $waliUser = $this->makeUserWithRole('Sari', 'sari@test.local', 'NIPY-001', 'wali kelas');
        $waliUser->setRelation('karyawan', $wali);
        Auth::shouldUse('web');
        Auth::setUser($waliUser);

        $draftBanner = [
            'jenis_komponen' => PenilaianFinalisasi::JENIS_MAPEL,
            'jenis_label' => 'Mata Pelajaran',
            'tahun_ajaran_id' => 1,
            'kelas_id' => 10,
            'mata_pelajaran_id' => 5,
            'ekstrakurikuler_id' => null,
            'periode' => PeriodeTp::PERIODE_MID,
            'periode_label' => 'Mid Semester',
            'status' => PenilaianFinalisasi::STATUS_DRAFT,
            'is_final' => false,
            'record' => null,
            'finalized_by_name' => null,
            'finalized_at' => null,
            'unlocked_by_name' => 'Admin Akademik',
            'unlocked_at' => Carbon::now(),
            'unlock_reason' => 'Revisi nilai praktik',
            'can_finalize' => true,
            'can_unlock' => false,
        ];

        $finalBanner = [
            ...$draftBanner,
            'status' => PenilaianFinalisasi::STATUS_FINAL,
            'is_final' => true,
            'finalized_by_name' => 'Budi Santoso, S.Ag',
            'finalized_at' => Carbon::now(),
            'can_finalize' => false,
            'can_unlock' => true,
        ];

        $readinessBlocked = [
            'periode' => PeriodeTp::PERIODE_MID,
            'periode_label' => 'Mid Semester',
            'is_sd' => true,
            'is_ready' => false,
            'total_components' => 3,
            'finalized_count' => 1,
            'pending_count' => 2,
            'progress_pct' => 33,
            'final_mapel_ids' => [5],
            'groups' => [],
            'pending_components' => [],
            'components' => [
                [
                    'jenis_komponen' => PenilaianFinalisasi::JENIS_MAPEL,
                    'jenis_label' => 'Mata Pelajaran',
                    'nama' => '[PAI] Pendidikan Agama Islam',
                    'owner_label' => 'Budi Santoso, S.Ag',
                    'is_owned_by_user' => false,
                    'is_final' => true,
                    'finalized_by_name' => 'Budi Santoso, S.Ag',
                    'tahun_ajaran_id' => 1,
                    'kelas_id' => 10,
                    'mata_pelajaran_id' => 5,
                    'ekstrakurikuler_id' => null,
                    'periode' => PeriodeTp::PERIODE_MID,
                    'is_placeholder' => false,
                ],
                [
                    'jenis_komponen' => PenilaianFinalisasi::JENIS_EKSKUL_WAJIB,
                    'jenis_label' => 'Ekskul Wajib',
                    'nama' => 'Pramuka (Wajib)',
                    'owner_label' => 'Sari Wulandari, S.Pd',
                    'is_owned_by_user' => true,
                    'is_final' => false,
                    'finalized_by_name' => null,
                    'tahun_ajaran_id' => 1,
                    'kelas_id' => 10,
                    'mata_pelajaran_id' => null,
                    'ekstrakurikuler_id' => null,
                    'periode' => PeriodeTp::PERIODE_MID,
                    'is_placeholder' => false,
                ],
                [
                    'jenis_komponen' => PenilaianFinalisasi::JENIS_PRESENSI,
                    'jenis_label' => 'Presensi',
                    'nama' => 'Ketidakhadiran & Presensi Siswa',
                    'owner_label' => 'Sari Wulandari, S.Pd',
                    'is_owned_by_user' => true,
                    'is_final' => false,
                    'finalized_by_name' => null,
                    'tahun_ajaran_id' => 1,
                    'kelas_id' => 10,
                    'mata_pelajaran_id' => null,
                    'ekstrakurikuler_id' => null,
                    'periode' => PeriodeTp::PERIODE_MID,
                    'is_placeholder' => false,
                ],
            ],
        ];

        $readinessReady = [
            ...$readinessBlocked,
            'is_ready' => true,
            'finalized_count' => 3,
            'pending_count' => 0,
            'progress_pct' => 100,
        ];

        Model::preventLazyLoading(true);
        try {
            $draftHtml = view('admin.penilaian.partials._finalisasi_banner', [
                'finalisasi' => $draftBanner,
                'routePrefix' => 'wali-kelas.penilaian-finalisasi',
                'componentTitle' => '[PAI] Pendidikan Agama Islam',
            ])->render();

            $finalHtml = view('admin.penilaian.partials._finalisasi_banner', [
                'finalisasi' => $finalBanner,
                'routePrefix' => 'wali-kelas.penilaian-finalisasi',
                'componentTitle' => '[PAI] Pendidikan Agama Islam',
            ])->render();

            $panelBlockedHtml = view('admin.penilaian.partials._rapor_readiness_panel', [
                'readiness' => $readinessBlocked,
                'routePrefix' => 'wali-kelas.penilaian-finalisasi',
                'periode' => PeriodeTp::PERIODE_MID,
            ])->render();

            $raporBlockedHtml = view('admin.penilaian.rapor.index', [
                'tahunAjaranOptions' => new EloquentCollection([$ta]),
                'kelasOptions' => new EloquentCollection([$kelas]),
                'selectedTahunAjaranId' => 1,
                'selectedTahunAjaran' => $ta,
                'selectedKelas' => $kelas,
                'periode' => PeriodeTp::PERIODE_MID,
                'periodeOptions' => PeriodeTp::PERIODE_OPTIONS,
                'isSd' => true,
                'sekolah' => null,
                'titimangsa' => null,
                'students' => new EloquentCollection([$anggota]),
                'rekapCountByAnggota' => [301 => 1],
                'ekskulCountByAnggota' => [301 => 1],
                't2qCountByAnggota' => [301 => 1],
                'presensiByAnggota' => [301 => ['sakit' => 0, 'izin' => 0, 'alpa' => 0]],
                'totalSdMapels' => 1,
                'readiness' => $readinessBlocked,
            ])->render();

            $raporReadyHtml = view('admin.penilaian.rapor.index', [
                'tahunAjaranOptions' => new EloquentCollection([$ta]),
                'kelasOptions' => new EloquentCollection([$kelas]),
                'selectedTahunAjaranId' => 1,
                'selectedTahunAjaran' => $ta,
                'selectedKelas' => $kelas,
                'periode' => PeriodeTp::PERIODE_MID,
                'periodeOptions' => PeriodeTp::PERIODE_OPTIONS,
                'isSd' => true,
                'sekolah' => null,
                'titimangsa' => null,
                'students' => new EloquentCollection([$anggota]),
                'rekapCountByAnggota' => [301 => 1],
                'ekskulCountByAnggota' => [301 => 1],
                't2qCountByAnggota' => [301 => 1],
                'presensiByAnggota' => [301 => ['sakit' => 0, 'izin' => 0, 'alpa' => 0]],
                'totalSdMapels' => 1,
                'readiness' => $readinessReady,
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('DRAFT (BELUM FINAL)', $draftHtml);
        $this->assertStringContainsString('Revisi nilai praktik', $draftHtml);
        $this->assertStringContainsString('Finalkan Mid Semester', $draftHtml);

        $this->assertStringContainsString('FINAL &amp; TERKUNCI', $finalHtml);
        $this->assertStringContainsString('Budi Santoso, S.Ag', $finalHtml);
        $this->assertStringContainsString('Buka Kunci', $finalHtml);

        // Di checklist Wali Kelas: PAI milik guru lain bersifat read-only, Pramuka & Presensi miliknya punya tombol Finalkan
        $this->assertStringContainsString('1/3 Komponen Final (33%)', $panelBlockedHtml);
        $this->assertStringContainsString('Pramuka (Wajib)', $panelBlockedHtml);
        $this->assertStringContainsString('Finalkan', $panelBlockedHtml);

        // Rapor Admin: terblokir saat belum siap, muncul link cetak saat 100% siap, dan script modal ter-render ke @stack('js')
        $this->assertStringContainsString('Cetak Nilai Rapor (2 Belum Final)', $raporBlockedHtml);
        $this->assertStringContainsString('window.__fnFinalisasiInit', $raporBlockedHtml);
        $this->assertStringContainsString('document.body.appendChild(el)', $raporBlockedHtml);
        $this->assertStringContainsString("closest('.js-toggle-readiness-table')", $raporBlockedHtml);
        $this->assertStringNotContainsString(route('penilaian-rapor.nilai.kelas', ['kelas' => $kelas, 'periode' => 'mid']), $raporBlockedHtml);
        $this->assertStringContainsString(e(route('penilaian-rapor.nilai.kelas', ['kelas' => $kelas, 'periode' => 'mid'])), $raporReadyHtml);
    }

    public function test_perkembangan_and_finalisasi_controller_render_correctly(): void
    {
        $ta = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);
        $ta->id = 1;
        $wali = Karyawan::make(['nipy' => 'NIPY-001', 'nama_lengkap' => 'Sari Wulandari', 'gelar' => 'S.Pd']);
        $kelas = Kelas::make(['akademik_master_tahun_ajaran_id' => 1, 'karyawan_nipy' => 'NIPY-001', 'tingkatan_kelas' => '5', 'nama_kelas' => '5A', 'jenjang_kelas' => 'SD']);
        $kelas->id = 10;
        $kelas->setRelation('tahunAjaran', $ta);
        $kelas->setRelation('waliKelas', $wali);
        $kelas->setRelation('pendamping', null);
        $mapel = MataPelajaran::make(['singkatan' => 'MTK', 'nama_mata_pelajaran' => 'Matematika', 'unit' => 'SD']);
        $mapel->id = 21;
        $siswa = Siswa::make(['nis' => '1', 'nama_lengkap' => 'Nayla Azzahra', 'jenis_kelamin' => 'P', 'status' => 'Aktif']);
        $anggota = AnggotaKelas::make(['siswa_nis' => '1', 'akademik_master_kelas_id' => 10, 'pendaftaran' => 'Naik Kelas']);
        $anggota->id = 301;
        $anggota->setRelation('siswa', $siswa);
        $anggota->setRelation('kelas', $kelas);
        $waliUser = $this->makeUserWithRole('Sari', 'sari@test.local', 'NIPY-001', 'wali kelas');
        $waliUser->setRelation('karyawan', $wali);
        Auth::shouldUse('web');
        Auth::setUser($waliUser);

        $mockService = $this->createMock(PenilaianFinalisasiService::class);
        $controller = new FinalisasiController($mockService);
        $this->assertInstanceOf(FinalisasiController::class, $controller);

        $readiness = [
            'periode' => PeriodeTp::PERIODE_MID,
            'periode_label' => 'Mid Semester',
            'is_sd' => true,
            'is_ready' => false,
            'total_components' => 1,
            'finalized_count' => 0,
            'pending_count' => 1,
            'progress_pct' => 0,
            'components' => [],
            'pending_components' => [],
        ];

        Model::preventLazyLoading(true);
        try {
            $wkHtml = view('waliKelas.perkembanganSiswa.index', [
                'isEligible' => true,
                'peranDiKelas' => 'Wali Kelas',
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
                    'teachers' => ['Guru Lain, S.Pd'],
                    'is_taught_by_user' => false,
                    'dinilai_count' => 1,
                    'tuntas_count' => 1,
                    'belum_tuntas_count' => 0,
                    'avg_rapor' => 88.0,
                    'is_final' => true,
                    'finalized_by_name' => 'Guru Lain, S.Pd',
                    'is_required_for_rapor' => true,
                ]]),
                'studentRows' => collect(),
                'overviewStats' => [
                    'avg_rapor' => 88.0,
                    'tuntas_rate' => 100,
                    'scored_entries' => 1,
                    'tuntas_entries' => 1,
                    'need_attention_students' => 0,
                    'avg_kehadiran' => 100,
                ],
                'readiness' => $readiness,
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('Nilai Rapor (1 Belum Final)', $wkHtml);
        $this->assertStringContainsString('js-blocked-print-trigger', $wkHtml);
        $this->assertStringContainsString('window.toastr.error', $wkHtml);
        $this->assertStringContainsString('id="wkTabPanelLeger"', $wkHtml);
        $this->assertStringContainsString('id="wkTabPanelMapel"', $wkHtml);
        $this->assertStringContainsString('Ringkasan Mapel', $wkHtml);
        $this->assertStringContainsString('data-wk-tab="mapel"', $wkHtml);
        $this->assertStringContainsString('id="wkTabPanelKesiapan"', $wkHtml);
        $this->assertStringContainsString('Kesiapan &amp; Finalisasi Rapor', $wkHtml);
        $this->assertStringContainsString('Final', $wkHtml);

        // Controller browser GET saat rapor belum final -> redirect dengan flash session 'error' (toastr), bukan debug exception
        $pdfBlockedMock = $this->createMock(RaporPdfService::class);
        $pdfBlockedMock->method('streamNilaiPdf')
            ->willThrowException(new HttpException(422, 'Cetak Nilai Rapor diblokir: masih ada 1 komponen belum final.'));

        $scopeMock = $this->createMock(WaliKelasScopeService::class);
        $scopeMock->method('resolveScopeContext')->willReturn([
            'isEligible' => true,
            'ineligibleReason' => null,
            'nipy' => 'NIPY-001',
            'karyawanProfile' => $wali,
            'activeTahunAjaran' => $ta,
            'assignedClasses' => new EloquentCollection([$kelas]),
            'selectedKelas' => $kelas,
            'peranDiKelas' => 'Wali Kelas',
        ]);
        $scopeMock->method('authorizeKelasIdForUser')->willReturn([
            'isEligible' => true,
            'ineligibleReason' => null,
            'nipy' => 'NIPY-001',
            'karyawanProfile' => $wali,
            'activeTahunAjaran' => $ta,
            'assignedClasses' => new EloquentCollection([$kelas]),
            'selectedKelas' => $kelas,
            'peranDiKelas' => 'Wali Kelas',
        ]);

        $wkController = new WaliKelasRaporController($pdfBlockedMock, $scopeMock);
        $req = Request::create('/wali-kelas/rapor/nilai/kelas/10?periode=mid', 'GET');
        $req->setUserResolver(static fn (): User => $waliUser);
        $wkResp = $wkController->nilaiKelas($req, $kelas);
        $this->assertInstanceOf(RedirectResponse::class, $wkResp);
        $this->assertStringContainsString('diblokir', (string) $wkResp->getSession()->get('error'));

        $adminController = new AdminRaporController($pdfBlockedMock);
        $adminResp = $adminController->nilaiKelas(Request::create('/penilaian/rapor/nilai/kelas/10?periode=mid', 'GET'), $kelas);
        $this->assertInstanceOf(RedirectResponse::class, $adminResp);
        $this->assertStringContainsString('diblokir', (string) $adminResp->getSession()->get('error'));
    }
}
