<?php

namespace App\Models\Admin\DataMaster;

use App\Models\Admin\Penilaian\Kkm;
use App\Models\Concerns\HasSqidRouteKey;
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
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'akademik_master_tahun_ajaran_id',
    'karyawan_nipy',
    'nama_ekstrakurikuler',
    'biaya',
    'unit',
    'kkm',
    'materi',
])]
#[Table('akademik_master_ekstrakuler')]
#[UseFactory(EkstrakurikulerFactory::class)]
class Ekstrakurikuler extends Model
{
    /** @use HasFactory<EkstrakurikulerFactory> */
    use HasFactory;

    use HasSqidRouteKey;

    /**
     * Daftar unit pendidikan yang tersedia untuk ekstrakurikuler.
     *
     * @var list<string>
     */
    public const UNIT_OPTIONS = ['PG', 'TK', 'SD'];

    public const int DEFAULT_KKM = 75;

    /**
     * Label kepanjangan resmi untuk predikat ekstrakurikuler (SAB, BSH, SB, MB).
     *
     * @var array<string, string>
     */
    public const array PREDIKAT_LABELS = [
        'SAB' => 'Sangat Baik',
        'BSH' => 'Berkembang Sesuai Harapan',
        'SB' => 'Sedang Berkembang',
        'MB' => 'Mulai Berkembang',
    ];

    /**
     * Atribut yang di-cast secara otomatis.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'biaya' => 'integer',
            'kkm' => 'integer',
        ];
    }

    /**
     * Menghasilkan rentang predikat ekstrakurikuler (SAB, BSH, SB, MB) dari nilai KKM (1–100).
     *
     * @return list<array{
     *     predikat: string,
     *     keterangan: string,
     *     nilai_min: int,
     *     nilai_max: int,
     *     is_tuntas: bool,
     *     urutan: int
     * }>
     */
    public static function generatePredikatRanges(int|float|null $kkm = null): array
    {
        $resolvedKkm = $kkm !== null && (int) $kkm > 0 ? (float) $kkm : (float) self::DEFAULT_KKM;
        $rawRanges = Kkm::generateDefaultPredikats($resolvedKkm);

        $codeMap = [
            'A' => 'SAB',
            'B' => 'BSH',
            'C' => 'SB',
            'D' => 'MB',
        ];

        return array_map(static function (array $item) use ($codeMap): array {
            $mappedCode = $codeMap[$item['predikat']] ?? $item['predikat'];

            return [
                'predikat' => $mappedCode,
                'keterangan' => self::PREDIKAT_LABELS[$mappedCode] ?? $item['keterangan'],
                'nilai_min' => (int) $item['nilai_min'],
                'nilai_max' => (int) $item['nilai_max'],
                'is_tuntas' => (bool) $item['is_tuntas'],
                'urutan' => (int) $item['urutan'],
            ];
        }, $rawRanges);
    }

    /**
     * Menentukan predikat (SAB, BSH, SB, MB) dari nilai angka berdasarkan KKM ekstrakurikuler.
     *
     * @return array{
     *     predikat: string,
     *     keterangan: string,
     *     nilai_min: int,
     *     nilai_max: int,
     *     is_tuntas: bool,
     *     urutan: int
     * }|null
     */
    public static function resolvePredikatForNilai(int|float|null $nilai, int|float|null $kkm = null): ?array
    {
        if ($nilai === null) {
            return null;
        }

        $rounded = max(0, min(100, (int) round((float) $nilai)));

        foreach (self::generatePredikatRanges($kkm) as $range) {
            if ($rounded >= $range['nilai_min'] && $rounded <= $range['nilai_max']) {
                return $range;
            }
        }

        return null;
    }

    /**
     * Menyusun kalimat deskripsi rapor otomatis berdasarkan predikat dan materi ekstrakurikuler.
     */
    public static function buildAutoDeskripsi(
        ?string $predikat,
        ?string $materi,
        ?string $namaEkstrakurikuler = null
    ): ?string {
        $code = strtoupper(trim((string) $predikat));
        if ($code === '' || ! isset(self::PREDIKAT_LABELS[$code])) {
            return null;
        }

        $cleanMateri = trim((string) $materi);
        $cleanMateri = rtrim($cleanMateri, ". \t\n\r\0\x0B");

        if ($cleanMateri === '') {
            $ekskulLabel = trim((string) $namaEkstrakurikuler);
            $targetText = $ekskulLabel !== ''
                ? 'kegiatan ekstrakurikuler '.$ekskulLabel
                : 'kegiatan ekstrakurikuler';
        } else {
            $targetText = $cleanMateri;
        }

        return match ($code) {
            'SAB' => "Mengikuti kegiatan dengan sangat baik serta mahir dalam {$targetText}.",
            'BSH' => "Mengikuti kegiatan dengan baik dan berkembang sesuai harapan dalam {$targetText}.",
            'SB' => "Sedang berkembang dan cukup baik dalam mengikuti {$targetText}.",
            'MB' => "Mulai berkembang dan perlu bimbingan lebih lanjut dalam {$targetText}.",
            default => null,
        };
    }

    /**
     * Daftar rentang predikat ekstrakurikuler ini berdasarkan nilai KKM-nya.
     *
     * @return list<array{
     *     predikat: string,
     *     keterangan: string,
     *     nilai_min: int,
     *     nilai_max: int,
     *     is_tuntas: bool,
     *     urutan: int
     * }>
     */
    public function predikatRanges(): array
    {
        return self::generatePredikatRanges($this->kkm ?? self::DEFAULT_KKM);
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
            'PG' => 'badge-info',
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

    /**
     * Relasi ke daftar anggota ekstrakurikuler melalui keanggotaan kelas siswa.
     */
    public function anggotaEkstrakurikuler(): HasMany
    {
        return $this->hasMany(AnggotaEkstrakurikuler::class, 'akademik_master_ekstrakuler_id');
    }
}
