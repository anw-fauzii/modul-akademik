<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\Pelajaran\KelompokPelajaranController;
use App\Http\Requests\Admin\Pelajaran\KelompokPelajaranRequest;
use App\Models\Admin\Pelajaran\KelompokPelajaran;
use App\Services\Admin\Pelajaran\KelompokPelajaranService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;
use ReflectionMethod;
use Tests\TestCase;

class KelompokPelajaranModelAndViewTest extends TestCase
{
    public function test_model_metadata_fillable_and_casts_are_configured(): void
    {
        $kelompok = new KelompokPelajaran;

        $this->assertSame('akademik_pelajaran_kelompok_pelajaran', $kelompok->getTable());
        $this->assertSame([
            'kode',
            'kelompok',
            'urutan',
        ], $kelompok->getFillable());
        $this->assertSame('integer', $kelompok->getCasts()['urutan'] ?? null);
    }

    public function test_service_index_query_selects_minimal_columns_applies_search_and_orders_consistently(): void
    {
        $service = new KelompokPelajaranService;
        $builder = $service->buildIndexQuery([
            'search' => '100%_A',
        ]);

        $query = $builder->getQuery();
        $this->assertSame([
            'id',
            'kode',
            'kelompok',
            'urutan',
            'created_at',
        ], $query->columns);

        $sql = strtolower($builder->toSql());
        $this->assertStringContainsString('kode', $sql);
        $this->assertStringContainsString('kelompok', $sql);
        $this->assertStringContainsString('order by', $sql);
        $this->assertStringContainsString('urutan', $sql);
        $this->assertSame(['%100\%\_A%', '%100\%\_A%'], $builder->getBindings());
    }

    public function test_request_normalizes_kode_kelompok_and_urutan_and_defines_validation_rules(): void
    {
        $request = KelompokPelajaranRequest::create('/pelajaran/kelompok-pelajaran', 'POST', [
            'kode' => '  klp   a  ',
            'kelompok' => '  Kelompok A   (Umum)  ',
            'urutan' => ' 1 ',
        ]);

        $prepareMethod = new ReflectionMethod(KelompokPelajaranRequest::class, 'prepareForValidation');
        $prepareMethod->invoke($request);

        $this->assertSame('KLP A', $request->input('kode'));
        $this->assertSame('Kelompok A (Umum)', $request->input('kelompok'));
        $this->assertSame('1', $request->input('urutan'));

        $rules = $request->rules();
        $this->assertArrayHasKey('kode', $rules);
        $this->assertArrayHasKey('kelompok', $rules);
        $this->assertArrayHasKey('urutan', $rules);
        $this->assertContains('integer', $rules['urutan']);
        $this->assertContains('min:1', $rules['urutan']);
    }

    public function test_thin_controller_delegates_crud_methods_to_kelompok_pelajaran_service(): void
    {
        $kelompok = KelompokPelajaran::make([
            'kode' => 'A',
            'kelompok' => 'Kelompok A (Wajib)',
            'urutan' => 1,
        ]);
        $kelompok->id = 7;

        $service = $this->createMock(KelompokPelajaranService::class);
        $service->expects($this->once())
            ->method('getIndexData')
            ->with(['search' => 'Wajib'])
            ->willReturn([
                'kelompokPelajaran' => new Collection([$kelompok]),
                'filters' => ['search' => 'Wajib'],
                'totalKelompok' => 1,
            ]);
        $service->expects($this->once())
            ->method('getNextUrutan')
            ->willReturn(2);
        $service->expects($this->once())
            ->method('store')
            ->willReturn($kelompok);
        $service->expects($this->once())
            ->method('update')
            ->with($kelompok, $this->isArray())
            ->willReturn(true);
        $service->expects($this->once())
            ->method('delete')
            ->with($kelompok)
            ->willReturn(true);

        $controller = new KelompokPelajaranController($service);

        $indexView = $controller->index(Request::create('/pelajaran/kelompok-pelajaran?search=Wajib', 'GET'));
        $this->assertSame('admin.pelajaran.kelompokPelajaran.index', $indexView->name());

        $createView = $controller->create();
        $this->assertSame('admin.pelajaran.kelompokPelajaran.create', $createView->name());
        $this->assertSame(2, $createView->getData()['nextUrutan']);

        $editView = $controller->edit($kelompok);
        $this->assertSame('admin.pelajaran.kelompokPelajaran.edit', $editView->name());

        $formRequest = KelompokPelajaranRequest::create('/pelajaran/kelompok-pelajaran', 'POST', [
            'kode' => 'A',
            'kelompok' => 'Kelompok A (Wajib)',
            'urutan' => 1,
        ]);
        $validator = app('validator')->make($formRequest->all(), [
            'kode' => 'required|string|max:50',
            'kelompok' => 'required|string|max:255',
            'urutan' => 'required|integer|min:1',
        ]);
        $validator->validate();
        $formRequest->setValidator($validator);

        $this->assertTrue($controller->store($formRequest)->isRedirect(route('kelompok-pelajaran.index')));
        $this->assertTrue($controller->update($formRequest, $kelompok)->isRedirect(route('kelompok-pelajaran.index')));
        $this->assertTrue($controller->destroy($kelompok)->isRedirect(route('kelompok-pelajaran.index')));
    }

    public function test_index_and_form_views_render_cleanly_without_lazy_loading(): void
    {
        view()->share('errors', new ViewErrorBag);

        $kelompok = KelompokPelajaran::make([
            'kode' => 'A',
            'kelompok' => 'Kelompok A (Umum)',
            'urutan' => 1,
        ]);
        $kelompok->id = 15;

        Model::preventLazyLoading(true);

        try {
            $indexHtml = view('admin.pelajaran.kelompokPelajaran.index', [
                'kelompokPelajaran' => new Collection([$kelompok]),
                'filters' => ['search' => ''],
                'totalKelompok' => 1,
            ])->render();

            $createHtml = view('admin.pelajaran.kelompokPelajaran.create', [
                'nextUrutan' => 4,
            ])->render();

            $editHtml = view('admin.pelajaran.kelompokPelajaran.edit', [
                'kelompokPelajaran' => $kelompok,
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('#1', $indexHtml);
        $this->assertStringContainsString('A', $indexHtml);
        $this->assertStringContainsString('Kelompok A (Umum)', $indexHtml);
        $this->assertStringContainsString(route('kelompok-pelajaran.edit', 15), $indexHtml);
        $this->assertStringContainsString(route('kelompok-pelajaran.destroy', 15), $indexHtml);

        $this->assertStringContainsString(route('kelompok-pelajaran.store'), $createHtml);
        $this->assertStringContainsString('name="kode"', $createHtml);
        $this->assertStringContainsString('name="kelompok"', $createHtml);
        $this->assertStringContainsString('name="urutan"', $createHtml);
        $this->assertStringContainsString('value="4"', $createHtml);

        $this->assertStringContainsString(route('kelompok-pelajaran.update', 15), $editHtml);
        $this->assertStringContainsString('Kelompok A (Umum)', $editHtml);
        $this->assertStringContainsString(route('kelompok-pelajaran.index'), view('layouts.sidebar')->render());
    }
}
