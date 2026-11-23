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
        return ($this->isGuruPengajar($user) || $this->isGuruT2q($user))
            ? Response::allow()
            : Response::deny('Akses ditolak.');
    }

    public function view(User $user, PenilaianFinalisasi $finalisasi): Response
    {
        return ($this->isGuruPengajar($user) || $this->isGuruT2q($user))
            ? Response::allow()
            : Response::deny('Akses ditolak.');
    }

    /**
     * Memastikan user berhak memfinalkan atau membuka kunci komponen penilaian spesifik.
     *
     * @param  array<string, mixed>  $context
     */
    public function manageComponent(User $user, array $context): Response
    {
        $isPengajar = $this->isGuruPengajar($user);
        $isT2q = $this->isGuruT2q($user);

        if (! $isPengajar && ! $isT2q) {
            return Response::deny('Akses ditolak.');
        }

        if (
            $isT2q
            && ! $isPengajar
            && ! in_array(trim((string) ($context['jenis_komponen'] ?? '')), [
                PenilaianFinalisasi::JENIS_T2Q,
                PenilaianFinalisasi::JENIS_EKSKUL_PILIHAN,
            ], true)
        ) {
            return Response::deny('Guru T2Q hanya dapat mengelola finalisasi Program T2Q atau Ekstrakurikuler Pilihan yang dibinanya.');
        }

        return $this->checkScope(static function () use ($user, $context): void {
            app(PenilaianFinalisasiService::class)->authorizeActorForComponent($user, $context);
        });
    }
}
