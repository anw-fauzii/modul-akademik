<?php

namespace Database\Factories\Admin\DataMaster;

use App\Models\Admin\DataMaster\AnggotaJemputan;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Jemputan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnggotaJemputan>
 */
class AnggotaJemputanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'anggota_kelas_id' => fn () => AnggotaKelas::query()->value('id')
                ?? AnggotaKelas::factory()->create()->id,
            'akademik_master_jemputan_id' => fn () => Jemputan::query()->value('id')
                ?? Jemputan::factory()->create()->id,
            'pendaftaran' => fake()->randomElement(AnggotaJemputan::PENDAFTARAN_OPTIONS),
            'keterangan' => null,
        ];
    }
}

