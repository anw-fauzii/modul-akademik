<?php

namespace Database\Factories\Admin\T2q;

use App\Models\Admin\DataMaster\AnggotaT2q;
use App\Models\Admin\DataMaster\AngkaArab;
use App\Models\Admin\DataMaster\Surah;
use App\Models\Admin\T2q\YaumiyahTahfizh;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<YaumiyahTahfizh>
 */
class YaumiyahTahfizhFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'anggota_t2q_id' => AnggotaT2q::factory(),
            'akademik_master_t2q_surah_id' => Surah::factory(),
            'akademik_master_t2q_angka_arab_id' => AngkaArab::factory(),
            'tanggal' => now()->toDateString(),
            'nilai' => fake()->randomFloat(2, 75, 100),
        ];
    }
}

