<?php

namespace App\Models\Admin\DataMaster;

use Database\Factories\Admin\DataMaster\AnggotaKelasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'siswa_nis',
    'akademik_master_kelas_id',
    'pendaftaran',
])]
#[Table('akademik_master_anggota_kelas')]
#[UseFactory(AnggotaKelasFactory::class)]
class AnggotaKelas extends Model
{
    /** @use HasFactory<AnggotaKelasFactory> */
    use HasFactory;

    /**
     * Pilihan jenis pendaftaran anggota kelas.
     *
     * @var list<string>
     */
    public const PENDAFTARAN_OPTIONS = [
        'Siswa Baru',
        'Naik Kelas',
        'Pindahan',
        'Mengulang',
    ];

    /**
     * Relasi yang di-eager load dengan kolom minimal untuk mencegah N+1.
     *
     * @var list<string>
     */
    public const MASTER_RELATIONS = [
        'siswa:nis,nama_lengkap,nisn,jenis_kelamin,status',
        'kelas:id,akademik_master_tahun_ajaran_id,karyawan_nipy,nama_kelas,jenjang_kelas,tingkatan_kelas',
        'kelas.tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
        'kelas.waliKelas:nipy,nama_lengkap,gelar',
    ];

    /**
     * Atribut yang di-cast secara otomatis.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'akademik_master_kelas_id' => 'integer',
        ];
    }

    /**
     * Eager load relasi utama siswa, kelas, tahun ajaran, dan wali kelas tanpa N+1.
     */
    #[Scope]
    protected function withMasterRelations(Builder $query): Builder
    {
        return $query->with(self::MASTER_RELATIONS);
    }

    /**
     * Filter anggota kelas berdasarkan tahun ajaran (semester) pada relasi kelas.
     */
    #[Scope]
    protected function forTahunAjaran(Builder $query, int $tahunAjaranId): Builder
    {
        return $query->whereHas(
            'kelas',
            fn (Builder $kelasQuery) => $kelasQuery->where('akademik_master_tahun_ajaran_id', $tahunAjaranId)
        );
    }

    /**
     * Filter anggota kelas berdasarkan kelas tertentu.
     */
    #[Scope]
    protected function forKelas(Builder $query, int $kelasId): Builder
    {
        return $query->where('akademik_master_kelas_id', $kelasId);
    }

    /**
     * Kelas badge Bootstrap untuk jenis pendaftaran di kelas.
     */
    protected function pendaftaranBadgeClass(): Attribute
    {
        return Attribute::get(fn (): string => match (strtolower(trim((string) $this->pendaftaran))) {
            'siswa baru' => 'badge-success',
            'naik kelas' => 'badge-primary',
            'pindahan' => 'badge-warning text-dark',
            'mengulang' => 'badge-danger',
            default => 'badge-secondary',
        });
    }

    /**
     * Relasi ke Siswa.
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_nis', 'nis');
    }

    /**
     * Relasi ke Kelas (yang mengikat Tahun Ajaran dan Semester).
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'akademik_master_kelas_id');
    }

    /**
     * Relasi ke keanggotaan ekstrakurikuler pada periode kelas ini.
     */
    public function anggotaEkstrakurikuler(): HasMany
    {
        return $this->hasMany(AnggotaEkstrakurikuler::class, 'anggota_kelas_id');
    }

    /**
     * Relasi ke keanggotaan jemputan pada periode kelas ini.
     */
    public function anggotaJemputan(): HasMany
    {
        return $this->hasMany(AnggotaJemputan::class, 'anggota_kelas_id');
    }
}
