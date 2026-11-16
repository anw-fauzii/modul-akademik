<?php

namespace App\Models\Admin\Penilaian;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'kkm_id',
    'predikat',
    'keterangan',
    'nilai_min',
    'nilai_max',
    'is_tuntas',
    'urutan',
])]
#[Table('akademik_penilaian_kkm_predikat')]
class KkmPredikat extends Model
{
    /**
     * Atribut yang di-cast secara otomatis.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kkm_id' => 'integer',
            'nilai_min' => 'float',
            'nilai_max' => 'float',
            'is_tuntas' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    public function kkm(): BelongsTo
    {
        return $this->belongsTo(Kkm::class, 'kkm_id');
    }
}
