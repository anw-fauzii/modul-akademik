<?php

namespace App\Models\Admin\DataMaster;

use App\Models\Admin\DataPelengkap\JenjangPendidikan;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'nipy',
    'nama_lengkap',
    'gelar',
    'kepegawaian_master_kategori_id',
    'tempat_lahir',
    'tanggal_lahir',
    'alamat',
    'rt',
    'rw',
    'kelurahan',
    'kecamatan',
    'kabupaten',
    'jk',
    'jabatan',
    'golongan',
    'unit',
    'mulai_tugas',
    'akademik_pelengkap_jenjang_pendidikan_id',
    'prodi',
    'tahun_lulus',
    'whatsapp',
    'nuptk',
    'sertifikasi',
    'nik',
    'kepegawaian_master_jam_kerja_id',

])]
#[Table('kepegawaian_master_karyawan')]
class Karyawan extends Model
{
    use HasFactory;

    protected $primaryKey = 'nipy';

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'mulai_tugas' => 'date',
            'sertifikasi' => 'boolean',
        ];
    }

    protected function lamaKerja(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->mulai_tugas) {
                return '-';
            }

            $diff = $this->mulai_tugas->startOfDay()->diff(now()->startOfDay());

            if ($diff->invert) {
                return '0 tahun 0 bulan';
            }

            return $diff->y.' tahun '.$diff->m.' bulan';
        });
    }

    protected function email(): Attribute
    {
        return Attribute::get(fn ($value) => $value ?? $this->user?->email);
    }

    protected function namaGelar(): Attribute
    {
        return Attribute::get(fn () => $this->nama_lengkap.($this->gelar ? ', '.$this->gelar : ''));
    }

    protected function statusPegawai(): Attribute
    {
        return Attribute::get(function () {
            $nipy = preg_replace('/\s+/', '', (string) ($this->nipy ?? ''));
            if (preg_match('/(?:-1|-01|01)$/', $nipy)) {
                return 'Tetap';
            }
            if (preg_match('/(?:-2|-02|02)$/', $nipy)) {
                return 'Kontrak';
            }
            if (preg_match('/(?:-3|-03|03)$/', $nipy)) {
                return 'Honor';
            }

            return 'Lainnya';
        });
    }

    protected function statusPegawaiBadge(): Attribute
    {
        return Attribute::get(function () {
            return match ($this->status_pegawai) {
                'Tetap' => ['label' => 'Tetap',   'code' => 'tetap',   'class' => 'badge-success', 'color' => '#10b981'],
                'Kontrak' => ['label' => 'Kontrak', 'code' => 'kontrak', 'class' => 'badge-primary', 'color' => '#0284c7'],
                'Honor' => ['label' => 'Honor',   'code' => 'honor',   'class' => 'badge-warning', 'color' => '#f59e0b'],
                default => ['label' => $this->status_pegawai, 'code' => 'lainnya', 'class' => 'badge-secondary', 'color' => '#64748b'],
            };
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nipy', 'no_induk');
    }

    public function jenjangPendidikan(): BelongsTo
    {
        return $this->belongsTo(JenjangPendidikan::class, 'akademik_pelengkap_jenjang_pendidikan_id');
    }

    /**
     * URL foto profil karyawan dari aplikasi kepegawaian.
     */
    protected function fotoUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            $baseUrl = rtrim((string) (config('services.kepegawaian.app_url') ?: config('services.kepegawaian.url') ?: ''), '/');
            $nipy = trim((string) ($this->nipy ?? ''));

            if ($baseUrl === '' || $nipy === '') {
                return null;
            }

            return $baseUrl.'/data-master/karyawan/'.rawurlencode($nipy).'/foto';
        });
    }
}
