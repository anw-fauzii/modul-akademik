<?php

namespace App\Models\Admin\Prestasi;

use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Concerns\HasSqidRouteKey;
use Database\Factories\Admin\Prestasi\PrestasiSiswaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'akademik_master_tahun_ajaran_id',
    'nama_prestasi',
    'kategori',
    'tingkat',
    'peringkat',
    'penyelenggara',
    'tanggal',
    'keterangan',
    'foto',
])]
#[Table('akademik_prestasi_siswa')]
#[UseFactory(PrestasiSiswaFactory::class)]
class PrestasiSiswa extends Model
{
    /** @use HasFactory<PrestasiSiswaFactory> */
    use HasFactory;

    use HasSqidRouteKey;

    public const KATEGORI_AKADEMIK = 'akademik';

    public const KATEGORI_NON_AKADEMIK = 'non_akademik';

    /**
     * Pilihan kategori prestasi siswa.
     *
     * @var array<string, string>
     */
    public const KATEGORI_OPTIONS = [
        self::KATEGORI_AKADEMIK => 'Akademik',
        self::KATEGORI_NON_AKADEMIK => 'Non-Akademik',
    ];

    /**
     * Pilihan preset tingkat kejuaraan/prestasi (tetap mendukung input kustom).
     *
     * @var list<string>
     */
    public const TINGKAT_PRESETS = [
        'Sekolah',
        'Kecamatan',
        'Kabupaten/Kota',
        'Provinsi',
        'Nasional',
        'Internasional',
    ];

    /**
     * Pilihan preset peringkat/capaian prestasi (tetap mendukung input kustom).
     *
     * @var list<string>
     */
    public const PERINGKAT_PRESETS = [
        'Juara 1',
        'Juara 2',
        'Juara 3',
        'Harapan 1',
        'Harapan 2',
        'Harapan 3',
        'Medali Emas',
        'Medali Perak',
        'Medali Perunggu',
        'Finalis',
        'Peserta Terbaik',
    ];

    /**
     * Relasi minimal yang di-eager load pada daftar prestasi untuk mencegah N+1.
     *
     * @var list<string>
     */
    public const MASTER_RELATIONS = [
        'siswa:nis,akademik_master_kelas_id,nama_lengkap,jenis_kelamin,avatar,status',
        'siswa.kelas:id,nama_kelas,jenjang_kelas,tingkatan_kelas',
        'tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
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
            'tanggal' => 'date',
        ];
    }

    /**
     * Eager load relasi siswa, kelas, dan tahun ajaran tanpa N+1.
     */
    #[Scope]
    protected function withMasterRelations(Builder $query): Builder
    {
        return $query->with(self::MASTER_RELATIONS);
    }

    /**
     * Mengurutkan prestasi dari tanggal pelaksanaan terbaru.
     */
    #[Scope]
    protected function orderedLatest(Builder $query): Builder
    {
        return $query
            ->orderByDesc($query->qualifyColumn('tanggal'))
            ->orderByDesc($query->qualifyColumn('id'));
    }

    /**
     * Relasi ke daftar siswa penerima prestasi (individu maupun beregu) via tabel pivot.
     */
    public function siswa(): BelongsToMany
    {
        return $this->belongsToMany(
            Siswa::class,
            'akademik_prestasi_siswa_anggota',
            'prestasi_siswa_id',
            'siswa_nis',
            'id',
            'nis'
        )
            ->orderBy('nama_lengkap')
            ->withTimestamps();
    }

    /**
     * Relasi ke data master Tahun Ajaran.
     */
    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'akademik_master_tahun_ajaran_id');
    }

    /**
     * Menentukan apakah prestasi ini bersifat beregu (> 1 siswa) dari relasi ter-load.
     */
    protected function isBeregu(): Attribute
    {
        return Attribute::get(
            fn (): bool => $this->relationLoaded('siswa') && $this->siswa->count() > 1
        );
    }

    /**
     * Label sifat keikutsertaan (Individu / Beregu N Siswa).
     */
    protected function jenisPesertaLabel(): Attribute
    {
        return Attribute::get(function (): string {
            if (! $this->relationLoaded('siswa')) {
                return 'Individu';
            }

            $count = $this->siswa->count();

            return $count > 1 ? "Beregu ({$count} Siswa)" : 'Individu';
        });
    }

    /**
     * Ringkasan daftar nama siswa penerima prestasi untuk pencarian/tooltip.
     */
    protected function daftarSiswaRingkas(): Attribute
    {
        return Attribute::get(function (): string {
            if (! $this->relationLoaded('siswa') || $this->siswa->isEmpty()) {
                return '-';
            }

            return $this->siswa->pluck('nama_lengkap')->filter()->implode(', ');
        });
    }

    /**
     * Label kategori prestasi yang ramah pengguna.
     */
    protected function kategoriLabel(): Attribute
    {
        return Attribute::get(
            fn (): string => self::KATEGORI_OPTIONS[$this->kategori] ?? ucfirst(str_replace('_', ' ', (string) $this->kategori))
        );
    }

    /**
     * Kelas badge Bootstrap untuk kategori prestasi.
     */
    protected function kategoriBadgeClass(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->kategori) {
            self::KATEGORI_AKADEMIK => 'badge-primary',
            self::KATEGORI_NON_AKADEMIK => 'badge-success',
            default => 'badge-secondary',
        });
    }

    /**
     * Kelas badge Bootstrap untuk tingkat prestasi.
     */
    protected function tingkatBadgeClass(): Attribute
    {
        return Attribute::get(function (): string {
            $norm = strtolower(trim((string) $this->tingkat));

            return match (true) {
                str_contains($norm, 'internasional') => 'badge-danger',
                str_contains($norm, 'nasional') => 'badge-warning text-dark',
                str_contains($norm, 'provinsi') => 'badge-info',
                str_contains($norm, 'kabupaten'), str_contains($norm, 'kota') => 'badge-primary',
                default => 'badge-secondary',
            };
        });
    }

    /**
     * Tanggal prestasi terformat Bahasa Indonesia (contoh: 24 November 2026).
     */
    protected function tanggalFormatted(): Attribute
    {
        return Attribute::get(
            fn (): string => $this->tanggal?->locale('id')->translatedFormat('d F Y') ?? '-'
        );
    }

    /**
     * Ringkasan singkat keterangan (maksimal 110 karakter) untuk tabel daftar.
     */
    protected function ringkasanKeterangan(): Attribute
    {
        return Attribute::get(function (): string {
            $text = trim(preg_replace('/\s+/', ' ', (string) $this->keterangan) ?? '');

            return $text === '' ? '-' : Str::limit($text, 110);
        });
    }

    /**
     * URL publik foto dokumentasi/sertifikat prestasi apabila tersedia.
     */
    protected function fotoUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! $this->foto) {
                return null;
            }

            return Storage::disk('public')->url($this->foto);
        });
    }
}
