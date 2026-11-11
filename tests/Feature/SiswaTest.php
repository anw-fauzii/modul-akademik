<?php

namespace Tests\Feature;

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
use Database\Seeders\SiswaSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiswaTest extends TestCase
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

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('siswa.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_without_sso_token_is_redirected_to_login(): void
    {
        $user = User::create([
            'name' => 'Admin Akademik',
            'email' => 'admin@primainsani.sch.id',
        ]);

        $response = $this->actingAs($user)->get(route('siswa.index'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_index_and_show_render_without_n_plus_one_queries(): void
    {
        $user = $this->authenticateUser();

        $tahunAjaran = TahunAjaran::create([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);

        $guru = Karyawan::create([
            'nipy' => '19900101-1',
            'nama_lengkap' => 'Budi Santoso',
            'gelar' => 'S.Pd',
        ]);

        $driver = Karyawan::create([
            'nipy' => '19920202-1',
            'nama_lengkap' => 'Agus Supriyadi',
        ]);

        $kelas = Kelas::create([
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'karyawan_nipy' => $guru->nipy,
            'tingkatan_kelas' => '1',
            'nama_kelas' => '1 Al-Fatihah',
            'jenjang_kelas' => 'SD',
            'romawi' => 'I',
        ]);

        $ekskul = Ekstrakurikuler::create([
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'karyawan_nipy' => $guru->nipy,
            'nama_ekstrakurikuler' => 'Panahan',
            'biaya' => 50000,
            'unit' => 'SD',
        ]);

        $jemputan = Jemputan::create([
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'karyawan_nipy' => $driver->nipy,
            'harga_pp' => 250000,
            'harga_setengah' => 150000,
        ]);

        foreach (range(1, 5) as $index) {
            Siswa::create([
                'nis' => "2026000{$index}",
                'akademik_master_kelas_id' => $kelas->id,
                'karyawan_nipy' => $guru->nipy,
                'akademik_master_ekstrakuler_id' => $ekskul->id,
                'akademik_master_jemputan_id' => $jemputan->id,
                'nama_lengkap' => "Siswa {$index}",
                'jenis_kelamin' => $index % 2 === 0 ? 'P' : 'L',
                'status' => $index === 5 ? 'Pindah' : 'Aktif',
            ]);
        }

        Model::preventLazyLoading(true);

        try {
            DB::enableQueryLog();
            DB::flushQueryLog();

            $items = app(SiswaService::class)->getFiltered(['status' => 'Aktif']);
            $queryCount = count(DB::getQueryLog());

            DB::disableQueryLog();

            $this->assertCount(4, $items);
            $this->assertSame(6, $queryCount);

            $this->actingAs($user)
                ->withSession(['access_token' => 'valid-token'])
                ->get(route('siswa.index'))
                ->assertOk()
                ->assertSee('Pilih Filter untuk Menampilkan Daftar Siswa')
                ->assertSee('Total Seluruh Siswa')
                ->assertDontSee('Siswa 1');

            $this->actingAs($user)
                ->withSession(['access_token' => 'valid-token'])
                ->get(route('siswa.index', ['status' => 'Aktif']))
                ->assertOk()
                ->assertSee('Siswa 1')
                ->assertSee('1 Al-Fatihah')
                ->assertSee('Budi Santoso, S.Pd')
                ->assertSee('Panahan')
                ->assertSee('Agus Supriyadi')
                ->assertDontSee('Siswa 5');

            $this->actingAs($user)
                ->withSession(['access_token' => 'valid-token'])
                ->get(route('siswa.index', ['show_all' => 1]))
                ->assertOk()
                ->assertSee('Siswa 1')
                ->assertSee('Siswa 5');

            $this->actingAs($user)
                ->withSession(['access_token' => 'valid-token'])
                ->get(route('siswa.show', '20260001'))
                ->assertOk()
                ->assertSee('Siswa 1')
                ->assertSee('1 Al-Fatihah');
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    public function test_store_persists_siswa_with_pelengkap_relations_and_avatar(): void
    {
        Storage::fake('public');
        $user = $this->authenticateUser();

        $khusus = BerkebutuhanKhusus::create(['nama_berkebutuhan_khusus' => 'Tidak Ada']);
        $transportasi = Transportasi::create(['nama_transportasi' => 'Sepeda Motor']);
        $jenjang = JenjangPendidikan::create(['nama_jenjang_pendidikan' => 'S1']);
        $pekerjaan = Pekerjaan::create(['nama_pekerjaan' => 'Karyawan Swasta']);
        $penghasilan = Penghasilan::create(['nama_penghasilan' => 'Rp 5.000.000 - Rp 10.000.000']);

        $response = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->post(route('siswa.store'), [
                'nis' => '202601001',
                'nama_lengkap' => 'Ahmad Fauzi',
                'jenis_kelamin' => 'L',
                'akademik_pelengkap_berkebutuhan_khusus_id' => $khusus->id,
                'akademik_pelengkap_transportasi_id' => $transportasi->id,
                'nama_ayah' => 'Hendra',
                'akademik_pelengkap_jenjang_pendidikan_ayah_id' => $jenjang->id,
                'akademik_pelengkap_pekerjaan_ayah_id' => $pekerjaan->id,
                'akademik_pelengkap_penghasilan_ayah_id' => $penghasilan->id,
                'akademik_pelengkap_berkebutuhan_khusus_ayah_id' => $khusus->id,
                'avatar' => UploadedFile::fake()->image('siswa.jpg'),
                'status' => 'Aktif',
            ]);

        $response->assertRedirect(route('siswa.index'))
            ->assertSessionHas('success', 'Data Siswa berhasil disimpan.');

        $siswa = Siswa::find('202601001');
        $this->assertNotNull($siswa);
        $this->assertSame('Ahmad Fauzi', $siswa->nama_lengkap);
        $this->assertNotNull($siswa->avatar);
        Storage::disk('public')->assertExists($siswa->avatar);
    }

    public function test_store_validates_required_fields_and_unique_nis(): void
    {
        $user = $this->authenticateUser();

        Siswa::factory()->create(['nis' => '202601001']);

        $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->from(route('siswa.create'))
            ->post(route('siswa.store'), [])
            ->assertRedirect(route('siswa.create'))
            ->assertSessionHasErrors([
                'nis' => 'NIS wajib diisi.',
                'nama_lengkap' => 'Nama lengkap siswa wajib diisi.',
                'jenis_kelamin' => 'Jenis kelamin wajib dipilih.',
                'status' => 'Status siswa wajib dipilih.',
            ]);

        $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->from(route('siswa.create'))
            ->post(route('siswa.store'), [
                'nis' => '202601001',
                'nama_lengkap' => 'Duplikat',
                'jenis_kelamin' => 'L',
                'status' => 'Aktif',
            ])
            ->assertRedirect(route('siswa.create'))
            ->assertSessionHasErrors([
                'nis' => 'NIS tersebut sudah terdaftar pada siswa lain.',
            ]);
    }

    public function test_update_and_destroy_manage_siswa_and_avatar_cleanup(): void
    {
        Storage::fake('public');
        $user = $this->authenticateUser();

        $oldAvatar = UploadedFile::fake()->image('old.jpg')->store('siswa/avatars', 'public');
        $siswa = Siswa::factory()->create([
            'nis' => '202601009',
            'nama_lengkap' => 'Nama Lama',
            'jenis_kelamin' => 'P',
            'avatar' => $oldAvatar,
            'status' => 'Aktif',
        ]);

        $newAvatar = UploadedFile::fake()->image('new.jpg');

        $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->put(route('siswa.update', $siswa->nis), [
                'nis' => '202601009',
                'nama_lengkap' => 'Nama Baru',
                'jenis_kelamin' => 'P',
                'avatar' => $newAvatar,
                'status' => 'Lulus',
            ])
            ->assertRedirect(route('siswa.index'))
            ->assertSessionHas('success', 'Data Siswa berhasil diperbarui.');

        $siswa->refresh();
        $this->assertSame('Nama Baru', $siswa->nama_lengkap);
        $this->assertSame('Lulus', $siswa->status);
        Storage::disk('public')->assertMissing($oldAvatar);
        Storage::disk('public')->assertExists($siswa->avatar);

        $currentAvatar = $siswa->avatar;

        $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->delete(route('siswa.destroy', $siswa->nis))
            ->assertRedirect(route('siswa.index'))
            ->assertSessionHas('success', 'Data Siswa berhasil dihapus.');

        $this->assertDatabaseMissing('akademik_master_siswa', ['nis' => '202601009']);
        Storage::disk('public')->assertMissing($currentAvatar);
    }

    public function test_siswa_seeder_populates_dummy_students_idempotently_and_syncs_anggota_kelas(): void
    {
        $ta = TahunAjaran::create([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);

        Karyawan::create([
            'nipy' => '19900101-1',
            'nama_lengkap' => 'Budi Santoso',
            'gelar' => 'S.Pd',
        ]);

        Kelas::create([
            'akademik_master_tahun_ajaran_id' => $ta->id,
            'karyawan_nipy' => '19900101-1',
            'tingkatan_kelas' => '1',
            'nama_kelas' => '1 Al-Fatihah',
            'jenjang_kelas' => 'SD',
            'romawi' => 'I',
        ]);

        $this->seed(SiswaSeeder::class);
        $this->seed(SiswaSeeder::class);

        $this->assertSame(20, Siswa::query()->count());
        $this->assertDatabaseHas('akademik_master_siswa', [
            'nis' => '20260001',
            'nama_lengkap' => 'Ahmad Fauzi Pratama',
            'status' => 'Aktif',
        ]);

        $activeCount = Siswa::query()->where('status', 'Aktif')->count();
        $this->assertSame($activeCount, AnggotaKelas::query()->count());
    }
}
