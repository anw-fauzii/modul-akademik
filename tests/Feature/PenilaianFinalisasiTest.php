<?php

namespace Tests\Feature;

use App\Models\Admin\DataMaster\AnggotaEkstrakurikuler;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\AnggotaT2q;
use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Sekolah;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Pelajaran\JadwalPelajaran;
use App\Models\Admin\Pelajaran\JamPelajaran;
use App\Models\Admin\Pelajaran\KelompokPelajaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Admin\Penilaian\AspekTp;
use App\Models\Admin\Penilaian\PenilaianFinalisasi;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Models\Admin\Penilaian\RekapMapel;
use App\Models\Admin\Presensi\PresensiSiswa;
use App\Models\Admin\T2q\YaumiyahTahsin;
use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use App\Models\User;
use App\Services\Admin\DataMaster\AnggotaT2qService;
use App\Services\Admin\Penilaian\PenilaianFinalisasiService;
use App\Services\Admin\Penilaian\PenilaianTpService;
use App\Services\Admin\Penilaian\RaporPdfService;
use App\Services\Admin\Presensi\PresensiSiswaService;
use App\Services\Admin\T2q\YaumiyahTahsinService;
use App\Services\WaliKelas\WaliKelasEkstrakurikulerService;
use App\Support\Sqid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PenilaianFinalisasiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['auth.host' => 'https://sso.test']);
        Http::preventStrayRequests();

        if (! Schema::hasTable('kepegawaian_master_karyawan')) {
            Schema::create('kepegawaian_master_karyawan', function (Blueprint $table): void {
                $table->string('nipy')->primary();
                $table->string('nama_lengkap');
                $table->string('gelar')->nullable();
                $table->string('jabatan')->nullable();
                $table->string('unit')->nullable();
                $table->timestamps();
            });
        }
    }

    private function seedRoles(): void
    {
        Role::findOrCreate('super admin', 'web');
        Role::findOrCreate('wali kelas', 'web');
    }

    /**
     * @return array<string, mixed>
     */
    private function seedOptionCFixtures(): array
    {
        $this->seedRoles();

        $ta = TahunAjaran::create([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);

        Karyawan::create([
            'nipy' => 'NIPY-WALI',
            'nama_lengkap' => 'Sari Wulandari',
            'gelar' => 'S.Pd',
            'jabatan' => 'Wali Kelas',
            'unit' => 'SD',
        ]);
        Karyawan::create([
            'nipy' => 'NIPY-GURU',
            'nama_lengkap' => 'Budi Santoso',
            'gelar' => 'S.Ag',
            'jabatan' => 'Guru Mapel',
            'unit' => 'SD',
        ]);
        Karyawan::create([
            'nipy' => 'NIPY-PEMBINA',
            'nama_lengkap' => 'Rizky Pratama',
            'gelar' => 'S.Or',
            'jabatan' => 'Pembina Ekskul',
            'unit' => 'SD',
        ]);
        Karyawan::create([
            'nipy' => 'NIPY-T2Q',
            'nama_lengkap' => 'Ustadz Ridwan',
            'gelar' => 'S.Pd.I',
            'jabatan' => 'Guru T2Q',
            'unit' => 'SD',
        ]);

        $admin = User::factory()->create(['name' => 'Admin Akademik', 'email' => 'admin@test.local', 'no_induk' => 'ADM-1']);
        $admin->assignRole('super admin');

        $waliUser = User::factory()->create(['name' => 'Wali Kelas', 'email' => 'wali@test.local', 'no_induk' => 'NIPY-WALI']);
        $waliUser->assignRole('wali kelas');

        $guruMapelUser = User::factory()->create(['name' => 'Guru Mapel', 'email' => 'guru@test.local', 'no_induk' => 'NIPY-GURU']);
        $guruMapelUser->assignRole('wali kelas');

        $pembinaUser = User::factory()->create(['name' => 'Pembina Ekskul', 'email' => 'pembina@test.local', 'no_induk' => 'NIPY-PEMBINA']);
        $pembinaUser->assignRole('wali kelas');

        $guruT2qUser = User::factory()->create(['name' => 'Guru T2Q', 'email' => 't2q@test.local', 'no_induk' => 'NIPY-T2Q']);
        $guruT2qUser->assignRole('wali kelas');

        Sekolah::create([
            'jenjang_kelas' => 'SD',
            'nama_yayasan' => 'Yayasan Prima Insani',
            'nama_sekolah' => 'SD Prima Insani',
            'npsn' => '20209000',
            'status' => true,
        ]);

        $kelasA = Kelas::create([
            'akademik_master_tahun_ajaran_id' => $ta->id,
            'karyawan_nipy' => 'NIPY-WALI',
            'tingkatan_kelas' => '5',
            'nama_kelas' => '5 Al-Fatihah',
            'jenjang_kelas' => 'SD',
        ]);

        $kelasB = Kelas::create([
            'akademik_master_tahun_ajaran_id' => $ta->id,
            'karyawan_nipy' => 'NIPY-GURU',
            'tingkatan_kelas' => '5',
            'nama_kelas' => '5 Al-Ikhlas',
            'jenjang_kelas' => 'SD',
        ]);

        $siswa = Siswa::create([
            'nis' => '20260001',
            'nama_lengkap' => 'Nayla Azzahra',
            'nisn' => '0123456789',
            'jenis_kelamin' => 'P',
            'status' => 'Aktif',
        ]);

        $siswaB = Siswa::create([
            'nis' => '20260002',
            'nama_lengkap' => 'Rafiq Al-Fatih',
            'nisn' => '0123456790',
            'jenis_kelamin' => 'L',
            'status' => 'Aktif',
        ]);

        $anggotaA = AnggotaKelas::create([
            'siswa_nis' => $siswa->nis,
            'akademik_master_kelas_id' => $kelasA->id,
            'pendaftaran' => 'Naik Kelas',
        ]);

        $anggotaB = AnggotaKelas::create([
            'siswa_nis' => $siswaB->nis,
            'akademik_master_kelas_id' => $kelasB->id,
            'pendaftaran' => 'Naik Kelas',
        ]);

        $kelompok = KelompokPelajaran::create([
            'kode' => 'A',
            'kelompok' => 'Kelompok A',
            'urutan' => 1,
        ]);

        $mapelPai = MataPelajaran::create([
            'akademik_pelajaran_kelompok_pelajaran_id' => $kelompok->id,
            'singkatan' => 'PAI',
            'nama_mata_pelajaran' => 'Pendidikan Agama Islam',
            'sifat' => 'Wajib',
            'unit' => 'SD',
            'urutan' => 1,
            'status' => true,
        ]);

        // Mapel IPS tidak dijadwalkan di kelas 5A sehingga tidak memblokir rapor
        $mapelUnscheduled = MataPelajaran::create([
            'akademik_pelajaran_kelompok_pelajaran_id' => $kelompok->id,
            'singkatan' => 'IPS',
            'nama_mata_pelajaran' => 'Ilmu Pengetahuan Sosial',
            'sifat' => 'Wajib',
            'unit' => 'SD',
            'urutan' => 2,
            'status' => true,
        ]);

        $jam = JamPelajaran::create([
            'jam_ke' => 1,
            'unit' => 'SD',
            'jam_mulai' => '07:00',
            'jam_selesai' => '07:35',
        ]);

        JadwalPelajaran::create([
            'akademik_master_tahun_ajaran_id' => $ta->id,
            'akademik_master_kelas_id' => $kelasA->id,
            'akademik_pelajaran_jam_pelajaran_id' => $jam->id,
            'akademik_pelajaran_mata_pelajaran_id' => $mapelPai->id,
            'karyawan_nipy' => 'NIPY-GURU',
            'hari' => 1,
            'status' => true,
        ]);

        JadwalPelajaran::create([
            'akademik_master_tahun_ajaran_id' => $ta->id,
            'akademik_master_kelas_id' => $kelasB->id,
            'akademik_pelajaran_jam_pelajaran_id' => $jam->id,
            'akademik_pelajaran_mata_pelajaran_id' => $mapelPai->id,
            'karyawan_nipy' => 'NIPY-GURU',
            'hari' => 2,
            'status' => true,
        ]);

        $tp = TujuanPembelajaran::create([
            'akademik_master_tahun_ajaran_id' => $ta->id,
            'akademik_pelajaran_mata_pelajaran_id' => $mapelPai->id,
            'tingkatan_kelas' => '5',
            'kode_tp' => '5.1',
            'tujuan_pembelajaran' => 'Memahami rukun iman',
            'status' => true,
            'urutan' => 1,
        ]);

        $aspek = AspekTp::create([
            'tujuan_pembelajaran_id' => $tp->id,
            'periode' => PeriodeTp::PERIODE_MID,
            'nama_aspek' => 'Penilaian Harian',
            'bobot' => 100,
            'masuk_hitung_na_tp' => true,
            'urutan' => 1,
        ]);

        $aspekSem = AspekTp::create([
            'tujuan_pembelajaran_id' => $tp->id,
            'periode' => PeriodeTp::PERIODE_SEMESTER,
            'nama_aspek' => 'Penilaian Semester',
            'bobot' => 100,
            'masuk_hitung_na_tp' => true,
            'urutan' => 1,
        ]);

        $ekskulPilihan = Ekstrakurikuler::create([
            'akademik_master_tahun_ajaran_id' => $ta->id,
            'karyawan_nipy' => 'NIPY-PEMBINA',
            'nama_ekstrakurikuler' => 'Futsal',
            'biaya' => 0,
            'unit' => 'SD',
            'sifat' => Ekstrakurikuler::SIFAT_PILIHAN,
            'kkm' => 75,
        ]);

        $anggotaEkskul = AnggotaEkstrakurikuler::create([
            'anggota_kelas_id' => $anggotaA->id,
            'akademik_master_ekstrakuler_id' => $ekskulPilihan->id,
            'pendaftaran' => 'Baru',
            'nilai' => 88,
            'predikat' => 'SAB',
        ]);

        $anggotaT2q = AnggotaT2q::create([
            'anggota_kelas_id' => $anggotaA->id,
            'karyawan_nipy' => 'NIPY-T2Q',
            'tingkat' => '5',
            'pendaftaran' => 'Lanjutan',
            'keterangan' => 'Jilid 5 lancar',
        ]);

        $presensi = PresensiSiswa::create([
            'anggota_kelas_id' => $anggotaA->id,
            'tanggal' => now()->toDateString(),
            'status' => 'sakit',
        ]);

        return compact(
            'ta',
            'admin',
            'waliUser',
            'guruMapelUser',
            'pembinaUser',
            'guruT2qUser',
            'kelasA',
            'kelasB',
            'siswa',
            'anggotaA',
            'anggotaB',
            'mapelPai',
            'mapelUnscheduled',
            'tp',
            'aspek',
            'aspekSem',
            'ekskulPilihan',
            'anggotaEkskul',
            'anggotaT2q',
            'presensi'
        );
    }

    public function test_ownership_permissions_enforced_for_finalization_and_unlock(): void
    {
        $f = $this->seedOptionCFixtures();
        $service = app(PenilaianFinalisasiService::class);

        $mapelParams = [
            'jenis_komponen' => PenilaianFinalisasi::JENIS_MAPEL,
            'tahun_ajaran_id' => $f['ta']->id,
            'kelas_id' => $f['kelasA']->id,
            'mata_pelajaran_id' => $f['mapelPai']->id,
            'periode' => PeriodeTp::PERIODE_MID,
        ];

        // Wali kelas 5A TIDAK mengajar PAI di 5A -> ditolak (hanya lihat)
        $this->assertTrue(Gate::forUser($f['waliUser'])->denies('manageComponent', [PenilaianFinalisasi::class, $mapelParams]));
        // Guru pengampu PAI di 5A & Super Admin -> diizinkan
        $this->assertTrue(Gate::forUser($f['guruMapelUser'])->allows('manageComponent', [PenilaianFinalisasi::class, $mapelParams]));
        $this->assertTrue(Gate::forUser($f['admin'])->allows('manageComponent', [PenilaianFinalisasi::class, $mapelParams]));

        // Pramuka Wajib & Presensi di 5A: hanya Wali Kelas 5A atau Super Admin
        $wajibParams = [
            'jenis_komponen' => PenilaianFinalisasi::JENIS_EKSKUL_WAJIB,
            'tahun_ajaran_id' => $f['ta']->id,
            'kelas_id' => $f['kelasA']->id,
            'periode' => PeriodeTp::PERIODE_MID,
        ];
        $this->assertTrue(Gate::forUser($f['waliUser'])->allows('manageComponent', [PenilaianFinalisasi::class, $wajibParams]));
        $this->assertTrue(Gate::forUser($f['guruMapelUser'])->denies('manageComponent', [PenilaianFinalisasi::class, $wajibParams]));

        // Ekskul Pilihan Futsal: hanya Pembina Futsal atau Super Admin
        $pilihanParams = [
            'jenis_komponen' => PenilaianFinalisasi::JENIS_EKSKUL_PILIHAN,
            'tahun_ajaran_id' => $f['ta']->id,
            'ekstrakurikuler_id' => $f['ekskulPilihan']->id,
            'periode' => PeriodeTp::PERIODE_MID,
        ];
        $this->assertTrue(Gate::forUser($f['pembinaUser'])->allows('manageComponent', [PenilaianFinalisasi::class, $pilihanParams]));
        $this->assertTrue(Gate::forUser($f['waliUser'])->denies('manageComponent', [PenilaianFinalisasi::class, $pilihanParams]));

        // T2Q kelas 5A: hanya Guru T2Q atau Super Admin (bukan Wali Kelas non-T2Q)
        $t2qParams = [
            'jenis_komponen' => PenilaianFinalisasi::JENIS_T2Q,
            'tahun_ajaran_id' => $f['ta']->id,
            'kelas_id' => $f['kelasA']->id,
            'periode' => PeriodeTp::PERIODE_MID,
        ];
        $this->assertTrue(Gate::forUser($f['guruT2qUser'])->allows('manageComponent', [PenilaianFinalisasi::class, $t2qParams]));
        $this->assertTrue(Gate::forUser($f['waliUser'])->denies('manageComponent', [PenilaianFinalisasi::class, $t2qParams]));

        // Finalize oleh guru pengampu lalu unlock wajib alasan >= 5 karakter (oleh admin & guru pengampu)
        $finalRec = $service->finalize($f['guruMapelUser'], $mapelParams);
        $this->assertTrue($finalRec->isFinal());

        try {
            $service->unlock($f['guruMapelUser'], $mapelParams, 'ok');
            $this->fail('Seharusnya menolak alasan unlock < 5 karakter.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('unlock_reason', $e->errors());
        }

        $unlockedByGuru = $service->unlock($f['guruMapelUser'], $mapelParams, 'Revisi nilai susulan');
        $this->assertFalse($unlockedByGuru->isFinal());
        $this->assertSame('Revisi nilai susulan', $unlockedByGuru->unlock_reason);
    }

    public function test_option_c_hard_blocks_rapor_printing_and_separates_mid_vs_semester(): void
    {
        $f = $this->seedOptionCFixtures();
        $finalService = app(PenilaianFinalisasiService::class);
        $pdfService = app(RaporPdfService::class);

        $readinessMid = $finalService->getClassReadiness($f['kelasA'], PeriodeTp::PERIODE_MID, $f['waliUser']);
        // 5 komponen wajib untuk 5A: Mapel PAI (bukan IPS yang tidak dijadwalkan), Pramuka Wajib, Futsal, T2Q, Presensi
        $this->assertSame(5, $readinessMid['total_components']);
        $this->assertSame(0, $readinessMid['finalized_count']);
        $this->assertFalse($readinessMid['is_ready']);

        // Identitas Rapor tetap boleh dicetak
        $identitasResponse = $pdfService->streamIdentitasPdf($f['kelasA'], $f['anggotaA']);
        $this->assertStringStartsWith('%PDF-', $identitasResponse->getContent());

        // Nilai Rapor HARUS diblokir dengan HTTP 422
        try {
            $pdfService->streamNilaiPdf($f['kelasA'], PeriodeTp::PERIODE_MID, $f['anggotaA']);
            $this->fail('Cetak PDF Nilai Rapor harus diblokir sebelum seluruh komponen final.');
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
            $this->assertStringContainsString('diblokir sementara', $e->getMessage());
        }

        // Finalize 4 dari 5 komponen Mid -> masih tetap diblokir
        $finalService->finalize($f['guruMapelUser'], [
            'jenis_komponen' => PenilaianFinalisasi::JENIS_MAPEL,
            'tahun_ajaran_id' => $f['ta']->id,
            'kelas_id' => $f['kelasA']->id,
            'mata_pelajaran_id' => $f['mapelPai']->id,
            'periode' => PeriodeTp::PERIODE_MID,
        ]);
        $finalService->finalize($f['waliUser'], [
            'jenis_komponen' => PenilaianFinalisasi::JENIS_EKSKUL_WAJIB,
            'tahun_ajaran_id' => $f['ta']->id,
            'kelas_id' => $f['kelasA']->id,
            'periode' => PeriodeTp::PERIODE_MID,
        ]);
        $finalService->finalize($f['pembinaUser'], [
            'jenis_komponen' => PenilaianFinalisasi::JENIS_EKSKUL_PILIHAN,
            'tahun_ajaran_id' => $f['ta']->id,
            'ekstrakurikuler_id' => $f['ekskulPilihan']->id,
            'periode' => PeriodeTp::PERIODE_MID,
        ]);
        $finalService->finalize($f['guruT2qUser'], [
            'jenis_komponen' => PenilaianFinalisasi::JENIS_T2Q,
            'tahun_ajaran_id' => $f['ta']->id,
            'kelas_id' => $f['kelasA']->id,
            'periode' => PeriodeTp::PERIODE_MID,
        ]);

        try {
            $pdfService->streamNilaiPdf($f['kelasA'], PeriodeTp::PERIODE_MID, $f['anggotaA']);
            $this->fail('Harus tetap diblokir selama Presensi belum Final.');
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }

        // Finalize komponen ke-5 (Presensi Mid) -> Mid menjadi siap cetak, Semester masih Draft/terblokir!
        $finalService->finalize($f['waliUser'], [
            'jenis_komponen' => PenilaianFinalisasi::JENIS_PRESENSI,
            'tahun_ajaran_id' => $f['ta']->id,
            'kelas_id' => $f['kelasA']->id,
            'periode' => PeriodeTp::PERIODE_MID,
        ]);

        $readinessMidAfter = $finalService->getClassReadiness($f['kelasA'], PeriodeTp::PERIODE_MID, $f['waliUser']);
        $readinessSem = $finalService->getClassReadiness($f['kelasA'], PeriodeTp::PERIODE_SEMESTER, $f['waliUser']);
        $this->assertTrue($readinessMidAfter['is_ready']);
        $this->assertFalse($readinessSem['is_ready']);

        $midPdfResponse = $pdfService->streamNilaiPdf($f['kelasA'], PeriodeTp::PERIODE_MID, $f['anggotaA']);
        $this->assertStringStartsWith('%PDF-', $midPdfResponse->getContent());

        try {
            $pdfService->streamNilaiPdf($f['kelasA'], PeriodeTp::PERIODE_SEMESTER, $f['anggotaA']);
            $this->fail('Periode Semester harus tetap terblokir meski Mid Semester sudah final.');
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }
    }

    public function test_write_locks_protect_mapel_ekskul_t2q_and_presensi_once_finalized(): void
    {
        $f = $this->seedOptionCFixtures();
        $finalService = app(PenilaianFinalisasiService::class);
        $tpService = app(PenilaianTpService::class);
        $ekskulService = app(WaliKelasEkstrakurikulerService::class);
        $presensiService = app(PresensiSiswaService::class);
        $t2qService = app(AnggotaT2qService::class);

        // 1. MAPEL LOCK
        $mapelMidParams = [
            'jenis_komponen' => PenilaianFinalisasi::JENIS_MAPEL,
            'tahun_ajaran_id' => $f['ta']->id,
            'kelas_id' => $f['kelasA']->id,
            'mata_pelajaran_id' => $f['mapelPai']->id,
            'periode' => PeriodeTp::PERIODE_MID,
        ];
        $finalService->finalize($f['guruMapelUser'], $mapelMidParams);

        // Input nilai di Kelas 5A Mid -> diblokir
        try {
            $tpService->saveNilaiGrid($f['kelasA'], $f['mapelPai'], PeriodeTp::PERIODE_MID, [
                $f['anggotaA']->id => [$f['aspek']->id => 90],
            ]);
            $this->fail('Simpan nilai grid Mapel Mid yang sudah final harus ditolak.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('finalisasi', $e->errors());
        }

        // Input aspek / sumatif / toggle TP di periode Mid pada tingkatan 5 -> diblokir
        try {
            $tpService->saveAspek($f['tp'], [
                'periode' => PeriodeTp::PERIODE_MID,
                'nama_aspek' => 'Proyek Baru',
                'bobot' => 100,
            ]);
            $this->fail('Menambah aspek pada tingkatan yang punya kelas Mid Final harus ditolak.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('finalisasi', $e->errors());
        }

        // Kelas paralel 5B Mid & Kelas 5A Semester -> TETAP BISA diinput!
        $resB = $tpService->saveNilaiGrid($f['kelasB'], $f['mapelPai'], PeriodeTp::PERIODE_MID, [
            $f['anggotaB']->id => [$f['aspek']->id => 85],
        ]);
        $this->assertSame(1, $resB['saved_cells']);

        $resSem = $tpService->saveNilaiGrid($f['kelasA'], $f['mapelPai'], PeriodeTp::PERIODE_SEMESTER, [
            $f['anggotaA']->id => [$f['aspekSem']->id => 92],
        ]);
        $this->assertSame(1, $resSem['saved_cells']);

        // Deskripsi rekap Mid -> diblokir
        $rekapMid = RekapMapel::create([
            'akademik_master_tahun_ajaran_id' => $f['ta']->id,
            'akademik_pelajaran_mata_pelajaran_id' => $f['mapelPai']->id,
            'anggota_kelas_id' => $f['anggotaA']->id,
            'periode' => PeriodeTp::PERIODE_MID,
            'nilai_akhir_rapor' => 90,
        ]);
        try {
            $tpService->updateDeskripsi($rekapMid, 'Deskripsi edit manual');
            $this->fail('Edit deskripsi Mid final harus ditolak.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('finalisasi', $e->errors());
        }

        // Setelah Admin buka kunci -> kembali dapat diedit
        $finalService->unlock($f['admin'], $mapelMidParams, 'Perlu perbaikan nilai praktik');
        $resAfterUnlock = $tpService->saveNilaiGrid($f['kelasA'], $f['mapelPai'], PeriodeTp::PERIODE_MID, [
            $f['anggotaA']->id => [$f['aspek']->id => 95],
        ]);
        $this->assertSame(1, $resAfterUnlock['saved_cells']);

        // 2. EKSKUL WAJIB & PILIHAN LOCK
        $finalService->finalize($f['waliUser'], [
            'jenis_komponen' => PenilaianFinalisasi::JENIS_EKSKUL_WAJIB,
            'tahun_ajaran_id' => $f['ta']->id,
            'kelas_id' => $f['kelasA']->id,
            'periode' => PeriodeTp::PERIODE_MID,
        ]);
        try {
            $ekskulService->savePenilaianWajibKelas($f['kelasA'], [
                'periode' => PeriodeTp::PERIODE_MID,
                'nilai' => [$f['anggotaA']->id => 88],
            ]);
            $this->fail('Simpan nilai Pramuka Wajib Mid final harus ditolak.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('finalisasi', $e->errors());
        }
        $savedSemWajib = $ekskulService->savePenilaianWajibKelas($f['kelasA'], [
            'periode' => PeriodeTp::PERIODE_SEMESTER,
            'nilai' => [$f['anggotaA']->id => 90],
        ]);
        $this->assertSame(1, $savedSemWajib);

        $finalService->finalize($f['pembinaUser'], [
            'jenis_komponen' => PenilaianFinalisasi::JENIS_EKSKUL_PILIHAN,
            'tahun_ajaran_id' => $f['ta']->id,
            'ekstrakurikuler_id' => $f['ekskulPilihan']->id,
            'periode' => PeriodeTp::PERIODE_MID,
        ]);
        try {
            $ekskulService->savePenilaian($f['ekskulPilihan'], [
                'periode' => PeriodeTp::PERIODE_MID,
                'nilai' => [$f['anggotaEkskul']->id => 80],
            ]);
            $this->fail('Simpan nilai Ekskul Pilihan Mid final harus ditolak.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('finalisasi', $e->errors());
        }

        // 3. PRESENSI DATE-AWARE & SEMESTER LOCK
        $finalService->finalize($f['waliUser'], [
            'jenis_komponen' => PenilaianFinalisasi::JENIS_PRESENSI,
            'tahun_ajaran_id' => $f['ta']->id,
            'kelas_id' => $f['kelasA']->id,
            'periode' => PeriodeTp::PERIODE_MID,
        ]);
        try {
            $presensiService->update($f['presensi'], [
                'anggota_kelas_id' => $f['anggotaA']->id,
                'tanggal' => now()->toDateString(),
                'status' => 'izin',
            ]);
            $this->fail('Ubah presensi pada tanggal sebelum/saat finalisasi Mid harus ditolak.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('finalisasi', $e->errors());
        }
        // Tanggal pasca-Mid tetap diizinkan selama Semester masih Draft
        $futureCount = $presensiService->storeBulk([
            'tanggal' => now()->addDays(2)->toDateString(),
            'items' => [['anggota_kelas_id' => $f['anggotaA']->id, 'status' => 'hadir']],
        ]);
        $this->assertSame(1, $futureCount);

        // Setelah Presensi Semester Final -> tanggal masa depan pun ikut dikunci
        $finalService->finalize($f['waliUser'], [
            'jenis_komponen' => PenilaianFinalisasi::JENIS_PRESENSI,
            'tahun_ajaran_id' => $f['ta']->id,
            'kelas_id' => $f['kelasA']->id,
            'periode' => PeriodeTp::PERIODE_SEMESTER,
        ]);
        try {
            $presensiService->storeBulk([
                'tanggal' => now()->addDays(5)->toDateString(),
                'items' => [['anggota_kelas_id' => $f['anggotaA']->id, 'status' => 'hadir']],
            ]);
            $this->fail('Presensi Semester final harus mengunci seluruh tanggal.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('finalisasi', $e->errors());
        }

        // 4. T2Q LOCK (AnggotaT2q & Yaumiyah)
        $finalService->finalize($f['guruT2qUser'], [
            'jenis_komponen' => PenilaianFinalisasi::JENIS_T2Q,
            'tahun_ajaran_id' => $f['ta']->id,
            'kelas_id' => $f['kelasA']->id,
            'periode' => PeriodeTp::PERIODE_MID,
        ]);
        try {
            $t2qService->update($f['anggotaT2q'], [
                'anggota_kelas_id' => $f['anggotaA']->id,
                'karyawan_nipy' => 'NIPY-T2Q',
                'tingkat' => '5',
                'pendaftaran' => 'Lanjutan',
                'keterangan' => 'Edit ilegal saat Mid Final',
            ]);
            $this->fail('Edit Anggota T2Q tanpa flag semester saat Mid Final harus ditolak.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('finalisasi', $e->errors());
        }
        // Dengan flag for_semester_update saat hanya Mid yang Final -> berhasil!
        $t2qService->update($f['anggotaT2q'], [
            'anggota_kelas_id' => $f['anggotaA']->id,
            'karyawan_nipy' => 'NIPY-T2Q',
            'tingkat' => '5',
            'pendaftaran' => 'Lanjutan',
            'keterangan' => 'Update semester',
            'for_semester_update' => true,
        ]);
        // Yaumiyah T2Q date-aware lock pada tanggal <= Mid Final
        $yaumiyahService = app(YaumiyahTahsinService::class);
        $entry = YaumiyahTahsin::create([
            'anggota_t2q_id' => $f['anggotaT2q']->id,
            'tanggal' => now()->toDateString(),
            'nilai' => 88,
        ]);
        try {
            $yaumiyahService->delete($entry);
            $this->fail('Hapus catatan Yaumiyah T2Q pada rentang Mid Final harus ditolak.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('finalisasi', $e->errors());
        }
    }

    public function test_views_render_finalization_banners_and_readiness_without_lazy_loading(): void
    {
        $f = $this->seedOptionCFixtures();
        $this->actingAs($f['guruMapelUser']);

        $tpPayload = app(PenilaianTpService::class)->getMatrixData([
            'tahun_ajaran_id' => $f['ta']->id,
            'kelas_id' => $f['kelasA']->id,
            'mata_pelajaran_id' => $f['mapelPai']->id,
            'periode' => PeriodeTp::PERIODE_MID,
        ]);
        $rekapPayload = app(PenilaianTpService::class)->getRekapData([
            'tahun_ajaran_id' => $f['ta']->id,
            'kelas_id' => $f['kelasA']->id,
            'mata_pelajaran_id' => $f['mapelPai']->id,
            'periode' => PeriodeTp::PERIODE_MID,
        ]);
        $raporPayload = app(RaporPdfService::class)->getAdminIndexData([
            'tahun_ajaran_id' => $f['ta']->id,
            'kelas_id' => $f['kelasA']->id,
            'periode' => PeriodeTp::PERIODE_MID,
        ]);
        $ekskulPayload = app(WaliKelasEkstrakurikulerService::class)->getPageData($f['waliUser'], null, $f['kelasA']->id, 'wajib', 'mid');

        Model::preventLazyLoading(true);
        try {
            $tpHtml = view('admin.penilaian.nilaiTp.index', $tpPayload)->render();
            $rekapHtml = view('admin.penilaian.rekap.index', $rekapPayload)->render();
            $raporHtml = view('admin.penilaian.rapor.index', $raporPayload)->render();
            $ekskulHtml = view('waliKelas.ekstrakurikuler.index', $ekskulPayload)->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('DRAFT (BELUM FINAL)', $tpHtml);
        $this->assertStringContainsString('Finalkan Mid Semester', $tpHtml);
        $this->assertStringContainsString('DRAFT (BELUM FINAL)', $rekapHtml);
        $this->assertStringContainsString('Kesiapan Cetak Nilai Rapor (Mid Semester)', $raporHtml);
        $this->assertStringContainsString('Cetak Nilai Rapor (5 Belum Final)', $raporHtml);
        $this->assertStringContainsString('Finalkan Mid Semester', $ekskulHtml);

        // End-to-end HTTP controller + Sqid decoding + policy + unlock flow
        $taSqid = Sqid::encodeFor(TahunAjaran::class, $f['ta']->id);
        $kelasSqid = Sqid::encodeFor(Kelas::class, $f['kelasA']->id);
        $mapelSqid = Sqid::encodeFor(MataPelajaran::class, $f['mapelPai']->id);

        // Wali kelas 5A bukan pengampu PAI -> HTTP 403
        $this->actingAs($f['waliUser'])
            ->post(route('wali-kelas.penilaian-finalisasi.finalize'), [
                'jenis_komponen' => PenilaianFinalisasi::JENIS_MAPEL,
                'tahun_ajaran_id' => $taSqid,
                'kelas_id' => $kelasSqid,
                'mata_pelajaran_id' => $mapelSqid,
                'periode' => PeriodeTp::PERIODE_MID,
            ])
            ->assertStatus(403);

        // Guru pengampu PAI memfinalkan melalui endpoint Wali Kelas -> 302 success
        $this->actingAs($f['guruMapelUser'])
            ->post(route('wali-kelas.penilaian-finalisasi.finalize'), [
                'jenis_komponen' => PenilaianFinalisasi::JENIS_MAPEL,
                'tahun_ajaran_id' => $taSqid,
                'kelas_id' => $kelasSqid,
                'mata_pelajaran_id' => $mapelSqid,
                'periode' => PeriodeTp::PERIODE_MID,
            ])
            ->assertRedirect();

        $this->assertTrue(app(PenilaianFinalisasiService::class)->isFinal(
            $f['ta']->id,
            PenilaianFinalisasi::JENIS_MAPEL,
            $f['kelasA']->id,
            $f['mapelPai']->id,
            null,
            PeriodeTp::PERIODE_MID
        ));

        // Cetak PDF Nilai Rapor via HTTP browser redirect back + flash error (toastr), JSON mengembalikan 422
        $this->actingAs($f['admin'])
            ->get(route('penilaian-rapor.nilai.kelas', ['kelas' => $f['kelasA'], 'periode' => PeriodeTp::PERIODE_MID]))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->actingAs($f['admin'])
            ->getJson(route('penilaian-rapor.nilai.kelas', ['kelas' => $f['kelasA'], 'periode' => PeriodeTp::PERIODE_MID]))
            ->assertStatus(422);

        // Super Admin membuka kunci dengan alasan valid -> kembali ke draft
        $this->actingAs($f['admin'])
            ->post(route('penilaian-finalisasi.unlock'), [
                'jenis_komponen' => PenilaianFinalisasi::JENIS_MAPEL,
                'tahun_ajaran_id' => $taSqid,
                'kelas_id' => $kelasSqid,
                'mata_pelajaran_id' => $mapelSqid,
                'periode' => PeriodeTp::PERIODE_MID,
                'unlock_reason' => 'Perlu perbaikan input nilai Ujian PSTS',
            ])
            ->assertRedirect();

        $this->assertFalse(app(PenilaianFinalisasiService::class)->isFinal(
            $f['ta']->id,
            PenilaianFinalisasi::JENIS_MAPEL,
            $f['kelasA']->id,
            $f['mapelPai']->id,
            null,
            PeriodeTp::PERIODE_MID
        ));
    }
}
