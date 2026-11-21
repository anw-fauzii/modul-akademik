<?php

namespace App\Policies;

use App\Models\Admin\Penilaian\PenilaianFinalisasi;
use App\Models\User;
use App\Services\Admin\Penilaian\PenilaianFinalisasiService;
use Illuminate\Auth\Access\Response;

/**
 * Policy Finalisasi Penilaian Rapor (Opsi C):
 * - Super Admin memiliki akses penuh finalisasi maupun buka kunci (via BasePolicy::before()).
 * - Guru Pengampu / Pembina Ekskul / Guru T2Q / Wali Kelas hanya dapat memfinalkan & membuka kunci komponen yang diampunya.
 */
class PenilaianFinalisasiPolicy extends BasePolicy
{
    public function viewAny(User $user): Response
    {
        return $this->allowIfWaliKelas($user);
    }

    public function view(User $user, PenilaianFinalisasi $finalisasi): Response
    {
        return $this->allowIfWaliKelas($user);
    }

    /**
     * Memastikan user berhak memfinalkan atau membuka kunci komponen penilaian spesifik.
     *
     * @param  array<string, mixed>  $context
     */
    public function manageComponent(User $user, array $context): Response
    {
        if (! $this->isWaliKelas($user)) {
            return Response::deny('Akses ditolak.');
        }

        return $this->checkScope(static function () use ($user, $context): void {
            app(PenilaianFinalisasiService::class)->authorizeActorForComponent($user, $context);
        });
    }
}
