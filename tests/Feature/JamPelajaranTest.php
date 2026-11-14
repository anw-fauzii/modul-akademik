<?php

namespace Tests\Feature;

use App\Models\Admin\Pelajaran\JamPelajaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class JamPelajaranTest extends TestCase
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

    public function test_index_loads_jam_pelajaran_without_n_plus_one_queries_and_supports_filters(): void
    {
        $user = $this->authenticateUser();

        foreach (range(1, 5) as $index) {
            JamPelajaran::factory()->create([
                'jam_ke' => $index,
                'unit' => 'SD',
                'jam_mulai' => sprintf('%02d:00', 6 + $index),
                'jam_selesai' => sprintf('%02d:35', 6 + $index),
                'keterangan' => "Sesi SD {$index}",
            ]);
        }

        JamPelajaran::factory()->create([
            'jam_ke' => 1,
            'unit' => 'PG',
            'jam_mulai' => '08:00',
            'jam_selesai' => '08:30',
            'keterangan' => 'Motorik Pagi PG',
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($user)->get(route('jam-pelajaran.index'));

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertOk();
        $response->assertSeeText('Sesi SD 1');
        $response->assertSeeText('Motorik Pagi PG');
        $this->assertLessThanOrEqual(4, count($queries), 'Expected eager/batched query count without N+1 per jam pelajaran.');

        $filteredResponse = $this->actingAs($user)->get(route('jam-pelajaran.index', [
            'unit' => 'PG',
            'search' => 'Motorik',
        ]));

        $filteredResponse->assertOk();
        $filteredResponse->assertSeeText('Motorik Pagi PG');
        $filteredResponse->assertDontSeeText('Sesi SD 1');
    }

    public function test_crud_workflow_creates_updates_and_deletes_jam_pelajaran(): void
    {
        $user = $this->authenticateUser();

        $storeResponse = $this->actingAs($user)->post(route('jam-pelajaran.store'), [
            'jam_ke' => 1,
            'unit' => 'sd',
            'jam_mulai' => '07:00',
            'jam_selesai' => '07:35',
            'keterangan' => ' Pembiasaan   Pagi ',
        ]);

        $storeResponse->assertRedirect(route('jam-pelajaran.index'));
        $this->assertDatabaseHas('akademik_pelajaran_jam_pelajaran', [
            'jam_ke' => 1,
            'unit' => 'SD',
            'keterangan' => 'Pembiasaan Pagi',
        ]);

        // Jam ke yang sama pada unit berbeda tetap diperbolehkan.
        $otherUnitResponse = $this->actingAs($user)->post(route('jam-pelajaran.store'), [
            'jam_ke' => 1,
            'unit' => 'TK',
            'jam_mulai' => '07:30',
            'jam_selesai' => '08:00',
            'keterangan' => 'Circle Time TK',
        ]);
        $otherUnitResponse->assertRedirect(route('jam-pelajaran.index'));

        $jamPelajaran = JamPelajaran::query()->where('unit', 'SD')->firstOrFail();

        $updateResponse = $this->actingAs($user)->put(route('jam-pelajaran.update', $jamPelajaran), [
            'jam_ke' => 2,
            'unit' => 'SD',
            'jam_mulai' => '07:35',
            'jam_selesai' => '08:10',
            'keterangan' => '',
        ]);

        $updateResponse->assertRedirect(route('jam-pelajaran.index'));
        $this->assertDatabaseHas('akademik_pelajaran_jam_pelajaran', [
            'id' => $jamPelajaran->id,
            'jam_ke' => 2,
            'unit' => 'SD',
            'keterangan' => null,
        ]);

        $deleteResponse = $this->actingAs($user)->delete(route('jam-pelajaran.destroy', $jamPelajaran));
        $deleteResponse->assertRedirect(route('jam-pelajaran.index'));
        $this->assertDatabaseMissing('akademik_pelajaran_jam_pelajaran', ['id' => $jamPelajaran->id]);
    }

    public function test_store_rejects_duplicate_unit_jam_ke_and_invalid_time_ranges(): void
    {
        $user = $this->authenticateUser();

        JamPelajaran::factory()->create([
            'jam_ke' => 1,
            'unit' => 'SD',
            'jam_mulai' => '07:00',
            'jam_selesai' => '07:35',
        ]);

        $response = $this->actingAs($user)->post(route('jam-pelajaran.store'), [
            'jam_ke' => 1,
            'unit' => 'SD',
            'jam_mulai' => '08:00',
            'jam_selesai' => '07:45',
        ]);

        $response->assertSessionHasErrors([
            'jam_ke',
            'jam_selesai',
        ]);
    }
}
