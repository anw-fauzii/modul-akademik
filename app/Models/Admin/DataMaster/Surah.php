<?php

namespace App\Models\Admin\DataMaster;

use Database\Factories\Admin\DataMaster\SurahFactory;
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
    'nama_surah',
    'nama_surah_arab',
    'jumlah_ayat',
])]
#[Table('akademik_master_t2q_surah')]
#[UseFactory(SurahFactory::class)]
class Surah extends Model
{
    /** @use HasFactory<SurahFactory> */
    use HasFactory;

    /**
     * Nilai default atribut model.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'jumlah_ayat' => 0,
    ];

    /**
     * Atribut yang di-cast secara otomatis.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jumlah_ayat' => 'integer',
        ];
    }

    /**
     * Memfilter surah berdasarkan kata kunci nama latin atau Arab.
     */
    #[Scope]
    protected function search(Builder $query, ?string $keyword): void
    {
        $term = trim((string) $keyword);
        if ($term === '') {
            return;
        }

        $query->where(static function (Builder $sub) use ($term): void {
            $sub->where('nama_surah', 'like', "%{$term}%")
                ->orWhere('nama_surah_arab', 'like', "%{$term}%");
        });
    }

    /**
     * Label ringkas nama surah beserta jumlah ayatnya.
     */
    protected function labelTampilan(): Attribute
    {
        return Attribute::get(function (): string {
            $nama = trim((string) $this->nama_surah);
            $ayat = (int) $this->jumlah_ayat;

            if ($nama === '') {
                return '-';
            }

            return $ayat > 0 ? "{$nama} ({$ayat} Ayat)" : $nama;
        });
    }

    /**
     * Relasi ke target capaian T2Q yang merujuk surah ini.
     *
     * @return HasMany<TargetCapaian, $this>
     */
    public function targetCapaian(): HasMany
    {
        return $this->hasMany(TargetCapaian::class, 'akademik_master_t2q_surah_id');
    }
}
