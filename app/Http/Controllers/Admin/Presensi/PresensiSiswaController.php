<?php

namespace App\Http\Controllers\Admin\Presensi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Presensi\PresensiSiswaBulkDestroyRequest;
use App\Http\Requests\Admin\Presensi\PresensiSiswaBulkRequest;
use App\Http\Requests\Admin\Presensi\PresensiSiswaRequest;
use App\Models\Admin\Presensi\PresensiSiswa;
use App\Services\Admin\Presensi\PresensiSiswaService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PresensiSiswaController extends Controller
{
    public function __construct(
        private readonly PresensiSiswaService $presensiSiswaService,
    ) {}

    /**
     * Menampilkan daftar & matriks presensi siswa.
     */
    public function index(Request $request): View
    {
        $filters = $request->only([
            'tanggal',
            'status',
            'kelas_id',
            'tahun_ajaran_id',
            'bulan',
        ]);
        $filters['has_explicit_tahun_ajaran'] = $request->exists('tahun_ajaran_id');
        $filters['has_explicit_filter'] = $request->hasAny(['tanggal', 'status', 'kelas_id', 'tahun_ajaran_id', 'bulan']);

        $data = $this->presensiSiswaService->getIndexData($filters);

        return view('admin.presensi.presensiSiswa.index', $data);
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
            ->route('presensi-siswa.index', $this->extractIndexRedirectQuery($request))
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
        $flashKey = $deletedCount > 0 ? 'success' : 'warning';
        $flashMessage = $deletedCount > 0
            ? "Berhasil menghapus {$deletedCount} data presensi siswa pada {$tanggal}."
            : "Tidak ada data presensi pada tanggal {$tanggal} untuk kelas yang dipilih.";

        if ($request->input('redirect_to') === 'bulk') {
            return redirect()
                ->route('presensi-siswa.bulk', [
                    'kelas_id' => $kelasId,
                    'tanggal' => $tanggal,
                ])
                ->with($flashKey, $flashMessage);
        }

        $redirectQuery = $this->extractIndexRedirectQuery($request, [
            'kelas_id' => $kelasId,
            'bulan' => Carbon::parse($tanggal)->format('Y-m'),
        ]);

        return redirect()
            ->route('presensi-siswa.index', $redirectQuery)
            ->with($flashKey, $flashMessage);
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
            ->route('presensi-siswa.index', [
                'kelas_id' => $validated['kelas_id'],
                'bulan' => $bulan,
            ])
            ->with('success', "Berhasil menyimpan presensi untuk {$savedCount} siswa.");
    }

    /**
     * Menampilkan halaman rekap dan persentase kehadiran siswa.
     */
    public function rekap(Request $request): View
    {
        $filters = $request->only([
            'tahun_ajaran_id',
            'kelas_id',
            'bulan',
            'include_weekend',
        ]);
        $filters['has_explicit_tahun_ajaran'] = $request->exists('tahun_ajaran_id');
        $filters['has_explicit_filter'] = $request->hasAny([
            'tahun_ajaran_id',
            'kelas_id',
            'bulan',
            'include_weekend',
        ]);

        return view('admin.presensi.presensiSiswa.rekap', $this->presensiSiswaService->getRekapData($filters));
    }

    /**
     * Menampilkan halaman statistik presensi tahunan berdasarkan tahun ajaran.
     */
    public function tahunan(Request $request): View
    {
        $filters = $request->only([
            'nama_tahun_ajaran',
            'tahun_ajaran_id',
            'semester',
            'jenjang',
            'kelas_id',
            'include_weekend',
        ]);
        $filters['has_explicit_filter'] = $request->hasAny([
            'nama_tahun_ajaran',
            'tahun_ajaran_id',
            'semester',
            'jenjang',
            'kelas_id',
            'include_weekend',
        ]);

        return view('admin.presensi.presensiSiswa.tahunan', $this->presensiSiswaService->getTahunanData($filters));
    }

    /**
     * Mengambil parameter query redirect yang dipertahankan saat kembali ke index.
     *
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    private function extractIndexRedirectQuery(Request $request, array $defaults = []): array
    {
        $redirectQuery = $defaults;
        if ($request->exists('redirect_tahun_ajaran_id')) {
            $redirectQuery['tahun_ajaran_id'] = (string) $request->input('redirect_tahun_ajaran_id', '');
        }
        foreach (['kelas_id', 'bulan', 'status', 'tanggal'] as $key) {
            if ($request->filled("redirect_{$key}")) {
                $redirectQuery[$key] = $request->input("redirect_{$key}");
            }
        }

        return $redirectQuery;
    }
}
