<?php

namespace App\Services\Admin\DataPelengkap;

use App\Models\Admin\DataPelengkap\JenjangPendidikan;
use Illuminate\Database\Eloquent\Collection;

class JenjangPendidikanService
{
    public function getAll(): Collection
    {
        return JenjangPendidikan::orderBy('nama_jenjang_pendidikan')->get();
    }

    public function store(array $data): JenjangPendidikan
    {
        return JenjangPendidikan::create($data);
    }

    public function update(JenjangPendidikan $jenjangPendidikan, array $data): bool
    {
        return $jenjangPendidikan->update($data);
    }

    public function delete(JenjangPendidikan $jenjangPendidikan): bool
    {
        return $jenjangPendidikan->delete();
    }
}
