<?php

namespace App\Policies;

use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Models\User;
use App\Services\WaliKelas\WaliKelasEkstrakurikulerService;
use Illuminate\Auth\Access\Response;

/**
 * Policy Ekstrakurikuler:
 * - Super Admin memiliki akses penuh CRUD Master Ekstrakurikuler dan Penilaian (via BasePolicy::before).
 * - Guru / Wali Kelas dapat mengakses halaman Penilaian Ekstrakurikuler dan menginput nilai
 *   untuk ekstrakurikuler yang dibimbingnya (karyawan_nipy === user->no_induk) pada Tahun Ajaran aktif.
 */
class EkstrakurikulerPolicy extends BasePolicy
{
    public function viewAny(User $user): Response
    {
        return Response::deny('Akses pengelolaan master ekstrakurikuler khusus Super Admin.');
    }

    public function view(User $user, Ekstrakurikuler $ekstrakurikuler): Response
    {
        return Response::deny('Akses detail master ekstrakurikuler khusus Super Admin.');
    }

    public function create(User $user): Response
    {
        return Response::deny('Penambahan master ekstrakurikuler khusus Super Admin.');
    }

    public function update(User $user, Ekstrakurikuler $ekstrakurikuler): Response
    {
        return Response::deny('Pembaruan master ekstrakurikuler khusus Super Admin.');
    }

    public function delete(User $user, Ekstrakurikuler $ekstrakurikuler): Response
    {
        return Response::deny('Penghapusan master ekstrakurikuler khusus Super Admin.');
    }

    public function viewPenilaian(User $user): Response
    {
        return $this->allowIfGuruPengajar($user);
    }

    public function inputNilai(User $user, Ekstrakurikuler $ekstrakurikuler): Response
    {
        if (! $this->isGuruPengajar($user)) {
            return Response::deny('Akses ditolak.');
        }

        return $this->checkScope(static function () use ($user, $ekstrakurikuler): void {
            app(WaliKelasEkstrakurikulerService::class)->authorizeEkstrakurikulerForUser($user, $ekstrakurikuler);
        });
    }
}
