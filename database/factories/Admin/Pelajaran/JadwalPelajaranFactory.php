<?php

namespace Database\Factories\Admin\Pelajaran;

use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Pelajaran\JadwalPelajaran;
use App\Models\Admin\Pelajaran\JamPelajaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JadwalPelajaran>
 */
class JadwalPelajaranFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sequence = fake()->unique()->numberBetween(1, 500);

        return [
            'akademik_master_tahun_ajaran_id' => fn () => TahunAjaran::query()->value('id')
                ?? TahunAjaran::create([
                    'nama_tahun_ajaran' => '2026/2027',
                    'semester' => '1',
                    'is_active' => true,
                ])->id,
            'akademik_master_kelas_id' => function (array $attributes): int {
                $existingId = Kelas::query()->value('id');
                if ($existingId !== null) {
                    return (int) $existingId;
                }

                return (int) Kelas::create([
                    'akademik_master_tahun_ajaran_id' => $attributes['akademik_master_tahun_ajaran_id'],
                    'tingkatan_kelas' => '1',
                    'nama_kelas' => '1A',
                    'jenjang_kelas' => 'SD',
                    'romawi' => 'I',
                ])->id;
            },
            'akademik_pelajaran_jam_pelajaran_id' => fn () => JamPelajaran::query()->value('id')
                ?? JamPelajaran::factory()->create(['unit' => 'SD', 'jam_ke' => ($sequence % 12) + 1])->id,
            'akademik_pelajaran_mata_pelajaran_id' => fn () => MataPelajaran::query()->value('id')
                ?? MataPelajaran::factory()->create(['unit' => 'SD'])->id,
            'karyawan_nipy' => fn () => Karyawan::query()->value('nipy'),
            'hari' => (($sequence - 1) % 5) + 1,
            'ruang' => null,
            'keterangan' => null,
            'status' => true,
        ];
    }
}
