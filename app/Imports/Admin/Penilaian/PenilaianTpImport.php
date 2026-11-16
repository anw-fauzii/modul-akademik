<?php

namespace App\Imports\Admin\Penilaian;

use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\Pelajaran\MataPelajaran;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PenilaianTpImport implements Import, WithMultipleSheets
{
    public PenilaianTpSheetImport $sheetImport;

    public function __construct(
        Kelas $kelas,
        MataPelajaran $mapel,
        string $periode,
        ?string $dinilaiOlehNipy = null
    ) {
        $this->sheetImport = new PenilaianTpSheetImport($kelas, $mapel, $periode, $dinilaiOlehNipy);
    }

    /**
     * Membaca hanya sheet pertama template nilai TP.
     *
     * @return array<int, Import>
     */
    public function sheets(): array
    {
        return [
            0 => $this->sheetImport,
        ];
    }

    public function getSavedCells(): int
    {
        return $this->sheetImport->savedCells;
    }

    public function getUpdatedStudents(): int
    {
        return $this->sheetImport->updatedStudents;
    }

    /**
     * @return list<string>
     */
    public function getErrors(): array
    {
        return $this->sheetImport->errors;
    }
}
