<?php

namespace Tests\Unit;

use App\Http\Controllers\WaliKelas\PerkembanganSiswaController;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Models\Admin\Penilaian\RekapMapel;
use App\Models\Admin\Penilaian\RekapTp;
use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use App\Models\User;
use App\Services\WaliKelas\WaliKelasPerkembanganService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WaliKelasPerkembanganTest extends TestCase
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

    public function test_controller_renders_holistic_class_ledger_student_detail_and_sidebar_without_lazy_loading(): void
    {
        $ta = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);
        $ta->id = 1;

        $wali = Karyawan::make(['nipy' => 'NIPY-001', 'nama_lengkap' => 'Sari Wulandari', 'gelar' => 'S.Pd']);
        $pendamping = Karyawan::make(['nipy' => 'NIPY-002', 'nama_lengkap' => 'Dewi Lestari', 'gelar' => 'S.Pd']);

        $kelas = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'karyawan_nipy' => 'NIPY-001',
            'pendamping_nipy' => 'NIPY-002',
            'tingkatan_kelas' => '5',
            'nama_kelas' => '5 Al-Fatihah',
            'jenjang_kelas' => 'SD',
        ]);
        $kelas->id = 11;
        $kelas->setRelation('tahunAjaran', $ta);
        $kelas->setRelation('waliKelas', $wali);
        $kelas->setRelation('pendamping', $pendamping);

        $mapelA = MataPelajaran::make(['singkatan' => 'MTK', 'nama_mata_pelajaran' => 'Matematika', 'unit' => 'SD']);
        $mapelA->id = 21;
        $mapelB = MataPelajaran::make(['singkatan' => 'PAI', 'nama_mata_pelajaran' => 'Pendidikan Agama Islam', 'unit' => 'SD']);
        $mapelB->id = 22;

        $siswa = Siswa::make([
            'nis' => '202601001',
            'nama_lengkap' => 'Ahmad Fauzi',
            'nisn' => '0123456789',
            'jenis_kelamin' => 'L',
            'status' => 'Aktif',
        ]);
        $anggota = AnggotaKelas::make(['siswa_nis' => '202601001', 'akademik_master_kelas_id' => 11, 'pendaftaran' => 'Naik Kelas']);
        $anggota->id = 301;
        $anggota->setRelation('siswa', $siswa);
        $anggota->setRelation('kelas', $kelas);

        $rekapMidMtk = RekapMapel::make([
            'rata_rata_tp' => 86,
            'rata_rata_sumatif' => 84,
            'nilai_ujian' => 88,
            'nilai_akhir_rapor' => 86,
            'predikat_rapor' => 'B',
            'tuntas_kkm' => true,
            'deskripsi_capaian' => 'Menguasai pecahan dan bilangan bulat dengan sangat baik.',
        ]);
        $rekapMidPai = RekapMapel::make([
            'rata_rata_tp' => 70,
            'nilai_akhir_rapor' => 70,
            'predikat_rapor' => 'D',
            'tuntas_kkm' => false,
            'deskripsi_capaian' => 'Perlu pendampingan tambahan pada hukum bacaan mad.',
        ]);

        $tpMtk = TujuanPembelajaran::make([
            'kode_tp' => 'TP 5.1',
            'tujuan_pembelajaran' => 'Operasi hitung pecahan campuran',
            'lingkup_materi' => 'Bilangan',
        ]);
        $rekapTpMid = RekapTp::make(['nilai_akhir_tp' => 86, 'tuntas_kkm' => true]);

        $user = $this->makeUserWithRole('Ustadzah Sari', 'sari@primainsani.sch.id', 'NIPY-001', 'wali kelas', $wali);
        Auth::shouldUse('web');
        Auth::setUser($user);

        $presensi = ['total' => 20, 'hadir' => 18, 'terlambat' => 1, 'izin' => 1, 'sakit' => 0, 'alpa' => 0, 'hadir_rate' => 95.0];

        $service = $this->createMock(WaliKelasPerkembanganService::class);
        $service->expects($this->once())
            ->method('getIndexData')
            ->with($user, 11, PeriodeTp::PERIODE_MID)
            ->willReturn([
                'isEligible' => true,
                'ineligibleReason' => null,
                'nipy' => 'NIPY-001',
                'karyawanProfile' => $wali,
                'activeTahunAjaran' => $ta,
                'assignedClasses' => new EloquentCollection([$kelas]),
                'selectedKelas' => $kelas,
                'peranDiKelas' => 'Wali Kelas',
                'periode' => PeriodeTp::PERIODE_MID,
                'isSd' => true,
                'students' => new EloquentCollection([$anggota]),
                'studentStats' => ['total' => 1, 'laki' => 1, 'perempuan' => 0, 'aktif' => 1, 'by_pendaftaran' => ['Naik Kelas' => 1]],
                'mapels' => new EloquentCollection([$mapelA, $mapelB]),
                'mapelSummaries' => collect([
                    [
                        'mapel' => $mapelA,
                        'kkm' => 75.0,
                        'teachers' => ['Sari Wulandari, S.Pd'],
                        'is_taught_by_user' => true,
                        'dinilai_count' => 1,
                        'tuntas_count' => 1,
                        'belum_tuntas_count' => 0,
                        'avg_rapor' => 86.0,
                    ],
                    [
                        'mapel' => $mapelB,
                        'kkm' => 75.0,
                        'teachers' => ['Ustadz Ahmad'],
                        'is_taught_by_user' => false,
                        'dinilai_count' => 1,
                        'tuntas_count' => 0,
                        'belum_tuntas_count' => 1,
                        'avg_rapor' => 70.0,
                    ],
                ]),
                'studentRows' => collect([
                    [
                        'anggota' => $anggota,
                        'presensi' => $presensi,
                        'mapel_cells' => [
                            21 => ['nilai' => 86.0, 'predikat' => 'B', 'tuntas' => true],
                            22 => ['nilai' => 70.0, 'predikat' => 'D', 'tuntas' => false],
                        ],
                        'dinilai_count' => 2,
                        'tuntas_count' => 1,
                        'belum_tuntas_count' => 1,
                        'rata_rapor' => 78.0,
                    ],
                ]),
                'overviewStats' => [
                    'avg_rapor' => 78.0,
                    'tuntas_rate' => 50.0,
                    'scored_entries' => 2,
                    'tuntas_entries' => 1,
                    'need_attention_students' => 1,
                    'avg_kehadiran' => 95.0,
                ],
            ]);

        $service->expects($this->once())
            ->method('getStudentDetailData')
            ->with($user, $anggota, PeriodeTp::PERIODE_MID)
            ->willReturn([
                'isEligible' => true,
                'ineligibleReason' => null,
                'nipy' => 'NIPY-001',
                'karyawanProfile' => $wali,
                'activeTahunAjaran' => $ta,
                'assignedClasses' => new EloquentCollection([$kelas]),
                'selectedKelas' => $kelas,
                'peranDiKelas' => 'Wali Kelas',
                'periode' => PeriodeTp::PERIODE_MID,
                'isSd' => true,
                'anggotaKelas' => $anggota,
                'siswa' => $siswa,
                'presensiStats' => $presensi,
                'subjectDetails' => collect([
                    [
                        'mapel' => $mapelA,
                        'kkm' => 75.0,
                        'teachers' => ['Sari Wulandari, S.Pd'],
                        'is_taught_by_user' => true,
                        'rekap_mid' => $rekapMidMtk,
                        'rekap_semester' => null,
                        'selected_rekap' => $rekapMidMtk,
                        'tp_items' => collect([['tp' => $tpMtk, 'mid' => $rekapTpMid, 'semester' => null]]),
                    ],
                    [
                        'mapel' => $mapelB,
                        'kkm' => 75.0,
                        'teachers' => ['Ustadz Ahmad'],
                        'is_taught_by_user' => false,
                        'rekap_mid' => $rekapMidPai,
                        'rekap_semester' => null,
                        'selected_rekap' => $rekapMidPai,
                        'tp_items' => collect(),
                    ],
                ]),
                'summary' => [
                    'total_mapel' => 2,
                    'dinilai_count' => 2,
                    'tuntas_count' => 1,
                    'belum_tuntas_count' => 1,
                    'rata_rapor' => 78.0,
                ],
            ]);

        $controller = new PerkembanganSiswaController($service);
        $indexRequest = Request::create('/wali-kelas/perkembangan-siswa?kelas_id=11&periode=mid', 'GET');
        $indexRequest->setUserResolver(static fn (): User => $user);

        $showRequest = Request::create('/wali-kelas/perkembangan-siswa/301?periode=mid', 'GET');
        $showRequest->setUserResolver(static fn (): User => $user);

        $indexView = $controller->index($indexRequest);
        $showView = $controller->show($anggota, $showRequest);

        $this->assertSame('waliKelas.perkembanganSiswa.index', $indexView->name());
        $this->assertSame('waliKelas.perkembanganSiswa.show', $showView->name());

        Model::preventLazyLoading(true);
        try {
            $indexHtml = $indexView->render();
            $showHtml = $showView->render();
            $waliSidebarHtml = view('layouts.sidebar')->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        // Leger menampilkan seluruh mapel (termasuk yang diampu Ustadz Ahmad) + link detail siswa
        $this->assertStringContainsString('Perkembangan Siswa Kelas 5 Al-Fatihah', $indexHtml);
        $this->assertStringContainsString('Matematika', $indexHtml);
        $this->assertStringContainsString('Pendidikan Agama Islam', $indexHtml);
        $this->assertStringContainsString('Ustadz Ahmad', $indexHtml);
        $this->assertStringContainsString('Mapel Anda', $indexHtml);
        $this->assertStringContainsString('wk-mapel-grid', $indexHtml);
        $this->assertStringContainsString('id="wkTabPanelMapel"', $indexHtml);
        $this->assertStringContainsString('data-wk-tab="mapel"', $indexHtml);
        $this->assertStringContainsString('Ringkasan Mapel', $indexHtml);
        $this->assertStringContainsString('id="wkPerkembanganTable"', $indexHtml);
        $this->assertStringContainsString(route('wali-kelas.perkembangan-siswa.show', ['anggota_kelas' => sqidFor('wali-kelas.perkembangan-siswa.show', 301), 'periode' => 'mid']), $indexHtml);
        $this->assertStringContainsString('fa-ellipsis-v', $indexHtml);
        $this->assertStringContainsString('action-dropdown-item', $indexHtml);
        $this->assertStringContainsString('Detail Perkembangan', $indexHtml);
        $this->assertStringContainsString('Cetak Identitas (PDF)', $indexHtml);
        $this->assertStringContainsString('Cetak Nilai Rapor (PDF)', $indexHtml);
        $this->assertStringContainsString(route('wali-kelas.rapor.identitas.siswa', sqidFor('wali-kelas.rapor.identitas.siswa', 301)), $indexHtml);
        $this->assertStringContainsString(route('wali-kelas.rapor.nilai.siswa', ['anggota_kelas' => sqidFor('wali-kelas.rapor.nilai.siswa', 301), 'periode' => 'mid']), $indexHtml);

        // Detail menampilkan progres seluruh mapel + deskripsi + TP
        $this->assertStringContainsString('Ahmad Fauzi', $showHtml);
        $this->assertStringContainsString('Menguasai pecahan dan bilangan bulat dengan sangat baik.', $showHtml);
        $this->assertStringContainsString('Perlu pendampingan tambahan pada hukum bacaan mad.', $showHtml);
        $this->assertStringContainsString('Operasi hitung pecahan campuran', $showHtml);

        // Sidebar role wali kelas menampilkan menu Perkembangan Siswa
        $this->assertStringContainsString('Perkembangan Siswa', $waliSidebarHtml);
        $this->assertStringContainsString(route('wali-kelas.perkembangan-siswa.index'), $waliSidebarHtml);

        // Super admin sidebar tidak menampilkan menu wali kelas / pendamping
        $superAdmin = $this->makeUserWithRole('Super Admin', 'admin@primainsani.sch.id', null, 'super admin');
        Auth::setUser($superAdmin);
        $adminSidebarHtml = view('layouts.sidebar')->render();
        $this->assertStringNotContainsString('Perkembangan Siswa', $adminSidebarHtml);
        $this->assertStringNotContainsString(route('wali-kelas.perkembangan-siswa.index'), $adminSidebarHtml);
    }
}
