<?php

namespace App\Http\Controllers\Admin\DataMaster;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DataMaster\SiswaRequest;
use App\Models\Admin\DataMaster\Siswa;
use App\Services\Admin\DataMaster\SiswaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SiswaController extends Controller
{
    public function __construct(
        protected SiswaService $service
    ) {}

    /**
     * Menampilkan daftar data siswa.
     */
    public function index(): View
    {
        return view('admin.dataMaster.siswa.index', [
            'siswa' => $this->service->getAll(),
        ]);
    }

    /**
     * Menampilkan form tambah siswa baru.
     */
    public function create(): View
    {
        return view('admin.dataMaster.siswa.create', $this->service->getFormOptions());
    }

    /**
     * Menyimpan data siswa baru ke database.
     */
    public function store(SiswaRequest $request): RedirectResponse
    {
        $this->service->store($request->validated(), $request->file('avatar'));

        return redirect()->route('siswa.index')
            ->with('success', 'Data Siswa berhasil disimpan.');
    }

    /**
     * Menampilkan detail lengkap profil siswa.
     */
    public function show(Siswa $siswa): View
    {
        return view('admin.dataMaster.siswa.show', [
            'siswa' => $this->service->loadDetail($siswa),
        ]);
    }

    /**
     * Menampilkan form edit data siswa.
     */
    public function edit(Siswa $siswa): View
    {
        return view('admin.dataMaster.siswa.edit', [
            'siswa' => $siswa,
            ...$this->service->getFormOptions(),
        ]);
    }

    /**
     * Memperbarui data siswa di database.
     */
    public function update(SiswaRequest $request, Siswa $siswa): RedirectResponse
    {
        $this->service->update(
            $siswa,
            $request->validated(),
            $request->file('avatar'),
            $request->boolean('remove_avatar')
        );

        return redirect()->route('siswa.index')
            ->with('success', 'Data Siswa berhasil diperbarui.');
    }

    /**
     * Menghapus data siswa dari database.
     */
    public function destroy(Siswa $siswa): RedirectResponse
    {
        $this->service->delete($siswa);

        return redirect()->route('siswa.index')
            ->with('success', 'Data Siswa berhasil dihapus.');
    }
}
