<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\Prestasi\PrestasiSiswaController;
use App\Http\Requests\Admin\Prestasi\PrestasiSiswaRequest;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Prestasi\PrestasiSiswa;
use App\Services\Admin\Prestasi\PrestasiSiswaService;
use App\Support\Sqid;
use DOMDocument;
use DOMXPath;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;
use ReflectionMethod;
use Tests\TestCase;

class PrestasiSiswaModelAndViewTest extends TestCase
{
    public function test_model_metadata_fillable_casts_accessors_and_many_to_many_relations_are_configured(): void
    {
        $prestasi = new PrestasiSiswa;

        $this->assertSame('akademik_prestasi_siswa', $prestasi->getTable());
        $this->assertSame([
            'akademik_master_tahun_ajaran_id',
            'nama_prestasi',
            'kategori',
            'tingkat',
            'peringkat',
            'penyelenggara',
            'tanggal',
            'keterangan',
            'foto',
        ], $prestasi->getFillable());

        $casts = $prestasi->getCasts();
        $this->assertSame('integer', $casts['akademik_master_tahun_ajaran_id']);
        $this->assertSame('date', $casts['tanggal']);

        $siswaRel = $prestasi->siswa();
        $this->assertInstanceOf(BelongsToMany::class, $siswaRel);
        $this->assertSame('akademik_prestasi_siswa_anggota', $siswaRel->getTable());
        $this->assertSame('prestasi_siswa_id', $siswaRel->getForeignPivotKeyName());
        $this->assertSame('siswa_nis', $siswaRel->getRelatedPivotKeyName());
        $this->assertSame('id', $siswaRel->getParentKeyName());
        $this->assertSame('nis', $siswaRel->getRelatedKeyName());

        $this->assertSame('akademik_master_tahun_ajaran_id', $prestasi->tahunAjaran()->getForeignKeyName());
        $this->assertSame('id', $prestasi->tahunAjaran()->getOwnerKeyName());

        $siswa = new Siswa;
        $prestasiRel = $siswa->prestasi();
        $this->assertInstanceOf(BelongsToMany::class, $prestasiRel);
        $this->assertSame('akademik_prestasi_siswa_anggota', $prestasiRel->getTable());
        $this->assertSame('siswa_nis', $prestasiRel->getForeignPivotKeyName());
        $this->assertSame('prestasi_siswa_id', $prestasiRel->getRelatedPivotKeyName());

        $this->assertContains(
            'prestasi:id,akademik_master_tahun_ajaran_id,nama_prestasi,kategori,tingkat,peringkat,penyelenggara,tanggal,keterangan,foto',
            Siswa::DETAIL_RELATIONS
        );
        $this->assertContains(
            'prestasi.siswa:nis,akademik_master_kelas_id,nama_lengkap,avatar',
            Siswa::DETAIL_RELATIONS
        );

        $siswaSatu = Siswa::make(['nis' => '202601001', 'nama_lengkap' => 'Ahmad Fauzi']);
        $siswaDua = Siswa::make(['nis' => '202601002', 'nama_lengkap' => 'Zaid Al-Faruq']);

        $sample = PrestasiSiswa::make([
            'nama_prestasi' => 'Turnamen Futsal Antar SD',
            'kategori' => PrestasiSiswa::KATEGORI_NON_AKADEMIK,
            'tingkat' => 'Nasional',
            'peringkat' => 'Juara 1',
            'penyelenggara' => 'Kemenpora RI',
            'tanggal' => '2026-11-20',
            'keterangan' => "Tim Futsal Garuda SD.\nBabak final menang 3-1.",
            'foto' => 'prestasi/foto/piagam-futsal.jpg',
        ]);
        $sample->setRelation('siswa', new Collection([$siswaSatu, $siswaDua]));

        $this->assertTrue($sample->is_beregu);
        $this->assertSame('Beregu (2 Siswa)', $sample->jenis_peserta_label);
        $this->assertSame('Ahmad Fauzi, Zaid Al-Faruq', $sample->daftar_siswa_ringkas);
        $this->assertSame('Non-Akademik', $sample->kategori_label);
        $this->assertSame('badge-success', $sample->kategori_badge_class);
        $this->assertSame('badge-warning text-dark', $sample->tingkat_badge_class);
        $this->assertStringContainsString('2026', $sample->tanggal_formatted);
        $this->assertStringContainsString('Tim Futsal Garuda SD.', $sample->ringkasan_keterangan);
        $this->assertNotNull($sample->foto_url);
        $this->assertStringContainsString('prestasi/foto/piagam-futsal.jpg', (string) $sample->foto_url);

        $individu = PrestasiSiswa::make(['nama_prestasi' => 'OSN Matematika']);
        $individu->setRelation('siswa', new Collection([$siswaSatu]));
        $this->assertFalse($individu->is_beregu);
        $this->assertSame('Individu', $individu->jenis_peserta_label);
    }

    public function test_service_normalizes_filters_summarizes_beregu_and_builds_query_without_n_plus_one(): void
    {
        $service = new PrestasiSiswaService;
        $siswaSqid = Sqid::encodeFor(Siswa::class, '202601001');
        $taSqid = Sqid::encodeFor(TahunAjaran::class, 7);

        $filters = $service->normalizeFilters([
            'tahun_ajaran_id' => $taSqid,
            'kategori' => 'AKADEMIK',
            'tingkat' => 'Provinsi',
            'siswa_nis' => $siswaSqid,
            'search' => '  Olimpiade  ',
        ]);

        $this->assertSame('7', $filters['tahun_ajaran_id']);
        $this->assertSame('akademik', $filters['kategori']);
        $this->assertSame('Provinsi', $filters['tingkat']);
        $this->assertSame('202601001', $filters['siswa_nis']);
        $this->assertSame('Olimpiade', $filters['search']);
        $this->assertTrue($service->hasActiveFilters($filters));

        $builder = $service->buildIndexQuery($filters);
        $query = $builder->getQuery();

        $this->assertSame([
            'id',
            'akademik_master_tahun_ajaran_id',
            'nama_prestasi',
            'kategori',
            'tingkat',
            'peringkat',
            'penyelenggara',
            'tanggal',
            'keterangan',
            'foto',
            'created_at',
        ], $query->columns);

        $eagerLoads = $builder->getEagerLoads();
        $this->assertArrayHasKey('siswa', $eagerLoads);
        $this->assertArrayHasKey('siswa.kelas', $eagerLoads);
        $this->assertArrayHasKey('tahunAjaran', $eagerLoads);

        $sql = strtolower($builder->toSql());
        $this->assertStringContainsString('akademik_master_tahun_ajaran_id', $sql);
        $this->assertStringContainsString('kategori', $sql);
        $this->assertStringContainsString('tingkat', $sql);
        $this->assertStringContainsString('akademik_prestasi_siswa_anggota', $sql);
        $this->assertStringContainsString('nama_prestasi', $sql);

        $s1 = Siswa::make(['nis' => '202601001', 'nama_lengkap' => 'Aisyah']);
        $s2 = Siswa::make(['nis' => '202601002', 'nama_lengkap' => 'Zaid']);

        $p1 = PrestasiSiswa::make(['kategori' => PrestasiSiswa::KATEGORI_AKADEMIK, 'foto' => 'prestasi/foto/a.jpg']);
        $p1->setRelation('siswa', new Collection([$s1]));
        $p2 = PrestasiSiswa::make(['kategori' => PrestasiSiswa::KATEGORI_NON_AKADEMIK, 'foto' => null]);
        $p2->setRelation('siswa', new Collection([$s1, $s2]));

        $stats = $service->summarizeCollection(new Collection([$p1, $p2]));
        $this->assertSame([
            'total' => 2,
            'akademik' => 1,
            'non_akademik' => 1,
            'beregu' => 1,
            'dengan_foto' => 1,
        ], $stats);
    }

    public function test_request_prepares_multi_siswa_inputs_decodes_sqids_and_validates_fields(): void
    {
        $siswaSqid1 = Sqid::encodeFor(Siswa::class, '202601009');
        $taSqid = Sqid::encodeFor(TahunAjaran::class, 4);

        $request = PrestasiSiswaRequest::create('/prestasi/prestasi-siswa', 'POST', [
            'siswa_nis' => ['  '.$siswaSqid1.'  ', '202601010', '202601009', '  '],
            'akademik_master_tahun_ajaran_id' => $taSqid,
            'nama_prestasi' => '  Lomba   Cerdas   Cermat   SD  ',
            'kategori' => '  AKADEMIK ',
            'tingkat' => '  Kabupaten/Kota ',
            'peringkat' => '  Juara   1 ',
            'penyelenggara' => '  Dinas   Pendidikan ',
            'tanggal' => ' 2026-11-24 ',
            'keterangan' => '   ',
        ]);

        $prepareMethod = new ReflectionMethod(PrestasiSiswaRequest::class, 'prepareForValidation');
        $prepareMethod->invoke($request);

        $this->assertSame(['202601009', '202601010'], $request->input('siswa_nis'));
        $this->assertSame(4, $request->input('akademik_master_tahun_ajaran_id'));
        $this->assertSame('Lomba Cerdas Cermat SD', $request->input('nama_prestasi'));
        $this->assertSame('akademik', $request->input('kategori'));
        $this->assertSame('Kabupaten/Kota', $request->input('tingkat'));
        $this->assertSame('Juara 1', $request->input('peringkat'));
        $this->assertSame('Dinas Pendidikan', $request->input('penyelenggara'));
        $this->assertSame('2026-11-24', $request->input('tanggal'));
        $this->assertNull($request->input('keterangan'));

        $rules = $request->rules();
        $this->assertTrue($request->authorize());
        $this->assertArrayHasKey('siswa_nis', $rules);
        $this->assertArrayHasKey('siswa_nis.*', $rules);
        $this->assertContains('array', $rules['siswa_nis']);
        $this->assertContains('min:1', $rules['siswa_nis']);
        $this->assertContains('distinct', $rules['siswa_nis.*']);
        $this->assertArrayHasKey('nama_prestasi', $rules);
        $this->assertArrayHasKey('foto', $rules);
    }

    public function test_thin_controller_delegates_crud_methods_to_prestasi_siswa_service(): void
    {
        $tahunAjaran = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);
        $tahunAjaran->id = 3;

        $prestasi = PrestasiSiswa::make([
            'akademik_master_tahun_ajaran_id' => 3,
            'nama_prestasi' => 'OSN Matematika SD',
            'kategori' => PrestasiSiswa::KATEGORI_AKADEMIK,
            'tingkat' => 'Provinsi',
            'peringkat' => 'Juara 1',
            'penyelenggara' => 'Kemendikdasmen',
            'tanggal' => '2026-11-24',
        ]);
        $prestasi->id = 15;

        $service = $this->createMock(PrestasiSiswaService::class);
        $service->expects($this->once())
            ->method('getIndexData')
            ->willReturn([
                'prestasi' => new Collection([$prestasi]),
                'statistics' => ['total' => 1, 'akademik' => 1, 'non_akademik' => 0, 'beregu' => 0, 'dengan_foto' => 0],
                'filters' => [
                    'tahun_ajaran_id' => 'all',
                    'kategori' => 'all',
                    'tingkat' => 'all',
                    'siswa_nis' => '',
                    'search' => '',
                ],
                'hasActiveFilter' => false,
                'tahunAjaranOptions' => new Collection([$tahunAjaran]),
                'activeTahunAjaran' => $tahunAjaran,
                'kategoriOptions' => PrestasiSiswa::KATEGORI_OPTIONS,
                'tingkatOptions' => PrestasiSiswa::TINGKAT_PRESETS,
            ]);
        $service->expects($this->exactly(2))
            ->method('getFormOptions')
            ->willReturn([
                'siswaOptions' => new Collection,
                'tahunAjaranOptions' => new Collection([$tahunAjaran]),
                'activeTahunAjaran' => $tahunAjaran,
                'kategoriOptions' => PrestasiSiswa::KATEGORI_OPTIONS,
                'tingkatPresets' => PrestasiSiswa::TINGKAT_PRESETS,
                'peringkatPresets' => PrestasiSiswa::PERINGKAT_PRESETS,
                'prefillSiswaNis' => '202601001',
            ]);
        $service->expects($this->exactly(2))
            ->method('loadDetail')
            ->with($prestasi)
            ->willReturn($prestasi);
        $service->expects($this->once())
            ->method('store')
            ->willReturn($prestasi);
        $service->expects($this->once())
            ->method('update')
            ->willReturn(true);
        $service->expects($this->once())
            ->method('delete')
            ->with($prestasi)
            ->willReturn(true);

        $controller = new PrestasiSiswaController($service);

        $this->assertSame(
            'admin.prestasi.prestasiSiswa.index',
            $controller->index(Request::create('/prestasi/prestasi-siswa', 'GET'))->name()
        );
        $this->assertSame(
            'admin.prestasi.prestasiSiswa.create',
            $controller->create(Request::create('/prestasi/prestasi-siswa/create?siswa_nis=202601001', 'GET'))->name()
        );
        $this->assertSame(
            'admin.prestasi.prestasiSiswa.show',
            $controller->show($prestasi)->name()
        );
        $this->assertSame(
            'admin.prestasi.prestasiSiswa.edit',
            $controller->edit($prestasi)->name()
        );

        $formRequest = PrestasiSiswaRequest::create('/prestasi/prestasi-siswa', 'POST', [
            'siswa_nis' => ['202601001', '202601002'],
            'akademik_master_tahun_ajaran_id' => 3,
            'nama_prestasi' => 'Lomba Cerdas Cermat SD',
            'kategori' => PrestasiSiswa::KATEGORI_AKADEMIK,
            'tingkat' => 'Provinsi',
            'peringkat' => 'Juara 1',
            'penyelenggara' => 'Kemendikdasmen',
            'tanggal' => '2026-11-24',
        ]);
        $validator = app('validator')->make($formRequest->all(), [
            'siswa_nis' => 'required|array|min:1',
            'siswa_nis.*' => 'required|string',
            'nama_prestasi' => 'required|string',
            'kategori' => 'required|string',
            'tingkat' => 'required|string',
            'peringkat' => 'required|string',
            'penyelenggara' => 'required|string',
            'tanggal' => 'required|date',
        ]);
        $validator->validate();
        $formRequest->setValidator($validator);

        $this->assertTrue($controller->store($formRequest)->isRedirect(route('prestasi-siswa.index')));
        $this->assertTrue($controller->update($formRequest, $prestasi)->isRedirect(route('prestasi-siswa.index')));
        $this->assertTrue($controller->destroy($prestasi)->isRedirect(route('prestasi-siswa.index')));
    }

    public function test_all_prestasi_views_and_siswa_show_tab_render_team_and_individual_cleanly_without_lazy_loading(): void
    {
        view()->share('errors', new ViewErrorBag);

        $tahunAjaran = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);
        $tahunAjaran->id = 1;

        $kelas = Kelas::make([
            'nama_kelas' => '5 Al-Kautsar',
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => '5',
        ]);
        $kelas->id = 10;
        $kelas->setRelation('tahunAjaran', $tahunAjaran);

        $siswa1 = Siswa::make([
            'nis' => '202601001',
            'nama_lengkap' => 'Aisyah Humaira',
            'jenis_kelamin' => 'P',
            'status' => 'Aktif',
        ]);
        $siswa1->setRelation('kelas', $kelas);
        $siswa1->setRelation('karyawan', null);
        $siswa1->setRelation('ekstrakurikuler', null);
        $siswa1->setRelation('jemputan', null);
        $siswa1->setRelation('berkebutuhanKhusus', null);
        $siswa1->setRelation('transportasi', null);
        $siswa1->setRelation('jenjangPendidikanAyah', null);
        $siswa1->setRelation('pekerjaanAyah', null);
        $siswa1->setRelation('penghasilanAyah', null);
        $siswa1->setRelation('berkebutuhanKhususAyah', null);
        $siswa1->setRelation('jenjangPendidikanIbu', null);
        $siswa1->setRelation('pekerjaanIbu', null);
        $siswa1->setRelation('penghasilanIbu', null);
        $siswa1->setRelation('berkebutuhanKhususIbu', null);
        $siswa1->setRelation('jenjangPendidikanWali', null);
        $siswa1->setRelation('pekerjaanWali', null);
        $siswa1->setRelation('penghasilanWali', null);
        $siswa1->setRelation('berkebutuhanKhususWali', null);

        $siswa2 = Siswa::make([
            'nis' => '202601002',
            'nama_lengkap' => 'Zaid Al-Faruq',
            'jenis_kelamin' => 'L',
            'status' => 'Aktif',
        ]);
        $siswa2->setRelation('kelas', $kelas);

        $prestasiBeregu = PrestasiSiswa::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'nama_prestasi' => 'Lomba Cerdas Cermat PAI SD',
            'kategori' => PrestasiSiswa::KATEGORI_AKADEMIK,
            'tingkat' => 'Provinsi',
            'peringkat' => 'Juara 1',
            'penyelenggara' => 'Kemenag Jawa Barat',
            'tanggal' => '2026-11-20',
            'keterangan' => 'Tim Cerdas Cermat Garuda SD',
            'foto' => 'prestasi/foto/piagam-pai.jpg',
        ]);
        $prestasiBeregu->id = 99;
        $prestasiBeregu->setRelation('siswa', new Collection([$siswa1, $siswa2]));
        $prestasiBeregu->setRelation('tahunAjaran', $tahunAjaran);

        $siswa1->setRelation('prestasi', new Collection([$prestasiBeregu]));

        Model::preventLazyLoading(true);
        try {
            $indexHtml = view('admin.prestasi.prestasiSiswa.index', [
                'prestasi' => new Collection([$prestasiBeregu]),
                'tahunAjaranOptions' => new Collection([$tahunAjaran]),
            ])->render();

            $createHtml = view('admin.prestasi.prestasiSiswa.create', [
                'siswaOptions' => new Collection([$siswa1, $siswa2]),
                'tahunAjaranOptions' => new Collection([$tahunAjaran]),
                'activeTahunAjaran' => $tahunAjaran,
                'prefillSiswaNis' => '202601001',
            ])->render();

            $editHtml = view('admin.prestasi.prestasiSiswa.edit', [
                'prestasiSiswa' => $prestasiBeregu,
                'siswaOptions' => new Collection([$siswa1, $siswa2]),
                'tahunAjaranOptions' => new Collection([$tahunAjaran]),
                'activeTahunAjaran' => $tahunAjaran,
            ])->render();

            $showHtml = view('admin.prestasi.prestasiSiswa.show', [
                'prestasiSiswa' => $prestasiBeregu,
            ])->render();

            $siswaShowHtml = view('admin.dataMaster.siswa.show', [
                'siswa' => $siswa1,
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('prestasi-filter-grid', $indexHtml);
        $this->assertStringContainsString('Lomba Cerdas Cermat PAI SD', $indexHtml);
        $this->assertStringContainsString('Beregu &bull; 2 Siswa', $indexHtml);
        $this->assertStringContainsString('js-prestasi-members', $indexHtml);
        $this->assertStringContainsString('data-search="Tim Cerdas Cermat Garuda SD Aisyah Humaira, Zaid Al-Faruq 202601001 202601002"', $indexHtml);
        $this->assertStringContainsString('Tim Cerdas Cermat Garuda SD', $indexHtml);

        $this->assertStringContainsString('enctype="multipart/form-data"', $createHtml);
        $this->assertStringContainsString('js-prestasi-siswa-picker', $createHtml);
        $this->assertStringContainsString('prestasi-ms-trigger', $createHtml);
        $this->assertStringContainsString('prestasi-ms-menu', $createHtml);
        $this->assertStringContainsString('prestasi-ms-selected', $createHtml);
        $this->assertStringContainsString('prestasiPesertaModeBadge', $createHtml);
        $this->assertStringContainsString('name="siswa_nis[]"', $createHtml);
        $this->assertStringContainsString('js-prestasi-preset-btn', $createHtml);

        $this->assertStringContainsString('Lomba Cerdas Cermat PAI SD', $editHtml);
        $this->assertStringContainsString('202601002', $editHtml);
        $this->assertStringContainsString('remove_foto', $editHtml);

        $this->assertStringContainsString('Beregu (2 Siswa)', $showHtml);
        $this->assertStringContainsString('Penerima Prestasi (2 Siswa)', $showHtml);
        $this->assertStringContainsString('Aisyah Humaira', $showHtml);
        $this->assertStringContainsString('Zaid Al-Faruq', $showHtml);

        $this->assertStringContainsString('data-siswa-tab="prestasi"', $siswaShowHtml);
        $this->assertStringContainsString('Lomba Cerdas Cermat PAI SD', $siswaShowHtml);
        $this->assertStringContainsString('Beregu (2 Siswa)', $siswaShowHtml);
        $this->assertStringContainsString('Rekan Tim:', $siswaShowHtml);
        $this->assertStringContainsString('Zaid Al-Faruq', $siswaShowHtml);

        $emptyHtml = view('admin.prestasi.prestasiSiswa.index', [
            'prestasi' => new Collection,
        ])->render();
        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML($emptyHtml);
        libxml_clear_errors();
        $xpath = new DOMXPath($document);
        $this->assertSame(8, $xpath->query('//table[@id="myTable3"]/thead/tr/th')->length);
        $this->assertSame(0, $xpath->query('//table[@id="myTable3"]/tbody/tr')->length);
    }
}
