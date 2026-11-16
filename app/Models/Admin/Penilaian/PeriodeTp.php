<?php

namespace App\Models\Admin\Penilaian;

use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tujuan_pembelajaran_id',
    'periode',
    'masuk_rapor',
    'urutan',
])]
#[Table('akademik_penilaian_periode_tp')]
class PeriodeTp extends Model
{
    public const string PERIODE_MID = 'mid';

    public const string PERIODE_SEMESTER = 'semester';

    /**
     * Daftar periode penilaian TP yang didukung.
     *
     * @var array<string, string>
     */
    public const array PERIODE_OPTIONS = [
        self::PERIODE_MID => 'Mid Semester',
        self::PERIODE_SEMESTER => 'Semester',
    ];

    /**
     * Label singkat komponen ujian periode (STS di Mid, SAS di Semester).
     */
    public static function ujianLabel(string $periode): string
    {
        return $periode === self::PERIODE_SEMESTER ? 'SAS' : 'STS';
    }

    /**
     * Label lengkap komponen ujian periode.
     */
    public static function ujianFullLabel(string $periode): string
    {
        return $periode === self::PERIODE_SEMESTER
            ? 'SAS (Sumatif Akhir Semester)'
            : 'STS (Sumatif Tengah Semester)';
    }

    /**
     * Atribut yang di-cast secara otomatis.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tujuan_pembelajaran_id' => 'integer',
            'masuk_rapor' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    public function tujuanPembelajaran(): BelongsTo
    {
        return $this->belongsTo(TujuanPembelajaran::class, 'tujuan_pembelajaran_id');
    }
}
