<?php

namespace Tests\Feature;

use App\Models\Admin\Pelajaran\KelompokPelajaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MataPelajaranTest extends TestCase
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

    public function test_index_loads_mata_pelajaran_without_n_plus_one_queries_and_supports_filters(): void
    {
        $user = $this->authenticateUser();

        $kelompok = KelompokPelajaran::factory()->create([
            'kode' => 'A',
            'kelompok' => 'Kelompok A (Wajib)',
            'urutan' => 1,
        ]);

        foreach (range(1, 5) as $index) {
            MataPelajaran::factory()->create([
                'akademik_pelajaran_kelompok_pelajaran_id' => $kelompok->id,
                'singkatan' => "SD{$index}",
                'nama_mata_pelajaran' => "Pelajaran SD {$index}",
                'unit' => 'SD',
                'sifat' => MataPelajaran::SIFAT_WAJIB,
                'urutan' => $index,
                'status' => true,
            ]);
        }

        MataPelajaran::factory()->create([
            'akademik_pelajaran_kelompok_pelajaran_id' => $kelompok->id,
            'singkatan' => 'RBT',
            'nama_mata_pelajaran' => 'Motorik PG',
            'unit' => 'PG',
            'sifat' => MataPelajaran::SIFAT_TAMBAHAN,
            'urutan' => 6,
            'status' => false,
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($user)->get(route('mata-pelajaran.index'));

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertOk();
        $response->assertSeeText('Pelajaran SD 1');
        $response->assertSeeText('Motorik PG');
        $this->assertLessThanOrEqual(6, count($queries), 'Expected eager-loaded query count without N+1 per mata pelajaran.');

        $filteredResponse = $this->actingAs($user)->get(route('mata-pelajaran.index', [
            'unit' => 'PG',
            'sifat' => 'tambahan',
            'status' => '0',
        ]));

        $filteredResponse->assertOk();
        $filteredResponse->assertSeeText('Motorik PG');
        $filteredResponse->assertDontSeeText('Pelajaran SD 1');
    }

    public function test_crud_workflow_creates_updates_toggles_status_and_deletes_mata_pelajaran(): void
    {
        $user = $this->authenticateUser();

        $kelompok = KelompokPelajaran::factory()->create([
            'kode' => 'A',
            'kelompok' => 'Kelompok A (Umum)',
            'urutan' => 1,
        ]);

        $storeResponse = $this->actingAs($user)->post(route('mata-pelajaran.store'), [
            'akademik_pelajaran_kelompok_pelajaran_id' => $kelompok->id,
            'singkatan' => ' mtk ',
            'nama_mata_pelajaran' => ' Matematika   Dasar ',
            'sifat' => 'wajib',
            'unit' => 'SD',
            'urutan' => 1,
            'keterangan' => 'Mapel wajib kelas 1-6',
            'status' => '1',
        ]);

        $storeResponse->assertRedirect(route('mata-pelajaran.index'));
        $this->assertDatabaseHas('akademik_pelajaran_mata_pelajaran', [
            'akademik_pelajaran_kelompok_pelajaran_id' => $kelompok->id,
            'singkatan' => 'MTK',
            'nama_mata_pelajaran' => 'Matematika Dasar',
            'sifat' => MataPelajaran::SIFAT_WAJIB,
            'unit' => 'SD',
            'urutan' => 1,
            'status' => true,
        ]);

        // Singkatan & nama yang sama pada unit berbeda tetap diperbolehkan.
        $otherUnitResponse = $this->actingAs($user)->post(route('mata-pelajaran.store'), [
            'akademik_pelajaran_kelompok_pelajaran_id' => $kelompok->id,
            'singkatan' => 'mtk',
            'nama_mata_pelajaran' => 'Matematika Dasar',
            'sifat' => 'wajib',
            'unit' => 'PG/TK',
            'urutan' => 1,
            'status' => '1',
        ]);
        $otherUnitResponse->assertRedirect(route('mata-pelajaran.index'));

        $mapel = MataPelajaran::query()->where('unit', 'SD')->firstOrFail();

        $toggleResponse = $this->actingAs($user)->post(route('mata-pelajaran.toggle-status', $mapel));
        $toggleResponse->assertRedirect(route('mata-pelajaran.index'));
        $this->assertFalse((bool) $mapel->fresh()->status);

        $updateResponse = $this->actingAs($user)->put(route('mata-pelajaran.update', $mapel), [
            'akademik_pelajaran_kelompok_pelajaran_id' => $kelompok->id,
            'singkatan' => 'mtk-sd',
            'nama_mata_pelajaran' => 'Matematika Terapan',
            'sifat' => 'tambahan',
            'unit' => 'SD',
            'urutan' => 2,
            'keterangan' => '',
            'status' => '0',
        ]);

        $updateResponse->assertRedirect(route('mata-pelajaran.index'));
        $this->assertDatabaseHas('akademik_pelajaran_mata_pelajaran', [
            'id' => $mapel->id,
            'singkatan' => 'MTK-SD',
            'nama_mata_pelajaran' => 'Matematika Terapan',
            'sifat' => MataPelajaran::SIFAT_TAMBAHAN,
            'urutan' => 2,
            'keterangan' => null,
            'status' => false,
        ]);

        $deleteResponse = $this->actingAs($user)->delete(route('mata-pelajaran.destroy', $mapel));
        $deleteResponse->assertRedirect(route('mata-pelajaran.index'));
        $this->assertDatabaseMissing('akademik_pelajaran_mata_pelajaran', ['id' => $mapel->id]);
    }

    public function test_store_rejects_duplicate_singkatan_or_nama_in_same_unit_and_invalid_inputs(): void
    {
        $user = $this->authenticateUser();

        $kelompok = KelompokPelajaran::factory()->create();

        MataPelajaran::factory()->create([
            'akademik_pelajaran_kelompok_pelajaran_id' => $kelompok->id,
            'singkatan' => 'MTK',
            'nama_mata_pelajaran' => 'Matematika',
            'unit' => 'SD',
        ]);

        $response = $this->actingAs($user)->post(route('mata-pelajaran.store'), [
            'akademik_pelajaran_kelompok_pelajaran_id' => 999999,
            'singkatan' => 'mtk',
            'nama_mata_pelajaran' => 'Matematika',
            'sifat' => 'pilihan',
            'unit' => 'SD',
            'urutan' => 0,
            'status' => 'invalid',
        ]);

        $response->assertSessionHasErrors([
            'akademik_pelajaran_kelompok_pelajaran_id',
            'singkatan',
            'nama_mata_pelajaran',
            'sifat',
            'urutan',
            'status',
        ]);
    }
}
