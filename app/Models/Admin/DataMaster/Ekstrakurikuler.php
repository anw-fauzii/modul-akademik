<?php

namespace App\Models\Admin\DataMaster;

use Database\Factories\Admin\DataMaster\EkstrakurikulerFactory;
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
    'akademik_master_tahun_ajaran_id',
    'karyawan_nipy',
    'nama_ekstrakurikuler',
    'biaya',
    'unit',
])]
#[Table('akademik_master_ekstrakuler')]
#[UseFactory(EkstrakurikulerFactory::class)]
class Ekstrakurikuler extends Model
{
    /** @use HasFactory<EkstrakurikulerFactory> */
    use HasFactory;

    /**
     * Daftar unit pendidikan yang tersedia untuk ekstrakurikuler.
     *
     * @var list<string>
     */
    public const UNIT_OPTIONS = ['TK', 'SD', 'SMP', 'SMA'];

    /**
     * Atribut yang di-cast secara otomatis.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'biaya' => 'integer',
        ];
    }

    /**
     * Eager load relasi utama dengan kolom minimal untuk mencegah N+1.
     */
    #[Scope]
    protected function withMasterRelations(Builder $query): Builder
    {
        return $query->with([
            'tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
            'pembina:nipy,nama_lengkap,gelar',
        ]);
    }

    /**
     * Format biaya ekstrakurikuler ke format Rupiah atau label Gratis.
     */
    protected function biayaFormatted(): Attribute
    {
        return Attribute::get(function (): string {
            if ((int) $this->biaya <= 0) {
                return 'Gratis';
            }

            return 'Rp '.number_format((int) $this->biaya, 0, ',', '.');
        });
    }

    /**
     * Kelas badge Bootstrap untuk label unit pendidikan.
     */
    protected function unitBadgeClass(): Attribute
    {
        return Attribute::get(fn (): string => match (strtoupper((string) $this->unit)) {
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
     * Relasi ke Pembina / Pelatih Ekstrakurikuler (Karyawan).
     */
    public function pembina(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_nipy', 'nipy');
    }

    /**
     * Alias relasi karyawan (Pembina Ekstrakurikuler).
     */
    public function karyawan(): BelongsTo
    {
        return $this->pembina();
    }
}
