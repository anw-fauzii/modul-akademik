<?php

namespace App\Http\Controllers\Admin\T2q;

use App\Http\Controllers\Concerns\ManagesYaumiyahT2qActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\T2q\YaumiyahTahsinRequest;
use App\Models\Admin\T2q\YaumiyahTahsin;
use App\Services\Admin\T2q\BaseYaumiyahT2qService;
use App\Services\Admin\T2q\YaumiyahTahsinService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class YaumiyahTahsinController extends Controller
{
    use ManagesYaumiyahT2qActions;

    public function __construct(
        protected YaumiyahTahsinService $service
    ) {
        $this->middleware('role:super admin');
    }

    protected function yaumiyahService(): BaseYaumiyahT2qService
    {
        return $this->service;
    }

    protected function indexRouteName(): string
    {
        return 't2q.yaumiyah-tahsin.index';
    }

    protected function indexViewName(): string
    {
        return 'admin.t2q.yaumiyahTahsin.index';
    }

    protected function matriksViewName(): string
    {
        return 'admin.t2q.yaumiyahTahsin.matriks';
    }

    protected function statistikViewName(): string
    {
        return 'admin.t2q.yaumiyahTahsin.statistik';
    }

    protected function labelJudul(): string
    {
        return 'Yaumiyah Tahsin';
    }

    /**
     * Menyimpan setoran harian Yaumiyah Tahsin siswa.
     */
    public function store(YaumiyahTahsinRequest $request): RedirectResponse
    {
        return $this->saveYaumiyahBatch($request);
    }

    /**
     * Menghapus satu entri Yaumiyah Tahsin siswa.
     */
    public function destroy(Request $request, YaumiyahTahsin $yaumiyahTahsin): RedirectResponse
    {
        return $this->deleteYaumiyahEntry($request, $yaumiyahTahsin);
    }
}
