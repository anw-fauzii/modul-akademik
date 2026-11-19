<?php

namespace App\Models\Admin\T2q;

use App\Models\Concerns\HasSqidRouteKey;
use App\Models\Concerns\HasYaumiyahT2q;
use Database\Factories\Admin\T2q\YaumiyahTahfizhFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'anggota_t2q_id',
    'akademik_master_t2q_surah_id',
    'akademik_master_t2q_angka_arab_id',
    'tanggal',
    'nilai',
])]
#[Table('akademik_t2q_yaumiyah_tahfizh')]
#[UseFactory(YaumiyahTahfizhFactory::class)]
class YaumiyahTahfizh extends Model
{
    /** @use HasFactory<YaumiyahTahfizhFactory> */
    use HasFactory;

    use HasSqidRouteKey;
    use HasYaumiyahT2q;

    /**
     * Relasi eager loading minimal untuk Yaumiyah Tahfizh tanpa N+1.
     *
     * @var list<string>
     */
    public const array MASTER_RELATIONS = [
        'surah:id,nama_surah,nama_surah_arab,jumlah_ayat',
        'angkaArab:id,angka_latin,angka_arab',
    ];

    /**
     * Atribut yang di-cast secara otomatis.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return $this->yaumiyahBaseCasts();
    }
}
