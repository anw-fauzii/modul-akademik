<?php

namespace App\Models\Admin\T2q;

use App\Models\Admin\DataMaster\DaftarJilid;
use App\Models\Concerns\HasSqidRouteKey;
use App\Models\Concerns\HasYaumiyahT2q;
use Database\Factories\Admin\T2q\YaumiyahTahsinFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'anggota_t2q_id',
    'akademik_master_t2q_daftar_jilid_id',
    'akademik_master_t2q_surah_id',
    'akademik_master_t2q_angka_arab_id',
    'tanggal',
    'nilai',
])]
#[Table('akademik_t2q_yaumiyah_tahsin')]
#[UseFactory(YaumiyahTahsinFactory::class)]
class YaumiyahTahsin extends Model
{
    /** @use HasFactory<YaumiyahTahsinFactory> */
    use HasFactory;

    use HasSqidRouteKey;
    use HasYaumiyahT2q;

    /**
     * Relasi eager loading minimal untuk Yaumiyah Tahsin tanpa N+1.
     *
     * @var list<string>
     */
    public const array MASTER_RELATIONS = [
        'daftarJilid:id,jilid_latin,jilid_arab,jenis,urutan',
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
        return [
            ...$this->yaumiyahBaseCasts(),
            'akademik_master_t2q_daftar_jilid_id' => 'integer',
        ];
    }

    /**
     * Relasi ke daftar jilid bacaan T2Q.
     *
     * @return BelongsTo<DaftarJilid, $this>
     */
    public function daftarJilid(): BelongsTo
    {
        return $this->belongsTo(DaftarJilid::class, 'akademik_master_t2q_daftar_jilid_id');
    }
}
