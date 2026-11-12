<?php

namespace App\Models\Admin\DataMaster;

use Database\Factories\Admin\DataMaster\AnggotaJemputanFactory;
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
    'akademik_master_jemputan_id',
    'pendaftaran',
    'keterangan',
])]
#[Table('akademik_master_anggota_jemputan')]
#[UseFactory(AnggotaJemputanFactory::class)]
class AnggotaJemputan extends Model
{
    /** @use HasFactory<AnggotaJemputanFactory> */
    use HasFactory;

    public const array PENDAFTARAN_OPTIONS = [
        'Baru',
        'Lanjutan',
    ];

    /**
     * Relasi eager-load standar untuk daftar anggota jemputan tanpa N+1.
     *
     * @var list<string>
     */
    public const array MASTER_RELATIONS = [
        'anggotaKelas:id,siswa_nis,akademik_master_kelas_id,pendaftaran',
        'anggotaKelas.siswa:nis,nama_lengkap,nisn,jenis_kelamin,status',
        'anggotaKelas.kelas:id,akademik_master_tahun_ajaran_id,karyawan_nipy,nama_kelas,jenjang_kelas,tingkatan_kelas',
        'jemputan:id,akademik_master_tahun_ajaran_id,karyawan_nipy,harga_pp,harga_setengah',
        'jemputan.tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
        'jemputan.petugas:nipy,nama_lengkap,gelar',
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
            'akademik_master_jemputan_id' => 'integer',
        ];
    }

    /**
     * Eager load relasi anggota kelas, siswa, kelas, jemputan, tahun ajaran, dan petugas sekaligus.
     */
    #[Scope]
    protected function withMasterRelations(Builder $query): Builder
    {
        return $query->with(self::MASTER_RELATIONS);
    }

    /**
     * Filter anggota jemputan berdasarkan Tahun Ajaran / Semester.
     */
    #[Scope]
    protected function forTahunAjaran(Builder $query, int $tahunAjaranId): Builder
    {
        return $query->whereHas('jemputan', function (Builder $jemputanQuery) use ($tahunAjaranId): void {
            $jemputanQuery->where('akademik_master_tahun_ajaran_id', $tahunAjaranId);
        });
    }

    /**
     * Filter anggota jemputan berdasarkan layanan jemputan spesifik.
     */
    #[Scope]
    protected function forJemputan(Builder $query, int $jemputanId): Builder
    {
        return $query->where($query->qualifyColumn('akademik_master_jemputan_id'), $jemputanId);
    }

    /**
     * Filter anggota jemputan berdasarkan kelas pada anggota kelas.
     */
    #[Scope]
    protected function forKelas(Builder $query, int $kelasId): Builder
    {
        return $query->whereHas('anggotaKelas', function (Builder $anggotaKelasQuery) use ($kelasId): void {
            $anggotaKelasQuery->where('akademik_master_kelas_id', $kelasId);
        });
    }

    /**
     * Kelas badge Bootstrap untuk status pendaftaran jemputan.
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
     * Relasi ke Jemputan tempat siswa terdaftar.
     */
    public function jemputan(): BelongsTo
    {
        return $this->belongsTo(Jemputan::class, 'akademik_master_jemputan_id');
    }
}

