<?php

namespace Database\Factories\Admin\Presensi;

use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\Presensi\PresensiSiswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PresensiSiswa>
 */
class PresensiSiswaFactory extends Factory
{
    protected $model = PresensiSiswa::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(array_keys(PresensiSiswa::STATUS_OPTIONS));
        $jamMasuk = match ($status) {
            'hadir' => '07:05',
            'terlambat' => '07:25',
            default => null,
        };
        $keterlambatan = $status === 'terlambat' ? $this->faker->numberBetween(5, 45) : 0;

        return [
            'anggota_kelas_id' => AnggotaKelas::factory(),
            'tanggal' => $this->faker->date('Y-m-d'),
            'jam_masuk' => $jamMasuk,
            'status' => $status,
            'keterlambatan_menit' => $keterlambatan,
        ];
    }
}
