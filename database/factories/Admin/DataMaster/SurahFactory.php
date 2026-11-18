<?php

namespace Database\Factories\Admin\DataMaster;

use App\Models\Admin\DataMaster\Surah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Surah>
 */
class SurahFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $samples = [
            ['nama_surah' => 'Al-Fatihah', 'nama_surah_arab' => 'الفاتحة', 'jumlah_ayat' => 7],
            ['nama_surah' => 'An-Naba', 'nama_surah_arab' => 'النبإ', 'jumlah_ayat' => 40],
            ['nama_surah' => 'Al-Ikhlas', 'nama_surah_arab' => 'الإخلاص', 'jumlah_ayat' => 4],
        ];

        return fake()->randomElement($samples);
    }
}

