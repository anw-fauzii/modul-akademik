<?php

namespace Database\Factories\Admin\TujuanPembelajaran;

use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TujuanPembelajaran>
 */
class TujuanPembelajaranFactory extends Factory
{
    protected $model = TujuanPembelajaran::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tingkatan = (string) fake()->randomElement(['1', '2', '3', '4', '5', '6']);

        return [
            'akademik_master_tahun_ajaran_id' => fn () => TahunAjaran::query()->value('id')
                ?? TahunAjaran::create([
                    'nama_tahun_ajaran' => '2026/2027',
                    'semester' => '1',
                    'is_active' => true,
                ])->id,
            'akademik_pelajaran_mata_pelajaran_id' => MataPelajaran::factory(),
            'tingkatan_kelas' => $tingkatan,
            'fase' => TujuanPembelajaran::resolveDefaultFase('SD', $tingkatan),
            'elemen' => fake()->randomElement(['Bilangan', 'Aljabar', 'Pengukuran', 'Menyimak', 'Membaca dan Memirsa']),
            'kode_tp' => 'TP-'.fake()->unique()->numerify('###'),
            'tujuan_pembelajaran' => 'Peserta didik mampu memahami konsep '.fake()->words(5, true).' dengan tepat.',
            'lingkup_materi' => fake()->words(3, true),
            'urutan' => fake()->numberBetween(1, 15),
            'status' => true,
        ];
    }
}
