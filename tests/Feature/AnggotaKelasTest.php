<?php

namespace Tests\Feature;

use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\User;
use App\Services\Admin\DataMaster\AnggotaKelasService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AnggotaKelasTest extends TestCase
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
        $response = $this->get(route('anggota-kelas.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_without_sso_token_is_redirected_to_login(): void
    {
        $user = User::create([
            'name' => 'Admin Akademik',
            'email' => 'admin@primainsani.sch.id',
        ]);

        $response = $this->actingAs($user)->get(route('anggota-kelas.index'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_index_and_filters_execute_without_n_plus_one_queries(): void
    {
        $user = $this->authenticateUser();

        $ta1 = TahunAjaran::create([
            'nama_tahun_ajaran' => '2025/2026',
            'semester' => '1',
            'is_active' => false,
        ]);

        $ta2 = TahunAjaran::create([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);

        $wali = Karyawan::create([
            'nipy' => '19900101-1',
            'nama_lengkap' => 'Budi Santoso',
            'gelar' => 'S.Pd',
        ]);

        $kelasTa1 = Kelas::create([
            'akademik_master_tahun_ajaran_id' => $ta1->id,
            'karyawan_nipy' => $wali->nipy,
            'tingkatan_kelas' => '1',
            'nama_kelas' => '1 Al-Fatihah',
            'jenjang_kelas' => 'SD',
            'romawi' => 'I',
        ]);

        $kelasTa2 = Kelas::create([
            'akademik_master_tahun_ajaran_id' => $ta2->id,
            'karyawan_nipy' => $wali->nipy,
            'tingkatan_kelas' => '2',
            'nama_kelas' => '2 Al-Baqarah',
            'jenjang_kelas' => 'SD',
            'romawi' => 'II',
        ]);

        foreach (range(1, 5) as $index) {
            $siswa = Siswa::factory()->create([
                'nis' => "2026000{$index}",
                'nama_lengkap' => "Siswa Ke-{$index}",
                'status' => 'Aktif',
            ]);

            AnggotaKelas::create([
                'siswa_nis' => $siswa->nis,
                'akademik_master_kelas_id' => $index <= 2 ? $kelasTa1->id : $kelasTa2->id,
                'pendaftaran' => $index <= 2 ? 'Siswa Baru' : 'Naik Kelas',
            ]);
        }

        Model::preventLazyLoading(true);

        try {
            DB::enableQueryLog();
            DB::flushQueryLog();

            $items = app(AnggotaKelasService::class)->getAll();
            $queryCount = count(DB::getQueryLog());

            DB::disableQueryLog();

            $this->assertCount(5, $items);
            $this->assertSame(5, $queryCount);

            $this->actingAs($user)
                ->withSession(['access_token' => 'valid-token'])
                ->get(route('anggota-kelas.index'))
                ->assertOk()
                ->assertSee('Siswa Ke-1')
                ->assertSee('Siswa Ke-5')
                ->assertSee('Budi Santoso, S.Pd');

            $this->actingAs($user)
                ->withSession(['access_token' => 'valid-token'])
                ->get(route('anggota-kelas.index', ['tahun_ajaran_id' => $ta1->id]))
                ->assertOk()
                ->assertSee('Siswa Ke-1')
                ->assertDontSee('Siswa Ke-5');

            $this->actingAs($user)
                ->withSession(['access_token' => 'valid-token'])
                ->get(route('anggota-kelas.index', ['kelas_id' => $kelasTa2->id]))
                ->assertOk()
                ->assertSee('Siswa Ke-3')
                ->assertDontSee('Siswa Ke-1');
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    public function test_store_supports_single_and_batch_students_and_syncs_active_year_class(): void
    {
        $user = $this->authenticateUser();

        $taInactive = TahunAjaran::create([
            'nama_tahun_ajaran' => '2025/2026',
            'semester' => '1',
            'is_active' => false,
        ]);

        $taActive = TahunAjaran::create([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);

        $kelasInactive = Kelas::create([
            'akademik_master_tahun_ajaran_id' => $taInactive->id,
            'tingkatan_kelas' => '1',
            'nama_kelas' => '1 Lama',
            'jenjang_kelas' => 'SD',
            'romawi' => 'I',
        ]);

        $kelasActive = Kelas::create([
            'akademik_master_tahun_ajaran_id' => $taActive->id,
            'tingkatan_kelas' => '2',
            'nama_kelas' => '2 Aktif',
            'jenjang_kelas' => 'SD',
            'romawi' => 'II',
        ]);

        $s1 = Siswa::factory()->create(['nis' => '2026101', 'akademik_master_kelas_id' => null]);
        $s2 = Siswa::factory()->create(['nis' => '2026102', 'akademik_master_kelas_id' => null]);
        $s3 = Siswa::factory()->create(['nis' => '2026103', 'akademik_master_kelas_id' => $kelasActive->id]);

        $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->post(route('anggota-kelas.store'), [
                'siswa_nis' => $s1->nis,
                'akademik_master_kelas_id' => $kelasActive->id,
                'pendaftaran' => 'Siswa Baru',
            ])
            ->assertRedirect(route('anggota-kelas.index'))
            ->assertSessionHas('success', '1 Anggota Kelas berhasil disimpan.');

        $this->assertSame($kelasActive->id, $s1->fresh()->akademik_master_kelas_id);

        $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->post(route('anggota-kelas.store'), [
                'siswa_nis' => [$s2->nis, $s3->nis],
                'akademik_master_kelas_id' => $kelasInactive->id,
                'pendaftaran' => 'Naik Kelas',
            ])
            ->assertRedirect(route('anggota-kelas.index'))
            ->assertSessionHas('success', '2 Anggota Kelas berhasil disimpan.');

        $this->assertDatabaseHas('akademik_master_anggota_kelas', [
            'siswa_nis' => $s2->nis,
            'akademik_master_kelas_id' => $kelasInactive->id,
            'pendaftaran' => 'Naik Kelas',
        ]);
        $this->assertDatabaseHas('akademik_master_anggota_kelas', [
            'siswa_nis' => $s3->nis,
            'akademik_master_kelas_id' => $kelasInactive->id,
            'pendaftaran' => 'Naik Kelas',
        ]);

        $this->assertNull($s2->fresh()->akademik_master_kelas_id);
        $this->assertSame($kelasActive->id, $s3->fresh()->akademik_master_kelas_id);
    }

    public function test_store_rejects_duplicate_student_in_same_tahun_ajaran_across_different_classes(): void
    {
        $user = $this->authenticateUser();

        $ta = TahunAjaran::create([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);

        $kelasA = Kelas::create([
            'akademik_master_tahun_ajaran_id' => $ta->id,
            'tingkatan_kelas' => '1',
            'nama_kelas' => '1A',
            'jenjang_kelas' => 'SD',
            'romawi' => 'I',
        ]);

        $kelasB = Kelas::create([
            'akademik_master_tahun_ajaran_id' => $ta->id,
            'tingkatan_kelas' => '1',
            'nama_kelas' => '1B',
            'jenjang_kelas' => 'SD',
            'romawi' => 'I',
        ]);

        $siswa = Siswa::factory()->create(['nis' => '2026201']);

        AnggotaKelas::create([
            'siswa_nis' => $siswa->nis,
            'akademik_master_kelas_id' => $kelasA->id,
            'pendaftaran' => 'Siswa Baru',
        ]);

        $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->from(route('anggota-kelas.create'))
            ->post(route('anggota-kelas.store'), [
                'siswa_nis' => [$siswa->nis],
                'akademik_master_kelas_id' => $kelasB->id,
                'pendaftaran' => 'Pindahan',
            ])
            ->assertRedirect(route('anggota-kelas.create'))
            ->assertSessionHasErrors('siswa_nis');

        $this->assertDatabaseMissing('akademik_master_anggota_kelas', [
            'siswa_nis' => $siswa->nis,
            'akademik_master_kelas_id' => $kelasB->id,
        ]);

        $this->expectException(ValidationException::class);
        app(AnggotaKelasService::class)->store([
            'siswa_nis' => [$siswa->nis],
            'akademik_master_kelas_id' => $kelasB->id,
            'pendaftaran' => 'Pindahan',
        ]);
    }

    public function test_same_student_can_join_classes_across_different_semesters_and_years(): void
    {
        $user = $this->authenticateUser();

        $taSmt1 = TahunAjaran::create([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => false,
        ]);

        $taSmt2 = TahunAjaran::create([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '2',
            'is_active' => true,
        ]);

        $kelasSmt1 = Kelas::create([
            'akademik_master_tahun_ajaran_id' => $taSmt1->id,
            'tingkatan_kelas' => '1',
            'nama_kelas' => '1 Al-Fatihah (Ganjil)',
            'jenjang_kelas' => 'SD',
            'romawi' => 'I',
        ]);

        $kelasSmt2 = Kelas::create([
            'akademik_master_tahun_ajaran_id' => $taSmt2->id,
            'tingkatan_kelas' => '1',
            'nama_kelas' => '1 Al-Fatihah (Genap)',
            'jenjang_kelas' => 'SD',
            'romawi' => 'I',
        ]);

        $siswa = Siswa::factory()->create(['nis' => '2026301']);

        $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->post(route('anggota-kelas.store'), [
                'siswa_nis' => $siswa->nis,
                'akademik_master_kelas_id' => $kelasSmt1->id,
                'pendaftaran' => 'Siswa Baru',
            ])
            ->assertRedirect(route('anggota-kelas.index'));

        $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->post(route('anggota-kelas.store'), [
                'siswa_nis' => $siswa->nis,
                'akademik_master_kelas_id' => $kelasSmt2->id,
                'pendaftaran' => 'Naik Kelas',
            ])
            ->assertRedirect(route('anggota-kelas.index'));

        $this->assertSame(
            2,
            AnggotaKelas::query()->where('siswa_nis', $siswa->nis)->count()
        );
        $this->assertSame($kelasSmt2->id, $siswa->fresh()->akademik_master_kelas_id);
    }

    public function test_update_and_destroy_manage_membership_and_sync_siswa_kelas(): void
    {
        $user = $this->authenticateUser();

        $taActive = TahunAjaran::create([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);

        $kelasA = Kelas::create([
            'akademik_master_tahun_ajaran_id' => $taActive->id,
            'tingkatan_kelas' => '1',
            'nama_kelas' => '1A',
            'jenjang_kelas' => 'SD',
            'romawi' => 'I',
        ]);

        $kelasB = Kelas::create([
            'akademik_master_tahun_ajaran_id' => $taActive->id,
            'tingkatan_kelas' => '1',
            'nama_kelas' => '1B',
            'jenjang_kelas' => 'SD',
            'romawi' => 'I',
        ]);

        $siswa = Siswa::factory()->create([
            'nis' => '2026401',
            'akademik_master_kelas_id' => $kelasA->id,
        ]);

        $otherSiswaInTa = Siswa::factory()->create([
            'nis' => '2026402',
            'akademik_master_kelas_id' => $kelasB->id,
        ]);

        AnggotaKelas::create([
            'siswa_nis' => $otherSiswaInTa->nis,
            'akademik_master_kelas_id' => $kelasB->id,
            'pendaftaran' => 'Siswa Baru',
        ]);

        $anggotaKelas = AnggotaKelas::create([
            'siswa_nis' => $siswa->nis,
            'akademik_master_kelas_id' => $kelasA->id,
            'pendaftaran' => 'Siswa Baru',
        ]);

        $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->from(route('anggota-kelas.edit', $anggotaKelas->id))
            ->put(route('anggota-kelas.update', $anggotaKelas->id), [
                'siswa_nis' => $otherSiswaInTa->nis,
                'akademik_master_kelas_id' => $kelasA->id,
                'pendaftaran' => 'Pindahan',
            ])
            ->assertRedirect(route('anggota-kelas.edit', $anggotaKelas->id))
            ->assertSessionHasErrors('siswa_nis');

        $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->put(route('anggota-kelas.update', $anggotaKelas->id), [
                'siswa_nis' => $siswa->nis,
                'akademik_master_kelas_id' => $kelasB->id,
                'pendaftaran' => 'Pindahan',
            ])
            ->assertRedirect(route('anggota-kelas.index'))
            ->assertSessionHas('success', 'Data Anggota Kelas berhasil diperbarui.');

        $anggotaKelas->refresh();
        $this->assertSame($kelasB->id, $anggotaKelas->akademik_master_kelas_id);
        $this->assertSame('Pindahan', $anggotaKelas->pendaftaran);
        $this->assertSame($kelasB->id, $siswa->fresh()->akademik_master_kelas_id);

        $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->delete(route('anggota-kelas.destroy', $anggotaKelas->id))
            ->assertRedirect(route('anggota-kelas.index'))
            ->assertSessionHas('success', 'Data Anggota Kelas berhasil dihapus.');

        $this->assertDatabaseMissing('akademik_master_anggota_kelas', ['id' => $anggotaKelas->id]);
        $this->assertNull($siswa->fresh()->akademik_master_kelas_id);
    }

    public function test_create_hides_students_already_registered_in_target_or_active_tahun_ajaran(): void
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

        $kelasLama = Kelas::create([
            'akademik_master_tahun_ajaran_id' => $taLama->id,
            'tingkatan_kelas' => '1',
            'nama_kelas' => '1 Abu Bakar',
            'jenjang_kelas' => 'SD',
            'romawi' => 'I',
        ]);

        $kelasAktif = Kelas::create([
            'akademik_master_tahun_ajaran_id' => $taAktif->id,
            'tingkatan_kelas' => '2',
            'nama_kelas' => '2 Al-Fatihah',
            'jenjang_kelas' => 'SD',
            'romawi' => 'II',
        ]);

        $siswaSudahAktif = Siswa::factory()->create([
            'nis' => '2026501',
            'nama_lengkap' => 'Siswa Sudah Masuk TA Aktif',
            'status' => 'Aktif',
        ]);
        $siswaNaikKelas = Siswa::factory()->create([
            'nis' => '2026502',
            'nama_lengkap' => 'Siswa Dari TA Lama Saja',
            'status' => 'Aktif',
        ]);
        $siswaBaru = Siswa::factory()->create([
            'nis' => '2026503',
            'nama_lengkap' => 'Siswa Belum Punya Kelas',
            'status' => 'Aktif',
        ]);
        Siswa::factory()->create([
            'nis' => '2026504',
            'nama_lengkap' => 'Siswa Status Nonaktif',
            'status' => 'Nonaktif',
        ]);
        Siswa::factory()->create([
            'nis' => '2026505',
            'nama_lengkap' => 'Siswa Status Pindah',
            'status' => 'Pindah',
        ]);
        Siswa::factory()->create([
            'nis' => '2026506',
            'nama_lengkap' => 'Siswa Status Lulus',
            'status' => 'Lulus',
        ]);

        AnggotaKelas::create([
            'siswa_nis' => $siswaSudahAktif->nis,
            'akademik_master_kelas_id' => $kelasAktif->id,
            'pendaftaran' => 'Siswa Baru',
        ]);

        AnggotaKelas::create([
            'siswa_nis' => $siswaNaikKelas->nis,
            'akademik_master_kelas_id' => $kelasLama->id,
            'pendaftaran' => 'Siswa Baru',
        ]);

        $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->get(route('anggota-kelas.create'))
            ->assertOk()
            ->assertDontSee('Siswa Sudah Masuk TA Aktif')
            ->assertDontSee('Siswa Status Nonaktif')
            ->assertDontSee('Siswa Status Pindah')
            ->assertDontSee('Siswa Status Lulus')
            ->assertSee('Siswa Dari TA Lama Saja')
            ->assertSee('1 Abu Bakar')
            ->assertSee('Siswa Belum Punya Kelas')
            ->assertSee('1 sudah masuk anggota kelas (disembunyikan)')
            ->assertSee('3 non-aktif/pindah/lulus (disembunyikan)');

        $this->actingAs($user)
            ->withSession(['access_token' => 'valid-token'])
            ->get(route('anggota-kelas.create', ['kelas_id' => $kelasLama->id]))
            ->assertOk()
            ->assertSee('Siswa Sudah Masuk TA Aktif')
            ->assertDontSee('Siswa Status Nonaktif')
            ->assertDontSee('Siswa Status Pindah')
            ->assertDontSee('Siswa Dari TA Lama Saja')
            ->assertSee('Siswa Belum Punya Kelas');
    }
}
