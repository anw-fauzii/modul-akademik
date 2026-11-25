<?php

namespace App\Services\Admin\TujuanPembelajaran;

use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class TujuanPembelajaranService
{
    public const string FILTER_ALL = 'all';

    /**
     * Membangun query daftar tujuan pembelajaran dengan kolom minimal dan eager loading lengkap tanpa N+1.
     *
     * @param  array{
     *     tahun_ajaran_id?: string|int|null,
     *     unit?: string|null,
     *     mata_pelajaran_id?: string|int|null,
     *     tingkatan_kelas?: string|null,
     *     fase?: string|null,
     *     status?: string|null,
     *     search?: string|null
     * }  $filters
     * @return Builder<TujuanPembelajaran>
     */
    public function buildIndexQuery(array $filters = []): Builder
    {
        $tahunAjaranId = $this->resolvePositiveInt($filters['tahun_ajaran_id'] ?? null);
        $unit = strtoupper(trim((string) ($filters['unit'] ?? '')));
        $mapelId = $this->resolvePositiveInt($filters['mata_pelajaran_id'] ?? null);
        $tingkatan = trim((string) ($filters['tingkatan_kelas'] ?? ''));
        $fase = trim((string) ($filters['fase'] ?? ''));
        $status = trim((string) ($filters['status'] ?? self::FILTER_ALL));
        $search = trim((string) ($filters['search'] ?? ''));

        return TujuanPembelajaran::query()
            ->select([
                'id',
                'akademik_master_tahun_ajaran_id',
                'akademik_pelajaran_mata_pelajaran_id',
                'tingkatan_kelas',
                'fase',
                'elemen',
                'kode_tp',
                'tujuan_pembelajaran',
                'urutan',
                'status',
                'created_at',
            ])
            ->withMasterRelations()
            ->when(
                $tahunAjaranId !== null,
                static fn (Builder $query) => $query->where(
                    $query->qualifyColumn('akademik_master_tahun_ajaran_id'),
                    $tahunAjaranId
                )
            )
            ->when($unit !== '' && $unit !== 'ALL', static function (Builder $query) use ($unit): void {
                $query->whereHas('mataPelajaran', static function (Builder $mpQuery) use ($unit): void {
                    $mpQuery->where($mpQuery->qualifyColumn('unit'), $unit);
                });
            })
            ->when(
                $mapelId !== null,
                static fn (Builder $query) => $query->where(
                    $query->qualifyColumn('akademik_pelajaran_mata_pelajaran_id'),
                    $mapelId
                )
            )
            ->when(
                $tingkatan !== '',
                static fn (Builder $query) => $query->where(
                    $query->qualifyColumn('tingkatan_kelas'),
                    $tingkatan
                )
            )
            ->when(
                $fase !== '' && array_key_exists($fase, TujuanPembelajaran::FASE_OPTIONS),
                static fn (Builder $query) => $query->where($query->qualifyColumn('fase'), $fase)
            )
            ->when(
                $status === '1' || $status === '0',
                static fn (Builder $query) => $query->where($query->qualifyColumn('status'), $status === '1')
            )
            ->when($search !== '', static function (Builder $query) use ($search): void {
                $escaped = addcslashes($search, '%_\\');
                $likePattern = '%'.$escaped.'%';

                $query->where(static function (Builder $nested) use ($likePattern): void {
                    $nested->where($nested->qualifyColumn('kode_tp'), 'like', $likePattern)
                        ->orWhere($nested->qualifyColumn('tujuan_pembelajaran'), 'like', $likePattern)
                        ->orWhere($nested->qualifyColumn('elemen'), 'like', $likePattern)
                        ->orWhereHas('mataPelajaran', static function (Builder $mpQuery) use ($likePattern): void {
                            $mpQuery->where('singkatan', 'like', $likePattern)
                                ->orWhere('nama_mata_pelajaran', 'like', $likePattern);
                        });
                });
            })
            ->ordered();
    }

    /**
     * Mengambil semua data tujuan pembelajaran sesuai filter tanpa N+1.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, TujuanPembelajaran>
     */
    public function getAll(array $filters = []): Collection
    {
        return $this->buildIndexQuery($filters)->get();
    }

    /**
     * Mengambil data lengkap halaman indeks tujuan pembelajaran:
     * default tahun ajaran/semester aktif, statistik unit dalam 1 query, dan seluruh opsi filter tanpa N+1.
     *
     * @param  array<string, mixed>  $input
     * @return array{
     *     tujuanPembelajaran: Collection<int, TujuanPembelajaran>,
     *     summary: array{total: int, aktif: int, nonaktif: int, per_unit: array<string, int>},
     *     filters: array{tahun_ajaran_id: string, unit: string, mata_pelajaran_id: string, tingkatan_kelas: string, fase: string, status: string, search: string},
     *     tahunAjaranOptions: Collection<int, TahunAjaran>,
     *     mataPelajaranOptions: Collection<int, MataPelajaran>,
     *     tingkatanByUnit: array<string, list<string>>,
     *     tingkatanOptions: list<string>,
     *     unitOptions: list<string>,
     *     faseOptions: array<string, string>
     * }
     */
    public function getIndexData(array $input = []): array
    {
        $tahunAjaranOptions = $this->getTahunAjaranOptions();
        $activeTahunAjaran = $tahunAjaranOptions->firstWhere('is_active', true);

        $rawTa = array_key_exists('tahun_ajaran_id', $input) ? trim((string) $input['tahun_ajaran_id']) : '';
        if ($rawTa === self::FILTER_ALL) {
            $effectiveTa = self::FILTER_ALL;
        } elseif ($rawTa !== '' && ctype_digit($rawTa) && (int) $rawTa > 0) {
            $effectiveTa = $rawTa;
        } elseif ($activeTahunAjaran) {
            $effectiveTa = (string) $activeTahunAjaran->id;
        } else {
            $effectiveTa = self::FILTER_ALL;
        }

        $unit = strtoupper(trim((string) ($input['unit'] ?? '')));
        $rawStatus = trim((string) ($input['status'] ?? self::FILTER_ALL));

        $filters = [
            'tahun_ajaran_id' => $effectiveTa,
            'unit' => in_array($unit, MataPelajaran::UNIT_OPTIONS, true) ? $unit : '',
            'mata_pelajaran_id' => trim((string) ($input['mata_pelajaran_id'] ?? '')),
            'tingkatan_kelas' => trim((string) ($input['tingkatan_kelas'] ?? '')),
            'fase' => trim((string) ($input['fase'] ?? '')),
            'status' => in_array($rawStatus, ['1', '0', self::FILTER_ALL], true) ? $rawStatus : self::FILTER_ALL,
            'search' => trim((string) ($input['search'] ?? '')),
        ];

        $tingkatanByUnit = $this->getTingkatanByUnit();
        $tingkatanOptions = $filters['unit'] !== ''
            ? ($tingkatanByUnit[$filters['unit']] ?? [])
            : $this->flattenUniqueTingkatan($tingkatanByUnit);

        $mataPelajaranOptions = $this->getMataPelajaranOptions($filters['unit']);
        $tujuanPembelajaran = $this->getAll($filters);

        $sortedTp = $tujuanPembelajaran->sortBy(static function (TujuanPembelajaran $row): string {
            $mapel = $row->relationLoaded('mataPelajaran') ? $row->mataPelajaran : null;
            $unitKey = (string) ($mapel?->unit ?? 'ZZZ');
            $unitPos = array_search(strtoupper($unitKey), MataPelajaran::UNIT_OPTIONS, true);
            $unitSort = str_pad((string) ($unitPos !== false ? $unitPos : 99), 2, '0', STR_PAD_LEFT);
            $tingkatSort = str_pad((string) $row->tingkatan_kelas, 5, '0', STR_PAD_LEFT);
            $mapelNama = (string) ($mapel?->nama_mata_pelajaran ?? '');
            $urutan = str_pad((string) $row->urutan, 4, '0', STR_PAD_LEFT);

            return "{$unitSort}-{$tingkatSort}-{$mapelNama}-{$urutan}-{$row->kode_tp}-{$row->id}";
        })->values();

        return [
            'tujuanPembelajaran' => $sortedTp,
            'summary' => $this->getUnitStatistics($filters),
            'filters' => $filters,
            'tahunAjaranOptions' => $tahunAjaranOptions,
            'mataPelajaranOptions' => $mataPelajaranOptions,
            'tingkatanByUnit' => $tingkatanByUnit,
            'tingkatanOptions' => $tingkatanOptions,
            'unitOptions' => MataPelajaran::UNIT_OPTIONS,
            'faseOptions' => TujuanPembelajaran::FASE_OPTIONS,
        ];
    }

    /**
     * Mengambil opsi dropdown untuk form tambah dan edit TP dalam query minimal tanpa N+1.
     *
     * @param  array<string, mixed>  $defaults
     * @return array{
     *     tahunAjaranOptions: Collection<int, TahunAjaran>,
     *     mataPelajaranOptions: Collection<int, MataPelajaran>,
     *     tingkatanByUnit: array<string, list<string>>,
     *     faseOptions: array<string, string>,
     *     defaults: array{tahun_ajaran_id: string, mata_pelajaran_id: string, tingkatan_kelas: string}
     * }
     */
    public function getFormOptions(array $defaults = []): array
    {
        $tahunAjaranOptions = $this->getTahunAjaranOptions();
        $activeTahunAjaran = $tahunAjaranOptions->firstWhere('is_active', true);

        $defaultTa = $this->resolvePositiveInt($defaults['tahun_ajaran_id'] ?? null)
            ?? $activeTahunAjaran?->id;

        return [
            'tahunAjaranOptions' => $tahunAjaranOptions,
            'mataPelajaranOptions' => $this->getMataPelajaranOptions(),
            'tingkatanByUnit' => $this->getTingkatanByUnit(),
            'faseOptions' => TujuanPembelajaran::FASE_OPTIONS,
            'defaults' => [
                'tahun_ajaran_id' => $defaultTa !== null ? (string) $defaultTa : '',
                'mata_pelajaran_id' => trim((string) ($defaults['mata_pelajaran_id'] ?? '')),
                'tingkatan_kelas' => trim((string) ($defaults['tingkatan_kelas'] ?? '')),
            ],
        ];
    }

    /**
     * Menyimpan data tujuan pembelajaran baru.
     *
     * @param  array<string, mixed>  $data
     */
    public function store(array $data): TujuanPembelajaran
    {
        return TujuanPembelajaran::create($data);
    }

    /**
     * Memperbarui data tujuan pembelajaran.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(TujuanPembelajaran $tujuanPembelajaran, array $data): bool
    {
        return $tujuanPembelajaran->update($data);
    }

    /**
     * Menghapus data tujuan pembelajaran.
     */
    public function delete(TujuanPembelajaran $tujuanPembelajaran): bool
    {
        return (bool) $tujuanPembelajaran->delete();
    }

    /**
     * Mengubah status aktif/nonaktif tujuan pembelajaran secara cepat.
     */
    public function toggleStatus(TujuanPembelajaran $tujuanPembelajaran): bool
    {
        return $tujuanPembelajaran->update([
            'status' => ! (bool) $tujuanPembelajaran->status,
        ]);
    }

    /**
     * Menghitung ringkasan jumlah TP per unit dan status aktif dalam satu query agregat tanpa N+1.
     *
     * @param  array<string, mixed>  $filters
     * @return array{total: int, aktif: int, nonaktif: int, per_unit: array<string, int>}
     */
    private function getUnitStatistics(array $filters): array
    {
        $tahunAjaranId = $this->resolvePositiveInt($filters['tahun_ajaran_id'] ?? null);
        $mapelId = $this->resolvePositiveInt($filters['mata_pelajaran_id'] ?? null);
        $tingkatan = trim((string) ($filters['tingkatan_kelas'] ?? ''));
        $status = trim((string) ($filters['status'] ?? self::FILTER_ALL));

        $tpTable = (new TujuanPembelajaran)->getTable();
        $mpTable = (new MataPelajaran)->getTable();

        $rows = TujuanPembelajaran::query()
            ->join($mpTable, "{$mpTable}.id", '=', "{$tpTable}.akademik_pelajaran_mata_pelajaran_id")
            ->selectRaw("{$mpTable}.unit as unit_code, COUNT(*) as total, SUM(CASE WHEN {$tpTable}.status = 1 THEN 1 ELSE 0 END) as total_aktif")
            ->when(
                $tahunAjaranId !== null,
                static fn (Builder $query) => $query->where("{$tpTable}.akademik_master_tahun_ajaran_id", $tahunAjaranId)
            )
            ->when(
                $mapelId !== null,
                static fn (Builder $query) => $query->where("{$tpTable}.akademik_pelajaran_mata_pelajaran_id", $mapelId)
            )
            ->when(
                $tingkatan !== '',
                static fn (Builder $query) => $query->where("{$tpTable}.tingkatan_kelas", $tingkatan)
            )
            ->when(
                $status === '1' || $status === '0',
                static fn (Builder $query) => $query->where("{$tpTable}.status", $status === '1')
            )
            ->groupBy("{$mpTable}.unit")
            ->get();

        $perUnit = array_fill_keys(MataPelajaran::UNIT_OPTIONS, 0);
        $total = 0;
        $aktif = 0;

        foreach ($rows as $row) {
            $unitCode = strtoupper(trim((string) ($row->unit_code ?? '')));
            $unitTotal = (int) ($row->total ?? 0);
            $unitAktif = (int) ($row->total_aktif ?? 0);

            if ($unitCode !== '') {
                $perUnit[$unitCode] = ($perUnit[$unitCode] ?? 0) + $unitTotal;
            }
            $total += $unitTotal;
            $aktif += $unitAktif;
        }

        return [
            'total' => $total,
            'aktif' => $aktif,
            'nonaktif' => max(0, $total - $aktif),
            'per_unit' => $perUnit,
        ];
    }

    /**
     * @return Collection<int, TahunAjaran>
     */
    private function getTahunAjaranOptions(): Collection
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
     * @return Collection<int, MataPelajaran>
     */
    private function getMataPelajaranOptions(string $unit = ''): Collection
    {
        $normalizedUnit = strtoupper(trim($unit));

        return MataPelajaran::query()
            ->select(['id', 'singkatan', 'nama_mata_pelajaran', 'unit', 'urutan', 'status'])
            ->when(
                $normalizedUnit !== '' && in_array($normalizedUnit, MataPelajaran::UNIT_OPTIONS, true),
                static fn (Builder $query) => $query->where('unit', $normalizedUnit)
            )
            ->orderByDesc('status')
            ->orderBy('unit')
            ->orderBy('urutan')
            ->orderBy('nama_mata_pelajaran')
            ->get();
    }

    /**
     * Mengambil kombinasi unik tingkatan kelas per unit dari tabel Kelas dan menggabungkannya dengan opsi standar.
     *
     * @return array<string, list<string>>
     */
    private function getTingkatanByUnit(): array
    {
        $grouped = TujuanPembelajaran::TINGKATAN_DEFAULTS_BY_UNIT;

        $kelasRows = Kelas::query()
            ->select(['jenjang_kelas', 'tingkatan_kelas'])
            ->distinct()
            ->orderBy('jenjang_kelas')
            ->orderBy('tingkatan_kelas')
            ->get();

        foreach ($kelasRows as $row) {
            $unitKey = strtoupper(trim((string) ($row->jenjang_kelas ?? '')));
            $tingkat = trim((string) ($row->tingkatan_kelas ?? ''));

            if ($unitKey === '' || $tingkat === '') {
                continue;
            }

            if (! isset($grouped[$unitKey])) {
                $grouped[$unitKey] = [];
            }

            if (! in_array($tingkat, $grouped[$unitKey], true)) {
                $grouped[$unitKey][] = $tingkat;
            }
        }

        return $grouped;
    }

    /**
     * @param  array<string, list<string>>  $tingkatanByUnit
     * @return list<string>
     */
    private function flattenUniqueTingkatan(array $tingkatanByUnit): array
    {
        $flat = [];

        foreach ($tingkatanByUnit as $items) {
            foreach ($items as $item) {
                if (! in_array($item, $flat, true)) {
                    $flat[] = $item;
                }
            }
        }

        return $flat;
    }

    private function resolvePositiveInt(mixed $value): ?int
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
