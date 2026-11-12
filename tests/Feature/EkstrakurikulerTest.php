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
use App\Services\Admin\DataMaster\EkstrakurikulerService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EkstrakurikulerTest extends TestCase
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
            'nama_lengkap' => 'Budi Santoso',
            'gelar' => 'S.Pd',
            'jabatan' => 'Guru Olahraga',
            'unit' => 'SD',
        ], $overrides));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('ekstrakurikuler.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_without_sso_token_is_redirected_to_login(): void
    {
        $user = User::create([
            'name' => 'Admin Akademik',
            'email' => 'admin@primainsani.sch.id',
        ]);

        $response = $this->actingAs($user)->get(route('ekstrakurikuler.index'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_index_displays_ekstrakurikuler_without_n_plus_one_queries(): void
    {
        $user = $this->authenticateUser();
        $tahunAjaran = $this->createTahunAjaran();

        foreach (range(1, 5) as $index) {
            $karyawan = $this->createKaryawan([
                'nipy' => "NIPY-00{$index}",
                'nama_lengkap' => "Pembina {$index}",
                'gelar' => 'S.Pd',
            ]);

            Ekstrakurikuler::create([
                'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
                'karyawan_nipy' => $karyawan->nipy,
                'nama_ekstrakurikuler' => "Ekskul {$index}",
                'biaya' => $index * 25000,
                'unit' => 'SD',
            ]);
        }

        Ekstrakurikuler::create([
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'karyawan_nipy' => null,
            'nama_ekstrakurikuler' => 'Pramuka Wajib',
            'biaya' => 0,
            'unit' => 'SMP',
        ]);

        Model::preventLazyLoading(true);

        try {
            DB::enableQueryLog();
            DB::flushQueryLog();

            $service = app(EkstrakurikulerService::class);
            $items = $service->getAll();
            $serviceQueryCount = count(DB::getQueryLog());

            DB::disableQueryLog();

            $this->assertCount(6, $items);
            $this->assertSame(3, $serviceQueryCount);

            $response = $this->actingAs($user)
                ->withSession(['access_token' => 'valid-token'])
                ->get(route('ekstrakurikuler.index'));

            $response->assertOk()
                ->assertSee('Ekskul 1')
                ->assertSee('Pembina 1, S.Pd')
                ->assertSee('Rp 25.000')
                ->assertSee('Pramuka Wajib')
                ->assertSee('Gratis')
                ->assertSee('Pembina belum ditentukan');
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    public function test_create_displays_form_with_tahun_ajaran_and_karyawan_options(): void
    {
        $user = $this->authenticateUser();
        $this->createTahunAjaran(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1']);
        $this->createKaryawan(['nipy' => '19910202-1', 'nama_lengkap' => 'Siti Aminah', 'gelar' => 'M.Pd']);

        $response = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->get(route('ekstrakurikuler.create'));

        $response->assertOk()
            ->assertSee('Tambah Ekstrakurikuler Baru')
            ->assertSee('2026/2027')
            ->assertSee('Siti Aminah, M.Pd (19910202-1)');
    }

    public function test_store_persists_new_ekstrakurikuler_and_redirects(): void
    {
        $user = $this->authenticateUser();
        $tahunAjaran = $this->createTahunAjaran();
        $karyawan = $this->createKaryawan();

        $response = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->post(route('ekstrakurikuler.store'), [
                'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
                'karyawan_nipy' => $karyawan->nipy,
                'nama_ekstrakurikuler' => 'Panahan',
                'biaya' => 150000,
                'unit' => 'SD',
            ]);

        $response->assertRedirect(route('ekstrakurikuler.index'))
            ->assertSessionHas('success', 'Data Ekstrakurikuler berhasil disimpan.');

        $this->assertDatabaseHas('akademik_master_ekstrakuler', [
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'karyawan_nipy' => $karyawan->nipy,
            'nama_ekstrakurikuler' => 'Panahan',
            'biaya' => 150000,
            'unit' => 'SD',
        ]);
    }

    public function test_store_accepts_zero_biaya_and_nullable_karyawan_nipy(): void
    {
        $user = $this->authenticateUser();
        $tahunAjaran = $this->createTahunAjaran();

        $response = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->post(route('ekstrakurikuler.store'), [
                'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
                'karyawan_nipy' => '',
                'nama_ekstrakurikuler' => 'Pramuka',
                'biaya' => 0,
                'unit' => 'SMP',
            ]);

        $response->assertRedirect(route('ekstrakurikuler.index'));

        $this->assertDatabaseHas('akademik_master_ekstrakuler', [
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'karyawan_nipy' => null,
            'nama_ekstrakurikuler' => 'Pramuka',
            'biaya' => 0,
            'unit' => 'SMP',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $user = $this->authenticateUser();

        $response = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->from(route('ekstrakurikuler.create'))
            ->post(route('ekstrakurikuler.store'), []);

        $response->assertRedirect(route('ekstrakurikuler.create'))
            ->assertSessionHasErrors([
                'akademik_master_tahun_ajaran_id' => 'Tahun Ajaran wajib dipilih.',
                'nama_ekstrakurikuler' => 'Nama Ekstrakurikuler wajib diisi.',
                'biaya' => 'Biaya Ekstrakurikuler wajib diisi.',
                'unit' => 'Unit wajib dipilih.',
            ]);
    }

    public function test_store_rejects_negative_biaya_and_invalid_foreign_keys(): void
    {
        $user = $this->authenticateUser();

        $response = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->from(route('ekstrakurikuler.create'))
            ->post(route('ekstrakurikuler.store'), [
                'akademik_master_tahun_ajaran_id' => 9999,
                'karyawan_nipy' => 'TIDAK-ADA',
                'nama_ekstrakurikuler' => 'Robotik',
                'biaya' => -5000,
                'unit' => 'SD',
            ]);

        $response->assertRedirect(route('ekstrakurikuler.create'))
            ->assertSessionHasErrors([
                'akademik_master_tahun_ajaran_id' => 'Tahun Ajaran yang dipilih tidak valid.',
                'karyawan_nipy' => 'Pembina / Pelatih tidak ditemukan dalam database.',
                'biaya' => 'Biaya Ekstrakurikuler tidak boleh bernilai negatif.',
            ]);
    }

    public function test_store_rejects_duplicate_name_in_same_unit_and_tahun_ajaran_but_allows_other_unit(): void
    {
        $user = $this->authenticateUser();
        $tahunAjaran = $this->createTahunAjaran();

        Ekstrakurikuler::create([
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'karyawan_nipy' => null,
            'nama_ekstrakurikuler' => 'Futsal',
            'biaya' => 50000,
            'unit' => 'SD',
        ]);

        $duplicateResponse = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->from(route('ekstrakurikuler.create'))
            ->post(route('ekstrakurikuler.store'), [
                'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
                'karyawan_nipy' => null,
                'nama_ekstrakurikuler' => 'Futsal',
                'biaya' => 60000,
                'unit' => 'SD',
            ]);

        $duplicateResponse->assertRedirect(route('ekstrakurikuler.create'))
            ->assertSessionHasErrors([
                'nama_ekstrakurikuler' => 'Ekstrakurikuler dengan nama tersebut sudah ada pada unit dan tahun ajaran yang dipilih.',
            ]);

        $otherUnitResponse = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->post(route('ekstrakurikuler.store'), [
                'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
                'karyawan_nipy' => null,
                'nama_ekstrakurikuler' => 'Futsal',
                'biaya' => 60000,
                'unit' => 'SMP',
            ]);

        $otherUnitResponse->assertRedirect(route('ekstrakurikuler.index'));
        $this->assertDatabaseCount('akademik_master_ekstrakuler', 2);
    }

    public function test_edit_and_update_ekstrakurikuler_persists_changes_and_ignores_self_unique(): void
    {
        $user = $this->authenticateUser();
        $tahunAjaran = $this->createTahunAjaran();
        $karyawan = $this->createKaryawan();

        $ekstrakurikuler = Ekstrakurikuler::create([
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'karyawan_nipy' => null,
            'nama_ekstrakurikuler' => 'Tahfidz',
            'biaya' => 0,
            'unit' => 'SD',
        ]);

        $editResponse = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->get(route('ekstrakurikuler.edit', $ekstrakurikuler));

        $editResponse->assertOk()
            ->assertSee('Edit Data Ekstrakurikuler')
            ->assertSee('Tahfidz');

        $updateResponse = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->put(route('ekstrakurikuler.update', $ekstrakurikuler), [
                'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
                'karyawan_nipy' => $karyawan->nipy,
                'nama_ekstrakurikuler' => 'Tahfidz',
                'biaya' => 75000,
                'unit' => 'SD',
            ]);

        $updateResponse->assertRedirect(route('ekstrakurikuler.index'))
            ->assertSessionHas('success', 'Data Ekstrakurikuler berhasil diperbarui.');

        $this->assertDatabaseHas('akademik_master_ekstrakuler', [
            'id' => $ekstrakurikuler->id,
            'karyawan_nipy' => $karyawan->nipy,
            'nama_ekstrakurikuler' => 'Tahfidz',
            'biaya' => 75000,
            'unit' => 'SD',
        ]);
    }

    public function test_destroy_deletes_ekstrakurikuler_and_redirects(): void
    {
        $user = $this->authenticateUser();
        $tahunAjaran = $this->createTahunAjaran();

        $ekstrakurikuler = Ekstrakurikuler::create([
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'karyawan_nipy' => null,
            'nama_ekstrakurikuler' => 'Melukis',
            'biaya' => 30000,
            'unit' => 'TK',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->delete(route('ekstrakurikuler.destroy', $ekstrakurikuler));

        $response->assertRedirect(route('ekstrakurikuler.index'))
            ->assertSessionHas('success', 'Data Ekstrakurikuler berhasil dihapus.');

        $this->assertDatabaseMissing('akademik_master_ekstrakuler', [
            'id' => $ekstrakurikuler->id,
        ]);
    }

    public function test_model_accessors_and_karyawan_alias_return_expected_values(): void
    {
        $tahunAjaran = $this->createTahunAjaran();
        $karyawan = $this->createKaryawan();

        $freeItem = Ekstrakurikuler::factory()->create([
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'karyawan_nipy' => $karyawan->nipy,
            'biaya' => 0,
            'unit' => 'TK',
        ]);

        $paidItem = Ekstrakurikuler::factory()->create([
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'karyawan_nipy' => null,
            'biaya' => 125000,
            'unit' => 'SMP',
        ]);

        $this->assertSame('Gratis', $freeItem->biaya_formatted);
        $this->assertSame('badge-warning text-dark', $freeItem->unit_badge_class);
        $this->assertSame($karyawan->nipy, $freeItem->karyawan?->nipy);

        $this->assertSame('Rp 125.000', $paidItem->biaya_formatted);
        $this->assertSame('badge-primary', $paidItem->unit_badge_class);
    }

    public function test_show_displays_members_and_modal_candidate_anggota_kelas(): void
    {
        $user = $this->authenticateUser();
        $tahunAjaran = $this->createTahunAjaran();
        $karyawan = $this->createKaryawan();

        $kelas = Kelas::create([
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'karyawan_nipy' => $karyawan->nipy,
            'nama_kelas' => '4 Umar',
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => '4',
            'romawi' => 'IV',
        ]);

        $ekstrakurikuler = Ekstrakurikuler::create([
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'karyawan_nipy' => $karyawan->nipy,
            'nama_ekstrakurikuler' => 'Panahan',
            'biaya' => 50000,
            'unit' => 'SD',
        ]);

        $memberSiswa = Siswa::create([
            'nis' => '20269001',
            'nama_lengkap' => 'Rafi Ahmad',
            'jenis_kelamin' => 'L',
            'status' => 'Aktif',
        ]);
        $candidateSiswa = Siswa::create([
            'nis' => '20269002',
            'nama_lengkap' => 'Dina Marlina',
            'jenis_kelamin' => 'P',
            'status' => 'Aktif',
        ]);

        $memberAk = AnggotaKelas::create([
            'siswa_nis' => $memberSiswa->nis,
            'akademik_master_kelas_id' => $kelas->id,
            'pendaftaran' => 'Siswa Baru',
        ]);
        AnggotaKelas::create([
            'siswa_nis' => $candidateSiswa->nis,
            'akademik_master_kelas_id' => $kelas->id,
            'pendaftaran' => 'Siswa Baru',
        ]);

        AnggotaEkstrakurikuler::create([
            'anggota_kelas_id' => $memberAk->id,
            'akademik_master_ekstrakuler_id' => $ekstrakurikuler->id,
            'pendaftaran' => 'Baru',
            'keterangan' => 'Inti',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->get(route('ekstrakurikuler.show', $ekstrakurikuler));

        $response->assertOk()
            ->assertSee('Panahan')
            ->assertSee('Rafi Ahmad')
            ->assertSee('4 Umar')
            ->assertSee('Inti')
            ->assertSee('Dina Marlina');
    }
}
