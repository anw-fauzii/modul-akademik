<?php

namespace Tests\Unit;

use App\Http\Requests\Admin\DataMaster\SiswaRequest;
use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Models\Admin\DataMaster\Jemputan;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\DataPelengkap\BerkebutuhanKhusus;
use App\Models\Admin\DataPelengkap\JenjangPendidikan;
use App\Models\Admin\DataPelengkap\Pekerjaan;
use App\Models\Admin\DataPelengkap\Penghasilan;
use App\Models\Admin\DataPelengkap\Transportasi;
use App\Services\Admin\DataMaster\SiswaService;
use DOMDocument;
use DOMXPath;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class SiswaModelAndViewTest extends TestCase
{
    public function test_model_metadata_excludes_tarif_spp_and_configures_keys_and_casts(): void
    {
        $siswa = new Siswa;

        $this->assertSame('akademik_master_siswa', $siswa->getTable());
        $this->assertSame('nis', $siswa->getKeyName());
        $this->assertFalse($siswa->getIncrementing());
        $this->assertSame('string', $siswa->getKeyType());

        $fillable = $siswa->getFillable();
        $this->assertNotContains('tarif_spp_id', $fillable);
        $this->assertContains('akademik_master_kelas_id', $fillable);
        $this->assertContains('karyawan_nipy', $fillable);
        $this->assertContains('akademik_master_ekstrakuler_id', $fillable);
        $this->assertContains('akademik_master_jemputan_id', $fillable);
        $this->assertContains('akademik_pelengkap_berkebutuhan_khusus_id', $fillable);
        $this->assertContains('akademik_pelengkap_transportasi_id', $fillable);
        $this->assertContains('akademik_pelengkap_pekerjaan_ayah_id', $fillable);
        $this->assertContains('akademik_pelengkap_pekerjaan_ibu_id', $fillable);
        $this->assertContains('akademik_pelengkap_pekerjaan_wali_id', $fillable);

        $casts = $siswa->getCasts();
        $this->assertSame('date', $casts['tanggal_lahir']);
        $this->assertSame('integer', $casts['anak_ke']);
        $this->assertSame('float', $casts['jarak']);

        $this->assertSame('no_induk', $siswa->user()->getOwnerKeyName());
        $this->assertSame('nis', $siswa->user()->getForeignKeyName());
        $this->assertSame('karyawan_nipy', $siswa->guru()->getForeignKeyName());
        $this->assertSame('nipy', $siswa->guru()->getOwnerKeyName());
        $this->assertSame('akademik_master_kelas_id', $siswa->kelas()->getForeignKeyName());
        $this->assertSame('akademik_master_ekstrakuler_id', $siswa->ekstrakurikuler()->getForeignKeyName());
        $this->assertSame('akademik_master_jemputan_id', $siswa->jemputan()->getForeignKeyName());
        $this->assertSame('akademik_pelengkap_berkebutuhan_khusus_id', $siswa->berkebutuhanKhusus()->getForeignKeyName());
        $this->assertSame('akademik_pelengkap_transportasi_id', $siswa->transportasi()->getForeignKeyName());
    }

    #[TestWith(['L', 'Laki-laki'])]
    #[TestWith(['P', 'Perempuan'])]
    #[TestWith(['', '-'])]
    public function test_jenis_kelamin_label_maps_values(string $jk, string $expected): void
    {
        $siswa = Siswa::make(['jenis_kelamin' => $jk]);

        $this->assertSame($expected, $siswa->jenis_kelamin_label);
    }

    #[TestWith(['Aktif', 'badge-success'])]
    #[TestWith(['Lulus', 'badge-primary'])]
    #[TestWith(['Pindah', 'badge-warning text-dark'])]
    #[TestWith(['Keluar', 'badge-danger'])]
    #[TestWith(['Nonaktif', 'badge-secondary'])]
    public function test_status_badge_class_maps_values(string $status, string $expectedClass): void
    {
        $siswa = Siswa::make(['status' => $status]);

        $this->assertSame($expectedClass, $siswa->status_badge_class);
    }

    public function test_service_index_query_configures_minimal_columns_and_eager_loads(): void
    {
        $service = new SiswaService;
        $builder = $service->buildIndexQuery();
        $query = $builder->getQuery();

        $this->assertSame([
            'nis',
            'akademik_master_kelas_id',
            'karyawan_nipy',
            'akademik_master_ekstrakuler_id',
            'akademik_master_jemputan_id',
            'nama_lengkap',
            'jenis_kelamin',
            'nisn',
            'avatar',
            'status',
        ], $query->columns);

        $eagerLoads = $builder->getEagerLoads();
        $this->assertArrayHasKey('kelas', $eagerLoads);
        $this->assertArrayHasKey('karyawan', $eagerLoads);
        $this->assertArrayHasKey('ekstrakurikuler', $eagerLoads);
        $this->assertArrayHasKey('jemputan', $eagerLoads);
        $this->assertArrayHasKey('jemputan.petugas', $eagerLoads);

        $kelasQuery = Kelas::query();
        ($eagerLoads['kelas'])($kelasQuery);
        $this->assertSame(['id', 'nama_kelas', 'jenjang_kelas'], $kelasQuery->getQuery()->columns);

        $petugasQuery = Karyawan::query();
        ($eagerLoads['jemputan.petugas'])($petugasQuery);
        $this->assertSame(['nipy', 'nama_lengkap', 'gelar'], $petugasQuery->getQuery()->columns);
    }

    public function test_service_filter_normalization_and_filtered_query_builder(): void
    {
        $service = new SiswaService;

        $emptyFilters = $service->normalizeIndexFilters([
            'search' => '   ',
            'kelas_id' => 'invalid-id',
            'jenis_kelamin' => 'Z',
        ]);

        $this->assertFalse($service->hasActiveFilters($emptyFilters));
        $this->assertCount(0, $service->getFiltered($emptyFilters));

        $activeFilters = $service->normalizeIndexFilters([
            'search' => ' ahmad ',
            'status' => 'Aktif',
            'kelas_id' => '5',
            'jenjang' => 'sd',
            'jenis_kelamin' => 'l',
        ]);

        $this->assertTrue($service->hasActiveFilters($activeFilters));

        $builder = $service->buildIndexQuery($activeFilters);
        $sql = $builder->toSql();

        $this->assertStringContainsString('nama_lengkap', $sql);
        $this->assertStringContainsString('nisn', $sql);
        $this->assertStringContainsString('akademik_master_kelas', $sql);
        $this->assertStringContainsString('jenjang_kelas', $sql);
        $this->assertStringContainsString('jenis_kelamin', $sql);
        $this->assertSame([
            '%ahmad%',
            '%ahmad%',
            '%ahmad%',
            'Aktif',
            'aktif',
            'AKTIF',
            5,
            'SD',
            'sd',
            'Sd',
            'L',
            'l',
        ], $builder->getBindings());

        $unassignedInactiveBuilder = $service->buildIndexQuery([
            'status' => SiswaService::FILTER_STATUS_INACTIVE,
            'kelas_id' => SiswaService::FILTER_KELAS_UNASSIGNED,
        ]);
        $unassignedSql = $unassignedInactiveBuilder->toSql();

        $this->assertStringContainsString('is null', strtolower($unassignedSql));
        $this->assertStringContainsString('not in', strtolower($unassignedSql));
    }

    public function test_request_rules_cover_required_fields_parent_fks_and_exclude_tarif_spp(): void
    {
        $request = new SiswaRequest;
        $rules = $request->rules();

        $this->assertTrue($request->authorize());
        $this->assertArrayNotHasKey('tarif_spp_id', $rules);
        $this->assertArrayHasKey('nis', $rules);
        $this->assertArrayHasKey('nama_lengkap', $rules);
        $this->assertArrayHasKey('jenis_kelamin', $rules);
        $this->assertArrayHasKey('status', $rules);

        foreach (['ayah', 'ibu', 'wali'] as $peran) {
            $this->assertContains('exists:akademik_pelengkap_jenjang_pendidikan,id', $rules["akademik_pelengkap_jenjang_pendidikan_{$peran}_id"]);
            $this->assertContains('exists:akademik_pelengkap_pekerjaan,id', $rules["akademik_pelengkap_pekerjaan_{$peran}_id"]);
            $this->assertContains('exists:akademik_pelengkap_penghasilan,id', $rules["akademik_pelengkap_penghasilan_{$peran}_id"]);
            $this->assertContains('exists:akademik_pelengkap_berkebutuhan_khusus,id', $rules["akademik_pelengkap_berkebutuhan_khusus_{$peran}_id"]);
        }
    }

    public function test_index_and_show_views_render_preloaded_relations_without_lazy_loading(): void
    {
        $tahunAjaran = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);

        $kelas = Kelas::make([
            'nama_kelas' => '1 Al-Fatihah',
            'jenjang_kelas' => 'SD',
        ]);
        $kelas->setRelation('tahunAjaran', $tahunAjaran);

        $pembimbing = Karyawan::make([
            'nipy' => '19900101-1',
            'nama_lengkap' => 'Budi Santoso',
            'gelar' => 'S.Pd',
        ]);

        $driver = Karyawan::make([
            'nipy' => '19920202-1',
            'nama_lengkap' => 'Agus Supriyadi',
            'gelar' => null,
        ]);

        $ekskul = Ekstrakurikuler::make([
            'nama_ekstrakurikuler' => 'Panahan',
            'unit' => 'SD',
        ]);

        $jemputan = Jemputan::make([
            'karyawan_nipy' => '19920202-1',
            'harga_pp' => 250000,
            'harga_setengah' => 150000,
        ]);
        $jemputan->setRelation('petugas', $driver);

        $khusus = BerkebutuhanKhusus::make(['nama_berkebutuhan_khusus' => 'Tidak Ada']);
        $transportasi = Transportasi::make(['nama_transportasi' => 'Antar Jemput Sekolah']);
        $jenjang = JenjangPendidikan::make(['nama_jenjang_pendidikan' => 'S1']);
        $pekerjaan = Pekerjaan::make(['nama_pekerjaan' => 'Wiraswasta']);
        $penghasilan = Penghasilan::make(['nama_penghasilan' => 'Rp 5.000.000 - Rp 10.000.000']);

        $siswa = Siswa::make([
            'nis' => '202601001',
            'nama_lengkap' => 'Ahmad Fauzi',
            'jenis_kelamin' => 'L',
            'nisn' => '0012345678',
            'tanggal_lahir' => '2018-05-10',
            'nama_ayah' => 'Hendra Wijaya',
            'nama_ibu' => 'Siti Rahma',
            'nama_wali' => 'Pamannya',
            'status' => 'Aktif',
        ]);
        $siswa->setRelation('kelas', $kelas);
        $siswa->setRelation('karyawan', $pembimbing);
        $siswa->setRelation('ekstrakurikuler', $ekskul);
        $siswa->setRelation('jemputan', $jemputan);
        $siswa->setRelation('berkebutuhanKhusus', $khusus);
        $siswa->setRelation('transportasi', $transportasi);
        $siswa->setRelation('jenjangPendidikanAyah', $jenjang);
        $siswa->setRelation('pekerjaanAyah', $pekerjaan);
        $siswa->setRelation('penghasilanAyah', $penghasilan);
        $siswa->setRelation('berkebutuhanKhususAyah', $khusus);
        $siswa->setRelation('jenjangPendidikanIbu', $jenjang);
        $siswa->setRelation('pekerjaanIbu', $pekerjaan);
        $siswa->setRelation('penghasilanIbu', $penghasilan);
        $siswa->setRelation('berkebutuhanKhususIbu', $khusus);
        $siswa->setRelation('jenjangPendidikanWali', null);
        $siswa->setRelation('pekerjaanWali', null);
        $siswa->setRelation('penghasilanWali', null);
        $siswa->setRelation('berkebutuhanKhususWali', null);

        Model::preventLazyLoading(true);

        try {
            $indexHtml = view('admin.dataMaster.siswa.index', [
                'siswa' => new Collection([$siswa]),
            ])->render();

            $unfilteredHtml = view('admin.dataMaster.siswa.index', [
                'siswa' => new Collection,
                'hasActiveFilter' => false,
                'statistics' => [
                    'total' => 25,
                    'aktif' => 20,
                    'lulus' => 2,
                    'pindah' => 1,
                    'nonaktif_keluar' => 2,
                    'laki_laki' => 13,
                    'perempuan' => 12,
                    'aktif_laki_laki' => 11,
                    'aktif_perempuan' => 9,
                    'punya_kelas' => 18,
                    'belum_punya_kelas' => 7,
                    'jenjang_aktif' => ['SD' => 14, 'SMP' => 6],
                ],
                'kelasOptions' => new Collection([$kelas]),
                'jenjangOptions' => ['SD', 'SMP'],
            ])->render();

            $showHtml = view('admin.dataMaster.siswa.show', [
                'siswa' => $siswa,
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('Ahmad Fauzi', $indexHtml);
        $this->assertStringContainsString('202601001', $indexHtml);
        $this->assertStringContainsString('1 Al-Fatihah', $indexHtml);
        $this->assertStringContainsString('Budi Santoso, S.Pd', $indexHtml);
        $this->assertStringContainsString('Panahan', $indexHtml);
        $this->assertStringContainsString('Agus Supriyadi', $indexHtml);
        $this->assertStringContainsString('Total Seluruh Siswa', $indexHtml);

        $this->assertStringContainsString('25', $unfilteredHtml);
        $this->assertStringContainsString('20', $unfilteredHtml);
        $this->assertStringContainsString('Pilih Filter untuk Menampilkan Daftar Siswa', $unfilteredHtml);
        $this->assertStringContainsString('Siswa Aktif per Jenjang', $unfilteredHtml);
        $this->assertStringNotContainsString('id="myTable3"', $unfilteredHtml);

        $this->assertStringContainsString('Ahmad Fauzi', $showHtml);
        $this->assertStringContainsString('Hendra Wijaya', $showHtml);
        $this->assertStringContainsString('Siti Rahma', $showHtml);
        $this->assertStringContainsString('Antar Jemput Sekolah', $showHtml);
        $this->assertStringContainsString('data-siswa-tab="akademik"', $showHtml);
        $this->assertStringContainsString('data-siswa-tab="kontak"', $showHtml);
        $this->assertStringContainsString('data-siswa-tab="keluarga"', $showHtml);
        $this->assertStringContainsString('panel-akademik', $showHtml);
        $this->assertStringContainsString('panel-kontak', $showHtml);
        $this->assertStringContainsString('panel-keluarga', $showHtml);
        $this->assertStringContainsString(route('siswa.index'), view('layouts.sidebar')->render());
    }

    public function test_index_view_does_not_render_colspan_row_when_empty_for_datatables_compatibility(): void
    {
        $html = view('admin.dataMaster.siswa.index', [
            'siswa' => new Collection,
        ])->render();

        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($document);
        $headerCells = $xpath->query('//table[@id="myTable3"]/thead/tr/th');
        $bodyRows = $xpath->query('//table[@id="myTable3"]/tbody/tr');
        $colspanCells = $xpath->query('//table[@id="myTable3"]/tbody//*[@colspan]');

        $this->assertNotFalse($headerCells);
        $this->assertNotFalse($bodyRows);
        $this->assertNotFalse($colspanCells);
        $this->assertSame(7, $headerCells->length);
        $this->assertSame(0, $bodyRows->length);
        $this->assertSame(0, $colspanCells->length);
    }

    public function test_create_and_edit_views_render_expected_form_fields_and_actions(): void
    {
        view()->share('errors', new ViewErrorBag);

        $tahunAjaran = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);

        $kelas = Kelas::make([
            'nama_kelas' => '1 Al-Fatihah',
            'jenjang_kelas' => 'SD',
        ]);
        $kelas->id = 1;
        $kelas->setRelation('tahunAjaran', $tahunAjaran);

        $karyawan = Karyawan::make([
            'nipy' => '19900101-1',
            'nama_lengkap' => 'Budi Santoso',
            'gelar' => 'S.Pd',
        ]);

        $ekskul = Ekstrakurikuler::make([
            'nama_ekstrakurikuler' => 'Panahan',
            'unit' => 'SD',
        ]);
        $ekskul->id = 2;

        $jemputan = Jemputan::make([
            'karyawan_nipy' => '19900101-1',
            'harga_pp' => 250000,
            'harga_setengah' => 150000,
        ]);
        $jemputan->id = 3;
        $jemputan->setRelation('petugas', $karyawan);

        $options = [
            'kelas' => new Collection([$kelas]),
            'karyawan' => new Collection([$karyawan]),
            'ekstrakurikuler' => new Collection([$ekskul]),
            'jemputan' => new Collection([$jemputan]),
            'berkebutuhanKhusus' => new Collection,
            'transportasi' => new Collection,
            'jenjangPendidikan' => new Collection,
            'pekerjaan' => new Collection,
            'penghasilan' => new Collection,
        ];

        Model::preventLazyLoading(true);

        try {
            $createHtml = view('admin.dataMaster.siswa.create', $options)->render();

            $siswa = Siswa::make([
                'nis' => '202601001',
                'nama_lengkap' => 'Ahmad Fauzi',
                'jenis_kelamin' => 'L',
                'tanggal_lahir' => '2018-05-10',
                'status' => 'Aktif',
            ]);

            $editHtml = view('admin.dataMaster.siswa.edit', [
                'siswa' => $siswa,
                ...$options,
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString(route('siswa.store'), $createHtml);
        $this->assertStringContainsString('enctype="multipart/form-data"', $createHtml);
        $this->assertStringContainsString('1 Al-Fatihah (SD)', $createHtml);
        $this->assertStringNotContainsString('tarif_spp_id', $createHtml);

        $this->assertStringContainsString(route('siswa.update', '202601001'), $editHtml);
        $this->assertStringContainsString('name="_method" value="PUT"', $editHtml);
        $this->assertStringContainsString('2018-05-10', $editHtml);
        $this->assertStringContainsString('Ahmad Fauzi', $editHtml);
    }
}
