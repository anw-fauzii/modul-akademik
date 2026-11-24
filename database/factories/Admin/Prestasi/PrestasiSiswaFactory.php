<?php

namespace Database\Factories\Admin\Prestasi;

use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Prestasi\PrestasiSiswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrestasiSiswa>
 */
class PrestasiSiswaFactory extends Factory
{
    protected $model = PrestasiSiswa::class;

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
            'nama_prestasi' => fake()->randomElement([
                'Olimpiade Sains Nasional Matematika SD',
                'Lomba Tahfidz Al-Qur\'an Juz 30',
                'Kejuaraan Panahan Pelajar Tingkat Kota',
                'Turnamen Futsal Antar SD Se-Priangan Timur',
                'Lomba Pidato Bahasa Inggris',
            ]),
            'kategori' => fake()->randomElement(array_keys(PrestasiSiswa::KATEGORI_OPTIONS)),
            'tingkat' => fake()->randomElement(PrestasiSiswa::TINGKAT_PRESETS),
            'peringkat' => fake()->randomElement(PrestasiSiswa::PERINGKAT_PRESETS),
            'penyelenggara' => fake()->randomElement([
                'Dinas Pendidikan Kabupaten Garut',
                'Kementerian Pendidikan Dasar dan Menengah',
                'Kementerian Agama Republik Indonesia',
                'JSIT Indonesia Wilayah Jawa Barat',
            ]),
            'tanggal' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'keterangan' => fake()->optional()->sentence(10),
            'foto' => null,
        ];
    }

    /**
     * Kaitkan minimal satu siswa saat model prestasi dibuat melalui factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (PrestasiSiswa $prestasi): void {
            if ($prestasi->siswa()->exists()) {
                return;
            }

            $nis = Siswa::query()->value('nis') ?? Siswa::factory()->create()->nis;
            $prestasi->siswa()->syncWithoutDetaching([$nis]);
        });
    }
}
