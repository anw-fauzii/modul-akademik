<?php

namespace Tests\Unit;

use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Services\Admin\DataMaster\KelasService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class KelasModelAndViewTest extends TestCase
{
    public function test_service_index_query_configures_minimal_columns_eager_loads_and_student_count(): void
    {
        $service = new KelasService;
        $builder = $service->buildIndexQuery();
        $sql = $builder->toSql();

        $this->assertSame([
            'id',
            'akademik_master_tahun_ajaran_id',
            'karyawan_nipy',
            'pendamping_nipy',
            'tingkatan_kelas',
            'nama_kelas',
            'jenjang_kelas',
            'romawi',
        ], array_slice($builder->getQuery()->columns, 0, 8));

        $this->assertStringContainsString('akademik_master_anggota_kelas', $sql);
        $this->assertStringContainsString('anggota_kelas_count', $sql);

        $eagerLoads = $builder->getEagerLoads();
        $this->assertArrayHasKey('tahunAjaran', $eagerLoads);
        $this->assertArrayHasKey('waliKelas', $eagerLoads);
        $this->assertArrayHasKey('pendamping', $eagerLoads);

        $tahunAjaranQuery = TahunAjaran::query();
        ($eagerLoads['tahunAjaran'])($tahunAjaranQuery);
        $this->assertSame(['id', 'nama_tahun_ajaran', 'semester', 'is_active'], $tahunAjaranQuery->getQuery()->columns);

        $waliQuery = Karyawan::query();
        ($eagerLoads['waliKelas'])($waliQuery);
        $this->assertSame(['nipy', 'nama_lengkap', 'gelar'], $waliQuery->getQuery()->columns);

        $pendampingQuery = Karyawan::query();
        ($eagerLoads['pendamping'])($pendampingQuery);
        $this->assertSame(['nipy', 'nama_lengkap', 'gelar'], $pendampingQuery->getQuery()->columns);
    }

    public function test_index_view_renders_student_counts_without_lazy_loading(): void
    {
        $tahunAjaran = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);
        $tahunAjaran->id = 10;

        $waliKelas = Karyawan::make([
            'nipy' => '19900101-1',
            'nama_lengkap' => 'Budi Santoso',
            'gelar' => 'S.Pd',
        ]);

        $pendamping = Karyawan::make([
            'nipy' => '19920202-1',
            'nama_lengkap' => 'Siti Aminah',
            'gelar' => 'S.Pd',
        ]);

        $kelasTerisi = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 10,
            'karyawan_nipy' => '19900101-1',
            'pendamping_nipy' => '19920202-1',
            'tingkatan_kelas' => '1',
            'nama_kelas' => '1 Al-Fatihah',
            'jenjang_kelas' => 'SD',
            'romawi' => 'I',
        ]);
        $kelasTerisi->id = 21;
        $kelasTerisi->setAttribute('anggota_kelas_count', 24);
        $kelasTerisi->setRelation('tahunAjaran', $tahunAjaran);
        $kelasTerisi->setRelation('waliKelas', $waliKelas);
        $kelasTerisi->setRelation('pendamping', $pendamping);

        $kelasKosong = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 10,
            'karyawan_nipy' => '19900101-1',
            'tingkatan_kelas' => '2',
            'nama_kelas' => '2 Abu Bakar',
            'jenjang_kelas' => 'SD',
            'romawi' => 'II',
        ]);
        $kelasKosong->id = 22;
        $kelasKosong->setAttribute('anggota_kelas_count', 0);
        $kelasKosong->setRelation('tahunAjaran', $tahunAjaran);
        $kelasKosong->setRelation('waliKelas', $waliKelas);
        $kelasKosong->setRelation('pendamping', null);

        Model::preventLazyLoading(true);

        try {
            $html = view('admin.dataMaster.kelas.index', [
                'kelas' => new Collection([$kelasTerisi, $kelasKosong]),
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('Jumlah Siswa', $html);
        $this->assertStringContainsString('24 Siswa', $html);
        $this->assertStringContainsString('0 Siswa', $html);
        $this->assertStringContainsString(route('anggota-kelas.index', ['kelas_id' => 21]), $html);
        $this->assertStringContainsString(route('anggota-kelas.create', ['kelas_id' => 22]), $html);
    }
}

