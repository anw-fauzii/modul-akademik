<?php

namespace Database\Factories\Admin\DataMaster;

use App\Models\Admin\DataMaster\Sekolah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sekolah>
 */
class SekolahFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jenjang = fake()->randomElement(Sekolah::JENJANG_OPTIONS);

        return [
            'jenjang_kelas' => $jenjang,
            'nama_yayasan' => 'Yayasan Prima Insani',
            'nama_sekolah' => "{$jenjang} Prima Insani",
            'npsn' => fake()->numerify('202#####'),
            'nss' => fake()->numerify('102021######'),
            'alamat' => fake()->streetAddress(),
            'rt' => '01',
            'rw' => '02',
            'kelurahan' => 'Sukamentri',
            'kecamatan' => 'Garut Kota',
            'kabupaten' => 'Kabupaten Garut',
            'provinsi' => 'Jawa Barat',
            'kode_pos' => '44116',
            'telepon' => '(0262) 234567',
            'email' => strtolower($jenjang).'@primainsani.sch.id',
            'website' => 'https://primainsani.sch.id',
            'status' => true,
        ];
    }
}

