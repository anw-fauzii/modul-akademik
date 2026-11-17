<?php

namespace App\Imports\Admin\Penilaian;

use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Admin\Penilaian\AspekTp;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Models\Admin\Penilaian\SumatifBab;
use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use App\Services\Admin\Penilaian\PenilaianTpService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;

class PenilaianTpSheetImport implements SkipsEmptyRows, ToCollection
{
    public int $savedCells = 0;

    public int $updatedStudents = 0;

    /**
     * @var list<string>
     */
    public array $errors = [];

    public function __construct(
        protected Kelas $kelas,
        protected MataPelajaran $mapel,
        protected string $periode,
        protected ?string $dinilaiOlehNipy = null
    ) {}

    /**
     * Mengimpor sheet 2-baris header nilai TP (Kode TP di baris 1, Nama Aspek di baris 2) secara batch anti-N+1.
     *
     * @param  Collection<array-key, mixed>  $collection
     */
    public function collection(Collection $collection): void
    {
        $this->savedCells = 0;
        $this->updatedStudents = 0;
        $this->errors = [];

        if ($collection->count() < 3) {
            $this->errors[] = 'File Excel tidak lengkap. Gunakan format template Export Nilai TP (minimal baris header 1, baris aspek 2, dan data siswa).';

            return;
        }

        $rows = $collection->values();
        $tpHeaderRow = $this->rowToIndexedArray($rows->get(0));
        $aspekHeaderRow = $this->rowToIndexedArray($rows->get(1));

        $tpMap = TujuanPembelajaran::query()
            ->select(['id', 'kode_tp'])
            ->where('akademik_master_tahun_ajaran_id', $this->kelas->akademik_master_tahun_ajaran_id)
            ->where('akademik_pelajaran_mata_pelajaran_id', $this->mapel->id)
            ->where('tingkatan_kelas', $this->kelas->tingkatan_kelas)
            ->where('status', true)
            ->get()
            ->keyBy(static fn (TujuanPembelajaran $tp): string => strtoupper(trim($tp->kode_tp)));

        $tpIds = $tpMap->pluck('id')->all();
        $aspekLookup = [];

        if ($tpIds !== []) {
            $aspekRows = AspekTp::query()
                ->select(['id', 'tujuan_pembelajaran_id', 'nama_aspek'])
                ->whereIn('tujuan_pembelajaran_id', $tpIds)
                ->where('periode', $this->periode)
                ->get();

            foreach ($aspekRows as $aspek) {
                $normName = mb_strtolower(trim($aspek->nama_aspek));
                $aspekLookup["{$aspek->tujuan_pembelajaran_id}:{$normName}"] = $aspek->id;
            }
        }

        $sumatifLookup = SumatifBab::query()
            ->select(['id', 'nama_bab'])
            ->where('akademik_master_tahun_ajaran_id', $this->kelas->akademik_master_tahun_ajaran_id)
            ->where('akademik_pelajaran_mata_pelajaran_id', $this->mapel->id)
            ->where('tingkatan_kelas', (string) $this->kelas->tingkatan_kelas)
            ->where('periode', $this->periode)
            ->get()
            ->mapWithKeys(static fn (SumatifBab $bab): array => [mb_strtolower(trim($bab->nama_bab)) => $bab->id])
            ->all();

        $nisHeaderIdx = $this->detectNisColumnIndex($tpHeaderRow, $aspekHeaderRow);
        if ($nisHeaderIdx === null) {
            $this->errors[] = 'Kolom "NIS" tidak ditemukan pada header Excel.';

            return;
        }

        // Petakan tiap kolom aspek TP, Sumatif Bab, dan Ujian dari 2 baris header (mendukung merge carry-forward)
        $colToAspekId = [];
        $colToSumatifId = [];
        $ujianColIdx = null;
        $maxCol = max(count($tpHeaderRow), count($aspekHeaderRow));
        $currentKodeTp = '';
        $currentSection = '';

        for ($colIdx = $nisHeaderIdx + 1; $colIdx < $maxCol; $colIdx++) {
            $rawTop = strtoupper(trim((string) ($tpHeaderRow[$colIdx] ?? '')));
            $rawSub = trim((string) ($aspekHeaderRow[$colIdx] ?? ''));
            $upperSub = strtoupper($rawSub);

            if (in_array($rawTop, ['PSTS', 'PSAS', 'PSAT', 'STS', 'SAS', 'UJIAN', 'NILAI UJIAN', 'PTS', 'PAS'], true)) {
                $ujianColIdx = $colIdx;
                $currentKodeTp = '';
                $currentSection = '';

                continue;
            }

            if (in_array($rawTop, ['NA RAPOR', 'PREDIKAT', 'KETUNTASAN', 'STATUS KKM'], true)) {
                $currentKodeTp = '';
                $currentSection = '';

                continue;
            }

            if (in_array($rawTop, ['SUMATIF BAB', 'SUMATIF'], true)) {
                $currentSection = 'sumatif';
                $currentKodeTp = '';
            } elseif ($rawTop !== '') {
                $currentSection = 'tp';
                $currentKodeTp = $rawTop;
            }

            if ($rawSub === '') {
                continue;
            }

            if ($currentSection === 'sumatif') {
                if (in_array($upperSub, ['RATA SUMATIF', 'RATA', 'RATA-RATA'], true)) {
                    continue;
                }

                $lowerSub = mb_strtolower($rawSub);
                if ($this->periode === PeriodeTp::PERIODE_SEMESTER && str_ends_with($lowerSub, '(mid)')) {
                    continue;
                }

                $babId = $sumatifLookup[$lowerSub] ?? null;
                if ($babId !== null) {
                    $colToSumatifId[$colIdx] = $babId;
                }

                continue;
            }

            if ($currentKodeTp === '') {
                continue;
            }

            if ($upperSub === 'NA TP' || $upperSub === 'NILAI' || $upperSub === 'HURUF' || $upperSub === 'KKM' || $upperSub === 'UJIAN') {
                continue;
            }

            $tp = $tpMap->get($currentKodeTp);
            if ($tp === null) {
                continue;
            }

            $aspekId = $aspekLookup["{$tp->id}:".mb_strtolower($rawSub)] ?? null;
            if ($aspekId !== null) {
                $colToAspekId[$colIdx] = $aspekId;
            }
        }

        if ($colToAspekId === [] && $colToSumatifId === [] && $ujianColIdx === null) {
            $this->errors[] = 'Tidak ada kolom aspek TP, Sumatif Bab, maupun kolom Ujian (PSTS/PSAS/PSAT) yang cocok dengan konfigurasi kelas ini.';

            return;
        }

        $anggotaByNis = AnggotaKelas::query()
            ->select(['id', 'siswa_nis'])
            ->where('akademik_master_kelas_id', $this->kelas->id)
            ->get()
            ->keyBy(static fn (AnggotaKelas $a): string => trim((string) $a->siswa_nis));

        $nilaiMatrix = [];
        $sumatifMatrix = [];
        $ujianMatrix = [];

        for ($r = 2; $r < $rows->count(); $r++) {
            $studentRow = $this->rowToIndexedArray($rows->get($r));
            $nis = trim((string) ($studentRow[$nisHeaderIdx] ?? ''));

            if ($nis === '') {
                continue;
            }

            $anggota = $anggotaByNis->get($nis);
            if ($anggota === null) {
                $excelLine = $r + 1;
                $this->errors[] = "Baris {$excelLine}: Siswa dengan NIS \"{$nis}\" tidak terdaftar pada kelas {$this->kelas->nama_kelas}.";

                continue;
            }

            $excelLine = $r + 1;

            if ($colToAspekId !== []) {
                $rowAspekValues = $this->extractScoreColumns($colToAspekId, $studentRow, $excelLine, $nis, 'Nilai');
                if ($rowAspekValues === null) {
                    continue;
                }
                $nilaiMatrix[$anggota->id] = $rowAspekValues;
            }

            if ($colToSumatifId !== []) {
                $rowSumatifValues = $this->extractScoreColumns($colToSumatifId, $studentRow, $excelLine, $nis, 'Nilai Sumatif Bab');
                if ($rowSumatifValues === null) {
                    continue;
                }
                $sumatifMatrix[$anggota->id] = $rowSumatifValues;
            }

            if ($ujianColIdx !== null) {
                $rawUjianCell = $studentRow[$ujianColIdx] ?? null;
                if ($rawUjianCell === null || trim((string) $rawUjianCell) === '') {
                    $ujianMatrix[$anggota->id] = null;
                } else {
                    $normalizedUjianText = str_replace(',', '.', trim((string) $rawUjianCell));
                    if (! is_numeric($normalizedUjianText)) {
                        $this->errors[] = "Baris {$excelLine} (NIS {$nis}): Nilai Ujian \"{$rawUjianCell}\" harus berupa angka 0–100.";

                        continue;
                    }

                    $numericUjian = (float) $normalizedUjianText;
                    if ($numericUjian < 0 || $numericUjian > 100) {
                        $this->errors[] = "Baris {$excelLine} (NIS {$nis}): Nilai Ujian {$numericUjian} di luar rentang 0–100.";

                        continue;
                    }

                    $ujianMatrix[$anggota->id] = $numericUjian;
                }
            }
        }

        if ($this->errors !== [] || ($nilaiMatrix === [] && $sumatifMatrix === [] && $ujianMatrix === [])) {
            if ($nilaiMatrix === [] && $sumatifMatrix === [] && $ujianMatrix === [] && $this->errors === []) {
                $this->errors[] = 'Tidak ada baris nilai siswa yang dapat diimpor.';
            }

            return;
        }

        $result = app(PenilaianTpService::class)->saveNilaiGrid(
            $this->kelas,
            $this->mapel,
            $this->periode,
            $nilaiMatrix,
            $this->dinilaiOlehNipy,
            $ujianMatrix,
            $sumatifMatrix
        );

        $this->savedCells = $result['saved_cells'];
        $this->updatedStudents = $result['updated_students'];
    }

    /**
     * Mengekstrak dan memvalidasi sel angka 0–100 untuk kolom aspek TP maupun Sumatif Bab.
     *
     * @param  array<int, int>  $colToTargetId
     * @param  array<int, mixed>  $studentRow
     * @return array<int, float|null>|null
     */
    protected function extractScoreColumns(
        array $colToTargetId,
        array $studentRow,
        int $excelLine,
        string $nis,
        string $label
    ): ?array {
        $values = [];

        foreach ($colToTargetId as $colIdx => $targetId) {
            $rawCell = $studentRow[$colIdx] ?? null;

            if ($rawCell === null || trim((string) $rawCell) === '') {
                $values[$targetId] = null;

                continue;
            }

            $normalizedText = str_replace(',', '.', trim((string) $rawCell));
            if (! is_numeric($normalizedText)) {
                $this->errors[] = "Baris {$excelLine} (NIS {$nis}): {$label} \"{$rawCell}\" harus berupa angka 0–100.";

                return null;
            }

            $numeric = (float) $normalizedText;
            if ($numeric < 0 || $numeric > 100) {
                $this->errors[] = "Baris {$excelLine} (NIS {$nis}): {$label} {$numeric} di luar rentang 0–100.";

                return null;
            }

            $values[$targetId] = $numeric;
        }

        return $values;
    }

    /**
     * @return array<int, mixed>
     */
    protected function rowToIndexedArray(mixed $rawRow): array
    {
        if ($rawRow instanceof Collection) {
            return $rawRow->values()->all();
        }

        return is_array($rawRow) ? array_values($rawRow) : [];
    }

    /**
     * @param  array<int, mixed>  $tpHeaderRow
     * @param  array<int, mixed>  $aspekHeaderRow
     */
    protected function detectNisColumnIndex(array $tpHeaderRow, array $aspekHeaderRow): ?int
    {
        foreach ($tpHeaderRow as $idx => $cell) {
            if (strtoupper(trim((string) $cell)) === 'NIS') {
                return (int) $idx;
            }
        }

        foreach ($aspekHeaderRow as $idx => $cell) {
            if (strtoupper(trim((string) $cell)) === 'NIS') {
                return (int) $idx;
            }
        }

        return null;
    }
}
