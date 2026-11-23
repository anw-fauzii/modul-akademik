<?php

namespace App\Http\Controllers\GuruT2q;

use App\Http\Requests\Admin\T2q\YaumiyahTahsinRequest;
use App\Models\Admin\T2q\YaumiyahTahsin;
use App\Services\Admin\T2q\BaseYaumiyahT2qService;
use App\Services\Admin\T2q\YaumiyahTahsinService;
use App\Services\GuruT2q\GuruT2qYaumiyahService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class YaumiyahTahsinController extends BaseGuruT2qYaumiyahController
{
    public function __construct(
        private readonly GuruT2qYaumiyahService $facadeService,
        private readonly YaumiyahTahsinService $tahsinService,
    ) {
        $this->middleware('role:super admin|guru t2q');
        $this->middleware('can:viewAny,'.YaumiyahTahsin::class)->only(['index']);
        $this->middleware('can:viewMatriks,'.YaumiyahTahsin::class)->only(['matriks']);
        $this->middleware('can:viewStatistik,'.YaumiyahTahsin::class)->only(['statistik']);
        $this->middleware('can:create,'.YaumiyahTahsin::class)->only(['store']);
        $this->middleware('can:delete,yaumiyah_tahsin')->only(['destroy']);
    }

    protected function modeKey(): string
    {
        return 'tahsin';
    }

    protected function indexRouteName(): string
    {
        return 'guru-t2q.yaumiyah-tahsin.index';
    }

    protected function indexViewName(): string
    {
        return 'guruT2q.yaumiyahTahsin.index';
    }

    protected function matriksViewName(): string
    {
        return 'guruT2q.yaumiyahTahsin.matriks';
    }

    protected function statistikViewName(): string
    {
        return 'guruT2q.yaumiyahTahsin.statistik';
    }

    protected function labelJudul(): string
    {
        return 'Yaumiyah Tahsin';
    }

    protected function yaumiyahWriteService(): BaseYaumiyahT2qService
    {
        return $this->tahsinService;
    }

    protected function yaumiyahFacade(): GuruT2qYaumiyahService
    {
        return $this->facadeService;
    }

    protected function ineligibleHeroTitle(): string
    {
        return 'Yaumiyah Tahsin Bimbingan T2Q';
    }

    public function store(YaumiyahTahsinRequest $request): RedirectResponse
    {
        return $this->saveScopedBatch($request);
    }

    public function destroy(Request $request, YaumiyahTahsin $yaumiyahTahsin): RedirectResponse
    {
        return $this->deleteScopedEntry($request, $yaumiyahTahsin);
    }
}
