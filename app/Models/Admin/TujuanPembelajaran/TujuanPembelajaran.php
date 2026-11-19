<?php

namespace App\Models\Admin\TujuanPembelajaran;

use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Concerns\HasSqidRouteKey;
use Database\Factories\Admin\TujuanPembelajaran\TujuanPembelajaranFactory;
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
    'akademik_pelajaran_mata_pelajaran_id',
    'tingkatan_kelas',
    'fase',
    'elemen',
    'kode_tp',
    'tujuan_pembelajaran',
    'lingkup_materi',
    'urutan',
    'status',
])]
#[Table('akademik_pelajaran_tujuan_pembelajaran')]
#[UseFactory(TujuanPembelajaranFactory::class)]
class TujuanPembelajaran extends Model
{
    /** @use HasFactory<TujuanPembelajaranFactory> */
    use HasFactory;

    use HasSqidRouteKey;

    /**
     * Pilihan Fase pada Kurikulum Merdeka sesuai jenjang sekolah.
     *
     * @var array<string, string>
     */
    public const array FASE_OPTIONS = [
        'Fondasi' => 'Fase Fondasi (PG / TK)',
        'A' => 'Fase A (SD Kelas 1–2)',
        'B' => 'Fase B (SD Kelas 3–4)',
        'C' => 'Fase C (SD Kelas 5–6)',
    ];

    /**
     * Opsi default tingkatan kelas per unit pendidikan.
     *
     * @var array<string, list<string>>
     */
    public const array TINGKATAN_DEFAULTS_BY_UNIT = [
        'PG' => ['PG'],
        'TK' => ['TK A', 'TK B'],
        'SD' => ['1', '2', '3', '4', '5', '6'],
    ];

    /**
     * Relasi utama yang selalu di-eager load untuk mencegah N+1.
     *
     * @var list<string>
     */
    public const array INDEX_RELATIONS = [
        'tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
        'mataPelajaran:id,singkatan,nama_mata_pelajaran,unit,sifat',
    ];

    /**
     * Nilai default atribut model.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'urutan' => 1,
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
            'akademik_master_tahun_ajaran_id' => 'integer',
            'akademik_pelajaran_mata_pelajaran_id' => 'integer',
            'urutan' => 'integer',
            'status' => 'boolean',
        ];
    }

    /**
     * Menentukan fase default Kurikulum Merdeka berdasarkan unit dan tingkatan kelas.
     */
    public static function resolveDefaultFase(string $unit, string $tingkatanKelas): string
    {
        $normalizedUnit = strtoupper(trim($unit));
        $normalizedTingkat = strtoupper(trim($tingkatanKelas));

        if (in_array($normalizedUnit, ['PG', 'TK', 'PAUD'], true)) {
            return 'Fondasi';
        }

        return match (true) {
            in_array($normalizedTingkat, ['1', '2', 'I', 'II'], true) => 'A',
            in_array($normalizedTingkat, ['3', '4', 'III', 'IV'], true) => 'B',
            in_array($normalizedTingkat, ['5', '6', 'V', 'VI'], true) => 'C',
            default => 'A',
        };
    }

    /**
     * Eager load seluruh relasi utama tujuan pembelajaran dengan kolom minimal untuk mencegah N+1.
     */
    #[Scope]
    protected function withMasterRelations(Builder $query): Builder
    {
        return $query->with(self::INDEX_RELATIONS);
    }

    /**
     * Mengurutkan tujuan pembelajaran berdasarkan tingkat kelas, urutan, kode TP, lalu id.
     */
    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query
            ->orderBy($query->qualifyColumn('tingkatan_kelas'))
            ->orderBy($query->qualifyColumn('urutan'))
            ->orderBy($query->qualifyColumn('kode_tp'))
            ->orderBy($query->qualifyColumn('id'));
    }

    /**
     * Label fase yang mudah dibaca di tampilan UI.
     */
    protected function faseLabel(): Attribute
    {
        return Attribute::get(function (): string {
            $fase = trim((string) ($this->fase ?? ''));

            if ($fase === '') {
                return '-';
            }

            return strcasecmp($fase, 'Fondasi') === 0 ? 'Fase Fondasi' : "Fase {$fase}";
        });
    }

    /**
     * Kelas badge Bootstrap untuk fase Kurikulum Merdeka.
     */
    protected function faseBadgeClass(): Attribute
    {
        return Attribute::get(fn (): string => match (strtoupper(trim((string) ($this->fase ?? '')))) {
            'FONDASI' => 'badge-warning text-dark',
            'A' => 'badge-primary',
            'B' => 'badge-info',
            'C' => 'badge-success',
            default => 'badge-secondary',
        });
    }

    /**
     * Label tingkatan kelas yang ramah dibaca.
     */
    protected function tingkatanLabel(): Attribute
    {
        return Attribute::get(function (): string {
            $tingkat = trim((string) ($this->tingkatan_kelas ?? ''));

            if ($tingkat === '') {
                return '-';
            }

            return ctype_digit($tingkat) ? "Kelas {$tingkat}" : $tingkat;
        });
    }

    /**
     * Label status aktif TP untuk tampilan UI.
     */
    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn (): string => (bool) $this->status ? 'Aktif' : 'Nonaktif');
    }

    /**
     * Kelas badge Bootstrap untuk status TP.
     */
    protected function statusBadgeClass(): Attribute
    {
        return Attribute::get(fn (): string => (bool) $this->status ? 'badge-success' : 'badge-secondary');
    }

    /**
     * Relasi ke Tahun Ajaran (beserta semester aktif).
     */
    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'akademik_master_tahun_ajaran_id');
    }

    /**
     * Relasi ke Mata Pelajaran.
     */
    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class, 'akademik_pelajaran_mata_pelajaran_id');
    }
}
