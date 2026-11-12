<?php

namespace Tests\Feature;

use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\User;
use App\Services\Admin\DataMaster\KelasService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class KelasTest extends TestCase
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

    public function test_index_displays_student_counts_per_class_without_n_plus_one_queries(): void
    {
        $user = $this->authenticateUser();

        $ta = TahunAjaran::create([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);

        $wali = Karyawan::create([
            'nipy' => '19900101-1',
            'nama_lengkap' => 'Budi Santoso',
            'gelar' => 'S.Pd',
        ]);

        $kelasTerisi = Kelas::create([
            'akademik_master_tahun_ajaran_id' => $ta->id,
            'karyawan_nipy' => $wali->nipy,
            'tingkatan_kelas' => '1',
            'nama_kelas' => '1 Al-Fatihah',
            'jenjang_kelas' => 'SD',
            'romawi' => 'I',
        ]);

        Kelas::create([
            'akademik_master_tahun_ajaran_id' => $ta->id,
            'karyawan_nipy' => $wali->nipy,
            'tingkatan_kelas' => '2',
            'nama_kelas' => '2 Abu Bakar',
            'jenjang_kelas' => 'SD',
            'romawi' => 'II',
        ]);

        foreach (range(1, 3) as $index) {
            $siswa = Siswa::factory()->create([
                'nis' => "202680{$index}",
                'akademik_master_kelas_id' => $kelasTerisi->id,
            ]);

            AnggotaKelas::create([
                'siswa_nis' => $siswa->nis,
                'akademik_master_kelas_id' => $kelasTerisi->id,
                'pendaftaran' => 'Siswa Baru',
            ]);
        }

        Model::preventLazyLoading(true);

        try {
            DB::enableQueryLog();
            DB::flushQueryLog();

            $kelasList = app(KelasService::class)->getAll();
            $queryCount = count(DB::getQueryLog());

            DB::disableQueryLog();

            $this->assertCount(2, $kelasList);
            $this->assertSame(3, $queryCount);
            $this->assertSame(3, (int) $kelasList->firstWhere('id', $kelasTerisi->id)?->anggota_kelas_count);

            $this->actingAs($user)
                ->withSession(['access_token' => 'valid-token'])
                ->get(route('kelas.index'))
                ->assertOk()
                ->assertSee('1 Al-Fatihah')
                ->assertSee('3 Siswa')
                ->assertSee('2 Abu Bakar')
                ->assertSee('0 Siswa');
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    public function test_show_displays_members_and_directly_adds_and_deletes_member_without_filters(): void
    {
        $user = $this->authenticateUser();

        $ta = TahunAjaran::create([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);

        $wali = Karyawan::create([
            'nipy' => '19900101-1',
            'nama_lengkap' => 'Budi Santoso',
            'gelar' => 'S.Pd',
        ]);

        $kelas = Kelas::create([
            'akademik_master_tahun_ajaran_id' => $ta->id,
            'karyawan_nipy' => $wali->nipy,
            'tingkatan_kelas' => '1',
            'nama_kelas' => '1 Al-Fatihah',
            'jenjang_kelas' => 'SD',
            'romawi' => 'I',
        ]);

        $kelasLain = Kelas::create([
            'akademik_master_tahun_ajaran_id' => $ta->id,
            'karyawan_nipy' => $wali->nipy,
            'tingkatan_kelas' => '1',
            'nama_kelas' => '1 Abu Bakar',
            'jenjang_kelas' => 'SD',
            'romawi' => 'I',
        ]);

        $siswaTerdaftar = Siswa::factory()->create([
            'nis' => '2026901',
            'nama_lengkap' => 'Sudah Masuk Kelas Lain',
            'status' => 'Aktif',
        ]);
        AnggotaKelas::create([
            'siswa_nis' => $siswaTerdaftar->nis,
            'akademik_master_kelas_id' => $kelasLain->id,
            'pendaftaran' => 'Siswa Baru',
        ]);

        $siswaKandidat = Siswa::factory()->create([
            'nis' => '2026902',
            'nama_lengkap' => 'Calon Anggota Baru',
            'status' => 'Aktif',
        ]);

        Siswa::factory()->create([
            'nis' => '2026903',
            'nama_lengkap' => 'Siswa Nonaktif Tersembunyi',
            'status' => 'Nonaktif',
        ]);

        Model::preventLazyLoading(true);

        try {
            $this->actingAs($user)
                ->withSession(['access_token' => 'valid-token'])
                ->get(route('kelas.show', $kelas->id))
                ->assertOk()
                ->assertSee('1 Al-Fatihah')
                ->assertSee('Calon Anggota Baru')
                ->assertDontSee('Sudah Masuk Kelas Lain')
                ->assertDontSee('Siswa Nonaktif Tersembunyi');
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->post(route('anggota-kelas.store'), [
                'akademik_master_kelas_id' => $kelas->id,
                'redirect_to_kelas' => $kelas->id,
                'pendaftaran' => 'Siswa Baru',
                'siswa_nis' => [$siswaKandidat->nis],
            ])
            ->assertRedirect(route('kelas.show', $kelas->id));

        $createdMember = AnggotaKelas::query()
            ->where('siswa_nis', $siswaKandidat->nis)
            ->where('akademik_master_kelas_id', $kelas->id)
            ->firstOrFail();

        $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->delete(route('anggota-kelas.destroy', $createdMember->id), [
                'redirect_to_kelas' => $kelas->id,
            ])
            ->assertRedirect(route('kelas.show', $kelas->id));

        $this->assertDatabaseMissing('akademik_master_anggota_kelas', [
            'id' => $createdMember->id,
        ]);
    }

    public function test_index_defaults_to_active_tahun_ajaran_and_filters_by_jenjang_with_counts(): void
    {
        $user = $this->authenticateUser();

        $taLama = TahunAjaran::create([
            'nama_tahun_ajaran' => '2025/2026',
            'semester' => '2',
            'is_active' => false,
        ]);

        $taAktif = TahunAjaran::create([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);

        $wali = Karyawan::create([
            'nipy' => '19900101-2',
            'nama_lengkap' => 'Siti Aminah',
            'gelar' => 'M.Pd',
        ]);

        $kelasTk = Kelas::create([
            'akademik_master_tahun_ajaran_id' => $taAktif->id,
            'karyawan_nipy' => $wali->nipy,
            'tingkatan_kelas' => 'TK-A',
            'nama_kelas' => 'TK A Makkah',
            'jenjang_kelas' => 'TK',
            'romawi' => 'A',
        ]);

        $kelasSd = Kelas::create([
            'akademik_master_tahun_ajaran_id' => $taAktif->id,
            'karyawan_nipy' => $wali->nipy,
            'tingkatan_kelas' => '1',
            'nama_kelas' => '1 Madinah',
            'jenjang_kelas' => 'SD',
            'romawi' => 'I',
        ]);

        Kelas::create([
            'akademik_master_tahun_ajaran_id' => $taLama->id,
            'karyawan_nipy' => $wali->nipy,
            'tingkatan_kelas' => '6',
            'nama_kelas' => '6 Andalusia (Lama)',
            'jenjang_kelas' => 'SD',
            'romawi' => 'VI',
        ]);

        foreach (range(1, 2) as $i) {
            $siswa = Siswa::factory()->create(['nis' => "202699{$i}", 'status' => 'Aktif']);
            AnggotaKelas::create([
                'siswa_nis' => $siswa->nis,
                'akademik_master_kelas_id' => $kelasSd->id,
                'pendaftaran' => 'Siswa Baru',
            ]);
        }

        // Default index hanya menampilkan TA aktif + statistik per jenjang (TK & SD)
        $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->get(route('kelas.index'))
            ->assertOk()
            ->assertSee($kelasTk->nama_kelas)
            ->assertSee($kelasSd->nama_kelas)
            ->assertDontSee('6 Andalusia (Lama)')
            ->assertSee('Jenjang TK')
            ->assertSee('Jenjang SD')
            ->assertSee('2 Siswa');

        // Filter jenjang=SD pada TA aktif menyaring tabel ke SD namun kartu statistik TK & SD tetap terlihat
        $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->get(route('kelas.index', ['tahun_ajaran_id' => $taAktif->id, 'jenjang' => 'SD']))
            ->assertOk()
            ->assertSee($kelasSd->nama_kelas)
            ->assertDontSee($kelasTk->nama_kelas)
            ->assertSee('Jenjang TK');

        // Menggunakan tahun_ajaran_id=all menampilkan kelas dari semua tahun ajaran
        $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->get(route('kelas.index', ['tahun_ajaran_id' => 'all']))
            ->assertOk()
            ->assertSee($kelasTk->nama_kelas)
            ->assertSee($kelasSd->nama_kelas)
            ->assertSee('6 Andalusia (Lama)');
    }
}
