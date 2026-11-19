<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

/**
 * Policy standar untuk resource yang hanya boleh diakses dan dikelola oleh Super Admin.
 */
class AdminOnlyPolicy extends BasePolicy
{
    public function viewAny(User $user): Response
    {
        return Response::deny('Akses ditolak. Halaman ini khusus Super Admin.');
    }

    public function view(User $user, Model $model): Response
    {
        return Response::deny('Akses ditolak. Data ini khusus Super Admin.');
    }

    public function create(User $user): Response
    {
        return Response::deny('Akses ditolak. Fitur ini khusus Super Admin.');
    }

    public function update(User $user, Model $model): Response
    {
        return Response::deny('Akses ditolak. Perubahan data ini khusus Super Admin.');
    }

    public function delete(User $user, Model $model): Response
    {
        return Response::deny('Akses ditolak. Penghapusan data ini khusus Super Admin.');
    }

    public function toggleStatus(User $user, Model $model): Response
    {
        return Response::deny('Akses ditolak. Perubahan status data ini khusus Super Admin.');
    }
}
