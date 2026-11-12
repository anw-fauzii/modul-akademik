<?php

namespace App\Models\Admin\DataMaster;

use Database\Factories\Admin\DataMaster\AnggotaT2qFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'anggota_kelas_id',
    'karyawan_nipy',
    'tingkat',
    'pendaftaran',
    'keterangan',
])]
#[Table('akademik_master_anggota_t2q')]
#[UseFactory(AnggotaT2qFactory::class)]
class AnggotaT2q extends Model
{
    /** @use HasFactory<AnggotaT2qFactory> */
    use HasFactory;

    public const array PENDAFTARAN_OPTIONS = [
        'Baru',
        'Lanjutan',
    ];

    /**
     * Daftar tingkat standar (sejalan dengan jenjang/tingkatan kelas dari PG hingga kelas 9).
     *
     * @var list<string>
     */
    public const array DEFAULT_TINGKAT_OPTIONS = [
        'PG',
        'A',
        'B',
        '1',
        '2',
        '3',
        '4',
        '5',
        '6',
        '7',
        '8',
        '9',
    ];

    public const string TINGKAT_AUTO = '__auto__';

    public const string TINGKAT_CUSTOM = '__custom__';

    /**
     * Relasi eager-load standar untuk daftar anggota T2Q tanpa N+1.
     *
     * @var list<string>
     */
    public const array MASTER_RELATIONS = [
        'anggotaKelas:id,siswa_nis,akademik_master_kelas_id,pendaftaran',
        'anggotaKelas.siswa:nis,nama_lengkap,nisn,jenis_kelamin,status',
        'anggotaKelas.kelas:id,akademik_master_tahun_ajaran_id,karyawan_nipy,nama_kelas,jenjang_kelas,tingkatan_kelas',
        'anggotaKelas.kelas.tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
        'guruT2q:nipy,nama_lengkap,gelar',
    ];

    /**
     * Normalisasi string tingkat agar konsisten tanpa spasi berlebih.
     */
    public static function normalizeTingkat(mixed $value): string
    {
        return trim((string) $value);
    }

    /**
     * Membandingkan dua nilai tingkat sesuai urutan jenjang sekolah (PG, A, B, 1..9, lainnya natural).
     */
    public static function compareTingkatOrder(string $a, string $b): int
    {
        $normA = strtoupper(self::normalizeTingkat($a));
        $normB = strtoupper(self::normalizeTingkat($b));
        $standardUpper = array_map('strtoupper', self::DEFAULT_TINGKAT_OPTIONS);

        $posA = array_search($normA, $standardUpper, true);
        $posB = array_search($normB, $standardUpper, true);

        if ($posA !== false && $posB !== false) {
            return $posA <=> $posB;
        }
        if ($posA !== false) {
            return -1;
        }
        if ($posB !== false) {
            return 1;
        }

        return strnatcasecmp($normA, $normB);
    }

    /**
     * Menggabungkan daftar tingkat standar dengan nilai tingkatan tambahan dari kelas / anggota T2Q.
     *
     * @param  iterable<int, mixed>  $extraValues
     * @return list<string>
     */
    public static function buildTingkatOptions(iterable $extraValues = []): array
    {
        $uniqueByKey = [];

        foreach (self::DEFAULT_TINGKAT_OPTIONS as $option) {
            $uniqueByKey[strtoupper($option)] = $option;
        }

        foreach ($extraValues as $rawValue) {
            $trimmed = self::normalizeTingkat($rawValue);
            if ($trimmed === '' || in_array($trimmed, [self::TINGKAT_AUTO, self::TINGKAT_CUSTOM], true)) {
                continue;
            }

            $key = strtoupper($trimmed);
            if (! array_key_exists($key, $uniqueByKey)) {
                $uniqueByKey[$key] = $trimmed;
            }
        }

        $options = array_values($uniqueByKey);
        usort($options, static fn (string $a, string $b): int => self::compareTingkatOrder($a, $b));

        return $options;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'anggota_kelas_id' => 'integer',
        ];
    }

    /**
     * Eager load relasi anggota kelas, siswa, kelas, tahun ajaran, dan guru T2Q sekaligus.
     */
    #[Scope]
    protected function withMasterRelations(Builder $query): Builder
    {
        return $query->with(self::MASTER_RELATIONS);
    }

    /**
     * Filter anggota T2Q berdasarkan Tahun Ajaran / Semester melalui kelas pada anggota kelas.
     */
    #[Scope]
    protected function forTahunAjaran(Builder $query, int $tahunAjaranId): Builder
    {
        return $query->whereHas('anggotaKelas.kelas', function (Builder $kelasQuery) use ($tahunAjaranId): void {
            $kelasQuery->where('akademik_master_tahun_ajaran_id', $tahunAjaranId);
        });
    }

    /**
     * Filter anggota T2Q berdasarkan guru pembimbing T2Q (karyawan_nipy).
     */
    #[Scope]
    protected function forKaryawan(Builder $query, string $karyawanNipy): Builder
    {
        return $query->where($query->qualifyColumn('karyawan_nipy'), $karyawanNipy);
    }

    /**
     * Filter anggota T2Q berdasarkan tingkat.
     */
    #[Scope]
    protected function forTingkat(Builder $query, string $tingkat): Builder
    {
        return $query->where($query->qualifyColumn('tingkat'), self::normalizeTingkat($tingkat));
    }

    /**
     * Filter anggota T2Q berdasarkan kelas pada anggota kelas.
     */
    #[Scope]
    protected function forKelas(Builder $query, int $kelasId): Builder
    {
        return $query->whereHas('anggotaKelas', function (Builder $anggotaKelasQuery) use ($kelasId): void {
            $anggotaKelasQuery->where('akademik_master_kelas_id', $kelasId);
        });
    }

    /**
     * Kelas badge Bootstrap untuk status pendaftaran T2Q.
     */
    protected function pendaftaranBadgeClass(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->pendaftaran) {
            'Baru' => 'badge-success',
            'Lanjutan' => 'badge-primary',
            default => 'badge-secondary',
        });
    }

    /**
     * Kelas badge Bootstrap untuk tingkat T2Q.
     */
    protected function tingkatBadgeClass(): Attribute
    {
        return Attribute::get(function (): string {
            $normalized = strtoupper(self::normalizeTingkat($this->tingkat));

            if (in_array($normalized, ['PG', 'A', 'B', 'TK-A', 'TK-B', 'TK A', 'TK B'], true)) {
                return 'badge-warning text-dark';
            }

            if (ctype_digit($normalized)) {
                $num = (int) $normalized;
                if ($num >= 1 && $num <= 6) {
                    return 'badge-success';
                }
                if ($num >= 7 && $num <= 9) {
                    return 'badge-primary';
                }
                if ($num >= 10) {
                    return 'badge-info';
                }
            }

            return 'badge-info';
        });
    }

    /**
     * Relasi ke keanggotaan kelas (yang menghubungkan siswa, rombel kelas, dan tahun ajaran).
     */
    public function anggotaKelas(): BelongsTo
    {
        return $this->belongsTo(AnggotaKelas::class, 'anggota_kelas_id');
    }

    /**
     * Relasi ke Guru Pembimbing T2Q (Karyawan).
     */
    public function guruT2q(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_nipy', 'nipy');
    }

    /**
     * Alias relasi karyawan (Guru Pembimbing T2Q).
     */
    public function karyawan(): BelongsTo
    {
        return $this->guruT2q();
    }
}
