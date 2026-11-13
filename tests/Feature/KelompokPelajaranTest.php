<?php

namespace Tests\Feature;

use App\Models\Admin\Pelajaran\KelompokPelajaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KelompokPelajaranTest extends TestCase
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

    public function test_index_lists_kelompok_pelajaran_in_urutan_order_and_supports_search(): void
    {
        $user = $this->authenticateUser();

        KelompokPelajaran::factory()->create([
            'kode' => 'B',
            'kelompok' => 'Kelompok B (Keterampilan)',
            'urutan' => 2,
        ]);

        KelompokPelajaran::factory()->create([
            'kode' => 'A',
            'kelompok' => 'Kelompok A (Umum)',
            'urutan' => 1,
        ]);

        $response = $this->actingAs($user)->get(route('kelompok-pelajaran.index'));

        $response->assertOk();
        $response->assertSeeInOrder([
            'Kelompok A (Umum)',
            'Kelompok B (Keterampilan)',
        ]);

        $filteredResponse = $this->actingAs($user)->get(route('kelompok-pelajaran.index', ['search' => 'Keterampilan']));
        $filteredResponse->assertOk();
        $filteredResponse->assertSeeText('Kelompok B (Keterampilan)');
        $filteredResponse->assertDontSeeText('Kelompok A (Umum)');
    }

    public function test_crud_workflow_creates_updates_and_deletes_kelompok_pelajaran(): void
    {
        $user = $this->authenticateUser();

        $storeResponse = $this->actingAs($user)->post(route('kelompok-pelajaran.store'), [
            'kode' => ' mulok ',
            'kelompok' => ' Muatan   Lokal ',
            'urutan' => 3,
        ]);

        $storeResponse->assertRedirect(route('kelompok-pelajaran.index'));
        $this->assertDatabaseHas('akademik_pelajaran_kelompok_pelajaran', [
            'kode' => 'MULOK',
            'kelompok' => 'Muatan Lokal',
            'urutan' => 3,
        ]);

        $kelompok = KelompokPelajaran::query()->firstOrFail();

        $updateResponse = $this->actingAs($user)->put(route('kelompok-pelajaran.update', $kelompok), [
            'kode' => 'mulok',
            'kelompok' => 'Muatan Lokal Daerah',
            'urutan' => 4,
        ]);

        $updateResponse->assertRedirect(route('kelompok-pelajaran.index'));
        $this->assertDatabaseHas('akademik_pelajaran_kelompok_pelajaran', [
            'id' => $kelompok->id,
            'kode' => 'MULOK',
            'kelompok' => 'Muatan Lokal Daerah',
            'urutan' => 4,
        ]);

        $deleteResponse = $this->actingAs($user)->delete(route('kelompok-pelajaran.destroy', $kelompok));

        $deleteResponse->assertRedirect(route('kelompok-pelajaran.index'));
        $this->assertDatabaseMissing('akademik_pelajaran_kelompok_pelajaran', ['id' => $kelompok->id]);
    }

    public function test_store_rejects_duplicate_kode_kelompok_and_invalid_urutan(): void
    {
        $user = $this->authenticateUser();

        KelompokPelajaran::factory()->create([
            'kode' => 'A',
            'kelompok' => 'Kelompok A (Wajib)',
            'urutan' => 1,
        ]);

        $response = $this->actingAs($user)->post(route('kelompok-pelajaran.store'), [
            'kode' => 'a',
            'kelompok' => 'Kelompok A (Wajib)',
            'urutan' => 0,
        ]);

        $response->assertSessionHasErrors([
            'kode',
            'kelompok',
            'urutan',
        ]);
    }
}
