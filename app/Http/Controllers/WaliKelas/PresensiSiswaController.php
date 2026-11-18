<?php

namespace App\Http\Controllers\WaliKelas;

use App\Http\Controllers\Concerns\ManagesPresensiSiswaActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Presensi\PresensiSiswaBulkDestroyRequest;
use App\Http\Requests\Admin\Presensi\PresensiSiswaBulkRequest;
use App\Http\Requests\Admin\Presensi\PresensiSiswaRequest;
use App\Models\Admin\Presensi\PresensiSiswa;
use App\Services\WaliKelas\WaliKelasPresensiService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PresensiSiswaController extends Controller
{
    use ManagesPresensiSiswaActions;

    public function __construct(
        private readonly WaliKelasPresensiService $service,
    ) {
        $this->middleware('role:super admin|wali kelas');
    }

    /**
     * Menampilkan matriks & log presensi khusus kelas binaan Wali Kelas / Pendamping aktif.
     */
    public function index(Request $request): View
    {
        return $this->renderScopedView(
            'admin.presensi.presensiSiswa.index',
            $this->service->getIndexPageData($request->user(), $this->extractIndexFilters($request)),
            'Presensi Siswa Wali Kelas',
            'Presensi Siswa Kelas Binaan'
        );
    }

    /**
     * Menampilkan form input presensi massal (bulk) khusus kelas binaan aktif.
     */
    public function bulk(Request $request): View
    {
        $kelasId = $request->filled('kelas_id') ? (int) $request->query('kelas_id') : null;
        $tanggal = trim((string) $request->query('tanggal', now()->format('Y-m-d')));

        return $this->renderScopedView(
            'admin.presensi.presensiSiswa.bulk',
            $this->service->getBulkPageData($request->user(), $kelasId, $tanggal),
            'Input Presensi Massal Wali Kelas',
            'Input Presensi Massal Kelas Binaan'
        );
    }

    /**
     * Menyimpan presensi massal setelah verifikasi kepemilikan kelas di tahun ajaran aktif.
     */
    public function bulkStore(PresensiSiswaBulkRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $savedCount = $this->service->storeBulkForUser($request->user(), $validated);
        $bulan = Carbon::parse($validated['tanggal'])->format('Y-m');

        return redirect()
            ->route('wali-kelas.presensi-siswa.index', [
                'kelas_id' => $validated['kelas_id'],
                'bulan' => $bulan,
            ])
            ->with('success', "Berhasil menyimpan presensi untuk {$savedCount} siswa.");
    }

    /**
     * Menghapus seluruh presensi per tanggal setelah verifikasi kepemilikan kelas aktif.
     */
    public function bulkDestroy(PresensiSiswaBulkDestroyRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $kelasId = (int) $validated['kelas_id'];
        $tanggal = Carbon::parse($validated['tanggal'])->format('Y-m-d');
        $deletedCount = $this->service->deleteBulkForUser($request->user(), $kelasId, $tanggal);

        return $this->buildBulkDestroyRedirect(
            $request,
            $kelasId,
            $tanggal,
            $deletedCount,
            'wali-kelas.presensi-siswa'
        );
    }

    /**
     * Menampilkan form edit presensi satu siswa dalam kelas binaan aktif.
     */
    public function edit(Request $request, PresensiSiswa $presensi_siswa): View
    {
        return view(
            'admin.presensi.presensiSiswa.edit',
            $this->service->getEditPageData($request->user(), $presensi_siswa)
        );
    }

    /**
     * Memperbarui catatan presensi siswa kelas binaan aktif.
     */
    public function update(PresensiSiswaRequest $request, PresensiSiswa $presensi_siswa): RedirectResponse
    {
        $this->service->updateForUser($request->user(), $presensi_siswa, $request->validated());

        return redirect()
            ->route('wali-kelas.presensi-siswa.index')
            ->with('success', 'Data presensi siswa berhasil diperbarui.');
    }

    /**
     * Menghapus satu catatan presensi siswa kelas binaan aktif.
     */
    public function destroy(Request $request, PresensiSiswa $presensi_siswa): RedirectResponse
    {
        $this->service->deleteForUser($request->user(), $presensi_siswa);

        return redirect()
            ->route('wali-kelas.presensi-siswa.index', $this->extractIndexRedirectQuery($request))
            ->with('success', 'Data presensi siswa berhasil dihapus.');
    }

    /**
     * Menampilkan rekap & persentase kehadiran kelas binaan aktif.
     */
    public function rekap(Request $request): View
    {
        return $this->renderScopedView(
            'admin.presensi.presensiSiswa.rekap',
            $this->service->getRekapPageData(
                $request->user(),
                $this->extractRekapFilters($request),
                $request->exists('kelas_id')
            ),
            'Rekap Presensi Wali Kelas',
            'Rekap & Persentase Presensi Kelas Binaan'
        );
    }

    /**
     * Menampilkan statistik presensi tahunan kelas binaan aktif.
     */
    public function tahunan(Request $request): View
    {
        return $this->renderScopedView(
            'admin.presensi.presensiSiswa.tahunan',
            $this->service->getTahunanPageData(
                $request->user(),
                $this->extractTahunanFilters($request),
                $request->exists('kelas_id')
            ),
            'Statistik Presensi Tahunan Wali Kelas',
            'Statistik Presensi Tahunan Kelas Binaan'
        );
    }

    /**
     * Menampilkan view presensi yang sama dengan Admin atau halaman guard jika belum memiliki kelas aktif.
     *
     * @param  array<string, mixed>  $data
     */
    private function renderScopedView(string $viewName, array $data, string $pageTitle, string $heroTitle): View
    {
        if (! ($data['isEligible'] ?? false)) {
            return view('waliKelas.presensi.ineligible', [
                ...$data,
                'pageTitle' => $pageTitle,
                'heroTitle' => $heroTitle,
            ]);
        }

        return view($viewName, $data);
    }
}

