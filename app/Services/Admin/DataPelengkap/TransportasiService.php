<?php

namespace App\Services\Admin\DataPelengkap;

use App\Models\Admin\DataPelengkap\Transportasi;
use Illuminate\Database\Eloquent\Collection;

class TransportasiService
{
    public function getAll(): Collection
    {
        return Transportasi::orderBy('nama_transportasi')->get();
    }

    public function store(array $data): Transportasi
    {
        return Transportasi::create($data);
    }

    public function update(Transportasi $transportasi, array $data): bool
    {
        return $transportasi->update($data);
    }

    public function delete(Transportasi $transportasi): bool
    {
        return $transportasi->delete();
    }
}
