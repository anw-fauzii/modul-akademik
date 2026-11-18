<?php

namespace Tests\Unit;

use App\Http\Controllers\WaliKelas\AnggotaKelasController;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Models\Admin\DataMaster\Jemputan;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\DataPelengkap\BerkebutuhanKhusus;
use App\Models\Admin\DataPelengkap\JenjangPendidikan;
use App\Models\Admin\DataPelengkap\Pekerjaan;
use App\Models\Admin\DataPelengkap\Penghasilan;
use App\Models\Admin\DataPelengkap\Transportasi;
use App\Models\User;
use App\Services\Admin\DataMaster\SiswaService;
use App\Services\WaliKelas\WaliKelasAnggotaKelasService;
use App\Services\WaliKelas\WaliKelasScopeService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class WaliKelasAnggotaKelasTest extends TestCase
{
    private function makeUserWithRole(string $name, string $email, ?string $noInduk, string $roleName): User
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

        return $user;
    }

    private function makeDetailSiswa(Kelas $kelas, string $nis, string $nama, string $jk = 'L', string $status = 'Aktif'): Siswa
    {
        $petugas = Karyawan::make(['nipy' => 'NIPY-JEMPUT', 'nama_lengkap' => 'Agus Supriyadi', 'gelar' => null]);
        $jemputan = Jemputan::make(['karyawan_nipy' => 'NIPY-JEMPUT', 'harga_pp' => 250000, 'harga_setengah' => 150000]);
        $jemputan->id = 7;
        $jemputan->setRelation('petugas', $petugas);

        $ekskul = Ekstrakurikuler::make(['nama_ekstrakurikuler' => 'Panahan', 'biaya' => 50000, 'unit' => 'SD']);
        $ekskul->id = 3;

        $siswa = Siswa::make([
            'nis' => $nis,
            'nama_lengkap' => $nama,
            'nisn' => '0123456789',
            'jenis_kelamin' => $jk,
            'tempat_lahir' => 'Garut',
            'tanggal_lahir' => '2017-05-12',
            'agama' => 'Islam',
            'kewarganegaraan' => 'WNI',
            'status' => $status,
            'jenis_pendaftaran' => 'Siswa Baru',
            'whatsapp' => '081234567890',
            'nama_ayah' => 'Hendra Wijaya',
            'nama_ibu' => 'Siti Rahma',
            'nama_wali' => '-',
        ]);

        $siswa->setRelation('kelas', $kelas);
        $siswa->setRelation('karyawan', $kelas->waliKelas);
        $siswa->setRelation('ekstrakurikuler', $ekskul);
        $siswa->setRelation('jemputan', $jemputan);
        $siswa->setRelation('berkebutuhanKhusus', BerkebutuhanKhusus::make(['nama_berkebutuhan_khusus' => 'Tidak Ada']));
        $siswa->setRelation('transportasi', Transportasi::make(['nama_transportasi' => 'Antar Jemput']));
        $siswa->setRelation('jenjangPendidikanAyah', JenjangPendidikan::make(['nama_jenjang_pendidikan' => 'S1']));
        $siswa->setRelation('pekerjaanAyah', Pekerjaan::make(['nama_pekerjaan' => 'Wiraswasta']));
        $siswa->setRelation('penghasilanAyah', Penghasilan::make(['nama_penghasilan' => 'Rp 3.000.000 - Rp 5.000.000']));
        $siswa->setRelation('berkebutuhanKhususAyah', null);
        $siswa->setRelation('jenjangPendidikanIbu', JenjangPendidikan::make(['nama_jenjang_pendidikan' => 'S1']));
        $siswa->setRelation('pekerjaanIbu', Pekerjaan::make(['nama_pekerjaan' => 'Guru']));
        $siswa->setRelation('penghasilanIbu', Penghasilan::make(['nama_penghasilan' => 'Rp 2.000.000 - Rp 3.000.000']));
        $siswa->setRelation('berkebutuhanKhususIbu', null);
        $siswa->setRelation('jenjangPendidikanWali', null);
        $siswa->setRelation('pekerjaanWali', null);
        $siswa->setRelation('penghasilanWali', null);
        $siswa->setRelation('berkebutuhanKhususWali', null);

        return $siswa;
    }

    public function test_scope_service_summarizes_and_sorts_students_without_n_plus_1(): void
    {
        $scopeService = new WaliKelasScopeService;

        $siswaB = Siswa::make(['nis' => '2602', 'nama_lengkap' => 'Zaki Pratama', 'jenis_kelamin' => 'L', 'status' => 'Aktif', 'nama_ayah' => 'Budi', 'nama_ibu' => 'Rina']);
        $siswaA = Siswa::make(['nis' => '2601', 'nama_lengkap' => 'Aisyah Putri', 'jenis_kelamin' => 'P', 'status' => 'Aktif', 'nama_ayah' => 'Hendra', 'nama_ibu' => 'Dewi']);
        $siswaC = Siswa::make(['nis' => '2603', 'nama_lengkap' => 'Bima Santoso', 'jenis_kelamin' => 'L', 'status' => 'Nonaktif', 'nama_ayah' => 'Dedi', 'nama_ibu' => 'Sari']);

        $anggotaB = AnggotaKelas::make(['siswa_nis' => '2602', 'akademik_master_kelas_id' => 10, 'pendaftaran' => 'Naik Kelas']);
        $anggotaB->id = 2;
        $anggotaB->setRelation('siswa', $siswaB);

        $anggotaA = AnggotaKelas::make(['siswa_nis' => '2601', 'akademik_master_kelas_id' => 10, 'pendaftaran' => 'Siswa Baru']);
        $anggotaA->id = 1;
        $anggotaA->setRelation('siswa', $siswaA);

        $anggotaC = AnggotaKelas::make(['siswa_nis' => '2603', 'akademik_master_kelas_id' => 10, 'pendaftaran' => 'Pindahan']);
        $anggotaC->id = 3;
        $anggotaC->setRelation('siswa', $siswaC);

        $sorted = $scopeService->sortAnggotaByStudentName(new EloquentCollection([$anggotaB, $anggotaA, $anggotaC]));
        $this->assertSame(['Aisyah Putri', 'Bima Santoso', 'Zaki Pratama'], $sorted->map(fn (AnggotaKelas $item) => $item->siswa->nama_lengkap)->all());

        $summary = $scopeService->summarizeStudents($sorted);
        $this->assertSame(3, $summary['total']);
        $this->assertSame(2, $summary['laki']);
        $this->assertSame(1, $summary['perempuan']);
        $this->assertSame(2, $summary['aktif']);
        $this->assertSame(['Siswa Baru' => 1, 'Pindahan' => 1, 'Naik Kelas' => 1], $summary['by_pendaftaran']);
    }

    public function test_get_student_detail_enforces_active_class_ownership_and_reuses_siswa_service_load_detail(): void
    {
        $ta = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);
        $ta->id = 1;

        $wali = Karyawan::make(['nipy' => 'NIPY-001', 'nama_lengkap' => 'Sari Wulandari', 'gelar' => 'S.Pd']);

        $kelas = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'karyawan_nipy' => 'NIPY-001',
            'pendamping_nipy' => 'NIPY-002',
            'nama_kelas' => '1 Al-Fatihah',
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => '1',
        ]);
        $kelas->id = 10;
        $kelas->setRelation('tahunAjaran', $ta);
        $kelas->setRelation('waliKelas', $wali);

        $user = $this->makeUserWithRole('Ustadzah Sari', 'sari@primainsani.sch.id', 'NIPY-001', 'wali kelas');
        $user->setRelation('karyawan', $wali);
        $siswa = $this->makeDetailSiswa($kelas, '202601001', 'Ahmad Fauzi');

        $anggotaOwn = AnggotaKelas::make(['siswa_nis' => '202601001', 'akademik_master_kelas_id' => 10, 'pendaftaran' => 'Siswa Baru']);
        $anggotaOwn->id = 55;
        $anggotaOwn->setRelation('siswa', $siswa);

        $scopeService = $this->createMock(WaliKelasScopeService::class);
        $scopeService->expects($this->once())
            ->method('authorizeAnggotaKelasForUser')
            ->with($user, $anggotaOwn)
            ->willReturn([
                'isEligible' => true,
                'ineligibleReason' => null,
                'nipy' => 'NIPY-001',
                'karyawanProfile' => null,
                'activeTahunAjaran' => $ta,
                'assignedClasses' => new EloquentCollection([$kelas]),
                'selectedKelas' => $kelas,
                'peranDiKelas' => 'Wali Kelas',
            ]);

        $siswaService = $this->createMock(SiswaService::class);
        $siswaService->expects($this->once())
            ->method('loadDetail')
            ->with($siswa)
            ->willReturn($siswa);

        $service = new WaliKelasAnggotaKelasService($scopeService, null, $siswaService);
        $detail = $service->getStudentDetail($user, $anggotaOwn);

        $this->assertSame($siswa, $detail['siswa']);
        $this->assertSame($kelas, $detail['selectedKelas']);
        $this->assertTrue($anggotaOwn->relationLoaded('kelas'));
    }

    public function test_unauthorized_cross_class_access_throws_403_http_exception(): void
    {
        $ta = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);
        $ta->id = 1;

        $assignedKelas = Kelas::make(['nama_kelas' => '1 Al-Fatihah']);
        $assignedKelas->id = 10;

        $user = $this->makeUserWithRole('Ustadzah Sari', 'sari@primainsani.sch.id', 'NIPY-001', 'wali kelas');
        $foreignAnggota = AnggotaKelas::make(['siswa_nis' => '202609999', 'akademik_master_kelas_id' => 99, 'pendaftaran' => 'Siswa Baru']);
        $foreignAnggota->id = 999;

        $mockScope = $this->getMockBuilder(WaliKelasScopeService::class)
            ->onlyMethods(['resolveScopeContext'])
            ->getMock();
        $mockScope->expects($this->once())
            ->method('resolveScopeContext')
            ->with($user, 99, false)
            ->willReturn([
                'isEligible' => true,
                'ineligibleReason' => null,
                'nipy' => 'NIPY-001',
                'karyawanProfile' => null,
                'activeTahunAjaran' => $ta,
                'assignedClasses' => new EloquentCollection([$assignedKelas]),
                'selectedKelas' => $assignedKelas,
                'peranDiKelas' => 'Wali Kelas',
            ]);

        try {
            $mockScope->authorizeAnggotaKelasForUser($user, $foreignAnggota);
            $this->fail('Seharusnya melempar HttpException 403 ketika mengakses anggota kelas di luar kelas binaan aktif.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_controller_renders_anggota_kelas_index_and_reuses_siswa_show_without_admin_links(): void
    {
        $ta = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);
        $ta->id = 1;

        $wali = Karyawan::make(['nipy' => 'NIPY-001', 'nama_lengkap' => 'Sari Wulandari', 'gelar' => 'S.Pd']);
        $pendamping = Karyawan::make(['nipy' => 'NIPY-002', 'nama_lengkap' => 'Dewi Lestari', 'gelar' => 'S.Pd']);

        $kelasA = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'karyawan_nipy' => 'NIPY-001',
            'pendamping_nipy' => 'NIPY-002',
            'tingkatan_kelas' => '1',
            'nama_kelas' => '1 Al-Fatihah',
            'jenjang_kelas' => 'SD',
        ]);
        $kelasA->id = 10;
        $kelasA->setRelation('tahunAjaran', $ta);
        $kelasA->setRelation('waliKelas', $wali);
        $kelasA->setRelation('pendamping', $pendamping);

        $kelasB = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'karyawan_nipy' => 'NIPY-003',
            'pendamping_nipy' => 'NIPY-001',
            'tingkatan_kelas' => '2',
            'nama_kelas' => '2 An-Naba',
            'jenjang_kelas' => 'SD',
        ]);
        $kelasB->id = 11;
        $kelasB->setRelation('tahunAjaran', $ta);
        $kelasB->setRelation('waliKelas', Karyawan::make(['nipy' => 'NIPY-003', 'nama_lengkap' => 'Ahmad']));
        $kelasB->setRelation('pendamping', $wali);

        $siswa = $this->makeDetailSiswa($kelasA, '202601001', 'Ahmad Fauzi');

        $anggota = AnggotaKelas::make(['siswa_nis' => '202601001', 'akademik_master_kelas_id' => 10, 'pendaftaran' => 'Siswa Baru']);
        $anggota->id = 55;
        $anggota->setRelation('siswa', $siswa);
        $anggota->setRelation('kelas', $kelasA);

        $user = $this->makeUserWithRole('Ustadzah Sari', 'sari@primainsani.sch.id', 'NIPY-001', 'wali kelas');
        $user->setRelation('karyawan', $wali);
        Auth::shouldUse('web');
        Auth::setUser($user);

        $service = $this->createMock(WaliKelasAnggotaKelasService::class);
        $service->expects($this->once())
            ->method('getIndexData')
            ->with($user, 10)
            ->willReturn([
                'isEligible' => true,
                'ineligibleReason' => null,
                'nipy' => 'NIPY-001',
                'karyawanProfile' => $wali,
                'activeTahunAjaran' => $ta,
                'assignedClasses' => new EloquentCollection([$kelasA, $kelasB]),
                'selectedKelas' => $kelasA,
                'peranDiKelas' => 'Wali Kelas',
                'students' => new EloquentCollection([$anggota]),
                'studentStats' => ['total' => 1, 'laki' => 1, 'perempuan' => 0, 'aktif' => 1, 'by_pendaftaran' => ['Siswa Baru' => 1]],
            ]);

        $service->expects($this->once())
            ->method('getStudentDetail')
            ->with($user, $anggota)
            ->willReturn([
                'isEligible' => true,
                'ineligibleReason' => null,
                'nipy' => 'NIPY-001',
                'karyawanProfile' => null,
                'activeTahunAjaran' => $ta,
                'assignedClasses' => new EloquentCollection([$kelasA, $kelasB]),
                'selectedKelas' => $kelasA,
                'peranDiKelas' => 'Wali Kelas',
                'anggotaKelas' => $anggota,
                'siswa' => $siswa,
            ]);

        $controller = new AnggotaKelasController($service);
        $indexRequest = Request::create('/wali-kelas/anggota-kelas?kelas_id=10', 'GET');
        $indexRequest->setUserResolver(static fn (): User => $user);

        $showRequest = Request::create('/wali-kelas/anggota-kelas/55', 'GET');
        $showRequest->setUserResolver(static fn (): User => $user);

        $indexView = $controller->index($indexRequest);
        $showView = $controller->show($anggota, $showRequest);

        $this->assertSame('waliKelas.anggotaKelas.index', $indexView->name());
        $this->assertSame('admin.dataMaster.siswa.show', $showView->name());

        Model::preventLazyLoading(true);
        try {
            $indexHtml = $indexView->render();
            $showHtml = $showView->render();
            $ineligibleHtml = view('waliKelas.anggotaKelas.index', [
                'isEligible' => false,
                'ineligibleReason' => 'not_assigned_active_year',
                'nipy' => 'NIPY-999',
                'karyawanProfile' => null,
                'activeTahunAjaran' => $ta,
                'assignedClasses' => new EloquentCollection,
                'selectedKelas' => null,
                'peranDiKelas' => null,
            ])->render();
            $waliSidebarHtml = view('layouts.sidebar')->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('Anggota Kelas 1 Al-Fatihah', $indexHtml);
        $this->assertStringContainsString('datatable-ordered', $indexHtml);
        $this->assertStringContainsString('id="wkAnggotaTable"', $indexHtml);
        $this->assertStringContainsString('data-search-placeholder="Cari nama siswa..."', $indexHtml);
        $this->assertStringContainsString('Ahmad Fauzi', $indexHtml);
        $this->assertStringContainsString('202601001', $indexHtml);
        $this->assertStringContainsString('Hendra Wijaya', $indexHtml);
        $this->assertStringContainsString('2 An-Naba', $indexHtml);
        $this->assertStringContainsString(route('wali-kelas.anggota-kelas.show', 55), $indexHtml);
        $this->assertStringNotContainsString('Semua Gender', $indexHtml);
        $this->assertStringNotContainsString('Semua Pendaftaran', $indexHtml);
        $this->assertStringNotContainsString('Terapkan', $indexHtml);
        $this->assertStringNotContainsString('Cetak Identitas (PDF)', $indexHtml);
        $this->assertStringNotContainsString(route('wali-kelas.rapor.identitas.kelas', 10), $indexHtml);
        $this->assertStringNotContainsString(route('wali-kelas.rapor.identitas.siswa', 55), $indexHtml);

        // Reuse view detail siswa tanpa menampilkan tautan admin-only edit/kelas/ekskul/jemputan
        $this->assertStringContainsString('Ahmad Fauzi', $showHtml);
        $this->assertStringContainsString('Kembali ke Anggota Kelas', $showHtml);
        $this->assertStringContainsString(route('wali-kelas.anggota-kelas.index', ['kelas_id' => 10]), $showHtml);
        $this->assertStringNotContainsString(route('siswa.edit', '202601001'), $showHtml);
        $this->assertStringNotContainsString(route('kelas.show', 10), $showHtml);
        $this->assertStringNotContainsString(route('ekstrakurikuler.show', 3), $showHtml);
        $this->assertStringNotContainsString(route('jemputan.show', 7), $showHtml);

        $this->assertStringContainsString('Belum Ada Penugasan Wali Kelas / Pendamping Aktif', $ineligibleHtml);

        $this->assertStringContainsString('Anggota Kelas', $waliSidebarHtml);
        $this->assertStringContainsString(route('wali-kelas.anggota-kelas.index'), $waliSidebarHtml);
        $this->assertStringNotContainsString('Data Master', $waliSidebarHtml);
    }

    public function test_super_admin_sidebar_hides_wali_kelas_menus(): void
    {
        $superAdmin = $this->makeUserWithRole('Super Admin', 'admin@primainsani.sch.id', null, 'super admin');
        $superAdmin->setRelation('karyawan', null);
        Auth::shouldUse('web');
        Auth::setUser($superAdmin);

        $sidebarHtml = view('layouts.sidebar')->render();

        $this->assertStringContainsString('Data Master', $sidebarHtml);
        $this->assertStringNotContainsString('Dashboard Wali Kelas', $sidebarHtml);
        $this->assertStringNotContainsString(route('wali-kelas.dashboard'), $sidebarHtml);
        $this->assertStringNotContainsString(route('wali-kelas.anggota-kelas.index'), $sidebarHtml);
        $this->assertStringNotContainsString(route('wali-kelas.perkembangan-siswa.index'), $sidebarHtml);
        $this->assertStringNotContainsString('submenu-wali-penilaian', $sidebarHtml);
        $this->assertStringNotContainsString('submenu-wali-presensi', $sidebarHtml);
    }
}
