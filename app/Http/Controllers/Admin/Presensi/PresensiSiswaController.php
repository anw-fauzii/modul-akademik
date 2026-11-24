<?php

namespace App\Http\Controllers\Admin\Presensi;

use App\Http\Controllers\Concerns\ManagesPresensiSiswaActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Presensi\FingerspotSyncRequest;
use App\Http\Requests\Admin\Presensi\PresensiSiswaBulkDestroyRequest;
use App\Http\Requests\Admin\Presensi\PresensiSiswaBulkRequest;
use App\Http\Requests\Admin\Presensi\PresensiSiswaRequest;
use App\Models\Admin\Presensi\PresensiSiswa;
use App\Services\Admin\Presensi\FingerspotSyncService;
use App\Services\Admin\Presensi\PresensiSiswaService;
use App\Support\Sqid;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PresensiSiswaController extends Controller
{
    use ManagesPresensiSiswaActions;

    public function __construct(
        private readonly PresensiSiswaService $presensiSiswaService,
        private readonly ?FingerspotSyncService $fingerspotSyncService = null,
    ) {
        $this->middleware('role:super admin');
        $this->middleware('can:viewAny,'.PresensiSiswa::class)->only(['index', 'rekap', 'tahunan']);
        $this->middleware('can:create,'.PresensiSiswa::class)->only(['bulk', 'bulkStore']);
        $this->middleware('can:bulkDestroy,'.PresensiSiswa::class)->only(['bulkDestroy']);
        $this->middleware('can:syncFingerspot,'.PresensiSiswa::class)->only(['syncFingerspot']);
        $this->middleware('can:update,presensi_siswa')->only(['edit', 'update']);
        $this->middleware('can:delete,presensi_siswa')->only(['destroy']);
    }

    /**
     * Menampilkan daftar & matriks presensi siswa.
     */
    public function index(Request $request): View
    {
        return view(
            'admin.presensi.presensiSiswa.index',
            array_merge(
                $this->presensiSiswaService->getIndexData($this->extractIndexFilters($request)),
                ['fingerspotStatus' => $this->resolveFingerspotSyncService()->getConfigurationStatus()]
            )
        );
    }

    /**
     * Menampilkan form edit presensi siswa.
     */
    public function edit(PresensiSiswa $presensi_siswa): View
    {
        $presensi_siswa->load(PresensiSiswa::MASTER_RELATIONS);

        return view('admin.presensi.presensiSiswa.edit', array_merge(
            ['presensiSiswa' => $presensi_siswa],
            $this->presensiSiswaService->getFormData(),
        ));
    }

    /**
     * Memperbarui data presensi siswa.
     */
    public function update(PresensiSiswaRequest $request, PresensiSiswa $presensi_siswa): RedirectResponse
    {
        $this->presensiSiswaService->update($presensi_siswa, $request->validated());

        return redirect()
            ->route('presensi-siswa.index')
            ->with('success', 'Data presensi siswa berhasil diperbarui.');
    }

    /**
     * Menghapus data presensi siswa.
     */
    public function destroy(Request $request, PresensiSiswa $presensi_siswa): RedirectResponse
    {
        $this->presensiSiswaService->delete($presensi_siswa);

        return redirect()
            ->route('presensi-siswa.index', Sqid::encodeQueryParams($this->extractIndexRedirectQuery($request)))
            ->with('success', 'Data presensi siswa berhasil dihapus.');
    }

    /**
     * Menghapus seluruh presensi siswa per tanggal berdasarkan kelas.
     */
    public function bulkDestroy(PresensiSiswaBulkDestroyRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $kelasId = (int) $validated['kelas_id'];
        $tanggal = Carbon::parse($validated['tanggal'])->format('Y-m-d');
        $deletedCount = $this->presensiSiswaService->deleteBulkByClassAndDate($kelasId, $tanggal);

        return $this->buildBulkDestroyRedirect($request, $kelasId, $tanggal, $deletedCount, 'presensi-siswa');
    }

    /**
     * Menampilkan form input presensi massal (bulk) per kelas dan tanggal.
     */
    public function bulk(Request $request): View
    {
        $kelasId = $request->filled('kelas_id') ? (int) $request->query('kelas_id') : null;
        $tanggal = trim((string) $request->query('tanggal', now()->format('Y-m-d')));

        return view('admin.presensi.presensiSiswa.bulk', $this->presensiSiswaService->getBulkData($kelasId, $tanggal));
    }

    /**
     * Menyimpan input presensi massal (bulk) per kelas dan tanggal.
     */
    public function bulkStore(PresensiSiswaBulkRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $savedCount = $this->presensiSiswaService->storeBulk($validated);
        $bulan = Carbon::parse($validated['tanggal'])->format('Y-m');

        return redirect()
            ->route('presensi-siswa.index', Sqid::encodeQueryParams([
                'kelas_id' => $validated['kelas_id'],
                'bulan' => $bulan,
            ]))
            ->with('success', "Berhasil menyimpan presensi untuk {$savedCount} siswa.");
    }

    /**
     * Menampilkan halaman rekap dan persentase kehadiran siswa.
     */
    public function rekap(Request $request): View
    {
        return view(
            'admin.presensi.presensiSiswa.rekap',
            $this->presensiSiswaService->getRekapData($this->extractRekapFilters($request))
        );
    }

    /**
     * Menampilkan halaman statistik presensi tahunan berdasarkan tahun ajaran.
     */
    public function tahunan(Request $request): View
    {
        return view(
            'admin.presensi.presensiSiswa.tahunan',
            $this->presensiSiswaService->getTahunanData($this->extractTahunanFilters($request))
        );
    }

    /**
     * Menarik data presensi dari mesin/API Fingerspot sesuai rentang tanggal ke presensi siswa.
     */
    public function syncFingerspot(FingerspotSyncRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $redirectQuery = Sqid::encodeQueryParams($this->extractIndexRedirectQuery($request));

        try {
            $result = $this->resolveFingerspotSyncService()->syncDateRange(
                (string) $validated['start_date'],
                (string) $validated['end_date']
            );
        } catch (ValidationException $exception) {
            return redirect()
                ->route('presensi-siswa.index', $redirectQuery)
                ->withErrors($exception->errors());
        }

        $periodLabel = $result['start_date'] === $result['end_date']
            ? Carbon::parse($result['start_date'])->translatedFormat('d M Y')
            : Carbon::parse($result['start_date'])->translatedFormat('d M Y').' s/d '.Carbon::parse($result['end_date'])->translatedFormat('d M Y');

        if ($result['clouds_success'] === 0) {
            return redirect()
                ->route('presensi-siswa.index', $redirectQuery)
                ->with('error', "Gagal menarik log Fingerspot ({$periodLabel}) dari {$result['clouds_total']} mesin. Periksa token/konektivitas API Fingerspot.");
        }

        $notes = [];
        if ($result['skipped_existing'] > 0) {
            $notes[] = "{$result['skipped_existing']} sudah tercatat";
        }
        if ($result['skipped_locked'] > 0) {
            $notes[] = "{$result['skipped_locked']} dilewati (terkunci finalisasi)";
        }
        if ($result['unmatched_pins'] !== []) {
            $notes[] = count($result['unmatched_pins']).' PIN/NIS belum terdaftar di kelas aktif';
        }
        if ($result['failed_clouds'] !== []) {
            $notes[] = count($result['failed_clouds']).' mesin gagal dihubungi';
        }

        $message = "Sinkronisasi Fingerspot ({$periodLabel}) selesai: {$result['inserted']} presensi baru disimpan ({$result['hadir']} hadir, {$result['terlambat']} terlambat)"
            .($notes !== [] ? ' — '.implode('; ', $notes).'.' : '.');

        $flashKey = $result['failed_clouds'] !== [] ? 'warning' : 'success';

        return redirect()
            ->route('presensi-siswa.index', $redirectQuery)
            ->with($flashKey, $message);
    }

    private function resolveFingerspotSyncService(): FingerspotSyncService
    {
        return $this->fingerspotSyncService ?? app(FingerspotSyncService::class);
    }
}
