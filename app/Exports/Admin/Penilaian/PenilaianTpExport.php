<?php

namespace App\Exports\Admin\Penilaian;

use App\Models\Admin\Penilaian\PeriodeTp;
use App\Services\Admin\Penilaian\PenilaianTpService;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PenilaianTpExport implements Export, FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    /**
     * @var array<string, mixed>|null
     */
    protected ?array $cachedMatrix = null;

    /**
     * @var list<array{start_col: int, end_col: int, kode_tp: string}>
     */
    protected array $tpSpans = [];

    /**
     * @var list<int>
     */
    protected array $aspekCols = [];

    /**
     * @var list<int>
     */
    protected array $naTpCols = [];

    /**
     * @var array{start_col: int, end_col: int}|null
     */
    protected ?array $sumatifSpan = null;

    /**
     * @var list<int>
     */
    protected array $sumatifCols = [];

    /**
     * @var list<int>
     */
    protected array $sumatifMidCols = [];

    protected ?int $rataSumatifCol = null;

    /**
     * @var list<int>
     */
    protected array $ujianCols = [];

    /**
     * @var list<int>
     */
    protected array $summaryCols = [];

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        protected array $filters = []
    ) {}

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        $matrix = $this->resolveMatrix();
        $tpList = $matrix['tpList'];
        $aspeksByTp = $matrix['aspeksByTp'];
        $students = $matrix['students'];
        $nilaiLookup = $matrix['nilaiLookup'];
        $rekapTpLookup = $matrix['rekapTpLookup'];
        $rekapMapelLookup = $matrix['rekapMapelLookup'];
        $periode = $matrix['periode'];
        $carriedMidSumatif = $matrix['carriedMidSumatifBabs'] ?? collect();
        $activeSumatif = $matrix['activeSumatifBabs'] ?? collect();
        $sumatifNilaiLookup = $matrix['sumatifNilaiLookup'] ?? [];
        $hasSumatif = $carriedMidSumatif->isNotEmpty() || $activeSumatif->isNotEmpty();

        $this->tpSpans = [];
        $this->aspekCols = [];
        $this->naTpCols = [];
        $this->sumatifSpan = null;
        $this->sumatifCols = [];
        $this->sumatifMidCols = [];
        $this->rataSumatifCol = null;
        $this->ujianCols = [];
        $this->summaryCols = [];

        $row1 = ['No', 'NIS', 'Nama Siswa'];
        $row2 = ['', '', ''];
        $colCursor = 4; // 1=No, 2=NIS, 3=Nama Siswa, 4+=TP columns

        foreach ($tpList as $tp) {
            $aspeks = $aspeksByTp->get($tp->id, collect());
            $startCol = $colCursor;

            foreach ($aspeks as $aspekIndex => $aspek) {
                $row1[] = $aspekIndex === 0 ? $tp->kode_tp : '';
                $row2[] = $aspek->nama_aspek;
                $this->aspekCols[] = $colCursor;
                $colCursor++;
            }

            $row1[] = $aspeks->isEmpty() ? $tp->kode_tp : '';
            $row2[] = 'NA TP';
            $this->naTpCols[] = $colCursor;
            $endCol = $colCursor;
            $colCursor++;

            $this->tpSpans[] = [
                'start_col' => $startCol,
                'end_col' => $endCol,
                'kode_tp' => $tp->kode_tp,
            ];
        }

        if ($hasSumatif) {
            $sumatifStartCol = $colCursor;
            $isFirstSumatifCol = true;

            foreach ($carriedMidSumatif as $midBab) {
                $row1[] = $isFirstSumatifCol ? 'SUMATIF BAB' : '';
                $row2[] = "{$midBab->nama_bab} (Mid)";
                $this->sumatifMidCols[] = $colCursor;
                $colCursor++;
                $isFirstSumatifCol = false;
            }

            foreach ($activeSumatif as $bab) {
                $row1[] = $isFirstSumatifCol ? 'SUMATIF BAB' : '';
                $row2[] = $bab->nama_bab;
                $this->sumatifCols[] = $colCursor;
                $colCursor++;
                $isFirstSumatifCol = false;
            }

            $row1[] = '';
            $row2[] = 'Rata Sumatif';
            $this->rataSumatifCol = $colCursor;
            $this->sumatifSpan = [
                'start_col' => $sumatifStartCol,
                'end_col' => $colCursor,
            ];
            $colCursor++;
        }

        $row1[] = $matrix['ujianLabel'] ?? PeriodeTp::ujianLabel($periode, $matrix['selectedTahunAjaran']?->semester ?? null);
        $row2[] = 'Ujian';
        $this->ujianCols[] = $colCursor++;

        $row1[] = 'NA Rapor';
        $row2[] = 'Nilai';
        $this->summaryCols[] = $colCursor++;

        $row1[] = 'Predikat';
        $row2[] = 'Huruf';
        $this->summaryCols[] = $colCursor++;

        $row1[] = 'Ketuntasan';
        $row2[] = 'KKM';
        $this->summaryCols[] = $colCursor++;

        $rows = [$row1, $row2];

        foreach ($students->values() as $idx => $student) {
            $dataRow = [
                $idx + 1,
                (string) $student->siswa_nis,
                (string) ($student->siswa?->nama_lengkap ?? $student->siswa_nis),
            ];

            foreach ($tpList as $tp) {
                $aspeks = $aspeksByTp->get($tp->id, collect());
                foreach ($aspeks as $aspek) {
                    $nilaiObj = $nilaiLookup["{$student->id}:{$aspek->id}"] ?? null;
                    $dataRow[] = $nilaiObj?->nilai !== null ? (float) $nilaiObj->nilai : null;
                }

                $rekapTp = $rekapTpLookup["{$student->id}:{$tp->id}:{$periode}"] ?? null;
                $dataRow[] = $rekapTp?->nilai_akhir_tp !== null ? (int) round((float) $rekapTp->nilai_akhir_tp) : null;
            }

            $rekapMapel = $rekapMapelLookup["{$student->id}:{$periode}"] ?? null;

            if ($hasSumatif) {
                foreach ($carriedMidSumatif as $midBab) {
                    $nilaiObj = $sumatifNilaiLookup["{$student->id}:{$midBab->id}"] ?? null;
                    $dataRow[] = $nilaiObj?->nilai !== null ? (float) $nilaiObj->nilai : null;
                }
                foreach ($activeSumatif as $bab) {
                    $nilaiObj = $sumatifNilaiLookup["{$student->id}:{$bab->id}"] ?? null;
                    $dataRow[] = $nilaiObj?->nilai !== null ? (float) $nilaiObj->nilai : null;
                }
                $dataRow[] = $rekapMapel?->rata_rata_sumatif !== null
                    ? (int) round((float) $rekapMapel->rata_rata_sumatif)
                    : null;
            }

            $dataRow[] = $rekapMapel?->nilai_ujian !== null ? (float) $rekapMapel->nilai_ujian : null;
            $dataRow[] = $rekapMapel?->nilai_akhir_rapor !== null ? (int) round((float) $rekapMapel->nilai_akhir_rapor) : null;
            $dataRow[] = $rekapMapel?->predikat_rapor ?? '';
            $dataRow[] = $rekapMapel?->tuntas_kkm === null
                ? ''
                : ($rekapMapel->tuntas_kkm ? 'Tuntas' : 'Belum Tuntas');

            $rows[] = $dataRow;
        }

        return $rows;
    }

    public function title(): string
    {
        $matrix = $this->resolveMatrix();
        $periode = $matrix['periode'] === PeriodeTp::PERIODE_SEMESTER ? 'Semester' : 'Mid';
        $mapel = $matrix['selectedMapel']?->singkatan ?? 'Nilai';
        $kelas = $matrix['selectedKelas']?->nama_kelas ?? 'SD';

        return mb_substr("TP {$periode} {$mapel} {$kelas}", 0, 31);
    }

    /**
     * Menerapkan merge header 2 baris ala buku nilai guru dan pewarnaan aspek vs NA TP.
     *
     * @return array<int|string, array<string, mixed>>|null
     */
    public function styles(Worksheet $sheet): ?array
    {
        $highestRow = $sheet->getHighestRow();
        $highestColLetter = $sheet->getHighestColumn();

        // Merge kolom identitas (No, NIS, Nama Siswa) & kolom ringkasan rapor vertikal
        foreach (['A', 'B', 'C'] as $identityCol) {
            $sheet->mergeCells("{$identityCol}1:{$identityCol}2");
            $sheet->getStyle("{$identityCol}1:{$identityCol}2")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '0F172A']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
        }

        // Merge tiap blok header TP pada baris 1 (biru lembut)
        foreach ($this->tpSpans as $span) {
            $startLetter = Coordinate::stringFromColumnIndex($span['start_col']);
            $endLetter = Coordinate::stringFromColumnIndex($span['end_col']);
            if ($span['end_col'] > $span['start_col']) {
                $sheet->mergeCells("{$startLetter}1:{$endLetter}1");
            }

            $sheet->getStyle("{$startLetter}1:{$endLetter}1")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '1E293B']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'BFDBFE'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
        }

        // Sub-header Aspek (kuning cerah sesuai format spreadsheet)
        foreach ($this->aspekCols as $colIdx) {
            $colLetter = Coordinate::stringFromColumnIndex($colIdx);
            $sheet->getStyle("{$colLetter}2")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '0F172A']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FEF08A'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
        }

        // Sub-header & kolom NA TP (merah marun header + biru pucat isi)
        foreach ($this->naTpCols as $colIdx) {
            $colLetter = Coordinate::stringFromColumnIndex($colIdx);
            $sheet->getStyle("{$colLetter}2")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'B91C1C'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            if ($highestRow >= 3) {
                $sheet->getStyle("{$colLetter}3:{$colLetter}{$highestRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '1E293B']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'EFF6FF'],
                    ],
                ]);
            }
        }

        // Grup kolom Sumatif Bab (aksen teal lembut)
        if ($this->sumatifSpan !== null) {
            $startLetter = Coordinate::stringFromColumnIndex($this->sumatifSpan['start_col']);
            $endLetter = Coordinate::stringFromColumnIndex($this->sumatifSpan['end_col']);
            if ($this->sumatifSpan['end_col'] > $this->sumatifSpan['start_col']) {
                $sheet->mergeCells("{$startLetter}1:{$endLetter}1");
            }
            $sheet->getStyle("{$startLetter}1:{$endLetter}1")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '134E4A']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '99F6E4'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
        }

        foreach ($this->sumatifMidCols as $colIdx) {
            $colLetter = Coordinate::stringFromColumnIndex($colIdx);
            $sheet->getStyle("{$colLetter}2")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '475569']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
            if ($highestRow >= 3) {
                $sheet->getStyle("{$colLetter}3:{$colLetter}{$highestRow}")->applyFromArray([
                    'font' => ['color' => ['rgb' => '475569']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F8FAFC'],
                    ],
                ]);
            }
        }

        foreach ($this->sumatifCols as $colIdx) {
            $colLetter = Coordinate::stringFromColumnIndex($colIdx);
            $sheet->getStyle("{$colLetter}2")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '0F172A']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'CCFBF1'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
        }

        if ($this->rataSumatifCol !== null) {
            $colLetter = Coordinate::stringFromColumnIndex($this->rataSumatifCol);
            $sheet->getStyle("{$colLetter}2")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0F766E'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
            if ($highestRow >= 3) {
                $sheet->getStyle("{$colLetter}3:{$colLetter}{$highestRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '115E59']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F0FDFA'],
                    ],
                ]);
            }
        }

        // Kolom Nilai Ujian (PSTS / PSAS / PSAT) dengan aksen indigo lembut
        foreach ($this->ujianCols as $colIdx) {
            $colLetter = Coordinate::stringFromColumnIndex($colIdx);
            $sheet->getStyle("{$colLetter}1:{$colLetter}2")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4338CA'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            if ($highestRow >= 3) {
                $sheet->getStyle("{$colLetter}3:{$colLetter}{$highestRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '312E81']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'EEF2FF'],
                    ],
                ]);
            }
        }

        // Kolom Ringkasan Rapor (NA Rapor, Predikat, Ketuntasan)
        foreach ($this->summaryCols as $colIdx) {
            $colLetter = Coordinate::stringFromColumnIndex($colIdx);
            $sheet->getStyle("{$colLetter}1:{$colLetter}2")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'EA580C'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
        }

        // Border seluruh tabel & center angka nilai
        $sheet->getStyle("A1:{$highestColLetter}{$highestRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
        ]);

        if ($highestRow >= 3) {
            $sheet->getStyle("D3:{$highestColLetter}{$highestRow}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveMatrix(): array
    {
        if ($this->cachedMatrix === null) {
            $this->cachedMatrix = app(PenilaianTpService::class)->getMatrixData($this->filters);
        }

        return $this->cachedMatrix;
    }
}
