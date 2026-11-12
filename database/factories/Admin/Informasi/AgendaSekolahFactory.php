<?php

namespace Database\Factories\Admin\Informasi;

use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Informasi\AgendaSekolah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgendaSekolah>
 */
class AgendaSekolahFactory extends Factory
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
            'nama_agenda' => fake()->randomElement([
                'Upacara Peringatan Hari Pendidikan',
                'Rapat Koordinasi Awal Semester',
                'Penilaian Tengah Semester',
                'Pesantren Kilat Ramadhan',
                'Penerimaan Rapor Semester',
            ]),
            'tanggal' => fake()->dateTimeBetween('now', '+3 months')->format('Y-m-d'),
            'jam' => fake()->randomElement(['07:30', '08:00', '09:30', '13:00']),
            'unit' => fake()->randomElement(AgendaSekolah::UNIT_OPTIONS),
            'jenis' => AgendaSekolah::JENIS_SISWA,
        ];
    }
}
