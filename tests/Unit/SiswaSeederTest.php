<?php

namespace Tests\Unit;

use App\Models\Admin\DataMaster\Siswa;
use Database\Seeders\SiswaSeeder;
use ReflectionMethod;
use Tests\TestCase;

class SiswaSeederTest extends TestCase
{
    public function test_dummy_profiles_are_unique_realistic_and_use_valid_options(): void
    {
        $profiles = SiswaSeeder::dummyProfiles();

        $this->assertCount(20, $profiles);

        $nisList = array_column($profiles, 'nis');
        $this->assertCount(20, array_unique($nisList));

        $genders = array_values(array_unique(array_column($profiles, 'jenis_kelamin')));
        $this->assertEqualsCanonicalizing(['L', 'P'], $genders);

        $statuses = array_values(array_unique(array_column($profiles, 'status')));
        $this->assertEqualsCanonicalizing(Siswa::STATUS_OPTIONS, $statuses);

        foreach ($profiles as $profile) {
            $this->assertContains($profile['jenis_pendaftaran'], Siswa::JENIS_PENDAFTARAN_OPTIONS);
            $this->assertContains($profile['agama'], Siswa::AGAMA_OPTIONS);
            $this->assertNotEmpty($profile['nama_lengkap']);
            $this->assertGreaterThan(0, $profile['tinggi_badan']);
            $this->assertGreaterThan(0, $profile['berat_badan']);
        }
    }

    public function test_seeder_attribute_builder_matches_fillable_columns_and_handles_empty_or_populated_pools(): void
    {
        $seeder = new SiswaSeeder;
        $builder = new ReflectionMethod($seeder, 'buildSiswaAttributes');
        $profiles = SiswaSeeder::dummyProfiles();
        $fillable = (new Siswa)->getFillable();

        $emptyPools = [
            'kelas' => [],
            'karyawan' => [],
            'ekstrakurikuler' => [],
            'jemputan' => [],
            'berkebutuhan_khusus' => [],
            'transportasi' => [],
            'jenjang_pendidikan' => [],
            'pekerjaan' => [],
            'penghasilan' => [],
        ];

        $attributesWithEmptyPools = $builder->invoke($seeder, $profiles[0], 0, $emptyPools);
        foreach (array_keys($attributesWithEmptyPools) as $column) {
            $this->assertContains($column, $fillable);
        }
        $this->assertArrayNotHasKey('tarif_spp_id', $attributesWithEmptyPools);
        $this->assertNull($attributesWithEmptyPools['akademik_master_kelas_id']);
        $this->assertNull($attributesWithEmptyPools['karyawan_nipy']);

        $populatedPools = [
            'kelas' => [
                ['id' => 11, 'tahun_ajaran_id' => 1],
                ['id' => 12, 'tahun_ajaran_id' => 1],
            ],
            'karyawan' => ['19900101-1', '19900101-2'],
            'ekstrakurikuler' => [31, 32],
            'jemputan' => [41],
            'berkebutuhan_khusus' => [51],
            'transportasi' => [61, 62],
            'jenjang_pendidikan' => [71, 72],
            'pekerjaan' => [81, 82],
            'penghasilan' => [91, 92],
        ];

        $activeAttributes = $builder->invoke($seeder, $profiles[0], 0, $populatedPools);
        $this->assertSame(11, $activeAttributes['akademik_master_kelas_id']);
        $this->assertSame('19900101-1', $activeAttributes['karyawan_nipy']);
        $this->assertSame(31, $activeAttributes['akademik_master_ekstrakuler_id']);
        $this->assertSame(41, $activeAttributes['akademik_master_jemputan_id']);
        $this->assertSame(81, $activeAttributes['akademik_pelengkap_pekerjaan_ayah_id']);
        $this->assertSame(82, $activeAttributes['akademik_pelengkap_pekerjaan_ibu_id']);

        $inactiveProfile = $profiles[19]; // Nonaktif
        $inactiveAttributes = $builder->invoke($seeder, $inactiveProfile, 19, $populatedPools);
        $this->assertSame('Nonaktif', $inactiveAttributes['status']);
        $this->assertNull($inactiveAttributes['akademik_master_kelas_id']);
        $this->assertNull($inactiveAttributes['akademik_master_ekstrakuler_id']);
        $this->assertNull($inactiveAttributes['akademik_master_jemputan_id']);
    }

    public function test_siswa_factory_generates_extended_biodata_and_excludes_tarif_spp(): void
    {
        $siswa = Siswa::factory()->make();

        $this->assertStringStartsWith('2026', $siswa->nis);
        $this->assertNotEmpty($siswa->nik_ayah);
        $this->assertNotEmpty($siswa->nik_ibu);
        $this->assertNotEmpty($siswa->lintang);
        $this->assertNotEmpty($siswa->bujur);
        $this->assertArrayNotHasKey('tarif_spp_id', $siswa->getAttributes());
    }
}
