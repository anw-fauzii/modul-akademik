<?php

namespace Tests\Feature;

use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\AnggotaT2q;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\User;
use Database\Seeders\SiswaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnggotaT2qTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 1;

    private function authenticateUser(): User
    {
        return User::factory()->create([
            'is_active' => true,
        ]);
    }

    private function createTahunAjaran(bool $isActive = true, string $nama = '2026/2027', string $semester = '1'): TahunAjaran
    {
        return TahunAjaran::factory()->create([
            'nama_tahun_ajaran' => $nama,
            'semester' => $semester,
            'is_active' => $isActive,
        ]);
    }

    private function createKaryawan(string $nama = 'Ustadz Ahmad'): Karyawan
    {
        return Karyawan::factory()->create([
            'nama_lengkap' => $nama,
        ]);
    }

    private function createKelas(
        TahunAjaran $tahunAjaran,
        Karyawan $waliKelas,
        string $namaKelas = '1 Al-Fatihah',
        string $jenjangKelas = 'SD',
        string $tingkatanKelas = '1'
    ): Kelas {
        return Kelas::create([
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'karyawan_nipy' => $waliKelas->nipy,
            'pendamping_nipy' => null,
            'nama_kelas' => $namaKelas,
            'jenjang_kelas' => $jenjangKelas,
            'tingkatan_kelas' => $tingkatanKelas,
            'romawi' => 'I',
        ]);
    }

    private function createSiswa(string $nama = 'Ahmad Fauzi', string $status = 'Aktif', ?string $karyawanNipy = null): Siswa
    {
        $number = $this->sequence++;

        return Siswa::create([
            'nis' => '2026'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
            'karyawan_nipy' => $karyawanNipy,
            'nama_lengkap' => $nama,
            'jenis_kelamin' => $number % 2 === 0 ? 'P' : 'L',
            'nisn' => '001234'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
            'status' => $status,
        ]);
    }

    public function test_index_filters_anggota_t2q_by_tahun_ajaran_guru_tingkat_and_kelas(): void
    {
        $user = $this->authenticateUser();
        $guruSatu = $this->createKaryawan('Ustadz Satu');
        $guruDua = $this->createKaryawan('Ustadzah Dua');
        $taAktif = $this->createTahunAjaran(true, '2026/2027', '1');
        $taLama = $this->createTahunAjaran(false, '2025/2026', '2');

        $kelasSatu = $this->createKelas($taAktif, $guruSatu, '1 Al-Fatihah', 'SD', '1');
        $kelasPg = $this->createKelas($taAktif, $guruDua, 'PG Mawar', 'TK', 'PG');
        $kelasLama = $this->createKelas($taLama, $guruSatu, 'A Melati', 'TK', 'A');

        $siswaSatu = $this->createSiswa('Siswa T2Q Satu');
        $siswaDua = $this->createSiswa('Siswa T2Q PG');
        $siswaLama = $this->createSiswa('Siswa T2Q Lama');

        $akSatu = AnggotaKelas::create(['siswa_nis' => $siswaSatu->nis, 'akademik_master_kelas_id' => $kelasSatu->id, 'pendaftaran' => 'Siswa Baru']);
        $akDua = AnggotaKelas::create(['siswa_nis' => $siswaDua->nis, 'akademik_master_kelas_id' => $kelasPg->id, 'pendaftaran' => 'Siswa Baru']);
        $akLama = AnggotaKelas::create(['siswa_nis' => $siswaLama->nis, 'akademik_master_kelas_id' => $kelasLama->id, 'pendaftaran' => 'Naik Kelas']);

        AnggotaT2q::create([
            'anggota_kelas_id' => $akSatu->id,
            'karyawan_nipy' => $guruSatu->nipy,
            'tingkat' => '1',
            'pendaftaran' => 'Baru',
            'keterangan' => 'Kelompok Tahfidz A',
        ]);
        AnggotaT2q::create([
            'anggota_kelas_id' => $akDua->id,
            'karyawan_nipy' => $guruDua->nipy,
            'tingkat' => 'PG',
            'pendaftaran' => 'Baru',
        ]);
        AnggotaT2q::create([
            'anggota_kelas_id' => $akLama->id,
            'karyawan_nipy' => $guruSatu->nipy,
            'tingkat' => 'A',
            'pendaftaran' => 'Lanjutan',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->get(route('anggota-t2q.index', [
                'tahun_ajaran_id' => $taAktif->id,
                'karyawan_nipy' => $guruSatu->nipy,
                'tingkat' => '1',
                'kelas_id' => $kelasSatu->id,
            ]));

        $response->assertOk()
            ->assertSee('Siswa T2Q Satu')
            ->assertSee('1 Al-Fatihah')
            ->assertSee('Tingkat 1')
            ->assertSee('Kelompok Tahfidz A')
            ->assertDontSee('Siswa T2Q PG')
            ->assertDontSee('Siswa T2Q Lama');
    }

    public function test_create_shows_only_active_anggota_kelas_not_yet_registered_in_selected_tahun_ajaran(): void
    {
        $user = $this->authenticateUser();
        $guruSatu = $this->createKaryawan('Guru A');
        $guruDua = $this->createKaryawan('Guru B');
        $taAktif = $this->createTahunAjaran(true, '2026/2027', '1');
        $kelas = $this->createKelas($taAktif, $guruSatu, '1 Umar', 'SD', '1');

        $eligible = $this->createSiswa('Eligible T2Q', 'Aktif');
        $alreadyJoined = $this->createSiswa('Sudah T2Q Aktif', 'Aktif');
        $this->createSiswa('Tanpa Kelas T2Q', 'Aktif');
        $nonAktif = $this->createSiswa('Alumni Nonaktif', 'Lulus');

        AnggotaKelas::create(['siswa_nis' => $eligible->nis, 'akademik_master_kelas_id' => $kelas->id, 'pendaftaran' => 'Siswa Baru']);
        $akJoined = AnggotaKelas::create(['siswa_nis' => $alreadyJoined->nis, 'akademik_master_kelas_id' => $kelas->id, 'pendaftaran' => 'Siswa Baru']);
        AnggotaKelas::create(['siswa_nis' => $nonAktif->nis, 'akademik_master_kelas_id' => $kelas->id, 'pendaftaran' => 'Siswa Baru']);

        AnggotaT2q::create([
            'anggota_kelas_id' => $akJoined->id,
            'karyawan_nipy' => $guruDua->nipy,
            'tingkat' => '1',
            'pendaftaran' => 'Baru',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->get(route('anggota-t2q.create', ['tahun_ajaran_id' => $taAktif->id]));

        $response->assertOk()
            ->assertSee('Eligible T2Q')
            ->assertDontSee('Sudah T2Q Aktif')
            ->assertDontSee('Tanpa Kelas T2Q')
            ->assertDontSee('Alumni Nonaktif')
            ->assertSee('1 sudah terdaftar T2Q (disembunyikan)')
            ->assertSee('1 belum punya anggota kelas (disembunyikan)')
            ->assertSee('1 non-aktif/pindah/lulus (disembunyikan)');
    }

    public function test_store_batch_supports_explicit_auto_and_custom_tingkat_without_modifying_siswa_karyawan_nipy(): void
    {
        $user = $this->authenticateUser();
        $waliKelas = $this->createKaryawan('Wali Kelas Tetap');
        $guruT2q = $this->createKaryawan('Guru T2Q Khusus');
        $taAktif = $this->createTahunAjaran(true, '2026/2027', '1');

        $kelasPg = $this->createKelas($taAktif, $waliKelas, 'PG Bintang', 'TK', 'PG');
        $kelasEmpat = $this->createKelas($taAktif, $waliKelas, '4 Ali', 'SD', '4');

        $siswaPg = $this->createSiswa('Siswa PG', 'Aktif', $waliKelas->nipy);
        $siswaEmpat = $this->createSiswa('Siswa Empat', 'Aktif', null);
        $siswaManual = $this->createSiswa('Siswa Manual', 'Aktif', $waliKelas->nipy);

        $akPg = AnggotaKelas::create(['siswa_nis' => $siswaPg->nis, 'akademik_master_kelas_id' => $kelasPg->id, 'pendaftaran' => 'Siswa Baru']);
        $akEmpat = AnggotaKelas::create(['siswa_nis' => $siswaEmpat->nis, 'akademik_master_kelas_id' => $kelasEmpat->id, 'pendaftaran' => 'Naik Kelas']);
        $akManual = AnggotaKelas::create(['siswa_nis' => $siswaManual->nis, 'akademik_master_kelas_id' => $kelasEmpat->id, 'pendaftaran' => 'Naik Kelas']);

        // Mode otomatis mengikuti tingkatan_kelas masing-masing kelas siswa
        $autoResponse = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->post(route('anggota-t2q.store'), [
                'tahun_ajaran_id' => $taAktif->id,
                'karyawan_nipy' => $guruT2q->nipy,
                'tingkat' => AnggotaT2q::TINGKAT_AUTO,
                'pendaftaran' => 'Baru',
                'keterangan' => 'Auto sesuai kelas',
                'anggota_kelas_id' => [$akPg->id, $akEmpat->id],
            ]);

        $autoResponse->assertRedirect(route('anggota-t2q.index'))
            ->assertSessionHas('success', '2 Anggota T2Q berhasil disimpan.');

        $this->assertDatabaseHas('akademik_master_anggota_t2q', [
            'anggota_kelas_id' => $akPg->id,
            'karyawan_nipy' => $guruT2q->nipy,
            'tingkat' => 'PG',
            'pendaftaran' => 'Baru',
            'keterangan' => 'Auto sesuai kelas',
        ]);
        $this->assertDatabaseHas('akademik_master_anggota_t2q', [
            'anggota_kelas_id' => $akEmpat->id,
            'karyawan_nipy' => $guruT2q->nipy,
            'tingkat' => '4',
            'pendaftaran' => 'Baru',
            'keterangan' => 'Auto sesuai kelas',
        ]);

        // Mode custom tingkat
        $customResponse = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->post(route('anggota-t2q.store'), [
                'tahun_ajaran_id' => $taAktif->id,
                'karyawan_nipy' => $guruT2q->nipy,
                'tingkat' => AnggotaT2q::TINGKAT_CUSTOM,
                'tingkat_custom' => 'TK-A',
                'pendaftaran' => 'Lanjutan',
                'anggota_kelas_id' => [$akManual->id],
            ]);

        $customResponse->assertRedirect(route('anggota-t2q.index'))
            ->assertSessionHas('success', '1 Anggota T2Q berhasil disimpan.');

        $this->assertDatabaseHas('akademik_master_anggota_t2q', [
            'anggota_kelas_id' => $akManual->id,
            'karyawan_nipy' => $guruT2q->nipy,
            'tingkat' => 'TK-A',
            'pendaftaran' => 'Lanjutan',
        ]);

        // Pastikan kolom karyawan_nipy pada tabel siswa tidak ikut diubah
        $this->assertSame($waliKelas->nipy, $siswaPg->fresh()->karyawan_nipy);
        $this->assertNull($siswaEmpat->fresh()->karyawan_nipy);
        $this->assertSame($waliKelas->nipy, $siswaManual->fresh()->karyawan_nipy);
    }

    public function test_store_rejects_mismatched_tahun_ajaran_and_duplicate_t2q_in_same_tahun_ajaran(): void
    {
        $user = $this->authenticateUser();
        $guruSatu = $this->createKaryawan('Guru Satu');
        $guruDua = $this->createKaryawan('Guru Dua');
        $ta1 = $this->createTahunAjaran(true, '2026/2027', '1');
        $ta2 = $this->createTahunAjaran(false, '2025/2026', '2');

        $kelasTa1 = $this->createKelas($ta1, $guruSatu, '2 Umar', 'SD', '2');
        $kelasTa2 = $this->createKelas($ta2, $guruSatu, '1 Umar', 'SD', '1');

        $siswa = $this->createSiswa('Fajar Nugraha');
        $akTa1 = AnggotaKelas::create(['siswa_nis' => $siswa->nis, 'akademik_master_kelas_id' => $kelasTa1->id, 'pendaftaran' => 'Siswa Baru']);
        $akTa2 = AnggotaKelas::create(['siswa_nis' => $siswa->nis, 'akademik_master_kelas_id' => $kelasTa2->id, 'pendaftaran' => 'Siswa Baru']);

        // Mismatch TA: akTa2 (ta2) dikirim bersama tahun_ajaran_id = ta1 -> ditolak
        $mismatchResponse = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->from(route('anggota-t2q.create', ['tahun_ajaran_id' => $ta1->id]))
            ->post(route('anggota-t2q.store'), [
                'tahun_ajaran_id' => $ta1->id,
                'karyawan_nipy' => $guruSatu->nipy,
                'tingkat' => '1',
                'pendaftaran' => 'Baru',
                'anggota_kelas_id' => [$akTa2->id],
            ]);

        $mismatchResponse->assertRedirect(route('anggota-t2q.create', ['tahun_ajaran_id' => $ta1->id]))
            ->assertSessionHasErrors('anggota_kelas_id');

        // Berhasil daftar di TA1 & TA2 karena beda tahun ajaran
        AnggotaT2q::create([
            'anggota_kelas_id' => $akTa1->id,
            'karyawan_nipy' => $guruSatu->nipy,
            'tingkat' => '2',
            'pendaftaran' => 'Lanjutan',
        ]);

        $otherYearResponse = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->post(route('anggota-t2q.store'), [
                'karyawan_nipy' => $guruDua->nipy,
                'tingkat' => '1',
                'pendaftaran' => 'Baru',
                'anggota_kelas_id' => [$akTa2->id],
            ]);

        $otherYearResponse->assertRedirect(route('anggota-t2q.index'));
        $this->assertDatabaseHas('akademik_master_anggota_t2q', [
            'anggota_kelas_id' => $akTa2->id,
            'karyawan_nipy' => $guruDua->nipy,
            'tingkat' => '1',
        ]);

        // Duplikat siswa di TA1 -> ditolak walau guru berbeda
        $duplicateResponse = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->from(route('anggota-t2q.create'))
            ->post(route('anggota-t2q.store'), [
                'tahun_ajaran_id' => $ta1->id,
                'karyawan_nipy' => $guruDua->nipy,
                'tingkat' => '2',
                'pendaftaran' => 'Baru',
                'anggota_kelas_id' => [$akTa1->id],
            ]);

        $duplicateResponse->assertRedirect(route('anggota-t2q.create'))
            ->assertSessionHasErrors('anggota_kelas_id');
    }

    public function test_edit_update_and_destroy_anggota_t2q(): void
    {
        $user = $this->authenticateUser();
        $guruSatu = $this->createKaryawan('Guru Awal');
        $guruDua = $this->createKaryawan('Guru Baru');
        $taAktif = $this->createTahunAjaran(true, '2026/2027', '1');
        $kelas = $this->createKelas($taAktif, $guruSatu, 'A Melati', 'TK', 'A');

        $siswa = $this->createSiswa('Nadia Putri');
        $ak = AnggotaKelas::create([
            'siswa_nis' => $siswa->nis,
            'akademik_master_kelas_id' => $kelas->id,
            'pendaftaran' => 'Naik Kelas',
        ]);

        $anggota = AnggotaT2q::create([
            'anggota_kelas_id' => $ak->id,
            'karyawan_nipy' => $guruSatu->nipy,
            'tingkat' => 'PG',
            'pendaftaran' => 'Baru',
        ]);

        $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->get(route('anggota-t2q.edit', $anggota->id))
            ->assertOk()
            ->assertSee('Nadia Putri')
            ->assertSee('Samakan dg Kelas');

        $updateResponse = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->put(route('anggota-t2q.update', $anggota->id), [
                'anggota_kelas_id' => $ak->id,
                'karyawan_nipy' => $guruDua->nipy,
                'tingkat' => 'A',
                'pendaftaran' => 'Lanjutan',
                'keterangan' => 'Naik tingkat A',
            ]);

        $updateResponse->assertRedirect(route('anggota-t2q.index'))
            ->assertSessionHas('success', 'Data Anggota T2Q berhasil diperbarui.');

        $this->assertDatabaseHas('akademik_master_anggota_t2q', [
            'id' => $anggota->id,
            'karyawan_nipy' => $guruDua->nipy,
            'tingkat' => 'A',
            'pendaftaran' => 'Lanjutan',
            'keterangan' => 'Naik tingkat A',
        ]);

        $destroyResponse = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->delete(route('anggota-t2q.destroy', $anggota->id));

        $destroyResponse->assertRedirect(route('anggota-t2q.index'))
            ->assertSessionHas('success', 'Data Anggota T2Q berhasil dihapus.');

        $this->assertDatabaseMissing('akademik_master_anggota_t2q', [
            'id' => $anggota->id,
        ]);
    }

    public function test_siswa_seeder_populates_anggota_t2q_idempotently(): void
    {
        $ta = $this->createTahunAjaran(true, '2026/2027', '1');
        $guru = $this->createKaryawan('Guru Seeder');
        $this->createKelas($ta, $guru, '1 Abu Bakar', 'SD', '1');

        $this->seed(SiswaSeeder::class);
        $firstCount = AnggotaT2q::query()->count();
        $this->assertGreaterThan(0, $firstCount);

        $this->seed(SiswaSeeder::class);
        $this->assertSame($firstCount, AnggotaT2q::query()->count());
    }
}
