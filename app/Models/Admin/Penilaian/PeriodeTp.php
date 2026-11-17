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
     * Menentukan apakah semester tahun ajaran termasuk semester 2 (genap) sehingga memakai label PSAT.
     */
    public static function isSemesterGenap(?string $tahunAjaranSemester): bool
    {
        $normalized = strtolower(trim((string) $tahunAjaranSemester));
        if ($normalized === '') {
            return false;
        }

        return $normalized === '2'
            || str_contains($normalized, '2')
            || str_contains($normalized, 'genap');
    }

    /**
     * Label singkat komponen ujian periode (PSTS di Mid, PSAS di Semester 1, PSAT di Semester 2).
     */
    public static function ujianLabel(string $periode, ?string $tahunAjaranSemester = null): string
    {
        if ($periode !== self::PERIODE_SEMESTER) {
            return 'PSTS';
        }

        return self::isSemesterGenap($tahunAjaranSemester) ? 'PSAT' : 'PSAS';
    }

    /**
     * Label lengkap komponen ujian periode (PSTS / PSAS / PSAT).
     */
    public static function ujianFullLabel(string $periode, ?string $tahunAjaranSemester = null): string
    {
        if ($periode !== self::PERIODE_SEMESTER) {
            return 'PSTS (Penilaian Sumatif Tengah Semester)';
        }

        return self::isSemesterGenap($tahunAjaranSemester)
            ? 'PSAT (Penilaian Sumatif Akhir Tahun)'
            : 'PSAS (Penilaian Sumatif Akhir Semester)';
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
