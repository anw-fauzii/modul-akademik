<?php

namespace App\Models\Admin\DataMaster;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'akademik_master_tahun_ajaran_id',
    'karyawan_nipy',
    'pendamping_nipy',
    'tingkatan_kelas',
    'nama_kelas',
    'jenjang_kelas',
    'romawi',
])]
#[Table('akademik_master_kelas')]
class Kelas extends Model
{
    use HasFactory;

    /**
     * Urutan standar jenjang pendidikan untuk pengurutan tampilan & statistik.
     *
     * @var list<string>
     */
    public const array JENJANG_ORDER = ['PG', 'PAUD', 'TK', 'SD', 'SMP', 'SMA', 'SMK'];

    /**
     * Membandingkan dua kode jenjang sesuai urutan tingkat pendidikan sekolah.
     */
    public static function compareJenjangOrder(string $a, string $b): int
    {
        $normA = strtoupper(trim($a));
        $normB = strtoupper(trim($b));

        $posA = array_search($normA, self::JENJANG_ORDER, true);
        $posB = array_search($normB, self::JENJANG_ORDER, true);

        if ($posA !== false && $posB !== false) {
            return $posA <=> $posB;
        }
        if ($posA !== false) {
            return -1;
        }
        if ($posB !== false) {
            return 1;
        }

        return $normA <=> $normB;
    }

    /**
     * Kelas badge Bootstrap untuk jenjang kelas.
     */
    protected function jenjangBadgeClass(): Attribute
    {
        return Attribute::get(fn (): string => match (strtoupper(trim((string) $this->jenjang_kelas))) {
            'PG' => 'badge-info',
            'SD' => 'badge-success',
            'SMP' => 'badge-primary',
            'TK', 'PAUD' => 'badge-warning text-dark',
            'SMA', 'SMK' => 'badge-info',
            default => 'badge-secondary',
        });
    }

    /**
     * Relasi ke Tahun Ajaran.
     */
    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'akademik_master_tahun_ajaran_id');
    }

    /**
     * Relasi ke Wali Kelas / Guru Utama (Karyawan).
     */
    public function waliKelas(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_nipy', 'nipy');
    }

    /**
     * Alias relasi karyawan (Wali Kelas).
     */
    public function karyawan(): BelongsTo
    {
        return $this->waliKelas();
    }

    /**
     * Relasi ke Guru Pendamping (Karyawan).
     */
    public function pendamping(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'pendamping_nipy', 'nipy');
    }

    /**
     * Relasi ke daftar anggota kelas.
     */
    public function anggotaKelas(): HasMany
    {
        return $this->hasMany(AnggotaKelas::class, 'akademik_master_kelas_id');
    }
}
