<?php

namespace App\Services\Admin\DataMaster;

use App\Models\Admin\DataMaster\TahunAjaran;
use Illuminate\Database\Eloquent\Collection;

class TahunAjaranService
{
    public function getAll(): Collection
    {
        return TahunAjaran::orderBy('nama_tahun_ajaran')->orderBy('semester')->get();
    }

    public function store(array $data): TahunAjaran
    {
        return TahunAjaran::create($data);
    }

    public function update(TahunAjaran $tahunAjaran, array $data): bool
    {
        return $tahunAjaran->update($data);
    }

    public function delete(TahunAjaran $tahunAjaran): bool
    {
        return $tahunAjaran->delete();
    }

    public function toggleActive(TahunAjaran $tahunAjaran): bool
    {
        if (!$tahunAjaran->is_active) {
            TahunAjaran::query()->update(['is_active' => false]);
            return $tahunAjaran->update(['is_active' => true]);
        }
        return $tahunAjaran->update(['is_active' => false]);
    }
}
