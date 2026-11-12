<?php

namespace App\Services\Admin\DataMaster;

use App\Models\Admin\DataMaster\AnggotaEkstrakurikuler;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Services\Admin\DataMaster\Concerns\ManagesTahunAjaranMembership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class EkstrakurikulerService
{
    use ManagesTahunAjaranMembership;

    /**
     * Membangun query daftar ekstrakurikuler beserta relasi dan jumlah anggota tanpa N+1.
     *
     * @return Builder<Ekstrakurikuler>
     */
    public function buildIndexQuery(): Builder
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
            ->withMasterRelations()
            ->withCount('anggotaEkstrakurikuler')
            ->orderBy('unit')
            ->orderBy('nama_ekstrakurikuler')
            ->orderBy('id');
    }

    /**
     * Mengambil semua data ekstrakurikuler beserta relasi tanpa N+1.
     *
     * @return Collection<int, Ekstrakurikuler>
     */
    public function getAll(): Collection
    {
        return $this->buildIndexQuery()->get();
    }

    /**
     * Mengambil detail ekstrakurikuler, daftar anggota, statistik gender, dan kandidat anggota kelas aktif.
     *
     * @return array{
     *     ekstrakurikuler: Ekstrakurikuler,
     *     anggotaEkstrakurikuler: Collection<int, AnggotaEkstrakurikuler>,
     *     stats: array{total: int, laki_laki: int, perempuan: int},
     *     kandidatAnggotaKelas: Collection<int, AnggotaKelas>,
     *     excludedTahunAjaranIds: list<int>,
     *     hiddenRegisteredCount: int,
     *     hiddenInactiveCount: int,
     *     hiddenWithoutKelasCount: int
     * }
     */
    public function getShowData(Ekstrakurikuler $ekstrakurikuler): array
    {
        $ekstrakurikuler->loadMissing([
            'tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
            'pembina:nipy,nama_lengkap,gelar',
        ]);

        $anggotaEkstrakurikuler = $ekstrakurikuler->anggotaEkstrakurikuler()
            ->select([
                'id',
                'anggota_kelas_id',
                'akademik_master_ekstrakuler_id',
                'pendaftaran',
                'keterangan',
            ])
            ->with([
                'anggotaKelas:id,siswa_nis,akademik_master_kelas_id,pendaftaran',
                'anggotaKelas.siswa:nis,nama_lengkap,nisn,jenis_kelamin,status',
                'anggotaKelas.kelas:id,akademik_master_tahun_ajaran_id,nama_kelas,jenjang_kelas,tingkatan_kelas',
            ])
            ->orderBy('anggota_kelas_id')
            ->orderBy('id')
            ->get();

        $candidatePayload = $this->anggotaService()->getCandidateAnggotaKelasForEkstrakurikuler($ekstrakurikuler);

        return [
            'ekstrakurikuler' => $ekstrakurikuler,
            'anggotaEkstrakurikuler' => $anggotaEkstrakurikuler,
            'stats' => $this->computeMemberGenderStatsFrom(
                $anggotaEkstrakurikuler,
                static fn ($item): ?Siswa => $item instanceof AnggotaEkstrakurikuler ? $item->anggotaKelas?->siswa : null
            ),
            'kandidatAnggotaKelas' => $candidatePayload['anggotaKelas'],
            'excludedTahunAjaranIds' => $candidatePayload['excludedTahunAjaranIds'],
            'hiddenRegisteredCount' => $candidatePayload['hiddenRegisteredCount'],
            'hiddenInactiveCount' => $candidatePayload['hiddenInactiveCount'],
            'hiddenWithoutKelasCount' => $candidatePayload['hiddenWithoutKelasCount'],
        ];
    }

    /**
     * Mengambil instance service anggota ekstrakurikuler.
     */
    private function anggotaService(): AnggotaEkstrakurikulerService
    {
        return app(AnggotaEkstrakurikulerService::class);
    }

    /**
     * Mengambil daftar opsi dropdown untuk form tambah dan edit ekstrakurikuler.
     *
     * @return array{
     *     tahunAjaran: Collection<int, TahunAjaran>,
     *     karyawan: Collection<int, Karyawan>
     * }
     */
    public function getFormOptions(): array
    {
        return [
            'tahunAjaran' => TahunAjaran::query()
                ->select(['id', 'nama_tahun_ajaran', 'semester', 'is_active'])
                ->orderByDesc('nama_tahun_ajaran')
                ->orderBy('semester')
                ->get(),
            'karyawan' => Karyawan::query()
                ->select(['nipy', 'nama_lengkap', 'gelar'])
                ->orderBy('nama_lengkap')
                ->get(),
        ];
    }

    /**
     * Menyimpan data ekstrakurikuler baru.
     *
     * @param  array<string, mixed>  $data
     */
    public function store(array $data): Ekstrakurikuler
    {
        return Ekstrakurikuler::create($data);
    }

    /**
     * Memperbarui data ekstrakurikuler.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Ekstrakurikuler $ekstrakurikuler, array $data): bool
    {
        return $ekstrakurikuler->update($data);
    }

    /**
     * Menghapus data ekstrakurikuler.
     */
    public function delete(Ekstrakurikuler $ekstrakurikuler): bool
    {
        return (bool) $ekstrakurikuler->delete();
    }
}
