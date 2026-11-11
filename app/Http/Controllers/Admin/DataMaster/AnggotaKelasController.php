<?php

namespace App\Http\Controllers\Admin\DataMaster;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DataMaster\AnggotaKelasRequest;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Services\Admin\DataMaster\AnggotaKelasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnggotaKelasController extends Controller
{
    public function __construct(
        protected AnggotaKelasService $service
    ) {}

    /**
     * Menampilkan daftar anggota kelas dengan filter tahun ajaran dan kelas opsional.
     */
    public function index(Request $request): View
    {
        $tahunAjaranId = $request->integer('tahun_ajaran_id') ?: null;
        $kelasId = $request->integer('kelas_id') ?: null;

        return view('admin.dataMaster.anggotaKelas.index', [
            'anggotaKelas' => $this->service->getAll($tahunAjaranId, $kelasId),
            'selectedTahunAjaranId' => $tahunAjaranId,
            'selectedKelasId' => $kelasId,
            ...$this->service->getFilterOptions(),
        ]);
    }

    /**
     * Menampilkan form tambah anggota kelas baru.
     */
    public function create(Request $request): View
    {
        $oldKelasId = $request->old('akademik_master_kelas_id');
        $targetKelasId = $request->integer('kelas_id')
            ?: (is_numeric($oldKelasId) ? (int) $oldKelasId : null);

        return view('admin.dataMaster.anggotaKelas.create', [
            ...$this->service->getCreateFormOptions($targetKelasId),
            'preselectedKelasId' => $targetKelasId,
            'preselectedPendaftaran' => $request->string('pendaftaran')->toString() ?: null,
        ]);
    }

    /**
     * Menyimpan anggota kelas baru ke database.
     */
    public function store(AnggotaKelasRequest $request): RedirectResponse
    {
        $created = $this->service->store($request->validated());
        $jumlah = $created->count();

        return redirect()->route('anggota-kelas.index')
            ->with('success', "{$jumlah} Anggota Kelas berhasil disimpan.");
    }

    /**
     * Menampilkan form edit anggota kelas.
     */
    public function edit(AnggotaKelas $anggotaKelas): View
    {
        return view('admin.dataMaster.anggotaKelas.edit', [
            'anggotaKelas' => $anggotaKelas,
            ...$this->service->getFormOptions(),
        ]);
    }

    /**
     * Memperbarui data anggota kelas di database.
     */
    public function update(AnggotaKelasRequest $request, AnggotaKelas $anggotaKelas): RedirectResponse
    {
        $this->service->update($anggotaKelas, $request->validated());

        return redirect()->route('anggota-kelas.index')
            ->with('success', 'Data Anggota Kelas berhasil diperbarui.');
    }

    /**
     * Menghapus data anggota kelas dari database.
     */
    public function destroy(AnggotaKelas $anggotaKelas): RedirectResponse
    {
        $this->service->delete($anggotaKelas);

        return redirect()->route('anggota-kelas.index')
            ->with('success', 'Data Anggota Kelas berhasil dihapus.');
    }
}
