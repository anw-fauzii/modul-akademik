<?php

namespace App\Policies;

use App\Models\Admin\Penilaian\RekapMapel;
use App\Models\User;
use App\Services\WaliKelas\WaliKelasScopeService;
use Illuminate\Auth\Access\Response;

class RekapMapelPolicy extends BasePolicy
{
    public function viewAny(User $user): Response
    {
        return $this->allowIfGuruPengajar($user);
    }

    public function updateDeskripsi(User $user, RekapMapel $rekapMapel, ?int $kelasId = null): Response
    {
        if (! $this->isGuruPengajar($user)) {
            return Response::deny('Akses ditolak.');
        }

        return $this->checkScope(function (WaliKelasScopeService $scope) use ($user, $rekapMapel, $kelasId): void {
            $authorized = $scope->authorizeRekapMapelForUser($user, $rekapMapel);

            if ($kelasId !== null && $kelasId > 0 && $kelasId !== (int) $authorized['selectedKelas']->id) {
                abort(403, 'Data rekap rapor tidak sesuai dengan kelas yang dipilih.');
            }
        });
    }

    public function regenerateDeskripsi(User $user, ?int $kelasId = null, ?int $mapelId = null): Response
    {
        if (! $this->isGuruPengajar($user)) {
            return Response::deny('Akses ditolak.');
        }

        return $this->checkScope(
            fn (WaliKelasScopeService $scope) => $scope->authorizeSdMapelScope($user, $kelasId, $mapelId)
        );
    }
}
