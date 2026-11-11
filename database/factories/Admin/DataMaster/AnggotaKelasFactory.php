<?php

namespace Database\Factories\Admin\DataMaster;

use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnggotaKelas>
 */
class AnggotaKelasFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'siswa_nis' => fn () => Siswa::query()->value('nis')
                ?? Siswa::factory()->create()->nis,
            'akademik_master_kelas_id' => fn () => Kelas::query()->value('id')
                ?? Kelas::create([
                    'akademik_master_tahun_ajaran_id' => TahunAjaran::query()->value('id')
                        ?? TahunAjaran::create([
                            'nama_tahun_ajaran' => '2026/2027',
                            'semester' => '1',
                            'is_active' => true,
                        ])->id,
                    'tingkatan_kelas' => '1',
                    'nama_kelas' => '1 Al-Fatihah',
                    'jenjang_kelas' => 'SD',
                    'romawi' => 'I',
                ])->id,
            'pendaftaran' => fake()->randomElement(AnggotaKelas::PENDAFTARAN_OPTIONS),
        ];
    }
}
