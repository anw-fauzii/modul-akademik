<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

/**
 * Policy Yaumiyah T2Q (Tahsin & Tahfizh):
 * - Super Admin memiliki akses penuh (create/delete/matriks/statistik).
 * - Wali Kelas hanya diizinkan melihat Matriks & Statistik kelas binaan (read-only).
 */
class YaumiyahT2qPolicy extends BasePolicy
{
    public function viewAny(User $user): Response
    {
        return Response::deny('Akses input Yaumiyah T2Q khusus Super Admin.');
    }

    public function viewMatriks(User $user): Response
    {
        return $this->allowIfWaliKelas($user);
    }

    public function viewStatistik(User $user): Response
    {
        return $this->allowIfWaliKelas($user);
    }

    public function create(User $user): Response
    {
        return Response::deny('Penyimpanan setoran Yaumiyah T2Q khusus Super Admin.');
    }

    public function delete(User $user, Model $yaumiyah): Response
    {
        return Response::deny('Penghapusan catatan Yaumiyah T2Q khusus Super Admin.');
    }
}
