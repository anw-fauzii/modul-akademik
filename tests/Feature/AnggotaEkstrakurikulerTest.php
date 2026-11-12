<?php

namespace Tests\Feature;

use App\Models\Admin\DataMaster\AnggotaEkstrakurikuler;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnggotaEkstrakurikulerTest extends TestCase
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

    private function createKaryawan(string $nama = 'Budi Santoso'): Karyawan
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

    private function createEkstrakurikuler(
        TahunAjaran $tahunAjaran,
        ?Karyawan $pembina = null,
        string $nama = 'Panahan',
        string $unit = 'SD'
    ): Ekstrakurikuler {
        return Ekstrakurikuler::create([
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'karyawan_nipy' => $pembina?->nipy,
            'nama_ekstrakurikuler' => $nama,
            'biaya' => 50000,
            'unit' => $unit,
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

    public function test_index_filters_anggota_ekstrakurikuler_by_tahun_ajaran_ekstrakurikuler_and_kelas(): void
    {
        $user = $this->authenticateUser();
        $karyawan = $this->createKaryawan();
        $ta1 = $this->createTahunAjaran(true, '2026/2027', '1');
        $ta2 = $this->createTahunAjaran(false, '2025/2026', '2');

        $kelasA = $this->createKelas($ta1, $karyawan, '4 Umar');
        $kelasB = $this->createKelas($ta1, $karyawan, '5 Ali');
        $kelasLama = $this->createKelas($ta2, $karyawan, '3 Usman');

        $panahan = $this->createEkstrakurikuler($ta1, $karyawan, 'Panahan');
        $robotik = $this->createEkstrakurikuler($ta1, $karyawan, 'Robotik');
        $futsalLama = $this->createEkstrakurikuler($ta2, $karyawan, 'Futsal');

        $siswaSatu = $this->createSiswa('Siswa Panahan');
        $siswaDua = $this->createSiswa('Siswa Robotik');
        $siswaTiga = $this->createSiswa('Siswa Futsal Lama');

        $akSatu = AnggotaKelas::create(['siswa_nis' => $siswaSatu->nis, 'akademik_master_kelas_id' => $kelasA->id, 'pendaftaran' => 'Siswa Baru']);
        $akDua = AnggotaKelas::create(['siswa_nis' => $siswaDua->nis, 'akademik_master_kelas_id' => $kelasB->id, 'pendaftaran' => 'Siswa Baru']);
        $akTiga = AnggotaKelas::create(['siswa_nis' => $siswaTiga->nis, 'akademik_master_kelas_id' => $kelasLama->id, 'pendaftaran' => 'Naik Kelas']);

        AnggotaEkstrakurikuler::create(['anggota_kelas_id' => $akSatu->id, 'akademik_master_ekstrakuler_id' => $panahan->id, 'pendaftaran' => 'Baru', 'keterangan' => 'Regu Inti']);
        AnggotaEkstrakurikuler::create(['anggota_kelas_id' => $akDua->id, 'akademik_master_ekstrakuler_id' => $robotik->id, 'pendaftaran' => 'Baru']);
        AnggotaEkstrakurikuler::create(['anggota_kelas_id' => $akTiga->id, 'akademik_master_ekstrakuler_id' => $futsalLama->id, 'pendaftaran' => 'Lanjutan']);

        $response = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->get(route('anggota-ekstrakurikuler.index', [
                'tahun_ajaran_id' => $ta1->id,
                'ekstrakurikuler_id' => $panahan->id,
                'kelas_id' => $kelasA->id,
            ]));

        $response->assertOk()
            ->assertSee('Siswa Panahan')
            ->assertSee('4 Umar')
            ->assertSee('Regu Inti')
            ->assertDontSee('Siswa Robotik')
            ->assertDontSee('Siswa Futsal Lama');
    }

    public function test_create_shows_only_active_anggota_kelas_not_yet_registered_in_target_tahun_ajaran(): void
    {
        $user = $this->authenticateUser();
        $karyawan = $this->createKaryawan();
        $taAktif = $this->createTahunAjaran(true, '2026/2027', '1');
        $kelas = $this->createKelas($taAktif, $karyawan, '4 Umar');
        $panahan = $this->createEkstrakurikuler($taAktif, $karyawan, 'Panahan');
        $robotik = $this->createEkstrakurikuler($taAktif, $karyawan, 'Robotik');

        $eligible = $this->createSiswa('Eligible Aktif', 'Aktif');
        $alreadyJoined = $this->createSiswa('Sudah Ekskul Lain', 'Aktif');
        $withoutKelas = $this->createSiswa('Tanpa Kelas Aktif', 'Aktif');
        $nonAktif = $this->createSiswa('Alumni Nonaktif', 'Lulus');

        AnggotaKelas::create(['siswa_nis' => $eligible->nis, 'akademik_master_kelas_id' => $kelas->id, 'pendaftaran' => 'Siswa Baru']);
        $akJoined = AnggotaKelas::create(['siswa_nis' => $alreadyJoined->nis, 'akademik_master_kelas_id' => $kelas->id, 'pendaftaran' => 'Siswa Baru']);
        AnggotaKelas::create(['siswa_nis' => $nonAktif->nis, 'akademik_master_kelas_id' => $kelas->id, 'pendaftaran' => 'Siswa Baru']);

        AnggotaEkstrakurikuler::create([
            'anggota_kelas_id' => $akJoined->id,
            'akademik_master_ekstrakuler_id' => $robotik->id,
            'pendaftaran' => 'Baru',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->get(route('anggota-ekstrakurikuler.create', ['ekstrakurikuler_id' => $panahan->id]));

        $response->assertOk()
            ->assertSee('Eligible Aktif')
            ->assertDontSee('Sudah Ekskul Lain')
            ->assertDontSee('Tanpa Kelas Aktif')
            ->assertDontSee('Alumni Nonaktif')
            ->assertSee('1 sudah punya ekskul (disembunyikan)')
            ->assertSee('1 belum punya anggota kelas (disembunyikan)')
            ->assertSee('1 non-aktif/pindah/lulus (disembunyikan)');
    }

    public function test_store_batch_anggota_ekstrakurikuler_syncs_siswa_when_tahun_ajaran_active_and_redirects_to_show(): void
    {
        $user = $this->authenticateUser();
        $karyawan = $this->createKaryawan();
        $taAktif = $this->createTahunAjaran(true, '2026/2027', '1');
        $kelas = $this->createKelas($taAktif, $karyawan, '5 Ali');
        $panahan = $this->createEkstrakurikuler($taAktif, $karyawan, 'Panahan');

        $siswa1 = $this->createSiswa('Siti Rahma');
        $siswa2 = $this->createSiswa('Bima Sakti');
        $ak1 = AnggotaKelas::create(['siswa_nis' => $siswa1->nis, 'akademik_master_kelas_id' => $kelas->id, 'pendaftaran' => 'Siswa Baru']);
        $ak2 = AnggotaKelas::create(['siswa_nis' => $siswa2->nis, 'akademik_master_kelas_id' => $kelas->id, 'pendaftaran' => 'Naik Kelas']);

        $response = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->post(route('anggota-ekstrakurikuler.store'), [
                'akademik_master_ekstrakuler_id' => $panahan->id,
                'anggota_kelas_id' => [$ak1->id, $ak2->id],
                'pendaftaran' => 'Baru',
                'keterangan' => 'Gelombang 1',
                'redirect_to_ekstrakurikuler' => $panahan->id,
            ]);

        $response->assertRedirect(route('ekstrakurikuler.show', $panahan->id))
            ->assertSessionHas('success', '2 Anggota Ekstrakurikuler berhasil disimpan.');

        $this->assertDatabaseHas('akademik_master_anggota_ekstrakuler', [
            'anggota_kelas_id' => $ak1->id,
            'akademik_master_ekstrakuler_id' => $panahan->id,
            'pendaftaran' => 'Baru',
            'keterangan' => 'Gelombang 1',
        ]);
        $this->assertDatabaseHas('akademik_master_anggota_ekstrakuler', [
            'anggota_kelas_id' => $ak2->id,
            'akademik_master_ekstrakuler_id' => $panahan->id,
            'pendaftaran' => 'Baru',
            'keterangan' => 'Gelombang 1',
        ]);

        $this->assertSame($panahan->id, $siswa1->fresh()->akademik_master_ekstrakuler_id);
        $this->assertSame($panahan->id, $siswa2->fresh()->akademik_master_ekstrakuler_id);
    }

    public function test_store_rejects_mismatched_tahun_ajaran_and_duplicate_ekstrakurikuler_in_same_tahun_ajaran(): void
    {
        $user = $this->authenticateUser();
        $karyawan = $this->createKaryawan();
        $ta1 = $this->createTahunAjaran(true, '2026/2027', '1');
        $ta2 = $this->createTahunAjaran(false, '2025/2026', '2');

        $kelasTa1 = $this->createKelas($ta1, $karyawan, '4 Umar');
        $kelasTa2 = $this->createKelas($ta2, $karyawan, '3 Umar');

        $panahanTa1 = $this->createEkstrakurikuler($ta1, $karyawan, 'Panahan');
        $robotikTa1 = $this->createEkstrakurikuler($ta1, $karyawan, 'Robotik');
        $futsalTa2 = $this->createEkstrakurikuler($ta2, $karyawan, 'Futsal');

        $siswa = $this->createSiswa('Fajar Nugraha');
        $akTa1 = AnggotaKelas::create(['siswa_nis' => $siswa->nis, 'akademik_master_kelas_id' => $kelasTa1->id, 'pendaftaran' => 'Siswa Baru']);
        $akTa2 = AnggotaKelas::create(['siswa_nis' => $siswa->nis, 'akademik_master_kelas_id' => $kelasTa2->id, 'pendaftaran' => 'Siswa Baru']);

        // Mismatch TA: akTa2 (ta2) masuk ekskul panahanTa1 (ta1) -> ditolak
        $mismatchResponse = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->from(route('ekstrakurikuler.show', $panahanTa1->id))
            ->post(route('anggota-ekstrakurikuler.store'), [
                'akademik_master_ekstrakuler_id' => $panahanTa1->id,
                'anggota_kelas_id' => [$akTa2->id],
                'pendaftaran' => 'Baru',
            ]);

        $mismatchResponse->assertRedirect(route('ekstrakurikuler.show', $panahanTa1->id))
            ->assertSessionHasErrors('anggota_kelas_id');

        // Berhasil daftar pada TA1 (panahanTa1) dan TA2 (futsalTa2) karena berbeda tahun ajaran
        AnggotaEkstrakurikuler::create([
            'anggota_kelas_id' => $akTa1->id,
            'akademik_master_ekstrakuler_id' => $panahanTa1->id,
            'pendaftaran' => 'Baru',
        ]);

        $otherYearResponse = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->post(route('anggota-ekstrakurikuler.store'), [
                'akademik_master_ekstrakuler_id' => $futsalTa2->id,
                'anggota_kelas_id' => [$akTa2->id],
                'pendaftaran' => 'Baru',
            ]);

        $otherYearResponse->assertRedirect(route('anggota-ekstrakurikuler.index'));
        $this->assertDatabaseHas('akademik_master_anggota_ekstrakuler', [
            'anggota_kelas_id' => $akTa2->id,
            'akademik_master_ekstrakuler_id' => $futsalTa2->id,
        ]);

        // Duplikat di TA1 (mencoba daftar robotikTa1 ketika sudah di panahanTa1) -> ditolak
        $duplicateResponse = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->from(route('ekstrakurikuler.show', $robotikTa1->id))
            ->post(route('anggota-ekstrakurikuler.store'), [
                'akademik_master_ekstrakuler_id' => $robotikTa1->id,
                'anggota_kelas_id' => [$akTa1->id],
                'pendaftaran' => 'Baru',
            ]);

        $duplicateResponse->assertRedirect(route('ekstrakurikuler.show', $robotikTa1->id))
            ->assertSessionHasErrors('anggota_kelas_id');
    }

    public function test_update_and_destroy_sync_active_ekstrakurikuler_and_redirect_to_show(): void
    {
        $user = $this->authenticateUser();
        $karyawan = $this->createKaryawan();
        $taAktif = $this->createTahunAjaran(true, '2026/2027', '1');
        $kelas = $this->createKelas($taAktif, $karyawan, '6 Abu Bakar');
        $panahan = $this->createEkstrakurikuler($taAktif, $karyawan, 'Panahan');
        $robotik = $this->createEkstrakurikuler($taAktif, $karyawan, 'Robotik');

        $siswa = $this->createSiswa('Nadia Putri');
        $siswa->update(['akademik_master_ekstrakuler_id' => $panahan->id]);

        $ak = AnggotaKelas::create([
            'siswa_nis' => $siswa->nis,
            'akademik_master_kelas_id' => $kelas->id,
            'pendaftaran' => 'Naik Kelas',
        ]);

        $anggota = AnggotaEkstrakurikuler::create([
            'anggota_kelas_id' => $ak->id,
            'akademik_master_ekstrakuler_id' => $panahan->id,
            'pendaftaran' => 'Baru',
        ]);

        $updateResponse = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->put(route('anggota-ekstrakurikuler.update', $anggota->id), [
                'anggota_kelas_id' => $ak->id,
                'akademik_master_ekstrakuler_id' => $robotik->id,
                'pendaftaran' => 'Lanjutan',
                'keterangan' => 'Pindah minat',
                'redirect_to_ekstrakurikuler' => $robotik->id,
            ]);

        $updateResponse->assertRedirect(route('ekstrakurikuler.show', $robotik->id))
            ->assertSessionHas('success', 'Data Anggota Ekstrakurikuler berhasil diperbarui.');

        $this->assertSame($robotik->id, $siswa->fresh()->akademik_master_ekstrakuler_id);
        $this->assertDatabaseHas('akademik_master_anggota_ekstrakuler', [
            'id' => $anggota->id,
            'akademik_master_ekstrakuler_id' => $robotik->id,
            'pendaftaran' => 'Lanjutan',
            'keterangan' => 'Pindah minat',
        ]);

        $destroyResponse = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->delete(route('anggota-ekstrakurikuler.destroy', $anggota->id), [
                'redirect_to_ekstrakurikuler' => $robotik->id,
            ]);

        $destroyResponse->assertRedirect(route('ekstrakurikuler.show', $robotik->id))
            ->assertSessionHas('success', 'Data Anggota Ekstrakurikuler berhasil dihapus.');

        $this->assertNull($siswa->fresh()->akademik_master_ekstrakuler_id);
        $this->assertDatabaseMissing('akademik_master_anggota_ekstrakuler', [
            'id' => $anggota->id,
        ]);
    }
}
