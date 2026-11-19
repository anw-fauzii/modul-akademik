<?php

namespace App\Models\Admin\Penilaian;

use App\Models\Admin\DataMaster\AnggotaKelas;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sumatif_bab_id',
    'anggota_kelas_id',
    'nilai',
    'dinilai_oleh_nipy',
])]
#[Table('akademik_penilaian_nilai_sumatif_bab')]
class NilaiSumatifBab extends Model
{
    /**
     * Atribut yang di-cast secara otomatis.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sumatif_bab_id' => 'integer',
            'anggota_kelas_id' => 'integer',
            'nilai' => 'float',
        ];
    }

    public function sumatifBab(): BelongsTo
    {
        return $this->belongsTo(SumatifBab::class, 'sumatif_bab_id');
    }

    public function anggotaKelas(): BelongsTo
    {
        return $this->belongsTo(AnggotaKelas::class, 'anggota_kelas_id');
    }
}
