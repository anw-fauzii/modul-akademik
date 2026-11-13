<?php

namespace Database\Factories\Admin\Pelajaran;

use App\Models\Admin\Pelajaran\KelompokPelajaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KelompokPelajaran>
 */
class KelompokPelajaranFactory extends Factory
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
            'kode' => sprintf('KLP-%03d', $sequence),
            'kelompok' => "Kelompok Pelajaran {$sequence}",
            'urutan' => fake()->numberBetween(1, 20),
        ];
    }
}
