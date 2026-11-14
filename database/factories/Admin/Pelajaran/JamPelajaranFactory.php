<?php

namespace Database\Factories\Admin\Pelajaran;

use App\Models\Admin\Pelajaran\JamPelajaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JamPelajaran>
 */
class JamPelajaranFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sequence = fake()->unique()->numberBetween(1, 300);
        $unitCount = count(JamPelajaran::UNIT_OPTIONS);
        $unit = JamPelajaran::UNIT_OPTIONS[($sequence - 1) % $unitCount];
        $jamKe = (int) floor(($sequence - 1) / $unitCount) + 1;

        $slotIndex = ($jamKe - 1) % 12;
        $startMinutes = (7 * 60) + ($slotIndex * 35);
        $endMinutes = $startMinutes + 35;

        return [
            'jam_ke' => $jamKe,
            'unit' => $unit,
            'jam_mulai' => sprintf('%02d:%02d', intdiv($startMinutes, 60), $startMinutes % 60),
            'jam_selesai' => sprintf('%02d:%02d', intdiv($endMinutes, 60), $endMinutes % 60),
            'keterangan' => null,
        ];
    }
}
