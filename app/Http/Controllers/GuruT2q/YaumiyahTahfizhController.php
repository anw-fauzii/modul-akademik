<?php

namespace App\Http\Controllers\GuruT2q;

use App\Http\Requests\Admin\T2q\YaumiyahTahfizhRequest;
use App\Models\Admin\T2q\YaumiyahTahfizh;
use App\Services\Admin\T2q\BaseYaumiyahT2qService;
use App\Services\Admin\T2q\YaumiyahTahfizhService;
use App\Services\GuruT2q\GuruT2qYaumiyahService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class YaumiyahTahfizhController extends BaseGuruT2qYaumiyahController
{
    public function __construct(
        private readonly GuruT2qYaumiyahService $facadeService,
        private readonly YaumiyahTahfizhService $tahfizhService,
    ) {
        $this->middleware('role:super admin|guru t2q');
        $this->middleware('can:viewAny,'.YaumiyahTahfizh::class)->only(['index']);
        $this->middleware('can:viewMatriks,'.YaumiyahTahfizh::class)->only(['matriks']);
        $this->middleware('can:viewStatistik,'.YaumiyahTahfizh::class)->only(['statistik']);
        $this->middleware('can:create,'.YaumiyahTahfizh::class)->only(['store']);
        $this->middleware('can:delete,yaumiyah_tahfizh')->only(['destroy']);
    }

    protected function modeKey(): string
    {
        return 'tahfizh';
    }

    protected function indexRouteName(): string
    {
        return 'guru-t2q.yaumiyah-tahfizh.index';
    }

    protected function indexViewName(): string
    {
        return 'guruT2q.yaumiyahTahfizh.index';
    }

    protected function matriksViewName(): string
    {
        return 'guruT2q.yaumiyahTahfizh.matriks';
    }

    protected function statistikViewName(): string
    {
        return 'guruT2q.yaumiyahTahfizh.statistik';
    }

    protected function labelJudul(): string
    {
        return 'Yaumiyah Tahfizh';
    }

    protected function yaumiyahWriteService(): BaseYaumiyahT2qService
    {
        return $this->tahfizhService;
    }

    protected function yaumiyahFacade(): GuruT2qYaumiyahService
    {
        return $this->facadeService;
    }

    protected function ineligibleHeroTitle(): string
    {
        return 'Yaumiyah Tahfizh Bimbingan T2Q';
    }

    public function store(YaumiyahTahfizhRequest $request): RedirectResponse
    {
        return $this->saveScopedBatch($request);
    }

    public function destroy(Request $request, YaumiyahTahfizh $yaumiyahTahfizh): RedirectResponse
    {
        return $this->deleteScopedEntry($request, $yaumiyahTahfizh);
    }
}
