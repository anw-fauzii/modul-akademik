<?php

namespace App\Models\Admin\DataMaster;

use App\Models\Admin\Penilaian\PeriodeTp;
use App\Models\Concerns\HasSqidRouteKey;
use Database\Factories\Admin\DataMaster\TitimangsaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

#[Fillable([
    'akademik_master_tahun_ajaran_id',
    'jenjang_kelas',
    'tingkatan_kelas',
    'periode',
    'tempat',
    'tanggal_titimangsa',
    'kepala_sekolah_nipy',
    'nama_kepala_sekolah_snapshot',
    'jabatan_kepala_sekolah',
    'status',
])]
#[Table('akademik_master_titimangsa')]
#[UseFactory(TitimangsaFactory::class)]
class Titimangsa extends Model
{
    /** @use HasFactory<TitimangsaFactory> */
    use HasFactory;

    use HasSqidRouteKey;

    public const string TINGKATAN_SEMUA = '*';

    public const string DEFAULT_JABATAN = 'Kepala Sekolah';

    /**
     * Daftar unit/jenjang pendidikan yang tersedia pada master titimangsa.
     *
     * @var list<string>
     */
    public const array JENJANG_OPTIONS = ['PG', 'TK', 'SD', 'SMP'];

    /**
     * Pilihan periode rapor yang didukung.
     *
     * @var array<string, string>
     */
    public const array PERIODE_OPTIONS = PeriodeTp::PERIODE_OPTIONS;

    /**
     * Relasi utama yang selalu di-eager load untuk mencegah N+1.
     *
     * @var list<string>
     */
    public const array INDEX_RELATIONS = [
        'tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
        'kepalaSekolah:nipy,nama_lengkap,gelar',
    ];

    /**
     * Nilai default atribut model.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'tingkatan_kelas' => self::TINGKATAN_SEMUA,
        'jabatan_kepala_sekolah' => self::DEFAULT_JABATAN,
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
            'tanggal_titimangsa' => 'date',
            'status' => 'boolean',
        ];
    }

    /**
     * Menentukan titimangsa paling spesifik untuk kelas & periode dari koleksi yang sudah dimuat (anti-N+1).
     * Prioritas 1: kecocokan tepat (tahun_ajaran_id, jenjang_kelas, tingkatan_kelas, periode).
     * Prioritas 2: fallback aturan umum jenjang (tingkatan_kelas = '*').
     *
     * @param  Collection<int, self>  $titimangsaList
     */
    public static function resolveFromCollection(
        Collection $titimangsaList,
        int $tahunAjaranId,
        string $jenjangKelas,
        string $tingkatanKelas,
        string $periode
    ): ?self {
        $normJenjang = strtoupper(trim($jenjangKelas));
        $normTingkatan = trim($tingkatanKelas);
        $normPeriode = strtolower(trim($periode));

        $candidates = $titimangsaList->filter(static fn (self $item): bool => (bool) $item->status
            && (int) $item->akademik_master_tahun_ajaran_id === $tahunAjaranId
            && strtoupper(trim((string) $item->jenjang_kelas)) === $normJenjang
            && strtolower(trim((string) $item->periode)) === $normPeriode);

        if ($normTingkatan !== '' && $normTingkatan !== self::TINGKATAN_SEMUA) {
            $specific = $candidates->first(
                static fn (self $item): bool => trim((string) $item->tingkatan_kelas) === $normTingkatan
            );
            if ($specific instanceof self) {
                return $specific;
            }
        }

        return $candidates->first(
            static fn (self $item): bool => trim((string) $item->tingkatan_kelas) === self::TINGKATAN_SEMUA
        );
    }

    /**
     * Eager load relasi utama titimangsa tanpa N+1.
     */
    #[Scope]
    protected function withMasterRelations(Builder $query): Builder
    {
        return $query->with(self::INDEX_RELATIONS);
    }

    /**
     * Label tingkatan kelas yang ramah UI.
     */
    protected function tingkatanLabel(): Attribute
    {
        return Attribute::get(function (): string {
            $tingkat = trim((string) $this->tingkatan_kelas);

            return ($tingkat === '' || $tingkat === self::TINGKATAN_SEMUA)
                ? 'Semua Tingkat'
                : "Tingkat {$tingkat}";
        });
    }

    /**
     * Penanda apakah aturan berlaku khusus tingkat tertentu atau semua tingkat jenjang.
     */
    protected function isSpecificTingkatan(): Attribute
    {
        return Attribute::get(function (): bool {
            $tingkat = trim((string) $this->tingkatan_kelas);

            return $tingkat !== '' && $tingkat !== self::TINGKATAN_SEMUA;
        });
    }

    /**
     * Label periode rapor dalam Bahasa Indonesia.
     */
    protected function periodeLabel(): Attribute
    {
        return Attribute::get(fn (): string => self::PERIODE_OPTIONS[$this->periode] ?? strtoupper((string) $this->periode));
    }

    /**
     * Kelas badge Bootstrap untuk jenjang kelas.
     */
    protected function jenjangBadgeClass(): Attribute
    {
        return Attribute::get(fn (): string => match (strtoupper(trim((string) $this->jenjang_kelas))) {
            'PG' => 'badge-info',
            'SD' => 'badge-success',
            'SMP' => 'badge-primary',
            'TK', 'PAUD' => 'badge-warning text-dark',
            default => 'badge-secondary',
        });
    }

    /**
     * Format tanggal titimangsa dalam Bahasa Indonesia (contoh: 20 Desember 2026).
     */
    protected function tanggalFormatted(): Attribute
    {
        return Attribute::get(fn (): string => $this->tanggal_titimangsa?->copy()->locale('id')->translatedFormat('d F Y') ?? '-');
    }

    /**
     * Format utuh tempat & tanggal titimangsa (contoh: Garut, 20 Desember 2026).
     */
    protected function titimangsaLabel(): Attribute
    {
        return Attribute::get(function (): string {
            $tempat = trim((string) $this->tempat);
            $tanggal = $this->tanggal_formatted;

            return $tempat !== '' ? "{$tempat}, {$tanggal}" : $tanggal;
        });
    }

    /**
     * Nama Kepala Sekolah untuk ditampilkan/dicetak tanpa memicu lazy loading.
     */
    protected function kepalaSekolahNamaTampil(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->relationLoaded('kepalaSekolah') && $this->kepalaSekolah instanceof Karyawan) {
                return $this->kepalaSekolah->nama_gelar;
            }

            $snapshot = trim((string) ($this->nama_kepala_sekolah_snapshot ?? ''));
            if ($snapshot !== '') {
                return $snapshot;
            }

            $nipy = trim((string) ($this->kepala_sekolah_nipy ?? ''));

            return $nipy !== '' ? $nipy : '-';
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
     * Relasi ke Kepala Sekolah (Karyawan).
     */
    public function kepalaSekolah(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'kepala_sekolah_nipy', 'nipy');
    }
}
