<?php

namespace App\Policies;

use App\Models\Admin\DataMaster\Kelas;
use App\Models\User;
use App\Services\WaliKelas\WaliKelasScopeService;
use Illuminate\Auth\Access\Response;

class KelasPolicy extends BasePolicy
{
    public function viewAny(User $user): Response
    {
        return ($this->isGuruPengajar($user) || $this->isGuruT2q($user))
            ? Response::allow()
            : Response::deny('Akses ditolak. Fitur ini khusus Super Admin, Wali Kelas, Guru Mapel SD, atau Guru T2Q.');
    }

    public function view(User $user, Kelas $kelas): Response
    {
        if (! $this->isWaliKelas($user)) {
            return Response::deny('Akses ditolak.');
        }

        return $this->checkScope(
            fn (WaliKelasScopeService $scope) => $scope->authorizeKelasIdForUser(
                $user,
                (int) $kelas->id,
                'Anda tidak memiliki akses untuk melihat kelas ini pada Tahun Ajaran aktif.'
            )
        );
    }

    public function viewT2qStatistik(User $user, Kelas $kelas): Response
    {
        if (! $this->isWaliKelas($user)) {
            return Response::deny('Akses ditolak.');
        }

        $allowed = $this->scopeService()
            ->getAllAssignedClassesForUser($user)
            ->contains('id', (int) $kelas->id);

        return $allowed
            ? Response::allow()
            : Response::deny('Anda tidak memiliki akses statistik T2Q untuk kelas tersebut.');
    }

    public function create(User $user): Response
    {
        return Response::deny('Penambahan data kelas khusus Super Admin.');
    }

    public function update(User $user, Kelas $kelas): Response
    {
        return Response::deny('Perubahan data kelas khusus Super Admin.');
    }

    public function delete(User $user, Kelas $kelas): Response
    {
        return Response::deny('Penghapusan data kelas khusus Super Admin.');
    }
}
