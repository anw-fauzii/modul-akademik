<?php

namespace Database\Factories\Admin\DataMaster;

use App\Models\Admin\DataMaster\Jemputan;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\TahunAjaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Jemputan>
 */
class JemputanFactory extends Factory
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
            'karyawan_nipy' => fn () => Karyawan::query()->value('nipy')
                ?? Karyawan::create([
                    'nipy' => 'DRV-'.fake()->unique()->numerify('####'),
                    'nama_lengkap' => fake()->name(),
                    'jabatan' => 'Driver Jemputan',
                ])->nipy,
            'harga_pp' => fake()->randomElement([150000, 200000, 250000, 300000]),
            'harga_setengah' => fake()->randomElement([80000, 100000, 125000, 150000]),
        ];
    }
}
