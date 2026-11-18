<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\DataMaster\SekolahController;
use App\Http\Requests\Admin\DataMaster\SekolahRequest;
use App\Models\Admin\DataMaster\Sekolah;
use App\Services\Admin\DataMaster\SekolahService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class SekolahModelAndViewTest extends TestCase
{
    public function test_sekolah_model_resolves_active_jenjang_and_formats_alamat_lengkap(): void
    {
        $tk = Sekolah::make([
            'jenjang_kelas' => 'TK',
            'nama_yayasan' => 'Yayasan Prima Insani',
            'nama_sekolah' => 'TK Prima Insani',
            'npsn' => '20201111',
            'alamat' => 'Jl. Cimanuk No. 12',
            'rt' => '01',
            'rw' => '02',
            'kelurahan' => 'Sukamentri',
            'kecamatan' => 'Garut Kota',
            'kabupaten' => 'Kabupaten Garut',
            'provinsi' => 'Jawa Barat',
            'kode_pos' => '44116',
            'status' => true,
        ]);
        $tk->id = 1;

        $sd = Sekolah::make([
            'jenjang_kelas' => 'SD',
            'nama_sekolah' => 'SD Prima Insani',
            'npsn' => '20209000',
            'alamat' => 'Jl. Cimanuk No. 14',
            'status' => true,
        ]);
        $sd->id = 2;

        $inactiveSmp = Sekolah::make([
            'jenjang_kelas' => 'SMP',
            'nama_sekolah' => 'SMP Prima Insani',
            'alamat' => 'Jl. Cimanuk',
            'status' => false,
        ]);
        $inactiveSmp->id = 3;

        $pool = new Collection([$tk, $sd, $inactiveSmp]);

        $this->assertSame($tk, Sekolah::resolveFromCollection($pool, 'tk'));
        $this->assertSame($sd, Sekolah::resolveFromCollection($pool, 'SD'));
        $this->assertNull(Sekolah::resolveFromCollection($pool, 'SMP'));
        $this->assertStringContainsString('Jl. Cimanuk No. 12', $tk->alamat_lengkap);
        $this->assertStringContainsString('RT 01 / RW 02', $tk->alamat_lengkap);
        $this->assertStringContainsString('Kel. Sukamentri', $tk->alamat_lengkap);
        $this->assertSame('badge-success', $sd->jenjang_badge_class);
    }

    public function test_sekolah_request_normalizes_and_validates_fields(): void
    {
        $request = new SekolahRequest;
        $request->merge([
            'jenjang_kelas' => ' sd ',
            'nama_sekolah' => '  SD Prima Insani  ',
            'alamat' => '  Jl. Cimanuk  ',
            'email' => 'not-an-email',
            'npsn' => '',
        ]);

        $prepare = \Closure::bind(function (): void {
            $this->prepareForValidation();
        }, $request, SekolahRequest::class);
        $prepare();

        $this->assertSame('SD', $request->input('jenjang_kelas'));
        $this->assertSame('SD Prima Insani', $request->input('nama_sekolah'));
        $this->assertNull($request->input('npsn'));

        $rules = $request->rules();
        $rules['jenjang_kelas'] = ['required', 'string', 'max:20'];
        $validator = Validator::make($request->all(), $rules, $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('email'));
    }

    public function test_controller_delegates_crud_and_renders_views_without_lazy_loading(): void
    {
        $tk = Sekolah::make([
            'jenjang_kelas' => 'TK',
            'nama_yayasan' => 'Yayasan Prima Insani',
            'nama_sekolah' => 'TK Prima Insani',
            'npsn' => '20201111',
            'alamat' => 'Jl. Cimanuk No. 12',
            'kabupaten' => 'Garut',
            'status' => true,
        ]);
        $tk->id = 1;

        $sd = Sekolah::make([
            'jenjang_kelas' => 'SD',
            'nama_yayasan' => 'Yayasan Prima Insani',
            'nama_sekolah' => 'SD Prima Insani',
            'npsn' => '20209000',
            'alamat' => 'Jl. Cimanuk No. 14',
            'kabupaten' => 'Garut',
            'status' => true,
        ]);
        $sd->id = 2;

        $service = $this->createMock(SekolahService::class);
        $service->expects($this->once())->method('getAll')->willReturn(new Collection([$tk, $sd]));
        $service->expects($this->exactly(2))->method('getFormOptions')->willReturn(['jenjangOptions' => Sekolah::JENJANG_OPTIONS]);
        $service->expects($this->once())->method('store')->willReturn($sd);
        $service->expects($this->once())->method('update')->with($sd, $this->isArray())->willReturn(true);
        $service->expects($this->once())->method('delete')->with($sd)->willReturn(true);
        $service->expects($this->once())->method('toggleStatus')->with($sd)->willReturn(true);

        $controller = new SekolahController($service);

        $indexView = $controller->index();
        $createView = $controller->create();
        $editView = $controller->edit($sd);

        $this->assertSame('admin.dataMaster.sekolah.index', $indexView->name());
        $this->assertSame('admin.dataMaster.sekolah.create', $createView->name());
        $this->assertSame('admin.dataMaster.sekolah.edit', $editView->name());

        $formRequest = SekolahRequest::create('/data-master/sekolah', 'POST', [
            'jenjang_kelas' => 'SD',
            'nama_sekolah' => 'SD Prima Insani',
            'alamat' => 'Jl. Cimanuk No. 14',
            'status' => true,
        ]);
        $formRequest->setValidator(Validator::make($formRequest->all(), []));

        $this->assertInstanceOf(RedirectResponse::class, $controller->store($formRequest));
        $this->assertInstanceOf(RedirectResponse::class, $controller->update($formRequest, $sd));
        $this->assertInstanceOf(RedirectResponse::class, $controller->destroy($sd));
        $this->assertInstanceOf(RedirectResponse::class, $controller->toggleStatus($sd));

        Model::preventLazyLoading(true);
        try {
            $indexHtml = $indexView->render();
            $createHtml = $createView->with('errors', new ViewErrorBag)->render();
            $editHtml = $editView->with('errors', new ViewErrorBag)->render();
            $sidebarHtml = view('layouts.sidebar')->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('TK Prima Insani', $indexHtml);
        $this->assertStringContainsString('SD Prima Insani', $indexHtml);
        $this->assertStringContainsString('20209000', $indexHtml);
        $this->assertStringContainsString(route('sekolah.toggle-status', 2), $indexHtml);
        $this->assertStringContainsString(route('sekolah.edit', 2), $indexHtml);

        $this->assertStringContainsString('Tambah Identitas Sekolah', $createHtml);
        $this->assertStringContainsString(route('sekolah.store'), $createHtml);

        $this->assertStringContainsString('Edit Identitas Sekolah', $editHtml);
        $this->assertStringContainsString(route('sekolah.update', 2), $editHtml);

        $this->assertStringContainsString('Identitas Sekolah', $sidebarHtml);
        $this->assertStringContainsString(route('sekolah.index'), $sidebarHtml);
    }
}

