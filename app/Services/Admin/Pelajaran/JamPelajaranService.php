<?php

namespace App\Services\Admin\Pelajaran;

use App\Models\Admin\Pelajaran\JamPelajaran;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class JamPelajaranService
{
    /**
     * Membangun query daftar jam pelajaran dengan kolom minimal dan eager loading relasi tanpa N+1.
     *
     * @param  array{
     *     unit?: string|null,
     *     search?: string|null
     * }  $filters
     * @return Builder<JamPelajaran>
     */
    public function buildIndexQuery(array $filters = []): Builder
    {
        $unit = strtoupper(trim((string) ($filters['unit'] ?? '')));
        $search = trim((string) ($filters['search'] ?? ''));

        return JamPelajaran::query()
            ->select([
                'id',
                'jam_ke',
                'unit',
                'jam_mulai',
                'jam_selesai',
                'keterangan',
                'created_at',
            ])
            ->withMasterRelations()
            ->when(
                $unit !== '' && $unit !== 'ALL',
                static fn (Builder $query) => $query->where($query->qualifyColumn('unit'), $unit)
            )
            ->when($search !== '', static function (Builder $query) use ($search): void {
                $escaped = addcslashes($search, '%_\\');
                $likePattern = '%'.$escaped.'%';

                $query->where(static function (Builder $nested) use ($search, $likePattern): void {
                    if (ctype_digit($search)) {
                        $nested->where($nested->qualifyColumn('jam_ke'), (int) $search)
                            ->orWhere($nested->qualifyColumn('keterangan'), 'like', $likePattern);

                        return;
                    }

                    $nested->where($nested->qualifyColumn('unit'), 'like', $likePattern)
                        ->orWhere($nested->qualifyColumn('jam_mulai'), 'like', $likePattern)
                        ->orWhere($nested->qualifyColumn('jam_selesai'), 'like', $likePattern)
                        ->orWhere($nested->qualifyColumn('keterangan'), 'like', $likePattern);
                });
            })
            ->ordered();
    }

    /**
     * Mengambil semua data jam pelajaran sesuai filter opsional tanpa N+1.
     *
     * @param  array{
     *     unit?: string|null,
     *     search?: string|null
     * }  $filters
     * @return Collection<int, JamPelajaran>
     */
    public function getAll(array $filters = []): Collection
    {
        return $this->buildIndexQuery($filters)->get();
    }

    /**
     * Mengambil data lengkap halaman indeks jam pelajaran beserta statistik per unit.
     *
     * @param  array<string, mixed>  $input
     * @return array{
     *     jamPelajaran: Collection<int, JamPelajaran>,
     *     summary: array{
     *         total: int,
     *         per_unit: array<string, array{total: int, jam_pertama: string|null, jam_terakhir: string|null}>
     *     },
     *     filters: array{unit: string, search: string},
     *     unitOptions: list<string>
     * }
     */
    public function getIndexData(array $input = []): array
    {
        $filters = [
            'unit' => strtoupper(trim((string) ($input['unit'] ?? ''))),
            'search' => trim((string) ($input['search'] ?? '')),
        ];

        return [
            'jamPelajaran' => $this->getAll($filters),
            'summary' => $this->getUnitStatistics(),
            'filters' => $filters,
            'unitOptions' => JamPelajaran::UNIT_OPTIONS,
        ];
    }

    /**
     * Mengambil opsi form tambah dan edit jam pelajaran serta saran jam_ke berikutnya per unit dalam 1 query.
     *
     * @return array{
     *     unitOptions: list<string>,
     *     nextJamKeByUnit: array<string, int>,
     *     defaultNextJamKe: int
     * }
     */
    public function getFormOptions(): array
    {
        $nextJamKeByUnit = $this->getNextJamKeByUnit();

        return [
            'unitOptions' => JamPelajaran::UNIT_OPTIONS,
            'nextJamKeByUnit' => $nextJamKeByUnit,
            'defaultNextJamKe' => min($nextJamKeByUnit),
        ];
    }

    /**
     * Menyimpan data jam pelajaran baru.
     *
     * @param  array<string, mixed>  $data
     */
    public function store(array $data): JamPelajaran
    {
        return JamPelajaran::create($data);
    }

    /**
     * Memperbarui data jam pelajaran.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(JamPelajaran $jamPelajaran, array $data): bool
    {
        return $jamPelajaran->update($data);
    }

    /**
     * Menghapus data jam pelajaran.
     */
    public function delete(JamPelajaran $jamPelajaran): bool
    {
        return (bool) $jamPelajaran->delete();
    }

    /**
     * Menghitung ringkasan jumlah slot jam pelajaran serta rentang waktu per unit dalam satu query agregat.
     *
     * @return array{
     *     total: int,
     *     per_unit: array<string, array{total: int, jam_pertama: string|null, jam_terakhir: string|null}>
     * }
     */
    private function getUnitStatistics(): array
    {
        $rows = JamPelajaran::query()
            ->selectRaw('unit, COUNT(*) as total, MIN(jam_mulai) as jam_pertama, MAX(jam_selesai) as jam_terakhir')
            ->groupBy('unit')
            ->get();

        $perUnit = [];
        $total = 0;

        foreach ($rows as $row) {
            $unitName = strtoupper(trim((string) ($row->unit ?? '')));
            $unitTotal = (int) ($row->total ?? 0);

            if ($unitName === '') {
                continue;
            }

            $jamPertama = JamPelajaran::formatTimeShort($row->jam_pertama ?? null);
            $jamTerakhir = JamPelajaran::formatTimeShort($row->jam_terakhir ?? null);

            $perUnit[$unitName] = [
                'total' => $unitTotal,
                'jam_pertama' => $jamPertama === '-' ? null : $jamPertama,
                'jam_terakhir' => $jamTerakhir === '-' ? null : $jamTerakhir,
            ];
            $total += $unitTotal;
        }

        uksort($perUnit, static fn (string $a, string $b): int => JamPelajaran::compareUnitOrder($a, $b));

        return [
            'total' => $total,
            'per_unit' => $perUnit,
        ];
    }

    /**
     * Menghitung saran jam_ke berikutnya per unit dalam satu query GROUP BY untuk mencegah N+1.
     *
     * @return array<string, int>
     */
    private function getNextJamKeByUnit(): array
    {
        $nextByUnit = array_fill_keys(JamPelajaran::UNIT_OPTIONS, 1);

        $rows = JamPelajaran::query()
            ->selectRaw('unit, MAX(jam_ke) as max_jam_ke')
            ->groupBy('unit')
            ->get();

        foreach ($rows as $row) {
            $unitName = strtoupper(trim((string) ($row->unit ?? '')));
            if ($unitName === '') {
                continue;
            }

            $nextByUnit[$unitName] = ((int) ($row->max_jam_ke ?? 0)) + 1;
        }

        return $nextByUnit;
    }
}
