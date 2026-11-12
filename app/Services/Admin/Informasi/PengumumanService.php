<?php

namespace App\Services\Admin\Informasi;

use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Informasi\Pengumuman;
use App\Services\Admin\Informasi\Concerns\ManagesInformasiTahunAjaran;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class PengumumanService
{
    use ManagesInformasiTahunAjaran;

    /**
     * Membangun query daftar pengumuman beserta eager loading relasi untuk mencegah N+1.
     *
     * @param  array{
     *     tahun_ajaran_id?: string|int|null,
     *     search?: string|null
     * }  $filters
     * @return Builder<Pengumuman>
     */
    public function buildIndexQuery(array $filters = []): Builder
    {
        $tahunAjaranId = $this->resolveIntegerTahunAjaranId($filters['tahun_ajaran_id'] ?? null);
        $search = trim((string) ($filters['search'] ?? ''));

        $query = Pengumuman::query()
            ->select([
                'id',
                'akademik_master_tahun_ajaran_id',
                'judul',
                'isi',
                'jenis',
                'created_at',
            ])
            ->withMasterRelations()
            ->orderedLatest();

        return $this->applyScopeFilters($query, $tahunAjaranId, $search);
    }

    /**
     * Mengambil semua data pengumuman sesuai filter opsional tanpa N+1.
     *
     * @param  array{
     *     tahun_ajaran_id?: string|int|null,
     *     search?: string|null
     * }  $filters
     * @return Collection<int, Pengumuman>
     */
    public function getAll(array $filters = []): Collection
    {
        return $this->buildIndexQuery($filters)->get();
    }

    /**
     * Mengambil data lengkap halaman indeks pengumuman: default tahun ajaran aktif, pencarian judul/isi, dan ringkasan total.
     *
     * @param  array<string, mixed>  $input
     * @return array{
     *     pengumuman: Collection<int, Pengumuman>,
     *     totalPengumuman: int,
     *     filters: array{tahun_ajaran_id: string, search: string},
     *     tahunAjaranOptions: Collection<int, TahunAjaran>,
     *     activeTahunAjaran: ?TahunAjaran
     * }
     */
    public function getIndexData(array $input = []): array
    {
        $context = $this->resolveTahunAjaranContext($input);
        $filters = [
            'tahun_ajaran_id' => $context['tahun_ajaran_id'],
            'search' => trim((string) ($input['search'] ?? '')),
        ];

        $pengumuman = $this->getAll($filters);
        $totalPengumuman = $filters['search'] === ''
            ? $pengumuman->count()
            : $this->countForTahunAjaran($context['tahun_ajaran_int']);

        return [
            'pengumuman' => $pengumuman,
            'totalPengumuman' => $totalPengumuman,
            'filters' => $filters,
            'tahunAjaranOptions' => $context['tahunAjaranOptions'],
            'activeTahunAjaran' => $context['activeTahunAjaran'],
        ];
    }

    /**
     * Mengambil daftar opsi dropdown untuk form tambah dan edit pengumuman.
     *
     * @return array{
     *     tahunAjaran: Collection<int, TahunAjaran>
     * }
     */
    public function getFormOptions(): array
    {
        return [
            'tahunAjaran' => $this->getTahunAjaranOptions(),
        ];
    }

    /**
     * Menyimpan data pengumuman baru (otomatis bertipe siswa pada aplikasi akademik).
     *
     * @param  array<string, mixed>  $data
     */
    public function store(array $data): Pengumuman
    {
        return $this->storeSiswaRecord(Pengumuman::class, $data);
    }

    /**
     * Memperbarui data pengumuman (tetap bertipe siswa pada aplikasi akademik).
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Pengumuman $pengumuman, array $data): bool
    {
        return $this->updateSiswaRecord($pengumuman, $data);
    }

    /**
     * Menghapus data pengumuman.
     */
    public function delete(Pengumuman $pengumuman): bool
    {
        return (bool) $pengumuman->delete();
    }

    /**
     * Menghitung total pengumuman siswa pada cakupan tahun ajaran terpilih.
     */
    private function countForTahunAjaran(?int $tahunAjaranId): int
    {
        return $this->applyScopeFilters(Pengumuman::query(), $tahunAjaranId)->count();
    }

    /**
     * Menerapkan filter tahun ajaran dan kata kunci judul/isi pada query pengumuman secara konsisten.
     *
     * @param  Builder<Pengumuman>  $query
     * @return Builder<Pengumuman>
     */
    private function applyScopeFilters(Builder $query, ?int $tahunAjaranId, string $search = ''): Builder
    {
        return $this->applyTahunAjaranAndSiswaScope($query, $tahunAjaranId)
            ->when($search !== '', static function (Builder $scoped) use ($search): void {
                $escaped = addcslashes($search, '%_\\');
                $likePattern = '%'.$escaped.'%';

                $scoped->where(static function (Builder $nested) use ($likePattern): void {
                    $nested->where($nested->qualifyColumn('judul'), 'like', $likePattern)
                        ->orWhere($nested->qualifyColumn('isi'), 'like', $likePattern);
                });
            });
    }
}
