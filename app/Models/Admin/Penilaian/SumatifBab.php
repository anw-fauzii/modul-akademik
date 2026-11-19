<?php

namespace App\Models\Admin\Penilaian;

use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Concerns\HasSqidRouteKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'akademik_master_tahun_ajaran_id',
    'akademik_pelajaran_mata_pelajaran_id',
    'tingkatan_kelas',
    'periode',
    'nama_bab',
    'keterangan',
    'urutan',
])]
#[Table('akademik_penilaian_sumatif_bab')]
class SumatifBab extends Model
{
    use HasSqidRouteKey;

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
        ];
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'akademik_master_tahun_ajaran_id');
    }

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class, 'akademik_pelajaran_mata_pelajaran_id');
    }

    public function nilaiSumatif(): HasMany
    {
        return $this->hasMany(NilaiSumatifBab::class, 'sumatif_bab_id');
    }
}
