<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy Rekap & Deskripsi Rapor T2Q:
 * - Super Admin memiliki akses penuh (via BasePolicy::before()).
 * - Guru T2Q dapat mengelola rekap & deskripsi rapor T2Q siswa bimbingannya (jenjang SD, sesuai NIPY).
 */
class RaporT2qPolicy extends BasePolicy
{
    public function viewAny(User $user): Response
    {
        return $this->isGuruT2q($user)
            ? Response::allow()
            : Response::deny('Akses Rekap & Deskripsi Rapor T2Q khusus Super Admin atau Guru T2Q.');
    }

    public function saveBatch(User $user): Response
    {
        return $this->isGuruT2q($user)
            ? Response::allow()
            : Response::deny('Penyimpanan Rekap & Deskripsi Rapor T2Q khusus Super Admin atau Guru T2Q.');
    }
}
