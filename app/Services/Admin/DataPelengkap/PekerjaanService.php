<?php

namespace App\Services\Admin\DataPelengkap;

use App\Models\Admin\DataPelengkap\Pekerjaan;
use Illuminate\Database\Eloquent\Collection;

class PekerjaanService
{
    public function getAll(): Collection
    {
        return Pekerjaan::orderBy('nama_pekerjaan')->get();
    }

    public function store(array $data): Pekerjaan
    {
        return Pekerjaan::create($data);
    }

    public function update(Pekerjaan $pekerjaan, array $data): bool
    {
        return $pekerjaan->update($data);
    }

    public function delete(Pekerjaan $pekerjaan): bool
    {
        return $pekerjaan->delete();
    }
}