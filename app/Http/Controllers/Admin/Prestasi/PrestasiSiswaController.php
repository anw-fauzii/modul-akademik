<?php

namespace App\Http\Controllers\Admin\Prestasi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Prestasi\PrestasiSiswaRequest;
use App\Models\Admin\Prestasi\PrestasiSiswa;
use App\Services\Admin\Prestasi\PrestasiSiswaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrestasiSiswaController extends Controller
{
    public function __construct(
        protected PrestasiSiswaService $service
    ) {
        $this->middleware('role:super admin');
        $this->authorizeResource(PrestasiSiswa::class, 'prestasi_siswa');
    }

    /**
     * Menampilkan statistik, filter, dan daftar prestasi siswa.
     */
    public function index(Request $request): View
    {
        return view(
            'admin.prestasi.prestasiSiswa.index',
            $this->service->getIndexData($request->only([
                'tahun_ajaran_id',
                'kategori',
                'tingkat',
                'siswa_nis',
                'search',
            ]))
        );
    }

    /**
     * Menampilkan form tambah prestasi siswa baru.
     */
    public function create(Request $request): View
    {
        return view(
            'admin.prestasi.prestasiSiswa.create',
            $this->service->getFormOptions($request->query('siswa_nis'))
        );
    }

    /**
     * Menyimpan data prestasi siswa baru ke database.
     */
    public function store(PrestasiSiswaRequest $request): RedirectResponse
    {
        $this->service->store($request->validated(), $request->file('foto'));

        return redirect()->route('prestasi-siswa.index')
            ->with('success', 'Data Prestasi Siswa berhasil disimpan.');
    }

    /**
     * Menampilkan detail lengkap prestasi siswa beserta dokumentasi foto.
     */
    public function show(PrestasiSiswa $prestasi_siswa): View
    {
        return view('admin.prestasi.prestasiSiswa.show', [
            'prestasiSiswa' => $this->service->loadDetail($prestasi_siswa),
        ]);
    }

    /**
     * Menampilkan form edit prestasi siswa.
     */
    public function edit(PrestasiSiswa $prestasi_siswa): View
    {
        return view('admin.prestasi.prestasiSiswa.edit', [
            'prestasiSiswa' => $this->service->loadDetail($prestasi_siswa),
            ...$this->service->getFormOptions(),
        ]);
    }

    /**
     * Memperbarui data prestasi siswa di database.
     */
    public function update(PrestasiSiswaRequest $request, PrestasiSiswa $prestasi_siswa): RedirectResponse
    {
        $this->service->update(
            $prestasi_siswa,
            $request->validated(),
            $request->file('foto'),
            $request->boolean('remove_foto')
        );

        return redirect()->route('prestasi-siswa.index')
            ->with('success', 'Data Prestasi Siswa berhasil diperbarui.');
    }

    /**
     * Menghapus data prestasi siswa dari database.
     */
    public function destroy(PrestasiSiswa $prestasi_siswa): RedirectResponse
    {
        $this->service->delete($prestasi_siswa);

        return redirect()->route('prestasi-siswa.index')
            ->with('success', 'Data Prestasi Siswa berhasil dihapus.');
    }
}
