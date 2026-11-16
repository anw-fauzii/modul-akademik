<?php

namespace App\Exports\Admin\TujuanPembelajaran;

use App\Exports\Admin\TujuanPembelajaran\Sheets\ReferensiTpSheet;
use App\Exports\Admin\TujuanPembelajaran\Sheets\TemplateTpSheet;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TujuanPembelajaranTemplateExport implements Export, WithMultipleSheets
{
    /**
     * @return array<int, Export>
     */
    public function sheets(): array
    {
        return [
            0 => new TemplateTpSheet,
            1 => new ReferensiTpSheet,
        ];
    }
}
