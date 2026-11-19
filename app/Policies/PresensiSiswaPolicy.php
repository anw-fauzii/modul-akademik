<?php

namespace App\Policies;

use App\Models\Admin\Presensi\PresensiSiswa;
use App\Models\User;
use App\Services\WaliKelas\WaliKelasScopeService;
use Illuminate\Auth\Access\Response;

class PresensiSiswaPolicy extends BasePolicy
{
    public function viewAny(User $user): Response
    {
        return $this->allowIfWaliKelas($user);
    }

    public function view(User $user, PresensiSiswa $presensiSiswa): Response
    {
        if (! $this->isWaliKelas($user)) {
            return Response::deny('Akses ditolak.');
        }

        return $this->checkScope(
            fn (WaliKelasScopeService $scope) => $scope->authorizePresensiSiswaForUser($user, $presensiSiswa)
        );
    }

    public function create(User $user, ?int $kelasId = null): Response
    {
        if (! $this->isWaliKelas($user)) {
            return Response::deny('Akses ditolak.');
        }

        if ($kelasId !== null && $kelasId > 0) {
            return $this->checkScope(
                fn (WaliKelasScopeService $scope) => $scope->authorizeKelasIdForUser(
                    $user,
                    $kelasId,
                    'Anda tidak memiliki akses untuk mengelola presensi kelas ini pada Tahun Ajaran aktif.'
                )
            );
        }

        return Response::allow();
    }

    public function update(User $user, PresensiSiswa $presensiSiswa): Response
    {
        return $this->view($user, $presensiSiswa);
    }

    public function delete(User $user, PresensiSiswa $presensiSiswa): Response
    {
        return $this->view($user, $presensiSiswa);
    }

    public function bulkDestroy(User $user, ?int $kelasId = null): Response
    {
        return $this->create($user, $kelasId);
    }
}
