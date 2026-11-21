<?php

namespace App\Http\Controllers\Admin\Penilaian;

use App\Exports\Admin\Penilaian\PenilaianTpExport;
use App\Http\Controllers\Concerns\ManagesPenilaianTpActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Penilaian\AspekTpRequest;
use App\Http\Requests\Admin\Penilaian\ImportNilaiTpRequest;
use App\Http\Requests\Admin\Penilaian\SimpanNilaiTpRequest;
use App\Http\Requests\Admin\Penilaian\SumatifBabRequest;
use App\Http\Requests\Admin\Penilaian\UpdateBobotRaporRequest;
use App\Imports\Admin\Penilaian\PenilaianTpImport;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Admin\Penilaian\AspekTp;
use App\Models\Admin\Penilaian\RekapMapel;
use App\Models\Admin\Penilaian\SumatifBab;
use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use App\Services\Admin\Penilaian\PenilaianTpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PenilaianTpController extends Controller
{
    use ManagesPenilaianTpActions;

    public function __construct(
        protected PenilaianTpService $service
    ) {
        $this->middleware('role:super admin');
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

    /**
     * Menampilkan matriks input nilai TP per kelas, mapel, dan periode (Mid / Semester).
     */
    public function index(Request $request): View
    {
        $data = $this->service->getMatrixData($this->extractPenilaianFilters($request));

        return view('admin.penilaian.nilaiTp.index', $data);
    }

    /**
     * Menyimpan seluruh nilai grid secara batch anti-N+1 dan memperbarui NA TP + NA Rapor.
     */
    public function store(SimpanNilaiTpRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $kelas = Kelas::query()->findOrFail((int) $validated['kelas_id']);
        $mapel = MataPelajaran::query()->findOrFail((int) $validated['mata_pelajaran_id']);
        $periode = (string) $validated['periode'];

        $result = $this->service->saveNilaiGrid(
            $kelas,
            $mapel,
            $periode,
            $validated['nilai'] ?? [],
            $request->user()?->no_induk,
            $validated['ujian'] ?? [],
            $validated['sumatif'] ?? []
        );

        return $this->buildStoreRedirectResponse($kelas, $mapel, $periode, $result, 'penilaian-tp');
    }

    /**
     * Memperbarui bobot persentase Rapor (NA TP, Sumatif Bab, dan Nilai Ujian PSTS/PSAS/PSAT) seragam per jenjang SD.
     */
    public function updateBobot(UpdateBobotRaporRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $kelas = Kelas::query()->findOrFail((int) $validated['kelas_id']);
        $mapel = MataPelajaran::query()->findOrFail((int) $validated['mata_pelajaran_id']);
        $periode = (string) $validated['periode'];
        $bobotTp = (int) $validated['bobot_tp'];
        $bobotSumatif = (int) ($validated['bobot_sumatif'] ?? 0);
        $bobotUjian = (int) $validated['bobot_ujian'];

        $this->service->updateBobotRapor($kelas, $mapel, $bobotTp, $bobotUjian, $bobotSumatif);

        return $this->buildUpdateBobotRedirectResponse(
            $kelas,
            $mapel,
            $periode,
            $bobotTp,
            $bobotSumatif,
            $bobotUjian,
            'penilaian-tp'
        );
    }

    /**
     * Menambahkan kolom Sumatif Bab baru per mapel & tingkatan kelas (seragam per jenjang).
     */
    public function storeSumatifBab(SumatifBabRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $sumatifBab = $this->service->saveSumatifBab($validated);

        return $this->buildSumatifRedirectResponse(
            $sumatifBab->akademik_master_tahun_ajaran_id,
            $validated['kelas_id'] ?? null,
            $sumatifBab->akademik_pelajaran_mata_pelajaran_id,
            $sumatifBab->periode,
            $this->buildSumatifCreatedMessage($sumatifBab),
            'penilaian-tp'
        );
    }

    /**
     * Memperbarui nama, keterangan, atau urutan kolom Sumatif Bab.
     */
    public function updateSumatifBab(SumatifBabRequest $request, SumatifBab $sumatifBab): RedirectResponse
    {
        $validated = $request->validated();
        $updated = $this->service->saveSumatifBab($validated, $sumatifBab);

        return $this->buildSumatifRedirectResponse(
            $updated->akademik_master_tahun_ajaran_id,
            $validated['kelas_id'] ?? null,
            $updated->akademik_pelajaran_mata_pelajaran_id,
            $updated->periode,
            "Kolom Sumatif Bab \"{$updated->nama_bab}\" berhasil diperbarui.",
            'penilaian-tp'
        );
    }

    /**
     * Menghapus kolom Sumatif Bab beserta nilainya dan menyinkronkan ulang NA Rapor.
     */
    public function destroySumatifBab(Request $request, SumatifBab $sumatifBab): RedirectResponse
    {
        $tahunAjaranId = $sumatifBab->akademik_master_tahun_ajaran_id;
        $mapelId = $sumatifBab->akademik_pelajaran_mata_pelajaran_id;
        $periode = $sumatifBab->periode;
        $namaBab = $sumatifBab->nama_bab;

        $this->service->deleteSumatifBab($sumatifBab);

        return $this->buildSumatifRedirectResponse(
            $tahunAjaranId,
            $request->input('kelas_id'),
            $mapelId,
            $periode,
            "Kolom Sumatif Bab \"{$namaBab}\" berhasil dihapus dan NA Rapor disinkronkan ulang.",
            'penilaian-tp'
        );
    }

    /**
     * Menambahkan aspek penilaian baru pada suatu TP (seragam per jenjang).
     */
    public function storeAspek(AspekTpRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $tp = TujuanPembelajaran::query()->findOrFail((int) $validated['tujuan_pembelajaran_id']);
        $aspek = $this->service->saveAspek($tp, $validated);

        return $this->buildAspekRedirectResponse(
            $tp,
            $validated['kelas_id'] ?? null,
            (string) $validated['periode'],
            "Aspek \"{$aspek->nama_aspek}\" berhasil ditambahkan pada {$tp->kode_tp} (seragam jenjang Kelas {$tp->tingkatan_kelas}).",
            'penilaian-tp'
        );
    }

    /**
     * Memperbarui nama, teknik, bobot, atau urutan aspek penilaian TP.
     */
    public function updateAspek(AspekTpRequest $request, AspekTp $aspekTp): RedirectResponse
    {
        $validated = $request->validated();
        $tp = TujuanPembelajaran::query()->findOrFail((int) $validated['tujuan_pembelajaran_id']);
        $this->service->saveAspek($tp, $validated, $aspekTp);

        return $this->buildAspekRedirectResponse(
            $tp,
            $validated['kelas_id'] ?? null,
            (string) $validated['periode'],
            'Aspek penilaian berhasil diperbarui.',
            'penilaian-tp'
        );
    }

    /**
     * Menghapus aspek penilaian dari TP dan menghitung ulang NA TP.
     */
    public function destroyAspek(Request $request, AspekTp $aspekTp): RedirectResponse
    {
        $aspekTp->loadMissing('tujuanPembelajaran');
        $tp = $aspekTp->tujuanPembelajaran;
        $periode = $aspekTp->periode;

        $this->service->deleteAspek($aspekTp);

        return $this->buildAspekRedirectResponse(
            $tp,
            $request->input('kelas_id'),
            $periode,
            'Aspek penilaian berhasil dihapus dan NA TP disinkronkan ulang.',
            'penilaian-tp'
        );
    }

    /**
     * Mengekspor matrix nilai TP format 2-baris header (sekaligus template siap impor).
     */
    public function export(Request $request): BinaryFileResponse
    {
        return Excel::download(
            new PenilaianTpExport($this->extractPenilaianFilters($request)),
            $this->buildExportFilename($request)
        );
    }

    /**
     * Mengimpor nilai aspek TP dari file Excel/CSV secara batch anti-N+1.
     */
    public function import(ImportNilaiTpRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $kelas = Kelas::query()->findOrFail((int) $validated['kelas_id']);
        $mapel = MataPelajaran::query()->findOrFail((int) $validated['mata_pelajaran_id']);
        $periode = (string) $validated['periode'];

        $import = new PenilaianTpImport($kelas, $mapel, $periode, $request->user()?->no_induk);
        Excel::import($import, $request->file('file'));

        return $this->buildImportRedirectResponse($kelas, $mapel, $periode, $import, 'penilaian-tp');
    }

    /**
     * Menampilkan halaman rekap nilai rapor mapel (Mid atau Semester gabungan Mid+Semester) dan deskripsi capaian.
     */
    public function rekap(Request $request): View
    {
        $data = $this->service->getRekapData($this->extractPenilaianFilters($request));

        return view('admin.penilaian.rekap.index', $data);
    }

    /**
     * Menyimpan penyuntingan deskripsi capaian rapor siswa secara manual.
     */
    public function updateDeskripsi(Request $request, RekapMapel $rekapMapel): RedirectResponse
    {
        $validated = $request->validate([
            'deskripsi_capaian' => ['nullable', 'string', 'max:2000'],
            'kelas_id' => ['nullable', 'integer'],
        ]);

        $this->service->updateDeskripsi($rekapMapel, $validated['deskripsi_capaian'] ?? null);

        return $this->buildUpdateDeskripsiResponse($rekapMapel, $validated['kelas_id'] ?? null, 'penilaian-rekap');
    }

    /**
     * Menghasilkan ulang seluruh draf deskripsi capaian rapor berdasarkan NA TP tertinggi & terendah.
     */
    public function regenerateDeskripsi(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kelas_id' => ['required', 'integer', 'exists:akademik_master_kelas,id'],
            'mata_pelajaran_id' => ['required', 'integer', 'exists:akademik_pelajaran_mata_pelajaran,id'],
            'periode' => ['required', 'string', 'in:mid,semester'],
        ]);

        $kelas = Kelas::query()->findOrFail((int) $validated['kelas_id']);
        $mapel = MataPelajaran::query()->findOrFail((int) $validated['mata_pelajaran_id']);
        $count = $this->service->regenerateAllDeskripsi($kelas, $mapel, (string) $validated['periode']);

        return $this->buildRegenerateDeskripsiResponse(
            $kelas,
            $mapel,
            (string) $validated['periode'],
            $count,
            'penilaian-rekap'
        );
    }
}
