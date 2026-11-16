<?php

namespace App\Exports\Admin\TujuanPembelajaran;

use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use App\Services\Admin\TujuanPembelajaran\TujuanPembelajaranService;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * @implements WithMapping<TujuanPembelajaran>
 */
class TujuanPembelajaranExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    /**
     * Header standar kolom Excel untuk Export dan Import TP agar konsisten.
     *
     * @var list<string>
     */
    public const array HEADINGS = [
        'Tahun Ajaran',
        'Semester',
        'Unit',
        'Kode Mapel',
        'Mata Pelajaran',
        'Tingkatan Kelas',
        'Fase',
        'Elemen',
        'Kode TP',
        'Tujuan Pembelajaran',
        'Lingkup Materi',
        'Urutan',
        'Status',
    ];

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        protected array $filters = []
    ) {}

    /**
     * @return Builder|EloquentBuilder<covariant Model>|Relation<covariant Model, covariant Model, Collection<int, Model>>
     */
    public function query(): Builder|EloquentBuilder|Relation
    {
        return app(TujuanPembelajaranService::class)->buildIndexQuery($this->filters);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return self::HEADINGS;
    }

    /**
     * @param  TujuanPembelajaran  $row
     * @return array<int, mixed>
     */
    public function map(mixed $row): array
    {
        $tahunAjaran = $row->relationLoaded('tahunAjaran') ? $row->tahunAjaran : null;
        $mapel = $row->relationLoaded('mataPelajaran') ? $row->mataPelajaran : null;

        return [
            $tahunAjaran?->nama_tahun_ajaran ?? '',
            $tahunAjaran?->semester ?? '',
            $mapel ? strtoupper((string) $mapel->unit) : '',
            $mapel?->singkatan ?? '',
            $mapel?->nama_mata_pelajaran ?? '',
            (string) $row->tingkatan_kelas,
            (string) ($row->fase ?? ''),
            (string) ($row->elemen ?? ''),
            (string) $row->kode_tp,
            (string) $row->tujuan_pembelajaran,
            (string) ($row->lingkup_materi ?? ''),
            (int) $row->urutan,
            $row->status_label,
        ];
    }

    public function title(): string
    {
        return 'Tujuan Pembelajaran';
    }

    /**
     * @return array<int|string, array<string, mixed>>|null
     */
    public function styles(Worksheet $sheet): ?array
    {
        return self::headerStyles();
    }

    /**
     * Style baris header standar untuk dokumen Excel TP.
     *
     * @return array<int|string, array<string, mixed>>
     */
    public static function headerStyles(): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => 'EA580C'],
                ],
            ],
        ];
    }
}
