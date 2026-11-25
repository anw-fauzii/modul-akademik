<?php

namespace App\Policies;

use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\Penilaian\CatatanRaporWali;
use App\Models\User;
use App\Services\WaliKelas\WaliKelasScopeService;
use Illuminate\Auth\Access\Response;

class CatatanRaporWaliPolicy extends BasePolicy
{
    public function viewAny(User $user): Response
    {
        return $this->allowIfWaliKelas($user, 'Akses ditolak. Pengisian Catatan Rapor khusus Wali Kelas, Guru Pendamping, atau Super Admin.');
    }

    public function saveBatch(User $user): Response
    {
        return $this->allowIfWaliKelas($user, 'Akses ditolak. Penyimpanan Catatan Rapor khusus Wali Kelas, Guru Pendamping, atau Super Admin.');
    }

    public function updateForAnggota(User $user, AnggotaKelas $anggotaKelas): Response
    {
        if (! $this->isWaliKelas($user)) {
            return Response::deny('Akses ditolak. Penyimpanan Catatan Rapor khusus Wali Kelas, Guru Pendamping, atau Super Admin.');
        }

        return $this->checkScope(
            fn (WaliKelasScopeService $scope) => $scope->authorizeAnggotaKelasForUser($user, $anggotaKelas)
        );
    }

    public function view(User $user, CatatanRaporWali $catatan): Response
    {
        return $this->allowIfWaliKelas($user);
    }
}
