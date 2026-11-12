<?php

namespace Tests\Unit;

use App\Models\Admin\DataMaster\AnggotaEkstrakurikuler;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Services\Admin\DataMaster\EkstrakurikulerService;
use DOMDocument;
use DOMXPath;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class EkstrakurikulerModelAndViewTest extends TestCase
{
    #[TestWith([0, 'Gratis'])]
    #[TestWith([-500, 'Gratis'])]
    #[TestWith([50000, 'Rp 50.000'])]
    #[TestWith([1250000, 'Rp 1.250.000'])]
    public function test_biaya_formatted_formats_zero_and_positive_amounts(int $biaya, string $expected): void
    {
        $ekstrakurikuler = Ekstrakurikuler::make(['biaya' => $biaya]);

        $this->assertSame($expected, $ekstrakurikuler->biaya_formatted);
    }

    #[TestWith(['SD', 'badge-success'])]
    #[TestWith(['smp', 'badge-primary'])]
    #[TestWith(['TK', 'badge-warning text-dark'])]
    #[TestWith(['PAUD', 'badge-warning text-dark'])]
    #[TestWith(['SMA', 'badge-info'])]
    #[TestWith(['SMK', 'badge-info'])]
    #[TestWith(['Yayasan', 'badge-secondary'])]
    public function test_unit_badge_class_maps_unit_names(string $unit, string $expectedClass): void
    {
        $ekstrakurikuler = Ekstrakurikuler::make(['unit' => $unit]);

        $this->assertSame($expectedClass, $ekstrakurikuler->unit_badge_class);
    }

    public function test_index_view_renders_preloaded_relations_without_lazy_loading(): void
    {
        $tahunAjaran = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);

        $pembina = Karyawan::make([
            'nipy' => '19900101-1',
            'nama_lengkap' => 'Budi Santoso',
            'gelar' => 'S.Pd',
        ]);

        $paid = Ekstrakurikuler::make([
            'nama_ekstrakurikuler' => 'Panahan',
            'unit' => 'SD',
            'biaya' => 75000,
        ]);
        $paid->id = 1;
        $paid->setAttribute('anggota_ekstrakurikuler_count', 12);
        $paid->setRelation('tahunAjaran', $tahunAjaran);
        $paid->setRelation('pembina', $pembina);

        $free = Ekstrakurikuler::make([
            'nama_ekstrakurikuler' => 'Pramuka',
            'unit' => 'SMP',
            'biaya' => 0,
        ]);
        $free->id = 2;
        $free->setAttribute('anggota_ekstrakurikuler_count', 0);
        $free->setRelation('tahunAjaran', $tahunAjaran);
        $free->setRelation('pembina', null);

        Model::preventLazyLoading(true);

        try {
            $rendered = view('admin.dataMaster.ekstrakurikuler.index', [
                'ekstrakurikuler' => new Collection([$paid, $free]),
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('Panahan', $rendered);
        $this->assertStringContainsString('Budi Santoso, S.Pd', $rendered);
        $this->assertStringContainsString('Rp 75.000', $rendered);
        $this->assertStringContainsString('12 Siswa', $rendered);
        $this->assertStringContainsString('Pramuka', $rendered);
        $this->assertStringContainsString('Gratis', $rendered);
        $this->assertStringContainsString('0 Siswa', $rendered);
        $this->assertStringContainsString('Pembina belum ditentukan', $rendered);
        $this->assertStringContainsString(route('ekstrakurikuler.show', 1), $rendered);
        $this->assertStringContainsString(route('ekstrakurikuler.index'), view('layouts.sidebar')->render());
    }

    public function test_index_view_does_not_render_colspan_row_when_empty_for_datatables_compatibility(): void
    {
        $rendered = view('admin.dataMaster.ekstrakurikuler.index', [
            'ekstrakurikuler' => new Collection,
        ])->render();

        $this->assertStringNotContainsString('colspan="8"', $rendered);
        $this->assertStringContainsString('Belum ada data tersedia', view('layouts.appui')->render());
    }

    public function test_create_and_edit_views_render_expected_form_fields_and_actions(): void
    {
        view()->share('errors', new ViewErrorBag);

        $tahunAjaran = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);
        $tahunAjaran->id = 10;

        $pembina = Karyawan::make([
            'nipy' => '19900101-1',
            'nama_lengkap' => 'Siti Aminah',
            'gelar' => 'M.Pd',
        ]);

        $createHtml = view('admin.dataMaster.ekstrakurikuler.create', [
            'tahunAjaran' => new Collection([$tahunAjaran]),
            'karyawan' => new Collection([$pembina]),
        ])->render();

        $this->assertStringContainsString(route('ekstrakurikuler.store'), $createHtml);
        $this->assertStringContainsString('Siti Aminah, M.Pd (19900101-1)', $createHtml);

        $ekstrakurikuler = Ekstrakurikuler::make([
            'akademik_master_tahun_ajaran_id' => 10,
            'karyawan_nipy' => '19900101-1',
            'nama_ekstrakurikuler' => 'Robotik',
            'biaya' => 120000,
            'unit' => 'SMP',
        ]);
        $ekstrakurikuler->id = 5;

        $editHtml = view('admin.dataMaster.ekstrakurikuler.edit', [
            'ekstrakurikuler' => $ekstrakurikuler,
            'tahunAjaran' => new Collection([$tahunAjaran]),
            'karyawan' => new Collection([$pembina]),
        ])->render();

        $this->assertStringContainsString(route('ekstrakurikuler.update', 5), $editHtml);
        $this->assertStringContainsString('name="_method" value="PUT"', $editHtml);
        $this->assertStringContainsString('Robotik', $editHtml);
        $this->assertStringContainsString('120000', $editHtml);
    }

    public function test_service_index_query_configures_member_count_and_eager_loads(): void
    {
        $query = app(EkstrakurikulerService::class)->buildIndexQuery();

        $this->assertStringContainsString('akademik_master_anggota_ekstrakuler', $query->toSql());
        $this->assertArrayHasKey('tahunAjaran', $query->getEagerLoads());
        $this->assertArrayHasKey('pembina', $query->getEagerLoads());
    }

    public function test_show_view_renders_members_and_modal_picker_without_lazy_loading(): void
    {
        view()->share('errors', new ViewErrorBag);

        $tahunAjaran = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);
        $tahunAjaran->id = 10;

        $pembina = Karyawan::make([
            'nipy' => '19900101-1',
            'nama_lengkap' => 'Budi Santoso',
            'gelar' => 'S.Pd',
        ]);

        $ekstrakurikuler = Ekstrakurikuler::make([
            'akademik_master_tahun_ajaran_id' => 10,
            'karyawan_nipy' => '19900101-1',
            'nama_ekstrakurikuler' => 'Robotik',
            'biaya' => 120000,
            'unit' => 'SMP',
        ]);
        $ekstrakurikuler->id = 5;
        $ekstrakurikuler->setRelation('tahunAjaran', $tahunAjaran);
        $ekstrakurikuler->setRelation('pembina', $pembina);

        $kelasMember = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 10,
            'nama_kelas' => '7 Al-Khawarizmi',
            'jenjang_kelas' => 'SMP',
            'tingkatan_kelas' => '7',
        ]);
        $kelasMember->id = 21;

        $memberSiswa = Siswa::make([
            'nis' => '20260011',
            'nisn' => '0011223344',
            'nama_lengkap' => 'Aisyah Putri',
            'jenis_kelamin' => 'P',
            'status' => 'Aktif',
        ]);
        $memberAk = AnggotaKelas::make([
            'siswa_nis' => '20260011',
            'akademik_master_kelas_id' => 21,
            'pendaftaran' => 'Siswa Baru',
        ]);
        $memberAk->id = 70;
        $memberAk->setRelation('siswa', $memberSiswa);
        $memberAk->setRelation('kelas', $kelasMember);

        $member = AnggotaEkstrakurikuler::make([
            'anggota_kelas_id' => 70,
            'akademik_master_ekstrakuler_id' => 5,
            'pendaftaran' => 'Baru',
            'keterangan' => 'Regu Inti',
        ]);
        $member->id = 55;
        $member->setRelation('anggotaKelas', $memberAk);
        $member->setRelation('ekstrakurikuler', $ekstrakurikuler);

        $candidateSiswa = Siswa::make([
            'nis' => '20260022',
            'nisn' => '0099887766',
            'nama_lengkap' => 'Bagas Pratama',
            'jenis_kelamin' => 'L',
            'status' => 'Aktif',
        ]);
        $candidateSiswa->setRelation('anggotaEkstrakurikuler', new Collection);

        $candidateAk = AnggotaKelas::make([
            'siswa_nis' => '20260022',
            'akademik_master_kelas_id' => 21,
            'pendaftaran' => 'Naik Kelas',
        ]);
        $candidateAk->id = 71;
        $candidateAk->setRelation('siswa', $candidateSiswa);
        $candidateAk->setRelation('kelas', $kelasMember);

        $payload = [
            'ekstrakurikuler' => $ekstrakurikuler,
            'anggotaEkstrakurikuler' => new Collection([$member]),
            'stats' => [
                'total' => 1,
                'laki_laki' => 0,
                'perempuan' => 1,
            ],
            'kandidatAnggotaKelas' => new Collection([$candidateAk]),
            'excludedTahunAjaranIds' => [10],
            'hiddenRegisteredCount' => 2,
            'hiddenInactiveCount' => 1,
            'hiddenWithoutKelasCount' => 3,
        ];

        Model::preventLazyLoading(true);

        try {
            $html = view('admin.dataMaster.ekstrakurikuler.show', $payload)->render();

            $errorBag = new ViewErrorBag;
            $errorBag->put('default', new MessageBag(['anggota_kelas_id' => 'Pilih minimal satu siswa anggota kelas.']));
            view()->share('errors', $errorBag);

            $htmlWithErrors = view('admin.dataMaster.ekstrakurikuler.show', $payload)->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('Robotik', $html);
        $this->assertStringContainsString('Budi Santoso, S.Pd', $html);
        $this->assertStringContainsString('Aisyah Putri', $html);
        $this->assertStringContainsString('7 Al-Khawarizmi', $html);
        $this->assertStringContainsString('Regu Inti', $html);
        $this->assertStringContainsString('Bagas Pratama', $html);
        $this->assertStringContainsString('name="anggota_kelas_id[]" value="71"', $html);
        $this->assertStringContainsString(route('anggota-ekstrakurikuler.store'), $html);
        $this->assertStringContainsString('name="redirect_to_ekstrakurikuler" value="5"', $html);
        $this->assertStringContainsString(route('anggota-ekstrakurikuler.edit', ['anggota_ekstrakurikuler' => 55, 'from_ekstrakurikuler' => 5]), $html);
        $this->assertStringContainsString(route('anggota-ekstrakurikuler.destroy', 55), $html);
        $this->assertStringContainsString('id="openTambahAnggotaModalBtn"', $html);
        $this->assertStringContainsString('id="tambahAnggotaModal"', $html);
        $this->assertStringNotContainsString('ak-modal-overlay is-open', $html);
        $this->assertStringNotContainsString('id="filterKelasSebelumnya"', $html);
        $this->assertStringNotContainsString('<select name="akademik_master_ekstrakuler_id"', $html);

        $this->assertStringContainsString('ak-modal-overlay is-open', $htmlWithErrors);
        $this->assertStringContainsString('Pilih minimal satu siswa anggota kelas.', $htmlWithErrors);
    }

    public function test_show_view_does_not_render_colspan_row_when_empty_for_datatables_compatibility(): void
    {
        view()->share('errors', new ViewErrorBag);

        $ekstrakurikuler = Ekstrakurikuler::make([
            'akademik_master_tahun_ajaran_id' => 10,
            'nama_ekstrakurikuler' => 'Robotik',
            'biaya' => 0,
            'unit' => 'SMP',
        ]);
        $ekstrakurikuler->id = 5;
        $ekstrakurikuler->setRelation('tahunAjaran', null);
        $ekstrakurikuler->setRelation('pembina', null);

        $html = view('admin.dataMaster.ekstrakurikuler.show', [
            'ekstrakurikuler' => $ekstrakurikuler,
            'anggotaEkstrakurikuler' => new Collection,
            'stats' => [
                'total' => 0,
                'laki_laki' => 0,
                'perempuan' => 0,
            ],
            'kandidatAnggotaKelas' => new Collection,
            'excludedTahunAjaranIds' => [10],
            'hiddenRegisteredCount' => 0,
            'hiddenInactiveCount' => 0,
            'hiddenWithoutKelasCount' => 0,
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
        $this->assertSame(8, $headerCells->length);
        $this->assertSame(0, $bodyRows->length);
        $this->assertSame(0, $colspanCells->length);
    }
}
