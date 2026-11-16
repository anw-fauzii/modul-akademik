<?php

namespace App\Exports\Admin\TujuanPembelajaran\Sheets;

use App\Exports\Admin\TujuanPembelajaran\TujuanPembelajaranExport;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReferensiTpSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    /**
     * @return Enumerable<int, array<int, mixed>>
     */
    public function collection(): Enumerable
    {
        $tahunAjaranList = TahunAjaran::query()
            ->select(['id', 'nama_tahun_ajaran', 'semester', 'is_active'])
            ->orderByDesc('is_active')
            ->orderByDesc('nama_tahun_ajaran')
            ->orderBy('semester')
            ->get();

        $mapelList = MataPelajaran::query()
            ->select(['id', 'unit', 'singkatan', 'nama_mata_pelajaran', 'status'])
            ->orderBy('unit')
            ->orderBy('urutan')
            ->orderBy('nama_mata_pelajaran')
            ->get();

        $rows = new Collection;
        $max = max($tahunAjaranList->count(), $mapelList->count());

        for ($i = 0; $i < $max; $i++) {
            $ta = $tahunAjaranList->get($i);
            $mp = $mapelList->get($i);

            $rows->push([
                $ta?->nama_tahun_ajaran ?? '',
                $ta?->semester ?? '',
                $ta ? ($ta->is_active ? 'Aktif' : 'Arsip') : '',
                $mp ? strtoupper((string) $mp->unit) : '',
                $mp?->singkatan ?? '',
                $mp?->nama_mata_pelajaran ?? '',
            ]);
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'Tahun Ajaran',
            'Semester',
            'Status TA',
            'Unit',
            'Kode Mapel',
            'Mata Pelajaran',
        ];
    }

    public function title(): string
    {
        return 'Referensi TA & Mapel';
    }

    /**
     * @return array<int|string, array<string, mixed>>|null
     */
    public function styles(Worksheet $sheet): ?array
    {
        return TujuanPembelajaranExport::headerStyles();
    }
}
