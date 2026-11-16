<?php

namespace App\Models\Admin\Penilaian;

use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'tujuan_pembelajaran_id',
    'periode',
    'nama_aspek',
    'teknik_penilaian',
    'bobot',
    'masuk_hitung_na_tp',
    'urutan',
])]
#[Table('akademik_penilaian_aspek_tp')]
class AspekTp extends Model
{
    public const string DEFAULT_NAMA_ASPEK = 'Nilai TP';

    /**
     * Pilihan teknik asesmen Kurikulum Merdeka per aspek TP.
     *
     * @var array<string, string>
     */
    public const array TEKNIK_OPTIONS = [
        'tulis' => 'Tes Tulis',
        'lisan' => 'Tes Lisan / Hafalan',
        'praktik' => 'Praktik / Unjuk Kerja',
        'proyek' => 'Proyek',
        'portofolio' => 'Portofolio / Produk',
        'observasi' => 'Observasi',
    ];

    /**
     * Atribut yang di-cast secara otomatis.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tujuan_pembelajaran_id' => 'integer',
            'bobot' => 'integer',
            'masuk_hitung_na_tp' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    public function tujuanPembelajaran(): BelongsTo
    {
        return $this->belongsTo(TujuanPembelajaran::class, 'tujuan_pembelajaran_id');
    }

    public function nilaiAspek(): HasMany
    {
        return $this->hasMany(NilaiAspek::class, 'aspek_tp_id');
    }
}
