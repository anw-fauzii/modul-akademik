<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\DataMaster\TitimangsaController;
use App\Http\Requests\Admin\DataMaster\TitimangsaRequest;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\DataMaster\Titimangsa;
use App\Services\Admin\DataMaster\TitimangsaService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class TitimangsaModelAndViewTest extends TestCase
{
    public function test_titimangsa_resolves_specific_tingkatan_before_wildcard_fallback(): void
    {
        $sdGeneral = Titimangsa::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => Titimangsa::TINGKATAN_SEMUA,
            'periode' => 'semester',
            'tempat' => 'Garut',
            'tanggal_titimangsa' => '2026-12-19',
            'kepala_sekolah_nipy' => 'NIPY-SD',
            'jabatan_kepala_sekolah' => 'Kepala SD Prima Insani',
            'status' => true,
        ]);
        $sdGeneral->id = 1;

        $sdGrade6 = Titimangsa::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => '6',
            'periode' => 'semester',
            'tempat' => 'Garut',
            'tanggal_titimangsa' => '2026-06-15',
            'kepala_sekolah_nipy' => 'NIPY-SD',
            'jabatan_kepala_sekolah' => 'Kepala SD Prima Insani',
            'status' => true,
        ]);
        $sdGrade6->id = 2;

        $sdGrade6Inactive = Titimangsa::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => '5',
            'periode' => 'semester',
            'tempat' => 'Garut',
            'tanggal_titimangsa' => '2026-06-10',
            'kepala_sekolah_nipy' => 'NIPY-SD',
            'status' => false,
        ]);
        $sdGrade6Inactive->id = 3;

        $tkGeneral = Titimangsa::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'jenjang_kelas' => 'TK',
            'tingkatan_kelas' => Titimangsa::TINGKATAN_SEMUA,
            'periode' => 'semester',
            'tempat' => 'Garut',
            'tanggal_titimangsa' => '2026-12-18',
            'kepala_sekolah_nipy' => 'NIPY-TK',
            'status' => true,
        ]);
        $tkGeneral->id = 4;

        $pool = new Collection([$sdGeneral, $sdGrade6, $sdGrade6Inactive, $tkGeneral]);

        // Kelas 6 SD mendapat pengaturan spesifik Tingkat 6
        $resolvedGrade6 = Titimangsa::resolveFromCollection($pool, 1, 'SD', '6', 'semester');
        $this->assertSame(2, $resolvedGrade6?->id);
        $this->assertTrue($resolvedGrade6->is_specific_tingkatan);
        $this->assertSame('Tingkat 6', $resolvedGrade6->tingkatan_label);

        // Kelas 5 SD (pengaturan spesifik nonaktif) otomatis fallback ke Semua Tingkat (*)
        $resolvedGrade5 = Titimangsa::resolveFromCollection($pool, 1, 'SD', '5', 'semester');
        $this->assertSame(1, $resolvedGrade5?->id);
        $this->assertFalse($resolvedGrade5->is_specific_tingkatan);
        $this->assertSame('Semua Tingkat', $resolvedGrade5->tingkatan_label);

        // Jenjang TK mendapat kepsek & tanggal TK
        $resolvedTk = Titimangsa::resolveFromCollection($pool, 1, 'tk', 'B', 'semester');
        $this->assertSame(4, $resolvedTk?->id);
        $this->assertSame('NIPY-TK', $resolvedTk?->kepala_sekolah_nipy);

        // Periode mid yang belum diatur mengembalikan null
        $this->assertNull(Titimangsa::resolveFromCollection($pool, 1, 'SD', '6', 'mid'));
    }

    public function test_titimangsa_request_validates_scope_normalization_and_signer_requirement(): void
    {
        $request = new TitimangsaRequest;
        $request->merge([
            'akademik_master_tahun_ajaran_id' => '1',
            'jenjang_kelas' => 'sd',
            'tingkatan_scope' => 'semua',
            'periode' => 'SEMESTER',
            'tempat' => ' Garut ',
            'tanggal_titimangsa' => '2026-12-19',
            'kepala_sekolah_nipy' => '',
            'nama_kepala_sekolah_snapshot' => '',
        ]);

        // Panggil prepareForValidation menggunakan Closure::bind
        $prepare = \Closure::bind(function (): void {
            $this->prepareForValidation();
        }, $request, TitimangsaRequest::class);
        $prepare();

        $this->assertSame('SD', $request->input('jenjang_kelas'));
        $this->assertSame('*', $request->input('tingkatan_kelas'));
        $this->assertSame('semester', $request->input('periode'));
        $this->assertNull($request->input('kepala_sekolah_nipy'));

        // Validasi nama_kepala_sekolah_snapshot wajib bila kepala_sekolah_nipy kosong
        $rules = $request->rules();
        // Ganti rule DB exists/unique untuk uji validasi aturan murni tanpa koneksi DB
        $rules['akademik_master_tahun_ajaran_id'] = ['required', 'integer'];
        $rules['periode'] = ['required', 'string'];
        $rules['kepala_sekolah_nipy'] = ['nullable', 'string'];

        $validator = Validator::make($request->all(), $rules, $request->messages());
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('nama_kepala_sekolah_snapshot'));
    }

    public function test_controller_delegates_crud_and_renders_views_without_lazy_loading(): void
    {
        $ta = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);
        $ta->id = 1;

        $kepsek = Karyawan::make(['nipy' => 'NIPY-KS1', 'nama_lengkap' => 'H. Ahmad Fauzi', 'gelar' => 'M.Pd']);

        $titimangsaGeneral = Titimangsa::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => '*',
            'periode' => 'mid',
            'tempat' => 'Garut',
            'tanggal_titimangsa' => '2026-10-15',
            'kepala_sekolah_nipy' => 'NIPY-KS1',
            'nama_kepala_sekolah_snapshot' => 'H. Ahmad Fauzi, M.Pd',
            'jabatan_kepala_sekolah' => 'Kepala SD Prima Insani',
            'status' => true,
        ]);
        $titimangsaGeneral->id = 10;
        $titimangsaGeneral->setRelation('tahunAjaran', $ta);
        $titimangsaGeneral->setRelation('kepalaSekolah', $kepsek);

        $titimangsaGrade6 = Titimangsa::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => '6',
            'periode' => 'semester',
            'tempat' => 'Garut',
            'tanggal_titimangsa' => '2026-06-20',
            'kepala_sekolah_nipy' => 'NIPY-KS1',
            'jabatan_kepala_sekolah' => 'Kepala SD Prima Insani',
            'status' => true,
        ]);
        $titimangsaGrade6->id = 11;
        $titimangsaGrade6->setRelation('tahunAjaran', $ta);
        $titimangsaGrade6->setRelation('kepalaSekolah', $kepsek);

        $formOptions = [
            'tahunAjaran' => new Collection([$ta]),
            'karyawan' => new Collection([$kepsek]),
            'jenjangOptions' => Titimangsa::JENJANG_OPTIONS,
            'periodeOptions' => Titimangsa::PERIODE_OPTIONS,
        ];

        $indexPayload = [
            'titimangsa' => new Collection([$titimangsaGeneral, $titimangsaGrade6]),
            'filters' => ['tahun_ajaran_id' => '', 'jenjang_kelas' => '', 'periode' => ''],
            'tahunAjaranOptions' => new Collection([$ta]),
            'jenjangOptions' => Titimangsa::JENJANG_OPTIONS,
            'periodeOptions' => Titimangsa::PERIODE_OPTIONS,
        ];

        $service = $this->createMock(TitimangsaService::class);
        $service->expects($this->once())->method('getIndexData')->willReturn($indexPayload);
        $service->expects($this->exactly(2))->method('getFormOptions')->willReturn($formOptions);
        $service->expects($this->once())->method('store')->willReturn($titimangsaGeneral);
        $service->expects($this->once())->method('update')->with($titimangsaGrade6, $this->isArray())->willReturn(true);
        $service->expects($this->once())->method('delete')->with($titimangsaGrade6)->willReturn(true);
        $service->expects($this->once())->method('toggleStatus')->with($titimangsaGrade6)->willReturn(true);

        $controller = new TitimangsaController($service);

        $indexView = $controller->index(Request::create('/data-master/titimangsa', 'GET'));
        $createView = $controller->create();
        $editView = $controller->edit($titimangsaGrade6);

        $this->assertSame('admin.dataMaster.titimangsa.index', $indexView->name());
        $this->assertSame('admin.dataMaster.titimangsa.create', $createView->name());
        $this->assertSame('admin.dataMaster.titimangsa.edit', $editView->name());

        $formRequest = TitimangsaRequest::create('/data-master/titimangsa', 'POST', [
            'akademik_master_tahun_ajaran_id' => 1,
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => '6',
            'periode' => 'semester',
            'tempat' => 'Garut',
            'tanggal_titimangsa' => '2026-06-20',
            'kepala_sekolah_nipy' => 'NIPY-KS1',
            'jabatan_kepala_sekolah' => 'Kepala SD Prima Insani',
            'status' => true,
        ]);
        $formRequest->setValidator(Validator::make($formRequest->all(), []));

        $storeResp = $controller->store($formRequest);
        $updateResp = $controller->update($formRequest, $titimangsaGrade6);
        $destroyResp = $controller->destroy($titimangsaGrade6);
        $toggleResp = $controller->toggleStatus($titimangsaGrade6);

        $this->assertInstanceOf(RedirectResponse::class, $storeResp);
        $this->assertInstanceOf(RedirectResponse::class, $updateResp);
        $this->assertInstanceOf(RedirectResponse::class, $destroyResp);
        $this->assertInstanceOf(RedirectResponse::class, $toggleResp);

        Model::preventLazyLoading(true);
        try {
            $indexHtml = $indexView->render();
            $createHtml = $createView->with('errors', new ViewErrorBag)->render();
            $editHtml = $editView->with('errors', new ViewErrorBag)->render();
            $sidebarHtml = view('layouts.sidebar')->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('Master Titimangsa', $indexHtml);
        $this->assertStringContainsString('Semua Tingkat (*)', $indexHtml);
        $this->assertStringContainsString('Khusus Tingkat 6', $indexHtml);
        $this->assertStringContainsString('H. Ahmad Fauzi, M.Pd', $indexHtml);
        $this->assertStringContainsString('Kepala SD Prima Insani', $indexHtml);
        $this->assertStringContainsString(route('titimangsa.toggle-status', sqidFor('titimangsa.toggle-status', 10)), $indexHtml);
        $this->assertStringContainsString(route('titimangsa.edit', sqidFor('titimangsa.edit', 10)), $indexHtml);

        $this->assertStringContainsString('Tambah Titimangsa Rapor', $createHtml);
        $this->assertStringContainsString(route('titimangsa.store'), $createHtml);
        $this->assertStringContainsString('Tingkatan Tertentu', $createHtml);

        $this->assertStringContainsString('Edit Titimangsa Rapor', $editHtml);
        $this->assertStringContainsString(route('titimangsa.update', sqidFor('titimangsa.update', 11)), $editHtml);

        $this->assertStringContainsString('Titimangsa Rapor', $sidebarHtml);
        $this->assertStringContainsString(route('titimangsa.index'), $sidebarHtml);
    }
}
