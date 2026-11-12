<?php

namespace App\Services\Admin\DataMaster\Concerns;

use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

trait ManagesTahunAjaranMembership
{
    /**
     * Menentukan ID tahun ajaran aktif atau tujuan berdasarkan opsi master (Kelas / Ekstrakurikuler / Jemputan).
     *
     * @param  Collection<int, Model>  $items
     * @return list<int>
     */
    private function resolveExcludedTahunAjaranIds(Collection $items, ?int $targetId = null): array
    {
        if ($targetId !== null) {
            $selected = $items->firstWhere('id', $targetId);
            $tahunAjaranId = $selected?->getAttribute('akademik_master_tahun_ajaran_id');
            if ($tahunAjaranId !== null) {
                return [(int) $tahunAjaranId];
            }
        }

        $activeIds = $items
            ->filter(static fn (Model $item): bool => (bool) $item->getRelationValue('tahunAjaran')?->is_active)
            ->pluck('akademik_master_tahun_ajaran_id')
            ->filter(static fn (mixed $id): bool => $id !== null)
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($activeIds !== []) {
            return $activeIds;
        }

        $latestItem = $items->first();
        $latestTahunAjaranId = $latestItem?->getAttribute('akademik_master_tahun_ajaran_id');
        if ($latestTahunAjaranId !== null) {
            return [(int) $latestTahunAjaranId];
        }

        return [];
    }

    /**
     * Menormalisasi input identifier tunggal maupun array menjadi daftar string unik.
     *
     * @return list<string>
     */
    private function normalizeBatchIdentifiers(mixed $rawValues): array
    {
        return is_array($rawValues)
            ? array_values(array_unique(array_map('strval', $rawValues)))
            : [(string) $rawValues];
    }

    /**
     * Menghitung statistik jumlah total serta siswa laki-laki dan perempuan dari koleksi keanggotaan ter-load.
     *
     * @param  Collection<int, Model>  $members
     * @param  callable(Model): ?Siswa  $siswaResolver
     * @return array{total: int, laki_laki: int, perempuan: int}
     */
    private function computeMemberGenderStatsFrom(Collection $members, callable $siswaResolver): array
    {
        $lakiLaki = $members->filter(
            static fn (Model $item): bool => $siswaResolver($item)?->jenis_kelamin === 'L'
        )->count();

        $perempuan = $members->filter(
            static fn (Model $item): bool => $siswaResolver($item)?->jenis_kelamin === 'P'
        )->count();

        return [
            'total' => $members->count(),
            'laki_laki' => $lakiLaki,
            'perempuan' => $perempuan,
        ];
    }

    /**
     * Mengambil opsi filter Tahun Ajaran dan Kelas standar untuk halaman indeks keanggotaan.
     *
     * @return array{
     *     tahunAjaran: Collection<int, TahunAjaran>,
     *     kelas: Collection<int, Kelas>
     * }
     */
    private function buildSharedMembershipFilterOptions(): array
    {
        return [
            'tahunAjaran' => TahunAjaran::query()
                ->select(['id', 'nama_tahun_ajaran', 'semester', 'is_active'])
                ->orderByDesc('nama_tahun_ajaran')
                ->orderBy('semester')
                ->get(),
            'kelas' => Kelas::query()
                ->select(['id', 'akademik_master_tahun_ajaran_id', 'nama_kelas', 'jenjang_kelas', 'tingkatan_kelas'])
                ->with(['tahunAjaran:id,nama_tahun_ajaran,semester,is_active'])
                ->orderByDesc('akademik_master_tahun_ajaran_id')
                ->orderBy('jenjang_kelas')
                ->orderBy('nama_kelas')
                ->get(),
        ];
    }

    /**
     * Membangun payload kandidat Anggota Kelas aktif dan jumlah data yang disembunyikan.
     *
     * @param  list<int>  $targetTahunAjaranIds
     * @param  Builder<AnggotaKelas>  $candidateQuery
     * @return array{
     *     anggotaKelas: Collection<int, AnggotaKelas>,
     *     excludedTahunAjaranIds: list<int>,
     *     hiddenRegisteredCount: int,
     *     hiddenInactiveCount: int,
     *     hiddenWithoutKelasCount: int
     * }
     */
    private function buildCandidateAnggotaKelasPayloadFor(
        array $targetTahunAjaranIds,
        Builder $candidateQuery,
        string $siswaMembershipNestedRelation
    ): array {
        $anggotaKelas = $candidateQuery->get();

        $hiddenRegisteredCount = $targetTahunAjaranIds === []
            ? 0
            : AnggotaKelas::query()
                ->whereHas('siswa', fn (Builder $siswaQuery) => $siswaQuery->active())
                ->whereHas('kelas', fn (Builder $kelasQuery) => $kelasQuery->whereIn('akademik_master_tahun_ajaran_id', $targetTahunAjaranIds))
                ->whereHas(
                    "siswa.{$siswaMembershipNestedRelation}",
                    fn (Builder $groupQuery) => $groupQuery->whereIn('akademik_master_tahun_ajaran_id', $targetTahunAjaranIds)
                )
                ->count();

        $hiddenInactiveCount = AnggotaKelas::query()
            ->whereHas('siswa', fn (Builder $siswaQuery) => $siswaQuery->inactive())
            ->when(
                $targetTahunAjaranIds !== [],
                fn (Builder $query) => $query->whereHas(
                    'kelas',
                    fn (Builder $kelasQuery) => $kelasQuery->whereIn('akademik_master_tahun_ajaran_id', $targetTahunAjaranIds)
                )
            )
            ->count();

        $hiddenWithoutKelasCount = $targetTahunAjaranIds === []
            ? 0
            : Siswa::query()
                ->active()
                ->whereDoesntHave(
                    'anggotaKelas.kelas',
                    fn (Builder $kelasQuery) => $kelasQuery->whereIn('akademik_master_tahun_ajaran_id', $targetTahunAjaranIds)
                )
                ->count();

        return [
            'anggotaKelas' => $anggotaKelas,
            'excludedTahunAjaranIds' => $targetTahunAjaranIds,
            'hiddenRegisteredCount' => $hiddenRegisteredCount,
            'hiddenInactiveCount' => $hiddenInactiveCount,
            'hiddenWithoutKelasCount' => $hiddenWithoutKelasCount,
        ];
    }

    /**
     * Memfilter query kandidat Anggota Kelas agar hanya mencakup siswa aktif yang sudah punya kelas
     * di tahun ajaran tujuan dan belum terdaftar pada keanggotaan grup tersebut di tahun ajaran yang sama.
     *
     * @param  Builder<AnggotaKelas>  $baseQuery
     * @param  list<int>  $targetTahunAjaranIds
     * @return Builder<AnggotaKelas>
     */
    private function applyCandidateAnggotaKelasFilters(
        Builder $baseQuery,
        array $targetTahunAjaranIds,
        string $siswaMembershipNestedRelation
    ): Builder {
        return $baseQuery
            ->whereHas('siswa', fn (Builder $siswaQuery) => $siswaQuery->active())
            ->when(
                $targetTahunAjaranIds !== [],
                fn (Builder $query) => $query
                    ->whereHas(
                        'kelas',
                        fn (Builder $kelasQuery) => $kelasQuery->whereIn('akademik_master_tahun_ajaran_id', $targetTahunAjaranIds)
                    )
                    ->whereDoesntHave(
                        "siswa.{$siswaMembershipNestedRelation}",
                        fn (Builder $groupQuery) => $groupQuery->whereIn('akademik_master_tahun_ajaran_id', $targetTahunAjaranIds)
                    )
            );
    }

    /**
     * Mencari ID anggota kelas yang tahun ajaran kelasnya tidak sama dengan tahun ajaran grup tujuan.
     *
     * @param  list<int>  $anggotaKelasIds
     * @return list<int>
     */
    private function findMismatchedTahunAjaranAnggotaKelasIds(array $anggotaKelasIds, int $tahunAjaranId): array
    {
        if ($anggotaKelasIds === []) {
            return [];
        }

        return AnggotaKelas::query()
            ->whereIn('id', $anggotaKelasIds)
            ->whereHas(
                'kelas',
                fn (Builder $kelasQuery) => $kelasQuery->where('akademik_master_tahun_ajaran_id', '!=', $tahunAjaranId)
            )
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Mencari daftar NIS siswa dari anggota kelas terpilih yang sudah terdaftar pada grup sejenis
     * dalam tahun ajaran dan semester yang sama.
     *
     * @param  class-string<Model>  $membershipModelClass
     * @param  list<int>  $anggotaKelasIds
     * @return list<string>
     */
    private function findConflictingMemberNisInTahunAjaran(
        string $membershipModelClass,
        array $anggotaKelasIds,
        int $tahunAjaranId,
        ?int $ignoreMembershipId = null,
        bool $lockForUpdate = false
    ): array {
        if ($anggotaKelasIds === []) {
            return [];
        }

        $selectedNis = AnggotaKelas::query()
            ->whereIn('id', $anggotaKelasIds)
            ->pluck('siswa_nis')
            ->filter()
            ->map(static fn (mixed $nis): string => (string) $nis)
            ->unique()
            ->values()
            ->all();

        $conflicts = $membershipModelClass::query()
            ->with(['anggotaKelas:id,siswa_nis'])
            ->forTahunAjaran($tahunAjaranId)
            ->where(static function (Builder $query) use ($anggotaKelasIds, $selectedNis): void {
                $query->whereIn('anggota_kelas_id', $anggotaKelasIds);
                if ($selectedNis !== []) {
                    $query->orWhereHas(
                        'anggotaKelas',
                        fn (Builder $akQuery) => $akQuery->whereIn('siswa_nis', $selectedNis)
                    );
                }
            })
            ->when(
                $ignoreMembershipId !== null,
                fn (Builder $query) => $query->whereKeyNot($ignoreMembershipId)
            )
            ->when($lockForUpdate, fn (Builder $query) => $query->lockForUpdate())
            ->get();

        return $conflicts
            ->map(static fn (Model $item): string => (string) ($item->getRelationValue('anggotaKelas')?->siswa_nis ?? $item->getAttribute('anggota_kelas_id')))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Memvalidasi kesesuaian tahun ajaran anggota kelas dan kebebasan konflik dalam transaksi.
     *
     * @param  class-string<Model>  $membershipModelClass
     * @param  list<int>  $anggotaKelasIds
     * @param  Collection<int, AnggotaKelas>  $anggotaKelasRows
     *
     * @throws ValidationException
     */
    private function assertValidAnggotaKelasTahunAjaranAndNoConflict(
        string $membershipModelClass,
        string $entityLabel,
        array $anggotaKelasIds,
        Collection $anggotaKelasRows,
        int $tahunAjaranId,
        ?int $ignoreMembershipId = null
    ): void {
        $hasMismatch = $anggotaKelasRows->contains(
            static fn (AnggotaKelas $item): bool => (int) ($item->kelas?->akademik_master_tahun_ajaran_id ?? 0) !== $tahunAjaranId
        );

        if ($hasMismatch || $anggotaKelasRows->count() !== count($anggotaKelasIds)) {
            throw ValidationException::withMessages([
                'anggota_kelas_id' => "Anggota kelas yang dipilih tidak berada pada tahun ajaran dan semester yang sama dengan {$entityLabel} tujuan.",
            ]);
        }

        $conflicts = $this->findConflictingMemberNisInTahunAjaran(
            $membershipModelClass,
            $anggotaKelasIds,
            $tahunAjaranId,
            $ignoreMembershipId,
            lockForUpdate: true
        );

        if ($conflicts !== []) {
            throw ValidationException::withMessages([
                'anggota_kelas_id' => 'Siswa dengan NIS '.implode(', ', $conflicts)." sudah terdaftar di {$entityLabel} pada tahun ajaran dan semester yang dipilih.",
            ]);
        }
    }

    /**
     * Menyimpan satu atau beberapa keanggotaan berbasis anggota_kelas_id secara atomik dan menyinkronkan Siswa.
     *
     * @template TMembership of Model
     * @template TGroup of Model
     *
     * @param  array<string, mixed>  $data
     * @param  class-string<TMembership>  $membershipModelClass
     * @param  class-string<TGroup>  $groupModelClass
     * @return Collection<int, TMembership>
     */
    private function storeAnggotaKelasMemberships(
        array $data,
        string $membershipModelClass,
        string $groupModelClass,
        string $groupForeignKey,
        string $entityLabel
    ): Collection {
        $anggotaKelasIds = array_values(array_unique(array_map(
            'intval',
            $this->normalizeBatchIdentifiers($data['anggota_kelas_id'])
        )));
        $groupId = (int) $data[$groupForeignKey];
        $pendaftaran = (string) $data['pendaftaran'];
        $keterangan = $this->normalizeNullableText($data['keterangan'] ?? null);

        return DB::transaction(function () use (
            $membershipModelClass,
            $groupModelClass,
            $groupForeignKey,
            $entityLabel,
            $anggotaKelasIds,
            $groupId,
            $pendaftaran,
            $keterangan
        ): Collection {
            $group = $groupModelClass::query()
                ->select(['id', 'akademik_master_tahun_ajaran_id'])
                ->with(['tahunAjaran:id,is_active'])
                ->findOrFail($groupId);

            $anggotaKelasRows = AnggotaKelas::query()
                ->select(['id', 'siswa_nis', 'akademik_master_kelas_id'])
                ->with(['kelas:id,akademik_master_tahun_ajaran_id'])
                ->whereIn('id', $anggotaKelasIds)
                ->get()
                ->keyBy('id');

            $this->assertValidAnggotaKelasTahunAjaranAndNoConflict(
                $membershipModelClass,
                $entityLabel,
                $anggotaKelasIds,
                $anggotaKelasRows,
                (int) $group->getAttribute('akademik_master_tahun_ajaran_id')
            );

            $created = new Collection;

            foreach ($anggotaKelasIds as $anggotaKelasId) {
                $created->push($membershipModelClass::create([
                    'anggota_kelas_id' => $anggotaKelasId,
                    $groupForeignKey => $group->getKey(),
                    'pendaftaran' => $pendaftaran,
                    'keterangan' => $keterangan,
                ]));
            }

            if ($group->getRelationValue('tahunAjaran')?->is_active) {
                $nisList = $anggotaKelasRows
                    ->pluck('siswa_nis')
                    ->filter()
                    ->map(static fn (mixed $nis): string => (string) $nis)
                    ->unique()
                    ->values()
                    ->all();

                if ($nisList !== []) {
                    Siswa::query()
                        ->whereIn('nis', $nisList)
                        ->update([$groupForeignKey => $group->getKey()]);
                }
            }

            return $created;
        });
    }

    /**
     * Memperbarui keanggotaan berbasis anggota_kelas_id secara atomik dan menyinkronkan Siswa.
     *
     * @param  class-string<Model>  $membershipModelClass
     * @param  class-string<Model>  $groupModelClass
     * @param  array<string, mixed>  $data
     */
    private function updateAnggotaKelasMembership(
        Model $membership,
        array $data,
        string $membershipModelClass,
        string $groupModelClass,
        string $groupRelation,
        string $groupForeignKey,
        string $entityLabel
    ): bool {
        $newAnggotaKelasId = (int) $data['anggota_kelas_id'];
        $newGroupId = (int) $data[$groupForeignKey];
        $keterangan = $this->normalizeNullableText($data['keterangan'] ?? null);

        return DB::transaction(function () use (
            $membership,
            $data,
            $membershipModelClass,
            $groupModelClass,
            $groupRelation,
            $groupForeignKey,
            $entityLabel,
            $newAnggotaKelasId,
            $newGroupId,
            $keterangan
        ): bool {
            $membership->loadMissing([
                'anggotaKelas:id,siswa_nis',
                "{$groupRelation}:id,akademik_master_tahun_ajaran_id",
                "{$groupRelation}.tahunAjaran:id,is_active",
            ]);

            $targetGroup = $groupModelClass::query()
                ->select(['id', 'akademik_master_tahun_ajaran_id'])
                ->with(['tahunAjaran:id,is_active'])
                ->findOrFail($newGroupId);

            $targetAnggotaKelas = AnggotaKelas::query()
                ->select(['id', 'siswa_nis', 'akademik_master_kelas_id'])
                ->with(['kelas:id,akademik_master_tahun_ajaran_id'])
                ->findOrFail($newAnggotaKelasId);

            $this->assertValidAnggotaKelasTahunAjaranAndNoConflict(
                $membershipModelClass,
                $entityLabel,
                [$newAnggotaKelasId],
                new Collection([$targetAnggotaKelas->id => $targetAnggotaKelas]),
                (int) $targetGroup->getAttribute('akademik_master_tahun_ajaran_id'),
                (int) $membership->getKey()
            );

            $oldNis = $membership->getRelationValue('anggotaKelas')?->siswa_nis;
            $oldGroupId = $membership->getAttribute($groupForeignKey);
            $wasActiveYear = (bool) $membership->getRelationValue($groupRelation)?->getRelationValue('tahunAjaran')?->is_active;
            $newNis = $targetAnggotaKelas->siswa_nis;
            $isTargetActive = (bool) $targetGroup->getRelationValue('tahunAjaran')?->is_active;

            $updated = $membership->update([
                'anggota_kelas_id' => $targetAnggotaKelas->id,
                $groupForeignKey => $targetGroup->getKey(),
                'pendaftaran' => $data['pendaftaran'],
                'keterangan' => $keterangan,
            ]);

            if ($updated) {
                if ($wasActiveYear && $oldNis !== null && ($oldNis !== $newNis || ! $isTargetActive)) {
                    Siswa::query()
                        ->where('nis', $oldNis)
                        ->where($groupForeignKey, $oldGroupId)
                        ->update([$groupForeignKey => null]);
                }

                if ($isTargetActive && $newNis !== null) {
                    Siswa::query()
                        ->where('nis', $newNis)
                        ->update([$groupForeignKey => $targetGroup->getKey()]);
                }
            }

            return $updated;
        });
    }

    /**
     * Menghapus keanggotaan berbasis anggota_kelas_id secara atomik dan membersihkan kolom aktif Siswa.
     */
    private function deleteAnggotaKelasMembership(
        Model $membership,
        string $groupRelation,
        string $groupForeignKey
    ): bool {
        return DB::transaction(function () use ($membership, $groupRelation, $groupForeignKey): bool {
            $membership->loadMissing([
                'anggotaKelas:id,siswa_nis',
                "{$groupRelation}:id,akademik_master_tahun_ajaran_id",
                "{$groupRelation}.tahunAjaran:id,is_active",
            ]);

            $siswaNis = $membership->getRelationValue('anggotaKelas')?->siswa_nis;
            $groupId = $membership->getAttribute($groupForeignKey);
            $wasActiveYear = (bool) $membership->getRelationValue($groupRelation)?->getRelationValue('tahunAjaran')?->is_active;

            $deleted = (bool) $membership->delete();

            if ($deleted && $wasActiveYear && $siswaNis !== null) {
                Siswa::query()
                    ->where('nis', $siswaNis)
                    ->where($groupForeignKey, $groupId)
                    ->update([$groupForeignKey => null]);
            }

            return $deleted;
        });
    }

    private function normalizeNullableText(mixed $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
