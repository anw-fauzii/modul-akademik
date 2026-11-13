<?php

namespace App\Models\Admin\Pelajaran;

use Database\Factories\Admin\Pelajaran\KelompokPelajaranFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'kode',
    'kelompok',
    'urutan',
])]
#[Table('akademik_pelajaran_kelompok_pelajaran')]
#[UseFactory(KelompokPelajaranFactory::class)]
class KelompokPelajaran extends Model
{
    /** @use HasFactory<KelompokPelajaranFactory> */
    use HasFactory;

    /**
     * Atribut yang di-cast secara otomatis.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
        ];
    }

    /**
     * Mengurutkan kelompok pelajaran berdasarkan urutan tampil, kode, lalu id.
     */
    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query
            ->orderBy($query->qualifyColumn('urutan'))
            ->orderBy($query->qualifyColumn('kode'))
            ->orderBy($query->qualifyColumn('id'));
    }

    /**
     * Relasi ke daftar Mata Pelajaran dalam kelompok ini.
     */
    public function mataPelajaran(): HasMany
    {
        return $this->hasMany(MataPelajaran::class, 'akademik_pelajaran_kelompok_pelajaran_id');
    }
}
