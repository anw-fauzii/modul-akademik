<?php

namespace App\Models\Admin\DataMaster;

use Database\Factories\Admin\DataMaster\DaftarJilidFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'jilid_latin',
    'jilid_arab',
    'jenis',
    'urutan',
])]
#[Table('akademik_master_t2q_daftar_jilid')]
#[UseFactory(DaftarJilidFactory::class)]
class DaftarJilid extends Model
{
    /** @use HasFactory<DaftarJilidFactory> */
    use HasFactory;

    /**
     * Nilai default atribut model.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'urutan' => 0,
    ];

    /**
     * Atribut yang di-cast secara otomatis.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
        ];
    }

    /**
     * Memfilter daftar jilid berdasarkan jenis jika dipilih.
     */
    #[Scope]
    protected function filterJenis(Builder $query, ?string $jenis): void
    {
        $normalized = trim((string) $jenis);
        if ($normalized !== '') {
            $query->where('jenis', $normalized);
        }
    }

    /**
     * Kelas badge Bootstrap sesuai jenis jilid T2Q.
     */
    protected function jenisBadgeClass(): Attribute
    {
        return Attribute::get(function (): string {
            $jenis = strtolower(trim((string) $this->jenis));

            return match (true) {
                str_contains($jenis, 'halaman'), str_contains($jenis, 'jilid'), str_contains($jenis, 'pra') => 'badge-primary',
                str_contains($jenis, 'quran'), str_contains($jenis, "qur'an") => 'badge-success',
                str_contains($jenis, 'tahfidz'), str_contains($jenis, 'tahfizh') => 'badge-info',
                str_contains($jenis, 'gharib'), str_contains($jenis, 'tajwid') => 'badge-warning text-dark',
                default => 'badge-secondary',
            };
        });
    }

    /**
     * Relasi ke target capaian T2Q yang merujuk daftar jilid ini.
     *
     * @return HasMany<TargetCapaian, $this>
     */
    public function targetCapaian(): HasMany
    {
        return $this->hasMany(TargetCapaian::class, 'akademik_master_t2q_daftar_jilid_id');
    }
}
