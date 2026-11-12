<?php

namespace Tests\Feature;

use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Informasi\AgendaSekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AgendaSekolahTest extends TestCase
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

    public function test_index_loads_agendas_for_active_tahun_ajaran_without_n_plus_one_queries(): void
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
            AgendaSekolah::factory()->create([
                'akademik_master_tahun_ajaran_id' => $taAktif->id,
                'nama_agenda' => "Agenda Aktif {$index}",
                'tanggal' => sprintf('2026-08-%02d', $index),
                'jam' => '08:00',
                'unit' => 'SD',
            ]);
        }

        AgendaSekolah::factory()->create([
            'akademik_master_tahun_ajaran_id' => $taLama->id,
            'nama_agenda' => 'Agenda Tahun Lalu',
            'tanggal' => '2026-02-10',
            'jam' => '09:00',
            'unit' => 'SMP',
        ]);

        AgendaSekolah::factory()->create([
            'akademik_master_tahun_ajaran_id' => $taAktif->id,
            'nama_agenda' => 'Agenda Khusus Pegawai',
            'tanggal' => '2026-08-06',
            'jam' => '10:00',
            'unit' => 'Semua Unit',
            'jenis' => AgendaSekolah::JENIS_PEGAWAI,
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($user)->get(route('agenda-sekolah.index'));

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertOk();
        $response->assertSeeText('Agenda Aktif 1');
        $response->assertDontSeeText('Agenda Tahun Lalu');
        $response->assertDontSeeText('Agenda Khusus Pegawai');
        $this->assertLessThanOrEqual(6, count($queries), 'Expected eager-loaded query count without N+1 per agenda.');
    }

    public function test_crud_workflow_creates_updates_filters_and_deletes_agenda_sekolah(): void
    {
        $user = $this->authenticateUser();

        $tahunAjaran = TahunAjaran::create([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);

        $storeResponse = $this->actingAs($user)->post(route('agenda-sekolah.store'), [
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'nama_agenda' => 'Rapat Awal Tahun Ajaran',
            'tanggal' => '2026-07-10',
            'jam' => '08:30',
            'unit' => 'Semua Unit',
            'jenis' => 'pegawai',
        ]);

        $storeResponse->assertRedirect(route('agenda-sekolah.index'));
        $this->assertDatabaseHas('akademik_informasi_agenda_sekolah', [
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'nama_agenda' => 'Rapat Awal Tahun Ajaran',
            'tanggal' => '2026-07-10',
            'jam' => '08:30',
            'unit' => 'Semua Unit',
            'jenis' => AgendaSekolah::JENIS_SISWA,
        ]);

        $agenda = AgendaSekolah::query()->firstOrFail();

        $updateResponse = $this->actingAs($user)->put(route('agenda-sekolah.update', $agenda), [
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'nama_agenda' => 'Rapat Pleno Komite SD',
            'tanggal' => '2026-07-15',
            'jam' => '09:45:00',
            'unit' => 'SD',
            'jenis' => 'pegawai',
        ]);

        $updateResponse->assertRedirect(route('agenda-sekolah.index'));
        $this->assertDatabaseHas('akademik_informasi_agenda_sekolah', [
            'id' => $agenda->id,
            'nama_agenda' => 'Rapat Pleno Komite SD',
            'tanggal' => '2026-07-15',
            'jam' => '09:45',
            'unit' => 'SD',
            'jenis' => AgendaSekolah::JENIS_SISWA,
        ]);

        $deleteResponse = $this->actingAs($user)->delete(route('agenda-sekolah.destroy', $agenda));

        $deleteResponse->assertRedirect(route('agenda-sekolah.index'));
        $this->assertDatabaseMissing('akademik_informasi_agenda_sekolah', ['id' => $agenda->id]);
    }

    public function test_store_rejects_invalid_unit_and_duplicate_schedule_entry(): void
    {
        $user = $this->authenticateUser();

        $tahunAjaran = TahunAjaran::create([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);

        AgendaSekolah::create([
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'nama_agenda' => 'Upacara Bendera',
            'tanggal' => '2026-08-17',
            'jam' => '07:00',
            'unit' => 'Semua Unit',
        ]);

        $duplicateResponse = $this->actingAs($user)->post(route('agenda-sekolah.store'), [
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'nama_agenda' => 'Upacara Bendera',
            'tanggal' => '2026-08-17',
            'jam' => '07:00',
            'unit' => 'Semua Unit',
        ]);

        $duplicateResponse->assertSessionHasErrors('nama_agenda');

        $invalidUnitResponse = $this->actingAs($user)->post(route('agenda-sekolah.store'), [
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'nama_agenda' => 'Rapat Khusus',
            'tanggal' => '2026-08-18',
            'jam' => '10:00',
            'unit' => 'Unit Tidak Valid',
        ]);

        $invalidUnitResponse->assertSessionHasErrors('unit');
    }
}
