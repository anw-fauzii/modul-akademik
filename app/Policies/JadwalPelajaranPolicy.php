<?php

namespace App\Policies;

use App\Models\Admin\Pelajaran\JadwalPelajaran;
use App\Models\User;
use App\Services\WaliKelas\WaliKelasScopeService;
use Illuminate\Auth\Access\Response;

class JadwalPelajaranPolicy extends BasePolicy
{
    public function viewAny(User $user): Response
    {
        return $this->allowIfGuruPengajar($user);
    }

    public function view(User $user, JadwalPelajaran $jadwalPelajaran): Response
    {
        if ($this->isWaliKelas($user)) {
            return $this->checkScope(
                fn (WaliKelasScopeService $scope) => $scope->authorizeKelasIdForUser(
                    $user,
                    (int) $jadwalPelajaran->akademik_master_kelas_id,
                    'Anda tidak memiliki akses untuk melihat jadwal pelajaran kelas ini.'
                )
            );
        }

        if ($this->isGuruMapelSd($user)) {
            $nipy = trim((string) ($user->no_induk ?? ''));

            return $nipy !== '' && trim((string) $jadwalPelajaran->karyawan_nipy) === $nipy
                ? Response::allow()
                : Response::deny('Anda hanya dapat melihat jadwal mengajar milik Anda sendiri.');
        }

        return Response::deny('Akses ditolak.');
    }

    public function create(User $user): Response
    {
        return Response::deny('Penambahan jadwal pelajaran khusus Super Admin.');
    }

    public function update(User $user, JadwalPelajaran $jadwalPelajaran): Response
    {
        return Response::deny('Perubahan jadwal pelajaran khusus Super Admin.');
    }

    public function delete(User $user, JadwalPelajaran $jadwalPelajaran): Response
    {
        return Response::deny('Penghapusan jadwal pelajaran khusus Super Admin.');
    }

    public function toggleStatus(User $user, JadwalPelajaran $jadwalPelajaran): Response
    {
        return Response::deny('Perubahan status jadwal pelajaran khusus Super Admin.');
    }
}
