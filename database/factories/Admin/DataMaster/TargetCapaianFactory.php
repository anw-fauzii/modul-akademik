<?php

namespace Database\Factories\Admin\DataMaster;

use App\Models\Admin\DataMaster\AnggotaT2q;
use App\Models\Admin\DataMaster\DaftarJilid;
use App\Models\Admin\DataMaster\Surah;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\DataMaster\TargetCapaian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TargetCapaian>
 */
class TargetCapaianFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'akademik_master_tahun_ajaran_id' => TahunAjaran::factory(),
            'tingkat' => fake()->randomElement(AnggotaT2q::DEFAULT_TINGKAT_OPTIONS),
            'akademik_master_t2q_daftar_jilid_id' => DaftarJilid::factory(),
            'akademik_master_t2q_surah_id' => Surah::factory(),
        ];
    }
}

