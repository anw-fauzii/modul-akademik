<?php

namespace App\Http\Controllers\Admin\Informasi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Informasi\PengumumanRequest;
use App\Models\Admin\Informasi\Pengumuman;
use App\Services\Admin\Informasi\PengumumanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengumumanController extends Controller
{
    public function __construct(
        protected PengumumanService $service
    ) {}

    /**
     * Menampilkan daftar pengumuman siswa beserta filter tahun ajaran dan pencarian.
     */
    public function index(Request $request): View
    {
        return view(
            'admin.informasi.pengumuman.index',
            $this->service->getIndexData($request->only(['tahun_ajaran_id', 'search']))
        );
    }

    /**
     * Menampilkan form tambah pengumuman baru.
     */
    public function create(): View
    {
        return view('admin.informasi.pengumuman.create', $this->service->getFormOptions());
    }

    /**
     * Menyimpan pengumuman baru ke database.
     */
    public function store(PengumumanRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()->route('pengumuman.index')
            ->with('success', 'Data Pengumuman berhasil disimpan.');
    }

    /**
     * Menampilkan form edit pengumuman.
     */
    public function edit(Pengumuman $pengumuman): View
    {
        return view('admin.informasi.pengumuman.edit', [
            'pengumuman' => $pengumuman,
            ...$this->service->getFormOptions(),
        ]);
    }

    /**
     * Memperbarui data pengumuman di database.
     */
    public function update(PengumumanRequest $request, Pengumuman $pengumuman): RedirectResponse
    {
        $this->service->update($pengumuman, $request->validated());

        return redirect()->route('pengumuman.index')
            ->with('success', 'Data Pengumuman berhasil diperbarui.');
    }

    /**
     * Menghapus data pengumuman dari database.
     */
    public function destroy(Pengumuman $pengumuman): RedirectResponse
    {
        $this->service->delete($pengumuman);

        return redirect()->route('pengumuman.index')
            ->with('success', 'Data Pengumuman berhasil dihapus.');
    }
}
