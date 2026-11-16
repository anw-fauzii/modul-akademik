<?php

namespace App\Models\Admin\Penilaian;

use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tujuan_pembelajaran_id',
    'anggota_kelas_id',
    'periode',
    'nilai_akhir_tp',
    'predikat',
    'tuntas_kkm',
    'is_manual_override',
])]
#[Table('akademik_penilaian_rekap_tp')]
class RekapTp extends Model
{
    /**
     * Atribut yang di-cast secara otomatis.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tujuan_pembelajaran_id' => 'integer',
            'anggota_kelas_id' => 'integer',
            'nilai_akhir_tp' => 'float',
            'tuntas_kkm' => 'boolean',
            'is_manual_override' => 'boolean',
        ];
    }

    public function tujuanPembelajaran(): BelongsTo
    {
        return $this->belongsTo(TujuanPembelajaran::class, 'tujuan_pembelajaran_id');
    }

    public function anggotaKelas(): BelongsTo
    {
        return $this->belongsTo(AnggotaKelas::class, 'anggota_kelas_id');
    }
}
