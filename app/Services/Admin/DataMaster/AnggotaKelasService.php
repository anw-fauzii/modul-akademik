<?php

namespace App\Services\Admin\DataMaster;

use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Services\Admin\DataMaster\Concerns\ManagesTahunAjaranMembership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AnggotaKelasService
{
    use ManagesTahunAjaranMembership;

    /**
     * Membangun query daftar anggota kelas beserta relasi utama tanpa N+1.
     *
     * @return Builder<AnggotaKelas>
     */
    public function buildIndexQuery(?int $tahunAjaranId = null, ?int $kelasId = null): Builder
    {
        return AnggotaKelas::query()
            ->select([
                'id',
                'siswa_nis',
                'akademik_master_kelas_id',
                'pendaftaran',
            ])
            ->withMasterRelations()
            ->when($tahunAjaranId !== null, fn (Builder $query) => $query->forTahunAjaran($tahunAjaranId))
            ->when($kelasId !== null, fn (Builder $query) => $query->forKelas($kelasId))
            ->orderByDesc('akademik_master_kelas_id')
            ->orderBy('siswa_nis')
            ->orderBy('id');
    }

    /**
     * Mengambil semua data anggota kelas dengan filter opsional tanpa N+1.
     *
     * @return Collection<int, AnggotaKelas>
     */
    public function getAll(?int $tahunAjaranId = null, ?int $kelasId = null): Collection
    {
        return $this->buildIndexQuery($tahunAjaranId, $kelasId)->get();
    }

    /**
     * Mengambil daftar opsi filter untuk halaman indeks anggota kelas.
     *
     * @return array{
     *     tahunAjaran: Collection<int, TahunAjaran>,
     *     kelas: Collection<int, Kelas>
     * }
     */
    public function getFilterOptions(): array
    {
        return [
            'tahunAjaran' => TahunAjaran::query()
                ->select(['id', 'nama_tahun_ajaran', 'semester', 'is_active'])
                ->orderByDesc('nama_tahun_ajaran')
                ->orderBy('semester')
                ->get(),
            'kelas' => $this->getKelasOptions(),
        ];
    }

    /**
     * Mengambil daftar opsi dropdown untuk form edit anggota kelas tanpa N+1.
     *
     * @return array{
     *     kelas: Collection<int, Kelas>,
     *     siswa: Collection<int, Siswa>
     * }
     */
    public function getFormOptions(): array
    {
        return [
            'kelas' => $this->getKelasOptions(),
            'siswa' => $this->baseSiswaFormQuery()->get(),
        ];
    }

    /**
     * Mengambil opsi form tambah anggota kelas dengan hanya memuat siswa aktif
     * yang belum terdaftar di Anggota Kelas pada tahun ajaran aktif / tujuan.
     *
     * @return array{
     *     kelas: Collection<int, Kelas>,
     *     siswa: Collection<int, Siswa>,
     *     excludedTahunAjaranIds: list<int>,
     *     hiddenRegisteredCount: int,
     *     hiddenInactiveCount: int
     * }
     */
    public function getCreateFormOptions(?int $targetKelasId = null): array
    {
        $kelas = $this->getKelasOptions();
        $excludedTahunAjaranIds = $this->resolveExcludedTahunAjaranIds($kelas, $targetKelasId);

        return [
            'kelas' => $kelas,
            ...$this->buildCandidateSiswaPayload($excludedTahunAjaranIds),
        ];
    }

    /**
     * Mengambil kandidat siswa aktif beserta ringkasan siswa yang disembunyikan
     * (sudah terdaftar di tahun ajaran pada kelas tujuan) untuk halaman detail kelas.
     *
     * @return array{
     *     siswa: Collection<int, Siswa>,
     *     excludedTahunAjaranIds: list<int>,
     *     hiddenRegisteredCount: int,
     *     hiddenInactiveCount: int
     * }
     */
    public function getCandidateSiswaForKelas(Kelas $kelas): array
    {
        $excludedTahunAjaranIds = $kelas->akademik_master_tahun_ajaran_id !== null
            ? [(int) $kelas->akademik_master_tahun_ajaran_id]
            : [];

        return $this->buildCandidateSiswaPayload($excludedTahunAjaranIds);
    }

    /**
     * Membangun payload kandidat siswa aktif dan jumlah siswa yang disembunyikan.
     *
     * @param  list<int>  $excludedTahunAjaranIds
     * @return array{
     *     siswa: Collection<int, Siswa>,
     *     excludedTahunAjaranIds: list<int>,
     *     hiddenRegisteredCount: int,
     *     hiddenInactiveCount: int
     * }
     */
    private function buildCandidateSiswaPayload(array $excludedTahunAjaranIds): array
    {
        $siswa = $this->buildCreateSiswaQuery($excludedTahunAjaranIds)->get();

        $hiddenRegisteredCount = $excludedTahunAjaranIds === []
            ? 0
            : Siswa::query()
                ->active()
                ->whereHas(
                    'anggotaKelas.kelas',
                    fn (Builder $kelasQuery) => $kelasQuery->whereIn('akademik_master_tahun_ajaran_id', $excludedTahunAjaranIds)
                )
                ->count();

        $hiddenInactiveCount = Siswa::query()
            ->inactive()
            ->count();

        return [
            'siswa' => $siswa,
            'excludedTahunAjaranIds' => $excludedTahunAjaranIds,
            'hiddenRegisteredCount' => $hiddenRegisteredCount,
            'hiddenInactiveCount' => $hiddenInactiveCount,
        ];
    }

    /**
     * Membangun query kandidat siswa pada form tambah anggota kelas
     * (hanya siswa berstatus Aktif yang belum terdaftar pada tahun ajaran tujuan).
     *
     * @param  list<int>  $excludedTahunAjaranIds
     * @return Builder<Siswa>
     */
    public function buildCreateSiswaQuery(array $excludedTahunAjaranIds = []): Builder
    {
        return $this->baseSiswaFormQuery()
            ->active()
            ->when(
                $excludedTahunAjaranIds !== [],
                fn (Builder $query) => $query->whereDoesntHave(
                    'anggotaKelas.kelas',
                    fn (Builder $kelasQuery) => $kelasQuery->whereIn('akademik_master_tahun_ajaran_id', $excludedTahunAjaranIds)
                )
            );
    }

    /**
     * Query dasar daftar siswa beserta relasi kelas sebelumnya untuk form anggota kelas.
     *
     * @return Builder<Siswa>
     */
    private function baseSiswaFormQuery(): Builder
    {
        return Siswa::query()
            ->select([
                'nis',
                'akademik_master_kelas_id',
                'nama_lengkap',
                'jenis_kelamin',
                'nisn',
                'status',
            ])
            ->with([
                'kelas:id,akademik_master_tahun_ajaran_id,nama_kelas,jenjang_kelas,tingkatan_kelas',
                'kelas.tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
                'anggotaKelas:id,siswa_nis,akademik_master_kelas_id,pendaftaran',
                'anggotaKelas.kelas:id,akademik_master_tahun_ajaran_id,nama_kelas,jenjang_kelas,tingkatan_kelas',
                'anggotaKelas.kelas.tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
            ])
            ->orderBy('nama_lengkap')
            ->orderBy('nis');
    }

    /**
     * Mencari daftar NIS yang sudah terdaftar di kelas lain pada tahun ajaran/semester yang sama.
     *
     * @param  list<string>  $nisList
     * @return list<string>
     */
    public function findConflictingNisInTahunAjaran(
        array $nisList,
        int $tahunAjaranId,
        ?int $ignoreAnggotaKelasId = null,
        bool $lockForUpdate = false
    ): array {
        if ($nisList === []) {
            return [];
        }

        return AnggotaKelas::query()
            ->whereIn('siswa_nis', $nisList)
            ->forTahunAjaran($tahunAjaranId)
            ->when(
                $ignoreAnggotaKelasId !== null,
                fn (Builder $query) => $query->whereKeyNot($ignoreAnggotaKelasId)
            )
            ->when($lockForUpdate, fn (Builder $query) => $query->lockForUpdate())
            ->pluck('siswa_nis')
            ->map(static fn (mixed $nis): string => (string) $nis)
            ->all();
    }

    /**
     * Menyimpan satu atau beberapa anggota kelas secara atomik dalam satu transaksi.
     *
     * @param  array<string, mixed>  $data
     * @return Collection<int, AnggotaKelas>
     */
    public function store(array $data): Collection
    {
        $nisList = $this->normalizeBatchIdentifiers($data['siswa_nis']);
        $kelasId = (int) $data['akademik_master_kelas_id'];
        $pendaftaran = (string) $data['pendaftaran'];

        return DB::transaction(function () use ($nisList, $kelasId, $pendaftaran): Collection {
            $kelas = Kelas::query()
                ->select(['id', 'akademik_master_tahun_ajaran_id'])
                ->with(['tahunAjaran:id,is_active'])
                ->findOrFail($kelasId);

            $this->assertNoTahunAjaranConflict(
                $nisList,
                (int) $kelas->akademik_master_tahun_ajaran_id
            );

            $created = new Collection;

            foreach ($nisList as $nis) {
                $created->push(AnggotaKelas::create([
                    'siswa_nis' => $nis,
                    'akademik_master_kelas_id' => $kelas->id,
                    'pendaftaran' => $pendaftaran,
                ]));
            }

            if ($kelas->tahunAjaran?->is_active) {
                Siswa::query()
                    ->whereIn('nis', $nisList)
                    ->update(['akademik_master_kelas_id' => $kelas->id]);
            }

            return $created;
        });
    }

    /**
     * Memperbarui data anggota kelas secara atomik dan menyinkronkan kelas aktif siswa.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(AnggotaKelas $anggotaKelas, array $data): bool
    {
        $newNis = (string) $data['siswa_nis'];
        $newKelasId = (int) $data['akademik_master_kelas_id'];

        return DB::transaction(function () use ($anggotaKelas, $data, $newNis, $newKelasId): bool {
            $anggotaKelas->loadMissing([
                'kelas:id,akademik_master_tahun_ajaran_id',
                'kelas.tahunAjaran:id,is_active',
            ]);

            $targetKelas = Kelas::query()
                ->select(['id', 'akademik_master_tahun_ajaran_id'])
                ->with(['tahunAjaran:id,is_active'])
                ->findOrFail($newKelasId);

            $this->assertNoTahunAjaranConflict(
                [$newNis],
                (int) $targetKelas->akademik_master_tahun_ajaran_id,
                $anggotaKelas->id
            );

            $oldNis = $anggotaKelas->siswa_nis;
            $oldKelasId = $anggotaKelas->akademik_master_kelas_id;
            $wasActiveYear = (bool) $anggotaKelas->kelas?->tahunAjaran?->is_active;

            $updated = $anggotaKelas->update([
                'siswa_nis' => $newNis,
                'akademik_master_kelas_id' => $targetKelas->id,
                'pendaftaran' => $data['pendaftaran'],
            ]);

            if ($updated) {
                if ($wasActiveYear && ($oldNis !== $newNis || ! $targetKelas->tahunAjaran?->is_active)) {
                    Siswa::query()
                        ->where('nis', $oldNis)
                        ->where('akademik_master_kelas_id', $oldKelasId)
                        ->update(['akademik_master_kelas_id' => null]);
                }

                if ($targetKelas->tahunAjaran?->is_active) {
                    Siswa::query()
                        ->where('nis', $newNis)
                        ->update(['akademik_master_kelas_id' => $targetKelas->id]);
                }
            }

            return $updated;
        });
    }

    /**
     * Menghapus anggota kelas dan membersihkan kelas aktif siswa jika tahun ajaran masih aktif.
     */
    public function delete(AnggotaKelas $anggotaKelas): bool
    {
        return DB::transaction(function () use ($anggotaKelas): bool {
            $anggotaKelas->loadMissing([
                'kelas:id,akademik_master_tahun_ajaran_id',
                'kelas.tahunAjaran:id,is_active',
            ]);

            $siswaNis = $anggotaKelas->siswa_nis;
            $kelasId = $anggotaKelas->akademik_master_kelas_id;
            $wasActiveYear = (bool) $anggotaKelas->kelas?->tahunAjaran?->is_active;

            $deleted = (bool) $anggotaKelas->delete();

            if ($deleted && $wasActiveYear) {
                Siswa::query()
                    ->where('nis', $siswaNis)
                    ->where('akademik_master_kelas_id', $kelasId)
                    ->update(['akademik_master_kelas_id' => null]);
            }

            return $deleted;
        });
    }

    /**
     * Mengambil daftar kelas beserta relasi tahun ajaran tanpa N+1.
     *
     * @return Collection<int, Kelas>
     */
    private function getKelasOptions(): Collection
    {
        return Kelas::query()
            ->select([
                'id',
                'akademik_master_tahun_ajaran_id',
                'nama_kelas',
                'jenjang_kelas',
                'tingkatan_kelas',
            ])
            ->with(['tahunAjaran:id,nama_tahun_ajaran,semester,is_active'])
            ->orderByDesc('akademik_master_tahun_ajaran_id')
            ->orderBy('jenjang_kelas')
            ->orderBy('nama_kelas')
            ->get();
    }

    /**
     * Memastikan tidak ada konflik keanggotaan kelas di tahun ajaran yang sama (race-safe).
     *
     * @param  list<string>  $nisList
     *
     * @throws ValidationException
     */
    private function assertNoTahunAjaranConflict(
        array $nisList,
        int $tahunAjaranId,
        ?int $ignoreAnggotaKelasId = null
    ): void {
        $conflicts = $this->findConflictingNisInTahunAjaran(
            $nisList,
            $tahunAjaranId,
            $ignoreAnggotaKelasId,
            lockForUpdate: true
        );

        if ($conflicts !== []) {
            throw ValidationException::withMessages([
                'siswa_nis' => 'Siswa dengan NIS '.implode(', ', $conflicts).' sudah terdaftar di kelas pada tahun ajaran dan semester yang dipilih.',
            ]);
        }
    }
}
