<?php

namespace App\Http\Controllers\WaliKelas;

use App\Http\Controllers\Concerns\ManagesPenilaianTpActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Penilaian\AspekTpRequest;
use App\Http\Requests\Admin\Penilaian\ImportNilaiTpRequest;
use App\Http\Requests\Admin\Penilaian\SimpanNilaiTpRequest;
use App\Http\Requests\Admin\Penilaian\SumatifBabRequest;
use App\Http\Requests\Admin\Penilaian\UpdateBobotRaporRequest;
use App\Models\Admin\Penilaian\AspekTp;
use App\Models\Admin\Penilaian\RekapMapel;
use App\Models\Admin\Penilaian\SumatifBab;
use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use App\Services\WaliKelas\WaliKelasPenilaianService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PenilaianTpController extends Controller
{
    use ManagesPenilaianTpActions;

    public function __construct(
        private readonly WaliKelasPenilaianService $service
    ) {
        $this->middleware($this->allowedRoleMiddleware());
        $this->middleware('can:viewAny,'.RekapMapel::class)->only(['index', 'rekap', 'export']);
        $this->middleware('can:create,'.SumatifBab::class)->only(['store', 'updateBobot', 'storeSumatifBab', 'import']);
        $this->middleware('can:update,sumatif_bab')->only(['updateSumatifBab']);
        $this->middleware('can:delete,sumatif_bab')->only(['destroySumatifBab']);
        $this->middleware('can:create,'.AspekTp::class)->only(['storeAspek']);
        $this->middleware('can:update,aspek_tp')->only(['updateAspek']);
        $this->middleware('can:delete,aspek_tp')->only(['destroyAspek']);
        $this->middleware('can:updateDeskripsi,rekap_mapel')->only(['updateDeskripsi']);
        $this->middleware('can:regenerateDeskripsi,'.RekapMapel::class)->only(['regenerateDeskripsi']);
    }

    protected function allowedRoleMiddleware(): string
    {
        return 'role:super admin|wali kelas';
    }

    protected function routeScope(): string
    {
        return 'wali-kelas';
    }

    /**
     * Menampilkan matriks input nilai TP per kelas & mata pelajaran SD yang diampu user.
     */
    public function index(Request $request): View
    {
        $scope = $this->routeScope();

        return $this->renderScopedView(
            'admin.penilaian.nilaiTp.index',
            $this->service->getPageData($request->user(), $this->extractPenilaianFilters($request), false, $scope),
            'Penilaian Tujuan Pembelajaran (SD)',
            $scope === 'guru-mapel' ? 'Penilaian TP Mapel Saya' : 'Penilaian TP Kelas Binaan'
        );
    }

    /**
     * Menyimpan seluruh nilai grid kelas SD secara batch anti-N+1.
     */
    public function store(SimpanNilaiTpRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $saved = $this->service->saveNilaiGridForUser($request->user(), $validated);

        return $this->buildStoreRedirectResponse(
            $saved['kelas'],
            $saved['mapel'],
            (string) $validated['periode'],
            $saved['result'],
            $this->routeScope().'.penilaian-tp'
        );
    }

    /**
     * Memperbarui bobot persentase Rapor jenjang kelas SD.
     */
    public function updateBobot(UpdateBobotRaporRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $updated = $this->service->updateBobotForUser($request->user(), $validated);

        return $this->buildUpdateBobotRedirectResponse(
            $updated['kelas'],
            $updated['mapel'],
            (string) $validated['periode'],
            (int) $validated['bobot_tp'],
            (int) ($validated['bobot_sumatif'] ?? 0),
            (int) $validated['bobot_ujian'],
            $this->routeScope().'.penilaian-tp'
        );
    }

    /**
     * Menambahkan kolom Sumatif Bab pada kelas/tingkatan SD.
     */
    public function storeSumatifBab(SumatifBabRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $sumatifBab = $this->service->saveSumatifBabForUser($request->user(), $validated);

        return $this->buildSumatifRedirectResponse(
            $sumatifBab->akademik_master_tahun_ajaran_id,
            $validated['kelas_id'] ?? null,
            $sumatifBab->akademik_pelajaran_mata_pelajaran_id,
            $sumatifBab->periode,
            $this->buildSumatifCreatedMessage($sumatifBab),
            $this->routeScope().'.penilaian-tp'
        );
    }

    /**
     * Memperbarui kolom Sumatif Bab pada kelas/tingkatan SD.
     */
    public function updateSumatifBab(SumatifBabRequest $request, SumatifBab $sumatifBab): RedirectResponse
    {
        $validated = $request->validated();
        $updated = $this->service->saveSumatifBabForUser($request->user(), $validated, $sumatifBab);

        return $this->buildSumatifRedirectResponse(
            $updated->akademik_master_tahun_ajaran_id,
            $validated['kelas_id'] ?? null,
            $updated->akademik_pelajaran_mata_pelajaran_id,
            $updated->periode,
            "Kolom Sumatif Bab \"{$updated->nama_bab}\" berhasil diperbarui.",
            $this->routeScope().'.penilaian-tp'
        );
    }

    /**
     * Menghapus kolom Sumatif Bab pada kelas/tingkatan SD.
     */
    public function destroySumatifBab(Request $request, SumatifBab $sumatifBab): RedirectResponse
    {
        $tahunAjaranId = $sumatifBab->akademik_master_tahun_ajaran_id;
        $mapelId = $sumatifBab->akademik_pelajaran_mata_pelajaran_id;
        $periode = $sumatifBab->periode;
        $namaBab = $sumatifBab->nama_bab;
        $kelasId = $request->filled('kelas_id') ? (int) $request->input('kelas_id') : null;

        $this->service->deleteSumatifBabForUser($request->user(), $sumatifBab, $kelasId);

        return $this->buildSumatifRedirectResponse(
            $tahunAjaranId,
            $request->input('kelas_id'),
            $mapelId,
            $periode,
            "Kolom Sumatif Bab \"{$namaBab}\" berhasil dihapus dan NA Rapor disinkronkan ulang.",
            $this->routeScope().'.penilaian-tp'
        );
    }

    /**
     * Menambahkan aspek penilaian pada TP kelas/tingkatan SD.
     */
    public function storeAspek(AspekTpRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $tp = TujuanPembelajaran::query()->findOrFail((int) $validated['tujuan_pembelajaran_id']);
        $saved = $this->service->saveAspekForUser($request->user(), $tp, $validated);

        return $this->buildAspekRedirectResponse(
            $saved['tp'],
            $validated['kelas_id'] ?? null,
            (string) $validated['periode'],
            "Aspek \"{$saved['aspek']->nama_aspek}\" berhasil ditambahkan pada {$saved['tp']->kode_tp} (seragam jenjang Kelas {$saved['tp']->tingkatan_kelas}).",
            $this->routeScope().'.penilaian-tp'
        );
    }

    /**
     * Memperbarui aspek penilaian pada TP kelas/tingkatan SD.
     */
    public function updateAspek(AspekTpRequest $request, AspekTp $aspekTp): RedirectResponse
    {
        $validated = $request->validated();
        $tp = TujuanPembelajaran::query()->findOrFail((int) $validated['tujuan_pembelajaran_id']);
        $saved = $this->service->saveAspekForUser($request->user(), $tp, $validated, $aspekTp);

        return $this->buildAspekRedirectResponse(
            $saved['tp'],
            $validated['kelas_id'] ?? null,
            (string) $validated['periode'],
            'Aspek penilaian berhasil diperbarui.',
            $this->routeScope().'.penilaian-tp'
        );
    }

    /**
     * Menghapus aspek penilaian pada TP kelas/tingkatan SD.
     */
    public function destroyAspek(Request $request, AspekTp $aspekTp): RedirectResponse
    {
        $periode = $aspekTp->periode;
        $kelasId = $request->filled('kelas_id') ? (int) $request->input('kelas_id') : null;
        $tp = $this->service->deleteAspekForUser($request->user(), $aspekTp, $kelasId);

        return $this->buildAspekRedirectResponse(
            $tp,
            $request->input('kelas_id'),
            $periode,
            'Aspek penilaian berhasil dihapus dan NA TP disinkronkan ulang.',
            $this->routeScope().'.penilaian-tp'
        );
    }

    /**
     * Mengekspor matrix nilai TP format 2-baris header.
     */
    public function export(Request $request): BinaryFileResponse
    {
        return Excel::download(
            $this->service->createExportForUser($request->user(), $this->extractPenilaianFilters($request)),
            $this->buildExportFilename($request)
        );
    }

    /**
     * Mengimpor nilai aspek TP dari Excel/CSV.
     */
    public function import(ImportNilaiTpRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $imported = $this->service->importForUser($request->user(), $validated, $request->file('file'));

        return $this->buildImportRedirectResponse(
            $imported['kelas'],
            $imported['mapel'],
            (string) $validated['periode'],
            $imported['import'],
            $this->routeScope().'.penilaian-tp'
        );
    }

    /**
     * Menampilkan rekap nilai rapor & deskripsi capaian mapel SD yang diampu.
     */
    public function rekap(Request $request): View
    {
        $scope = $this->routeScope();

        return $this->renderScopedView(
            'admin.penilaian.rekap.index',
            $this->service->getPageData($request->user(), $this->extractPenilaianFilters($request), true, $scope),
            'Rekap Nilai & Deskripsi Rapor (SD)',
            $scope === 'guru-mapel' ? 'Rekap & Deskripsi Mapel Saya' : 'Rekap & Deskripsi Rapor Kelas Binaan'
        );
    }

    /**
     * Memperbarui deskripsi capaian rapor siswa.
     */
    public function updateDeskripsi(Request $request, RekapMapel $rekapMapel): RedirectResponse
    {
        $validated = $request->validate([
            'deskripsi_capaian' => ['nullable', 'string', 'max:2000'],
            'kelas_id' => ['nullable', 'integer'],
        ]);

        $kelasId = isset($validated['kelas_id']) ? (int) $validated['kelas_id'] : null;
        $this->service->updateDeskripsiForUser($request->user(), $rekapMapel, $validated['deskripsi_capaian'] ?? null, $kelasId);

        return $this->buildUpdateDeskripsiResponse(
            $rekapMapel,
            $validated['kelas_id'] ?? null,
            $this->routeScope().'.penilaian-rekap'
        );
    }

    /**
     * Menghasilkan ulang seluruh draf deskripsi capaian rapor.
     */
    public function regenerateDeskripsi(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kelas_id' => ['required', 'integer', 'exists:akademik_master_kelas,id'],
            'mata_pelajaran_id' => ['required', 'integer', 'exists:akademik_pelajaran_mata_pelajaran,id'],
            'periode' => ['required', 'string', 'in:mid,semester'],
        ]);

        $regenerated = $this->service->regenerateDeskripsiForUser($request->user(), $validated);

        return $this->buildRegenerateDeskripsiResponse(
            $regenerated['kelas'],
            $regenerated['mapel'],
            (string) $validated['periode'],
            $regenerated['count'],
            $this->routeScope().'.penilaian-rekap'
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function renderScopedView(string $viewName, array $data, string $pageTitle, string $heroTitle): View
    {
        if (! ($data['isEligible'] ?? false)) {
            return view('waliKelas.penilaian.ineligible', [
                ...$data,
                'pageTitle' => $pageTitle,
                'heroTitle' => $heroTitle,
            ]);
        }

        return view($viewName, $data);
    }
}
