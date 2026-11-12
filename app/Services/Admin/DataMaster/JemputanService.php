<?php

namespace App\Services\Admin\DataMaster;

use App\Models\Admin\DataMaster\AnggotaJemputan;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Jemputan;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Services\Admin\DataMaster\Concerns\ManagesTahunAjaranMembership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class JemputanService
{
    use ManagesTahunAjaranMembership;

    /**
     * Membangun query daftar jemputan beserta relasi dan jumlah anggota tanpa N+1.
     *
     * @return Builder<Jemputan>
     */
    public function buildIndexQuery(): Builder
    {
        return Jemputan::query()
            ->select([
                'id',
                'akademik_master_tahun_ajaran_id',
                'karyawan_nipy',
                'harga_pp',
                'harga_setengah',
            ])
            ->withMasterRelations()
            ->withCount('anggotaJemputan')
            ->orderByDesc('akademik_master_tahun_ajaran_id')
            ->orderBy('karyawan_nipy')
            ->orderBy('id');
    }

    /**
     * Mengambil semua data jemputan beserta relasi tahun ajaran dan petugas tanpa N+1.
     *
     * @return Collection<int, Jemputan>
     */
    public function getAll(): Collection
    {
        return $this->buildIndexQuery()->get();
    }

    /**
     * Mengambil detail jemputan, daftar anggota, statistik gender, dan kandidat anggota kelas aktif.
     *
     * @return array{
     *     jemputan: Jemputan,
     *     anggotaJemputan: Collection<int, AnggotaJemputan>,
     *     stats: array{total: int, laki_laki: int, perempuan: int},
     *     kandidatAnggotaKelas: Collection<int, AnggotaKelas>,
     *     excludedTahunAjaranIds: list<int>,
     *     hiddenRegisteredCount: int,
     *     hiddenInactiveCount: int,
     *     hiddenWithoutKelasCount: int
     * }
     */
    public function getShowData(Jemputan $jemputan): array
    {
        $jemputan->loadMissing([
            'tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
            'petugas:nipy,nama_lengkap,gelar',
        ]);

        $anggotaJemputan = $jemputan->anggotaJemputan()
            ->select([
                'id',
                'anggota_kelas_id',
                'akademik_master_jemputan_id',
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

        $candidatePayload = app(AnggotaJemputanService::class)->getCandidateAnggotaKelasForJemputan($jemputan);

        return [
            'jemputan' => $jemputan,
            'anggotaJemputan' => $anggotaJemputan,
            'stats' => $this->computeMemberGenderStatsFrom(
                $anggotaJemputan,
                static fn ($item): ?Siswa => $item instanceof AnggotaJemputan ? $item->anggotaKelas?->siswa : null
            ),
            'kandidatAnggotaKelas' => $candidatePayload['anggotaKelas'],
            'excludedTahunAjaranIds' => $candidatePayload['excludedTahunAjaranIds'],
            'hiddenRegisteredCount' => $candidatePayload['hiddenRegisteredCount'],
            'hiddenInactiveCount' => $candidatePayload['hiddenInactiveCount'],
            'hiddenWithoutKelasCount' => $candidatePayload['hiddenWithoutKelasCount'],
        ];
    }

    /**
     * Mengambil daftar opsi dropdown untuk form tambah dan edit jemputan.
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
     * Menyimpan data jemputan baru.
     *
     * @param  array<string, mixed>  $data
     */
    public function store(array $data): Jemputan
    {
        return Jemputan::create($data);
    }

    /**
     * Memperbarui data jemputan.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Jemputan $jemputan, array $data): bool
    {
        return $jemputan->update($data);
    }

    /**
     * Menghapus data jemputan.
     */
    public function delete(Jemputan $jemputan): bool
    {
        return (bool) $jemputan->delete();
    }
}
