<?php

namespace App\Http\Controllers\Admin\DataMaster;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DataMaster\TitimangsaRequest;
use App\Models\Admin\DataMaster\Titimangsa;
use App\Services\Admin\DataMaster\TitimangsaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TitimangsaController extends Controller
{
    public function __construct(
        protected TitimangsaService $service
    ) {
        $this->middleware('role:super admin');
    }

    /**
     * Menampilkan daftar master titimangsa & pejabat penandatangan rapor.
     */
    public function index(Request $request): View
    {
        return view(
            'admin.dataMaster.titimangsa.index',
            $this->service->getIndexData($request->only(['tahun_ajaran_id', 'jenjang_kelas', 'periode']))
        );
    }

    /**
     * Menampilkan form tambah titimangsa rapor baru.
     */
    public function create(): View
    {
        return view('admin.dataMaster.titimangsa.create', $this->service->getFormOptions());
    }

    /**
     * Menyimpan pengaturan titimangsa rapor baru ke database.
     */
    public function store(TitimangsaRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()->route('titimangsa.index')
            ->with('success', 'Data Titimangsa Rapor berhasil disimpan.');
    }

    /**
     * Menampilkan form edit titimangsa rapor.
     */
    public function edit(Titimangsa $titimangsa): View
    {
        return view('admin.dataMaster.titimangsa.edit', [
            'titimangsa' => $titimangsa,
            ...$this->service->getFormOptions(),
        ]);
    }

    /**
     * Memperbarui pengaturan titimangsa rapor di database.
     */
    public function update(TitimangsaRequest $request, Titimangsa $titimangsa): RedirectResponse
    {
        $this->service->update($titimangsa, $request->validated());

        return redirect()->route('titimangsa.index')
            ->with('success', 'Data Titimangsa Rapor berhasil diperbarui.');
    }

    /**
     * Menghapus pengaturan titimangsa rapor dari database.
     */
    public function destroy(Titimangsa $titimangsa): RedirectResponse
    {
        $this->service->delete($titimangsa);

        return redirect()->route('titimangsa.index')
            ->with('success', 'Data Titimangsa Rapor berhasil dihapus.');
    }

    /**
     * Mengubah status aktif/nonaktif pengaturan titimangsa.
     */
    public function toggleStatus(Titimangsa $titimangsa): RedirectResponse
    {
        $this->service->toggleStatus($titimangsa);

        return redirect()->route('titimangsa.index')
            ->with('success', 'Status Titimangsa Rapor berhasil diperbarui.');
    }
}

