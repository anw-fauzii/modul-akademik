<?php

namespace App\Services\Admin\Informasi\Concerns;

use App\Models\Admin\DataMaster\TahunAjaran;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

trait ManagesInformasiTahunAjaran
{
    public const string FILTER_TAHUN_AJARAN_ALL = 'all';

    /**
     * Mengambil opsi tahun ajaran dengan kolom minimal untuk dropdown dan filter.
     *
     * @return Collection<int, TahunAjaran>
     */
    protected function getTahunAjaranOptions(): Collection
    {
        return TahunAjaran::query()
            ->select(['id', 'nama_tahun_ajaran', 'semester', 'is_active'])
            ->orderByDesc('is_active')
            ->orderByDesc('nama_tahun_ajaran')
            ->orderBy('semester')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Menyiapkan konteks tahun ajaran dan nilai filter efektif halaman daftar (default: tahun ajaran aktif).
     *
     * @param  array<string, mixed>  $input
     * @return array{
     *     tahunAjaranOptions: Collection<int, TahunAjaran>,
     *     activeTahunAjaran: ?TahunAjaran,
     *     tahun_ajaran_id: string,
     *     tahun_ajaran_int: ?int
     * }
     */
    protected function resolveTahunAjaranContext(array $input): array
    {
        $tahunAjaranOptions = $this->getTahunAjaranOptions();
        $activeTahunAjaran = $tahunAjaranOptions->firstWhere('is_active', true);
        $tahunAjaranId = $this->resolveTahunAjaranFilter($input, $activeTahunAjaran);

        return [
            'tahunAjaranOptions' => $tahunAjaranOptions,
            'activeTahunAjaran' => $activeTahunAjaran,
            'tahun_ajaran_id' => $tahunAjaranId,
            'tahun_ajaran_int' => $this->resolveIntegerTahunAjaranId($tahunAjaranId),
        ];
    }

    /**
     * Menentukan nilai filter tahun ajaran efektif (default: tahun ajaran aktif, atau 'all').
     *
     * @param  array<string, mixed>  $input
     */
    protected function resolveTahunAjaranFilter(array $input, ?TahunAjaran $activeTahunAjaran): string
    {
        $rawTahunAjaran = array_key_exists('tahun_ajaran_id', $input)
            ? trim((string) $input['tahun_ajaran_id'])
            : '';

        if ($rawTahunAjaran === self::FILTER_TAHUN_AJARAN_ALL) {
            return self::FILTER_TAHUN_AJARAN_ALL;
        }

        if ($rawTahunAjaran !== '' && ctype_digit($rawTahunAjaran) && (int) $rawTahunAjaran > 0) {
            return $rawTahunAjaran;
        }

        if ($activeTahunAjaran !== null) {
            return (string) $activeTahunAjaran->id;
        }

        return self::FILTER_TAHUN_AJARAN_ALL;
    }

    /**
     * Mengonversi nilai filter tahun ajaran menjadi integer ID atau null jika menampilkan semua tahun ajaran.
     */
    protected function resolveIntegerTahunAjaranId(string|int|null $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (is_string($value) && $value !== self::FILTER_TAHUN_AJARAN_ALL && ctype_digit($value) && (int) $value > 0) {
            return (int) $value;
        }

        return null;
    }

    /**
     * Menerapkan scope wajib jenis siswa serta filter opsional tahun ajaran pada query informasi.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    protected function applyTahunAjaranAndSiswaScope(Builder $query, ?int $tahunAjaranId): Builder
    {
        return $query
            ->forSiswa()
            ->when(
                $tahunAjaranId !== null,
                static fn (Builder $scoped) => $scoped->where(
                    $scoped->qualifyColumn('akademik_master_tahun_ajaran_id'),
                    $tahunAjaranId
                )
            );
    }

    /**
     * Menyimpan record informasi baru dengan memaksa kolom jenis bernilai siswa.
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $modelClass
     * @param  array<string, mixed>  $data
     * @return TModel
     */
    protected function storeSiswaRecord(string $modelClass, array $data): Model
    {
        return $modelClass::query()->create([
            ...$data,
            'jenis' => $modelClass::JENIS_SISWA,
        ]);
    }

    /**
     * Memperbarui record informasi dengan mempertahankan kolom jenis bernilai siswa.
     *
     * @param  array<string, mixed>  $data
     */
    protected function updateSiswaRecord(Model $model, array $data): bool
    {
        return $model->update([
            ...$data,
            'jenis' => $model::JENIS_SISWA,
        ]);
    }
}
