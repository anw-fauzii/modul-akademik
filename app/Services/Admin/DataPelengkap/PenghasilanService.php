<?php

namespace App\Services\Admin\DataPelengkap;

use App\Models\Admin\DataPelengkap\Penghasilan;
use Illuminate\Database\Eloquent\Collection;

class PenghasilanService
{
    public function getAll(): Collection
    {
        return Penghasilan::orderBy('nama_penghasilan')->get();
    }

    public function store(array $data): Penghasilan
    {
        return Penghasilan::create($data);
    }

    public function update(Penghasilan $penghasilan, array $data): bool
    {
        return $penghasilan->update($data);
    }

    public function delete(Penghasilan $penghasilan): bool
    {
        return $penghasilan->delete();
    }
}
