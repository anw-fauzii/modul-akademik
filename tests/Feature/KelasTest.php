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
}

