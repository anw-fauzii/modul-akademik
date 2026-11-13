<?php

namespace App\Services\Admin\Pelajaran;

use App\Models\Admin\Pelajaran\KelompokPelajaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class MataPelajaranService
{
    public const string FILTER_ALL = 'all';

    /**
     * Membangun query daftar mata pelajaran beserta eager loading kelompok pelajaran tanpa N+1.
     *
     * @param  array{
     *     unit?: string|null,
     *     kelompok_pelajaran_id?: string|int|null,
     *     sifat?: string|null,
     *     status?: string|null,
     *     search?: string|null
     * }  $filters
     * @return Builder<MataPelajaran>
     */
    public function buildIndexQuery(array $filters = []): Builder
    {
        $unit = strtoupper(trim((string) ($filters['unit'] ?? '')));
        $kelompokId = $this->resolvePositiveInt($filters['kelompok_pelajaran_id'] ?? null);
        $sifat = strtolower(trim((string) ($filters['sifat'] ?? '')));
        $status = trim((string) ($filters['status'] ?? self::FILTER_ALL));
        $search = trim((string) ($filters['search'] ?? ''));

        return MataPelajaran::query()
            ->select([
                'id',
                'akademik_pelajaran_kelompok_pelajaran_id',
                'singkatan',
                'nama_mata_pelajaran',
                'sifat',
                'unit',
                'urutan',
                'keterangan',
                'status',
                'created_at',
            ])
            ->withMasterRelations()
            ->when(
                $unit !== '' && $unit !== 'ALL',
                static fn (Builder $query) => $query->where($query->qualifyColumn('unit'), $unit)
            )
            ->when(
                $kelompokId !== null,
                static fn (Builder $query) => $query->where(
                    $query->qualifyColumn('akademik_pelajaran_kelompok_pelajaran_id'),
                    $kelompokId
                )
            )
            ->when(
                in_array($sifat, MataPelajaran::SIFAT_OPTIONS, true),
                static fn (Builder $query) => $query->where($query->qualifyColumn('sifat'), $sifat)
            )
            ->when(
                $status === '1' || $status === '0',
                static fn (Builder $query) => $query->where($query->qualifyColumn('status'), $status === '1')
            )
            ->when($search !== '', static function (Builder $query) use ($search): void {
                $escaped = addcslashes($search, '%_\\');
                $likePattern = '%'.$escaped.'%';

                $query->where(static function (Builder $nested) use ($likePattern): void {
                    $nested->where($nested->qualifyColumn('singkatan'), 'like', $likePattern)
                        ->orWhere($nested->qualifyColumn('nama_mata_pelajaran'), 'like', $likePattern)
                        ->orWhere($nested->qualifyColumn('keterangan'), 'like', $likePattern);
                });
            })
            ->ordered();
    }

    /**
     * Mengambil semua data mata pelajaran sesuai filter opsional tanpa N+1.
     *
     * @param  array{
     *     unit?: string|null,
     *     kelompok_pelajaran_id?: string|int|null,
     *     sifat?: string|null,
     *     status?: string|null,
     *     search?: string|null
     * }  $filters
     * @return Collection<int, MataPelajaran>
     */
    public function getAll(array $filters = []): Collection
    {
        return $this->buildIndexQuery($filters)->get();
    }

    /**
     * Mengambil data lengkap halaman indeks mata pelajaran beserta opsi filter dan ringkasan statistik.
     *
     * @param  array<string, mixed>  $input
     * @return array{
     *     mataPelajaran: Collection<int, MataPelajaran>,
     *     summary: array{total: int, aktif: int, nonaktif: int, per_unit: array<string, array{total: int, aktif: int}>},
     *     filters: array{unit: string, kelompok_pelajaran_id: string, sifat: string, status: string, search: string},
     *     kelompokPelajaranOptions: Collection<int, KelompokPelajaran>,
     *     unitOptions: list<string>,
     *     sifatOptions: list<string>
     * }
     */
    public function getIndexData(array $input = []): array
    {
        $rawStatus = trim((string) ($input['status'] ?? self::FILTER_ALL));
        $filters = [
            'unit' => strtoupper(trim((string) ($input['unit'] ?? ''))),
            'kelompok_pelajaran_id' => trim((string) ($input['kelompok_pelajaran_id'] ?? '')),
            'sifat' => strtolower(trim((string) ($input['sifat'] ?? ''))),
            'status' => in_array($rawStatus, ['1', '0', self::FILTER_ALL], true) ? $rawStatus : self::FILTER_ALL,
            'search' => trim((string) ($input['search'] ?? '')),
        ];

        return [
            'mataPelajaran' => $this->getAll($filters),
            'summary' => $this->getUnitStatistics(),
            'filters' => $filters,
            'kelompokPelajaranOptions' => $this->getKelompokPelajaranOptions(),
            'unitOptions' => MataPelajaran::UNIT_OPTIONS,
            'sifatOptions' => MataPelajaran::SIFAT_OPTIONS,
        ];
    }

    /**
     * Mengambil daftar opsi dropdown untuk form tambah dan edit mata pelajaran.
     *
     * @return array{
     *     kelompokPelajaran: Collection<int, KelompokPelajaran>,
     *     unitOptions: list<string>,
     *     sifatOptions: list<string>,
     *     nextUrutan: int
     * }
     */
    public function getFormOptions(): array
    {
        return [
            'kelompokPelajaran' => $this->getKelompokPelajaranOptions(),
            'unitOptions' => MataPelajaran::UNIT_OPTIONS,
            'sifatOptions' => MataPelajaran::SIFAT_OPTIONS,
            'nextUrutan' => $this->getNextUrutan(),
        ];
    }

    /**
     * Menyimpan data mata pelajaran baru.
     *
     * @param  array<string, mixed>  $data
     */
    public function store(array $data): MataPelajaran
    {
        return MataPelajaran::create($data);
    }

    /**
     * Memperbarui data mata pelajaran.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(MataPelajaran $mataPelajaran, array $data): bool
    {
        return $mataPelajaran->update($data);
    }

    /**
     * Menghapus data mata pelajaran.
     */
    public function delete(MataPelajaran $mataPelajaran): bool
    {
        return (bool) $mataPelajaran->delete();
    }

    /**
     * Mengubah status aktif/nonaktif mata pelajaran secara cepat.
     */
    public function toggleStatus(MataPelajaran $mataPelajaran): bool
    {
        return $mataPelajaran->update([
            'status' => ! (bool) $mataPelajaran->status,
        ]);
    }

    /**
     * Menghitung ringkasan jumlah mata pelajaran per unit dan status dalam satu query agregat.
     *
     * @return array{total: int, aktif: int, nonaktif: int, per_unit: array<string, array{total: int, aktif: int}>}
     */
    private function getUnitStatistics(): array
    {
        $rows = MataPelajaran::query()
            ->selectRaw('unit, COUNT(*) as total, SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as total_aktif')
            ->groupBy('unit')
            ->get();

        $perUnit = [];
        $total = 0;
        $aktif = 0;

        foreach ($rows as $row) {
            $unitName = strtoupper(trim((string) ($row->unit ?? '')));
            $unitTotal = (int) ($row->total ?? 0);
            $unitAktif = (int) ($row->total_aktif ?? 0);

            if ($unitName === '') {
                continue;
            }

            $perUnit[$unitName] = [
                'total' => $unitTotal,
                'aktif' => $unitAktif,
            ];
            $total += $unitTotal;
            $aktif += $unitAktif;
        }

        uksort($perUnit, static fn (string $a, string $b): int => MataPelajaran::compareUnitOrder($a, $b));

        return [
            'total' => $total,
            'aktif' => $aktif,
            'nonaktif' => max(0, $total - $aktif),
            'per_unit' => $perUnit,
        ];
    }

    /**
     * Mengambil opsi kelompok pelajaran dengan kolom minimal dan urutan tampil.
     *
     * @return Collection<int, KelompokPelajaran>
     */
    private function getKelompokPelajaranOptions(): Collection
    {
        return KelompokPelajaran::query()
            ->select(['id', 'kode', 'kelompok', 'urutan'])
            ->ordered()
            ->get();
    }

    private function getNextUrutan(): int
    {
        $maxUrutan = MataPelajaran::query()->max('urutan');

        return ((int) ($maxUrutan ?? 0)) + 1;
    }

    private function resolvePositiveInt(string|int|null $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (is_string($value) && $value !== '' && ctype_digit($value) && (int) $value > 0) {
            return (int) $value;
        }

        return null;
    }
}
