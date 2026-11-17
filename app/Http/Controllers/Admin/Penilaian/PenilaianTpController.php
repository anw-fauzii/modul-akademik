<?php

namespace App\Http\Controllers\Admin\Penilaian;

use App\Exports\Admin\Penilaian\PenilaianTpExport;
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
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Models\Admin\Penilaian\RekapMapel;
use App\Models\Admin\Penilaian\SumatifBab;
use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use App\Services\Admin\Penilaian\PenilaianTpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PenilaianTpController extends Controller
{
    public function __construct(
        protected PenilaianTpService $service
    ) {}

    /**
     * Menampilkan matriks input nilai TP per kelas, mapel, dan periode (Mid / Semester).
     */
    public function index(Request $request): View
    {
        $data = $this->service->getMatrixData($request->only([
            'tahun_ajaran_id',
            'kelas_id',
            'mata_pelajaran_id',
            'periode',
        ]));

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

        return redirect()
            ->route('penilaian-tp.index', $this->contextQueryParams(
                $kelas->akademik_master_tahun_ajaran_id,
                $kelas->id,
                $mapel->id,
                $periode
            ))
            ->with(
                'success',
                "Berhasil menyimpan {$result['saved_cells']} sel nilai untuk {$result['updated_students']} siswa dan memperbarui NA TP & Rapor."
            );
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

        return redirect()
            ->route('penilaian-tp.index', $this->contextQueryParams(
                $kelas->akademik_master_tahun_ajaran_id,
                $kelas->id,
                $mapel->id,
                $periode
            ))
            ->with(
                'success',
                "Bobot Rapor jenjang Kelas {$kelas->tingkatan_kelas} SD diperbarui menjadi {$bobotTp}% NA TP + {$bobotSumatif}% Sumatif Bab + {$bobotUjian}% Nilai Ujian."
            );
    }

    /**
     * Menambahkan kolom Sumatif Bab baru per mapel & tingkatan kelas (seragam per jenjang).
     */
    public function storeSumatifBab(SumatifBabRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $sumatifBab = $this->service->saveSumatifBab($validated);

        return redirect()
            ->route('penilaian-tp.index', $this->contextQueryParams(
                $sumatifBab->akademik_master_tahun_ajaran_id,
                $validated['kelas_id'] ?? null,
                $sumatifBab->akademik_pelajaran_mata_pelajaran_id,
                $sumatifBab->periode
            ))
            ->with('success', "Kolom Sumatif Bab \"{$sumatifBab->nama_bab}\" berhasil ditambahkan (seragam jenjang Kelas {$sumatifBab->tingkatan_kelas}).");
    }

    /**
     * Memperbarui nama, keterangan, atau urutan kolom Sumatif Bab.
     */
    public function updateSumatifBab(SumatifBabRequest $request, SumatifBab $sumatifBab): RedirectResponse
    {
        $validated = $request->validated();
        $updated = $this->service->saveSumatifBab($validated, $sumatifBab);

        return redirect()
            ->route('penilaian-tp.index', $this->contextQueryParams(
                $updated->akademik_master_tahun_ajaran_id,
                $validated['kelas_id'] ?? null,
                $updated->akademik_pelajaran_mata_pelajaran_id,
                $updated->periode
            ))
            ->with('success', "Kolom Sumatif Bab \"{$updated->nama_bab}\" berhasil diperbarui.");
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

        return redirect()
            ->route('penilaian-tp.index', $this->contextQueryParams(
                $tahunAjaranId,
                $request->input('kelas_id'),
                $mapelId,
                $periode
            ))
            ->with('success', "Kolom Sumatif Bab \"{$namaBab}\" berhasil dihapus dan NA Rapor disinkronkan ulang.");
    }

    /**
     * Mengubah pilihan apakah TP dimasukkan ke dalam Rapor Mid / Semester.
     */
    public function toggleMasukRapor(Request $request, TujuanPembelajaran $tujuanPembelajaran): RedirectResponse
    {
        $periode = strtolower(trim((string) $request->input('periode', PeriodeTp::PERIODE_MID)));
        if (! in_array($periode, [PeriodeTp::PERIODE_MID, PeriodeTp::PERIODE_SEMESTER], true)) {
            $periode = PeriodeTp::PERIODE_MID;
        }

        $masukRapor = filter_var($request->input('masuk_rapor', false), FILTER_VALIDATE_BOOLEAN);

        $this->service->toggleMasukRapor($tujuanPembelajaran, $periode, $masukRapor);

        $statusLabel = $masukRapor ? 'dimasukkan ke Rapor' : 'dikecualikan dari Rapor';
        $periodeLabel = $periode === PeriodeTp::PERIODE_MID ? 'Mid Semester' : 'Semester';

        return redirect()
            ->route('penilaian-tp.index', $this->contextQueryParams(
                $tujuanPembelajaran->akademik_master_tahun_ajaran_id,
                $request->input('kelas_id'),
                $tujuanPembelajaran->akademik_pelajaran_mata_pelajaran_id,
                $periode
            ))
            ->with('success', "{$tujuanPembelajaran->kode_tp} berhasil {$statusLabel} ({$periodeLabel}).");
    }

    /**
     * Menambahkan aspek penilaian baru pada suatu TP (seragam per jenjang).
     */
    public function storeAspek(AspekTpRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $tp = TujuanPembelajaran::query()->findOrFail((int) $validated['tujuan_pembelajaran_id']);
        $aspek = $this->service->saveAspek($tp, $validated);

        return redirect()
            ->route('penilaian-tp.index', $this->contextQueryParams(
                $tp->akademik_master_tahun_ajaran_id,
                $validated['kelas_id'] ?? null,
                $tp->akademik_pelajaran_mata_pelajaran_id,
                (string) $validated['periode']
            ))
            ->with('success', "Aspek \"{$aspek->nama_aspek}\" berhasil ditambahkan pada {$tp->kode_tp} (seragam jenjang Kelas {$tp->tingkatan_kelas}).");
    }

    /**
     * Memperbarui nama, teknik, bobot, atau urutan aspek penilaian TP.
     */
    public function updateAspek(AspekTpRequest $request, AspekTp $aspekTp): RedirectResponse
    {
        $validated = $request->validated();
        $tp = TujuanPembelajaran::query()->findOrFail((int) $validated['tujuan_pembelajaran_id']);
        $this->service->saveAspek($tp, $validated, $aspekTp);

        return redirect()
            ->route('penilaian-tp.index', $this->contextQueryParams(
                $tp->akademik_master_tahun_ajaran_id,
                $validated['kelas_id'] ?? null,
                $tp->akademik_pelajaran_mata_pelajaran_id,
                (string) $validated['periode']
            ))
            ->with('success', 'Aspek penilaian berhasil diperbarui.');
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

        return redirect()
            ->route('penilaian-tp.index', $this->contextQueryParams(
                $tp?->akademik_master_tahun_ajaran_id,
                $request->input('kelas_id'),
                $tp?->akademik_pelajaran_mata_pelajaran_id,
                $periode
            ))
            ->with('success', 'Aspek penilaian berhasil dihapus dan NA TP disinkronkan ulang.');
    }

    /**
     * Mengekspor matrix nilai TP format 2-baris header (sekaligus template siap impor).
     */
    public function export(Request $request): BinaryFileResponse
    {
        $filters = $request->only([
            'tahun_ajaran_id',
            'kelas_id',
            'mata_pelajaran_id',
            'periode',
        ]);

        $periode = ($filters['periode'] ?? 'mid') === PeriodeTp::PERIODE_SEMESTER ? 'semester' : 'mid';
        $kelasSlug = Str::slug((string) ($request->input('kelas_id', 'sd')));
        $filename = "nilai-tp-{$periode}-kelas-{$kelasSlug}-".now()->format('Ymd-His').'.xlsx';

        return Excel::download(new PenilaianTpExport($filters), $filename);
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

        $redirect = redirect()->route('penilaian-tp.index', $this->contextQueryParams(
            $kelas->akademik_master_tahun_ajaran_id,
            $kelas->id,
            $mapel->id,
            $periode
        ));

        if ($import->getErrors() !== []) {
            return $redirect
                ->with('error', 'Import nilai TP gagal. Periksa daftar error di bawah ini.')
                ->with('import_errors', array_slice($import->getErrors(), 0, 15));
        }

        return $redirect->with(
            'success',
            "Import berhasil: {$import->getSavedCells()} sel nilai diperbarui untuk {$import->getUpdatedStudents()} siswa."
        );
    }

    /**
     * Menampilkan halaman rekap nilai rapor mapel (Mid atau Semester gabungan Mid+Semester) dan deskripsi capaian.
     */
    public function rekap(Request $request): View
    {
        $data = $this->service->getRekapData($request->only([
            'tahun_ajaran_id',
            'kelas_id',
            'mata_pelajaran_id',
            'periode',
        ]));

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

        return redirect()
            ->route('penilaian-rekap.index', $this->contextQueryParams(
                $rekapMapel->akademik_master_tahun_ajaran_id,
                $validated['kelas_id'] ?? null,
                $rekapMapel->akademik_pelajaran_mata_pelajaran_id,
                $rekapMapel->periode
            ))
            ->with('success', 'Deskripsi capaian rapor berhasil diperbarui.');
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

        return redirect()
            ->route('penilaian-rekap.index', $this->contextQueryParams(
                $kelas->akademik_master_tahun_ajaran_id,
                $kelas->id,
                $mapel->id,
                (string) $validated['periode']
            ))
            ->with('success', "Draf deskripsi rapor otomatis berhasil disegarkan untuk {$count} data rekap.");
    }

    /**
     * @return array<string, mixed>
     */
    protected function contextQueryParams(
        mixed $tahunAjaranId,
        mixed $kelasId,
        mixed $mapelId,
        string $periode
    ): array {
        return array_filter([
            'tahun_ajaran_id' => $tahunAjaranId,
            'kelas_id' => $kelasId,
            'mata_pelajaran_id' => $mapelId,
            'periode' => $periode,
        ], static fn (mixed $v): bool => $v !== null && $v !== '');
    }
}
