<?php

namespace Database\Factories\Admin\DataMaster;

use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Models\Admin\DataMaster\TahunAjaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ekstrakurikuler>
 */
class EkstrakurikulerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'akademik_master_tahun_ajaran_id' => fn () => TahunAjaran::query()->value('id')
                ?? TahunAjaran::create([
                    'nama_tahun_ajaran' => '2026/2027',
                    'semester' => '1',
                    'is_active' => true,
                ])->id,
            'karyawan_nipy' => null,
            'nama_ekstrakurikuler' => fake()->unique()->words(2, true),
            'biaya' => fake()->randomElement([0, 50000, 100000, 150000]),
            'unit' => fake()->randomElement(Ekstrakurikuler::UNIT_OPTIONS),
        ];
    }
}
