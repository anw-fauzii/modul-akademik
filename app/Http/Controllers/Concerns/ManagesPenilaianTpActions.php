<?php

namespace App\Http\Controllers\Concerns;

use App\Imports\Admin\Penilaian\PenilaianTpImport;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Models\Admin\Penilaian\RekapMapel;
use App\Models\Admin\Penilaian\SumatifBab;
use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use App\Support\Sqid;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait ManagesPenilaianTpActions
{
    /**
     * Mengekstrak filter konteks penilaian TP dari request.
     *
     * @return array<string, mixed>
     */
    protected function extractPenilaianFilters(Request $request): array
    {
        return $request->only([
            'tahun_ajaran_id',
            'kelas_id',
            'mata_pelajaran_id',
            'periode',
        ]);
    }

    /**
     * Menormalkan nilai periode (`mid` atau `semester`) dari request.
     */
    protected function normalizePeriodeInput(Request $request): string
    {
        $periode = strtolower(trim((string) $request->input('periode', PeriodeTp::PERIODE_MID)));

        return $periode === PeriodeTp::PERIODE_SEMESTER
            ? PeriodeTp::PERIODE_SEMESTER
            : PeriodeTp::PERIODE_MID;
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
        return Sqid::encodeQueryParams(array_filter([
            'tahun_ajaran_id' => $tahunAjaranId,
            'kelas_id' => $kelasId,
            'mata_pelajaran_id' => $mapelId,
            'periode' => $periode,
        ], static fn (mixed $v): bool => $v !== null && $v !== ''));
    }

    /**
     * @param  array{saved_cells: int, updated_students: int}  $result
     */
    protected function buildStoreRedirectResponse(
        Kelas $kelas,
        MataPelajaran $mapel,
        string $periode,
        array $result,
        string $tpRoutePrefix
    ): RedirectResponse {
        return redirect()
            ->route("{$tpRoutePrefix}.index", $this->contextQueryParams(
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

    protected function buildUpdateBobotRedirectResponse(
        Kelas $kelas,
        MataPelajaran $mapel,
        string $periode,
        int $bobotTp,
        int $bobotSumatif,
        int $bobotUjian,
        string $tpRoutePrefix
    ): RedirectResponse {
        return redirect()
            ->route("{$tpRoutePrefix}.index", $this->contextQueryParams(
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

    protected function buildSumatifRedirectResponse(
        int $tahunAjaranId,
        mixed $kelasId,
        int $mapelId,
        string $periode,
        string $message,
        string $tpRoutePrefix
    ): RedirectResponse {
        return redirect()
            ->route("{$tpRoutePrefix}.index", $this->contextQueryParams(
                $tahunAjaranId,
                $kelasId,
                $mapelId,
                $periode
            ))
            ->with('success', $message);
    }

    protected function buildToggleMasukRaporResponse(
        TujuanPembelajaran $tp,
        mixed $kelasId,
        string $periode,
        bool $masukRapor,
        string $tpRoutePrefix
    ): RedirectResponse {
        $statusLabel = $masukRapor ? 'dimasukkan ke Rapor' : 'dikecualikan dari Rapor';
        $periodeLabel = $periode === PeriodeTp::PERIODE_SEMESTER ? 'Semester' : 'Mid Semester';

        return redirect()
            ->route("{$tpRoutePrefix}.index", $this->contextQueryParams(
                $tp->akademik_master_tahun_ajaran_id,
                $kelasId,
                $tp->akademik_pelajaran_mata_pelajaran_id,
                $periode
            ))
            ->with('success', "{$tp->kode_tp} berhasil {$statusLabel} ({$periodeLabel}).");
    }

    protected function buildAspekRedirectResponse(
        ?TujuanPembelajaran $tp,
        mixed $kelasId,
        string $periode,
        string $message,
        string $tpRoutePrefix
    ): RedirectResponse {
        return redirect()
            ->route("{$tpRoutePrefix}.index", $this->contextQueryParams(
                $tp?->akademik_master_tahun_ajaran_id,
                $kelasId,
                $tp?->akademik_pelajaran_mata_pelajaran_id,
                $periode
            ))
            ->with('success', $message);
    }

    protected function buildImportRedirectResponse(
        Kelas $kelas,
        MataPelajaran $mapel,
        string $periode,
        PenilaianTpImport $import,
        string $tpRoutePrefix
    ): RedirectResponse {
        $redirect = redirect()->route("{$tpRoutePrefix}.index", $this->contextQueryParams(
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

    protected function buildExportFilename(Request $request): string
    {
        $periode = $request->input('periode') === PeriodeTp::PERIODE_SEMESTER ? 'semester' : 'mid';
        $kelasSlug = Str::slug((string) ($request->input('kelas_id', 'sd')));

        return "nilai-tp-{$periode}-kelas-{$kelasSlug}-".now()->format('Ymd-His').'.xlsx';
    }

    protected function buildUpdateDeskripsiResponse(
        RekapMapel $rekapMapel,
        mixed $kelasId,
        string $rekapRoutePrefix
    ): RedirectResponse {
        return redirect()
            ->route("{$rekapRoutePrefix}.index", $this->contextQueryParams(
                $rekapMapel->akademik_master_tahun_ajaran_id,
                $kelasId,
                $rekapMapel->akademik_pelajaran_mata_pelajaran_id,
                $rekapMapel->periode
            ))
            ->with('success', 'Deskripsi capaian rapor berhasil diperbarui.');
    }

    protected function buildRegenerateDeskripsiResponse(
        Kelas $kelas,
        MataPelajaran $mapel,
        string $periode,
        int $count,
        string $rekapRoutePrefix
    ): RedirectResponse {
        return redirect()
            ->route("{$rekapRoutePrefix}.index", $this->contextQueryParams(
                $kelas->akademik_master_tahun_ajaran_id,
                $kelas->id,
                $mapel->id,
                $periode
            ))
            ->with('success', "Draf deskripsi rapor otomatis berhasil disegarkan untuk {$count} data rekap.");
    }

    protected function buildSumatifCreatedMessage(SumatifBab $sumatifBab): string
    {
        return "Kolom Sumatif Bab \"{$sumatifBab->nama_bab}\" berhasil ditambahkan (seragam jenjang Kelas {$sumatifBab->tingkatan_kelas}).";
    }
}
