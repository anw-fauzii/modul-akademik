<?php

namespace Tests\Feature;

use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Informasi\Pengumuman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PengumumanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['auth.host' => 'https://sso.test']);
        Http::preventStrayRequests();
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

    public function test_index_loads_pengumuman_for_active_tahun_ajaran_without_n_plus_one_queries(): void
    {
        $user = $this->authenticateUser();

        $taAktif = TahunAjaran::create([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);

        $taLama = TahunAjaran::create([
            'nama_tahun_ajaran' => '2025/2026',
            'semester' => '2',
            'is_active' => false,
        ]);

        foreach (range(1, 5) as $index) {
            Pengumuman::factory()->create([
                'akademik_master_tahun_ajaran_id' => $taAktif->id,
                'judul' => "Pengumuman Siswa Aktif {$index}",
                'isi' => "Isi pengumuman resmi nomor {$index}.",
            ]);
        }

        Pengumuman::factory()->create([
            'akademik_master_tahun_ajaran_id' => $taLama->id,
            'judul' => 'Pengumuman Tahun Lalu',
            'isi' => 'Arsip pengumuman tahun ajaran lama.',
        ]);

        Pengumuman::factory()->create([
            'akademik_master_tahun_ajaran_id' => $taAktif->id,
            'judul' => 'Pengumuman Khusus Pegawai',
            'isi' => 'Informasi internal pegawai.',
            'jenis' => Pengumuman::JENIS_PEGAWAI,
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($user)->get(route('pengumuman.index'));

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertOk();
        $response->assertSeeText('Pengumuman Siswa Aktif 1');
        $response->assertDontSeeText('Pengumuman Tahun Lalu');
        $response->assertDontSeeText('Pengumuman Khusus Pegawai');
        $this->assertLessThanOrEqual(6, count($queries), 'Expected eager-loaded query count without N+1 per pengumuman.');
    }

    public function test_crud_workflow_creates_updates_searches_and_deletes_pengumuman(): void
    {
        $user = $this->authenticateUser();

        $tahunAjaran = TahunAjaran::create([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);

        $storeResponse = $this->actingAs($user)->post(route('pengumuman.store'), [
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'judul' => 'Pemberitahuan Ujian Tengah Semester',
            'isi' => '<p>Ujian Tengah Semester dilaksanakan <strong>minggu depan</strong>.</p><script>alert(1)</script>',
            'jenis' => 'pegawai',
        ]);

        $storeResponse->assertRedirect(route('pengumuman.index'));
        $this->assertDatabaseHas('akademik_informasi_pengumuman', [
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'judul' => 'Pemberitahuan Ujian Tengah Semester',
            'isi' => '<p>Ujian Tengah Semester dilaksanakan <strong>minggu depan</strong>.</p>',
            'jenis' => Pengumuman::JENIS_SISWA,
        ]);

        $pengumuman = Pengumuman::query()->firstOrFail();

        $searchResponse = $this->actingAs($user)->get(route('pengumuman.index', ['search' => 'Ujian Tengah']));
        $searchResponse->assertOk();
        $searchResponse->assertSeeText('Pemberitahuan Ujian Tengah Semester');

        $updateResponse = $this->actingAs($user)->put(route('pengumuman.update', $pengumuman), [
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'judul' => 'Revisi Jadwal Ujian Tengah Semester',
            'isi' => 'Pelaksanaan UTS diundur satu pekan.',
            'jenis' => 'pegawai',
        ]);

        $updateResponse->assertRedirect(route('pengumuman.index'));
        $this->assertDatabaseHas('akademik_informasi_pengumuman', [
            'id' => $pengumuman->id,
            'judul' => 'Revisi Jadwal Ujian Tengah Semester',
            'jenis' => Pengumuman::JENIS_SISWA,
        ]);

        $deleteResponse = $this->actingAs($user)->delete(route('pengumuman.destroy', $pengumuman));

        $deleteResponse->assertRedirect(route('pengumuman.index'));
        $this->assertDatabaseMissing('akademik_informasi_pengumuman', ['id' => $pengumuman->id]);
    }

    public function test_store_validates_required_fields_and_invalid_tahun_ajaran(): void
    {
        $user = $this->authenticateUser();

        $response = $this->actingAs($user)->post(route('pengumuman.store'), [
            'akademik_master_tahun_ajaran_id' => 999999,
            'judul' => '',
            'isi' => '',
        ]);

        $response->assertSessionHasErrors([
            'akademik_master_tahun_ajaran_id',
            'judul',
            'isi',
        ]);
    }
}
