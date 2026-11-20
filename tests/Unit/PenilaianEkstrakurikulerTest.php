<?php

namespace Tests\Unit;

use App\Http\Controllers\WaliKelas\PenilaianEkstrakurikulerController;
use App\Models\Admin\DataMaster\AnggotaEkstrakurikuler;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\User;
use App\Policies\EkstrakurikulerPolicy;
use App\Services\WaliKelas\WaliKelasEkstrakurikulerService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ViewErrorBag;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PenilaianEkstrakurikulerTest extends TestCase
{
    public function test_kkm_predikat_ranges_and_auto_deskripsi_use_sab_bsh_sb_mb(): void
    {
        $ranges = Ekstrakurikuler::generatePredikatRanges(75);

        $this->assertCount(4, $ranges);
        $this->assertSame('SAB', $ranges[0]['predikat']);
        $this->assertSame('Sangat Baik', $ranges[0]['keterangan']);
        $this->assertSame(92, $ranges[0]['nilai_min']);
        $this->assertSame(100, $ranges[0]['nilai_max']);

        $this->assertSame('BSH', $ranges[1]['predikat']);
        $this->assertSame('Berkembang Sesuai Harapan', $ranges[1]['keterangan']);
        $this->assertSame(83, $ranges[1]['nilai_min']);
        $this->assertSame(91, $ranges[1]['nilai_max']);

        $this->assertSame('SB', $ranges[2]['predikat']);
        $this->assertSame('Sedang Berkembang', $ranges[2]['keterangan']);
        $this->assertSame(75, $ranges[2]['nilai_min']);
        $this->assertSame(82, $ranges[2]['nilai_max']);

        $this->assertSame('MB', $ranges[3]['predikat']);
        $this->assertSame('Mulai Berkembang', $ranges[3]['keterangan']);
        $this->assertSame(0, $ranges[3]['nilai_min']);
        $this->assertSame(74, $ranges[3]['nilai_max']);

        $this->assertSame('SAB', Ekstrakurikuler::resolvePredikatForNilai(95, 75)['predikat']);
        $this->assertSame('BSH', Ekstrakurikuler::resolvePredikatForNilai(88, 75)['predikat']);
        $this->assertSame('SB', Ekstrakurikuler::resolvePredikatForNilai(78, 75)['predikat']);
        $this->assertSame('MB', Ekstrakurikuler::resolvePredikatForNilai(65, 75)['predikat']);
        $this->assertNull(Ekstrakurikuler::resolvePredikatForNilai(null, 75));

        $materi = 'teknik dasar memanah dan fokus bidikan';
        $this->assertSame(
            'Mengikuti kegiatan dengan sangat baik serta mahir dalam teknik dasar memanah dan fokus bidikan.',
            Ekstrakurikuler::buildAutoDeskripsi('SAB', $materi, 'Panahan')
        );
        $this->assertSame(
            'Mengikuti kegiatan dengan baik dan berkembang sesuai harapan dalam teknik dasar memanah dan fokus bidikan.',
            Ekstrakurikuler::buildAutoDeskripsi('BSH', $materi, 'Panahan')
        );
        $this->assertSame(
            'Sedang berkembang dan cukup baik dalam mengikuti teknik dasar memanah dan fokus bidikan.',
            Ekstrakurikuler::buildAutoDeskripsi('SB', $materi, 'Panahan')
        );
        $this->assertSame(
            'Mulai berkembang dan perlu bimbingan lebih lanjut dalam teknik dasar memanah dan fokus bidikan.',
            Ekstrakurikuler::buildAutoDeskripsi('MB', $materi, 'Panahan')
        );
    }

    public function test_pembimbing_view_renders_alert_when_not_coaching_any_ekskul_and_grading_table_when_eligible(): void
    {
        view()->share('errors', new ViewErrorBag);

        $ta = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);
        $ta->id = 1;

        $pembina = Karyawan::make([
            'nipy' => 'NIPY-EKS-01',
            'nama_lengkap' => 'Ustadz Ridwan',
            'gelar' => 'S.Or',
        ]);

        $user = $this->makeUserWithRole('Ustadz Ridwan', 'ridwan@primainsani.sch.id', 'NIPY-EKS-01', 'wali kelas');
        $user->setRelation('karyawan', $pembina);
        Auth::shouldUse('web');
        Auth::setUser($user);

        $ekskulPilihan = Ekstrakurikuler::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'karyawan_nipy' => 'NIPY-EKS-01',
            'nama_ekstrakurikuler' => 'Panahan',
            'biaya' => 0,
            'unit' => 'SD',
            'sifat' => Ekstrakurikuler::SIFAT_PILIHAN,
            'kkm' => 75,
            'materi' => 'teknik dasar memanah dan fokus bidikan',
        ]);
        $ekskulPilihan->id = 12;
        $ekskulPilihan->setRelation('tahunAjaran', $ta);
        $ekskulPilihan->setRelation('pembina', $pembina);

        $ekskulWajib = Ekstrakurikuler::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'karyawan_nipy' => null,
            'nama_ekstrakurikuler' => 'Pramuka',
            'biaya' => 0,
            'unit' => 'SD',
            'sifat' => Ekstrakurikuler::SIFAT_WAJIB,
            'kkm' => 75,
            'materi' => 'baris-berbaris, tali-temali, dan kemandirian pramuka',
        ]);
        $ekskulWajib->id = 11;
        $ekskulWajib->setRelation('tahunAjaran', $ta);
        $ekskulWajib->setRelation('pembina', null);

        $kelas = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'nama_kelas' => '3 Al-Mulk',
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => '3',
        ]);
        $kelas->id = 7;

        $siswa = Siswa::make([
            'nis' => '20260099',
            'nama_lengkap' => 'Zaidan Alfarizi',
            'jenis_kelamin' => 'L',
            'status' => 'Aktif',
        ]);

        $anggotaKelas = AnggotaKelas::make([
            'siswa_nis' => '20260099',
            'akademik_master_kelas_id' => 7,
            'pendaftaran' => 'Naik Kelas',
        ]);
        $anggotaKelas->id = 88;
        $anggotaKelas->setRelation('siswa', $siswa);
        $anggotaKelas->setRelation('kelas', $kelas);

        $anggotaEkskulPilihan = AnggotaEkstrakurikuler::make([
            'anggota_kelas_id' => 88,
            'akademik_master_ekstrakuler_id' => 12,
            'pendaftaran' => 'Baru',
            'nilai' => 94,
            'predikat' => 'SAB',
            'keterangan' => null,
        ]);
        $anggotaEkskulPilihan->id = 101;
        $anggotaEkskulPilihan->setRelation('anggotaKelas', $anggotaKelas);
        $anggotaEkskulPilihan->setRelation('ekstrakurikuler', $ekskulPilihan);

        $anggotaEkskulWajib = AnggotaEkstrakurikuler::make([
            'anggota_kelas_id' => 88,
            'akademik_master_ekstrakuler_id' => 11,
            'pendaftaran' => 'Baru',
            'nilai' => 88,
            'predikat' => 'BSH',
            'keterangan' => null,
        ]);
        $anggotaEkskulWajib->id = 102;
        $anggotaEkskulWajib->setRelation('anggotaKelas', $anggotaKelas);
        $anggotaEkskulWajib->setRelation('ekstrakurikuler', $ekskulWajib);

        $service = $this->createMock(WaliKelasEkstrakurikulerService::class);
        $service->expects($this->exactly(3))
            ->method('getPageData')
            ->willReturnOnConsecutiveCalls(
                [
                    'isEligible' => false,
                    'mode' => 'pilihan',
                    'guardReason' => 'no_assigned_ekskul',
                    'ineligibleReason' => 'no_assigned_ekskul',
                    'nipy' => 'NIPY-EKS-01',
                    'karyawanProfile' => $pembina,
                    'activeTahunAjaran' => $ta,
                    'assignedClasses' => new EloquentCollection,
                    'selectedKelas' => null,
                    'peranDiKelas' => null,
                    'assignedEkskuls' => new EloquentCollection,
                    'selectedEkskul' => null,
                    'members' => new EloquentCollection,
                    'predikatRanges' => [],
                    'stats' => ['total' => 0, 'dinilai' => 0, 'belum_dinilai' => 0],
                ],
                [
                    'isEligible' => true,
                    'mode' => 'pilihan',
                    'guardReason' => null,
                    'ineligibleReason' => null,
                    'nipy' => 'NIPY-EKS-01',
                    'karyawanProfile' => $pembina,
                    'activeTahunAjaran' => $ta,
                    'assignedClasses' => new EloquentCollection([$kelas]),
                    'selectedKelas' => null,
                    'peranDiKelas' => null,
                    'assignedEkskuls' => new EloquentCollection([$ekskulPilihan]),
                    'selectedEkskul' => $ekskulPilihan,
                    'members' => new EloquentCollection([$anggotaEkskulPilihan]),
                    'predikatRanges' => $ekskulPilihan->predikatRanges(),
                    'stats' => ['total' => 1, 'dinilai' => 1, 'belum_dinilai' => 0],
                ],
                [
                    'isEligible' => true,
                    'mode' => 'wajib',
                    'guardReason' => null,
                    'ineligibleReason' => null,
                    'nipy' => 'NIPY-EKS-01',
                    'karyawanProfile' => $pembina,
                    'activeTahunAjaran' => $ta,
                    'assignedClasses' => new EloquentCollection([$kelas]),
                    'selectedKelas' => $kelas,
                    'peranDiKelas' => 'Wali Kelas',
                    'assignedEkskuls' => new EloquentCollection([$ekskulPilihan]),
                    'selectedEkskul' => $ekskulWajib,
                    'members' => new EloquentCollection([$anggotaEkskulWajib]),
                    'predikatRanges' => $ekskulWajib->predikatRanges(),
                    'stats' => ['total' => 1, 'dinilai' => 1, 'belum_dinilai' => 0],
                ]
            );

        $controller = new PenilaianEkstrakurikulerController($service);
        $request = Request::create('/wali-kelas/ekstrakurikuler', 'GET');
        $request->setUserResolver(static fn (): User => $user);

        Model::preventLazyLoading(true);
        try {
            $ineligibleHtml = $controller->index($request)->render();
            $eligibleHtml = $controller->index($request)->render();
            $wajibHtml = $controller->index($request)->render();
            $raporHtml = view('pdf.rapor.nilai', [
                'kelas' => $kelas,
                'tahunAjaran' => $ta,
                'periode' => 'semester',
                'periodeLabel' => ' Akhir Semester (SAS)',
                'fase' => 'B',
                'sekolah' => null,
                'titimangsa' => null,
                'logoDataUri' => null,
                'entries' => [
                    [
                        'anggota' => $anggotaKelas,
                        'siswa' => $siswa,
                        'mapelRows' => [],
                        'mapelGroups' => [],
                        'ekskulRows' => [$anggotaEkskulPilihan, $anggotaEkskulWajib],
                        't2q' => null,
                        'presensi' => ['sakit' => 0, 'izin' => 0, 'alpa' => 0],
                    ],
                ],
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('Tidak Ada Ekstrakurikuler yang Anda Dampingi', $ineligibleHtml);
        $this->assertStringContainsString('NIPY-EKS-01', $ineligibleHtml);

        $this->assertStringContainsString('Penilaian Ekstrakurikuler Panahan', $eligibleHtml);
        $this->assertStringContainsString('Zaidan Alfarizi', $eligibleHtml);
        $this->assertStringContainsString('SAB', $eligibleHtml);
        $this->assertStringContainsString('Sangat Baik', $eligibleHtml);
        $this->assertStringNotContainsString('name="kkm"', $eligibleHtml);
        $this->assertStringNotContainsString('name="materi"', $eligibleHtml);
        $this->assertStringNotContainsString('<textarea', $eligibleHtml);
        $this->assertStringNotContainsString('name="keterangan[', $eligibleHtml);
        $this->assertStringContainsString(
            'Mengikuti kegiatan dengan sangat baik serta mahir dalam teknik dasar memanah dan fokus bidikan.',
            $eligibleHtml
        );
        $this->assertSame(1, substr_count($eligibleHtml, 'Simpan Nilai'));

        // Mode Wajib (Pramuka) untuk Wali Kelas
        $this->assertStringContainsString('Penilaian Ekstrakurikuler Pramuka (Wajib) — Kelas 3 Al-Mulk', $wajibHtml);
        $this->assertStringContainsString('BSH', $wajibHtml);
        $this->assertStringContainsString(
            'Mengikuti kegiatan dengan baik dan berkembang sesuai harapan dalam baris-berbaris, tali-temali, dan kemandirian pramuka.',
            $wajibHtml
        );
        $this->assertStringContainsString(route('wali-kelas.ekstrakurikuler.wajib.store', sqidFor('wali-kelas.ekstrakurikuler.wajib.store', 7)), $wajibHtml);

        // Di PDF Rapor: Pramuka (Wajib) otomatis urut pertama diikuti Panahan (Pilihan), dan hanya menampilkan predikat (BSH & SAB)
        $this->assertStringContainsString('CAPAIAN', $raporHtml);
        $posPramuka = strpos($raporHtml, 'Pramuka');
        $posPanahan = strpos($raporHtml, 'Panahan');
        $this->assertNotFalse($posPramuka);
        $this->assertNotFalse($posPanahan);
        $this->assertLessThan($posPanahan, $posPramuka);
        $this->assertStringContainsString('>BSH<', $raporHtml);
        $this->assertStringContainsString('>SAB<', $raporHtml);
        $this->assertStringContainsString(
            'Mengikuti kegiatan dengan sangat baik serta mahir dalam teknik dasar memanah dan fokus bidikan.',
            $raporHtml
        );

        $policy = new EkstrakurikulerPolicy;
        $this->assertTrue($policy->viewPenilaian($user)->allowed());
    }

    private function makeUserWithRole(string $name, string $email, ?string $noInduk, string $roleName): User
    {
        $user = User::make([
            'name' => $name,
            'email' => $email,
            'no_induk' => $noInduk,
        ]);
        $user->id = 777;

        $role = new Role([
            'name' => $roleName,
            'guard_name' => 'web',
        ]);
        $user->setRelation('roles', new Collection([$role]));
        $user->setRelation('permissions', new Collection);

        return $user;
    }
}
