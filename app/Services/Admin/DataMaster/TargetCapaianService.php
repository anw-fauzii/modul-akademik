<?php

namespace App\Services\Admin\DataMaster;

use App\Models\Admin\DataMaster\AnggotaT2q;
use App\Models\Admin\DataMaster\DaftarJilid;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Surah;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\DataMaster\TargetCapaian;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class TargetCapaianService
{
    private const array INDEX_COLUMNS = [
        'id',
        'akademik_master_tahun_ajaran_id',
        'tingkat',
        'akademik_master_t2q_daftar_jilid_id',
        'akademik_master_t2q_surah_id',
        'created_at',
    ];

    /**
     * Mengambil satu Tahun Ajaran yang sedang aktif.
     */
    public function resolveActiveTahunAjaran(): ?TahunAjaran
    {
        return TahunAjaran::query()
            ->active()
            ->orderedForDropdown()
            ->first();
    }

    /**
     * Membangun query target capaian T2Q dengan kolom minimal dan eager loading relasi tanpa N+1.
     *
     * @param  array{
     *     tahun_ajaran_id?: string|int|null,
     *     tingkat?: string|null,
     *     search?: string|null
     * }  $filters
     * @return Builder<TargetCapaian>
     */
    public function buildIndexQuery(array $filters = []): Builder
    {
        $tahunAjaranId = $this->parsePositiveInt($filters['tahun_ajaran_id'] ?? null);
        $tingkat = TargetCapaian::normalizeTingkat($filters['tingkat'] ?? '');
        $search = trim((string) ($filters['search'] ?? ''));

        return TargetCapaian::query()
            ->select(self::INDEX_COLUMNS)
            ->withMasterRelations()
            ->forTahunAjaran($tahunAjaranId)
            ->forTingkat($tingkat)
            ->when($search !== '', static function (Builder $query) use ($search): void {
                $escaped = addcslashes($search, '%_\\');
                $likePattern = '%'.$escaped.'%';

                $query->where(static function (Builder $nested) use ($likePattern): void {
                    $nested->where($nested->qualifyColumn('tingkat'), 'like', $likePattern)
                        ->orWhereHas('daftarJilid', static function (Builder $jilidQuery) use ($likePattern): void {
                            $jilidQuery->where('jilid_latin', 'like', $likePattern)
                                ->orWhere('jilid_arab', 'like', $likePattern)
                                ->orWhere('jenis', 'like', $likePattern);
                        })
                        ->orWhereHas('surah', static function (Builder $surahQuery) use ($likePattern): void {
                            $surahQuery->where('nama_surah', 'like', $likePattern)
                                ->orWhere('nama_surah_arab', 'like', $likePattern);
                        });
                });
            })
            ->ordered();
    }

    /**
     * Mengambil semua data target capaian sesuai filter opsional dan urutan tingkat sekolah tanpa N+1.
     *
     * @param  array{
     *     tahun_ajaran_id?: string|int|null,
     *     tingkat?: string|null,
     *     search?: string|null
     * }  $filters
     * @return Collection<int, TargetCapaian>
     */
    public function getAll(array $filters = []): Collection
    {
        /** @var Collection<int, TargetCapaian> $items */
        $items = $this->buildIndexQuery($filters)->get();

        $sorted = $items->all();
        usort($sorted, static function (TargetCapaian $a, TargetCapaian $b): int {
            $tingkatCmp = TargetCapaian::compareTingkatOrder((string) $a->tingkat, (string) $b->tingkat);
            if ($tingkatCmp !== 0) {
                return $tingkatCmp;
            }

            $aUrutan = $a->relationLoaded('daftarJilid') ? (int) ($a->daftarJilid?->urutan ?? PHP_INT_MAX) : PHP_INT_MAX;
            $bUrutan = $b->relationLoaded('daftarJilid') ? (int) ($b->daftarJilid?->urutan ?? PHP_INT_MAX) : PHP_INT_MAX;
            if ($aUrutan !== $bUrutan) {
                return $aUrutan <=> $bUrutan;
            }

            return (int) $a->id <=> (int) $b->id;
        });

        return new Collection($sorted);
    }

    /**
     * Mengambil data halaman indeks target capaian T2Q untuk satu Tahun Ajaran aktif.
     *
     * @param  array<string, mixed>  $input
     * @return array{
     *     targetCapaian: Collection<int, TargetCapaian>,
     *     activeTahunAjaran: ?TahunAjaran,
     *     filters: array{tingkat: string, search: string},
     *     tingkatOptions: list<string>
     * }
     */
    public function getIndexData(array $input = []): array
    {
        $activeTahunAjaran = $this->resolveActiveTahunAjaran();

        $filters = [
            'tingkat' => TargetCapaian::normalizeTingkat($input['tingkat'] ?? ''),
            'search' => trim((string) ($input['search'] ?? '')),
        ];

        $targetCapaian = $activeTahunAjaran
            ? $this->getAll([
                ...$filters,
                'tahun_ajaran_id' => $activeTahunAjaran->id,
            ])
            : new Collection;

        return [
            'targetCapaian' => $targetCapaian,
            'activeTahunAjaran' => $activeTahunAjaran,
            'filters' => $filters,
            'tingkatOptions' => $this->getTingkatOptions(),
        ];
    }

    /**
     * Mengambil opsi form tambah dan edit target capaian T2Q menggunakan Tahun Ajaran aktif tunggal tanpa N+1.
     *
     * @return array{
     *     activeTahunAjaran: ?TahunAjaran,
     *     tingkatOptions: list<string>,
     *     daftarJilid: Collection<int, DaftarJilid>,
     *     surahList: Collection<int, Surah>
     * }
     */
    public function getFormOptions(): array
    {
        return [
            'activeTahunAjaran' => $this->resolveActiveTahunAjaran(),
            'tingkatOptions' => $this->getTingkatOptions(),
            'daftarJilid' => DaftarJilid::query()
                ->select(['id', 'jilid_latin', 'jilid_arab', 'jenis', 'urutan'])
                ->orderBy('urutan')
                ->orderBy('id')
                ->get(),
            'surahList' => Surah::query()
                ->select(['id', 'nama_surah', 'nama_surah_arab', 'jumlah_ayat'])
                ->orderBy('id')
                ->get(),
        ];
    }

    /**
     * Menyimpan target capaian T2Q baru.
     *
     * @param  array<string, mixed>  $data
     */
    public function store(array $data): TargetCapaian
    {
        return TargetCapaian::create($data);
    }

    /**
     * Memperbarui target capaian T2Q yang dipilih.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(TargetCapaian $targetCapaian, array $data): bool
    {
        return $targetCapaian->update($data);
    }

    /**
     * Menghapus target capaian T2Q.
     */
    public function delete(TargetCapaian $targetCapaian): bool
    {
        return (bool) $targetCapaian->delete();
    }

    /**
     * Menggabungkan daftar tingkat standar sekolah dengan tingkat yang sudah ada di DB secara DRY.
     *
     * @return list<string>
     */
    private function getTingkatOptions(): array
    {
        $kelasTingkat = Kelas::query()
            ->select('tingkatan_kelas')
            ->whereNotNull('tingkatan_kelas')
            ->distinct()
            ->pluck('tingkatan_kelas')
            ->all();

        $targetTingkat = TargetCapaian::query()
            ->select('tingkat')
            ->whereNotNull('tingkat')
            ->distinct()
            ->pluck('tingkat')
            ->all();

        return AnggotaT2q::buildTingkatOptions(array_merge($kelasTingkat, $targetTingkat));
    }

    private function parsePositiveInt(mixed $value): ?int
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '' || ! ctype_digit($raw)) {
            return null;
        }

        $parsed = (int) $raw;

        return $parsed > 0 ? $parsed : null;
    }
}
