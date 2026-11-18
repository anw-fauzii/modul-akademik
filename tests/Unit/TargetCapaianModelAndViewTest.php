<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\DataMaster\TargetCapaianController;
use App\Http\Requests\Admin\DataMaster\TargetCapaianRequest;
use App\Models\Admin\DataMaster\DaftarJilid;
use App\Models\Admin\DataMaster\Surah;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\DataMaster\TargetCapaian;
use App\Services\Admin\DataMaster\TargetCapaianService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class TargetCapaianModelAndViewTest extends TestCase
{
    public function test_target_capaian_metadata_relations_and_helpers_follow_dry_and_anti_n_plus_one(): void
    {
        $model = new TargetCapaian;

        $this->assertSame('akademik_master_t2q_target_capaian', $model->getTable());
        $this->assertSame([
            'akademik_master_tahun_ajaran_id',
            'tingkat',
            'akademik_master_t2q_daftar_jilid_id',
            'akademik_master_t2q_surah_id',
        ], $model->getFillable());

        $casts = $model->getCasts();
        $this->assertSame('integer', $casts['akademik_master_tahun_ajaran_id'] ?? null);
        $this->assertSame('integer', $casts['akademik_master_t2q_daftar_jilid_id'] ?? null);
        $this->assertSame('integer', $casts['akademik_master_t2q_surah_id'] ?? null);

        $this->assertSame([
            'tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
            'daftarJilid:id,jilid_latin,jilid_arab,jenis,urutan',
            'surah:id,nama_surah,nama_surah_arab,jumlah_ayat',
        ], TargetCapaian::INDEX_RELATIONS);

        $this->assertSame('1', TargetCapaian::normalizeTingkat('  1 '));
        $this->assertLessThan(0, TargetCapaian::compareTingkatOrder('PG', 'A'));
        $this->assertLessThan(0, TargetCapaian::compareTingkatOrder('B', '1'));
        $this->assertLessThan(0, TargetCapaian::compareTingkatOrder('1', '6'));
    }

    #[TestWith(['PG', 'badge-warning text-dark', 'tc-tingkat-pill--tk'])]
    #[TestWith(['A', 'badge-warning text-dark', 'tc-tingkat-pill--tk'])]
    #[TestWith(['1', 'badge-success', 'tc-tingkat-pill--sd'])]
    #[TestWith(['6', 'badge-success', 'tc-tingkat-pill--sd'])]
    #[TestWith(['7', 'badge-primary', 'tc-tingkat-pill--smp'])]
    public function test_accessors_build_badge_and_ringkasan_without_lazy_loading(
        string $tingkat,
        string $expectedBadgeClass,
        string $expectedPillClass
    ): void {
        $jilid = DaftarJilid::make([
            'jilid_latin' => 'Jilid 2',
            'jilid_arab' => 'المجلد ٢',
            'jenis' => 'halaman',
            'urutan' => 2,
        ]);
        $surah = Surah::make([
            'nama_surah' => 'An-Naba',
            'nama_surah_arab' => 'النبإ',
            'jumlah_ayat' => 40,
        ]);

        $target = TargetCapaian::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'tingkat' => $tingkat,
            'akademik_master_t2q_daftar_jilid_id' => 2,
            'akademik_master_t2q_surah_id' => 78,
        ]);

        Model::preventLazyLoading(true);
        try {
            $this->assertSame('-', $target->ringkasan_target);
            $target->setRelation('daftarJilid', $jilid);
            $target->setRelation('surah', $surah);
            $this->assertSame('Jilid 2 • An-Naba (40 Ayat)', $target->ringkasan_target);
            $this->assertSame($expectedBadgeClass, $target->tingkat_badge_class);
            $this->assertSame($expectedPillClass, $target->tingkat_pill_class);
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    public function test_service_build_index_query_selects_minimal_columns_eager_loads_and_escapes_search(): void
    {
        $service = new TargetCapaianService;

        $builder = $service->buildIndexQuery([
            'tahun_ajaran_id' => '5',
            'tingkat' => ' 3 ',
            'search' => '100%_naba',
        ]);

        $query = $builder->getQuery();
        $this->assertSame([
            'id',
            'akademik_master_tahun_ajaran_id',
            'tingkat',
            'akademik_master_t2q_daftar_jilid_id',
            'akademik_master_t2q_surah_id',
            'created_at',
        ], $query->columns);

        $eagerLoads = array_keys($builder->getEagerLoads());
        $this->assertSame(['tahunAjaran', 'daftarJilid', 'surah'], $eagerLoads);

        $bindings = $builder->getBindings();
        $this->assertContains(5, $bindings);
        $this->assertContains('3', $bindings);
        $this->assertContains('%100\%\_naba%', $bindings);
    }

    public function test_request_normalizes_empty_optional_ids_and_requires_at_least_jilid_or_surah(): void
    {
        $request = TargetCapaianRequest::create('/data-master/target-capaian', 'POST', [
            'akademik_master_tahun_ajaran_id' => '1',
            'tingkat' => '  2  ',
            'akademik_master_t2q_daftar_jilid_id' => '',
            'akademik_master_t2q_surah_id' => '',
        ]);

        $prepare = \Closure::bind(function (): void {
            $this->prepareForValidation();
        }, $request, TargetCapaianRequest::class);
        $prepare();

        $this->assertSame('2', $request->input('tingkat'));
        $this->assertNull($request->input('akademik_master_t2q_daftar_jilid_id'));
        $this->assertNull($request->input('akademik_master_t2q_surah_id'));

        $rules = $request->rules();
        $rules['akademik_master_tahun_ajaran_id'] = ['required', 'integer'];
        $rules['akademik_master_t2q_daftar_jilid_id'] = ['nullable', 'integer', 'required_without:akademik_master_t2q_surah_id'];
        $rules['akademik_master_t2q_surah_id'] = ['nullable', 'integer', 'required_without:akademik_master_t2q_daftar_jilid_id'];

        $validator = Validator::make($request->all(), $rules, $request->messages());
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('akademik_master_t2q_daftar_jilid_id'));
        $this->assertTrue($validator->errors()->has('akademik_master_t2q_surah_id'));
    }

    public function test_controller_routes_and_views_render_cleanly_without_lazy_loading(): void
    {
        $this->assertTrue(Route::has('target-capaian.index'));
        $this->assertTrue(Route::has('target-capaian.create'));
        $this->assertTrue(Route::has('target-capaian.store'));
        $this->assertTrue(Route::has('target-capaian.edit'));
        $this->assertTrue(Route::has('target-capaian.update'));
        $this->assertTrue(Route::has('target-capaian.destroy'));

        $ta = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);
        $ta->id = 1;

        $jilid = DaftarJilid::make([
            'jilid_latin' => 'Jilid 2',
            'jilid_arab' => 'المجلد ٢',
            'jenis' => 'halaman',
            'urutan' => 2,
        ]);
        $jilid->id = 2;

        $surah = Surah::make([
            'nama_surah' => 'An-Naba',
            'nama_surah_arab' => 'النبأ',
            'jumlah_ayat' => 40,
        ]);
        $surah->id = 78;

        $target = TargetCapaian::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'tingkat' => '1',
            'akademik_master_t2q_daftar_jilid_id' => 2,
            'akademik_master_t2q_surah_id' => 78,
        ]);
        $target->id = 10;
        $target->setRelation('tahunAjaran', $ta);
        $target->setRelation('daftarJilid', $jilid);
        $target->setRelation('surah', $surah);

        $indexData = [
            'targetCapaian' => new Collection([$target]),
            'activeTahunAjaran' => $ta,
            'filters' => [
                'tingkat' => '',
                'search' => '',
            ],
            'tingkatOptions' => ['PG', 'A', 'B', '1', '2'],
        ];

        $formOptions = [
            'activeTahunAjaran' => $ta,
            'tingkatOptions' => ['PG', 'A', 'B', '1', '2'],
            'daftarJilid' => new Collection([$jilid]),
            'surahList' => new Collection([$surah]),
        ];

        $service = $this->createMock(TargetCapaianService::class);
        $service->expects($this->once())->method('getIndexData')->willReturn($indexData);
        $service->expects($this->exactly(2))->method('getFormOptions')->willReturn($formOptions);
        $service->expects($this->once())->method('store')->willReturn($target);
        $service->expects($this->once())->method('update')->with($target, $this->isArray())->willReturn(true);
        $service->expects($this->once())->method('delete')->with($target)->willReturn(true);

        $controller = new TargetCapaianController($service);
        $indexView = $controller->index(Request::create('/data-master/target-capaian', 'GET'));
        $createView = $controller->create();
        $editView = $controller->edit($target);

        $this->assertSame('admin.dataMaster.targetCapaian.index', $indexView->name());
        $this->assertSame('admin.dataMaster.targetCapaian.create', $createView->name());
        $this->assertSame('admin.dataMaster.targetCapaian.edit', $editView->name());

        $formRequest = TargetCapaianRequest::create('/data-master/target-capaian', 'POST', [
            'akademik_master_tahun_ajaran_id' => 1,
            'tingkat' => '1',
            'akademik_master_t2q_daftar_jilid_id' => 2,
            'akademik_master_t2q_surah_id' => 78,
        ]);
        $formRequest->setValidator(Validator::make($formRequest->all(), []));

        $this->assertInstanceOf(RedirectResponse::class, $controller->store($formRequest));
        $this->assertInstanceOf(RedirectResponse::class, $controller->update($formRequest, $target));
        $this->assertInstanceOf(RedirectResponse::class, $controller->destroy($target));

        Model::preventLazyLoading(true);
        try {
            $indexHtml = $indexView->render();
            $createHtml = $createView->with('errors', new ViewErrorBag)->render();
            $editHtml = $editView->with('errors', new ViewErrorBag)->render();
            $sidebarHtml = view('layouts.sidebar')->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('Target Capaian T2Q', $indexHtml);
        $this->assertStringContainsString('2026/2027 - Semester 1', $indexHtml);
        $this->assertStringContainsString('tc-ta-badge', $indexHtml);
        $this->assertStringContainsString('tc-item-box', $indexHtml);
        $this->assertStringContainsString('tc-tingkat-pill--sd', $indexHtml);
        $this->assertStringNotContainsString('class="tc-summary-grid', $indexHtml);
        $this->assertStringContainsString('Tingkat 1', $indexHtml);
        $this->assertStringContainsString('Jilid 2', $indexHtml);
        $this->assertStringContainsString('المجلد ٢', $indexHtml);
        $this->assertStringContainsString('An-Naba', $indexHtml);
        $this->assertStringContainsString('40 Ayat', $indexHtml);
        $this->assertStringContainsString('action-dropdown-item', $indexHtml);
        $this->assertStringContainsString(route('target-capaian.edit', 10), $indexHtml);

        $this->assertStringContainsString('Tambah Target Capaian T2Q', $createHtml);
        $this->assertStringContainsString('type="hidden" name="akademik_master_tahun_ajaran_id" value="1"', $createHtml);
        $this->assertStringNotContainsString('<select name="akademik_master_tahun_ajaran_id"', $createHtml);
        $this->assertStringContainsString(route('target-capaian.store'), $createHtml);

        $this->assertStringContainsString('Edit Target Capaian T2Q', $editHtml);
        $this->assertStringContainsString('type="hidden" name="akademik_master_tahun_ajaran_id" value="1"', $editHtml);
        $this->assertStringContainsString(route('target-capaian.update', 10), $editHtml);

        $this->assertStringContainsString('Target Capaian', $sidebarHtml);
        $this->assertStringContainsString(route('target-capaian.index'), $sidebarHtml);
    }
}
