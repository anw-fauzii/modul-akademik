<?php

namespace App\Services\Admin\Pelajaran;

use App\Models\Admin\Pelajaran\KelompokPelajaran;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class KelompokPelajaranService
{
    /**
     * Membangun query daftar kelompok pelajaran dengan kolom minimal dan pengurutan terstruktur.
     *
     * @param  array{
     *     search?: string|null
     * }  $filters
     * @return Builder<KelompokPelajaran>
     */
    public function buildIndexQuery(array $filters = []): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return KelompokPelajaran::query()
            ->select([
                'id',
                'kode',
                'kelompok',
                'urutan',
                'created_at',
            ])
            ->when($search !== '', static function (Builder $scoped) use ($search): void {
                $escaped = addcslashes($search, '%_\\');
                $likePattern = '%'.$escaped.'%';

                $scoped->where(static function (Builder $nested) use ($likePattern): void {
                    $nested->where($nested->qualifyColumn('kode'), 'like', $likePattern)
                        ->orWhere($nested->qualifyColumn('kelompok'), 'like', $likePattern);
                });
            })
            ->ordered();
    }

    /**
     * Mengambil semua data kelompok pelajaran sesuai filter opsional.
     *
     * @param  array{
     *     search?: string|null
     * }  $filters
     * @return Collection<int, KelompokPelajaran>
     */
    public function getAll(array $filters = []): Collection
    {
        return $this->buildIndexQuery($filters)->get();
    }

    /**
     * Mengambil data lengkap halaman indeks kelompok pelajaran beserta filter pencarian dan total data.
     *
     * @param  array<string, mixed>  $input
     * @return array{
     *     kelompokPelajaran: Collection<int, KelompokPelajaran>,
     *     filters: array{search: string},
     *     totalKelompok: int
     * }
     */
    public function getIndexData(array $input = []): array
    {
        $filters = [
            'search' => trim((string) ($input['search'] ?? '')),
        ];

        $kelompokPelajaran = $this->getAll($filters);
        $totalKelompok = $filters['search'] === ''
            ? $kelompokPelajaran->count()
            : KelompokPelajaran::query()->count();

        return [
            'kelompokPelajaran' => $kelompokPelajaran,
            'filters' => $filters,
            'totalKelompok' => $totalKelompok,
        ];
    }

    /**
     * Menentukan saran nomor urutan berikutnya saat menambahkan kelompok pelajaran baru.
     */
    public function getNextUrutan(): int
    {
        $maxUrutan = KelompokPelajaran::query()->max('urutan');

        return ((int) ($maxUrutan ?? 0)) + 1;
    }

    /**
     * Menyimpan data kelompok pelajaran baru.
     *
     * @param  array<string, mixed>  $data
     */
    public function store(array $data): KelompokPelajaran
    {
        return KelompokPelajaran::create($data);
    }

    /**
     * Memperbarui data kelompok pelajaran.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(KelompokPelajaran $kelompokPelajaran, array $data): bool
    {
        return $kelompokPelajaran->update($data);
    }

    /**
     * Menghapus data kelompok pelajaran.
     */
    public function delete(KelompokPelajaran $kelompokPelajaran): bool
    {
        return (bool) $kelompokPelajaran->delete();
    }
}
