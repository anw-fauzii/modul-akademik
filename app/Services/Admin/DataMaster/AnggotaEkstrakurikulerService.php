<?php

namespace App\Services\Admin\DataMaster;

use App\Models\Admin\DataMaster\AnggotaEkstrakurikuler;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Services\Admin\DataMaster\Concerns\ManagesTahunAjaranMembership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AnggotaEkstrakurikulerService
{
    use ManagesTahunAjaranMembership;

    private const string GROUP_RELATION = 'ekstrakurikuler';

    private const string GROUP_FOREIGN_KEY = 'akademik_master_ekstrakuler_id';

    private const string NESTED_SISWA_MEMBERSHIP_RELATION = 'anggotaEkstrakurikuler.ekstrakurikuler';

    private const string ENTITY_LABEL = 'ekstrakurikuler';

    /**
     * Membangun query daftar anggota ekstrakurikuler beserta relasi kelas dan siswa tanpa N+1.
     *
     * @return Builder<AnggotaEkstrakurikuler>
     */
    public function buildIndexQuery(
        ?int $tahunAjaranId = null,
        ?int $ekstrakurikulerId = null,
        ?int $kelasId = null
    ): Builder {
        return AnggotaEkstrakurikuler::query()
            ->select([
                'id',
                'anggota_kelas_id',
                self::GROUP_FOREIGN_KEY,
                'pendaftaran',
                'keterangan',
            ])
            ->withMasterRelations()
            ->when($tahunAjaranId !== null, fn (Builder $query) => $query->forTahunAjaran($tahunAjaranId))
            ->when($ekstrakurikulerId !== null, fn (Builder $query) => $query->forEkstrakurikuler($ekstrakurikulerId))
            ->when($kelasId !== null, fn (Builder $query) => $query->forKelas($kelasId))
            ->orderByDesc(self::GROUP_FOREIGN_KEY)
            ->orderBy('anggota_kelas_id')
            ->orderBy('id');
    }

    /**
     * Mengambil semua data anggota ekstrakurikuler dengan filter opsional tanpa N+1.
     *
     * @return Collection<int, AnggotaEkstrakurikuler>
     */
    public function getAll(
        ?int $tahunAjaranId = null,
        ?int $ekstrakurikulerId = null,
        ?int $kelasId = null
    ): Collection {
        return $this->buildIndexQuery($tahunAjaranId, $ekstrakurikulerId, $kelasId)->get();
    }

    /**
     * Mengambil daftar opsi filter untuk halaman indeks anggota ekstrakurikuler.
     *
     * @return array{
     *     tahunAjaran: Collection<int, TahunAjaran>,
     *     ekstrakurikuler: Collection<int, Ekstrakurikuler>,
     *     kelas: Collection<int, Kelas>
     * }
     */
    public function getFilterOptions(): array
    {
        return [
            ...$this->buildSharedMembershipFilterOptions(),
            'ekstrakurikuler' => $this->getEkstrakurikulerOptions(),
        ];
    }

    /**
     * Mengambil daftar opsi dropdown untuk form edit anggota ekstrakurikuler tanpa N+1.
     *
     * @return array{
     *     ekstrakurikuler: Collection<int, Ekstrakurikuler>,
     *     anggotaKelas: Collection<int, AnggotaKelas>
     * }
     */
    public function getFormOptions(?AnggotaEkstrakurikuler $anggotaEkstrakurikuler = null): array
    {
        $tahunAjaranId = $anggotaEkstrakurikuler?->ekstrakurikuler?->akademik_master_tahun_ajaran_id;

        $anggotaKelas = $this->baseAnggotaKelasFormQuery()
            ->when(
                $tahunAjaranId !== null,
                fn (Builder $query) => $query->whereHas(
                    'kelas',
                    fn (Builder $kelasQuery) => $kelasQuery->where('akademik_master_tahun_ajaran_id', (int) $tahunAjaranId)
                )
            )
            ->get();

        return [
            'ekstrakurikuler' => $this->getEkstrakurikulerOptions(),
            'anggotaKelas' => $anggotaKelas,
        ];
    }

    /**
     * Mengambil opsi form tambah anggota ekstrakurikuler berdasarkan daftar Anggota Kelas aktif
     * pada tahun ajaran tujuan yang belum terdaftar ekstrakurikuler di tahun ajaran tersebut.
     *
     * @return array{
     *     ekstrakurikuler: Collection<int, Ekstrakurikuler>,
     *     anggotaKelas: Collection<int, AnggotaKelas>,
     *     excludedTahunAjaranIds: list<int>,
     *     hiddenRegisteredCount: int,
     *     hiddenInactiveCount: int,
     *     hiddenWithoutKelasCount: int
     * }
     */
    public function getCreateFormOptions(?int $targetEkstrakurikulerId = null): array
    {
        $ekstrakurikuler = $this->getEkstrakurikulerOptions();
        $targetTahunAjaranIds = $this->resolveExcludedTahunAjaranIds($ekstrakurikuler, $targetEkstrakurikulerId);

        return [
            'ekstrakurikuler' => $ekstrakurikuler,
            ...$this->buildCandidateAnggotaKelasPayloadFor(
                $targetTahunAjaranIds,
                $this->buildCreateAnggotaKelasQuery($targetTahunAjaranIds),
                self::NESTED_SISWA_MEMBERSHIP_RELATION
            ),
        ];
    }

    /**
     * Mengambil kandidat Anggota Kelas aktif beserta ringkasan yang disembunyikan
     * untuk halaman detail ekstrakurikuler.
     *
     * @return array{
     *     anggotaKelas: Collection<int, AnggotaKelas>,
     *     excludedTahunAjaranIds: list<int>,
     *     hiddenRegisteredCount: int,
     *     hiddenInactiveCount: int,
     *     hiddenWithoutKelasCount: int
     * }
     */
    public function getCandidateAnggotaKelasForEkstrakurikuler(Ekstrakurikuler $ekstrakurikuler): array
    {
        $targetTahunAjaranIds = $ekstrakurikuler->akademik_master_tahun_ajaran_id !== null
            ? [(int) $ekstrakurikuler->akademik_master_tahun_ajaran_id]
            : [];

        return $this->buildCandidateAnggotaKelasPayloadFor(
            $targetTahunAjaranIds,
            $this->buildCreateAnggotaKelasQuery($targetTahunAjaranIds),
            self::NESTED_SISWA_MEMBERSHIP_RELATION
        );
    }

    /**
     * Membangun query kandidat Anggota Kelas pada form tambah anggota ekstrakurikuler.
     *
     * @param  list<int>  $targetTahunAjaranIds
     * @return Builder<AnggotaKelas>
     */
    public function buildCreateAnggotaKelasQuery(array $targetTahunAjaranIds = []): Builder
    {
        return $this->applyCandidateAnggotaKelasFilters(
            $this->baseAnggotaKelasFormQuery(),
            $targetTahunAjaranIds,
            self::NESTED_SISWA_MEMBERSHIP_RELATION
        );
    }

    /**
     * Mencari ID anggota kelas yang tahun ajaran kelasnya tidak sama dengan tahun ajaran ekstrakurikuler tujuan.
     *
     * @param  list<int>  $anggotaKelasIds
     * @return list<int>
     */
    public function findMismatchedTahunAjaranAnggotaKelas(array $anggotaKelasIds, int $tahunAjaranId): array
    {
        return $this->findMismatchedTahunAjaranAnggotaKelasIds($anggotaKelasIds, $tahunAjaranId);
    }

    /**
     * Mencari daftar NIS siswa dari anggota kelas terpilih yang sudah terdaftar di ekstrakurikuler
     * pada tahun ajaran/semester yang sama.
     *
     * @param  list<int>  $anggotaKelasIds
     * @return list<string>
     */
    public function findConflictingAnggotaKelasInTahunAjaran(
        array $anggotaKelasIds,
        int $tahunAjaranId,
        ?int $ignoreAnggotaEkstrakurikulerId = null,
        bool $lockForUpdate = false
    ): array {
        return $this->findConflictingMemberNisInTahunAjaran(
            AnggotaEkstrakurikuler::class,
            $anggotaKelasIds,
            $tahunAjaranId,
            $ignoreAnggotaEkstrakurikulerId,
            $lockForUpdate
        );
    }

    /**
     * Menyimpan satu atau beberapa anggota ekstrakurikuler secara atomik dalam satu transaksi.
     *
     * @param  array<string, mixed>  $data
     * @return Collection<int, AnggotaEkstrakurikuler>
     */
    public function store(array $data): Collection
    {
        return $this->storeAnggotaKelasMemberships(
            $data,
            AnggotaEkstrakurikuler::class,
            Ekstrakurikuler::class,
            self::GROUP_FOREIGN_KEY,
            self::ENTITY_LABEL
        );
    }

    /**
     * Memperbarui data anggota ekstrakurikuler secara atomik dan menyinkronkan ekstrakurikuler aktif siswa.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(AnggotaEkstrakurikuler $anggotaEkstrakurikuler, array $data): bool
    {
        return $this->updateAnggotaKelasMembership(
            $anggotaEkstrakurikuler,
            $data,
            AnggotaEkstrakurikuler::class,
            Ekstrakurikuler::class,
            self::GROUP_RELATION,
            self::GROUP_FOREIGN_KEY,
            self::ENTITY_LABEL
        );
    }

    /**
     * Menghapus anggota ekstrakurikuler dan membersihkan ekstrakurikuler aktif siswa jika tahun ajaran aktif.
     */
    public function delete(AnggotaEkstrakurikuler $anggotaEkstrakurikuler): bool
    {
        return $this->deleteAnggotaKelasMembership(
            $anggotaEkstrakurikuler,
            self::GROUP_RELATION,
            self::GROUP_FOREIGN_KEY
        );
    }

    /**
     * Query dasar daftar Anggota Kelas beserta relasi siswa, kelas, dan riwayat ekstrakurikuler.
     *
     * @return Builder<AnggotaKelas>
     */
    private function baseAnggotaKelasFormQuery(): Builder
    {
        return AnggotaKelas::query()
            ->select([
                'id',
                'siswa_nis',
                'akademik_master_kelas_id',
                'pendaftaran',
            ])
            ->with([
                'siswa:nis,akademik_master_ekstrakuler_id,nama_lengkap,jenis_kelamin,nisn,status',
                'siswa.ekstrakurikuler:id,akademik_master_tahun_ajaran_id,nama_ekstrakurikuler,unit',
                'siswa.ekstrakurikuler.tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
                'siswa.anggotaEkstrakurikuler:akademik_master_anggota_ekstrakuler.id,akademik_master_anggota_ekstrakuler.anggota_kelas_id,akademik_master_anggota_ekstrakuler.akademik_master_ekstrakuler_id,akademik_master_anggota_ekstrakuler.pendaftaran',
                'siswa.anggotaEkstrakurikuler.ekstrakurikuler:id,akademik_master_tahun_ajaran_id,nama_ekstrakurikuler,unit',
                'siswa.anggotaEkstrakurikuler.ekstrakurikuler.tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
                'kelas:id,akademik_master_tahun_ajaran_id,nama_kelas,jenjang_kelas,tingkatan_kelas',
                'kelas.tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
            ])
            ->orderBy('akademik_master_kelas_id')
            ->orderBy('siswa_nis')
            ->orderBy('id');
    }

    /**
     * Mengambil daftar ekstrakurikuler beserta relasi tahun ajaran dan pembina tanpa N+1.
     *
     * @return Collection<int, Ekstrakurikuler>
     */
    private function getEkstrakurikulerOptions(): Collection
    {
        return Ekstrakurikuler::query()
            ->select([
                'id',
                'akademik_master_tahun_ajaran_id',
                'karyawan_nipy',
                'nama_ekstrakurikuler',
                'biaya',
                'unit',
            ])
            ->with([
                'tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
                'pembina:nipy,nama_lengkap,gelar',
            ])
            ->orderByDesc('akademik_master_tahun_ajaran_id')
            ->orderBy('unit')
            ->orderBy('nama_ekstrakurikuler')
            ->get();
    }
}
