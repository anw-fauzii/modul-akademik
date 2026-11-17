<?php

namespace App\Models\Admin\Penilaian;

use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'akademik_master_tahun_ajaran_id',
    'akademik_pelajaran_mata_pelajaran_id',
    'anggota_kelas_id',
    'periode',
    'rata_rata_tp',
    'rata_rata_sumatif',
    'nilai_ujian',
    'nilai_akhir_rapor',
    'jumlah_tp_dinilai',
    'nilai_kkm_snapshot',
    'bobot_tp_snapshot',
    'bobot_sumatif_snapshot',
    'bobot_ujian_snapshot',
    'predikat_rapor',
    'keterangan_predikat',
    'tuntas_kkm',
    'deskripsi_capaian',
])]
#[Table('akademik_penilaian_rekap_mapel')]
class RekapMapel extends Model
{
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
            'anggota_kelas_id' => 'integer',
            'rata_rata_tp' => 'float',
            'rata_rata_sumatif' => 'float',
            'nilai_ujian' => 'float',
            'nilai_akhir_rapor' => 'float',
            'jumlah_tp_dinilai' => 'integer',
            'nilai_kkm_snapshot' => 'float',
            'bobot_tp_snapshot' => 'integer',
            'bobot_sumatif_snapshot' => 'integer',
            'bobot_ujian_snapshot' => 'integer',
            'tuntas_kkm' => 'boolean',
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

    public function anggotaKelas(): BelongsTo
    {
        return $this->belongsTo(AnggotaKelas::class, 'anggota_kelas_id');
    }
}
