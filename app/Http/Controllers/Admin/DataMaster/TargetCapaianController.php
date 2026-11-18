<?php

namespace App\Http\Controllers\Admin\DataMaster;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DataMaster\TargetCapaianRequest;
use App\Models\Admin\DataMaster\TargetCapaian;
use App\Services\Admin\DataMaster\TargetCapaianService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TargetCapaianController extends Controller
{
    public function __construct(
        protected TargetCapaianService $service
    ) {
        $this->middleware('role:super admin');
    }

    /**
     * Menampilkan daftar target capaian T2Q beserta ringkasan dan filter.
     */
    public function index(Request $request): View
    {
        return view(
            'admin.dataMaster.targetCapaian.index',
            $this->service->getIndexData($request->only([
                'tingkat',
                'search',
            ]))
        );
    }

    /**
     * Menampilkan form tambah target capaian T2Q baru.
     */
    public function create(): View
    {
        return view('admin.dataMaster.targetCapaian.create', $this->service->getFormOptions());
    }

    /**
     * Menyimpan target capaian T2Q baru ke database.
     */
    public function store(TargetCapaianRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()->route('target-capaian.index')
            ->with('success', 'Data Target Capaian T2Q berhasil disimpan.');
    }

    /**
     * Menampilkan form edit target capaian T2Q.
     */
    public function edit(TargetCapaian $targetCapaian): View
    {
        $targetCapaian->loadMissing('tahunAjaran:id,nama_tahun_ajaran,semester,is_active');

        return view('admin.dataMaster.targetCapaian.edit', [
            'targetCapaian' => $targetCapaian,
            ...$this->service->getFormOptions(),
        ]);
    }

    /**
     * Memperbarui target capaian T2Q di database.
     */
    public function update(TargetCapaianRequest $request, TargetCapaian $targetCapaian): RedirectResponse
    {
        $this->service->update($targetCapaian, $request->validated());

        return redirect()->route('target-capaian.index')
            ->with('success', 'Data Target Capaian T2Q berhasil diperbarui.');
    }

    /**
     * Menghapus target capaian T2Q dari database.
     */
    public function destroy(TargetCapaian $targetCapaian): RedirectResponse
    {
        $this->service->delete($targetCapaian);

        return redirect()->route('target-capaian.index')
            ->with('success', 'Data Target Capaian T2Q berhasil dihapus.');
    }
}
