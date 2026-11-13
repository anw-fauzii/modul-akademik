<?php

namespace Database\Factories\Admin\Pelajaran;

use App\Models\Admin\Pelajaran\KelompokPelajaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MataPelajaran>
 */
class MataPelajaranFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sequence = fake()->unique()->numberBetween(1, 999);

        return [
            'akademik_pelajaran_kelompok_pelajaran_id' => fn () => KelompokPelajaran::query()->value('id')
                ?? KelompokPelajaran::factory()->create()->id,
            'singkatan' => sprintf('MP%03d', $sequence),
            'nama_mata_pelajaran' => "Mata Pelajaran {$sequence}",
            'sifat' => MataPelajaran::SIFAT_WAJIB,
            'unit' => fake()->randomElement(MataPelajaran::UNIT_OPTIONS),
            'urutan' => fake()->numberBetween(1, 30),
            'keterangan' => null,
            'status' => true,
        ];
    }
}
