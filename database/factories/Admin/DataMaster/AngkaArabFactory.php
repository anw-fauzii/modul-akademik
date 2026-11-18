<?php

namespace Database\Factories\Admin\DataMaster;

use App\Models\Admin\DataMaster\AngkaArab;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AngkaArab>
 */
class AngkaArabFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $latinDigits = ['1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $arabDigits = ['١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $index = fake()->numberBetween(0, count($latinDigits) - 1);

        return [
            'angka_latin' => $latinDigits[$index],
            'angka_arab' => $arabDigits[$index],
        ];
    }
}

