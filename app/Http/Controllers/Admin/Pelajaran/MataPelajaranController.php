<?php

namespace App\Http\Controllers\Admin\Pelajaran;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Pelajaran\MataPelajaranRequest;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Services\Admin\Pelajaran\MataPelajaranService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MataPelajaranController extends Controller
{
    public function __construct(
        protected MataPelajaranService $service
    ) {}

    /**
     * Menampilkan daftar mata pelajaran beserta filter unit, kelompok, sifat, status, dan pencarian.
     */
    public function index(Request $request): View
    {
        return view(
            'admin.pelajaran.mataPelajaran.index',
            $this->service->getIndexData($request->only([
                'unit',
                'kelompok_pelajaran_id',
                'sifat',
                'status',
                'search',
            ]))
        );
    }

    /**
     * Menampilkan form tambah mata pelajaran baru.
     */
    public function create(): View
    {
        return view('admin.pelajaran.mataPelajaran.create', $this->service->getFormOptions());
    }

    /**
     * Menyimpan mata pelajaran baru ke database.
     */
    public function store(MataPelajaranRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()->route('mata-pelajaran.index')
            ->with('success', 'Data Mata Pelajaran berhasil disimpan.');
    }

    /**
     * Menampilkan form edit mata pelajaran.
     */
    public function edit(MataPelajaran $mataPelajaran): View
    {
        return view('admin.pelajaran.mataPelajaran.edit', [
            'mataPelajaran' => $mataPelajaran,
            ...$this->service->getFormOptions(),
        ]);
    }

    /**
     * Memperbarui data mata pelajaran di database.
     */
    public function update(MataPelajaranRequest $request, MataPelajaran $mataPelajaran): RedirectResponse
    {
        $this->service->update($mataPelajaran, $request->validated());

        return redirect()->route('mata-pelajaran.index')
            ->with('success', 'Data Mata Pelajaran berhasil diperbarui.');
    }

    /**
     * Menghapus data mata pelajaran dari database.
     */
    public function destroy(MataPelajaran $mataPelajaran): RedirectResponse
    {
        $this->service->delete($mataPelajaran);

        return redirect()->route('mata-pelajaran.index')
            ->with('success', 'Data Mata Pelajaran berhasil dihapus.');
    }

    /**
     * Mengubah status aktif/nonaktif mata pelajaran secara langsung dari daftar.
     */
    public function toggleStatus(MataPelajaran $mataPelajaran): RedirectResponse
    {
        $this->service->toggleStatus($mataPelajaran);

        $statusText = $mataPelajaran->fresh()?->status ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('mata-pelajaran.index')
            ->with('success', "Status Mata Pelajaran berhasil {$statusText}.");
    }
}
