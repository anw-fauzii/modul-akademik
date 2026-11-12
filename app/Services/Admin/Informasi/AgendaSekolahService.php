<?php

namespace App\Services\Admin\Informasi;

use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Informasi\AgendaSekolah;
use App\Services\Admin\Informasi\Concerns\ManagesInformasiTahunAjaran;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AgendaSekolahService
{
    use ManagesInformasiTahunAjaran;

    /**
     * Membangun query daftar agenda sekolah beserta eager loading relasi untuk mencegah N+1.
     *
     * @param  array{
     *     tahun_ajaran_id?: string|int|null,
     *     unit?: string|null
     * }  $filters
     * @return Builder<AgendaSekolah>
     */
    public function buildIndexQuery(array $filters = []): Builder
    {
        $tahunAjaranId = $this->resolveIntegerTahunAjaranId($filters['tahun_ajaran_id'] ?? null);
        $unit = trim((string) ($filters['unit'] ?? ''));

        $query = AgendaSekolah::query()
            ->select([
                'id',
                'akademik_master_tahun_ajaran_id',
                'nama_agenda',
                'tanggal',
                'jam',
                'unit',
                'jenis',
            ])
            ->withMasterRelations()
            ->orderedChronologically();

        return $this->applyScopeFilters($query, $tahunAjaranId, $unit);
    }

    /**
     * Mengambil semua data agenda sekolah sesuai filter opsional tanpa N+1.
     *
     * @param  array{
     *     tahun_ajaran_id?: string|int|null,
     *     unit?: string|null
     * }  $filters
     * @return Collection<int, AgendaSekolah>
     */
    public function getAll(array $filters = []): Collection
    {
        return $this->buildIndexQuery($filters)->get();
    }

    /**
     * Mengambil data lengkap halaman indeks agenda sekolah: default tahun ajaran aktif, filter unit, dan ringkasan per unit.
     *
     * @param  array<string, mixed>  $input
     * @return array{
     *     agendaSekolah: Collection<int, AgendaSekolah>,
     *     unitSummary: array{total_agenda: int, per_unit: array<string, int>},
     *     filters: array{tahun_ajaran_id: string, unit: string},
     *     tahunAjaranOptions: Collection<int, TahunAjaran>,
     *     activeTahunAjaran: ?TahunAjaran,
     *     unitOptions: list<string>
     * }
     */
    public function getIndexData(array $input = []): array
    {
        $context = $this->resolveTahunAjaranContext($input);
        $filters = [
            'tahun_ajaran_id' => $context['tahun_ajaran_id'],
            'unit' => trim((string) ($input['unit'] ?? '')),
        ];

        return [
            'agendaSekolah' => $this->getAll($filters),
            'unitSummary' => $this->getUnitStatistics($context['tahun_ajaran_int']),
            'filters' => $filters,
            'tahunAjaranOptions' => $context['tahunAjaranOptions'],
            'activeTahunAjaran' => $context['activeTahunAjaran'],
            'unitOptions' => AgendaSekolah::UNIT_OPTIONS,
        ];
    }

    /**
     * Mengambil daftar opsi dropdown untuk form tambah dan edit agenda sekolah.
     *
     * @return array{
     *     tahunAjaran: Collection<int, TahunAjaran>,
     *     unitOptions: list<string>
     * }
     */
    public function getFormOptions(): array
    {
        return [
            'tahunAjaran' => $this->getTahunAjaranOptions(),
            'unitOptions' => AgendaSekolah::UNIT_OPTIONS,
        ];
    }

    /**
     * Menyimpan data agenda sekolah baru (otomatis bertipe siswa pada aplikasi akademik).
     *
     * @param  array<string, mixed>  $data
     */
    public function store(array $data): AgendaSekolah
    {
        return $this->storeSiswaRecord(AgendaSekolah::class, $data);
    }

    /**
     * Memperbarui data agenda sekolah (otomatis mempertahankan tipe siswa).
     *
     * @param  array<string, mixed>  $data
     */
    public function update(AgendaSekolah $agendaSekolah, array $data): bool
    {
        return $this->updateSiswaRecord($agendaSekolah, $data);
    }

    /**
     * Menghapus data agenda sekolah.
     */
    public function delete(AgendaSekolah $agendaSekolah): bool
    {
        return (bool) $agendaSekolah->delete();
    }

    /**
     * Menghitung ringkasan jumlah agenda per unit dalam satu query agregat.
     *
     * @return array{total_agenda: int, per_unit: array<string, int>}
     */
    private function getUnitStatistics(?int $tahunAjaranId): array
    {
        $rows = $this->applyScopeFilters(AgendaSekolah::query(), $tahunAjaranId)
            ->selectRaw('unit, COUNT(*) as total')
            ->groupBy('unit')
            ->get();

        $perUnit = [];
        $totalAgenda = 0;

        foreach ($rows as $row) {
            $unitName = trim((string) ($row->unit ?? ''));
            $count = (int) ($row->total ?? 0);

            if ($unitName === '') {
                continue;
            }

            $perUnit[$unitName] = ($perUnit[$unitName] ?? 0) + $count;
            $totalAgenda += $count;
        }

        uksort($perUnit, static fn (string $a, string $b): int => AgendaSekolah::compareUnitOrder($a, $b));

        return [
            'total_agenda' => $totalAgenda,
            'per_unit' => $perUnit,
        ];
    }

    /**
     * Menerapkan filter tahun ajaran dan unit pada query agenda sekolah secara konsisten.
     *
     * @param  Builder<AgendaSekolah>  $query
     * @return Builder<AgendaSekolah>
     */
    private function applyScopeFilters(Builder $query, ?int $tahunAjaranId, string $unit = ''): Builder
    {
        return $this->applyTahunAjaranAndSiswaScope($query, $tahunAjaranId)
            ->when($unit !== '', function (Builder $scoped) use ($unit): void {
                $variants = $this->buildUnitVariants($unit);
                $scoped->whereIn($scoped->qualifyColumn('unit'), $variants);
            });
    }

    /**
     * Menghasilkan variasi kapitalisasi unit agar filter tetap akurat across collation.
     *
     * @return list<string>
     */
    private function buildUnitVariants(string $unit): array
    {
        $trimmed = trim($unit);
        $lower = strtolower($trimmed);

        return array_values(array_unique([
            $trimmed,
            strtoupper($trimmed),
            $lower,
            ucwords($lower),
        ]));
    }
}
