<?php

namespace Database\Factories\Admin\DataMaster;

use App\Models\Admin\DataMaster\DaftarJilid;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DaftarJilid>
 */
class DaftarJilidFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $number = fake()->numberBetween(1, 6);

        return [
            'jilid_latin' => "Jilid {$number}",
            'jilid_arab' => "المجلد {$number}",
            'jenis' => 'halaman',
            'urutan' => $number,
        ];
    }
}
