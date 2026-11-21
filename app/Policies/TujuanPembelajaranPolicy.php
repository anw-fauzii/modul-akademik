<?php

namespace App\Policies;

use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TujuanPembelajaranPolicy extends BasePolicy
{
    public function viewAny(User $user): Response
    {
        return Response::deny('Akses daftar master Tujuan Pembelajaran khusus Super Admin.');
    }

    public function view(User $user, TujuanPembelajaran $tujuanPembelajaran): Response
    {
        return Response::deny('Akses detail Tujuan Pembelajaran khusus Super Admin.');
    }

    public function create(User $user): Response
    {
        return Response::deny('Penambahan Tujuan Pembelajaran khusus Super Admin.');
    }

    public function update(User $user, TujuanPembelajaran $tujuanPembelajaran): Response
    {
        return Response::deny('Perubahan Tujuan Pembelajaran khusus Super Admin.');
    }

    public function delete(User $user, TujuanPembelajaran $tujuanPembelajaran): Response
    {
        return Response::deny('Penghapusan Tujuan Pembelajaran khusus Super Admin.');
    }

    public function toggleStatus(User $user, TujuanPembelajaran $tujuanPembelajaran): Response
    {
        return Response::deny('Perubahan status Tujuan Pembelajaran khusus Super Admin.');
    }
}
