<?php

namespace App\Services\Admin\DataPelengkap;

use App\Models\Admin\DataPelengkap\BerkebutuhanKhusus;
use Illuminate\Database\Eloquent\Collection;

class BerkebutuhanKhususService
{
    public function getAll(): Collection
    {
        return BerkebutuhanKhusus::orderBy('nama_berkebutuhan_khusus')->get();
    }

    public function store(array $data): BerkebutuhanKhusus  
    {
        return BerkebutuhanKhusus::create($data);
    }

    public function update(BerkebutuhanKhusus $berkebutuhanKhusus, array $data): bool
    {
        return $berkebutuhanKhusus->update($data);
    }

    public function delete(BerkebutuhanKhusus $berkebutuhanKhusus): bool
    {
        return $berkebutuhanKhusus->delete();
    }
}
