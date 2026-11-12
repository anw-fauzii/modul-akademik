<?php

namespace App\Http\Controllers\Admin\DataMaster;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DataMaster\AnggotaJemputanRequest;
use App\Models\Admin\DataMaster\AnggotaJemputan;
use App\Models\Admin\DataMaster\Jemputan;
use App\Services\Admin\DataMaster\AnggotaJemputanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnggotaJemputanController extends Controller
{
    public function __construct(
        protected AnggotaJemputanService $service
    ) {}

    /**
     * Menampilkan daftar anggota jemputan dengan filter tahun ajaran, jemputan, dan kelas opsional.
     */
    public function index(Request $request): View
    {
        $tahunAjaranId = $request->integer('tahun_ajaran_id') ?: null;
        $jemputanId = $request->integer('jemputan_id') ?: null;
        $kelasId = $request->integer('kelas_id') ?: null;

        return view('admin.dataMaster.anggotaJemputan.index', [
            'anggotaJemputan' => $this->service->getAll($tahunAjaranId, $jemputanId, $kelasId),
            'selectedTahunAjaranId' => $tahunAjaranId,
            'selectedJemputanId' => $jemputanId,
            'selectedKelasId' => $kelasId,
            ...$this->service->getFilterOptions(),
        ]);
    }

    /**
     * Menampilkan form tambah anggota jemputan baru.
     */
    public function create(Request $request): View
    {
        $oldJemputanId = $request->old('akademik_master_jemputan_id');
        $targetJemputanId = $request->integer('jemputan_id')
            ?: (is_numeric($oldJemputanId) ? (int) $oldJemputanId : null);

        return view('admin.dataMaster.anggotaJemputan.create', [
            ...$this->service->getCreateFormOptions($targetJemputanId),
            'preselectedJemputanId' => $targetJemputanId,
            'preselectedPendaftaran' => $request->string('pendaftaran')->toString() ?: null,
        ]);
    }

    /**
     * Menyimpan anggota jemputan baru ke database.
     */
    public function store(AnggotaJemputanRequest $request): RedirectResponse
    {
        $created = $this->service->store($request->validated());
        $jumlah = $created->count();
        $redirectJemputanId = $this->resolveJemputanRedirect($request);

        return $this->redirectAfterMutation($redirectJemputanId)
            ->with('success', "{$jumlah} Anggota Jemputan berhasil disimpan.");
    }

    /**
     * Menampilkan form edit anggota jemputan.
     */
    public function edit(Request $request, AnggotaJemputan $anggotaJemputan): View
    {
        $anggotaJemputan->loadMissing([
            'jemputan:id,akademik_master_tahun_ajaran_id',
            'anggotaKelas:id,siswa_nis',
        ]);

        $fromJemputanId = $request->integer('from_jemputan') ?: null;

        return view('admin.dataMaster.anggotaJemputan.edit', [
            'anggotaJemputan' => $anggotaJemputan,
            'fromJemputanId' => $fromJemputanId,
            ...$this->service->getFormOptions($anggotaJemputan),
        ]);
    }

    /**
     * Memperbarui data anggota jemputan di database.
     */
    public function update(
        AnggotaJemputanRequest $request,
        AnggotaJemputan $anggotaJemputan
    ): RedirectResponse {
        $this->service->update($anggotaJemputan, $request->validated());
        $redirectJemputanId = $this->resolveJemputanRedirect($request);

        return $this->redirectAfterMutation($redirectJemputanId)
            ->with('success', 'Data Anggota Jemputan berhasil diperbarui.');
    }

    /**
     * Menghapus data anggota jemputan dari database.
     */
    public function destroy(
        Request $request,
        AnggotaJemputan $anggotaJemputan
    ): RedirectResponse {
        $this->service->delete($anggotaJemputan);
        $redirectJemputanId = $this->resolveJemputanRedirect($request);

        return $this->redirectAfterMutation($redirectJemputanId)
            ->with('success', 'Data Anggota Jemputan berhasil dihapus.');
    }

    /**
     * Menentukan ID jemputan tujuan redirect jika request berasal dari halaman detail jemputan.
     */
    private function resolveJemputanRedirect(Request $request): ?int
    {
        $jemputanId = $request->integer('redirect_to_jemputan');

        if ($jemputanId <= 0 || ! Jemputan::query()->whereKey($jemputanId)->exists()) {
            return null;
        }

        return $jemputanId;
    }

    /**
     * Mengembalikan redirect ke detail jemputan apabila diminta, atau ke indeks anggota jemputan.
     */
    private function redirectAfterMutation(?int $redirectJemputanId): RedirectResponse
    {
        if ($redirectJemputanId !== null) {
            return redirect()->route('jemputan.show', $redirectJemputanId);
        }

        return redirect()->route('anggota-jemputan.index');
    }
}

