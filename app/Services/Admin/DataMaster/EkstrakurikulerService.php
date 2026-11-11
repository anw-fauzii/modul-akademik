<?php

namespace App\Services\Admin\DataMaster;

use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\TahunAjaran;
use Illuminate\Database\Eloquent\Collection;

class EkstrakurikulerService
{
    /**
     * Mengambil semua data ekstrakurikuler beserta relasi tanpa N+1.
     *
     * @return Collection<int, Ekstrakurikuler>
     */
    public function getAll(): Collection
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
            ->orderBy('unit')
            ->orderBy('nama_ekstrakurikuler')
            ->orderBy('id')
            ->get();
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
