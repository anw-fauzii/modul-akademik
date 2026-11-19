<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\Penilaian\RaporController as AdminRaporController;
use App\Http\Controllers\WaliKelas\RaporController as WaliKelasRaporController;
use App\Models\Admin\DataMaster\AnggotaEkstrakurikuler;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\AnggotaT2q;
use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Sekolah;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\DataMaster\Titimangsa;
use App\Models\Admin\DataPelengkap\Pekerjaan;
use App\Models\Admin\Pelajaran\KelompokPelajaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Models\Admin\Penilaian\RekapMapel;
use App\Models\User;
use App\Services\Admin\Penilaian\RaporPdfService;
use App\Services\WaliKelas\WaliKelasScopeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class RaporPdfTest extends TestCase
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
    private function makeRaporFixtures(): array
    {
        $ta = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);
        $ta->id = 1;

        $wali = Karyawan::make(['nipy' => 'NIPY-001', 'nama_lengkap' => 'Sari Wulandari', 'gelar' => 'S.Pd']);
        $kepsek = Karyawan::make(['nipy' => 'NIPY-KS1', 'nama_lengkap' => 'H. Ahmad Fauzi', 'gelar' => 'M.Pd']);
        $guruT2q = Karyawan::make(['nipy' => 'NIPY-TQ1', 'nama_lengkap' => 'Ustadz Ridwan', 'gelar' => 'S.Ag']);

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

        $sekolah = Sekolah::make([
            'jenjang_kelas' => 'SD',
            'nama_yayasan' => 'Yayasan Prima Insani',
            'nama_sekolah' => 'SD Prima Insani',
            'npsn' => '20209000',
            'nss' => '1020211001',
            'alamat' => 'Jl. Cimanuk No. 14',
            'kelurahan' => 'Sukamentri',
            'kecamatan' => 'Garut Kota',
            'kabupaten' => 'Kabupaten Garut',
            'provinsi' => 'Jawa Barat',
            'kode_pos' => '44116',
            'telepon' => '(0262) 234567',
            'email' => 'sd@primainsani.sch.id',
            'website' => 'https://primainsani.sch.id',
            'status' => true,
        ]);
        $sekolah->id = 1;

        $titimangsa = Titimangsa::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => '*',
            'periode' => PeriodeTp::PERIODE_SEMESTER,
            'tempat' => 'Garut',
            'tanggal_titimangsa' => '2026-12-19',
            'kepala_sekolah_nipy' => 'NIPY-KS1',
            'nama_kepala_sekolah_snapshot' => 'H. Ahmad Fauzi, M.Pd',
            'jabatan_kepala_sekolah' => 'Kepala SD Prima Insani',
            'status' => true,
        ]);
        $titimangsa->id = 1;
        $titimangsa->setRelation('kepalaSekolah', $kepsek);

        $pekAyah = Pekerjaan::make(['nama_pekerjaan' => 'Wiraswasta']);
        $pekIbu = Pekerjaan::make(['nama_pekerjaan' => 'Ibu Rumah Tangga']);

        $siswa1 = Siswa::make([
            'nis' => '202601001',
            'nisn' => '0139990001',
            'nama_lengkap' => 'Ahmad Fauzi Pratama',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Garut',
            'tanggal_lahir' => '2016-05-12',
            'agama' => 'Islam',
            'anak_ke' => 1,
            'alamat' => 'Jl. Merdeka No. 10',
            'kabupaten' => 'Garut',
            'nama_ayah' => 'Budi Pratama',
            'nama_ibu' => 'Siti Aminah',
            'status' => 'Aktif',
        ]);
        $siswa1->setRelation('pekerjaanAyah', $pekAyah);
        $siswa1->setRelation('pekerjaanIbu', $pekIbu);
        $siswa1->setRelation('pekerjaanWali', null);

        $siswa2 = Siswa::make([
            'nis' => '202601002',
            'nisn' => '0139990002',
            'nama_lengkap' => 'Zahra Aulia',
            'jenis_kelamin' => 'P',
            'status' => 'Aktif',
        ]);
        $siswa2->setRelation('pekerjaanAyah', null);
        $siswa2->setRelation('pekerjaanIbu', null);
        $siswa2->setRelation('pekerjaanWali', null);

        $anggota1 = AnggotaKelas::make(['siswa_nis' => '202601001', 'akademik_master_kelas_id' => 10, 'pendaftaran' => 'Naik Kelas']);
        $anggota1->id = 301;
        $anggota1->setRelation('siswa', $siswa1);
        $anggota1->setRelation('kelas', $kelas);

        $anggota2 = AnggotaKelas::make(['siswa_nis' => '202601002', 'akademik_master_kelas_id' => 10, 'pendaftaran' => 'Naik Kelas']);
        $anggota2->id = 302;
        $anggota2->setRelation('siswa', $siswa2);
        $anggota2->setRelation('kelas', $kelas);

        $mapel = MataPelajaran::make(['singkatan' => 'MTK', 'nama_mata_pelajaran' => 'Matematika', 'unit' => 'SD']);
        $mapel->id = 21;

        $rekap = RekapMapel::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'akademik_pelajaran_mata_pelajaran_id' => 21,
            'anggota_kelas_id' => 301,
            'periode' => PeriodeTp::PERIODE_SEMESTER,
            'nilai_akhir_rapor' => 89,
            'predikat_rapor' => 'B',
            'keterangan_predikat' => 'BSH',
            'tuntas_kkm' => true,
            'deskripsi_capaian' => 'Menunjukkan penguasaan baik pada operasi hitung pecahan.',
        ]);

        $ekskulModel = Ekstrakurikuler::make(['nama_ekstrakurikuler' => 'Pramuka']);
        $anggotaEkskul = AnggotaEkstrakurikuler::make(['anggota_kelas_id' => 301, 'keterangan' => 'Aktif dan disiplin.']);
        $anggotaEkskul->setRelation('ekstrakurikuler', $ekskulModel);

        $anggotaT2q = AnggotaT2q::make(['anggota_kelas_id' => 301, 'karyawan_nipy' => 'NIPY-TQ1', 'tingkat' => 'Jilid 4', 'keterangan' => 'Lancar tajwid']);
        $anggotaT2q->setRelation('guruT2q', $guruT2q);

        return compact('ta', 'wali', 'kepsek', 'kelas', 'sekolah', 'titimangsa', 'anggota1', 'anggota2', 'mapel', 'rekap', 'anggotaEkskul', 'anggotaT2q');
    }

    public function test_separated_identitas_and_nilai_pdf_views_render_clean_html_and_valid_dompdf_binaries(): void
    {
        $f = $this->makeRaporFixtures();

        $identitasPayload = [
            'kelas' => $f['kelas'],
            'tahunAjaran' => $f['ta'],
            'sekolah' => $f['sekolah'],
            'titimangsa' => $f['titimangsa'],
            'logoDataUri' => null,
            'entries' => [
                ['anggota' => $f['anggota1'], 'siswa' => $f['anggota1']->siswa, 'avatarDataUri' => null],
                ['anggota' => $f['anggota2'], 'siswa' => $f['anggota2']->siswa, 'avatarDataUri' => null],
            ],
        ];

        $nilaiPayload = [
            'kelas' => $f['kelas'],
            'tahunAjaran' => $f['ta'],
            'periode' => PeriodeTp::PERIODE_SEMESTER,
            'periodeLabel' => 'Semester',
            'fase' => 'C',
            'sekolah' => $f['sekolah'],
            'titimangsa' => $f['titimangsa'],
            'logoDataUri' => null,
            'entries' => [
                [
                    'anggota' => $f['anggota1'],
                    'siswa' => $f['anggota1']->siswa,
                    'mapelRows' => [['mapel' => $f['mapel'], 'rekap' => $f['rekap']]],
                    'ekskulRows' => [$f['anggotaEkskul']],
                    't2q' => $f['anggotaT2q'],
                    'presensi' => ['sakit' => 1, 'izin' => 0, 'alpa' => 0],
                ],
            ],
        ];

        Model::preventLazyLoading(true);
        try {
            $identitasHtml = view('pdf.rapor.identitas', $identitasPayload)->render();
            $nilaiHtml = view('pdf.rapor.nilai', $nilaiPayload)->render();
            $midPayload = [
                ...$nilaiPayload,
                'periode' => PeriodeTp::PERIODE_MID,
                'periodeLabel' => 'Mid Semester',
            ];
            $midHtml = view('pdf.rapor.nilai', $midPayload)->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        // PDF Identitas memuat Cover, Profil Sekolah & Keterangan Diri Siswa (bukan tabel nilai)
        $this->assertStringContainsString('RAPOR PESERTA DIDIK', $identitasHtml);
        $this->assertStringContainsString('IDENTITAS SATUAN PENDIDIKAN', $identitasHtml);
        $this->assertStringContainsString('KETERANGAN TENTANG DIRI PESERTA DIDIK', $identitasHtml);
        $this->assertStringContainsString('SD Prima Insani', $identitasHtml);
        $this->assertStringContainsString('20209000', $identitasHtml);
        $this->assertStringContainsString('Ahmad Fauzi Pratama', $identitasHtml);
        $this->assertStringContainsString('Zahra Aulia', $identitasHtml);
        $this->assertStringContainsString('Wiraswasta', $identitasHtml);
        $this->assertStringContainsString('Garut, 19 Desember 2026', $identitasHtml);
        $this->assertStringContainsString('H. Ahmad Fauzi, M.Pd', $identitasHtml);
        $this->assertStringNotContainsString('Capaian Kompetensi Mata Pelajaran', $identitasHtml);

        // PDF Nilai Semester memuat Nilai angka & Deskripsi rata kanan-kiri tanpa kop serta tanpa keterangan SAB/BSH
        $this->assertStringContainsString('PENCAPAIAN KOMPETENSI PESERTA DIDIK', $nilaiHtml);
        $this->assertStringContainsString('text-align: justify;', $nilaiHtml);
        $this->assertStringContainsString('<th style="width: 10%;">NILAI</th>', $nilaiHtml);
        $this->assertStringContainsString('Fase C', $nilaiHtml);
        $this->assertStringContainsString('Ganjil (Semester)', $nilaiHtml);
        $this->assertStringContainsString('Matematika', $nilaiHtml);
        $this->assertStringContainsString('>89<', str_replace(["\n", ' '], '', $nilaiHtml));
        $this->assertStringContainsString('Menunjukkan penguasaan baik pada operasi hitung pecahan.', $nilaiHtml);
        $this->assertStringNotContainsString('>CAPAIAN<', $nilaiHtml);
        $this->assertStringNotContainsString('(BSH)', $nilaiHtml);
        $this->assertStringContainsString('Pramuka', $nilaiHtml);
        $this->assertStringContainsString('Jilid 4', $nilaiHtml);
        $this->assertStringContainsString('Ustadz Ridwan, S.Ag', $nilaiHtml);
        $this->assertStringContainsString('1 hari', $nilaiHtml);
        $this->assertStringContainsString('CATATAN WALI KELAS', $nilaiHtml);
        $this->assertStringContainsString('Sari Wulandari, S.Pd', $nilaiHtml);
        $this->assertStringContainsString('Garut, 19 Desember 2026', $nilaiHtml);
        $this->assertStringNotContainsString('<table class="kop-table">', $nilaiHtml);
        $this->assertStringNotContainsString('KETERANGAN TENTANG DIRI PESERTA DIDIK', $nilaiHtml);
        $this->assertStringNotContainsString('IDENTITAS SATUAN PENDIDIKAN', $nilaiHtml);

        // PDF Mid Semester juga menampilkan kolom NILAI angka & DESKRIPSI tanpa predikat/keterangan SAB, BSH dll
        $this->assertStringContainsString('Ganjil (Mid Semester)', $midHtml);
        $this->assertStringContainsString('<th style="width: 10%;">NILAI</th>', $midHtml);
        $this->assertStringContainsString('>89<', str_replace(["\n", ' '], '', $midHtml));
        $this->assertStringContainsString('Menunjukkan penguasaan baik pada operasi hitung pecahan.', $midHtml);
        $this->assertStringNotContainsString('>CAPAIAN<', $midHtml);
        $this->assertStringNotContainsString('(BSH)', $midHtml);

        // Pastikan DomPDF benar-benar bisa merender PDF Identitas, Semester, dan Mid menjadi binary valid (%PDF-)
        $identitasPdfBinary = Pdf::loadView('pdf.rapor.identitas', $identitasPayload)->setPaper('a4', 'portrait')->output();
        $nilaiPdfBinary = Pdf::loadView('pdf.rapor.nilai', $nilaiPayload)->setPaper('a4', 'portrait')->output();
        $midPdfBinary = Pdf::loadView('pdf.rapor.nilai', $midPayload)->setPaper('a4', 'portrait')->output();

        $this->assertStringStartsWith('%PDF-', $identitasPdfBinary);
        $this->assertStringStartsWith('%PDF-', $nilaiPdfBinary);
        $this->assertStringStartsWith('%PDF-', $midPdfBinary);
    }

    public function test_admin_and_wali_kelas_controllers_enforce_scopes_and_render_print_actions(): void
    {
        $f = $this->makeRaporFixtures();

        // 1. Admin Controller + index view + sidebar link
        $admin = $this->makeUserWithRole('Super Admin', 'admin@primainsani.sch.id', null, 'super admin');
        Auth::shouldUse('web');
        Auth::setUser($admin);

        $mockPdfService = $this->createMock(RaporPdfService::class);
        $mockPdfService->expects($this->once())
            ->method('getAdminIndexData')
            ->willReturn([
                'tahunAjaranOptions' => new EloquentCollection([$f['ta']]),
                'kelasOptions' => new EloquentCollection([$f['kelas']]),
                'selectedTahunAjaranId' => 1,
                'selectedTahunAjaran' => $f['ta'],
                'selectedKelas' => $f['kelas'],
                'periode' => PeriodeTp::PERIODE_SEMESTER,
                'periodeOptions' => PeriodeTp::PERIODE_OPTIONS,
                'isSd' => true,
                'sekolah' => $f['sekolah'],
                'titimangsa' => $f['titimangsa'],
                'students' => new EloquentCollection([$f['anggota1']]),
                'rekapCountByAnggota' => [301 => 8],
                'ekskulCountByAnggota' => [301 => 1],
                't2qCountByAnggota' => [301 => 1],
                'presensiByAnggota' => [301 => ['sakit' => 1, 'izin' => 0, 'alpa' => 0]],
                'totalSdMapels' => 8,
            ]);
        $mockPdfService->expects($this->once())
            ->method('streamIdentitasPdf')
            ->with($f['kelas'], null)
            ->willReturn(new Response('%PDF-identitas', 200, ['Content-Type' => 'application/pdf']));
        $mockPdfService->expects($this->once())
            ->method('streamNilaiPdf')
            ->with($f['kelas'], PeriodeTp::PERIODE_SEMESTER, $f['anggota1'])
            ->willReturn(new Response('%PDF-nilai', 200, ['Content-Type' => 'application/pdf']));

        $adminController = new AdminRaporController($mockPdfService);
        $adminIndexView = $adminController->index(Request::create('/penilaian/rapor', 'GET'));

        $this->assertSame('admin.penilaian.rapor.index', $adminIndexView->name());
        $this->assertSame('%PDF-identitas', $adminController->identitasKelas($f['kelas'])->getContent());
        $this->assertSame(
            '%PDF-nilai',
            $adminController->nilaiSiswa(Request::create('/penilaian/rapor/nilai/siswa/301?periode=semester', 'GET'), $f['anggota1'])->getContent()
        );

        Model::preventLazyLoading(true);
        try {
            $adminIndexHtml = $adminIndexView->render();
            $adminSidebarHtml = view('layouts.sidebar')->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('Cetak PDF Rapor Hasil Pembelajaran', $adminIndexHtml);
        $this->assertStringContainsString(route('penilaian-rapor.identitas.kelas', 10), $adminIndexHtml);
        $this->assertStringContainsString(e(route('penilaian-rapor.nilai.kelas', ['kelas' => 10, 'periode' => 'semester'])), $adminIndexHtml);
        $this->assertStringContainsString(route('penilaian-rapor.identitas.siswa', 301), $adminIndexHtml);
        $this->assertStringContainsString('Cetak Rapor PDF', $adminSidebarHtml);
        $this->assertStringContainsString(route('penilaian-rapor.index'), $adminSidebarHtml);

        // 2. Wali Kelas Controller: diizinkan untuk kelas/siswa binaan, ditolak 403 untuk kelas/siswa lain
        $waliUser = $this->makeUserWithRole('Ustadzah Sari', 'sari@primainsani.sch.id', 'NIPY-001', 'wali kelas', $f['wali']);
        Auth::setUser($waliUser);

        $scopeMock = $this->getMockBuilder(WaliKelasScopeService::class)
            ->onlyMethods(['resolveScopeContext'])
            ->getMock();
        $scopeMock->method('resolveScopeContext')->willReturn([
            'isEligible' => true,
            'ineligibleReason' => null,
            'nipy' => 'NIPY-001',
            'karyawanProfile' => $f['wali'],
            'activeTahunAjaran' => $f['ta'],
            'assignedClasses' => new EloquentCollection([$f['kelas']]),
            'selectedKelas' => $f['kelas'],
            'peranDiKelas' => 'Wali Kelas',
        ]);

        $waliPdfMock = $this->createMock(RaporPdfService::class);
        $waliPdfMock->expects($this->once())
            ->method('streamIdentitasPdf')
            ->with($f['kelas'], null)
            ->willReturn(new Response('%PDF-wk-identitas', 200));
        $waliPdfMock->expects($this->once())
            ->method('streamNilaiPdf')
            ->with($f['kelas'], PeriodeTp::PERIODE_MID, $f['anggota1'])
            ->willReturn(new Response('%PDF-wk-nilai', 200));

        $waliController = new WaliKelasRaporController($waliPdfMock, $scopeMock);
        $req = Request::create('/wali-kelas/rapor', 'GET');
        $req->setUserResolver(static fn (): User => $waliUser);

        $this->assertSame('%PDF-wk-identitas', $waliController->identitasKelas($req, $f['kelas'])->getContent());
        $this->assertSame('%PDF-wk-nilai', $waliController->nilaiSiswa($req, $f['anggota1'])->getContent());

        // Kelas milik wali kelas lain harus ditolak 403
        $foreignKelas = Kelas::make(['akademik_master_tahun_ajaran_id' => 1, 'nama_kelas' => '6 Abu Bakar']);
        $foreignKelas->id = 99;
        try {
            $waliController->nilaiKelas($req, $foreignKelas);
            $this->fail('Seharusnya menolak cetak PDF kelas yang tidak diampu.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        // Siswa milik kelas lain harus ditolak 403
        $foreignAnggota = AnggotaKelas::make(['siswa_nis' => '202699', 'akademik_master_kelas_id' => 99]);
        $foreignAnggota->id = 999;
        try {
            $waliController->identitasSiswa($req, $foreignAnggota);
            $this->fail('Seharusnya menolak cetak PDF identitas siswa kelas lain.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_nilai_pdf_separates_tables_by_kelompok_pelajaran_in_urutan_order_without_lazy_loading(): void
    {
        $f = $this->makeRaporFixtures();

        $kelompokA = KelompokPelajaran::make(['kode' => 'A', 'kelompok' => 'Kelompok A (Umum)', 'urutan' => 1]);
        $kelompokA->id = 1;
        $kelompokB = KelompokPelajaran::make(['kode' => 'B', 'kelompok' => 'Kelompok B (Seni & Olahraga)', 'urutan' => 2]);
        $kelompokB->id = 2;
        $kelompokMulok = KelompokPelajaran::make(['kode' => 'MULOK', 'kelompok' => 'Muatan Lokal', 'urutan' => 3]);
        $kelompokMulok->id = 3;
        $kelompokKosong = KelompokPelajaran::make(['kode' => 'PIL', 'kelompok' => 'Kelompok Pilihan', 'urutan' => 4]);
        $kelompokKosong->id = 4;

        $mapelPai = MataPelajaran::make([
            'akademik_pelajaran_kelompok_pelajaran_id' => 1,
            'singkatan' => 'PAI',
            'nama_mata_pelajaran' => 'Pendidikan Agama Islam',
            'urutan' => 1,
        ]);
        $mapelPai->id = 101;
        $mapelPai->setRelation('kelompokPelajaran', $kelompokA);

        $mapelMtk = MataPelajaran::make([
            'akademik_pelajaran_kelompok_pelajaran_id' => 1,
            'singkatan' => 'MTK',
            'nama_mata_pelajaran' => 'Matematika',
            'urutan' => 2,
        ]);
        $mapelMtk->id = 102;
        $mapelMtk->setRelation('kelompokPelajaran', $kelompokA);

        $mapelUnscoredInA = MataPelajaran::make([
            'akademik_pelajaran_kelompok_pelajaran_id' => 1,
            'singkatan' => 'IPAS',
            'nama_mata_pelajaran' => 'IPAS (Belum Diajarkan)',
            'urutan' => 3,
        ]);
        $mapelUnscoredInA->id = 103;
        $mapelUnscoredInA->setRelation('kelompokPelajaran', $kelompokA);

        $mapelSbdp = MataPelajaran::make([
            'akademik_pelajaran_kelompok_pelajaran_id' => 2,
            'singkatan' => 'SBDP',
            'nama_mata_pelajaran' => 'Seni Budaya dan Prakarya',
            'urutan' => 1,
        ]);
        $mapelSbdp->id = 201;
        $mapelSbdp->setRelation('kelompokPelajaran', $kelompokB);

        $mapelSunda = MataPelajaran::make([
            'akademik_pelajaran_kelompok_pelajaran_id' => 3,
            'singkatan' => 'BSUN',
            'nama_mata_pelajaran' => 'Bahasa Sunda',
            'urutan' => 1,
        ]);
        $mapelSunda->id = 301;
        $mapelSunda->setRelation('kelompokPelajaran', $kelompokMulok);

        $mapelPilihanTanpaNilai = MataPelajaran::make([
            'akademik_pelajaran_kelompok_pelajaran_id' => 4,
            'singkatan' => 'ROBOT',
            'nama_mata_pelajaran' => 'Robotika (Tidak Ada Di Kelas Ini)',
            'urutan' => 1,
        ]);
        $mapelPilihanTanpaNilai->id = 401;
        $mapelPilihanTanpaNilai->setRelation('kelompokPelajaran', $kelompokKosong);

        $makeRekap = static fn (int $mapelId, ?float $nilai, string $desc = 'Capaian baik.'): RekapMapel => RekapMapel::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'akademik_pelajaran_mata_pelajaran_id' => $mapelId,
            'anggota_kelas_id' => 301,
            'periode' => PeriodeTp::PERIODE_SEMESTER,
            'nilai_akhir_rapor' => $nilai,
            'deskripsi_capaian' => $desc,
        ]);

        // Sengaja diacak urutannya dan menyertakan mapel tanpa nilai (rekap null & nilai_akhir_rapor null) + mapel bernilai 0
        $shuffledRows = [
            ['mapel' => $mapelSunda, 'rekap' => $makeRekap(301, 0.0, 'Mulai mengikuti pembelajaran bahasa daerah.')],
            ['mapel' => $mapelMtk, 'rekap' => $f['rekap']],
            ['mapel' => $mapelUnscoredInA, 'rekap' => $makeRekap(103, null)],
            ['mapel' => $mapelSbdp, 'rekap' => $makeRekap(201, 91.0)],
            ['mapel' => $mapelPilihanTanpaNilai, 'rekap' => null],
            ['mapel' => $mapelPai, 'rekap' => $makeRekap(101, 94.0)],
        ];

        Model::preventLazyLoading(true);
        try {
            $groupedAll = RaporPdfService::groupMapelRowsByKelompok($shuffledRows);
            $groups = RaporPdfService::filterScoredMapelGroups($groupedAll);

            $this->assertCount(4, $groupedAll);
            $this->assertCount(3, $groups);
            $this->assertSame(['Kelompok A (Umum)', 'Kelompok B (Seni & Olahraga)', 'Muatan Lokal'], array_column($groups, 'label'));
            $this->assertSame(['Pendidikan Agama Islam', 'Matematika'], array_map(
                static fn (array $r): string => $r['mapel']->nama_mata_pelajaran,
                $groups[0]['rows']
            ));
            $this->assertSame(['Seni Budaya dan Prakarya'], array_map(
                static fn (array $r): string => $r['mapel']->nama_mata_pelajaran,
                $groups[1]['rows']
            ));
            $this->assertSame(['Bahasa Sunda'], array_map(
                static fn (array $r): string => $r['mapel']->nama_mata_pelajaran,
                $groups[2]['rows']
            ));

            $payload = [
                'kelas' => $f['kelas'],
                'tahunAjaran' => $f['ta'],
                'periode' => PeriodeTp::PERIODE_SEMESTER,
                'periodeLabel' => 'Semester',
                'fase' => 'C',
                'sekolah' => $f['sekolah'],
                'titimangsa' => $f['titimangsa'],
                'logoDataUri' => null,
                'entries' => [
                    [
                        'anggota' => $f['anggota1'],
                        'siswa' => $f['anggota1']->siswa,
                        'mapelRows' => $shuffledRows,
                        'mapelGroups' => $groupedAll,
                        'ekskulRows' => [$f['anggotaEkskul']],
                        't2q' => $f['anggotaT2q'],
                        'presensi' => ['sakit' => 1, 'izin' => 0, 'alpa' => 0],
                    ],
                ],
            ];

            $html = view('pdf.rapor.nilai', $payload)->render();
            $midHtml = view('pdf.rapor.nilai', [
                ...$payload,
                'periode' => PeriodeTp::PERIODE_MID,
                'periodeLabel' => 'Mid Semester',
            ])->render();
            // Fallback ketika view hanya menerima mapelRows tanpa mapelGroups juga harus memfilter mapel tanpa nilai & anti-N+1
            $fallbackPayload = $payload;
            unset($fallbackPayload['entries'][0]['mapelGroups']);
            $fallbackHtml = view('pdf.rapor.nilai', $fallbackPayload)->render();

            $emptyScorePayload = $payload;
            $emptyScorePayload['entries'][0]['mapelGroups'] = [
                [
                    'kelompok' => $kelompokKosong,
                    'kode' => 'PIL',
                    'label' => 'Kelompok Pilihan',
                    'urutan' => 4,
                    'rows' => [['mapel' => $mapelPilihanTanpaNilai, 'rekap' => null]],
                ],
            ];
            $emptyScoreHtml = view('pdf.rapor.nilai', $emptyScorePayload)->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        // Mapel & kelompok tanpa nilai tidak boleh muncul di rapor
        $this->assertStringNotContainsString('IPAS (Belum Diajarkan)', $html);
        $this->assertStringNotContainsString('Robotika (Tidak Ada Di Kelas Ini)', $html);
        $this->assertStringNotContainsString('Kelompok Pilihan', $html);
        $this->assertStringNotContainsString('IPAS (Belum Diajarkan)', $fallbackHtml);
        $this->assertStringNotContainsString('Robotika (Tidak Ada Di Kelas Ini)', $fallbackHtml);
        $this->assertStringContainsString('Belum ada mata pelajaran yang memiliki nilai.', $emptyScoreHtml);
        $this->assertStringContainsString('Bahasa Sunda', $html);

        // Isolasi bagian kelompok pelajaran (sebelum D. EKSTRAKURIKULER) untuk memverifikasi 3 tabel terpisah & penomoran mulai dari 1 tiap tabel
        $this->assertStringContainsString('size: A4 portrait;', $html);
        $this->assertStringContainsString('border-left: none;', $html);
        $this->assertStringContainsString('border-right: none;', $html);
        $this->assertStringNotContainsString('border-left: 3px', $html);
        $this->assertStringContainsString('class="meta-box"', $html);
        $this->assertStringNotContainsString('score-pill', $html);
        $this->assertStringContainsString('<td class="font-bold" style="vertical-align: middle;">Matematika</td>', $html);
        $this->assertStringNotContainsString('CAPAIAN KOMPETENSI MATA PELAJARAN', $html);

        $secStart = strpos($html, 'PENCAPAIAN KOMPETENSI PESERTA DIDIK');
        $secEkskulStart = strpos($html, '<!-- EKSTRAKURIKULER -->');
        $this->assertNotFalse($secStart);
        $this->assertNotFalse($secEkskulStart);
        $sectionMapelHtml = substr($html, $secStart, $secEkskulStart - $secStart);

        $this->assertSame(3, substr_count($sectionMapelHtml, 'class="rapor-table rapor-table--mapel"'));
        $this->assertSame(3, substr_count($midHtml, 'class="rapor-table rapor-table--mapel"'));
        $this->assertSame(3, substr_count($fallbackHtml, 'class="rapor-table rapor-table--mapel"'));
        $this->assertSame(3, substr_count($sectionMapelHtml, 'class="section-heading uppercase"'));
        $this->assertSame(3, substr_count($sectionMapelHtml, '>1</td>'));
        $this->assertSame(3, substr_count($sectionMapelHtml, '<th style="width: 10%;">NILAI</th>'));
        $this->assertSame(3, substr_count($midHtml, '<th style="width: 10%;">NILAI</th>'));
        $this->assertStringContainsString('>89<', str_replace(["\n", ' '], '', $midHtml));
        $this->assertStringNotContainsString('>CAPAIAN<', $sectionMapelHtml);
        $this->assertStringNotContainsString('>CAPAIAN<', $midHtml);
        $this->assertStringNotContainsString('(BSH)', $midHtml);

        $posA = strpos($sectionMapelHtml, 'A. Kelompok A (Umum)');
        $posB = strpos($sectionMapelHtml, 'B. Kelompok B (Seni &amp; Olahraga)');
        $posMulok = strpos($sectionMapelHtml, 'C. Muatan Lokal');
        $this->assertNotFalse($posA);
        $this->assertNotFalse($posB);
        $this->assertNotFalse($posMulok);
        $this->assertLessThan($posB, $posA);
        $this->assertLessThan($posMulok, $posB);

        $this->assertStringContainsString('D. EKSTRAKURIKULER', $html);
        $this->assertStringContainsString("E. PROGRAM TAHSIN &amp; TAHFIDZ AL-QUR'AN (T2Q)", $html);
        $this->assertStringContainsString('F. KETIDAKHADIRAN', $html);
        $this->assertStringContainsString('G. CATATAN WALI KELAS', $html);

        $noT2qPayload = $payload;
        $noT2qPayload['entries'][0]['t2q'] = null;
        $noT2qHtml = view('pdf.rapor.nilai', $noT2qPayload)->render();
        $this->assertStringContainsString('D. EKSTRAKURIKULER', $noT2qHtml);
        $this->assertStringContainsString('E. KETIDAKHADIRAN', $noT2qHtml);
        $this->assertStringContainsString('F. CATATAN WALI KELAS', $noT2qHtml);

        $pdfBinary = Pdf::loadView('pdf.rapor.nilai', $payload)->setPaper('a4', 'portrait')->output();
        $this->assertStringStartsWith('%PDF-', $pdfBinary);
    }
}
