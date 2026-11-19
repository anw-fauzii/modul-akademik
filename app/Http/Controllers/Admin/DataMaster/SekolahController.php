<?php

namespace App\Http\Controllers\Admin\DataMaster;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DataMaster\SekolahRequest;
use App\Models\Admin\DataMaster\Sekolah;
use App\Services\Admin\DataMaster\SekolahService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SekolahController extends Controller
{
    public function __construct(
        protected SekolahService $service
    ) {
        $this->middleware('role:super admin');
        $this->authorizeResource(Sekolah::class, 'sekolah');
        $this->middleware('can:toggleStatus,sekolah')->only(['toggleStatus']);
    }

    /**
     * Menampilkan daftar identitas satuan pendidikan per jenjang.
     */
    public function index(): View
    {
        return view('admin.dataMaster.sekolah.index', [
            'sekolahList' => $this->service->getAll(),
        ]);
    }

    /**
     * Menampilkan form tambah identitas sekolah baru.
     */
    public function create(): View
    {
        return view('admin.dataMaster.sekolah.create', $this->service->getFormOptions());
    }

    /**
     * Menyimpan identitas sekolah baru ke database.
     */
    public function store(SekolahRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()->route('sekolah.index')
            ->with('success', 'Data Identitas Sekolah berhasil disimpan.');
    }

    /**
     * Menampilkan form edit identitas sekolah.
     */
    public function edit(Sekolah $sekolah): View
    {
        return view('admin.dataMaster.sekolah.edit', [
            'sekolah' => $sekolah,
            ...$this->service->getFormOptions(),
        ]);
    }

    /**
     * Memperbarui identitas sekolah di database.
     */
    public function update(SekolahRequest $request, Sekolah $sekolah): RedirectResponse
    {
        $this->service->update($sekolah, $request->validated());

        return redirect()->route('sekolah.index')
            ->with('success', 'Data Identitas Sekolah berhasil diperbarui.');
    }

    /**
     * Menghapus data identitas sekolah dari database.
     */
    public function destroy(Sekolah $sekolah): RedirectResponse
    {
        $this->service->delete($sekolah);

        return redirect()->route('sekolah.index')
            ->with('success', 'Data Identitas Sekolah berhasil dihapus.');
    }

    /**
     * Mengubah status aktif/nonaktif profil sekolah.
     */
    public function toggleStatus(Sekolah $sekolah): RedirectResponse
    {
        $this->service->toggleStatus($sekolah);

        return redirect()->route('sekolah.index')
            ->with('success', 'Status Identitas Sekolah berhasil diperbarui.');
    }
}
