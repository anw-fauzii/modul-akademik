<?php

namespace App\Services\Admin\DataMaster;

use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Models\Admin\DataMaster\Jemputan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TahunAjaranService
{
    /**
     * Kolom master siswa yang diubah menjadi null saat store atau delete tahun ajaran.
     *
     * @var array<string, null>
     */
    private const array SISWA_RESET_ATTRIBUTES = [
        'akademik_master_kelas_id' => null,
        'karyawan_nipy' => null,
        'akademik_master_ekstrakuler_id' => null,
        'akademik_master_jemputan_id' => null,
    ];

    /**
     * Pemetaan kolom siswa ke model yang terhubung langsung dengan tahun ajaran.
     *
     * @var array<string, class-string<Kelas|Ekstrakurikuler|Jemputan>>
     */
    private const array SISWA_TAHUN_AJARAN_COLUMN_MODELS = [
        'akademik_master_kelas_id' => Kelas::class,
        'akademik_master_ekstrakuler_id' => Ekstrakurikuler::class,
        'akademik_master_jemputan_id' => Jemputan::class,
    ];

    public function getAll(): Collection
    {
        return TahunAjaran::orderBy('nama_tahun_ajaran')->orderBy('semester')->get();
    }

    /**
     * Menyimpan tahun ajaran baru dan mereset kolom master pada seluruh siswa.
     *
     * @param  array<string, mixed>  $data
     */
    public function store(array $data): TahunAjaran
    {
        return DB::transaction(function () use ($data): TahunAjaran {
            $tahunAjaran = TahunAjaran::create($data);

            $this->resetSiswaData();

            return $tahunAjaran;
        });
    }

    public function update(TahunAjaran $tahunAjaran, array $data): bool
    {
        return $tahunAjaran->update($data);
    }

    /**
     * Menghapus tahun ajaran dan mereset kolom master siswa yang terkait sebelum cascade delete.
     */
    public function delete(TahunAjaran $tahunAjaran): bool
    {
        if (! $tahunAjaran->exists) {
            return false;
        }

        $tahunAjaranKey = $tahunAjaran->getKey();
        if (! $tahunAjaran->is_active && $tahunAjaranKey === null) {
            return false;
        }

        return DB::transaction(function () use ($tahunAjaran, $tahunAjaranKey): bool {
            // Reset sebelum delete() agar subquery kelas/ekstrakurikuler/jemputan masih terbaca sebelum cascade delete.
            $tahunAjaranId = $tahunAjaran->is_active ? null : (int) $tahunAjaranKey;
            $this->resetSiswaData($tahunAjaranId);

            return (bool) $tahunAjaran->delete();
        });
    }

    public function toggleActive(TahunAjaran $tahunAjaran): bool
    {
        if (! $tahunAjaran->is_active) {
            TahunAjaran::query()->update(['is_active' => false]);

            return $tahunAjaran->update(['is_active' => true]);
        }

        return $tahunAjaran->update(['is_active' => false]);
    }

    /**
     * Mengembalikan daftar atribut siswa yang di-set null.
     *
     * @return array<string, null>
     */
    public function siswaResetAttributes(): array
    {
        return self::SISWA_RESET_ATTRIBUTES;
    }

    /**
     * Menyusun query siswa yang kolom master-nya akan diubah menjadi null.
     *
     * @return Builder<Siswa>
     */
    public function buildSiswaResetQuery(?int $tahunAjaranId = null): Builder
    {
        $query = Siswa::query();

        if ($tahunAjaranId === null) {
            return $query->where(static function (Builder $nonNullQuery): void {
                foreach (array_keys(self::SISWA_RESET_ATTRIBUTES) as $column) {
                    $nonNullQuery->orWhereNotNull($column);
                }
            });
        }

        return $query->where(static function (Builder $scopeQuery) use ($tahunAjaranId): void {
            foreach (self::SISWA_TAHUN_AJARAN_COLUMN_MODELS as $column => $modelClass) {
                $scopeQuery->orWhereIn(
                    $column,
                    $modelClass::query()
                        ->select('id')
                        ->where('akademik_master_tahun_ajaran_id', $tahunAjaranId)
                );
            }
        });
    }

    /**
     * Mengubah kolom kelas, wali/pembimbing, ekstrakurikuler, dan jemputan pada tabel siswa menjadi null.
     */
    protected function resetSiswaData(?int $tahunAjaranId = null): void
    {
        $this->buildSiswaResetQuery($tahunAjaranId)
            ->update(self::SISWA_RESET_ATTRIBUTES);
    }
}
