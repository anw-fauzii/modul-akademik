<?php

namespace App\Http\Controllers\Admin\DataMaster;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DataMaster\AnggotaEkstrakurikulerRequest;
use App\Models\Admin\DataMaster\AnggotaEkstrakurikuler;
use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Services\Admin\DataMaster\AnggotaEkstrakurikulerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnggotaEkstrakurikulerController extends Controller
{
    public function __construct(
        protected AnggotaEkstrakurikulerService $service
    ) {}

    /**
     * Menampilkan daftar anggota ekstrakurikuler dengan filter tahun ajaran, ekstrakurikuler, dan kelas opsional.
     */
    public function index(Request $request): View
    {
        $tahunAjaranId = $request->integer('tahun_ajaran_id') ?: null;
        $ekstrakurikulerId = $request->integer('ekstrakurikuler_id') ?: null;
        $kelasId = $request->integer('kelas_id') ?: null;

        return view('admin.dataMaster.anggotaEkstrakurikuler.index', [
            'anggotaEkstrakurikuler' => $this->service->getAll($tahunAjaranId, $ekstrakurikulerId, $kelasId),
            'selectedTahunAjaranId' => $tahunAjaranId,
            'selectedEkstrakurikulerId' => $ekstrakurikulerId,
            'selectedKelasId' => $kelasId,
            ...$this->service->getFilterOptions(),
        ]);
    }

    /**
     * Menampilkan form tambah anggota ekstrakurikuler baru.
     */
    public function create(Request $request): View
    {
        $oldEkstrakurikulerId = $request->old('akademik_master_ekstrakuler_id');
        $targetEkstrakurikulerId = $request->integer('ekstrakurikuler_id')
            ?: (is_numeric($oldEkstrakurikulerId) ? (int) $oldEkstrakurikulerId : null);

        return view('admin.dataMaster.anggotaEkstrakurikuler.create', [
            ...$this->service->getCreateFormOptions($targetEkstrakurikulerId),
            'preselectedEkstrakurikulerId' => $targetEkstrakurikulerId,
            'preselectedPendaftaran' => $request->string('pendaftaran')->toString() ?: null,
        ]);
    }

    /**
     * Menyimpan anggota ekstrakurikuler baru ke database.
     */
    public function store(AnggotaEkstrakurikulerRequest $request): RedirectResponse
    {
        $created = $this->service->store($request->validated());
        $jumlah = $created->count();
        $redirectEkstrakurikulerId = $this->resolveEkstrakurikulerRedirect($request);

        return $this->redirectAfterMutation($redirectEkstrakurikulerId)
            ->with('success', "{$jumlah} Anggota Ekstrakurikuler berhasil disimpan.");
    }

    /**
     * Menampilkan form edit anggota ekstrakurikuler.
     */
    public function edit(Request $request, AnggotaEkstrakurikuler $anggotaEkstrakurikuler): View
    {
        $anggotaEkstrakurikuler->loadMissing([
            'ekstrakurikuler:id,akademik_master_tahun_ajaran_id',
            'anggotaKelas:id,siswa_nis',
        ]);

        $fromEkstrakurikulerId = $request->integer('from_ekstrakurikuler') ?: null;

        return view('admin.dataMaster.anggotaEkstrakurikuler.edit', [
            'anggotaEkstrakurikuler' => $anggotaEkstrakurikuler,
            'fromEkstrakurikulerId' => $fromEkstrakurikulerId,
            ...$this->service->getFormOptions($anggotaEkstrakurikuler),
        ]);
    }

    /**
     * Memperbarui data anggota ekstrakurikuler di database.
     */
    public function update(
        AnggotaEkstrakurikulerRequest $request,
        AnggotaEkstrakurikuler $anggotaEkstrakurikuler
    ): RedirectResponse {
        $this->service->update($anggotaEkstrakurikuler, $request->validated());
        $redirectEkstrakurikulerId = $this->resolveEkstrakurikulerRedirect($request);

        return $this->redirectAfterMutation($redirectEkstrakurikulerId)
            ->with('success', 'Data Anggota Ekstrakurikuler berhasil diperbarui.');
    }

    /**
     * Menghapus data anggota ekstrakurikuler dari database.
     */
    public function destroy(
        Request $request,
        AnggotaEkstrakurikuler $anggotaEkstrakurikuler
    ): RedirectResponse {
        $this->service->delete($anggotaEkstrakurikuler);
        $redirectEkstrakurikulerId = $this->resolveEkstrakurikulerRedirect($request);

        return $this->redirectAfterMutation($redirectEkstrakurikulerId)
            ->with('success', 'Data Anggota Ekstrakurikuler berhasil dihapus.');
    }

    /**
     * Menentukan ID ekstrakurikuler tujuan redirect jika request berasal dari halaman detail ekstrakurikuler.
     */
    private function resolveEkstrakurikulerRedirect(Request $request): ?int
    {
        $ekstrakurikulerId = $request->integer('redirect_to_ekstrakurikuler');

        if ($ekstrakurikulerId <= 0 || ! Ekstrakurikuler::query()->whereKey($ekstrakurikulerId)->exists()) {
            return null;
        }

        return $ekstrakurikulerId;
    }

    /**
     * Mengembalikan redirect ke detail ekstrakurikuler apabila diminta, atau ke indeks anggota ekstrakurikuler.
     */
    private function redirectAfterMutation(?int $redirectEkstrakurikulerId): RedirectResponse
    {
        if ($redirectEkstrakurikulerId !== null) {
            return redirect()->route('ekstrakurikuler.show', $redirectEkstrakurikulerId);
        }

        return redirect()->route('anggota-ekstrakurikuler.index');
    }
}
