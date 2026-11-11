<?php

namespace App\Models\Admin\DataMaster;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'akademik_master_tahun_ajaran_id',
    'karyawan_nipy',
    'pendamping_nipy',
    'tingkatan_kelas',
    'nama_kelas',
    'jenjang_kelas',
    'romawi',
])]
#[Table('akademik_master_kelas')]
class Kelas extends Model
{
    use HasFactory;

    /**
     * Relasi ke Tahun Ajaran.
     */
    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'akademik_master_tahun_ajaran_id');
    }

    /**
     * Relasi ke Wali Kelas / Guru Utama (Karyawan).
     */
    public function waliKelas(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_nipy', 'nipy');
    }

    /**
     * Alias relasi karyawan (Wali Kelas).
     */
    public function karyawan(): BelongsTo
    {
        return $this->waliKelas();
    }

    /**
     * Relasi ke Guru Pendamping (Karyawan).
     */
    public function pendamping(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'pendamping_nipy', 'nipy');
    }
}
