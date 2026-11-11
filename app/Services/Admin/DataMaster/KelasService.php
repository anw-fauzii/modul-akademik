<?php

namespace App\Services\Admin\DataMaster;

use App\Models\Admin\DataMaster\Kelas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class KelasService
{
    /**
     * Membangun query daftar kelas beserta relasi utama dan jumlah siswa tanpa N+1.
     *
     * @return Builder<Kelas>
     */
    public function buildIndexQuery(): Builder
    {
        return Kelas::query()
            ->select([
                'id',
                'akademik_master_tahun_ajaran_id',
                'karyawan_nipy',
                'pendamping_nipy',
                'tingkatan_kelas',
                'nama_kelas',
                'jenjang_kelas',
                'romawi',
            ])
            ->with([
                'tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
                'waliKelas:nipy,nama_lengkap,gelar',
                'pendamping:nipy,nama_lengkap,gelar',
            ])
            ->withCount('anggotaKelas')
            ->orderBy('jenjang_kelas')
            ->orderBy('tingkatan_kelas')
            ->orderBy('nama_kelas');
    }

    /**
     * Mengambil semua data kelas beserta relasi tahun ajaran, guru, dan jumlah siswa.
     *
     * @return Collection<int, Kelas>
     */
    public function getAll(): Collection
    {
        return $this->buildIndexQuery()->get();
    }

    /**
     * Menyimpan data kelas baru.
     */
    public function store(array $data): Kelas
    {
        return Kelas::create($data);
    }

    /**
     * Memperbarui data kelas.
     */
    public function update(Kelas $kelas, array $data): bool
    {
        return $kelas->update($data);
    }

    /**
     * Menghapus data kelas.
     */
    public function delete(Kelas $kelas): bool
    {
        return $kelas->delete();
    }
}
