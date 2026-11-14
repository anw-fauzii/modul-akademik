<?php

namespace App\Models\Admin\Pelajaran;

use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\TahunAjaran;
use Database\Factories\Admin\Pelajaran\JadwalPelajaranFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'akademik_master_tahun_ajaran_id',
    'akademik_master_kelas_id',
    'akademik_pelajaran_jam_pelajaran_id',
    'akademik_pelajaran_mata_pelajaran_id',
    'karyawan_nipy',
    'hari',
    'ruang',
    'keterangan',
    'status',
])]
#[Table('akademik_pelajaran_jadwal_pelajaran')]
#[UseFactory(JadwalPelajaranFactory::class)]
class JadwalPelajaran extends Model
{
    /** @use HasFactory<JadwalPelajaranFactory> */
    use HasFactory;

    public const int HARI_SENIN = 1;

    public const int HARI_SELASA = 2;

    public const int HARI_RABU = 3;

    public const int HARI_KAMIS = 4;

    public const int HARI_JUMAT = 5;

    /**
     * Daftar hari pembelajaran aktif (Senin sampai Jumat).
     *
     * @var array<int, string>
     */
    public const array HARI_OPTIONS = [
        self::HARI_SENIN => 'Senin',
        self::HARI_SELASA => 'Selasa',
        self::HARI_RABU => 'Rabu',
        self::HARI_KAMIS => 'Kamis',
        self::HARI_JUMAT => 'Jumat',
    ];

    /**
     * Pilihan cepat untuk slot non-mata pelajaran.
     *
     * @var list<string>
     */
    public const array NON_MAPEL_PRESETS = [
        'Pembiasaan Pagi',
        'Upacara Bendera',
        'Istirahat',
        'Makan Bersama',
        'Pulang',
    ];

    /**
     * Relasi utama yang selalu di-eager load untuk mencegah N+1.
     *
     * @var list<string>
     */
    public const array INDEX_RELATIONS = [
        'tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
        'kelas:id,akademik_master_tahun_ajaran_id,nama_kelas,jenjang_kelas,tingkatan_kelas',
        'jamPelajaran:id,jam_ke,unit,jam_mulai,jam_selesai,keterangan',
        'mataPelajaran:id,singkatan,nama_mata_pelajaran,sifat,unit',
        'guru:nipy,nama_lengkap,gelar',
    ];

    /**
     * Nilai default atribut model.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => true,
    ];

    /**
     * Atribut yang di-cast secara otomatis.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'akademik_master_tahun_ajaran_id' => 'integer',
            'akademik_master_kelas_id' => 'integer',
            'akademik_pelajaran_jam_pelajaran_id' => 'integer',
            'akademik_pelajaran_mata_pelajaran_id' => 'integer',
            'hari' => 'integer',
            'status' => 'boolean',
        ];
    }

    /**
     * Eager load seluruh relasi utama jadwal pelajaran dengan kolom minimal untuk mencegah N+1.
     */
    #[Scope]
    protected function withMasterRelations(Builder $query): Builder
    {
        return $query->with(self::INDEX_RELATIONS);
    }

    /**
     * Mengurutkan jadwal berdasarkan hari, kelas, slot jam pelajaran, lalu id.
     */
    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query
            ->orderBy($query->qualifyColumn('hari'))
            ->orderBy($query->qualifyColumn('akademik_master_kelas_id'))
            ->orderBy($query->qualifyColumn('akademik_pelajaran_jam_pelajaran_id'))
            ->orderBy($query->qualifyColumn('id'));
    }

    /**
     * Nama hari pembelajaran dalam Bahasa Indonesia.
     */
    protected function hariLabel(): Attribute
    {
        return Attribute::get(fn (): string => self::HARI_OPTIONS[(int) $this->hari] ?? '-');
    }

    /**
     * Kelas badge Bootstrap untuk hari pembelajaran.
     */
    protected function hariBadgeClass(): Attribute
    {
        return Attribute::get(fn (): string => match ((int) $this->hari) {
            self::HARI_SENIN => 'badge-primary',
            self::HARI_SELASA => 'badge-info',
            self::HARI_RABU => 'badge-success',
            self::HARI_KAMIS => 'badge-warning text-dark',
            self::HARI_JUMAT => 'badge-dark',
            default => 'badge-secondary',
        });
    }

    /**
     * Menandakan apakah slot jadwal ini merupakan kegiatan non-mata pelajaran.
     */
    protected function isNonMapel(): Attribute
    {
        return Attribute::get(fn (): bool => $this->akademik_pelajaran_mata_pelajaran_id === null);
    }

    /**
     * Judul slot yang aman ditampilkan tanpa memicu lazy loading.
     */
    protected function judulTampil(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->akademik_pelajaran_mata_pelajaran_id !== null && $this->relationLoaded('mataPelajaran')) {
                $namaMapel = trim((string) ($this->mataPelajaran?->nama_mata_pelajaran ?? ''));
                if ($namaMapel !== '') {
                    return $namaMapel;
                }
            }

            $ket = trim((string) ($this->keterangan ?? ''));

            return $ket !== '' ? $ket : 'Kegiatan Non-Mapel';
        });
    }

    /**
     * Label status aktif jadwal pelajaran untuk tampilan UI.
     */
    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn (): string => (bool) $this->status ? 'Aktif' : 'Nonaktif');
    }

    /**
     * Kelas badge Bootstrap untuk status jadwal pelajaran.
     */
    protected function statusBadgeClass(): Attribute
    {
        return Attribute::get(fn (): string => (bool) $this->status ? 'badge-success' : 'badge-secondary');
    }

    /**
     * Relasi ke Tahun Ajaran.
     */
    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'akademik_master_tahun_ajaran_id');
    }

    /**
     * Relasi ke Kelas.
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'akademik_master_kelas_id');
    }

    /**
     * Relasi ke Jam Pelajaran.
     */
    public function jamPelajaran(): BelongsTo
    {
        return $this->belongsTo(JamPelajaran::class, 'akademik_pelajaran_jam_pelajaran_id');
    }

    /**
     * Relasi ke Mata Pelajaran (opsional / nullable untuk kegiatan non-mapel).
     */
    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class, 'akademik_pelajaran_mata_pelajaran_id');
    }

    /**
     * Relasi ke Guru Pengampu (Karyawan).
     */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_nipy', 'nipy');
    }
}
