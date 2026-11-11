<?php

namespace App\Models\Admin\DataMaster;

use App\Models\Admin\DataPelengkap\BerkebutuhanKhusus;
use App\Models\Admin\DataPelengkap\JenjangPendidikan;
use App\Models\Admin\DataPelengkap\Pekerjaan;
use App\Models\Admin\DataPelengkap\Penghasilan;
use App\Models\Admin\DataPelengkap\Transportasi;
use App\Models\User;
use Database\Factories\Admin\DataMaster\SiswaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'nis',
    'akademik_master_kelas_id',
    'karyawan_nipy',
    'akademik_master_ekstrakuler_id',
    'akademik_master_jemputan_id',
    'jenis_pendaftaran',
    'nama_lengkap',
    'jenis_kelamin',
    'nisn',
    'nik',
    'no_kk',
    'tempat_lahir',
    'tanggal_lahir',
    'akta_lahir',
    'agama',
    'kewarganegaraan',
    'nama_negara',
    'akademik_pelengkap_berkebutuhan_khusus_id',
    'alamat',
    'rt',
    'rw',
    'desa',
    'kecamatan',
    'kabupaten',
    'provinsi',
    'kode_pos',
    'lintang',
    'bujur',
    'tempat_tinggal',
    'akademik_pelengkap_transportasi_id',
    'anak_ke',
    'jumlah_saudara',
    'nik_ayah',
    'nama_ayah',
    'lahir_ayah',
    'akademik_pelengkap_jenjang_pendidikan_ayah_id',
    'akademik_pelengkap_pekerjaan_ayah_id',
    'akademik_pelengkap_penghasilan_ayah_id',
    'akademik_pelengkap_berkebutuhan_khusus_ayah_id',
    'nik_ibu',
    'nama_ibu',
    'lahir_ibu',
    'akademik_pelengkap_jenjang_pendidikan_ibu_id',
    'akademik_pelengkap_pekerjaan_ibu_id',
    'akademik_pelengkap_penghasilan_ibu_id',
    'akademik_pelengkap_berkebutuhan_khusus_ibu_id',
    'nik_wali',
    'nama_wali',
    'lahir_wali',
    'akademik_pelengkap_jenjang_pendidikan_wali_id',
    'akademik_pelengkap_pekerjaan_wali_id',
    'akademik_pelengkap_penghasilan_wali_id',
    'akademik_pelengkap_berkebutuhan_khusus_wali_id',
    'nomor_hp',
    'whatsapp',
    'email',
    'tinggi_badan',
    'berat_badan',
    'jarak',
    'lingkar_kepala',
    'waktu_tempuh',
    'avatar',
    'status',
])]
#[Table('akademik_master_siswa')]
#[UseFactory(SiswaFactory::class)]
class Siswa extends Model
{
    /** @use HasFactory<SiswaFactory> */
    use HasFactory;

    protected $primaryKey = 'nis';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * Pilihan jenis kelamin siswa.
     *
     * @var array<string, string>
     */
    public const JENIS_KELAMIN_OPTIONS = [
        'L' => 'Laki-laki',
        'P' => 'Perempuan',
    ];

    /**
     * Pilihan jenis pendaftaran siswa.
     *
     * @var list<string>
     */
    public const JENIS_PENDAFTARAN_OPTIONS = [
        'Siswa Baru',
        'Pindahan',
        'Lanjutan',
    ];

    /**
     * Pilihan agama siswa.
     *
     * @var list<string>
     */
    public const AGAMA_OPTIONS = [
        'Islam',
        'Kristen',
        'Katolik',
        'Hindu',
        'Buddha',
        'Konghucu',
        'Lainnya',
    ];

    /**
     * Pilihan kewarganegaraan siswa.
     *
     * @var list<string>
     */
    public const KEWARGANEGARAAN_OPTIONS = ['WNI', 'WNA'];

    /**
     * Pilihan jenis tempat tinggal siswa.
     *
     * @var list<string>
     */
    public const TEMPAT_TINGGAL_OPTIONS = [
        'Bersama Orang Tua',
        'Wali',
        'Kos',
        'Asrama',
        'Panti Asuhan',
        'Lainnya',
    ];

    /**
     * Status keaktifan default siswa yang dianggap aktif.
     */
    public const STATUS_AKTIF = 'Aktif';

    /**
     * Pilihan status keaktifan siswa.
     *
     * @var list<string>
     */
    public const STATUS_OPTIONS = [
        self::STATUS_AKTIF,
        'Nonaktif',
        'Lulus',
        'Pindah',
        'Keluar',
    ];

    /**
     * Relasi yang di-eager load pada halaman daftar siswa dengan kolom minimal.
     *
     * @var list<string>
     */
    public const INDEX_RELATIONS = [
        'kelas:id,nama_kelas,jenjang_kelas',
        'karyawan:nipy,nama_lengkap,gelar',
        'ekstrakurikuler:id,nama_ekstrakurikuler',
        'jemputan:id,karyawan_nipy,harga_pp,harga_setengah',
        'jemputan.petugas:nipy,nama_lengkap,gelar',
    ];

    /**
     * Relasi lengkap yang dimuat pada halaman detail siswa untuk mencegah N+1.
     *
     * @var list<string>
     */
    public const DETAIL_RELATIONS = [
        'kelas:id,akademik_master_tahun_ajaran_id,nama_kelas,jenjang_kelas,tingkatan_kelas',
        'kelas.tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
        'karyawan:nipy,nama_lengkap,gelar',
        'ekstrakurikuler:id,nama_ekstrakurikuler,biaya,unit',
        'jemputan:id,karyawan_nipy,harga_pp,harga_setengah',
        'jemputan.petugas:nipy,nama_lengkap,gelar',
        'berkebutuhanKhusus:id,nama_berkebutuhan_khusus',
        'transportasi:id,nama_transportasi',
        'jenjangPendidikanAyah:id,nama_jenjang_pendidikan',
        'pekerjaanAyah:id,nama_pekerjaan',
        'penghasilanAyah:id,nama_penghasilan',
        'berkebutuhanKhususAyah:id,nama_berkebutuhan_khusus',
        'jenjangPendidikanIbu:id,nama_jenjang_pendidikan',
        'pekerjaanIbu:id,nama_pekerjaan',
        'penghasilanIbu:id,nama_penghasilan',
        'berkebutuhanKhususIbu:id,nama_berkebutuhan_khusus',
        'jenjangPendidikanWali:id,nama_jenjang_pendidikan',
        'pekerjaanWali:id,nama_pekerjaan',
        'penghasilanWali:id,nama_penghasilan',
        'berkebutuhanKhususWali:id,nama_berkebutuhan_khusus',
    ];

    /**
     * Atribut yang di-cast secara otomatis.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'akademik_master_kelas_id' => 'integer',
            'akademik_master_ekstrakuler_id' => 'integer',
            'akademik_master_jemputan_id' => 'integer',
            'akademik_pelengkap_berkebutuhan_khusus_id' => 'integer',
            'akademik_pelengkap_transportasi_id' => 'integer',
            'akademik_pelengkap_jenjang_pendidikan_ayah_id' => 'integer',
            'akademik_pelengkap_pekerjaan_ayah_id' => 'integer',
            'akademik_pelengkap_penghasilan_ayah_id' => 'integer',
            'akademik_pelengkap_berkebutuhan_khusus_ayah_id' => 'integer',
            'akademik_pelengkap_jenjang_pendidikan_ibu_id' => 'integer',
            'akademik_pelengkap_pekerjaan_ibu_id' => 'integer',
            'akademik_pelengkap_penghasilan_ibu_id' => 'integer',
            'akademik_pelengkap_berkebutuhan_khusus_ibu_id' => 'integer',
            'akademik_pelengkap_jenjang_pendidikan_wali_id' => 'integer',
            'akademik_pelengkap_pekerjaan_wali_id' => 'integer',
            'akademik_pelengkap_penghasilan_wali_id' => 'integer',
            'akademik_pelengkap_berkebutuhan_khusus_wali_id' => 'integer',
            'tanggal_lahir' => 'date',
            'anak_ke' => 'integer',
            'jumlah_saudara' => 'integer',
            'tinggi_badan' => 'integer',
            'berat_badan' => 'integer',
            'jarak' => 'float',
            'lingkar_kepala' => 'integer',
            'waktu_tempuh' => 'integer',
        ];
    }

    /**
     * Eager load relasi utama untuk daftar siswa tanpa N+1.
     */
    #[Scope]
    protected function withMasterRelations(Builder $query): Builder
    {
        return $query->with(self::INDEX_RELATIONS);
    }

    /**
     * Eager load seluruh relasi biodata, layanan, dan keluarga siswa.
     */
    #[Scope]
    protected function withDetailRelations(Builder $query): Builder
    {
        return $query->with(self::DETAIL_RELATIONS);
    }

    /**
     * Filter hanya siswa yang berstatus Aktif.
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->whereIn($query->qualifyColumn('status'), [self::STATUS_AKTIF, 'aktif', 'AKTIF']);
    }

    /**
     * Filter siswa dengan status selain Aktif (Nonaktif, Lulus, Pindah, Keluar, dll).
     */
    #[Scope]
    protected function inactive(Builder $query): Builder
    {
        $statusColumn = $query->qualifyColumn('status');

        return $query->where(static function (Builder $statusQuery) use ($statusColumn): void {
            $statusQuery->whereNull($statusColumn)
                ->orWhereNotIn($statusColumn, [self::STATUS_AKTIF, 'aktif', 'AKTIF']);
        });
    }

    /**
     * Label jenis kelamin yang terbaca manusia.
     */
    protected function jenisKelaminLabel(): Attribute
    {
        return Attribute::get(function (): string {
            $jk = strtoupper(trim((string) $this->jenis_kelamin));

            if ($jk === 'L' || $jk === 'LAKI-LAKI') {
                return 'Laki-laki';
            }

            if ($jk === 'P' || $jk === 'PEREMPUAN') {
                return 'Perempuan';
            }

            return $this->jenis_kelamin ?: '-';
        });
    }

    /**
     * Kelas badge Bootstrap untuk status siswa.
     */
    protected function statusBadgeClass(): Attribute
    {
        return Attribute::get(fn (): string => match (strtolower(trim((string) $this->status))) {
            'aktif' => 'badge-success',
            'lulus' => 'badge-primary',
            'pindah' => 'badge-warning text-dark',
            'keluar' => 'badge-danger',
            default => 'badge-secondary',
        });
    }

    /**
     * URL publik avatar siswa apabila tersedia.
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! $this->avatar) {
                return null;
            }

            return Storage::disk('public')->url($this->avatar);
        });
    }

    /**
     * Inisial nama lengkap untuk fallback avatar.
     */
    protected function inisialNama(): Attribute
    {
        return Attribute::get(function (): string {
            $nama = trim((string) $this->nama_lengkap);

            if ($nama === '') {
                return 'S';
            }

            return mb_strtoupper(mb_substr($nama, 0, 1));
        });
    }

    /**
     * Relasi ke akun User SSO berdasarkan NIS / nomor induk.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nis', 'no_induk');
    }

    /**
     * Relasi ke Kelas.
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'akademik_master_kelas_id');
    }

    /**
     * Relasi ke Guru Pembimbing / Wali (Karyawan).
     */
    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_nipy', 'nipy');
    }

    /**
     * Alias relasi guru yang mengarah ke model Karyawan.
     */
    public function guru(): BelongsTo
    {
        return $this->karyawan();
    }

    /**
     * Relasi ke Ekstrakurikuler.
     */
    public function ekstrakurikuler(): BelongsTo
    {
        return $this->belongsTo(Ekstrakurikuler::class, 'akademik_master_ekstrakuler_id');
    }

    /**
     * Relasi ke Jemputan.
     */
    public function jemputan(): BelongsTo
    {
        return $this->belongsTo(Jemputan::class, 'akademik_master_jemputan_id');
    }

    /**
     * Relasi ke Berkebutuhan Khusus siswa.
     */
    public function berkebutuhanKhusus(): BelongsTo
    {
        return $this->belongsTo(BerkebutuhanKhusus::class, 'akademik_pelengkap_berkebutuhan_khusus_id');
    }

    /**
     * Relasi ke Transportasi siswa.
     */
    public function transportasi(): BelongsTo
    {
        return $this->belongsTo(Transportasi::class, 'akademik_pelengkap_transportasi_id');
    }

    /**
     * Relasi ke Jenjang Pendidikan Ayah.
     */
    public function jenjangPendidikanAyah(): BelongsTo
    {
        return $this->belongsTo(JenjangPendidikan::class, 'akademik_pelengkap_jenjang_pendidikan_ayah_id');
    }

    /**
     * Relasi ke Pekerjaan Ayah.
     */
    public function pekerjaanAyah(): BelongsTo
    {
        return $this->belongsTo(Pekerjaan::class, 'akademik_pelengkap_pekerjaan_ayah_id');
    }

    /**
     * Relasi ke Penghasilan Ayah.
     */
    public function penghasilanAyah(): BelongsTo
    {
        return $this->belongsTo(Penghasilan::class, 'akademik_pelengkap_penghasilan_ayah_id');
    }

    /**
     * Relasi ke Berkebutuhan Khusus Ayah.
     */
    public function berkebutuhanKhususAyah(): BelongsTo
    {
        return $this->belongsTo(BerkebutuhanKhusus::class, 'akademik_pelengkap_berkebutuhan_khusus_ayah_id');
    }

    /**
     * Relasi ke Jenjang Pendidikan Ibu.
     */
    public function jenjangPendidikanIbu(): BelongsTo
    {
        return $this->belongsTo(JenjangPendidikan::class, 'akademik_pelengkap_jenjang_pendidikan_ibu_id');
    }

    /**
     * Relasi ke Pekerjaan Ibu.
     */
    public function pekerjaanIbu(): BelongsTo
    {
        return $this->belongsTo(Pekerjaan::class, 'akademik_pelengkap_pekerjaan_ibu_id');
    }

    /**
     * Relasi ke Penghasilan Ibu.
     */
    public function penghasilanIbu(): BelongsTo
    {
        return $this->belongsTo(Penghasilan::class, 'akademik_pelengkap_penghasilan_ibu_id');
    }

    /**
     * Relasi ke Berkebutuhan Khusus Ibu.
     */
    public function berkebutuhanKhususIbu(): BelongsTo
    {
        return $this->belongsTo(BerkebutuhanKhusus::class, 'akademik_pelengkap_berkebutuhan_khusus_ibu_id');
    }

    /**
     * Relasi ke Jenjang Pendidikan Wali.
     */
    public function jenjangPendidikanWali(): BelongsTo
    {
        return $this->belongsTo(JenjangPendidikan::class, 'akademik_pelengkap_jenjang_pendidikan_wali_id');
    }

    /**
     * Relasi ke Pekerjaan Wali.
     */
    public function pekerjaanWali(): BelongsTo
    {
        return $this->belongsTo(Pekerjaan::class, 'akademik_pelengkap_pekerjaan_wali_id');
    }

    /**
     * Relasi ke Penghasilan Wali.
     */
    public function penghasilanWali(): BelongsTo
    {
        return $this->belongsTo(Penghasilan::class, 'akademik_pelengkap_penghasilan_wali_id');
    }

    /**
     * Relasi ke Berkebutuhan Khusus Wali.
     */
    public function berkebutuhanKhususWali(): BelongsTo
    {
        return $this->belongsTo(BerkebutuhanKhusus::class, 'akademik_pelengkap_berkebutuhan_khusus_wali_id');
    }

    /**
     * Relasi ke riwayat keanggotaan kelas per tahun ajaran / semester.
     */
    public function anggotaKelas(): HasMany
    {
        return $this->hasMany(AnggotaKelas::class, 'siswa_nis', 'nis');
    }

    /**
     * Mengambil daftar ID tahun ajaran yang sudah pernah diikuti siswa dari relasi ter-load.
     *
     * @return list<int>
     */
    public function registeredTahunAjaranIds(?int $excludeAnggotaKelasId = null): array
    {
        if (! $this->relationLoaded('anggotaKelas')) {
            return [];
        }

        return $this->anggotaKelas
            ->filter(static fn (AnggotaKelas $anggota): bool => $excludeAnggotaKelasId === null || (int) $anggota->id !== $excludeAnggotaKelasId)
            ->map(static function (AnggotaKelas $anggota): ?int {
                if (! $anggota->relationLoaded('kelas') || $anggota->kelas === null) {
                    return null;
                }

                return (int) $anggota->kelas->akademik_master_tahun_ajaran_id;
            })
            ->filter(static fn (?int $id): bool => $id !== null && $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Mengambil ringkasan kelas terakhir / sebelumnya siswa tanpa memicu lazy loading.
     *
     * @return array{
     *     nama_kelas: string,
     *     jenjang_kelas: string,
     *     tahun_ajaran: string|null,
     *     semester: string|null,
     *     tahun_ajaran_id: int|null
     * }|null
     */
    public function resolvePreviousKelasSummary(?int $excludeAnggotaKelasId = null): ?array
    {
        $selectedKelas = null;

        if ($this->relationLoaded('anggotaKelas')) {
            $latestMembership = $this->anggotaKelas
                ->filter(static function (AnggotaKelas $anggota) use ($excludeAnggotaKelasId): bool {
                    if ($excludeAnggotaKelasId !== null && (int) $anggota->id === $excludeAnggotaKelasId) {
                        return false;
                    }

                    return $anggota->relationLoaded('kelas') && $anggota->kelas !== null;
                })
                ->sortByDesc(static function (AnggotaKelas $anggota): string {
                    $kelas = $anggota->kelas;
                    $ta = $kelas->relationLoaded('tahunAjaran') ? $kelas->tahunAjaran : null;

                    return sprintf(
                        '%s-%s-%010d',
                        $ta?->nama_tahun_ajaran ?? '0000/0000',
                        $ta?->semester ?? '0',
                        (int) ($anggota->id ?? 0)
                    );
                })
                ->first();

            $selectedKelas = $latestMembership?->kelas;
        }

        if ($selectedKelas === null && $this->relationLoaded('kelas')) {
            $selectedKelas = $this->kelas;
        }

        if ($selectedKelas === null) {
            return null;
        }

        $tahunAjaran = $selectedKelas->relationLoaded('tahunAjaran') ? $selectedKelas->tahunAjaran : null;

        return [
            'nama_kelas' => (string) $selectedKelas->nama_kelas,
            'jenjang_kelas' => (string) $selectedKelas->jenjang_kelas,
            'tahun_ajaran' => $tahunAjaran?->nama_tahun_ajaran,
            'semester' => $tahunAjaran?->semester,
            'tahun_ajaran_id' => $selectedKelas->akademik_master_tahun_ajaran_id
                ? (int) $selectedKelas->akademik_master_tahun_ajaran_id
                : null,
        ];
    }
}
