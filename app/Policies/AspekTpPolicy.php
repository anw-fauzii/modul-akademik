<?php

namespace App\Policies;

use App\Models\Admin\Penilaian\AspekTp;
use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use App\Models\User;
use App\Services\WaliKelas\WaliKelasScopeService;
use Illuminate\Auth\Access\Response;

class AspekTpPolicy extends BasePolicy
{
    public function create(User $user): Response
    {
        return $this->allowIfWaliKelas($user);
    }

    public function createForTp(User $user, TujuanPembelajaran $tujuanPembelajaran, ?int $kelasId = null): Response
    {
        if (! $this->isWaliKelas($user)) {
            return Response::deny('Akses ditolak.');
        }

        return $this->checkScope(
            fn (WaliKelasScopeService $scope) => $scope->authorizeSdMapelScope(
                $user,
                $kelasId,
                (int) $tujuanPembelajaran->akademik_pelajaran_mata_pelajaran_id,
                (int) $tujuanPembelajaran->akademik_master_tahun_ajaran_id,
                (string) $tujuanPembelajaran->tingkatan_kelas
            )
        );
    }

    public function update(User $user, AspekTp $aspekTp, ?int $kelasId = null): Response
    {
        if (! $this->isWaliKelas($user)) {
            return Response::deny('Akses ditolak.');
        }

        $aspekTp->loadMissing('tujuanPembelajaran:id,akademik_master_tahun_ajaran_id,akademik_pelajaran_mata_pelajaran_id,tingkatan_kelas');
        $tp = $aspekTp->tujuanPembelajaran;

        if (! $tp instanceof TujuanPembelajaran) {
            return Response::deny('Tujuan Pembelajaran untuk aspek ini tidak ditemukan.');
        }

        return $this->createForTp($user, $tp, $kelasId);
    }

    public function delete(User $user, AspekTp $aspekTp, ?int $kelasId = null): Response
    {
        return $this->update($user, $aspekTp, $kelasId);
    }
}
