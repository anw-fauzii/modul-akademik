<?php

namespace Database\Factories\Admin\DataMaster;

use App\Models\Admin\DataMaster\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Siswa>
 */
class SiswaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nis' => '2026'.fake()->unique()->numerify('#####'),
            'akademik_master_kelas_id' => null,
            'karyawan_nipy' => null,
            'akademik_master_ekstrakuler_id' => null,
            'akademik_master_jemputan_id' => null,
            'jenis_pendaftaran' => fake()->randomElement(Siswa::JENIS_PENDAFTARAN_OPTIONS),
            'nama_lengkap' => fake()->name(),
            'jenis_kelamin' => fake()->randomElement(array_keys(Siswa::JENIS_KELAMIN_OPTIONS)),
            'nisn' => fake()->numerify('00########'),
            'nik' => fake()->numerify('3201############'),
            'no_kk' => fake()->numerify('3201############'),
            'tempat_lahir' => fake()->city(),
            'tanggal_lahir' => fake()->dateTimeBetween('-18 years', '-5 years')->format('Y-m-d'),
            'akta_lahir' => fake()->bothify('AKTA-####-????'),
            'agama' => 'Islam',
            'kewarganegaraan' => 'WNI',
            'nama_negara' => 'Indonesia',
            'akademik_pelengkap_berkebutuhan_khusus_id' => null,
            'alamat' => fake()->streetAddress(),
            'rt' => '001',
            'rw' => '002',
            'desa' => fake()->citySuffix(),
            'kecamatan' => fake()->city(),
            'kabupaten' => fake()->city(),
            'provinsi' => 'Jawa Barat',
            'kode_pos' => fake()->postcode(),
            'lintang' => (string) fake()->latitude(-7.1, -6.7),
            'bujur' => (string) fake()->longitude(107.4, 107.8),
            'tempat_tinggal' => 'Bersama Orang Tua',
            'akademik_pelengkap_transportasi_id' => null,
            'anak_ke' => 1,
            'jumlah_saudara' => 2,
            'nik_ayah' => fake()->numerify('3273############'),
            'nama_ayah' => fake()->name('male'),
            'lahir_ayah' => (string) fake()->numberBetween(1975, 1990),
            'nik_ibu' => fake()->numerify('3273############'),
            'nama_ibu' => fake()->name('female'),
            'lahir_ibu' => (string) fake()->numberBetween(1978, 1993),
            'nomor_hp' => fake()->numerify('0812########'),
            'whatsapp' => fake()->numerify('0812########'),
            'email' => fake()->unique()->safeEmail(),
            'tinggi_badan' => fake()->numberBetween(110, 175),
            'berat_badan' => fake()->numberBetween(20, 70),
            'jarak' => fake()->randomFloat(2, 0.5, 20),
            'lingkar_kepala' => fake()->numberBetween(48, 58),
            'waktu_tempuh' => fake()->numberBetween(5, 60),
            'avatar' => null,
            'status' => 'Aktif',
        ];
    }
}
