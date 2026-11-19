<?php

namespace App\Models\Admin\Penilaian;

use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Concerns\HasSqidRouteKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable([
    'akademik_master_tahun_ajaran_id',
    'akademik_pelajaran_mata_pelajaran_id',
    'tingkatan_kelas',
    'nilai_kkm',
    'bobot_tp',
    'bobot_sumatif',
    'bobot_ujian',
    'status',
])]
#[Table('akademik_penilaian_kkm')]
class Kkm extends Model
{
    use HasSqidRouteKey;

    public const float DEFAULT_KKM = 75.00;

    public const int DEFAULT_BOBOT_TP = 100;

    public const int DEFAULT_BOBOT_SUMATIF = 0;

    public const int DEFAULT_BOBOT_UJIAN = 0;

    /**
     * Memvalidasi bahwa jumlah bobot komponen rapor (NA TP + Sumatif Bab + Ujian PSTS/PSAS/PSAT) tepat 100%.
     */
    public static function validateBobotPair(
        int $bobotTp,
        int $bobotUjian,
        int $bobotSumatif = self::DEFAULT_BOBOT_SUMATIF
    ): ?string {
        if (($bobotTp + $bobotSumatif + $bobotUjian) !== 100) {
            return 'Total Bobot NA TP (%), Bobot Sumatif Bab (%), dan Bobot Nilai Ujian (%) harus tepat 100%.';
        }

        return null;
    }

    public const string TARGET_UNIT = 'SD';

    /**
     * Daftar tingkatan kelas SD yang didukung pada tahap pertama.
     *
     * @var list<string>
     */
    public const array SD_TINGKATAN_OPTIONS = ['1', '2', '3', '4', '5', '6'];

    /**
     * Relasi utama yang selalu di-eager load untuk mencegah N+1.
     *
     * @var list<string>
     */
    public const array INDEX_RELATIONS = [
        'tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
        'mataPelajaran:id,singkatan,nama_mata_pelajaran,unit',
        'predikats:id,kkm_id,predikat,keterangan,nilai_min,nilai_max,is_tuntas,urutan',
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
            'nilai_kkm' => 'float',
            'bobot_tp' => 'integer',
            'bobot_sumatif' => 'integer',
            'bobot_ujian' => 'integer',
            'status' => 'boolean',
        ];
    }

    /**
     * Menghasilkan 4 rentang predikat standar A–D dari nilai KKM tanpa overlap atau celah (0–100).
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
    public static function generateDefaultPredikats(float $kkm): array
    {
        $roundedKkm = max(1, min(100, (int) round($kkm)));

        if ($roundedKkm >= 100) {
            return [
                [
                    'predikat' => 'A',
                    'keterangan' => 'Sangat Baik',
                    'nilai_min' => 100,
                    'nilai_max' => 100,
                    'is_tuntas' => true,
                    'urutan' => 1,
                ],
                [
                    'predikat' => 'D',
                    'keterangan' => 'Perlu Bimbingan',
                    'nilai_min' => 0,
                    'nilai_max' => 99,
                    'is_tuntas' => false,
                    'urutan' => 2,
                ],
            ];
        }

        if ($roundedKkm >= 98) {
            return [
                [
                    'predikat' => 'A',
                    'keterangan' => 'Sangat Baik',
                    'nilai_min' => $roundedKkm,
                    'nilai_max' => 100,
                    'is_tuntas' => true,
                    'urutan' => 1,
                ],
                [
                    'predikat' => 'D',
                    'keterangan' => 'Perlu Bimbingan',
                    'nilai_min' => 0,
                    'nilai_max' => $roundedKkm - 1,
                    'is_tuntas' => false,
                    'urutan' => 2,
                ],
            ];
        }

        $totalPassingIntegers = 100 - $roundedKkm + 1;
        $baseSpan = intdiv($totalPassingIntegers, 3);
        $remainder = $totalPassingIntegers % 3;

        $spanA = $baseSpan + ($remainder >= 1 ? 1 : 0);
        $spanB = $baseSpan + ($remainder >= 2 ? 1 : 0);

        $minA = 100 - $spanA + 1;
        $maxB = $minA - 1;
        $minB = $maxB - $spanB + 1;
        $maxC = $minB - 1;

        return [
            [
                'predikat' => 'A',
                'keterangan' => 'Sangat Baik',
                'nilai_min' => $minA,
                'nilai_max' => 100,
                'is_tuntas' => true,
                'urutan' => 1,
            ],
            [
                'predikat' => 'B',
                'keterangan' => 'Baik',
                'nilai_min' => $minB,
                'nilai_max' => $maxB,
                'is_tuntas' => true,
                'urutan' => 2,
            ],
            [
                'predikat' => 'C',
                'keterangan' => 'Cukup',
                'nilai_min' => $roundedKkm,
                'nilai_max' => $maxC,
                'is_tuntas' => true,
                'urutan' => 3,
            ],
            [
                'predikat' => 'D',
                'keterangan' => 'Perlu Bimbingan',
                'nilai_min' => 0,
                'nilai_max' => $roundedKkm - 1,
                'is_tuntas' => false,
                'urutan' => 4,
            ],
        ];
    }

    /**
     * Mencocokkan nilai bulat hasil pembulatan terhadap daftar rentang predikat KKM.
     *
     * @param  Collection<int, KkmPredikat>  $predikats
     */
    public static function resolvePredikatForNilai(float $nilai, Collection $predikats): ?KkmPredikat
    {
        $rounded = (float) round($nilai);

        /** @var KkmPredikat|null $matched */
        $matched = $predikats->first(
            static fn (KkmPredikat $item): bool => $rounded >= (float) $item->nilai_min
                && $rounded <= (float) $item->nilai_max
        );

        if ($matched !== null) {
            return $matched;
        }

        // Fallback aman apabila guru mendefinisikan batas desimal tipis
        return $predikats->first(
            static fn (KkmPredikat $item): bool => $nilai >= (float) $item->nilai_min
                && $nilai <= (float) $item->nilai_max
        );
    }

    #[Scope]
    protected function withMasterRelations(Builder $query): Builder
    {
        return $query->with(self::INDEX_RELATIONS);
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'akademik_master_tahun_ajaran_id');
    }

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class, 'akademik_pelajaran_mata_pelajaran_id');
    }

    public function predikats(): HasMany
    {
        return $this->hasMany(KkmPredikat::class, 'kkm_id')
            ->orderBy('urutan')
            ->orderByDesc('nilai_min');
    }
}
