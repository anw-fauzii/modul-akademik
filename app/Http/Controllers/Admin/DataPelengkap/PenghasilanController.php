<?php

namespace App\Http\Controllers\Admin\DataPelengkap;

use App\Http\Controllers\Controller;
use App\Models\Admin\DataPelengkap\Penghasilan;
use App\Http\Requests\Admin\DataPelengkap\PenghasilanRequest;
use App\Services\Admin\DataPelengkap\PenghasilanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PenghasilanController extends Controller
{
    public function __construct(
        protected PenghasilanService $service
    ) {}

    public function index(): View
    {
        return view('admin.dataPelengkap.penghasilan.index', [
            'penghasilan' => $this->service->getAll()
        ]);
    }

    public function create(): View
    {
        return view('admin.dataPelengkap.penghasilan.create');
    }

    public function store(PenghasilanRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()->route('penghasilan.index')
            ->with('success', 'Penghasilan berhasil disimpan');
    }

    public function edit(Penghasilan $penghasilan): View
    {
        return view('admin.dataPelengkap.penghasilan.edit', compact('penghasilan'));
    }

    public function update(PenghasilanRequest $request, Penghasilan $penghasilan): RedirectResponse
    {
        $this->service->update($penghasilan, $request->validated());

        return redirect()->route('penghasilan.index')
            ->with('success', 'Penghasilan berhasil diupdate');
    }

    public function destroy(Penghasilan $penghasilan): RedirectResponse
    {
        $this->service->delete($penghasilan);

        return redirect()->route('penghasilan.index')
            ->with('success', 'Penghasilan berhasil dihapus');
    }
}
