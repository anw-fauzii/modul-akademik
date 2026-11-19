<?php

namespace App\Http\Controllers\Admin\T2q;

use App\Http\Controllers\Concerns\ManagesYaumiyahT2qActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\T2q\YaumiyahTahfizhRequest;
use App\Models\Admin\T2q\YaumiyahTahfizh;
use App\Services\Admin\T2q\BaseYaumiyahT2qService;
use App\Services\Admin\T2q\YaumiyahTahfizhService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class YaumiyahTahfizhController extends Controller
{
    use ManagesYaumiyahT2qActions;

    public function __construct(
        protected YaumiyahTahfizhService $service
    ) {
        $this->middleware('role:super admin');
    }

    protected function yaumiyahService(): BaseYaumiyahT2qService
    {
        return $this->service;
    }

    protected function indexRouteName(): string
    {
        return 't2q.yaumiyah-tahfizh.index';
    }

    protected function indexViewName(): string
    {
        return 'admin.t2q.yaumiyahTahfizh.index';
    }

    protected function matriksViewName(): string
    {
        return 'admin.t2q.yaumiyahTahfizh.matriks';
    }

    protected function statistikViewName(): string
    {
        return 'admin.t2q.yaumiyahTahfizh.statistik';
    }

    protected function labelJudul(): string
    {
        return 'Yaumiyah Tahfizh';
    }

    /**
     * Menyimpan setoran harian Yaumiyah Tahfizh siswa.
     */
    public function store(YaumiyahTahfizhRequest $request): RedirectResponse
    {
        return $this->saveYaumiyahBatch($request);
    }

    /**
     * Menghapus satu entri Yaumiyah Tahfizh siswa.
     */
    public function destroy(Request $request, YaumiyahTahfizh $yaumiyahTahfizh): RedirectResponse
    {
        return $this->deleteYaumiyahEntry($request, $yaumiyahTahfizh);
    }
}
