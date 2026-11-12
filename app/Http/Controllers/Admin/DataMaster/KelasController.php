<?php

namespace App\Http\Controllers\Admin\DataMaster;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DataMaster\KelasRequest;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Services\Admin\DataMaster\KelasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KelasController extends Controller
{
    public function __construct(
        protected KelasService $service
    ) {}

    /**
     * Menampilkan daftar data kelas (default tahun ajaran aktif) beserta filter dan ringkasan tiap jenjang.
     */
    public function index(Request $request): View
    {
        return view('admin.dataMaster.kelas.index', $this->service->getIndexData($request->query()));
    }

    /**
     * Menampilkan form tambah kelas baru.
     */
    public function create(): View
    {
        return view('admin.dataMaster.kelas.create', [
            'tahunAjaran' => TahunAjaran::orderByDesc('nama_tahun_ajaran')->get(),
            'karyawan' => Karyawan::orderBy('nama_lengkap')->get(),
        ]);
    }

    /**
     * Menampilkan detail kelas, daftar anggota, dan form tambah anggota kelas langsung.
     */
    public function show(Kelas $kelas): View
    {
        return view('admin.dataMaster.kelas.show', $this->service->getShowData($kelas));
    }

    /**
     * Menyimpan data kelas baru ke database.
     */
    public function store(KelasRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()->route('kelas.index')
            ->with('success', 'Data Kelas berhasil disimpan.');
    }

    /**
     * Menampilkan form edit kelas.
     */
    public function edit(Kelas $kelas): View
    {
        return view('admin.dataMaster.kelas.edit', [
            'kelas' => $kelas,
            'tahunAjaran' => TahunAjaran::orderByDesc('nama_tahun_ajaran')->get(),
            'karyawan' => Karyawan::orderBy('nama_lengkap')->get(),
        ]);
    }

    /**
     * Memperbarui data kelas di database.
     */
    public function update(KelasRequest $request, Kelas $kelas): RedirectResponse
    {
        $this->service->update($kelas, $request->validated());

        return redirect()->route('kelas.index')
            ->with('success', 'Data Kelas berhasil diperbarui.');
    }

    /**
     * Menghapus data kelas dari database.
     */
    public function destroy(Kelas $kelas): RedirectResponse
    {
        $this->service->delete($kelas);

        return redirect()->route('kelas.index')
            ->with('success', 'Data Kelas berhasil dihapus.');
    }
}
