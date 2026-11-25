<?php

namespace App\Models\Admin\DataMaster;

use App\Models\Concerns\HasSqidRouteKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'siswa_nis',
    'akademik_master_tahun_ajaran_id',
    'kelas_asal_id',
    'kelas_tujuan_id',
    'unit',
    'kategori',
    'status_sebelum',
    'status_sesudah',
    'tanggal_efektif',
    'nomor_surat',
    'nomor_ijazah',
    'sekolah_asal_tujuan',
    'alasan',
    'dibatalkan_at',
    'dibatalkan_by_user_id',
    'dibatalkan_by_name',
    'alasan_batal',
    'created_by_user_id',
    'created_by_name',
])]
#[Table('akademik_siswa_mutasi')]
class SiswaMutasi extends Model
{
    use HasSqidRouteKey;

    public const string KATEGORI_MASUK = 'masuk';

    public const string KATEGORI_LULUS = 'lulus';

    public const string KATEGORI_PINDAH = 'pindah';

    public const string KATEGORI_KELUAR = 'keluar';

    public const string KATEGORI_NONAKTIF = 'nonaktif';

    /**
     * Seluruh kategori peristiwa mutasi & kelulusan siswa.
     *
     * @var array<string, string>
     */
    public const array KATEGORI_OPTIONS = [
        self::KATEGORI_MASUK => 'Mutasi Masuk',
        self::KATEGORI_LULUS => 'Kelulusan / Alumni',
        self::KATEGORI_PINDAH => 'Pindah Sekolah',
        self::KATEGORI_KELUAR => 'Keluar / Mengundurkan Diri',
        self::KATEGORI_NONAKTIF => 'Nonaktif',
    ];

    /**
     * Sub-kategori khusus proses Mutasi Keluar (selain kelulusan & masuk).
     *
     * @var array<string, string>
     */
    public const array KATEGORI_KELUAR_OPTIONS = [
        self::KATEGORI_PINDAH => 'Pindah Sekolah',
        self::KATEGORI_KELUAR => 'Keluar / Mengundurkan Diri',
        self::KATEGORI_NONAKTIF => 'Nonaktif',
    ];

    /**
     * Pemetaan kategori transaksi mutasi ke nilai kolom status pada tabel Siswa.
     *
     * @var array<string, string>
     */
    public const array KATEGORI_TARGET_STATUS_MAP = [
        self::KATEGORI_MASUK => Siswa::STATUS_AKTIF,
        self::KATEGORI_LULUS => Siswa::STATUS_LULUS,
        self::KATEGORI_PINDAH => Siswa::STATUS_PINDAH,
        self::KATEGORI_KELUAR => Siswa::STATUS_KELUAR,
        self::KATEGORI_NONAKTIF => Siswa::STATUS_NONAKTIF,
    ];

    /**
     * Relasi minimal yang di-eager load pada daftar ledger mutasi untuk mencegah N+1.
     *
     * @var list<string>
     */
    public const array INDEX_RELATIONS = [
        'siswa:nis,nama_lengkap,status,jenis_kelamin,avatar,akademik_master_kelas_id',
        'tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
        'kelasAsal:id,nama_kelas,jenjang_kelas,tingkatan_kelas',
        'kelasTujuan:id,nama_kelas,jenjang_kelas,tingkatan_kelas',
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
            'kelas_asal_id' => 'integer',
            'kelas_tujuan_id' => 'integer',
            'tanggal_efektif' => 'date',
            'dibatalkan_at' => 'datetime',
            'dibatalkan_by_user_id' => 'integer',
            'created_by_user_id' => 'integer',
        ];
    }

    /**
     * Mengembalikan label kategori yang ramah dibaca.
     */
    public static function labelForKategori(string $kategori): string
    {
        return self::KATEGORI_OPTIONS[$kategori] ?? ucfirst($kategori);
    }

    /**
     * Mengembalikan kelas badge Bootstrap untuk kategori tertentu.
     */
    public static function badgeForKategori(string $kategori): string
    {
        return match ($kategori) {
            self::KATEGORI_MASUK => 'badge-success',
            self::KATEGORI_LULUS => 'badge-info',
            self::KATEGORI_PINDAH => 'badge-warning text-dark',
            self::KATEGORI_KELUAR => 'badge-danger',
            self::KATEGORI_NONAKTIF => 'badge-secondary',
            default => 'badge-secondary',
        };
    }

    /**
     * Label kategori mutasi untuk tampilan UI.
     */
    protected function kategoriLabel(): Attribute
    {
        return Attribute::get(fn (): string => self::labelForKategori((string) $this->kategori));
    }

    /**
     * Kelas badge Bootstrap untuk kategori mutasi.
     */
    protected function kategoriBadgeClass(): Attribute
    {
        return Attribute::get(fn (): string => self::badgeForKategori((string) $this->kategori));
    }

    /**
     * Menandakan apakah catatan mutasi telah dibatalkan (*soft reversal*).
     */
    protected function isDibatalkan(): Attribute
    {
        return Attribute::get(fn (): bool => $this->dibatalkan_at !== null);
    }

    /**
     * Scope transaksi mutasi yang masih aktif (belum dibatalkan).
     */
    #[Scope]
    protected function aktif(Builder $query): Builder
    {
        return $query->whereNull('dibatalkan_at');
    }

    /**
     * Scope transaksi mutasi yang sudah dibatalkan.
     */
    #[Scope]
    protected function dibatalkan(Builder $query): Builder
    {
        return $query->whereNotNull('dibatalkan_at');
    }

    /**
     * Urutkan riwayat mutasi dari peristiwa terbaru.
     */
    #[Scope]
    protected function terbaru(Builder $query): Builder
    {
        return $query->orderByDesc('tanggal_efektif')->orderByDesc('id');
    }

    /**
     * Relasi ke data siswa terkait.
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_nis', 'nis');
    }

    /**
     * Relasi ke tahun ajaran saat peristiwa mutasi/kelulusan terjadi.
     */
    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'akademik_master_tahun_ajaran_id');
    }

    /**
     * Relasi ke snapshot kelas asal siswa.
     */
    public function kelasAsal(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_asal_id');
    }

    /**
     * Relasi ke kelas tujuan penempatan (khusus Mutasi Masuk).
     */
    public function kelasTujuan(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_tujuan_id');
    }

    /**
     * Relasi ke pengguna pencatat transaksi mutasi.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Relasi ke pengguna yang membatalkan transaksi mutasi.
     */
    public function dibatalkanBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibatalkan_by_user_id');
    }
}

