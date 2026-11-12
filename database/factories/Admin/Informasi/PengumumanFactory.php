<?php

namespace Database\Factories\Admin\Informasi;

use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Informasi\Pengumuman;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pengumuman>
 */
class PengumumanFactory extends Factory
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
            'judul' => fake()->randomElement([
                'Pemberitahuan Jadwal Ujian Tengah Semester',
                'Informasi Libur Awal Ramadhan',
                'Pengambilan Rapor Semester Ganjil',
                'Himbauan Penggunaan Seragam Lengkap',
                'Sosialisasi Program Ekstrakurikuler Baru',
            ]),
            'isi' => fake()->paragraphs(2, true),
            'jenis' => Pengumuman::JENIS_SISWA,
        ];
    }
}
