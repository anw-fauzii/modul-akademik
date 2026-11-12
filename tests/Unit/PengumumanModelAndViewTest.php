<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\Informasi\PengumumanController;
use App\Http\Requests\Admin\Informasi\PengumumanRequest;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Informasi\Pengumuman;
use App\Services\Admin\Informasi\PengumumanService;
use App\Support\HtmlSanitizer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;
use ReflectionMethod;
use Tests\TestCase;

class PengumumanModelAndViewTest extends TestCase
{
    public function test_model_metadata_fillable_casts_and_relation_are_configured(): void
    {
        $pengumuman = new Pengumuman;

        $this->assertSame('akademik_informasi_pengumuman', $pengumuman->getTable());
        $this->assertSame([
            'akademik_master_tahun_ajaran_id',
            'judul',
            'isi',
            'jenis',
        ], $pengumuman->getFillable());
        $this->assertSame(Pengumuman::JENIS_SISWA, $pengumuman->jenis);
        $this->assertSame(['siswa', 'pegawai'], Pengumuman::JENIS_OPTIONS);

        $casts = $pengumuman->getCasts();
        $this->assertSame('integer', $casts['akademik_master_tahun_ajaran_id']);

        $relation = $pengumuman->tahunAjaran();
        $this->assertSame('akademik_master_tahun_ajaran_id', $relation->getForeignKeyName());
        $this->assertSame('id', $relation->getOwnerKeyName());
    }

    public function test_ringkasan_isi_and_tanggal_dibuat_accessors_format_values(): void
    {
        $longContent = "Baris pertama pengumuman penting untuk siswa.\n\n".str_repeat('Detail informasi akademik ', 10);
        $pengumuman = Pengumuman::make(['isi' => $longContent]);
        $pengumuman->created_at = Carbon::create(2026, 11, 12, 9, 30, 0);

        $this->assertLessThanOrEqual(143, strlen($pengumuman->ringkasan_isi));
        $this->assertStringNotContainsString("\n", $pengumuman->ringkasan_isi);
        $this->assertStringContainsString('Baris pertama pengumuman', $pengumuman->ringkasan_isi);
        $this->assertNotSame('-', $pengumuman->tanggal_dibuat_formatted);
        $this->assertStringContainsString('2026', $pengumuman->tanggal_dibuat_formatted);

        $emptyPengumuman = Pengumuman::make(['isi' => '   ']);
        $this->assertSame('-', $emptyPengumuman->ringkasan_isi);
        $this->assertSame('-', $emptyPengumuman->tanggal_dibuat_formatted);

        $htmlPengumuman = Pengumuman::make([
            'isi' => '<p>Jadwal&nbsp;<strong>UTS</strong> &amp; Pembagian Rapor</p><script>alert(1)</script><ul><li onclick="hack()">Senin: Matematika</li></ul>',
        ]);
        $this->assertSame('Jadwal UTS & Pembagian Rapor Senin: Matematika', $htmlPengumuman->ringkasan_isi);
        $this->assertStringContainsString('<strong>UTS</strong>', $htmlPengumuman->isi_aman);
        $this->assertStringContainsString('<ul><li>Senin: Matematika</li></ul>', $htmlPengumuman->isi_aman);
        $this->assertStringNotContainsString('<script>', $htmlPengumuman->isi_aman);
        $this->assertStringNotContainsString('onclick', $htmlPengumuman->isi_aman);
    }

    public function test_html_sanitizer_preserves_safe_rich_text_and_strips_dangerous_markup(): void
    {
        $dirtyHtml = '<p style="text-align: center; color: #ea580c; background: url(javascript:alert(1));">'
            .'Informasi <a href="https://primainsani.sch.id" target="_blank" onclick="steal()">Website</a> '
            .'dan <a href="javascript:alert(2)">Tautan Jahat</a></p>'
            .'<iframe src="https://evil.test"></iframe>';

        $clean = HtmlSanitizer::sanitize($dirtyHtml);

        $this->assertStringContainsString('style="text-align: center; color: #ea580c"', $clean);
        $this->assertStringContainsString('href="https://primainsani.sch.id"', $clean);
        $this->assertStringContainsString('rel="noopener noreferrer"', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringNotContainsString('<iframe', $clean);
        $this->assertSame("Baris 1\n\nBaris 2", HtmlSanitizer::sanitize("  Baris 1\n\nBaris 2  "));
    }

    public function test_service_index_query_selects_minimal_columns_and_eager_loads_tahun_ajaran_to_prevent_n_plus_one(): void
    {
        $service = new PengumumanService;
        $builder = $service->buildIndexQuery([
            'tahun_ajaran_id' => 10,
            'search' => 'Libur',
        ]);

        $query = $builder->getQuery();
        $this->assertSame([
            'id',
            'akademik_master_tahun_ajaran_id',
            'judul',
            'isi',
            'jenis',
            'created_at',
        ], $query->columns);

        $sql = strtolower($builder->toSql());
        $this->assertStringContainsString('jenis', $sql);
        $this->assertStringContainsString('akademik_master_tahun_ajaran_id', $sql);
        $this->assertStringContainsString('judul', $sql);
        $this->assertStringContainsString('isi', $sql);
        $this->assertStringContainsString('order by', $sql);
        $this->assertSame(['siswa', 10, '%Libur%', '%Libur%'], $builder->getBindings());

        $eagerLoads = $builder->getEagerLoads();
        $this->assertArrayHasKey('tahunAjaran', $eagerLoads);

        $tahunAjaranQuery = TahunAjaran::query();
        ($eagerLoads['tahunAjaran'])($tahunAjaranQuery);
        $this->assertSame(['id', 'nama_tahun_ajaran', 'semester', 'is_active'], $tahunAjaranQuery->getQuery()->columns);
    }

    public function test_request_prepares_trimmed_inputs_and_forces_siswa_jenis(): void
    {
        $request = PengumumanRequest::create('/informasi/pengumuman', 'POST', [
            'akademik_master_tahun_ajaran_id' => '5',
            'judul' => '  Libur Awal   Ramadhan  ',
            'isi' => "  Kegiatan belajar diliburkan.\n\nMasuk kembali sesuai jadwal.  ",
            'jenis' => 'pegawai',
        ]);

        $prepareMethod = new ReflectionMethod(PengumumanRequest::class, 'prepareForValidation');
        $prepareMethod->invoke($request);

        $this->assertSame('Libur Awal Ramadhan', $request->input('judul'));
        $this->assertSame("Kegiatan belajar diliburkan.\n\nMasuk kembali sesuai jadwal.", $request->input('isi'));
        $this->assertSame(Pengumuman::JENIS_SISWA, $request->input('jenis'));

        $rules = $request->rules();
        $this->assertArrayHasKey('akademik_master_tahun_ajaran_id', $rules);
        $this->assertArrayHasKey('judul', $rules);
        $this->assertArrayHasKey('isi', $rules);
        $this->assertArrayHasKey('jenis', $rules);

        $emptyEditorRequest = PengumumanRequest::create('/informasi/pengumuman', 'POST', [
            'akademik_master_tahun_ajaran_id' => '5',
            'judul' => 'Pengumuman Kosong',
            'isi' => '<p><br></p><p>&nbsp;</p>',
        ]);
        $prepareMethod->invoke($emptyEditorRequest);
        $this->assertSame('', $emptyEditorRequest->input('isi'));

        $dirtyEditorRequest = PengumumanRequest::create('/informasi/pengumuman', 'POST', [
            'akademik_master_tahun_ajaran_id' => '5',
            'judul' => 'Pengumuman Aman',
            'isi' => '<p><strong>Pengumuman</strong> penting.</p><script>alert(1)</script>',
        ]);
        $prepareMethod->invoke($dirtyEditorRequest);
        $this->assertSame('<p><strong>Pengumuman</strong> penting.</p>', $dirtyEditorRequest->input('isi'));
    }

    public function test_thin_controller_delegates_crud_methods_to_pengumuman_service(): void
    {
        $tahunAjaran = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);
        $tahunAjaran->id = 3;

        $pengumuman = Pengumuman::make([
            'akademik_master_tahun_ajaran_id' => 3,
            'judul' => 'Pengambilan Rapor Semester Ganjil',
            'isi' => 'Wali murid dimohon hadir tepat waktu.',
        ]);
        $pengumuman->id = 12;

        $service = $this->createMock(PengumumanService::class);
        $service->expects($this->once())
            ->method('getIndexData')
            ->with(['search' => 'Rapor'])
            ->willReturn([
                'pengumuman' => new Collection,
                'totalPengumuman' => 0,
                'filters' => ['tahun_ajaran_id' => '3', 'search' => 'Rapor'],
                'tahunAjaranOptions' => new Collection([$tahunAjaran]),
                'activeTahunAjaran' => $tahunAjaran,
            ]);
        $service->expects($this->exactly(2))
            ->method('getFormOptions')
            ->willReturn([
                'tahunAjaran' => new Collection([$tahunAjaran]),
            ]);
        $service->expects($this->once())
            ->method('store')
            ->willReturn($pengumuman);
        $service->expects($this->once())
            ->method('update')
            ->with($pengumuman, $this->isArray())
            ->willReturn(true);
        $service->expects($this->once())
            ->method('delete')
            ->with($pengumuman)
            ->willReturn(true);

        $controller = new PengumumanController($service);

        $indexView = $controller->index(Request::create('/informasi/pengumuman?search=Rapor', 'GET'));
        $this->assertSame('admin.informasi.pengumuman.index', $indexView->name());

        $createView = $controller->create();
        $this->assertSame('admin.informasi.pengumuman.create', $createView->name());

        $editView = $controller->edit($pengumuman);
        $this->assertSame('admin.informasi.pengumuman.edit', $editView->name());

        $formRequest = PengumumanRequest::create('/informasi/pengumuman', 'POST', [
            'akademik_master_tahun_ajaran_id' => 3,
            'judul' => 'Pengambilan Rapor Semester Ganjil',
            'isi' => 'Wali murid dimohon hadir tepat waktu.',
            'jenis' => Pengumuman::JENIS_SISWA,
        ]);
        $validator = app('validator')->make($formRequest->all(), [
            'akademik_master_tahun_ajaran_id' => 'required|integer',
            'judul' => 'required|string|max:255',
            'isi' => 'required|string',
            'jenis' => 'required|string',
        ]);
        $validator->validate();
        $formRequest->setValidator($validator);

        $this->assertTrue($controller->store($formRequest)->isRedirect(route('pengumuman.index')));
        $this->assertTrue($controller->update($formRequest, $pengumuman)->isRedirect(route('pengumuman.index')));
        $this->assertTrue($controller->destroy($pengumuman)->isRedirect(route('pengumuman.index')));
    }

    public function test_index_and_form_views_render_without_lazy_loading_and_with_formatted_values(): void
    {
        view()->share('errors', new ViewErrorBag);

        $tahunAjaran = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);
        $tahunAjaran->id = 1;

        $pengumuman = Pengumuman::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'judul' => 'Sosialisasi Ujian Sekolah',
            'isi' => "Paragraf pertama.\n".str_repeat('Harap mempersiapkan perlengkapan ujian dengan lengkap. ', 4),
            'jenis' => Pengumuman::JENIS_SISWA,
        ]);
        $pengumuman->id = 42;
        $pengumuman->created_at = Carbon::create(2026, 11, 12, 8, 0, 0);
        $pengumuman->setRelation('tahunAjaran', $tahunAjaran);

        Model::preventLazyLoading(true);

        try {
            $indexHtml = view('admin.informasi.pengumuman.index', [
                'pengumuman' => new Collection([$pengumuman]),
                'totalPengumuman' => 1,
                'filters' => ['tahun_ajaran_id' => '1', 'search' => ''],
                'tahunAjaranOptions' => new Collection([$tahunAjaran]),
                'activeTahunAjaran' => $tahunAjaran,
            ])->render();

            $createHtml = view('admin.informasi.pengumuman.create', [
                'tahunAjaran' => new Collection([$tahunAjaran]),
            ])->render();

            $editHtml = view('admin.informasi.pengumuman.edit', [
                'pengumuman' => $pengumuman,
                'tahunAjaran' => new Collection([$tahunAjaran]),
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('Sosialisasi Ujian Sekolah', $indexHtml);
        $this->assertStringContainsString('Baca selengkapnya', $indexHtml);
        $this->assertStringContainsString('pengumuman-rich-content', $indexHtml);
        $this->assertStringContainsString('Siswa', $indexHtml);
        $this->assertStringContainsString('2026/2027', $indexHtml);
        $this->assertStringContainsString(route('pengumuman.edit', 42), $indexHtml);
        $this->assertStringContainsString(route('pengumuman.destroy', 42), $indexHtml);

        $this->assertStringContainsString(route('pengumuman.store'), $createHtml);
        $this->assertStringContainsString('name="judul"', $createHtml);
        $this->assertStringContainsString('name="isi"', $createHtml);
        $this->assertStringContainsString('name="jenis" value="siswa"', $createHtml);
        $this->assertStringContainsString('tinymce.min.js', $createHtml);
        $this->assertStringContainsString("license_key: 'gpl'", $createHtml);

        $this->assertStringContainsString(route('pengumuman.update', 42), $editHtml);
        $this->assertStringContainsString('Sosialisasi Ujian Sekolah', $editHtml);
        $this->assertStringContainsString('tinymce.min.js', $editHtml);
        $this->assertStringContainsString("license_key: 'gpl'", $editHtml);
        $this->assertStringContainsString(route('pengumuman.index'), view('layouts.sidebar')->render());
    }
}
