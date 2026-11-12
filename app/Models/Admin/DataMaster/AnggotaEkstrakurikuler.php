<?php

namespace App\Models\Admin\DataMaster;

use Database\Factories\Admin\DataMaster\AnggotaEkstrakurikulerFactory;
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
    'akademik_master_ekstrakuler_id',
    'pendaftaran',
    'keterangan',
])]
#[Table('akademik_master_anggota_ekstrakuler')]
#[UseFactory(AnggotaEkstrakurikulerFactory::class)]
class AnggotaEkstrakurikuler extends Model
{
    /** @use HasFactory<AnggotaEkstrakurikulerFactory> */
    use HasFactory;

    public const array PENDAFTARAN_OPTIONS = [
        'Baru',
        'Lanjutan',
    ];

    /**
     * Relasi eager-load standar untuk daftar anggota ekstrakurikuler tanpa N+1.
     *
     * @var list<string>
     */
    public const array MASTER_RELATIONS = [
        'anggotaKelas:id,siswa_nis,akademik_master_kelas_id,pendaftaran',
        'anggotaKelas.siswa:nis,nama_lengkap,nisn,jenis_kelamin,status',
        'anggotaKelas.kelas:id,akademik_master_tahun_ajaran_id,karyawan_nipy,nama_kelas,jenjang_kelas,tingkatan_kelas',
        'ekstrakurikuler:id,akademik_master_tahun_ajaran_id,karyawan_nipy,nama_ekstrakurikuler,biaya,unit',
        'ekstrakurikuler.tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
        'ekstrakurikuler.pembina:nipy,nama_lengkap,gelar',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'anggota_kelas_id' => 'integer',
            'akademik_master_ekstrakuler_id' => 'integer',
        ];
    }

    /**
     * Eager load relasi anggota kelas, siswa, kelas, ekstrakurikuler, tahun ajaran, dan pembina sekaligus.
     */
    #[Scope]
    protected function withMasterRelations(Builder $query): Builder
    {
        return $query->with(self::MASTER_RELATIONS);
    }

    /**
     * Filter anggota ekstrakurikuler berdasarkan Tahun Ajaran / Semester.
     */
    #[Scope]
    protected function forTahunAjaran(Builder $query, int $tahunAjaranId): Builder
    {
        return $query->whereHas('ekstrakurikuler', function (Builder $ekskulQuery) use ($tahunAjaranId): void {
            $ekskulQuery->where('akademik_master_tahun_ajaran_id', $tahunAjaranId);
        });
    }

    /**
     * Filter anggota ekstrakurikuler berdasarkan ekstrakurikuler spesifik.
     */
    #[Scope]
    protected function forEkstrakurikuler(Builder $query, int $ekstrakurikulerId): Builder
    {
        return $query->where($query->qualifyColumn('akademik_master_ekstrakuler_id'), $ekstrakurikulerId);
    }

    /**
     * Filter anggota ekstrakurikuler berdasarkan kelas pada anggota kelas.
     */
    #[Scope]
    protected function forKelas(Builder $query, int $kelasId): Builder
    {
        return $query->whereHas('anggotaKelas', function (Builder $anggotaKelasQuery) use ($kelasId): void {
            $anggotaKelasQuery->where('akademik_master_kelas_id', $kelasId);
        });
    }

    /**
     * Kelas badge Bootstrap untuk status pendaftaran ekstrakurikuler.
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
     * Relasi ke keanggotaan kelas (yang menghubungkan siswa, rombel kelas, dan tahun ajaran).
     */
    public function anggotaKelas(): BelongsTo
    {
        return $this->belongsTo(AnggotaKelas::class, 'anggota_kelas_id');
    }

    /**
     * Relasi ke Ekstrakurikuler tempat siswa terdaftar.
     */
    public function ekstrakurikuler(): BelongsTo
    {
        return $this->belongsTo(Ekstrakurikuler::class, 'akademik_master_ekstrakuler_id');
    }
}
