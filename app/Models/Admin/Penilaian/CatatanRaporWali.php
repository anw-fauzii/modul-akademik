<?php

namespace App\Models\Admin\Penilaian;

use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\KomentarRapor;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Concerns\HasSqidRouteKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'akademik_master_tahun_ajaran_id',
    'anggota_kelas_id',
    'periode',
    'catatan',
    'komentar_rapor_id',
    'updated_by_user_id',
    'updated_by_name',
    'updated_by_role_label',
])]
#[Table('akademik_rapor_catatan_wali')]
class CatatanRaporWali extends Model
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
            'anggota_kelas_id' => 'integer',
            'komentar_rapor_id' => 'integer',
            'updated_by_user_id' => 'integer',
        ];
    }

    /**
     * Relasi ke Tahun Ajaran terkait.
     */
    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'akademik_master_tahun_ajaran_id');
    }

    /**
     * Relasi ke anggota kelas siswa terkait.
     */
    public function anggotaKelas(): BelongsTo
    {
        return $this->belongsTo(AnggotaKelas::class, 'anggota_kelas_id');
    }

    /**
     * Relasi ke bank template komentar rapor bila catatan diawali dari template.
     */
    public function templateKomentar(): BelongsTo
    {
        return $this->belongsTo(KomentarRapor::class, 'komentar_rapor_id');
    }

    /**
     * Relasi ke akun pengisi/penyunting catatan terakhir.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
