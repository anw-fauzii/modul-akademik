<?php

namespace App\Models\Admin\DataMaster;

use App\Models\Concerns\HasSqidRouteKey;
use Database\Factories\Admin\DataMaster\TargetCapaianFactory;
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
    'tingkat',
    'akademik_master_t2q_daftar_jilid_id',
    'akademik_master_t2q_surah_id',
])]
#[Table('akademik_master_t2q_target_capaian')]
#[UseFactory(TargetCapaianFactory::class)]
class TargetCapaian extends Model
{
    /** @use HasFactory<TargetCapaianFactory> */
    use HasFactory;

    use HasSqidRouteKey;

    /**
     * Relasi utama dengan kolom minimal untuk eager loading anti-N+1.
     *
     * @var list<string>
     */
    public const array INDEX_RELATIONS = [
        'tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
        'daftarJilid:id,jilid_latin,jilid_arab,jenis,urutan',
        'surah:id,nama_surah,nama_surah_arab,jumlah_ayat',
    ];

    /**
     * Atribut yang di-cast secara otomatis.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'akademik_master_tahun_ajaran_id' => 'integer',
            'akademik_master_t2q_daftar_jilid_id' => 'integer',
            'akademik_master_t2q_surah_id' => 'integer',
        ];
    }

    /**
     * Membandingkan urutan dua nilai tingkat dengan merujuk helper DRY pada AnggotaT2q.
     */
    public static function compareTingkatOrder(string $a, string $b): int
    {
        return AnggotaT2q::compareTingkatOrder($a, $b);
    }

    /**
     * Menormalisasi string tingkat dengan merujuk helper DRY pada AnggotaT2q.
     */
    public static function normalizeTingkat(mixed $value): string
    {
        return AnggotaT2q::normalizeTingkat($value);
    }

    /**
     * Mengelompokkan kategori tingkat (tk, sd, smp, default) untuk pewarnaan badge dan pill secara DRY.
     */
    public function resolveTingkatGroup(): string
    {
        $norm = strtoupper(self::normalizeTingkat($this->tingkat));

        return match (true) {
            in_array($norm, ['PG', 'A', 'B'], true) => 'tk',
            in_array($norm, ['1', '2', '3', '4', '5', '6'], true) => 'sd',
            in_array($norm, ['7', '8', '9'], true) => 'smp',
            default => 'default',
        };
    }

    /**
     * Mengembalikan kelas badge Bootstrap sesuai tingkatan kelas (TK vs SD/SMP).
     */
    protected function tingkatBadgeClass(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->resolveTingkatGroup()) {
            'tk' => 'badge-warning text-dark',
            'sd' => 'badge-success',
            'smp' => 'badge-primary',
            default => 'badge-secondary',
        });
    }

    /**
     * Kelas CSS pill pastel untuk kolom Tingkat pada tabel Target Capaian.
     */
    protected function tingkatPillClass(): Attribute
    {
        return Attribute::get(fn (): string => 'tc-tingkat-pill--'.$this->resolveTingkatGroup());
    }

    /**
     * Ringkasan gabungan target bacaan & hafalan tanpa memicu lazy loading.
     */
    protected function ringkasanTarget(): Attribute
    {
        return Attribute::get(function (): string {
            $parts = [];

            $jilid = $this->relationLoaded('daftarJilid') ? $this->daftarJilid : null;
            if ($jilid !== null && filled($jilid->jilid_latin)) {
                $parts[] = $jilid->jilid_latin;
            }

            $surah = $this->relationLoaded('surah') ? $this->surah : null;
            if ($surah !== null && filled($surah->nama_surah)) {
                $parts[] = $surah->label_tampilan;
            }

            return $parts === [] ? '-' : implode(' • ', $parts);
        });
    }

    /**
     * Eager load relasi utama secara konsisten tanpa N+1.
     */
    #[Scope]
    protected function withMasterRelations(Builder $query): Builder
    {
        return $query->with(self::INDEX_RELATIONS);
    }

    /**
     * Mengurutkan target capaian berdasarkan tahun ajaran terbaru, tingkat, dan id.
     */
    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query
            ->orderByDesc($query->qualifyColumn('akademik_master_tahun_ajaran_id'))
            ->orderBy($query->qualifyColumn('tingkat'))
            ->orderBy($query->qualifyColumn('id'));
    }

    /**
     * Memfilter berdasarkan tahun ajaran jika diberikan.
     */
    #[Scope]
    protected function forTahunAjaran(Builder $query, ?int $tahunAjaranId): Builder
    {
        if ($tahunAjaranId === null || $tahunAjaranId <= 0) {
            return $query;
        }

        return $query->where($query->qualifyColumn('akademik_master_tahun_ajaran_id'), $tahunAjaranId);
    }

    /**
     * Memfilter berdasarkan tingkat jika diberikan.
     */
    #[Scope]
    protected function forTingkat(Builder $query, ?string $tingkat): Builder
    {
        $normalized = self::normalizeTingkat($tingkat);
        if ($normalized === '') {
            return $query;
        }

        return $query->where($query->qualifyColumn('tingkat'), $normalized);
    }

    /**
     * Relasi ke tahun ajaran / semester target capaian.
     *
     * @return BelongsTo<TahunAjaran, $this>
     */
    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'akademik_master_tahun_ajaran_id');
    }

    /**
     * Relasi ke daftar jilid target bacaan T2Q.
     *
     * @return BelongsTo<DaftarJilid, $this>
     */
    public function daftarJilid(): BelongsTo
    {
        return $this->belongsTo(DaftarJilid::class, 'akademik_master_t2q_daftar_jilid_id');
    }

    /**
     * Relasi ke surah Al-Qur'an target hafalan T2Q.
     *
     * @return BelongsTo<Surah, $this>
     */
    public function surah(): BelongsTo
    {
        return $this->belongsTo(Surah::class, 'akademik_master_t2q_surah_id');
    }
}
