<?php

namespace Database\Factories\Admin\DataMaster;

use App\Models\Admin\DataMaster\AnggotaEkstrakurikuler;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Models\Admin\DataMaster\TahunAjaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnggotaEkstrakurikuler>
 */
class AnggotaEkstrakurikulerFactory extends Factory
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
            'akademik_master_ekstrakuler_id' => fn () => Ekstrakurikuler::query()->value('id')
                ?? Ekstrakurikuler::create([
                    'akademik_master_tahun_ajaran_id' => TahunAjaran::query()->value('id')
                        ?? TahunAjaran::create([
                            'nama_tahun_ajaran' => '2026/2027',
                            'semester' => '1',
                            'is_active' => true,
                        ])->id,
                    'nama_ekstrakurikuler' => 'Pramuka',
                    'biaya' => 0,
                    'unit' => 'SD',
                ])->id,
            'pendaftaran' => fake()->randomElement(AnggotaEkstrakurikuler::PENDAFTARAN_OPTIONS),
            'keterangan' => null,
        ];
    }
}
