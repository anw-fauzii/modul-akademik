<?php

namespace App\Policies;

use App\Models\Admin\Penilaian\SumatifBab;
use App\Models\User;
use App\Services\WaliKelas\WaliKelasScopeService;
use Illuminate\Auth\Access\Response;

class SumatifBabPolicy extends BasePolicy
{
    public function create(User $user): Response
    {
        return $this->allowIfWaliKelas($user);
    }

    public function update(User $user, SumatifBab $sumatifBab, ?int $kelasId = null): Response
    {
        if (! $this->isWaliKelas($user)) {
            return Response::deny('Akses ditolak.');
        }

        return $this->checkScope(
            fn (WaliKelasScopeService $scope) => $scope->authorizeSdMapelScope(
                $user,
                $kelasId,
                (int) $sumatifBab->akademik_pelajaran_mata_pelajaran_id,
                (int) $sumatifBab->akademik_master_tahun_ajaran_id,
                (string) $sumatifBab->tingkatan_kelas
            )
        );
    }

    public function delete(User $user, SumatifBab $sumatifBab, ?int $kelasId = null): Response
    {
        return $this->update($user, $sumatifBab, $kelasId);
    }
}
