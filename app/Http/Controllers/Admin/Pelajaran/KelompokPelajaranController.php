<?php

namespace App\Http\Controllers\Admin\Pelajaran;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Pelajaran\KelompokPelajaranRequest;
use App\Models\Admin\Pelajaran\KelompokPelajaran;
use App\Services\Admin\Pelajaran\KelompokPelajaranService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KelompokPelajaranController extends Controller
{
    public function __construct(
        protected KelompokPelajaranService $service
    ) {}

    /**
     * Menampilkan daftar kelompok pelajaran.
     */
    public function index(Request $request): View
    {
        return view(
            'admin.pelajaran.kelompokPelajaran.index',
            $this->service->getIndexData($request->only(['search']))
        );
    }

    /**
     * Menampilkan form tambah kelompok pelajaran baru.
     */
    public function create(): View
    {
        return view('admin.pelajaran.kelompokPelajaran.create', [
            'nextUrutan' => $this->service->getNextUrutan(),
        ]);
    }

    /**
     * Menyimpan data kelompok pelajaran baru ke database.
     */
    public function store(KelompokPelajaranRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()->route('kelompok-pelajaran.index')
            ->with('success', 'Data Kelompok Pelajaran berhasil disimpan.');
    }

    /**
     * Menampilkan form edit kelompok pelajaran.
     */
    public function edit(KelompokPelajaran $kelompokPelajaran): View
    {
        return view('admin.pelajaran.kelompokPelajaran.edit', [
            'kelompokPelajaran' => $kelompokPelajaran,
        ]);
    }

    /**
     * Memperbarui data kelompok pelajaran di database.
     */
    public function update(KelompokPelajaranRequest $request, KelompokPelajaran $kelompokPelajaran): RedirectResponse
    {
        $this->service->update($kelompokPelajaran, $request->validated());

        return redirect()->route('kelompok-pelajaran.index')
            ->with('success', 'Data Kelompok Pelajaran berhasil diperbarui.');
    }

    /**
     * Menghapus data kelompok pelajaran dari database.
     */
    public function destroy(KelompokPelajaran $kelompokPelajaran): RedirectResponse
    {
        $this->service->delete($kelompokPelajaran);

        return redirect()->route('kelompok-pelajaran.index')
            ->with('success', 'Data Kelompok Pelajaran berhasil dihapus.');
    }
}
