<?php

namespace Database\Factories\Admin\DataMaster;

use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\DataMaster\Titimangsa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Titimangsa>
 */
class TitimangsaFactory extends Factory
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
            'jenjang_kelas' => fake()->randomElement(Titimangsa::JENJANG_OPTIONS),
            'tingkatan_kelas' => Titimangsa::TINGKATAN_SEMUA,
            'periode' => fake()->randomElement(array_keys(Titimangsa::PERIODE_OPTIONS)),
            'tempat' => fake()->city(),
            'tanggal_titimangsa' => fake()->date('Y-m-d'),
            'kepala_sekolah_nipy' => null,
            'nama_kepala_sekolah_snapshot' => fake()->name(),
            'jabatan_kepala_sekolah' => Titimangsa::DEFAULT_JABATAN,
            'status' => true,
        ];
    }
}
