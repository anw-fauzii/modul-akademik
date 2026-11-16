<?php

namespace App\Imports\Admin\TujuanPembelajaran;

use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TujuanPembelajaranImport implements Import, WithMultipleSheets
{
    public TujuanPembelajaranSheetImport $sheetImport;

    public function __construct(
        protected ?int $defaultTahunAjaranId = null
    ) {
        $this->sheetImport = new TujuanPembelajaranSheetImport($this->defaultTahunAjaranId);
    }

    /**
     * Membaca hanya sheet pertama agar sheet referensi pada template tidak ikut diproses.
     *
     * @return array<int, Import>
     */
    public function sheets(): array
    {
        return [
            0 => $this->sheetImport,
        ];
    }

    public function getCreatedCount(): int
    {
        return $this->sheetImport->createdCount;
    }

    public function getUpdatedCount(): int
    {
        return $this->sheetImport->updatedCount;
    }

    /**
     * @return list<string>
     */
    public function getErrors(): array
    {
        return $this->sheetImport->errors;
    }
}
