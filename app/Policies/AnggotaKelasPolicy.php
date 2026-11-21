<?php

namespace App\Policies;

use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\User;
use App\Services\WaliKelas\WaliKelasScopeService;
use Illuminate\Auth\Access\Response;

class AnggotaKelasPolicy extends BasePolicy
{
    public function viewAny(User $user): Response
    {
        return $this->allowIfGuruPengajar($user);
    }

    public function view(User $user, AnggotaKelas $anggotaKelas): Response
    {
        if ($this->isWaliKelas($user)) {
            return $this->checkScope(
                fn (WaliKelasScopeService $scope) => $scope->authorizeAnggotaKelasForUser($user, $anggotaKelas)
            );
        }

        if ($this->isGuruMapelSd($user)) {
            return $this->checkScope(
                fn (WaliKelasScopeService $scope) => $scope->authorizeTaughtSdKelasIdForUser(
                    $user,
                    (int) $anggotaKelas->akademik_master_kelas_id
                )
            );
        }

        return Response::deny('Akses ditolak.');
    }

    public function create(User $user): Response
    {
        return Response::deny('Penambahan anggota kelas khusus Super Admin.');
    }

    public function update(User $user, AnggotaKelas $anggotaKelas): Response
    {
        return Response::deny('Perubahan anggota kelas khusus Super Admin.');
    }

    public function delete(User $user, AnggotaKelas $anggotaKelas): Response
    {
        return Response::deny('Penghapusan anggota kelas khusus Super Admin.');
    }
}
