<?php

namespace App\Http\Controllers\Admin\DataPelengkap;

use App\Http\Controllers\Controller;
use App\Models\Admin\DataPelengkap\Pekerjaan;
use App\Http\Requests\Admin\DataPelengkap\PekerjaanRequest;
use App\Services\Admin\DataPelengkap\PekerjaanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PekerjaanController extends Controller
{
    public function __construct(
        protected PekerjaanService $service
    ) {}

    public function index(): View
    {
        return view('admin.dataPelengkap.pekerjaan.index', [
            'pekerjaan' => $this->service->getAll()
        ]);
    }

    public function create(): View
    {
        return view('admin.dataPelengkap.pekerjaan.create');
    }

    public function store(PekerjaanRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()->route('pekerjaan.index')
            ->with('success', 'Pekerjaan berhasil disimpan');
    }

    public function edit(Pekerjaan $pekerjaan): View
    {
        return view('admin.dataPelengkap.pekerjaan.edit', compact('pekerjaan'));
    }

    public function update(PekerjaanRequest $request, Pekerjaan $pekerjaan): RedirectResponse
    {
        $this->service->update($pekerjaan, $request->validated());

        return redirect()->route('pekerjaan.index')
            ->with('success', 'Pekerjaan berhasil diupdate');
    }

    public function destroy(Pekerjaan $pekerjaan): RedirectResponse
    {
        $this->service->delete($pekerjaan);

        return redirect()->route('pekerjaan.index')
            ->with('success', 'Pekerjaan berhasil dihapus');
    }
}