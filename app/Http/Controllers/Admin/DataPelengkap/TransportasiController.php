<?php

namespace App\Http\Controllers\Admin\DataPelengkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DataPelengkap\TransportasiRequest;
use App\Models\Admin\DataPelengkap\Transportasi;
use App\Services\Admin\DataPelengkap\TransportasiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TransportasiController extends Controller
{
    public function __construct(
        protected TransportasiService $service
    ) {
        $this->middleware('role:super admin');
        $this->authorizeResource(Transportasi::class, 'transportasi');
    }

    public function index(): View
    {
        return view('admin.dataPelengkap.transportasi.index', [
            'transportasi' => $this->service->getAll(),
        ]);
    }

    public function create(): View
    {
        return view('admin.dataPelengkap.transportasi.create');
    }

    public function store(TransportasiRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()->route('transportasi.index')
            ->with('success', 'Transportasi berhasil disimpan');
    }

    public function edit(Transportasi $transportasi): View
    {
        return view('admin.dataPelengkap.transportasi.edit', compact('transportasi'));
    }

    public function update(TransportasiRequest $request, Transportasi $transportasi): RedirectResponse
    {
        $this->service->update($transportasi, $request->validated());

        return redirect()->route('transportasi.index')
            ->with('success', 'Transportasi berhasil diupdate');
    }

    public function destroy(Transportasi $transportasi): RedirectResponse
    {
        $this->service->delete($transportasi);

        return redirect()->route('transportasi.index')
            ->with('success', 'Transportasi berhasil dihapus');
    }
}
