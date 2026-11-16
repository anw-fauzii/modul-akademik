<?php

namespace App\Imports\Admin\Penilaian;

use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Admin\Penilaian\AspekTp;
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

        $nisHeaderIdx = $this->detectNisColumnIndex($tpHeaderRow, $aspekHeaderRow);
        if ($nisHeaderIdx === null) {
            $this->errors[] = 'Kolom "NIS" tidak ditemukan pada header Excel.';

            return;
        }

        // Petakan tiap kolom aspek dari baris header 1 (Kode TP dengan carry-forward untuk sel ter-merge) & baris header 2 (Nama Aspek)
        $colToAspekId = [];
        $ujianColIdx = null;
        $maxCol = max(count($tpHeaderRow), count($aspekHeaderRow));
        $currentKodeTp = '';

        for ($colIdx = $nisHeaderIdx + 1; $colIdx < $maxCol; $colIdx++) {
            $rawTop = strtoupper(trim((string) ($tpHeaderRow[$colIdx] ?? '')));
            $rawSub = trim((string) ($aspekHeaderRow[$colIdx] ?? ''));
            $upperSub = strtoupper($rawSub);

            if (in_array($rawTop, ['STS', 'SAS', 'UJIAN', 'NILAI UJIAN', 'PTS', 'PAS'], true)) {
                $ujianColIdx = $colIdx;
                $currentKodeTp = '';

                continue;
            }

            if (in_array($rawTop, ['NA RAPOR', 'PREDIKAT', 'KETUNTASAN', 'STATUS KKM'], true)) {
                $currentKodeTp = '';

                continue;
            }

            if ($rawTop !== '') {
                $currentKodeTp = $rawTop;
            }

            if ($currentKodeTp === '' || $rawSub === '') {
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

        if ($colToAspekId === [] && $ujianColIdx === null) {
            $this->errors[] = 'Tidak ada kolom aspek TP maupun kolom Ujian (STS/SAS) yang cocok dengan konfigurasi kelas ini.';

            return;
        }

        $anggotaByNis = AnggotaKelas::query()
            ->select(['id', 'siswa_nis'])
            ->where('akademik_master_kelas_id', $this->kelas->id)
            ->get()
            ->keyBy(static fn (AnggotaKelas $a): string => trim((string) $a->siswa_nis));

        $nilaiMatrix = [];
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

            $rowValues = [];
            foreach ($colToAspekId as $colIdx => $aspekId) {
                $rawCell = $studentRow[$colIdx] ?? null;

                if ($rawCell === null || trim((string) $rawCell) === '') {
                    $rowValues[$aspekId] = null;

                    continue;
                }

                $normalizedText = str_replace(',', '.', trim((string) $rawCell));
                if (! is_numeric($normalizedText)) {
                    $excelLine = $r + 1;
                    $this->errors[] = "Baris {$excelLine} (NIS {$nis}): Nilai \"{$rawCell}\" harus berupa angka 0–100.";

                    continue 2;
                }

                $numeric = (float) $normalizedText;
                if ($numeric < 0 || $numeric > 100) {
                    $excelLine = $r + 1;
                    $this->errors[] = "Baris {$excelLine} (NIS {$nis}): Nilai {$numeric} di luar rentang 0–100.";

                    continue 2;
                }

                $rowValues[$aspekId] = $numeric;
            }

            if ($colToAspekId !== []) {
                $nilaiMatrix[$anggota->id] = $rowValues;
            }

            if ($ujianColIdx !== null) {
                $rawUjianCell = $studentRow[$ujianColIdx] ?? null;
                if ($rawUjianCell === null || trim((string) $rawUjianCell) === '') {
                    $ujianMatrix[$anggota->id] = null;
                } else {
                    $normalizedUjianText = str_replace(',', '.', trim((string) $rawUjianCell));
                    if (! is_numeric($normalizedUjianText)) {
                        $excelLine = $r + 1;
                        $this->errors[] = "Baris {$excelLine} (NIS {$nis}): Nilai Ujian \"{$rawUjianCell}\" harus berupa angka 0–100.";

                        continue;
                    }

                    $numericUjian = (float) $normalizedUjianText;
                    if ($numericUjian < 0 || $numericUjian > 100) {
                        $excelLine = $r + 1;
                        $this->errors[] = "Baris {$excelLine} (NIS {$nis}): Nilai Ujian {$numericUjian} di luar rentang 0–100.";

                        continue;
                    }

                    $ujianMatrix[$anggota->id] = $numericUjian;
                }
            }
        }

        if ($this->errors !== [] || ($nilaiMatrix === [] && $ujianMatrix === [])) {
            if ($nilaiMatrix === [] && $ujianMatrix === [] && $this->errors === []) {
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
            $ujianMatrix
        );

        $this->savedCells = $result['saved_cells'];
        $this->updatedStudents = $result['updated_students'];
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
