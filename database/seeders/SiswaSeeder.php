<?php

namespace Database\Seeders;

use App\Models\Admin\DataMaster\AnggotaEkstrakurikuler;
use App\Models\Admin\DataMaster\AnggotaJemputan;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Models\Admin\DataMaster\Jemputan;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataPelengkap\BerkebutuhanKhusus;
use App\Models\Admin\DataPelengkap\JenjangPendidikan;
use App\Models\Admin\DataPelengkap\Pekerjaan;
use App\Models\Admin\DataPelengkap\Penghasilan;
use App\Models\Admin\DataPelengkap\Transportasi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SiswaSeeder extends Seeder
{
    /**
     * Daftar data dummy siswa realistis untuk pengujian & pengembangan Data Master.
     *
     * @return list<array{
     *     nis: string,
     *     nama_lengkap: string,
     *     jenis_kelamin: string,
     *     jenis_pendaftaran: string,
     *     status: string,
     *     tempat_lahir: string,
     *     tanggal_lahir: string,
     *     agama: string,
     *     nama_ayah: string,
     *     nama_ibu: string,
     *     kecamatan: string,
     *     kabupaten: string,
     *     tinggi_badan: int,
     *     berat_badan: int,
     *     jarak: float
     * }>
     */
    public static function dummyProfiles(): array
    {
        return [
            [
                'nis' => '20260001',
                'nama_lengkap' => 'Ahmad Fauzi Pratama',
                'jenis_kelamin' => 'L',
                'jenis_pendaftaran' => 'Siswa Baru',
                'status' => 'Aktif',
                'tempat_lahir' => 'Bandung',
                'tanggal_lahir' => '2019-03-14',
                'agama' => 'Islam',
                'nama_ayah' => 'Hendra Pratama',
                'nama_ibu' => 'Siti Rahmawati',
                'kecamatan' => 'Coblong',
                'kabupaten' => 'Kota Bandung',
                'tinggi_badan' => 122,
                'berat_badan' => 24,
                'jarak' => 2.40,
            ],
            [
                'nis' => '20260002',
                'nama_lengkap' => 'Aisyah Nurul Hikmah',
                'jenis_kelamin' => 'P',
                'jenis_pendaftaran' => 'Siswa Baru',
                'status' => 'Aktif',
                'tempat_lahir' => 'Cimahi',
                'tanggal_lahir' => '2019-06-21',
                'agama' => 'Islam',
                'nama_ayah' => 'Dedi Kurniawan',
                'nama_ibu' => 'Nita Sari',
                'kecamatan' => 'Cimahi Tengah',
                'kabupaten' => 'Kota Cimahi',
                'tinggi_badan' => 119,
                'berat_badan' => 22,
                'jarak' => 3.80,
            ],
            [
                'nis' => '20260003',
                'nama_lengkap' => 'Muhammad Rayyan Al-Fatih',
                'jenis_kelamin' => 'L',
                'jenis_pendaftaran' => 'Siswa Baru',
                'status' => 'Aktif',
                'tempat_lahir' => 'Bandung',
                'tanggal_lahir' => '2018-11-05',
                'agama' => 'Islam',
                'nama_ayah' => 'Rizki Ramadhan',
                'nama_ibu' => 'Dewi Lestari',
                'kecamatan' => 'Sukajadi',
                'kabupaten' => 'Kota Bandung',
                'tinggi_badan' => 125,
                'berat_badan' => 26,
                'jarak' => 1.75,
            ],
            [
                'nis' => '20260004',
                'nama_lengkap' => 'Zahra Putri Khairunnisa',
                'jenis_kelamin' => 'P',
                'jenis_pendaftaran' => 'Lanjutan',
                'status' => 'Aktif',
                'tempat_lahir' => 'Sumedang',
                'tanggal_lahir' => '2018-02-18',
                'agama' => 'Islam',
                'nama_ayah' => 'Agus Supriatna',
                'nama_ibu' => 'Rina Marlina',
                'kecamatan' => 'Jatinangor',
                'kabupaten' => 'Kabupaten Sumedang',
                'tinggi_badan' => 128,
                'berat_badan' => 27,
                'jarak' => 5.60,
            ],
            [
                'nis' => '20260005',
                'nama_lengkap' => 'Bima Sakti Nugraha',
                'jenis_kelamin' => 'L',
                'jenis_pendaftaran' => 'Pindahan',
                'status' => 'Aktif',
                'tempat_lahir' => 'Garut',
                'tanggal_lahir' => '2017-08-09',
                'agama' => 'Islam',
                'nama_ayah' => 'Taufik Hidayat',
                'nama_ibu' => 'Yanti Susanti',
                'kecamatan' => 'Arcamanik',
                'kabupaten' => 'Kota Bandung',
                'tinggi_badan' => 132,
                'berat_badan' => 29,
                'jarak' => 4.20,
            ],
            [
                'nis' => '20260006',
                'nama_lengkap' => 'Nayla Azzahra Putri',
                'jenis_kelamin' => 'P',
                'jenis_pendaftaran' => 'Lanjutan',
                'status' => 'Aktif',
                'tempat_lahir' => 'Bandung',
                'tanggal_lahir' => '2017-05-27',
                'agama' => 'Islam',
                'nama_ayah' => 'Fajar Maulana',
                'nama_ibu' => 'Maya Puspita',
                'kecamatan' => 'Antapani',
                'kabupaten' => 'Kota Bandung',
                'tinggi_badan' => 131,
                'berat_badan' => 28,
                'jarak' => 2.10,
            ],
            [
                'nis' => '20260007',
                'nama_lengkap' => 'Arkananta Rizky Wijaya',
                'jenis_kelamin' => 'L',
                'jenis_pendaftaran' => 'Lanjutan',
                'status' => 'Aktif',
                'tempat_lahir' => 'Jakarta',
                'tanggal_lahir' => '2016-12-03',
                'agama' => 'Islam',
                'nama_ayah' => 'Bambang Wijaya',
                'nama_ibu' => 'Ratna Dewi',
                'kecamatan' => 'Buahbatu',
                'kabupaten' => 'Kota Bandung',
                'tinggi_badan' => 137,
                'berat_badan' => 33,
                'jarak' => 6.40,
            ],
            [
                'nis' => '20260008',
                'nama_lengkap' => 'Keisha Salsabila Amanda',
                'jenis_kelamin' => 'P',
                'jenis_pendaftaran' => 'Siswa Baru',
                'status' => 'Aktif',
                'tempat_lahir' => 'Bandung',
                'tanggal_lahir' => '2019-09-12',
                'agama' => 'Islam',
                'nama_ayah' => 'Irfan Hakim',
                'nama_ibu' => 'Sari Wulandari',
                'kecamatan' => 'Cidadap',
                'kabupaten' => 'Kota Bandung',
                'tinggi_badan' => 120,
                'berat_badan' => 23,
                'jarak' => 3.10,
            ],
            [
                'nis' => '20260009',
                'nama_lengkap' => 'Farhan Maulana Yusuf',
                'jenis_kelamin' => 'L',
                'jenis_pendaftaran' => 'Lanjutan',
                'status' => 'Aktif',
                'tempat_lahir' => 'Tasikmalaya',
                'tanggal_lahir' => '2016-04-16',
                'agama' => 'Islam',
                'nama_ayah' => 'Yusuf Mansyur',
                'nama_ibu' => 'Fitri Handayani',
                'kecamatan' => 'Lengkong',
                'kabupaten' => 'Kota Bandung',
                'tinggi_badan' => 140,
                'berat_badan' => 35,
                'jarak' => 4.90,
            ],
            [
                'nis' => '20260010',
                'nama_lengkap' => 'Khansa Humaira Yasmin',
                'jenis_kelamin' => 'P',
                'jenis_pendaftaran' => 'Siswa Baru',
                'status' => 'Aktif',
                'tempat_lahir' => 'Bandung',
                'tanggal_lahir' => '2018-07-30',
                'agama' => 'Islam',
                'nama_ayah' => 'Ridwan Kamiludin',
                'nama_ibu' => 'Lia Amalia',
                'kecamatan' => 'Cicendo',
                'kabupaten' => 'Kota Bandung',
                'tinggi_badan' => 126,
                'berat_badan' => 25,
                'jarak' => 1.50,
            ],
            [
                'nis' => '20260011',
                'nama_lengkap' => 'Daffa Alifiansyah Putra',
                'jenis_kelamin' => 'L',
                'jenis_pendaftaran' => 'Lanjutan',
                'status' => 'Aktif',
                'tempat_lahir' => 'Subang',
                'tanggal_lahir' => '2015-10-19',
                'agama' => 'Islam',
                'nama_ayah' => 'Asep Saepudin',
                'nama_ibu' => 'Euis Kartika',
                'kecamatan' => 'Cibeunying Kaler',
                'kabupaten' => 'Kota Bandung',
                'tinggi_badan' => 145,
                'berat_badan' => 38,
                'jarak' => 7.20,
            ],
            [
                'nis' => '20260012',
                'nama_lengkap' => 'Salma Nabila Azzahra',
                'jenis_kelamin' => 'P',
                'jenis_pendaftaran' => 'Pindahan',
                'status' => 'Aktif',
                'tempat_lahir' => 'Bogor',
                'tanggal_lahir' => '2015-01-25',
                'agama' => 'Islam',
                'nama_ayah' => 'Rahmatullah',
                'nama_ibu' => 'Indah Permata',
                'kecamatan' => 'Rancasari',
                'kabupaten' => 'Kota Bandung',
                'tinggi_badan' => 143,
                'berat_badan' => 36,
                'jarak' => 8.50,
            ],
            [
                'nis' => '20260013',
                'nama_lengkap' => 'Rafiqi Zaki Al-Qarni',
                'jenis_kelamin' => 'L',
                'jenis_pendaftaran' => 'Siswa Baru',
                'status' => 'Aktif',
                'tempat_lahir' => 'Bandung',
                'tanggal_lahir' => '2019-12-08',
                'agama' => 'Islam',
                'nama_ayah' => 'Imam Syafii',
                'nama_ibu' => 'Nurul Aini',
                'kecamatan' => 'Andir',
                'kabupaten' => 'Kota Bandung',
                'tinggi_badan' => 118,
                'berat_badan' => 21,
                'jarak' => 2.90,
            ],
            [
                'nis' => '20260014',
                'nama_lengkap' => 'Clarissa Aurelia Gunawan',
                'jenis_kelamin' => 'P',
                'jenis_pendaftaran' => 'Siswa Baru',
                'status' => 'Aktif',
                'tempat_lahir' => 'Bandung',
                'tanggal_lahir' => '2018-04-02',
                'agama' => 'Kristen',
                'nama_ayah' => 'Herry Gunawan',
                'nama_ibu' => 'Melissa Tanuwijaya',
                'kecamatan' => 'Sumur Bandung',
                'kabupaten' => 'Kota Bandung',
                'tinggi_badan' => 127,
                'berat_badan' => 26,
                'jarak' => 3.30,
            ],
            [
                'nis' => '20260015',
                'nama_lengkap' => 'Naufal Haikal Dzaki',
                'jenis_kelamin' => 'L',
                'jenis_pendaftaran' => 'Lanjutan',
                'status' => 'Aktif',
                'tempat_lahir' => 'Cirebon',
                'tanggal_lahir' => '2014-06-11',
                'agama' => 'Islam',
                'nama_ayah' => 'Syamsul Bahri',
                'nama_ibu' => 'Wiwik Hartati',
                'kecamatan' => 'Batununggal',
                'kabupaten' => 'Kota Bandung',
                'tinggi_badan' => 151,
                'berat_badan' => 42,
                'jarak' => 5.10,
            ],
            [
                'nis' => '20260016',
                'nama_lengkap' => 'Hana Kirana Maharani',
                'jenis_kelamin' => 'P',
                'jenis_pendaftaran' => 'Lanjutan',
                'status' => 'Aktif',
                'tempat_lahir' => 'Bandung',
                'tanggal_lahir' => '2014-09-23',
                'agama' => 'Islam',
                'nama_ayah' => 'Andi Setiawan',
                'nama_ibu' => 'Tika Puspita',
                'kecamatan' => 'Kiaracondong',
                'kabupaten' => 'Kota Bandung',
                'tinggi_badan' => 148,
                'berat_badan' => 40,
                'jarak' => 4.40,
            ],
            [
                'nis' => '20260017',
                'nama_lengkap' => 'Gibran Putra Mahardika',
                'jenis_kelamin' => 'L',
                'jenis_pendaftaran' => 'Lanjutan',
                'status' => 'Lulus',
                'tempat_lahir' => 'Bandung',
                'tanggal_lahir' => '2013-07-17',
                'agama' => 'Islam',
                'nama_ayah' => 'Darmawan Putra',
                'nama_ibu' => 'Sri Wahyuni',
                'kecamatan' => 'Mandalajati',
                'kabupaten' => 'Kota Bandung',
                'tinggi_badan' => 158,
                'berat_badan' => 48,
                'jarak' => 6.00,
            ],
            [
                'nis' => '20260018',
                'nama_lengkap' => 'Syifa Marwah Aliyah',
                'jenis_kelamin' => 'P',
                'jenis_pendaftaran' => 'Pindahan',
                'status' => 'Pindah',
                'tempat_lahir' => 'Bekasi',
                'tanggal_lahir' => '2016-03-29',
                'agama' => 'Islam',
                'nama_ayah' => 'Ilham Akbar',
                'nama_ibu' => 'Nadia Kusuma',
                'kecamatan' => 'Bojongloa Kidul',
                'kabupaten' => 'Kota Bandung',
                'tinggi_badan' => 136,
                'berat_badan' => 31,
                'jarak' => 9.10,
            ],
            [
                'nis' => '20260019',
                'nama_lengkap' => 'Kevin Andreas Simanjuntak',
                'jenis_kelamin' => 'L',
                'jenis_pendaftaran' => 'Pindahan',
                'status' => 'Keluar',
                'tempat_lahir' => 'Medan',
                'tanggal_lahir' => '2015-11-15',
                'agama' => 'Kristen',
                'nama_ayah' => 'Poltak Simanjuntak',
                'nama_ibu' => 'Rospita Siregar',
                'kecamatan' => 'Regol',
                'kabupaten' => 'Kota Bandung',
                'tinggi_badan' => 144,
                'berat_badan' => 37,
                'jarak' => 5.80,
            ],
            [
                'nis' => '20260020',
                'nama_lengkap' => 'Putri Melati Kusumawati',
                'jenis_kelamin' => 'P',
                'jenis_pendaftaran' => 'Siswa Baru',
                'status' => 'Nonaktif',
                'tempat_lahir' => 'Bandung',
                'tanggal_lahir' => '2019-01-07',
                'agama' => 'Islam',
                'nama_ayah' => 'Hadi Susanto',
                'nama_ibu' => 'Lina Marlina',
                'kecamatan' => 'Gedebage',
                'kabupaten' => 'Kota Bandung',
                'tinggi_badan' => 121,
                'berat_badan' => 23,
                'jarak' => 11.20,
            ],
        ];
    }

    /**
     * Jalankan seeder data dummy siswa secara idempoten dan bebas N+1.
     */
    public function run(): void
    {
        $profiles = self::dummyProfiles();
        $pools = $this->loadReferencePools();

        DB::transaction(function () use ($profiles, $pools): void {
            $activeStudentsForKelas = [];
            $activeStudentsForEkstrakurikuler = [];
            $activeStudentsForJemputan = [];

            foreach ($profiles as $index => $profile) {
                $attributes = $this->buildSiswaAttributes($profile, $index, $pools);

                Siswa::query()->updateOrCreate(
                    ['nis' => $profile['nis']],
                    $attributes
                );

                if ($profile['status'] === 'Aktif' && $attributes['akademik_master_kelas_id'] !== null) {
                    $activeStudentsForKelas[$profile['nis']] = [
                        'kelas_id' => (int) $attributes['akademik_master_kelas_id'],
                        'pendaftaran' => $this->mapJenisPendaftaranToAnggotaKelas($profile['jenis_pendaftaran']),
                    ];
                }

                $membershipPendaftaran = $profile['jenis_pendaftaran'] === 'Siswa Baru' ? 'Baru' : 'Lanjutan';

                if ($profile['status'] === 'Aktif' && $attributes['akademik_master_ekstrakuler_id'] !== null) {
                    $activeStudentsForEkstrakurikuler[$profile['nis']] = [
                        'ekstrakurikuler_id' => (int) $attributes['akademik_master_ekstrakuler_id'],
                        'pendaftaran' => $membershipPendaftaran,
                    ];
                }

                if ($profile['status'] === 'Aktif' && $attributes['akademik_master_jemputan_id'] !== null) {
                    $activeStudentsForJemputan[$profile['nis']] = [
                        'jemputan_id' => (int) $attributes['akademik_master_jemputan_id'],
                        'pendaftaran' => $membershipPendaftaran,
                    ];
                }
            }

            $this->syncAnggotaKelasForSeededSiswa($activeStudentsForKelas, $pools['kelas']);
            $this->syncAnggotaEkstrakurikulerForSeededSiswa($activeStudentsForEkstrakurikuler);
            $this->syncAnggotaJemputanForSeededSiswa($activeStudentsForJemputan);
        });
    }

    /**
     * Memuat daftar ID referensi master & pelengkap sekaligus (maksimal 1 query per tabel yang tersedia).
     *
     * @return array{
     *     kelas: list<array{id: int, tahun_ajaran_id: int}>,
     *     karyawan: list<string>,
     *     ekstrakurikuler: list<int>,
     *     jemputan: list<int>,
     *     berkebutuhan_khusus: list<int>,
     *     transportasi: list<int>,
     *     jenjang_pendidikan: list<int>,
     *     pekerjaan: list<int>,
     *     penghasilan: list<int>
     * }
     */
    private function loadReferencePools(): array
    {
        $kelasRows = [];

        if (Schema::hasTable('akademik_master_kelas') && Schema::hasTable('akademik_master_tahun_ajaran')) {
            $activeKelas = Kelas::query()
                ->select(['id', 'akademik_master_tahun_ajaran_id'])
                ->whereHas('tahunAjaran', fn ($query) => $query->where('is_active', true))
                ->orderBy('id')
                ->get();

            $sourceKelas = $activeKelas->isNotEmpty()
                ? $activeKelas
                : Kelas::query()
                    ->select(['id', 'akademik_master_tahun_ajaran_id'])
                    ->orderBy('id')
                    ->get();

            $kelasRows = $sourceKelas
                ->map(static fn (Kelas $kelas): array => [
                    'id' => (int) $kelas->id,
                    'tahun_ajaran_id' => (int) $kelas->akademik_master_tahun_ajaran_id,
                ])
                ->values()
                ->all();
        }

        $ekstrakurikulerIds = $this->pluckActiveYearIdsIfTableExists('akademik_master_ekstrakuler', Ekstrakurikuler::class);
        $jemputanIds = $this->pluckActiveYearIdsIfTableExists('akademik_master_jemputan', Jemputan::class);

        return [
            'kelas' => $kelasRows,
            'karyawan' => Schema::hasTable('kepegawaian_master_karyawan')
                ? Karyawan::query()->orderBy('nipy')->pluck('nipy')->map(static fn (mixed $v): string => (string) $v)->all()
                : [],
            'ekstrakurikuler' => $ekstrakurikulerIds,
            'jemputan' => $jemputanIds,
            'berkebutuhan_khusus' => $this->pluckIntColumnIfTableExists('akademik_pelengkap_berkebutuhan_khusus', BerkebutuhanKhusus::class),
            'transportasi' => $this->pluckIntColumnIfTableExists('akademik_pelengkap_transportasi', Transportasi::class),
            'jenjang_pendidikan' => $this->pluckIntColumnIfTableExists('akademik_pelengkap_jenjang_pendidikan', JenjangPendidikan::class),
            'pekerjaan' => $this->pluckIntColumnIfTableExists('akademik_pelengkap_pekerjaan', Pekerjaan::class),
            'penghasilan' => $this->pluckIntColumnIfTableExists('akademik_pelengkap_penghasilan', Penghasilan::class),
        ];
    }

    /**
     * Mengambil daftar ID master pada tahun ajaran aktif (atau seluruh data bila belum ada TA aktif).
     *
     * @param  class-string<Model>  $modelClass
     * @return list<int>
     */
    private function pluckActiveYearIdsIfTableExists(string $table, string $modelClass): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        if (Schema::hasTable('akademik_master_tahun_ajaran')) {
            $activeIds = $modelClass::query()
                ->whereHas('tahunAjaran', fn ($query) => $query->where('is_active', true))
                ->orderBy('id')
                ->pluck('id');

            return ($activeIds->isNotEmpty()
                ? $activeIds
                : $modelClass::query()->orderBy('id')->pluck('id'))
                ->map(static fn (mixed $id): int => (int) $id)
                ->values()
                ->all();
        }

        return $this->pluckIntColumnIfTableExists($table, $modelClass);
    }

    /**
     * Mengambil daftar primary key integer bila tabel referensi tersedia.
     *
     * @param  class-string<Model>  $modelClass
     * @return list<int>
     */
    private function pluckIntColumnIfTableExists(string $table, string $modelClass): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        return $modelClass::query()
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * Menyusun payload lengkap atribut siswa sesuai skema tabel akademik_master_siswa.
     *
     * @param  array<string, mixed>  $profile
     * @param  array<string, list<mixed>>  $pools
     * @return array<string, mixed>
     */
    private function buildSiswaAttributes(array $profile, int $index, array $pools): array
    {
        $nomorUrut = str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
        $selectedKelas = $this->pickCyclic($pools['kelas'], $index);
        $isActive = $profile['status'] === 'Aktif';
        $hpNumber = '08122026'.$nomorUrut;

        return [
            'akademik_master_kelas_id' => $isActive && is_array($selectedKelas) ? $selectedKelas['id'] : null,
            'karyawan_nipy' => $this->pickCyclic($pools['karyawan'], $index),
            'akademik_master_ekstrakuler_id' => $isActive ? $this->pickCyclic($pools['ekstrakurikuler'], $index) : null,
            'akademik_master_jemputan_id' => $isActive && ($index % 2 === 0) ? $this->pickCyclic($pools['jemputan'], $index) : null,
            'jenis_pendaftaran' => $profile['jenis_pendaftaran'],
            'nama_lengkap' => $profile['nama_lengkap'],
            'jenis_kelamin' => $profile['jenis_kelamin'],
            'nisn' => '001520'.$nomorUrut,
            'nik' => '327301100118'.$nomorUrut,
            'no_kk' => '327301010115'.$nomorUrut,
            'tempat_lahir' => $profile['tempat_lahir'],
            'tanggal_lahir' => $profile['tanggal_lahir'],
            'akta_lahir' => 'AKTA-3273-2026-'.$nomorUrut,
            'agama' => $profile['agama'],
            'kewarganegaraan' => 'WNI',
            'nama_negara' => 'Indonesia',
            'akademik_pelengkap_berkebutuhan_khusus_id' => $this->pickCyclic($pools['berkebutuhan_khusus'], 0),
            'alamat' => 'Jl. Pendidikan Prima No. '.($index + 10),
            'rt' => '00'.(($index % 5) + 1),
            'rw' => '00'.(($index % 3) + 1),
            'desa' => 'Dago',
            'kecamatan' => $profile['kecamatan'],
            'kabupaten' => $profile['kabupaten'],
            'provinsi' => 'Jawa Barat',
            'kode_pos' => '4013'.($index % 9),
            'lintang' => '-6.89'.str_pad((string) (10 + $index), 2, '0', STR_PAD_LEFT),
            'bujur' => '107.61'.str_pad((string) (10 + $index), 2, '0', STR_PAD_LEFT),
            'tempat_tinggal' => 'Bersama Orang Tua',
            'akademik_pelengkap_transportasi_id' => $this->pickCyclic($pools['transportasi'], $index),
            'anak_ke' => ($index % 3) + 1,
            'jumlah_saudara' => ($index % 3) + 1,
            'nik_ayah' => '327301010582'.$nomorUrut,
            'nama_ayah' => $profile['nama_ayah'],
            'lahir_ayah' => (string) (1980 + ($index % 9)),
            'akademik_pelengkap_jenjang_pendidikan_ayah_id' => $this->pickCyclic($pools['jenjang_pendidikan'], $index),
            'akademik_pelengkap_pekerjaan_ayah_id' => $this->pickCyclic($pools['pekerjaan'], $index),
            'akademik_pelengkap_penghasilan_ayah_id' => $this->pickCyclic($pools['penghasilan'], $index),
            'akademik_pelengkap_berkebutuhan_khusus_ayah_id' => $this->pickCyclic($pools['berkebutuhan_khusus'], 0),
            'nik_ibu' => '327301410885'.$nomorUrut,
            'nama_ibu' => $profile['nama_ibu'],
            'lahir_ibu' => (string) (1983 + ($index % 9)),
            'akademik_pelengkap_jenjang_pendidikan_ibu_id' => $this->pickCyclic($pools['jenjang_pendidikan'], $index + 1),
            'akademik_pelengkap_pekerjaan_ibu_id' => $this->pickCyclic($pools['pekerjaan'], $index + 1),
            'akademik_pelengkap_penghasilan_ibu_id' => $this->pickCyclic($pools['penghasilan'], $index + 1),
            'akademik_pelengkap_berkebutuhan_khusus_ibu_id' => $this->pickCyclic($pools['berkebutuhan_khusus'], 0),
            'nik_wali' => null,
            'nama_wali' => null,
            'lahir_wali' => null,
            'akademik_pelengkap_jenjang_pendidikan_wali_id' => null,
            'akademik_pelengkap_pekerjaan_wali_id' => null,
            'akademik_pelengkap_penghasilan_wali_id' => null,
            'akademik_pelengkap_berkebutuhan_khusus_wali_id' => null,
            'nomor_hp' => $hpNumber,
            'whatsapp' => $hpNumber,
            'email' => "siswa{$nomorUrut}@primainsani.sch.id",
            'tinggi_badan' => $profile['tinggi_badan'],
            'berat_badan' => $profile['berat_badan'],
            'jarak' => $profile['jarak'],
            'lingkar_kepala' => 51 + ($index % 5),
            'waktu_tempuh' => 10 + (($index % 6) * 5),
            'avatar' => null,
            'status' => $profile['status'],
        ];
    }

    /**
     * Memilih elemen secara siklik agar distribusi FK merata bila data referensi tersedia.
     *
     * @template T
     *
     * @param  list<T>  $items
     * @return T|null
     */
    private function pickCyclic(array $items, int $index): mixed
    {
        if ($items === []) {
            return null;
        }

        return $items[$index % count($items)];
    }

    /**
     * Memetakan jenis pendaftaran siswa ke opsi pendaftaran anggota kelas yang valid.
     */
    private function mapJenisPendaftaranToAnggotaKelas(string $jenisPendaftaran): string
    {
        return match ($jenisPendaftaran) {
            'Pindahan' => 'Pindahan',
            'Lanjutan' => 'Naik Kelas',
            default => 'Siswa Baru',
        };
    }

    /**
     * Menyinkronkan keanggotaan kelas tanpa duplikasi tahun ajaran & tanpa query N+1.
     *
     * @param  array<string, array{kelas_id: int, pendaftaran: string}>  $activeStudentsForKelas
     * @param  list<array{id: int, tahun_ajaran_id: int}>  $kelasPool
     */
    private function syncAnggotaKelasForSeededSiswa(array $activeStudentsForKelas, array $kelasPool): void
    {
        if ($activeStudentsForKelas === [] || $kelasPool === [] || ! Schema::hasTable('akademik_master_anggota_kelas')) {
            return;
        }

        $kelasYearMap = [];
        foreach ($kelasPool as $kelas) {
            $kelasYearMap[$kelas['id']] = $kelas['tahun_ajaran_id'];
        }

        $nisKeys = array_map('strval', array_keys($activeStudentsForKelas));

        $existingByStudentYear = AnggotaKelas::query()
            ->select(['id', 'siswa_nis', 'akademik_master_kelas_id'])
            ->whereIn('siswa_nis', $nisKeys)
            ->with(['kelas:id,akademik_master_tahun_ajaran_id'])
            ->get()
            ->mapWithKeys(static fn (AnggotaKelas $anggota): array => [
                $anggota->siswa_nis.':'.($anggota->kelas?->akademik_master_tahun_ajaran_id ?? 0) => true,
            ])
            ->all();

        foreach ($activeStudentsForKelas as $nis => $target) {
            $nisString = (string) $nis;
            $tahunAjaranId = $kelasYearMap[$target['kelas_id']] ?? 0;
            $key = $nisString.':'.$tahunAjaranId;

            if ($tahunAjaranId <= 0 || isset($existingByStudentYear[$key])) {
                continue;
            }

            AnggotaKelas::create([
                'siswa_nis' => $nisString,
                'akademik_master_kelas_id' => $target['kelas_id'],
                'pendaftaran' => $target['pendaftaran'],
            ]);

            $existingByStudentYear[$key] = true;
        }
    }

    /**
     * Menyinkronkan keanggotaan ekstrakurikuler berbasis anggota_kelas_id tanpa duplikasi tahun ajaran & tanpa query N+1.
     *
     * @param  array<string, array{ekstrakurikuler_id: int, pendaftaran: string}>  $activeStudentsForEkstrakurikuler
     */
    private function syncAnggotaEkstrakurikulerForSeededSiswa(array $activeStudentsForEkstrakurikuler): void
    {
        $normalized = array_map(
            static fn (array $target): array => [
                'group_id' => $target['ekstrakurikuler_id'],
                'pendaftaran' => $target['pendaftaran'],
            ],
            $activeStudentsForEkstrakurikuler
        );

        $this->syncAnggotaGroupMembershipForSeededSiswa(
            $normalized,
            'akademik_master_anggota_ekstrakuler',
            'akademik_master_ekstrakuler',
            AnggotaEkstrakurikuler::class,
            Ekstrakurikuler::class,
            'ekstrakurikuler',
            'akademik_master_ekstrakuler_id'
        );
    }

    /**
     * Menyinkronkan keanggotaan jemputan berbasis anggota_kelas_id tanpa duplikasi tahun ajaran & tanpa query N+1.
     *
     * @param  array<string, array{jemputan_id: int, pendaftaran: string}>  $activeStudentsForJemputan
     */
    private function syncAnggotaJemputanForSeededSiswa(array $activeStudentsForJemputan): void
    {
        $normalized = array_map(
            static fn (array $target): array => [
                'group_id' => $target['jemputan_id'],
                'pendaftaran' => $target['pendaftaran'],
            ],
            $activeStudentsForJemputan
        );

        $this->syncAnggotaGroupMembershipForSeededSiswa(
            $normalized,
            'akademik_master_anggota_jemputan',
            'akademik_master_jemputan',
            AnggotaJemputan::class,
            Jemputan::class,
            'jemputan',
            'akademik_master_jemputan_id'
        );
    }

    /**
     * Helper terpadu untuk menyinkronkan keanggotaan berbasis anggota_kelas_id saat seeding siswa.
     *
     * @param  array<string, array{group_id: int, pendaftaran: string}>  $activeStudents
     * @param  class-string<Model>  $membershipModelClass
     * @param  class-string<Model>  $groupModelClass
     */
    private function syncAnggotaGroupMembershipForSeededSiswa(
        array $activeStudents,
        string $membershipTable,
        string $groupTable,
        string $membershipModelClass,
        string $groupModelClass,
        string $groupRelation,
        string $groupForeignKey
    ): void {
        if (
            $activeStudents === []
            || ! Schema::hasTable($membershipTable)
            || ! Schema::hasTable('akademik_master_anggota_kelas')
            || ! Schema::hasTable($groupTable)
        ) {
            return;
        }

        $groupIds = array_values(array_unique(array_map(
            static fn (array $target): int => $target['group_id'],
            $activeStudents
        )));

        $groupYearMap = $groupModelClass::query()
            ->whereIn('id', $groupIds)
            ->pluck('akademik_master_tahun_ajaran_id', 'id')
            ->map(static fn (mixed $tahunAjaranId): int => (int) $tahunAjaranId)
            ->all();

        $nisKeys = array_map('strval', array_keys($activeStudents));

        $anggotaKelasByStudentYear = AnggotaKelas::query()
            ->select(['id', 'siswa_nis', 'akademik_master_kelas_id'])
            ->whereIn('siswa_nis', $nisKeys)
            ->with(['kelas:id,akademik_master_tahun_ajaran_id'])
            ->orderByDesc('id')
            ->get()
            ->mapWithKeys(static fn (AnggotaKelas $anggota): array => [
                $anggota->siswa_nis.':'.($anggota->kelas?->akademik_master_tahun_ajaran_id ?? 0) => (int) $anggota->id,
            ])
            ->all();

        $existingByStudentYear = $membershipModelClass::query()
            ->select(['id', 'anggota_kelas_id', $groupForeignKey])
            ->whereHas('anggotaKelas', fn ($query) => $query->whereIn('siswa_nis', $nisKeys))
            ->with([
                'anggotaKelas:id,siswa_nis',
                "{$groupRelation}:id,akademik_master_tahun_ajaran_id",
            ])
            ->get()
            ->mapWithKeys(static fn (Model $anggota): array => [
                ($anggota->getRelationValue('anggotaKelas')?->siswa_nis ?? '').':'.($anggota->getRelationValue($groupRelation)?->akademik_master_tahun_ajaran_id ?? 0) => true,
            ])
            ->all();

        foreach ($activeStudents as $nis => $target) {
            $nisString = (string) $nis;
            $tahunAjaranId = $groupYearMap[$target['group_id']] ?? 0;
            $key = $nisString.':'.$tahunAjaranId;
            $anggotaKelasId = $anggotaKelasByStudentYear[$key] ?? null;

            if ($tahunAjaranId <= 0 || $anggotaKelasId === null || isset($existingByStudentYear[$key])) {
                continue;
            }

            $membershipModelClass::create([
                'anggota_kelas_id' => $anggotaKelasId,
                $groupForeignKey => $target['group_id'],
                'pendaftaran' => $target['pendaftaran'],
            ]);

            $existingByStudentYear[$key] = true;
        }
    }
}
