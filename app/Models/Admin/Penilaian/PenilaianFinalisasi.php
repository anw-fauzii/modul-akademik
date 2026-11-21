<?php

namespace App\Models\Admin\Penilaian;

use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Concerns\HasSqidRouteKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'scope_key',
    'akademik_master_tahun_ajaran_id',
    'jenis_komponen',
    'akademik_master_kelas_id',
    'akademik_pelajaran_mata_pelajaran_id',
    'akademik_master_ekstrakuler_id',
    'periode',
    'status',
    'finalized_by_user_id',
    'finalized_by_name',
    'finalized_at',
    'unlocked_by_user_id',
    'unlocked_by_name',
    'unlocked_at',
    'unlock_reason',
])]
#[Table('akademik_penilaian_finalisasi')]
class PenilaianFinalisasi extends Model
{
    use HasSqidRouteKey;

    public const string JENIS_MAPEL = 'mapel';

    public const string JENIS_EKSKUL_WAJIB = 'ekskul_wajib';

    public const string JENIS_EKSKUL_PILIHAN = 'ekskul_pilihan';

    public const string JENIS_T2Q = 't2q';

    public const string JENIS_PRESENSI = 'presensi';

    public const array JENIS_OPTIONS = [
        self::JENIS_MAPEL => 'Mata Pelajaran',
        self::JENIS_EKSKUL_WAJIB => 'Ekskul Wajib (Pramuka)',
        self::JENIS_EKSKUL_PILIHAN => 'Ekskul Pilihan',
        self::JENIS_T2Q => 'Program T2Q',
        self::JENIS_PRESENSI => 'Presensi Siswa',
    ];

    public const string STATUS_DRAFT = 'draft';

    public const string STATUS_FINAL = 'final';

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
            'akademik_pelajaran_mata_pelajaran_id' => 'integer',
            'akademik_master_ekstrakuler_id' => 'integer',
            'finalized_by_user_id' => 'integer',
            'finalized_at' => 'datetime',
            'unlocked_by_user_id' => 'integer',
            'unlocked_at' => 'datetime',
        ];
    }

    /**
     * Membangun kunci unik deterministik untuk mencegah duplikasi baris finalisasi di DB.
     */
    public static function buildScopeKey(
        int $tahunAjaranId,
        string $jenisKomponen,
        ?int $kelasId,
        ?int $mapelId,
        ?int $ekskulId,
        string $periode
    ): string {
        $normPeriode = strtolower(trim($periode)) === PeriodeTp::PERIODE_SEMESTER
            ? PeriodeTp::PERIODE_SEMESTER
            : PeriodeTp::PERIODE_MID;

        return sprintf(
            'ta:%d:%s:k:%d:m:%d:e:%d:p:%s',
            $tahunAjaranId,
            trim($jenisKomponen),
            (int) ($kelasId ?? 0),
            (int) ($mapelId ?? 0),
            (int) ($ekskulId ?? 0),
            $normPeriode
        );
    }

    public function isFinal(): bool
    {
        return $this->status === self::STATUS_FINAL;
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'akademik_master_tahun_ajaran_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'akademik_master_kelas_id');
    }

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class, 'akademik_pelajaran_mata_pelajaran_id');
    }

    public function ekstrakurikuler(): BelongsTo
    {
        return $this->belongsTo(Ekstrakurikuler::class, 'akademik_master_ekstrakuler_id');
    }

    public function finalizedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by_user_id');
    }

    public function unlockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unlocked_by_user_id');
    }
}
