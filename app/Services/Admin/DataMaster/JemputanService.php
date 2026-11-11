<?php

namespace App\Services\Admin\DataMaster;

use App\Models\Admin\DataMaster\Jemputan;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\TahunAjaran;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class JemputanService
{
    /**
     * Membangun query daftar jemputan beserta relasi utama tanpa N+1.
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
