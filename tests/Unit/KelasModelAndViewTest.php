<?php

namespace Tests\Unit;

use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Services\Admin\DataMaster\KelasService;
use DOMDocument;
use DOMXPath;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
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
        $this->assertStringContainsString(route('kelas.show', 21), $html);
        $this->assertStringContainsString(route('kelas.show', 22), $html);
        $this->assertStringNotContainsString(route('anggota-kelas.index'), $html);
        $this->assertStringNotContainsString(route('anggota-kelas.create'), $html);
    }

    public function test_show_view_renders_members_and_direct_add_picker_without_lazy_loading_or_filter_select(): void
    {
        view()->share('errors', new ViewErrorBag);

        $taLama = TahunAjaran::make([
            'nama_tahun_ajaran' => '2025/2026',
            'semester' => '2',
            'is_active' => false,
        ]);
        $taLama->id = 9;

        $taAktif = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);
        $taAktif->id = 10;

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

        $kelas = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 10,
            'karyawan_nipy' => '19900101-1',
            'pendamping_nipy' => '19920202-1',
            'tingkatan_kelas' => '2',
            'nama_kelas' => '2 Al-Fatihah',
            'jenjang_kelas' => 'SD',
            'romawi' => 'II',
        ]);
        $kelas->id = 21;
        $kelas->setRelation('tahunAjaran', $taAktif);
        $kelas->setRelation('waliKelas', $waliKelas);
        $kelas->setRelation('pendamping', $pendamping);

        $kelasLama = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 9,
            'tingkatan_kelas' => '1',
            'nama_kelas' => '1 Abu Bakar',
            'jenjang_kelas' => 'SD',
            'romawi' => 'I',
        ]);
        $kelasLama->id = 19;
        $kelasLama->setRelation('tahunAjaran', $taLama);

        $siswaAnggota = Siswa::make([
            'nis' => '20260001',
            'nama_lengkap' => 'Aisyah Putri',
            'jenis_kelamin' => 'P',
            'nisn' => '0012345670',
            'status' => 'Aktif',
        ]);

        $anggota = AnggotaKelas::make([
            'siswa_nis' => '20260001',
            'akademik_master_kelas_id' => 21,
            'pendaftaran' => 'Siswa Baru',
        ]);
        $anggota->id = 55;
        $anggota->setRelation('siswa', $siswaAnggota);
        $anggota->setRelation('kelas', $kelas);

        $riwayatLama = AnggotaKelas::make([
            'siswa_nis' => '20260002',
            'akademik_master_kelas_id' => 19,
            'pendaftaran' => 'Siswa Baru',
        ]);
        $riwayatLama->id = 5;
        $riwayatLama->setRelation('kelas', $kelasLama);

        $kandidat = Siswa::make([
            'nis' => '20260002',
            'nama_lengkap' => 'Ahmad Fauzi',
            'jenis_kelamin' => 'L',
            'nisn' => '0012345678',
            'status' => 'Aktif',
        ]);
        $kandidat->setRelation('anggotaKelas', new Collection([$riwayatLama]));
        $kandidat->setRelation('kelas', null);

        Model::preventLazyLoading(true);

        try {
            $html = view('admin.dataMaster.kelas.show', [
                'kelas' => $kelas,
                'anggotaKelas' => new Collection([$anggota]),
                'stats' => [
                    'total' => 1,
                    'laki_laki' => 0,
                    'perempuan' => 1,
                ],
                'kandidatSiswa' => new Collection([$kandidat]),
                'excludedTahunAjaranIds' => [10],
                'hiddenRegisteredCount' => 1,
                'hiddenInactiveCount' => 2,
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('2 Al-Fatihah', $html);
        $this->assertStringContainsString('Budi Santoso, S.Pd', $html);
        $this->assertStringContainsString('Aisyah Putri', $html);
        $this->assertStringContainsString('Ahmad Fauzi', $html);
        $this->assertStringContainsString('1 Abu Bakar', $html);
        $this->assertStringContainsString(route('anggota-kelas.store'), $html);
        $this->assertStringContainsString('name="redirect_to_kelas" value="21"', $html);
        $this->assertStringContainsString(route('anggota-kelas.edit', ['anggota_kelas' => 55, 'from_kelas' => 21]), $html);
        $this->assertStringContainsString(route('anggota-kelas.destroy', 55), $html);
        $this->assertStringContainsString('id="openTambahAnggotaModalBtn"', $html);
        $this->assertStringContainsString('id="tambahAnggotaModal"', $html);
        $this->assertStringNotContainsString('ak-modal-overlay is-open', $html);
        $this->assertStringNotContainsString('id="filterKelasSebelumnya"', $html);
        $this->assertStringNotContainsString('<select name="akademik_master_kelas_id"', $html);

        $errorBag = new ViewErrorBag;
        $errorBag->put('default', new MessageBag(['siswa_nis' => ['Pilih minimal satu siswa.']]));
        view()->share('errors', $errorBag);

        Model::preventLazyLoading(true);

        try {
            $htmlWithErrors = view('admin.dataMaster.kelas.show', [
                'kelas' => $kelas,
                'anggotaKelas' => new Collection([$anggota]),
                'stats' => [
                    'total' => 1,
                    'laki_laki' => 0,
                    'perempuan' => 1,
                ],
                'kandidatSiswa' => new Collection([$kandidat]),
                'excludedTahunAjaranIds' => [10],
                'hiddenRegisteredCount' => 1,
                'hiddenInactiveCount' => 2,
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('ak-modal-overlay is-open', $htmlWithErrors);
        $this->assertStringContainsString('Pilih minimal satu siswa.', $htmlWithErrors);
    }

    public function test_show_view_does_not_render_colspan_row_when_empty_for_datatables_compatibility(): void
    {
        view()->share('errors', new ViewErrorBag);

        $kelas = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 10,
            'tingkatan_kelas' => '1',
            'nama_kelas' => '1 Kosong',
            'jenjang_kelas' => 'SD',
            'romawi' => 'I',
        ]);
        $kelas->id = 99;
        $kelas->setRelation('tahunAjaran', null);
        $kelas->setRelation('waliKelas', null);
        $kelas->setRelation('pendamping', null);

        $html = view('admin.dataMaster.kelas.show', [
            'kelas' => $kelas,
            'anggotaKelas' => new Collection,
            'stats' => [
                'total' => 0,
                'laki_laki' => 0,
                'perempuan' => 0,
            ],
            'kandidatSiswa' => new Collection,
            'excludedTahunAjaranIds' => [],
            'hiddenRegisteredCount' => 0,
            'hiddenInactiveCount' => 0,
        ])->render();

        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($document);
        $headerCells = $xpath->query('//table[@id="myTable3"]/thead/tr/th');
        $bodyRows = $xpath->query('//table[@id="myTable3"]/tbody/tr');
        $colspanCells = $xpath->query('//table[@id="myTable3"]/tbody//*[@colspan]');

        $this->assertSame(6, $headerCells->length);
        $this->assertSame(0, $bodyRows->length);
        $this->assertSame(0, $colspanCells->length);
    }

    public function test_anggota_kelas_edit_view_preserves_redirect_to_kelas_show_without_lazy_loading(): void
    {
        view()->share('errors', new ViewErrorBag);

        $taAktif = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);
        $taAktif->id = 10;

        $kelas = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 10,
            'tingkatan_kelas' => '2',
            'nama_kelas' => '2 Al-Fatihah',
            'jenjang_kelas' => 'SD',
            'romawi' => 'II',
        ]);
        $kelas->id = 21;
        $kelas->setRelation('tahunAjaran', $taAktif);

        $siswa = Siswa::make([
            'nis' => '20260001',
            'nama_lengkap' => 'Aisyah Putri',
            'jenis_kelamin' => 'P',
            'nisn' => '0012345670',
            'status' => 'Aktif',
        ]);
        $siswa->setRelation('anggotaKelas', new Collection);
        $siswa->setRelation('kelas', null);

        $anggotaKelas = AnggotaKelas::make([
            'siswa_nis' => '20260001',
            'akademik_master_kelas_id' => 21,
            'pendaftaran' => 'Siswa Baru',
        ]);
        $anggotaKelas->id = 55;

        Model::preventLazyLoading(true);

        try {
            $html = view('admin.dataMaster.anggotaKelas.edit', [
                'anggotaKelas' => $anggotaKelas,
                'fromKelasId' => 21,
                'kelas' => new Collection([$kelas]),
                'siswa' => new Collection([$siswa]),
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString(route('kelas.show', 21), $html);
        $this->assertStringContainsString('name="redirect_to_kelas" value="21"', $html);
        $this->assertStringContainsString('Aisyah Putri', $html);
    }

    public function test_jenjang_order_and_badge_class_and_filter_resolution_work_correctly(): void
    {
        $jenjang = ['SMP', 'TK', 'SMA', 'PAUD', 'SD'];
        usort($jenjang, static fn (string $a, string $b): int => Kelas::compareJenjangOrder($a, $b));

        $this->assertSame(['PAUD', 'TK', 'SD', 'SMP', 'SMA'], $jenjang);
        $this->assertSame('badge-success', Kelas::make(['jenjang_kelas' => 'sd'])->jenjang_badge_class);
        $this->assertSame('badge-primary', Kelas::make(['jenjang_kelas' => 'SMP'])->jenjang_badge_class);
        $this->assertSame('badge-warning text-dark', Kelas::make(['jenjang_kelas' => 'TK'])->jenjang_badge_class);

        $activeTa = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);
        $activeTa->id = 10;

        $service = new KelasService;

        $this->assertSame([
            'tahun_ajaran_id' => '10',
            'jenjang' => '',
        ], $service->resolveIndexFilters([], $activeTa));

        $this->assertSame([
            'tahun_ajaran_id' => '10',
            'jenjang' => 'SD',
        ], $service->resolveIndexFilters(['jenjang' => 'sd'], $activeTa));

        $this->assertSame([
            'tahun_ajaran_id' => 'all',
            'jenjang' => 'SMP',
        ], $service->resolveIndexFilters(['tahun_ajaran_id' => 'all', 'jenjang' => 'smp'], $activeTa));

        $this->assertSame([
            'tahun_ajaran_id' => 'all',
            'jenjang' => '',
        ], $service->resolveIndexFilters([], null));

        $filteredSql = $service->buildIndexQuery([
            'tahun_ajaran_id' => '10',
            'jenjang' => 'SD',
        ])->toSql();

        $this->assertStringContainsString('akademik_master_tahun_ajaran_id', $filteredSql);
        $this->assertStringContainsString('jenjang_kelas', $filteredSql);
    }

    public function test_index_view_renders_jenjang_summary_cards_and_active_tahun_ajaran_filter(): void
    {
        $taAktif = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);
        $taAktif->id = 10;

        $taLama = TahunAjaran::make([
            'nama_tahun_ajaran' => '2025/2026',
            'semester' => '2',
            'is_active' => false,
        ]);
        $taLama->id = 9;

        $waliKelas = Karyawan::make([
            'nipy' => '19900101-1',
            'nama_lengkap' => 'Budi Santoso',
            'gelar' => 'S.Pd',
        ]);

        $kelasSd = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 10,
            'karyawan_nipy' => '19900101-1',
            'tingkatan_kelas' => '1',
            'nama_kelas' => '1 Al-Fatihah',
            'jenjang_kelas' => 'SD',
            'romawi' => 'I',
        ]);
        $kelasSd->id = 21;
        $kelasSd->setAttribute('anggota_kelas_count', 24);
        $kelasSd->setRelation('tahunAjaran', $taAktif);
        $kelasSd->setRelation('waliKelas', $waliKelas);
        $kelasSd->setRelation('pendamping', null);

        Model::preventLazyLoading(true);

        try {
            $html = view('admin.dataMaster.kelas.index', [
                'kelas' => new Collection([$kelasSd]),
                'statistics' => [
                    'total_kelas' => 3,
                    'total_siswa' => 60,
                    'per_jenjang' => [
                        'TK' => ['kelas' => 1, 'siswa' => 16],
                        'SD' => ['kelas' => 2, 'siswa' => 44],
                    ],
                ],
                'filters' => [
                    'tahun_ajaran_id' => '10',
                    'jenjang' => 'SD',
                ],
                'tahunAjaranOptions' => new Collection([$taAktif, $taLama]),
                'activeTahunAjaran' => $taAktif,
                'jenjangOptions' => ['TK', 'SD'],
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('Semua Jenjang', $html);
        $this->assertStringContainsString('3 Kelas', $html);
        $this->assertStringContainsString('Jenjang TK', $html);
        $this->assertStringContainsString('16 Siswa', $html);
        $this->assertStringContainsString('Jenjang SD', $html);
        $this->assertStringContainsString('2 Kelas', $html);
        $this->assertStringContainsString('44 Siswa', $html);
        $this->assertStringContainsString('name="tahun_ajaran_id"', $html);
        $this->assertStringContainsString('name="jenjang"', $html);
        $this->assertStringContainsString('2026/2027 - Semester 1 (Aktif)', $html);
        $this->assertStringContainsString('Tahun Ajaran Aktif (2026/2027 Smt 1 (Aktif))', $html);
    }
}
