<?php

namespace App\Models\Admin\Informasi\Concerns;

use App\Models\Admin\DataMaster\TahunAjaran;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasInformasiTahunAjaranAndJenis
{
    public const string JENIS_SISWA = 'siswa';

    public const string JENIS_PEGAWAI = 'pegawai';

    /**
     * Pilihan nilai enum kolom jenis informasi sekolah.
     *
     * @var list<string>
     */
    public const array JENIS_OPTIONS = [
        self::JENIS_SISWA,
        self::JENIS_PEGAWAI,
    ];

    /**
     * Relasi utama yang selalu di-eager load untuk daftar informasi tanpa N+1.
     *
     * @var list<string>
     */
    public const array INDEX_RELATIONS = [
        'tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
    ];

    /**
     * Mengisi nilai default kolom jenis menjadi siswa pada aplikasi akademik.
     */
    public function initializeHasInformasiTahunAjaranAndJenis(): void
    {
        $this->attributes['jenis'] ??= self::JENIS_SISWA;
    }

    /**
     * Eager load relasi tahun ajaran dengan kolom minimal untuk mencegah N+1.
     */
    #[Scope]
    protected function withMasterRelations(Builder $query): Builder
    {
        return $query->with(self::INDEX_RELATIONS);
    }

    /**
     * Membatasi query hanya pada data informasi bertipe siswa.
     */
    #[Scope]
    protected function forSiswa(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('jenis'), self::JENIS_SISWA);
    }

    /**
     * Relasi ke Tahun Ajaran.
     */
    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'akademik_master_tahun_ajaran_id');
    }
}
