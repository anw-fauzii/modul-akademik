<?php

namespace Tests\Feature;

use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Pelajaran\JadwalPelajaran;
use App\Models\Admin\Pelajaran\JamPelajaran;
use App\Models\Admin\Pelajaran\KelompokPelajaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class JadwalPelajaranTest extends TestCase
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

    public function test_index_loads_jadwal_pelajaran_without_n_plus_one_and_renders_weekly_matrix(): void
    {
        $user = $this->authenticateUser();

        $ta = TahunAjaran::create([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);

        $kelas = Kelas::create([
            'akademik_master_tahun_ajaran_id' => $ta->id,
            'tingkatan_kelas' => '1',
            'nama_kelas' => '1A',
            'jenjang_kelas' => 'SD',
            'romawi' => 'I',
        ]);

        $kelompok = KelompokPelajaran::factory()->create();
        $mapel = MataPelajaran::factory()->create([
            'akademik_pelajaran_kelompok_pelajaran_id' => $kelompok->id,
            'singkatan' => 'MTK',
            'nama_mata_pelajaran' => 'Matematika',
            'unit' => 'SD',
        ]);

        $guru = Karyawan::create([
            'nipy' => '19900101-1',
            'nama_lengkap' => 'Siti Aminah',
            'gelar' => 'S.Pd',
            'unit' => 'SD',
        ]);

        foreach (range(1, 5) as $hari) {
            $jam = JamPelajaran::factory()->create([
                'jam_ke' => $hari,
                'unit' => 'SD',
                'jam_mulai' => sprintf('%02d:00', 6 + $hari),
                'jam_selesai' => sprintf('%02d:35', 6 + $hari),
            ]);

            JadwalPelajaran::create([
                'akademik_master_tahun_ajaran_id' => $ta->id,
                'akademik_master_kelas_id' => $kelas->id,
                'akademik_pelajaran_jam_pelajaran_id' => $jam->id,
                'akademik_pelajaran_mata_pelajaran_id' => $mapel->id,
                'karyawan_nipy' => $guru->nipy,
                'hari' => $hari,
                'status' => true,
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($user)->get(route('jadwal-pelajaran.index', [
            'tahun_ajaran_id' => $ta->id,
            'kelas_id' => $kelas->id,
        ]));

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertOk();
        $response->assertSeeText('Matematika');
        $response->assertSeeText('Matriks Jadwal Mingguan');
        $this->assertLessThanOrEqual(12, count($queries));
    }

    public function test_crud_supports_mapel_and_nullable_non_mapel_slots(): void
    {
        $user = $this->authenticateUser();

        $ta = TahunAjaran::create([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);

        $kelas = Kelas::create([
            'akademik_master_tahun_ajaran_id' => $ta->id,
            'tingkatan_kelas' => '1',
            'nama_kelas' => '1A',
            'jenjang_kelas' => 'SD',
            'romawi' => 'I',
        ]);

        $jamIstirahat = JamPelajaran::factory()->create([
            'jam_ke' => 1,
            'unit' => 'SD',
            'jam_mulai' => '09:00',
            'jam_selesai' => '09:30',
        ]);

        // Slot Non-Mapel (Istirahat/Upacara/Makan/Pulang) dengan mapel & guru nullable.
        $nonMapelRes = $this->actingAs($user)->post(route('jadwal-pelajaran.store'), [
            'akademik_master_tahun_ajaran_id' => $ta->id,
            'akademik_master_kelas_id' => $kelas->id,
            'akademik_pelajaran_jam_pelajaran_id' => $jamIstirahat->id,
            'akademik_pelajaran_mata_pelajaran_id' => '',
            'karyawan_nipy' => '',
            'hari' => JadwalPelajaran::HARI_SENIN,
            'keterangan' => 'Istirahat & Makan',
            'status' => '1',
        ]);

        $nonMapelRes->assertRedirect();
        $this->assertDatabaseHas('akademik_pelajaran_jadwal_pelajaran', [
            'akademik_master_kelas_id' => $kelas->id,
            'akademik_pelajaran_jam_pelajaran_id' => $jamIstirahat->id,
            'akademik_pelajaran_mata_pelajaran_id' => null,
            'karyawan_nipy' => null,
            'hari' => 1,
            'keterangan' => 'Istirahat & Makan',
        ]);

        $jadwal = JadwalPelajaran::query()->firstOrFail();

        $toggleRes = $this->actingAs($user)->post(route('jadwal-pelajaran.toggle-status', $jadwal));
        $toggleRes->assertRedirect(route('jadwal-pelajaran.index'));
        $this->assertFalse((bool) $jadwal->fresh()->status);

        $deleteRes = $this->actingAs($user)->delete(route('jadwal-pelajaran.destroy', $jadwal));
        $deleteRes->assertRedirect(route('jadwal-pelajaran.index'));
        $this->assertDatabaseMissing('akademik_pelajaran_jadwal_pelajaran', ['id' => $jadwal->id]);
    }

    public function test_store_rejects_teacher_time_conflict_and_unit_mismatch(): void
    {
        $user = $this->authenticateUser();

        $ta = TahunAjaran::create([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);

        $kelasSd1 = Kelas::create([
            'akademik_master_tahun_ajaran_id' => $ta->id,
            'tingkatan_kelas' => '1',
            'nama_kelas' => '1A',
            'jenjang_kelas' => 'SD',
            'romawi' => 'I',
        ]);
        $kelasPg1 = Kelas::create([
            'akademik_master_tahun_ajaran_id' => $ta->id,
            'tingkatan_kelas' => 'PG',
            'nama_kelas' => 'PG Apel',
            'jenjang_kelas' => 'PG',
            'romawi' => 'PG',
        ]);

        $jamSd = JamPelajaran::factory()->create([
            'jam_ke' => 1,
            'unit' => 'SD',
            'jam_mulai' => '07:00',
            'jam_selesai' => '07:35',
        ]);
        $jamPgOverlap = JamPelajaran::factory()->create([
            'jam_ke' => 1,
            'unit' => 'PG',
            'jam_mulai' => '07:30',
            'jam_selesai' => '08:00',
        ]);

        $kelompok = KelompokPelajaran::factory()->create();
        $mapelSd = MataPelajaran::factory()->create([
            'akademik_pelajaran_kelompok_pelajaran_id' => $kelompok->id,
            'unit' => 'SD',
        ]);
        $mapelPg = MataPelajaran::factory()->create([
            'akademik_pelajaran_kelompok_pelajaran_id' => $kelompok->id,
            'unit' => 'PG',
        ]);

        $guru = Karyawan::create([
            'nipy' => '19900101-1',
            'nama_lengkap' => 'Ahmad Fauzi',
            'gelar' => 'S.Pd',
        ]);

        JadwalPelajaran::create([
            'akademik_master_tahun_ajaran_id' => $ta->id,
            'akademik_master_kelas_id' => $kelasSd1->id,
            'akademik_pelajaran_jam_pelajaran_id' => $jamSd->id,
            'akademik_pelajaran_mata_pelajaran_id' => $mapelSd->id,
            'karyawan_nipy' => $guru->nipy,
            'hari' => JadwalPelajaran::HARI_SENIN,
            'status' => true,
        ]);

        // Guru mengajar di PG pada jam 07:30-08:00 bentrok dengan SD 07:00-07:35
        $conflictRes = $this->actingAs($user)->post(route('jadwal-pelajaran.store'), [
            'akademik_master_tahun_ajaran_id' => $ta->id,
            'akademik_master_kelas_id' => $kelasPg1->id,
            'akademik_pelajaran_jam_pelajaran_id' => $jamPgOverlap->id,
            'akademik_pelajaran_mata_pelajaran_id' => $mapelPg->id,
            'karyawan_nipy' => $guru->nipy,
            'hari' => JadwalPelajaran::HARI_SENIN,
            'status' => '1',
        ]);
        $conflictRes->assertSessionHasErrors(['karyawan_nipy']);

        // Unit jam SD dipasang ke kelas PG harus ditolak
        $mismatchRes = $this->actingAs($user)->post(route('jadwal-pelajaran.store'), [
            'akademik_master_tahun_ajaran_id' => $ta->id,
            'akademik_master_kelas_id' => $kelasPg1->id,
            'akademik_pelajaran_jam_pelajaran_id' => $jamSd->id,
            'akademik_pelajaran_mata_pelajaran_id' => $mapelSd->id,
            'karyawan_nipy' => null,
            'hari' => JadwalPelajaran::HARI_SELASA,
            'status' => '1',
        ]);
        $mismatchRes->assertSessionHasErrors([
            'akademik_pelajaran_jam_pelajaran_id',
            'akademik_pelajaran_mata_pelajaran_id',
        ]);
    }
}
