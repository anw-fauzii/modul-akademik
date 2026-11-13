<?php

namespace App\Models\Admin\Presensi;

use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Kelas;
use Database\Factories\Admin\Presensi\PresensiSiswaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

#[Fillable([
    'anggota_kelas_id',
    'tanggal',
    'jam_masuk',
    'status',
    'keterlambatan_menit',
])]
#[Table('akademik_presensi_siswa')]
#[UseFactory(PresensiSiswaFactory::class)]
class PresensiSiswa extends Model
{
    /** @use HasFactory<PresensiSiswaFactory> */
    use HasFactory;

    /**
     * Batas maksimal jam masuk tepat waktu (JJ:MM).
     */
    public const BATAS_JAM_MASUK = '07:35';

    /**
     * Pilihan status presensi siswa.
     *
     * @var array<string, string>
     */
    public const STATUS_OPTIONS = [
        'hadir' => 'Hadir',
        'terlambat' => 'Terlambat',
        'izin' => 'Izin',
        'sakit' => 'Sakit',
        'alpa' => 'Alpa',
    ];

    /**
     * Relasi minimal untuk mencegah query N+1.
     *
     * @var list<string>
     */
    public const MASTER_RELATIONS = [
        'anggotaKelas:id,siswa_nis,akademik_master_kelas_id,pendaftaran',
        'anggotaKelas.siswa:nis,nama_lengkap,nisn,jenis_kelamin,status',
        'anggotaKelas.kelas:id,akademik_master_tahun_ajaran_id,nama_kelas,jenjang_kelas,tingkatan_kelas',
        'anggotaKelas.kelas.tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
    ];

    /**
     * Atribut yang di-cast secara otomatis.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'anggota_kelas_id' => 'integer',
            'tanggal' => 'date',
            'keterlambatan_menit' => 'integer',
        ];
    }

    /**
     * Menghitung selisih keterlambatan (menit) terhadap batas maksimal jam masuk.
     */
    public static function calculateKeterlambatanMenit(?string $jamMasuk): int
    {
        if (! is_string($jamMasuk) || trim($jamMasuk) === '') {
            return 0;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', trim($jamMasuk), $matches) !== 1) {
            return 0;
        }

        $hour = (int) $matches[1];
        $minute = (int) $matches[2];
        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
            return 0;
        }

        [$maxHour, $maxMinute] = array_map('intval', explode(':', self::BATAS_JAM_MASUK));
        $entryTotalMinutes = ($hour * 60) + $minute;
        $maxTotalMinutes = ($maxHour * 60) + $maxMinute;

        return max(0, $entryTotalMinutes - $maxTotalMinutes);
    }

    /**
     * Eager load relasi utama siswa, kelas, dan tahun ajaran tanpa N+1.
     */
    #[Scope]
    protected function withMasterRelations(Builder $query): Builder
    {
        return $query->with(self::MASTER_RELATIONS);
    }

    /**
     * Filter catatan presensi berdasarkan kelas tertentu.
     */
    #[Scope]
    protected function forKelas(Builder $query, int $kelasId): Builder
    {
        return $query->whereHas(
            'anggotaKelas',
            fn (Builder $q) => $q->where('akademik_master_kelas_id', $kelasId)
        );
    }

    /**
     * Filter catatan presensi berdasarkan ID tahun ajaran (semester) tertentu.
     */
    #[Scope]
    protected function forTahunAjaran(Builder $query, int $tahunAjaranId): Builder
    {
        return $query->whereHas(
            'anggotaKelas.kelas',
            fn (Builder $q) => $q->where('akademik_master_tahun_ajaran_id', $tahunAjaranId)
        );
    }

    /**
     * Filter catatan presensi berdasarkan nama tahun ajaran (mis. "2026/2027") dan semester opsional.
     */
    #[Scope]
    protected function forNamaTahunAjaran(Builder $query, string $namaTahunAjaran, ?string $semester = null): Builder
    {
        return $query->whereHas('anggotaKelas.kelas.tahunAjaran', function (Builder $taQuery) use ($namaTahunAjaran, $semester): void {
            $taQuery->where('nama_tahun_ajaran', $namaTahunAjaran)
                ->when($semester !== null && $semester !== '', fn (Builder $q) => $q->where('semester', $semester));
        });
    }

    /**
     * Relasi ke anggota kelas siswa.
     */
    public function anggotaKelas(): BelongsTo
    {
        return $this->belongsTo(AnggotaKelas::class, 'anggota_kelas_id');
    }

    /**
     * Relasi langsung ke kelas melalui anggota kelas.
     */
    public function kelas(): HasOneThrough
    {
        return $this->hasOneThrough(
            Kelas::class,
            AnggotaKelas::class,
            'id',
            'id',
            'anggota_kelas_id',
            'akademik_master_kelas_id'
        );
    }

    /**
     * Label status presensi untuk tampilan.
     */
    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn (): string => self::STATUS_OPTIONS[$this->status] ?? ucfirst((string) $this->status));
    }

    /**
     * Metadata badge status presensi untuk tampilan UI.
     */
    protected function statusBadge(): Attribute
    {
        return Attribute::get(function (): array {
            return match ($this->status) {
                'hadir' => [
                    'label' => 'Hadir',
                    'class' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                    'dot' => 'bg-emerald-500',
                ],
                'terlambat' => [
                    'label' => 'Terlambat',
                    'class' => 'bg-amber-50 text-amber-700 border border-amber-200',
                    'dot' => 'bg-amber-500',
                ],
                'izin' => [
                    'label' => 'Izin',
                    'class' => 'bg-sky-50 text-sky-700 border border-sky-200',
                    'dot' => 'bg-sky-500',
                ],
                'sakit' => [
                    'label' => 'Sakit',
                    'class' => 'bg-indigo-50 text-indigo-700 border border-indigo-200',
                    'dot' => 'bg-indigo-500',
                ],
                default => [
                    'label' => 'Alpa',
                    'class' => 'bg-rose-50 text-rose-700 border border-rose-200',
                    'dot' => 'bg-rose-500',
                ],
            };
        });
    }

    /**
     * Teks keterangan ringkas mengenai jam masuk, keterlambatan, atau ketidakhadiran siswa.
     */
    protected function statusKeterangan(): Attribute
    {
        return Attribute::get(function (): string {
            $jamMasuk = is_string($this->jam_masuk) && trim($this->jam_masuk) !== ''
                ? substr(trim($this->jam_masuk), 0, 5)
                : null;
            $telatMenit = (int) $this->keterlambatan_menit;
            $batas = self::BATAS_JAM_MASUK;

            return match ($this->status) {
                'terlambat' => match (true) {
                    $jamMasuk !== null && $telatMenit > 0 => "Terlambat {$telatMenit} menit pada pukul {$jamMasuk}.",
                    $jamMasuk !== null => "Terlambat pada pukul {$jamMasuk} (batas tepat waktu {$batas}).",
                    $telatMenit > 0 => "Terlambat {$telatMenit} menit dari batas waktu {$batas}.",
                    default => 'Siswa tercatat masuk terlambat.',
                },
                'hadir' => match (true) {
                    $jamMasuk !== null && $telatMenit > 0 => "Siswa hadir pada pukul {$jamMasuk} (terlambat {$telatMenit} menit dari batas {$batas}).",
                    $jamMasuk !== null => "Siswa hadir tepat waktu pada pukul {$jamMasuk} (batas maksimal {$batas}).",
                    default => 'Siswa tercatat hadir tepat waktu.',
                },
                'izin' => $jamMasuk !== null
                    ? "Siswa tidak masuk karena izin resmi (dicatat pukul {$jamMasuk})."
                    : 'Siswa tidak masuk karena izin resmi.',
                'sakit' => $jamMasuk !== null
                    ? "Siswa tidak masuk karena sakit (dicatat pukul {$jamMasuk})."
                    : 'Siswa tidak masuk karena sakit.',
                default => $jamMasuk !== null
                    ? "Siswa tidak hadir tanpa keterangan / Alpa (dicatat pukul {$jamMasuk})."
                    : 'Siswa tidak hadir tanpa keterangan (Alpa).',
            };
        });
    }
}
