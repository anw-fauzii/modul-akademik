<?php

namespace App\Http\Controllers\Admin\DataMaster;

use App\Http\Controllers\Controller;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Http\Requests\Admin\DataMaster\TahunAjaranRequest;
use App\Services\Admin\DataMaster\TahunAjaranService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TahunAjaranController extends Controller
{
    public function __construct(
        protected TahunAjaranService $service
    ) {}

    public function index(): View
    {
        return view('admin.dataMaster.tahunAjaran.index', [
            'tahunAjaran' => $this->service->getAll()
        ]);
    }

    public function create(): View
    {
        return view('admin.dataMaster.tahunAjaran.create');
    }

    public function store(TahunAjaranRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()->route('tahun-ajaran.index')
            ->with('success', 'Tahun Ajaran berhasil disimpan');
    }

    public function edit(TahunAjaran $tahunAjaran): View
    {
        return view('admin.dataMaster.tahunAjaran.edit', compact('tahunAjaran'));
    }

    public function update(TahunAjaranRequest $request, TahunAjaran $tahunAjaran): RedirectResponse
    {
        $this->service->update($tahunAjaran, $request->validated());

        return redirect()->route('tahun-ajaran.index')
            ->with('success', 'Tahun Ajaran berhasil diupdate');
    }

    public function destroy(TahunAjaran $tahunAjaran): RedirectResponse
    {
        $this->service->delete($tahunAjaran);

        return redirect()->route('tahun-ajaran.index')
            ->with('success', 'Tahun Ajaran berhasil dihapus');
    }

    public function toggleActive(TahunAjaran $tahunAjaran): RedirectResponse
    {
        $this->service->toggleActive($tahunAjaran);

        $status = $tahunAjaran->fresh()->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('tahun-ajaran.index')
            ->with('success', "Tahun Ajaran berhasil {$status}");
    }
}
