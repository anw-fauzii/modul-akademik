<?php

namespace Tests\Feature;

use App\Models\Admin\DataMaster\AnggotaJemputan;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Jemputan;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnggotaJemputanTest extends TestCase
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

    private function createKaryawan(string $nama = 'Agus Setiawan'): Karyawan
    {
        return Karyawan::factory()->create([
            'nama_lengkap' => $nama,
        ]);
    }

    private function createKelas(TahunAjaran $tahunAjaran, Karyawan $karyawan, string $namaKelas = '1 Al-Fatihah'): Kelas
    {
        return Kelas::create([
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'karyawan_nipy' => $karyawan->nipy,
            'pendamping_nipy' => null,
            'nama_kelas' => $namaKelas,
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => '1',
            'romawi' => 'I',
        ]);
    }

    private function createJemputan(
        TahunAjaran $tahunAjaran,
        Karyawan $petugas,
        int $hargaPp = 250000,
        int $hargaSetengah = 150000
    ): Jemputan {
        return Jemputan::create([
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'karyawan_nipy' => $petugas->nipy,
            'harga_pp' => $hargaPp,
            'harga_setengah' => $hargaSetengah,
        ]);
    }

    private function createSiswa(string $nama = 'Ahmad Fauzi', string $status = 'Aktif'): Siswa
    {
        $number = $this->sequence++;

        return Siswa::create([
            'nis' => '2026'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
            'nama_lengkap' => $nama,
            'jenis_kelamin' => $number % 2 === 0 ? 'P' : 'L',
            'nisn' => '001234'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
            'status' => $status,
        ]);
    }

    public function test_index_filters_anggota_jemputan_by_tahun_ajaran_jemputan_and_kelas(): void
    {
        $user = $this->authenticateUser();
        $driverSatu = $this->createKaryawan('Driver Satu');
        $driverDua = $this->createKaryawan('Driver Dua');
        $ta1 = $this->createTahunAjaran(true, '2026/2027', '1');
        $ta2 = $this->createTahunAjaran(false, '2025/2026', '2');

        $kelasA = $this->createKelas($ta1, $driverSatu, '4 Umar');
        $kelasB = $this->createKelas($ta1, $driverDua, '5 Ali');
        $kelasLama = $this->createKelas($ta2, $driverSatu, '3 Usman');

        $jemputanA = $this->createJemputan($ta1, $driverSatu, 250000, 150000);
        $jemputanB = $this->createJemputan($ta1, $driverDua, 200000, 120000);
        $jemputanLama = $this->createJemputan($ta2, $driverSatu, 180000, 100000);

        $siswaSatu = $this->createSiswa('Siswa Driver Satu');
        $siswaDua = $this->createSiswa('Siswa Driver Dua');
        $siswaTiga = $this->createSiswa('Siswa Driver Lama');

        $akSatu = AnggotaKelas::create(['siswa_nis' => $siswaSatu->nis, 'akademik_master_kelas_id' => $kelasA->id, 'pendaftaran' => 'Siswa Baru']);
        $akDua = AnggotaKelas::create(['siswa_nis' => $siswaDua->nis, 'akademik_master_kelas_id' => $kelasB->id, 'pendaftaran' => 'Siswa Baru']);
        $akTiga = AnggotaKelas::create(['siswa_nis' => $siswaTiga->nis, 'akademik_master_kelas_id' => $kelasLama->id, 'pendaftaran' => 'Naik Kelas']);

        AnggotaJemputan::create(['anggota_kelas_id' => $akSatu->id, 'akademik_master_jemputan_id' => $jemputanA->id, 'pendaftaran' => 'Baru', 'keterangan' => 'Antar PP']);
        AnggotaJemputan::create(['anggota_kelas_id' => $akDua->id, 'akademik_master_jemputan_id' => $jemputanB->id, 'pendaftaran' => 'Baru']);
        AnggotaJemputan::create(['anggota_kelas_id' => $akTiga->id, 'akademik_master_jemputan_id' => $jemputanLama->id, 'pendaftaran' => 'Lanjutan']);

        $response = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->get(route('anggota-jemputan.index', [
                'tahun_ajaran_id' => $ta1->id,
                'jemputan_id' => $jemputanA->id,
                'kelas_id' => $kelasA->id,
            ]));

        $response->assertOk()
            ->assertSee('Siswa Driver Satu')
            ->assertSee('4 Umar')
            ->assertSee('Antar PP')
            ->assertDontSee('Siswa Driver Dua')
            ->assertDontSee('Siswa Driver Lama');
    }

    public function test_create_shows_only_active_anggota_kelas_not_yet_registered_in_target_tahun_ajaran(): void
    {
        $user = $this->authenticateUser();
        $driverSatu = $this->createKaryawan('Driver A');
        $driverDua = $this->createKaryawan('Driver B');
        $taAktif = $this->createTahunAjaran(true, '2026/2027', '1');
        $kelas = $this->createKelas($taAktif, $driverSatu, '4 Umar');
        $jemputanA = $this->createJemputan($taAktif, $driverSatu);
        $jemputanB = $this->createJemputan($taAktif, $driverDua);

        $eligible = $this->createSiswa('Eligible Aktif', 'Aktif');
        $alreadyJoined = $this->createSiswa('Sudah Jemputan Lain', 'Aktif');
        $this->createSiswa('Tanpa Kelas Aktif', 'Aktif');
        $nonAktif = $this->createSiswa('Alumni Nonaktif', 'Lulus');

        AnggotaKelas::create(['siswa_nis' => $eligible->nis, 'akademik_master_kelas_id' => $kelas->id, 'pendaftaran' => 'Siswa Baru']);
        $akJoined = AnggotaKelas::create(['siswa_nis' => $alreadyJoined->nis, 'akademik_master_kelas_id' => $kelas->id, 'pendaftaran' => 'Siswa Baru']);
        AnggotaKelas::create(['siswa_nis' => $nonAktif->nis, 'akademik_master_kelas_id' => $kelas->id, 'pendaftaran' => 'Siswa Baru']);

        AnggotaJemputan::create([
            'anggota_kelas_id' => $akJoined->id,
            'akademik_master_jemputan_id' => $jemputanB->id,
            'pendaftaran' => 'Baru',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->get(route('anggota-jemputan.create', ['jemputan_id' => $jemputanA->id]));

        $response->assertOk()
            ->assertSee('Eligible Aktif')
            ->assertDontSee('Sudah Jemputan Lain')
            ->assertDontSee('Tanpa Kelas Aktif')
            ->assertDontSee('Alumni Nonaktif')
            ->assertSee('1 sudah punya jemputan (disembunyikan)')
            ->assertSee('1 belum punya anggota kelas (disembunyikan)')
            ->assertSee('1 non-aktif/pindah/lulus (disembunyikan)');
    }

    public function test_store_batch_anggota_jemputan_syncs_siswa_when_tahun_ajaran_active_and_redirects_to_show(): void
    {
        $user = $this->authenticateUser();
        $driver = $this->createKaryawan('Driver Utama');
        $taAktif = $this->createTahunAjaran(true, '2026/2027', '1');
        $kelas = $this->createKelas($taAktif, $driver, '5 Ali');
        $jemputan = $this->createJemputan($taAktif, $driver);

        $siswa1 = $this->createSiswa('Siti Rahma');
        $siswa2 = $this->createSiswa('Bima Sakti');
        $ak1 = AnggotaKelas::create(['siswa_nis' => $siswa1->nis, 'akademik_master_kelas_id' => $kelas->id, 'pendaftaran' => 'Siswa Baru']);
        $ak2 = AnggotaKelas::create(['siswa_nis' => $siswa2->nis, 'akademik_master_kelas_id' => $kelas->id, 'pendaftaran' => 'Naik Kelas']);

        $response = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->post(route('anggota-jemputan.store'), [
                'akademik_master_jemputan_id' => $jemputan->id,
                'anggota_kelas_id' => [$ak1->id, $ak2->id],
                'pendaftaran' => 'Baru',
                'keterangan' => 'Antar Jemput PP',
                'redirect_to_jemputan' => $jemputan->id,
            ]);

        $response->assertRedirect(route('jemputan.show', $jemputan->id))
            ->assertSessionHas('success', '2 Anggota Jemputan berhasil disimpan.');

        $this->assertDatabaseHas('akademik_master_anggota_jemputan', [
            'anggota_kelas_id' => $ak1->id,
            'akademik_master_jemputan_id' => $jemputan->id,
            'pendaftaran' => 'Baru',
            'keterangan' => 'Antar Jemput PP',
        ]);
        $this->assertDatabaseHas('akademik_master_anggota_jemputan', [
            'anggota_kelas_id' => $ak2->id,
            'akademik_master_jemputan_id' => $jemputan->id,
            'pendaftaran' => 'Baru',
            'keterangan' => 'Antar Jemput PP',
        ]);

        $this->assertSame($jemputan->id, $siswa1->fresh()->akademik_master_jemputan_id);
        $this->assertSame($jemputan->id, $siswa2->fresh()->akademik_master_jemputan_id);
    }

    public function test_store_rejects_mismatched_tahun_ajaran_and_duplicate_jemputan_in_same_tahun_ajaran(): void
    {
        $user = $this->authenticateUser();
        $driverSatu = $this->createKaryawan('Driver Satu');
        $driverDua = $this->createKaryawan('Driver Dua');
        $ta1 = $this->createTahunAjaran(true, '2026/2027', '1');
        $ta2 = $this->createTahunAjaran(false, '2025/2026', '2');

        $kelasTa1 = $this->createKelas($ta1, $driverSatu, '4 Umar');
        $kelasTa2 = $this->createKelas($ta2, $driverSatu, '3 Umar');

        $jemputanSatuTa1 = $this->createJemputan($ta1, $driverSatu);
        $jemputanDuaTa1 = $this->createJemputan($ta1, $driverDua);
        $jemputanTa2 = $this->createJemputan($ta2, $driverSatu);

        $siswa = $this->createSiswa('Fajar Nugraha');
        $akTa1 = AnggotaKelas::create(['siswa_nis' => $siswa->nis, 'akademik_master_kelas_id' => $kelasTa1->id, 'pendaftaran' => 'Siswa Baru']);
        $akTa2 = AnggotaKelas::create(['siswa_nis' => $siswa->nis, 'akademik_master_kelas_id' => $kelasTa2->id, 'pendaftaran' => 'Siswa Baru']);

        // Mismatch TA: akTa2 (ta2) masuk jemputanSatuTa1 (ta1) -> ditolak
        $mismatchResponse = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->from(route('jemputan.show', $jemputanSatuTa1->id))
            ->post(route('anggota-jemputan.store'), [
                'akademik_master_jemputan_id' => $jemputanSatuTa1->id,
                'anggota_kelas_id' => [$akTa2->id],
                'pendaftaran' => 'Baru',
            ]);

        $mismatchResponse->assertRedirect(route('jemputan.show', $jemputanSatuTa1->id))
            ->assertSessionHasErrors('anggota_kelas_id');

        // Berhasil daftar pada TA1 (jemputanSatuTa1) dan TA2 (jemputanTa2) karena berbeda tahun ajaran
        AnggotaJemputan::create([
            'anggota_kelas_id' => $akTa1->id,
            'akademik_master_jemputan_id' => $jemputanSatuTa1->id,
            'pendaftaran' => 'Baru',
        ]);

        $otherYearResponse = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->post(route('anggota-jemputan.store'), [
                'akademik_master_jemputan_id' => $jemputanTa2->id,
                'anggota_kelas_id' => [$akTa2->id],
                'pendaftaran' => 'Baru',
            ]);

        $otherYearResponse->assertRedirect(route('anggota-jemputan.index'));
        $this->assertDatabaseHas('akademik_master_anggota_jemputan', [
            'anggota_kelas_id' => $akTa2->id,
            'akademik_master_jemputan_id' => $jemputanTa2->id,
        ]);

        // Duplikat di TA1 (mencoba daftar jemputanDuaTa1 ketika sudah di jemputanSatuTa1) -> ditolak
        $duplicateResponse = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->from(route('jemputan.show', $jemputanDuaTa1->id))
            ->post(route('anggota-jemputan.store'), [
                'akademik_master_jemputan_id' => $jemputanDuaTa1->id,
                'anggota_kelas_id' => [$akTa1->id],
                'pendaftaran' => 'Baru',
            ]);

        $duplicateResponse->assertRedirect(route('jemputan.show', $jemputanDuaTa1->id))
            ->assertSessionHasErrors('anggota_kelas_id');
    }

    public function test_update_and_destroy_sync_active_jemputan_and_redirect_to_show(): void
    {
        $user = $this->authenticateUser();
        $driverSatu = $this->createKaryawan('Driver Satu');
        $driverDua = $this->createKaryawan('Driver Dua');
        $taAktif = $this->createTahunAjaran(true, '2026/2027', '1');
        $kelas = $this->createKelas($taAktif, $driverSatu, '6 Abu Bakar');
        $jemputanSatu = $this->createJemputan($taAktif, $driverSatu);
        $jemputanDua = $this->createJemputan($taAktif, $driverDua);

        $siswa = $this->createSiswa('Nadia Putri');
        $siswa->update(['akademik_master_jemputan_id' => $jemputanSatu->id]);

        $ak = AnggotaKelas::create([
            'siswa_nis' => $siswa->nis,
            'akademik_master_kelas_id' => $kelas->id,
            'pendaftaran' => 'Naik Kelas',
        ]);

        $anggota = AnggotaJemputan::create([
            'anggota_kelas_id' => $ak->id,
            'akademik_master_jemputan_id' => $jemputanSatu->id,
            'pendaftaran' => 'Baru',
        ]);

        $updateResponse = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->put(route('anggota-jemputan.update', $anggota->id), [
                'anggota_kelas_id' => $ak->id,
                'akademik_master_jemputan_id' => $jemputanDua->id,
                'pendaftaran' => 'Lanjutan',
                'keterangan' => 'Pindah rute driver',
                'redirect_to_jemputan' => $jemputanDua->id,
            ]);

        $updateResponse->assertRedirect(route('jemputan.show', $jemputanDua->id))
            ->assertSessionHas('success', 'Data Anggota Jemputan berhasil diperbarui.');

        $this->assertSame($jemputanDua->id, $siswa->fresh()->akademik_master_jemputan_id);
        $this->assertDatabaseHas('akademik_master_anggota_jemputan', [
            'id' => $anggota->id,
            'akademik_master_jemputan_id' => $jemputanDua->id,
            'pendaftaran' => 'Lanjutan',
            'keterangan' => 'Pindah rute driver',
        ]);

        $destroyResponse = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->delete(route('anggota-jemputan.destroy', $anggota->id), [
                'redirect_to_jemputan' => $jemputanDua->id,
            ]);

        $destroyResponse->assertRedirect(route('jemputan.show', $jemputanDua->id))
            ->assertSessionHas('success', 'Data Anggota Jemputan berhasil dihapus.');

        $this->assertNull($siswa->fresh()->akademik_master_jemputan_id);
        $this->assertDatabaseMissing('akademik_master_anggota_jemputan', [
            'id' => $anggota->id,
        ]);
    }
}

