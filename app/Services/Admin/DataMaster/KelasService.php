<?php

namespace App\Services\Admin\DataMaster;

use App\Models\Admin\DataMaster\Kelas;
use Illuminate\Database\Eloquent\Collection;

class KelasService
{
    /**
     * Mengambil semua data kelas beserta relasi tahun ajaran dan guru.
     */
    public function getAll(): Collection
    {
        return Kelas::with(['tahunAjaran', 'waliKelas', 'pendamping'])
            ->orderBy('jenjang_kelas')
            ->orderBy('tingkatan_kelas')
            ->orderBy('nama_kelas')
            ->get();
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
