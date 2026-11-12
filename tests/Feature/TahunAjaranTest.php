<?php

namespace Tests\Feature;

use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Models\Admin\DataMaster\Jemputan;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TahunAjaranTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['auth.host' => 'https://sso.test']);
        Http::preventStrayRequests();

        if (! Schema::hasTable('kepegawaian_master_karyawan')) {
            Schema::create('kepegawaian_master_karyawan', function (Blueprint $table) {
                $table->string('nipy')->primary();
                $table->string('nama_lengkap');
                $table->string('gelar')->nullable();
                $table->string('jabatan')->nullable();
                $table->string('unit')->nullable();
                $table->timestamps();
            });
        }
    }

    private function authenticateUser(): User
    {
        Http::fake([
            'https://sso.test/api/user' => Http::response([
                'name' => 'Admin Akademik',
                'email' => 'admin@primainsani.sch.id',
            ], 200),
        ]);

        return User::create([
            'name' => 'Admin Akademik',
            'email' => 'admin@primainsani.sch.id',
        ]);
    }

    public function test_store_creates_tahun_ajaran_and_resets_siswa_master_columns_to_null(): void
    {
        $user = $this->authenticateUser();

        $taLama = TahunAjaran::create([
            'nama_tahun_ajaran' => '2025/2026',
            'semester' => '2',
            'is_active' => true,
        ]);

        $karyawan = Karyawan::create([
            'nipy' => '19900101-1',
            'nama_lengkap' => 'Budi Santoso',
            'gelar' => 'S.Pd',
        ]);

        $kelas = Kelas::create([
            'akademik_master_tahun_ajaran_id' => $taLama->id,
            'karyawan_nipy' => $karyawan->nipy,
            'tingkatan_kelas' => '1',
            'nama_kelas' => '1 Al-Fatihah',
            'jenjang_kelas' => 'SD',
            'romawi' => 'I',
        ]);

        $ekstrakurikuler = Ekstrakurikuler::factory()->create([
            'akademik_master_tahun_ajaran_id' => $taLama->id,
            'karyawan_nipy' => $karyawan->nipy,
        ]);

        $jemputan = Jemputan::factory()->create([
            'akademik_master_tahun_ajaran_id' => $taLama->id,
            'karyawan_nipy' => $karyawan->nipy,
        ]);

        $siswaAktif = Siswa::factory()->create([
            'nis' => '2026001',
            'akademik_master_kelas_id' => $kelas->id,
            'karyawan_nipy' => $karyawan->nipy,
            'akademik_master_ekstrakuler_id' => $ekstrakurikuler->id,
            'akademik_master_jemputan_id' => $jemputan->id,
            'status' => 'Aktif',
        ]);

        $siswaNonaktif = Siswa::factory()->create([
            'nis' => '2026002',
            'akademik_master_kelas_id' => $kelas->id,
            'karyawan_nipy' => $karyawan->nipy,
            'akademik_master_ekstrakuler_id' => $ekstrakurikuler->id,
            'akademik_master_jemputan_id' => $jemputan->id,
            'status' => 'Nonaktif',
        ]);

        $response = $this->actingAs($user)->post(route('tahun-ajaran.store'), [
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
        ]);

        $response->assertRedirect(route('tahun-ajaran.index'));

        $this->assertDatabaseHas('akademik_master_tahun_ajaran', [
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
        ]);

        foreach ([$siswaAktif->nis, $siswaNonaktif->nis] as $nis) {
            $this->assertDatabaseHas('akademik_master_siswa', [
                'nis' => $nis,
                'akademik_master_kelas_id' => null,
                'karyawan_nipy' => null,
                'akademik_master_ekstrakuler_id' => null,
                'akademik_master_jemputan_id' => null,
            ]);
        }
    }

    public function test_destroy_inactive_tahun_ajaran_resets_only_students_linked_to_that_tahun_ajaran(): void
    {
        $user = $this->authenticateUser();

        $taTerhapus = TahunAjaran::create([
            'nama_tahun_ajaran' => '2024/2025',
            'semester' => '2',
            'is_active' => false,
        ]);

        $taAktif = TahunAjaran::create([
            'nama_tahun_ajaran' => '2025/2026',
            'semester' => '1',
            'is_active' => true,
        ]);

        $karyawan = Karyawan::create([
            'nipy' => '19900102-1',
            'nama_lengkap' => 'Siti Aminah',
            'gelar' => 'S.Pd',
        ]);

        $kelasLama = Kelas::create([
            'akademik_master_tahun_ajaran_id' => $taTerhapus->id,
            'karyawan_nipy' => $karyawan->nipy,
            'tingkatan_kelas' => '1',
            'nama_kelas' => '1 Al-Fatihah',
            'jenjang_kelas' => 'SD',
            'romawi' => 'I',
        ]);

        $ekskulLama = Ekstrakurikuler::factory()->create([
            'akademik_master_tahun_ajaran_id' => $taTerhapus->id,
            'karyawan_nipy' => $karyawan->nipy,
        ]);

        $jemputanLama = Jemputan::factory()->create([
            'akademik_master_tahun_ajaran_id' => $taTerhapus->id,
            'karyawan_nipy' => $karyawan->nipy,
        ]);

        $kelasAktif = Kelas::create([
            'akademik_master_tahun_ajaran_id' => $taAktif->id,
            'karyawan_nipy' => $karyawan->nipy,
            'tingkatan_kelas' => '2',
            'nama_kelas' => '2 Abu Bakar',
            'jenjang_kelas' => 'SD',
            'romawi' => 'II',
        ]);

        $ekskulAktif = Ekstrakurikuler::factory()->create([
            'akademik_master_tahun_ajaran_id' => $taAktif->id,
            'karyawan_nipy' => $karyawan->nipy,
        ]);

        $jemputanAktif = Jemputan::factory()->create([
            'akademik_master_tahun_ajaran_id' => $taAktif->id,
            'karyawan_nipy' => $karyawan->nipy,
        ]);

        $siswaViaKelas = Siswa::factory()->create([
            'nis' => '2026101',
            'akademik_master_kelas_id' => $kelasLama->id,
            'karyawan_nipy' => $karyawan->nipy,
        ]);

        $siswaViaEkskul = Siswa::factory()->create([
            'nis' => '2026102',
            'akademik_master_ekstrakuler_id' => $ekskulLama->id,
            'karyawan_nipy' => $karyawan->nipy,
        ]);

        $siswaViaJemputan = Siswa::factory()->create([
            'nis' => '2026103',
            'akademik_master_jemputan_id' => $jemputanLama->id,
            'karyawan_nipy' => $karyawan->nipy,
        ]);

        $siswaTahunAktif = Siswa::factory()->create([
            'nis' => '2026104',
            'akademik_master_kelas_id' => $kelasAktif->id,
            'karyawan_nipy' => $karyawan->nipy,
            'akademik_master_ekstrakuler_id' => $ekskulAktif->id,
            'akademik_master_jemputan_id' => $jemputanAktif->id,
        ]);

        $response = $this->actingAs($user)->delete(route('tahun-ajaran.destroy', $taTerhapus));

        $response->assertRedirect(route('tahun-ajaran.index'));
        $this->assertDatabaseMissing('akademik_master_tahun_ajaran', ['id' => $taTerhapus->id]);

        foreach ([$siswaViaKelas->nis, $siswaViaEkskul->nis, $siswaViaJemputan->nis] as $nis) {
            $this->assertDatabaseHas('akademik_master_siswa', [
                'nis' => $nis,
                'akademik_master_kelas_id' => null,
                'karyawan_nipy' => null,
                'akademik_master_ekstrakuler_id' => null,
                'akademik_master_jemputan_id' => null,
            ]);
        }

        $this->assertDatabaseHas('akademik_master_siswa', [
            'nis' => $siswaTahunAktif->nis,
            'akademik_master_kelas_id' => $kelasAktif->id,
            'karyawan_nipy' => $karyawan->nipy,
            'akademik_master_ekstrakuler_id' => $ekskulAktif->id,
            'akademik_master_jemputan_id' => $jemputanAktif->id,
        ]);
    }

    public function test_destroy_active_tahun_ajaran_resets_all_students_including_mentor_only_rows(): void
    {
        $user = $this->authenticateUser();

        $taAktif = TahunAjaran::create([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);

        $karyawan = Karyawan::create([
            'nipy' => '19900103-1',
            'nama_lengkap' => 'Ahmad Fauzi',
            'gelar' => 'S.Pd',
        ]);

        $siswaHanyaWali = Siswa::factory()->create([
            'nis' => '2026201',
            'karyawan_nipy' => $karyawan->nipy,
        ]);

        $response = $this->actingAs($user)->delete(route('tahun-ajaran.destroy', $taAktif));

        $response->assertRedirect(route('tahun-ajaran.index'));
        $this->assertDatabaseMissing('akademik_master_tahun_ajaran', ['id' => $taAktif->id]);
        $this->assertDatabaseHas('akademik_master_siswa', [
            'nis' => $siswaHanyaWali->nis,
            'akademik_master_kelas_id' => null,
            'karyawan_nipy' => null,
            'akademik_master_ekstrakuler_id' => null,
            'akademik_master_jemputan_id' => null,
        ]);
    }
}

