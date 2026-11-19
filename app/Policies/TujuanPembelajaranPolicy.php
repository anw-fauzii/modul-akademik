<?php

namespace App\Policies;

use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use App\Models\User;
use App\Services\WaliKelas\WaliKelasScopeService;
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

    public function toggleMasukRapor(User $user, TujuanPembelajaran $tujuanPembelajaran, ?int $kelasId = null): Response
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
}
