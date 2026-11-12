<?php

namespace App\Http\Controllers\Admin\DataMaster;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DataMaster\AnggotaKelasRequest;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Kelas;
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
        $redirectKelasId = $this->resolveKelasRedirect($request);

        return $this->redirectAfterMutation($redirectKelasId)
            ->with('success', "{$jumlah} Anggota Kelas berhasil disimpan.");
    }

    /**
     * Menampilkan form edit anggota kelas.
     */
    public function edit(Request $request, AnggotaKelas $anggotaKelas): View
    {
        $fromKelasId = $request->integer('from_kelas') ?: null;

        return view('admin.dataMaster.anggotaKelas.edit', [
            'anggotaKelas' => $anggotaKelas,
            'fromKelasId' => $fromKelasId,
            ...$this->service->getFormOptions(),
        ]);
    }

    /**
     * Memperbarui data anggota kelas di database.
     */
    public function update(AnggotaKelasRequest $request, AnggotaKelas $anggotaKelas): RedirectResponse
    {
        $this->service->update($anggotaKelas, $request->validated());
        $redirectKelasId = $this->resolveKelasRedirect($request);

        return $this->redirectAfterMutation($redirectKelasId)
            ->with('success', 'Data Anggota Kelas berhasil diperbarui.');
    }

    /**
     * Menghapus data anggota kelas dari database.
     */
    public function destroy(Request $request, AnggotaKelas $anggotaKelas): RedirectResponse
    {
        $this->service->delete($anggotaKelas);
        $redirectKelasId = $this->resolveKelasRedirect($request);

        return $this->redirectAfterMutation($redirectKelasId)
            ->with('success', 'Data Anggota Kelas berhasil dihapus.');
    }

    /**
     * Menentukan ID kelas tujuan redirect jika request berasal dari halaman detail kelas.
     */
    private function resolveKelasRedirect(Request $request): ?int
    {
        $kelasId = $request->integer('redirect_to_kelas');

        if ($kelasId <= 0 || ! Kelas::query()->whereKey($kelasId)->exists()) {
            return null;
        }

        return $kelasId;
    }

    /**
     * Mengembalikan redirect ke detail kelas apabila diminta, atau ke indeks anggota kelas.
     */
    private function redirectAfterMutation(?int $redirectKelasId): RedirectResponse
    {
        if ($redirectKelasId !== null) {
            return redirect()->route('kelas.show', $redirectKelasId);
        }

        return redirect()->route('anggota-kelas.index');
    }
}
