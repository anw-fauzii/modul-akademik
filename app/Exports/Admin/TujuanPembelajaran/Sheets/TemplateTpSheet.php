<?php

namespace App\Exports\Admin\TujuanPembelajaran\Sheets;

use App\Exports\Admin\TujuanPembelajaran\TujuanPembelajaranExport;
use App\Models\Admin\DataMaster\TahunAjaran;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TemplateTpSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    /**
     * @return Enumerable<int, array<int, mixed>>
     */
    public function collection(): Enumerable
    {
        $activeTa = TahunAjaran::query()
            ->select(['id', 'nama_tahun_ajaran', 'semester', 'is_active'])
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->first();

        $tahunAjaranLabel = $activeTa?->nama_tahun_ajaran ?? '2026/2027';
        $semesterLabel = $activeTa?->semester ?? '1';

        return new Collection([
            [
                $tahunAjaranLabel,
                $semesterLabel,
                'SD',
                'MTK',
                'Matematika',
                '1',
                'A',
                'Bilangan',
                'MTK.1.1',
                'Peserta didik mampu membilang, membaca, dan menuliskan bilangan cacah sampai 20 dengan tepat.',
                1,
                'Aktif',
            ],
            [
                $tahunAjaranLabel,
                $semesterLabel,
                'TK',
                'NABP',
                'Nilai Agama & Budi Pekerti',
                'TK A',
                'Fondasi',
                'Nilai Agama dan Budi Pekerti',
                'TP-TKA-01',
                'Anak mampu mengenal doa sehari-hari dan mempraktikkan sikap santun.',
                1,
                'Aktif',
            ],
        ]);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return TujuanPembelajaranExport::HEADINGS;
    }

    public function title(): string
    {
        return 'Template TP';
    }

    /**
     * @return array<int|string, array<string, mixed>>|null
     */
    public function styles(Worksheet $sheet): ?array
    {
        return TujuanPembelajaranExport::headerStyles();
    }
}
