<?php

namespace App\Models\Admin\Penilaian;

use App\Models\Admin\DataMaster\AnggotaKelas;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'aspek_tp_id',
    'anggota_kelas_id',
    'nilai',
    'is_remedial',
    'catatan',
    'dinilai_oleh_nipy',
])]
#[Table('akademik_penilaian_nilai_aspek')]
class NilaiAspek extends Model
{
    /**
     * Atribut yang di-cast secara otomatis.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'aspek_tp_id' => 'integer',
            'anggota_kelas_id' => 'integer',
            'nilai' => 'float',
            'is_remedial' => 'boolean',
        ];
    }

    public function aspekTp(): BelongsTo
    {
        return $this->belongsTo(AspekTp::class, 'aspek_tp_id');
    }

    public function anggotaKelas(): BelongsTo
    {
        return $this->belongsTo(AnggotaKelas::class, 'anggota_kelas_id');
    }
}
