<?php

namespace App\Models\Admin\DataMaster;

use App\Models\Concerns\HasSqidRouteKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable(['nama_tahun_ajaran', 'semester', 'is_active'])]
#[Table('akademik_master_tahun_ajaran')]
class TahunAjaran extends Model
{
    use HasFactory;
    use HasSqidRouteKey;

    /**
     * Pilihan label semester pada tahun ajaran.
     *
     * @var array<string, string>
     */
    public const array SEMESTER_OPTIONS = [
        '1' => 'Semester 1',
        'mid 1' => 'Mid Semester 1',
        '2' => 'Semester 2',
        'mid 2' => 'Mid Semester 2',
    ];

    /**
     * Atribut yang di-cast secara otomatis.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Mengekstrak rentang tahun awal dan akhir dari nama tahun ajaran (mis. "2026/2027" => [2026, 2027]).
     *
     * @return array{0: int, 1: int}|null
     */
    public static function parseYearRange(?string $namaTahunAjaran): ?array
    {
        if (! is_string($namaTahunAjaran) || trim($namaTahunAjaran) === '') {
            return null;
        }

        if (preg_match('/(20\d{2})\s*[\/\-]\s*(20\d{2})/', $namaTahunAjaran, $matches) === 1) {
            return [(int) $matches[1], (int) $matches[2]];
        }

        return null;
    }

    /**
     * Menghasilkan daftar kunci bulan kalender (Y-m) sesuai nama tahun ajaran dan semester instance ini.
     *
     * @return list<string>
     */
    public function academicMonthKeys(): array
    {
        $range = self::parseYearRange($this->nama_tahun_ajaran);
        if ($range === null) {
            return [];
        }

        [$startYear, $endYear] = $range;
        $semester = strtolower(trim((string) $this->semester));

        if (in_array($semester, ['1', 'mid 1'], true)) {
            $months = range(7, 12);

            return array_map(static fn (int $m): string => sprintf('%04d-%02d', $startYear, $m), $months);
        }

        if (in_array($semester, ['2', 'mid 2'], true)) {
            $months = range(1, 6);

            return array_map(static fn (int $m): string => sprintf('%04d-%02d', $endYear, $m), $months);
        }

        $result = [];
        for ($m = 7; $m <= 12; $m++) {
            $result[] = sprintf('%04d-%02d', $startYear, $m);
        }
        for ($m = 1; $m <= 6; $m++) {
            $result[] = sprintf('%04d-%02d', $endYear, $m);
        }

        return $result;
    }

    /**
     * Mengurutkan daftar tahun ajaran untuk dropdown (aktif dahulu, terbaru, semester) dengan kolom minimal.
     */
    #[Scope]
    protected function orderedForDropdown(Builder $query): Builder
    {
        return $query
            ->select(['id', 'nama_tahun_ajaran', 'semester', 'is_active'])
            ->orderByDesc('is_active')
            ->orderByDesc('nama_tahun_ajaran')
            ->orderBy('semester');
    }

    /**
     * Memfilter tahun ajaran yang sedang aktif.
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Memfilter semua semester pada satu nama tahun ajaran yang sama.
     */
    #[Scope]
    protected function forNamaTahunAjaran(Builder $query, string $namaTahunAjaran): Builder
    {
        return $query->where('nama_tahun_ajaran', $namaTahunAjaran);
    }

    /**
     * Label semester yang mudah dibaca.
     */
    protected function semesterLabel(): Attribute
    {
        return Attribute::get(function (): string {
            $key = strtolower(trim((string) $this->semester));

            return self::SEMESTER_OPTIONS[$key] ?? ('Semester '.strtoupper((string) $this->semester));
        });
    }

    /**
     * Label lengkap tahun ajaran beserta semesternya.
     */
    protected function fullLabel(): Attribute
    {
        return Attribute::get(fn (): string => trim((string) $this->nama_tahun_ajaran).' - '.$this->semester_label);
    }

    /**
     * Relasi ke seluruh kelas pada tahun ajaran (semester) ini.
     */
    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class, 'akademik_master_tahun_ajaran_id');
    }

    /**
     * Relasi ke seluruh anggota kelas pada tahun ajaran ini melalui kelas.
     */
    public function anggotaKelas(): HasManyThrough
    {
        return $this->hasManyThrough(
            AnggotaKelas::class,
            Kelas::class,
            'akademik_master_tahun_ajaran_id',
            'akademik_master_kelas_id'
        );
    }

    /**
     * Relasi ke seluruh target capaian T2Q pada tahun ajaran ini.
     *
     * @return HasMany<TargetCapaian, $this>
     */
    public function targetCapaian(): HasMany
    {
        return $this->hasMany(TargetCapaian::class, 'akademik_master_tahun_ajaran_id');
    }
}
