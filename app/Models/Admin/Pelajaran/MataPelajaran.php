<?php

namespace App\Models\Admin\Pelajaran;

use App\Models\Concerns\HasUnitMetadata;
use Database\Factories\Admin\Pelajaran\MataPelajaranFactory;
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
    'akademik_pelajaran_kelompok_pelajaran_id',
    'singkatan',
    'nama_mata_pelajaran',
    'sifat',
    'unit',
    'urutan',
    'keterangan',
    'status',
])]
#[Table('akademik_pelajaran_mata_pelajaran')]
#[UseFactory(MataPelajaranFactory::class)]
class MataPelajaran extends Model
{
    /** @use HasFactory<MataPelajaranFactory> */
    use HasFactory;

    use HasUnitMetadata;

    public const string SIFAT_WAJIB = 'wajib';

    public const string SIFAT_TAMBAHAN = 'tambahan';

    /**
     * Pilihan nilai enum sifat mata pelajaran.
     *
     * @var list<string>
     */
    public const array SIFAT_OPTIONS = [
        self::SIFAT_WAJIB,
        self::SIFAT_TAMBAHAN,
    ];

    /**
     * Daftar unit pendidikan yang tersedia untuk mata pelajaran.
     *
     * @var list<string>
     */
    public const array UNIT_OPTIONS = ['PG', 'TK', 'SD'];

    /**
     * Relasi utama yang selalu di-eager load untuk mencegah N+1.
     *
     * @var list<string>
     */
    public const array INDEX_RELATIONS = [
        'kelompokPelajaran:id,kode,kelompok,urutan',
    ];

    /**
     * Nilai default atribut model.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'sifat' => self::SIFAT_WAJIB,
        'status' => true,
    ];

    /**
     * Atribut yang di-cast secara otomatis.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'akademik_pelajaran_kelompok_pelajaran_id' => 'integer',
            'urutan' => 'integer',
            'status' => 'boolean',
        ];
    }

    /**
     * Eager load relasi kelompok pelajaran dengan kolom minimal untuk mencegah N+1.
     */
    #[Scope]
    protected function withMasterRelations(Builder $query): Builder
    {
        return $query->with(self::INDEX_RELATIONS);
    }

    /**
     * Mengurutkan mata pelajaran berdasarkan urutan tampil, unit, nama, dan id.
     */
    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query
            ->orderBy($query->qualifyColumn('urutan'))
            ->orderBy($query->qualifyColumn('unit'))
            ->orderBy($query->qualifyColumn('nama_mata_pelajaran'))
            ->orderBy($query->qualifyColumn('id'));
    }

    /**
     * Label sifat mata pelajaran untuk tampilan UI.
     */
    protected function sifatLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->sifat === self::SIFAT_TAMBAHAN ? 'Tambahan' : 'Wajib');
    }

    /**
     * Kelas badge Bootstrap untuk sifat mata pelajaran.
     */
    protected function sifatBadgeClass(): Attribute
    {
        return Attribute::get(fn (): string => $this->sifat === self::SIFAT_TAMBAHAN ? 'badge-secondary' : 'badge-primary');
    }

    /**
     * Label status aktif mata pelajaran untuk tampilan UI.
     */
    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn (): string => (bool) $this->status ? 'Aktif' : 'Nonaktif');
    }

    /**
     * Kelas badge Bootstrap untuk status mata pelajaran.
     */
    protected function statusBadgeClass(): Attribute
    {
        return Attribute::get(fn (): string => (bool) $this->status ? 'badge-success' : 'badge-secondary');
    }

    /**
     * Relasi ke Kelompok Pelajaran.
     */
    public function kelompokPelajaran(): BelongsTo
    {
        return $this->belongsTo(KelompokPelajaran::class, 'akademik_pelajaran_kelompok_pelajaran_id');
    }
}
