<?php

namespace App\Services\Admin\DataMaster;

use App\Models\Admin\DataMaster\AnggotaJemputan;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Jemputan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Services\Admin\DataMaster\Concerns\ManagesTahunAjaranMembership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AnggotaJemputanService
{
    use ManagesTahunAjaranMembership;

    private const string GROUP_RELATION = 'jemputan';

    private const string GROUP_FOREIGN_KEY = 'akademik_master_jemputan_id';

    private const string NESTED_SISWA_MEMBERSHIP_RELATION = 'anggotaJemputan.jemputan';

    private const string ENTITY_LABEL = 'jemputan';

    /**
     * Membangun query daftar anggota jemputan beserta relasi kelas, siswa, dan petugas tanpa N+1.
     *
     * @return Builder<AnggotaJemputan>
     */
    public function buildIndexQuery(
        ?int $tahunAjaranId = null,
        ?int $jemputanId = null,
        ?int $kelasId = null
    ): Builder {
        return AnggotaJemputan::query()
            ->select([
                'id',
                'anggota_kelas_id',
                self::GROUP_FOREIGN_KEY,
                'pendaftaran',
                'keterangan',
            ])
            ->withMasterRelations()
            ->when($tahunAjaranId !== null, fn (Builder $query) => $query->forTahunAjaran($tahunAjaranId))
            ->when($jemputanId !== null, fn (Builder $query) => $query->forJemputan($jemputanId))
            ->when($kelasId !== null, fn (Builder $query) => $query->forKelas($kelasId))
            ->orderByDesc(self::GROUP_FOREIGN_KEY)
            ->orderBy('anggota_kelas_id')
            ->orderBy('id');
    }

    /**
     * Mengambil semua data anggota jemputan dengan filter opsional tanpa N+1.
     *
     * @return Collection<int, AnggotaJemputan>
     */
    public function getAll(
        ?int $tahunAjaranId = null,
        ?int $jemputanId = null,
        ?int $kelasId = null
    ): Collection {
        return $this->buildIndexQuery($tahunAjaranId, $jemputanId, $kelasId)->get();
    }

    /**
     * Mengambil daftar opsi filter untuk halaman indeks anggota jemputan.
     *
     * @return array{
     *     tahunAjaran: Collection<int, TahunAjaran>,
     *     jemputan: Collection<int, Jemputan>,
     *     kelas: Collection<int, Kelas>
     * }
     */
    public function getFilterOptions(): array
    {
        return [
            ...$this->buildSharedMembershipFilterOptions(),
            'jemputan' => $this->getJemputanOptions(),
        ];
    }

    /**
     * Mengambil daftar opsi dropdown untuk form edit anggota jemputan tanpa N+1.
     *
     * @return array{
     *     jemputan: Collection<int, Jemputan>,
     *     anggotaKelas: Collection<int, AnggotaKelas>
     * }
     */
    public function getFormOptions(?AnggotaJemputan $anggotaJemputan = null): array
    {
        $tahunAjaranId = $anggotaJemputan?->jemputan?->akademik_master_tahun_ajaran_id;

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
            'jemputan' => $this->getJemputanOptions(),
            'anggotaKelas' => $anggotaKelas,
        ];
    }

    /**
     * Mengambil opsi form tambah anggota jemputan berdasarkan daftar Anggota Kelas aktif
     * pada tahun ajaran tujuan yang belum terdaftar jemputan di tahun ajaran tersebut.
     *
     * @return array{
     *     jemputan: Collection<int, Jemputan>,
     *     anggotaKelas: Collection<int, AnggotaKelas>,
     *     excludedTahunAjaranIds: list<int>,
     *     hiddenRegisteredCount: int,
     *     hiddenInactiveCount: int,
     *     hiddenWithoutKelasCount: int
     * }
     */
    public function getCreateFormOptions(?int $targetJemputanId = null): array
    {
        $jemputan = $this->getJemputanOptions();
        $targetTahunAjaranIds = $this->resolveExcludedTahunAjaranIds($jemputan, $targetJemputanId);

        return [
            'jemputan' => $jemputan,
            ...$this->buildCandidateAnggotaKelasPayloadFor(
                $targetTahunAjaranIds,
                $this->buildCreateAnggotaKelasQuery($targetTahunAjaranIds),
                self::NESTED_SISWA_MEMBERSHIP_RELATION
            ),
        ];
    }

    /**
     * Mengambil kandidat Anggota Kelas aktif beserta ringkasan yang disembunyikan
     * untuk halaman detail jemputan.
     *
     * @return array{
     *     anggotaKelas: Collection<int, AnggotaKelas>,
     *     excludedTahunAjaranIds: list<int>,
     *     hiddenRegisteredCount: int,
     *     hiddenInactiveCount: int,
     *     hiddenWithoutKelasCount: int
     * }
     */
    public function getCandidateAnggotaKelasForJemputan(Jemputan $jemputan): array
    {
        $targetTahunAjaranIds = $jemputan->akademik_master_tahun_ajaran_id !== null
            ? [(int) $jemputan->akademik_master_tahun_ajaran_id]
            : [];

        return $this->buildCandidateAnggotaKelasPayloadFor(
            $targetTahunAjaranIds,
            $this->buildCreateAnggotaKelasQuery($targetTahunAjaranIds),
            self::NESTED_SISWA_MEMBERSHIP_RELATION
        );
    }

    /**
     * Membangun query kandidat Anggota Kelas pada form tambah anggota jemputan.
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
     * Mencari ID anggota kelas yang tahun ajaran kelasnya tidak sama dengan tahun ajaran jemputan tujuan.
     *
     * @param  list<int>  $anggotaKelasIds
     * @return list<int>
     */
    public function findMismatchedTahunAjaranAnggotaKelas(array $anggotaKelasIds, int $tahunAjaranId): array
    {
        return $this->findMismatchedTahunAjaranAnggotaKelasIds($anggotaKelasIds, $tahunAjaranId);
    }

    /**
     * Mencari daftar NIS siswa dari anggota kelas terpilih yang sudah terdaftar di jemputan
     * pada tahun ajaran/semester yang sama.
     *
     * @param  list<int>  $anggotaKelasIds
     * @return list<string>
     */
    public function findConflictingAnggotaKelasInTahunAjaran(
        array $anggotaKelasIds,
        int $tahunAjaranId,
        ?int $ignoreAnggotaJemputanId = null,
        bool $lockForUpdate = false
    ): array {
        return $this->findConflictingMemberNisInTahunAjaran(
            AnggotaJemputan::class,
            $anggotaKelasIds,
            $tahunAjaranId,
            $ignoreAnggotaJemputanId,
            $lockForUpdate
        );
    }

    /**
     * Menyimpan satu atau beberapa anggota jemputan secara atomik dalam satu transaksi.
     *
     * @param  array<string, mixed>  $data
     * @return Collection<int, AnggotaJemputan>
     */
    public function store(array $data): Collection
    {
        return $this->storeAnggotaKelasMemberships(
            $data,
            AnggotaJemputan::class,
            Jemputan::class,
            self::GROUP_FOREIGN_KEY,
            self::ENTITY_LABEL
        );
    }

    /**
     * Memperbarui data anggota jemputan secara atomik dan menyinkronkan jemputan aktif siswa.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(AnggotaJemputan $anggotaJemputan, array $data): bool
    {
        return $this->updateAnggotaKelasMembership(
            $anggotaJemputan,
            $data,
            AnggotaJemputan::class,
            Jemputan::class,
            self::GROUP_RELATION,
            self::GROUP_FOREIGN_KEY,
            self::ENTITY_LABEL
        );
    }

    /**
     * Menghapus anggota jemputan dan membersihkan jemputan aktif siswa jika tahun ajaran aktif.
     */
    public function delete(AnggotaJemputan $anggotaJemputan): bool
    {
        return $this->deleteAnggotaKelasMembership(
            $anggotaJemputan,
            self::GROUP_RELATION,
            self::GROUP_FOREIGN_KEY
        );
    }

    /**
     * Query dasar daftar Anggota Kelas beserta relasi siswa, kelas, dan riwayat jemputan.
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
                'siswa:nis,akademik_master_jemputan_id,nama_lengkap,jenis_kelamin,nisn,status',
                'siswa.jemputan:id,akademik_master_tahun_ajaran_id,karyawan_nipy,harga_pp,harga_setengah',
                'siswa.jemputan.petugas:nipy,nama_lengkap,gelar',
                'siswa.jemputan.tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
                'siswa.anggotaJemputan:akademik_master_anggota_jemputan.id,akademik_master_anggota_jemputan.anggota_kelas_id,akademik_master_anggota_jemputan.akademik_master_jemputan_id,akademik_master_anggota_jemputan.pendaftaran',
                'siswa.anggotaJemputan.jemputan:id,akademik_master_tahun_ajaran_id,karyawan_nipy,harga_pp,harga_setengah',
                'siswa.anggotaJemputan.jemputan.petugas:nipy,nama_lengkap,gelar',
                'siswa.anggotaJemputan.jemputan.tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
                'kelas:id,akademik_master_tahun_ajaran_id,nama_kelas,jenjang_kelas,tingkatan_kelas',
                'kelas.tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
            ])
            ->orderBy('akademik_master_kelas_id')
            ->orderBy('siswa_nis')
            ->orderBy('id');
    }

    /**
     * Mengambil daftar layanan jemputan beserta relasi tahun ajaran dan petugas tanpa N+1.
     *
     * @return Collection<int, Jemputan>
     */
    private function getJemputanOptions(): Collection
    {
        return Jemputan::query()
            ->select([
                'id',
                'akademik_master_tahun_ajaran_id',
                'karyawan_nipy',
                'harga_pp',
                'harga_setengah',
            ])
            ->with([
                'tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
                'petugas:nipy,nama_lengkap,gelar',
            ])
            ->orderByDesc('akademik_master_tahun_ajaran_id')
            ->orderBy('karyawan_nipy')
            ->orderBy('id')
            ->get();
    }
}

