<?php

namespace Database\Factories\Admin\DataMaster;

use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\AnggotaT2q;
use App\Models\Admin\DataMaster\Karyawan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnggotaT2q>
 */
class AnggotaT2qFactory extends Factory
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
            'karyawan_nipy' => fn () => Karyawan::query()->value('nipy')
                ?? Karyawan::factory()->create()->nipy,
            'tingkat' => fake()->randomElement(AnggotaT2q::DEFAULT_TINGKAT_OPTIONS),
            'pendaftaran' => fake()->randomElement(AnggotaT2q::PENDAFTARAN_OPTIONS),
            'keterangan' => null,
        ];
    }
}
