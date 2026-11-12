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
use App\Services\Admin\DataMaster\JemputanService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class JemputanTest extends TestCase
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

    private function createTahunAjaran(array $overrides = []): TahunAjaran
    {
        return TahunAjaran::create(array_merge([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ], $overrides));
    }

    private function createKaryawan(array $overrides = []): Karyawan
    {
        return Karyawan::create(array_merge([
            'nipy' => '19900101-1',
            'nama_lengkap' => 'Agus Setiawan',
            'gelar' => 'S.Pd',
            'jabatan' => 'Driver Jemputan',
            'unit' => 'Yayasan',
        ], $overrides));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('jemputan.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_without_sso_token_is_redirected_to_login(): void
    {
        $user = User::create([
            'name' => 'Admin Akademik',
            'email' => 'admin@primainsani.sch.id',
        ]);

        $response = $this->actingAs($user)->get(route('jemputan.index'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_index_displays_jemputan_without_n_plus_one_queries(): void
    {
        $user = $this->authenticateUser();
        $tahunAjaran = $this->createTahunAjaran();

        foreach (range(1, 5) as $index) {
            $karyawan = $this->createKaryawan([
                'nipy' => "NIPY-00{$index}",
                'nama_lengkap' => "Petugas {$index}",
                'gelar' => 'S.Pd',
            ]);

            Jemputan::create([
                'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
                'karyawan_nipy' => $karyawan->nipy,
                'harga_pp' => $index * 50000,
                'harga_setengah' => $index * 30000,
            ]);
        }

        Model::preventLazyLoading(true);

        try {
            DB::enableQueryLog();
            DB::flushQueryLog();

            $service = app(JemputanService::class);
            $items = $service->getAll();
            $serviceQueryCount = count(DB::getQueryLog());

            DB::disableQueryLog();

            $this->assertCount(5, $items);
            $this->assertSame(3, $serviceQueryCount);

            $response = $this->actingAs($user)
                ->withSession(['access_token' => 'valid-token'])
                ->get(route('jemputan.index'));

            $response->assertOk()
                ->assertSee('Petugas 1, S.Pd')
                ->assertSee('Rp 50.000')
                ->assertSee('Rp 30.000')
                ->assertSee('2026/2027');
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    public function test_create_displays_form_with_tahun_ajaran_and_karyawan_options(): void
    {
        $user = $this->authenticateUser();
        $this->createTahunAjaran(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1']);
        $this->createKaryawan(['nipy' => '19910202-1', 'nama_lengkap' => 'Rudi Hartono', 'gelar' => null]);

        $response = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->get(route('jemputan.create'));

        $response->assertOk()
            ->assertSee('Tambah Jemputan Baru')
            ->assertSee('2026/2027')
            ->assertSee('Rudi Hartono (19910202-1)');
    }

    public function test_store_persists_new_jemputan_and_redirects(): void
    {
        $user = $this->authenticateUser();
        $tahunAjaran = $this->createTahunAjaran();
        $karyawan = $this->createKaryawan();

        $response = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->post(route('jemputan.store'), [
                'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
                'karyawan_nipy' => $karyawan->nipy,
                'harga_pp' => 250000,
                'harga_setengah' => 150000,
            ]);

        $response->assertRedirect(route('jemputan.index'))
            ->assertSessionHas('success', 'Data Jemputan berhasil disimpan.');

        $this->assertDatabaseHas('akademik_master_jemputan', [
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'karyawan_nipy' => $karyawan->nipy,
            'harga_pp' => 250000,
            'harga_setengah' => 150000,
        ]);
    }

    public function test_store_accepts_zero_harga_pp_and_harga_setengah(): void
    {
        $user = $this->authenticateUser();
        $tahunAjaran = $this->createTahunAjaran();
        $karyawan = $this->createKaryawan();

        $response = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->post(route('jemputan.store'), [
                'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
                'karyawan_nipy' => $karyawan->nipy,
                'harga_pp' => 0,
                'harga_setengah' => 0,
            ]);

        $response->assertRedirect(route('jemputan.index'));

        $this->assertDatabaseHas('akademik_master_jemputan', [
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'karyawan_nipy' => $karyawan->nipy,
            'harga_pp' => 0,
            'harga_setengah' => 0,
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $user = $this->authenticateUser();

        $response = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->from(route('jemputan.create'))
            ->post(route('jemputan.store'), []);

        $response->assertRedirect(route('jemputan.create'))
            ->assertSessionHasErrors([
                'akademik_master_tahun_ajaran_id' => 'Tahun Ajaran wajib dipilih.',
                'karyawan_nipy' => 'Petugas / Driver Jemputan wajib dipilih.',
                'harga_pp' => 'Harga PP wajib diisi.',
                'harga_setengah' => 'Harga Setengah wajib diisi.',
            ]);
    }

    public function test_store_rejects_negative_tariffs_and_invalid_foreign_keys(): void
    {
        $user = $this->authenticateUser();

        $response = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->from(route('jemputan.create'))
            ->post(route('jemputan.store'), [
                'akademik_master_tahun_ajaran_id' => 9999,
                'karyawan_nipy' => 'TIDAK-ADA',
                'harga_pp' => -5000,
                'harga_setengah' => -2000,
            ]);

        $response->assertRedirect(route('jemputan.create'))
            ->assertSessionHasErrors([
                'akademik_master_tahun_ajaran_id' => 'Tahun Ajaran yang dipilih tidak valid.',
                'karyawan_nipy' => 'Petugas / Driver Jemputan tidak ditemukan dalam database.',
                'harga_pp' => 'Harga PP tidak boleh bernilai negatif.',
                'harga_setengah' => 'Harga Setengah tidak boleh bernilai negatif.',
            ]);
    }

    public function test_edit_and_update_jemputan_persists_changes(): void
    {
        $user = $this->authenticateUser();
        $tahunAjaran = $this->createTahunAjaran();
        $karyawanAwal = $this->createKaryawan(['nipy' => 'DRV-01', 'nama_lengkap' => 'Petugas Satu']);
        $karyawanBaru = $this->createKaryawan(['nipy' => 'DRV-02', 'nama_lengkap' => 'Petugas Dua']);

        $jemputan = Jemputan::create([
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'karyawan_nipy' => $karyawanAwal->nipy,
            'harga_pp' => 200000,
            'harga_setengah' => 100000,
        ]);

        $editResponse = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->get(route('jemputan.edit', $jemputan));

        $editResponse->assertOk()
            ->assertSee('Edit Data Jemputan')
            ->assertSee('200000');

        $updateResponse = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->put(route('jemputan.update', $jemputan), [
                'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
                'karyawan_nipy' => $karyawanBaru->nipy,
                'harga_pp' => 275000,
                'harga_setengah' => 160000,
            ]);

        $updateResponse->assertRedirect(route('jemputan.index'))
            ->assertSessionHas('success', 'Data Jemputan berhasil diperbarui.');

        $this->assertDatabaseHas('akademik_master_jemputan', [
            'id' => $jemputan->id,
            'karyawan_nipy' => $karyawanBaru->nipy,
            'harga_pp' => 275000,
            'harga_setengah' => 160000,
        ]);
    }

    public function test_destroy_deletes_jemputan_and_redirects(): void
    {
        $user = $this->authenticateUser();
        $tahunAjaran = $this->createTahunAjaran();
        $karyawan = $this->createKaryawan();

        $jemputan = Jemputan::create([
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'karyawan_nipy' => $karyawan->nipy,
            'harga_pp' => 200000,
            'harga_setengah' => 100000,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->delete(route('jemputan.destroy', $jemputan));

        $response->assertRedirect(route('jemputan.index'))
            ->assertSessionHas('success', 'Data Jemputan berhasil dihapus.');

        $this->assertDatabaseMissing('akademik_master_jemputan', [
            'id' => $jemputan->id,
        ]);
    }

    public function test_model_accessors_and_karyawan_alias_return_expected_values(): void
    {
        $tahunAjaran = $this->createTahunAjaran();
        $karyawan = $this->createKaryawan();

        $freeItem = Jemputan::factory()->create([
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'karyawan_nipy' => $karyawan->nipy,
            'harga_pp' => 0,
            'harga_setengah' => 0,
        ]);

        $paidItem = Jemputan::factory()->create([
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'karyawan_nipy' => $karyawan->nipy,
            'harga_pp' => 250000,
            'harga_setengah' => 140000,
        ]);

        $this->assertSame('Gratis', $freeItem->harga_pp_formatted);
        $this->assertSame('Gratis', $freeItem->harga_setengah_formatted);
        $this->assertSame($karyawan->nipy, $freeItem->karyawan?->nipy);

        $this->assertSame('Rp 250.000', $paidItem->harga_pp_formatted);
        $this->assertSame('Rp 140.000', $paidItem->harga_setengah_formatted);
    }

    public function test_show_displays_jemputan_detail_with_members_and_active_candidates(): void
    {
        $user = $this->authenticateUser();
        $tahunAjaran = $this->createTahunAjaran();
        $petugas = $this->createKaryawan([
            'nipy' => 'DRV-101',
            'nama_lengkap' => 'Slamet Riyadi',
            'gelar' => null,
        ]);

        $jemputan = Jemputan::create([
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'karyawan_nipy' => $petugas->nipy,
            'harga_pp' => 250000,
            'harga_setengah' => 150000,
        ]);

        $kelas = Kelas::create([
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'karyawan_nipy' => $petugas->nipy,
            'pendamping_nipy' => null,
            'nama_kelas' => '4 Umar',
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => '4',
            'romawi' => 'IV',
        ]);

        $memberSiswa = Siswa::create([
            'nis' => '20260001',
            'nama_lengkap' => 'Aisyah Putri',
            'jenis_kelamin' => 'P',
            'status' => 'Aktif',
        ]);
        $candidateSiswa = Siswa::create([
            'nis' => '20260002',
            'nama_lengkap' => 'Bagas Pratama',
            'jenis_kelamin' => 'L',
            'status' => 'Aktif',
        ]);

        $akMember = AnggotaKelas::create([
            'siswa_nis' => $memberSiswa->nis,
            'akademik_master_kelas_id' => $kelas->id,
            'pendaftaran' => 'Siswa Baru',
        ]);
        AnggotaKelas::create([
            'siswa_nis' => $candidateSiswa->nis,
            'akademik_master_kelas_id' => $kelas->id,
            'pendaftaran' => 'Siswa Baru',
        ]);

        AnggotaJemputan::create([
            'anggota_kelas_id' => $akMember->id,
            'akademik_master_jemputan_id' => $jemputan->id,
            'pendaftaran' => 'Baru',
            'keterangan' => 'Antar Jemput PP',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->get(route('jemputan.show', $jemputan));

        $response->assertOk()
            ->assertSee('Slamet Riyadi')
            ->assertSee('Aisyah Putri')
            ->assertSee('4 Umar')
            ->assertSee('Antar Jemput PP')
            ->assertSee('Bagas Pratama');
    }
}
