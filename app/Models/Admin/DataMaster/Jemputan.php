<?php

namespace App\Models\Admin\DataMaster;

use Database\Factories\Admin\DataMaster\JemputanFactory;
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
    'akademik_master_tahun_ajaran_id',
    'karyawan_nipy',
    'harga_pp',
    'harga_setengah',
])]
#[Table('akademik_master_jemputan')]
#[UseFactory(JemputanFactory::class)]
class Jemputan extends Model
{
    /** @use HasFactory<JemputanFactory> */
    use HasFactory;

    /**
     * Atribut yang di-cast secara otomatis.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'akademik_master_tahun_ajaran_id' => 'integer',
            'harga_pp' => 'integer',
            'harga_setengah' => 'integer',
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
            'petugas:nipy,nama_lengkap,gelar',
        ]);
    }

    /**
     * Format tarif pulang-pergi (PP) ke format Rupiah atau label Gratis.
     */
    protected function hargaPpFormatted(): Attribute
    {
        return Attribute::get(fn (): string => $this->formatRupiah($this->harga_pp));
    }

    /**
     * Format tarif sekali jalan (setengah) ke format Rupiah atau label Gratis.
     */
    protected function hargaSetengahFormatted(): Attribute
    {
        return Attribute::get(fn (): string => $this->formatRupiah($this->harga_setengah));
    }

    /**
     * Relasi ke Tahun Ajaran.
     */
    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'akademik_master_tahun_ajaran_id');
    }

    /**
     * Relasi ke Petugas / Driver Jemputan (Karyawan).
     */
    public function petugas(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_nipy', 'nipy');
    }

    /**
     * Alias relasi karyawan (Petugas / Driver Jemputan).
     */
    public function karyawan(): BelongsTo
    {
        return $this->petugas();
    }

    /**
     * Relasi ke daftar anggota jemputan melalui keanggotaan kelas siswa.
     */
    public function anggotaJemputan(): HasMany
    {
        return $this->hasMany(AnggotaJemputan::class, 'akademik_master_jemputan_id');
    }

    /**
     * Format nominal biaya ke format Rupiah atau Gratis.
     */
    private function formatRupiah(int|string|null $amount): string
    {
        $nominal = (int) $amount;

        if ($nominal <= 0) {
            return 'Gratis';
        }

        return 'Rp '.number_format($nominal, 0, ',', '.');
    }
}
