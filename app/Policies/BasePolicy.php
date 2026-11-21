<?php

namespace App\Policies;

use App\Models\User;
use App\Services\WaliKelas\WaliKelasScopeService;
use Illuminate\Auth\Access\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Policy dasar aplikasi:
 * - Role super admin memiliki akses penuh pada seluruh modul (checked once in before()).
 * - Policy domain lainnya mendelegasikan aturan cakupan ke WaliKelasScopeService (DRY & anti-N+1).
 */
abstract class BasePolicy
{
    public const string ROLE_SUPER_ADMIN = 'super admin';

    public const string ROLE_WALI_KELAS = 'wali kelas';

    public const string ROLE_GURU_MAPEL_SD = 'guru mapel sd';

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(self::ROLE_SUPER_ADMIN)) {
            return true;
        }

        return null;
    }

    protected function isWaliKelas(User $user): bool
    {
        return $user->hasRole(self::ROLE_WALI_KELAS);
    }

    protected function isGuruMapelSd(User $user): bool
    {
        return $user->hasRole(self::ROLE_GURU_MAPEL_SD);
    }

    protected function isGuruPengajar(User $user): bool
    {
        return $user->hasAnyRole([self::ROLE_WALI_KELAS, self::ROLE_GURU_MAPEL_SD]);
    }

    protected function allowIfWaliKelas(
        User $user,
        string $deniedMessage = 'Akses ditolak. Fitur ini khusus Super Admin atau Wali Kelas.'
    ): Response {
        return $this->isWaliKelas($user)
            ? Response::allow()
            : Response::deny($deniedMessage);
    }

    protected function allowIfGuruPengajar(
        User $user,
        string $deniedMessage = 'Akses ditolak. Fitur ini khusus Super Admin, Wali Kelas, atau Guru Mapel SD.'
    ): Response {
        return $this->isGuruPengajar($user)
            ? Response::allow()
            : Response::deny($deniedMessage);
    }

    protected function scopeService(): WaliKelasScopeService
    {
        return app(WaliKelasScopeService::class);
    }

    /**
     * Mengevaluasi helper otorisasi scope dan mengonversi HttpException 403 menjadi Response::deny().
     *
     * @param  callable(WaliKelasScopeService): mixed  $authorizer
     */
    protected function checkScope(
        callable $authorizer,
        string $fallbackMessage = 'Anda tidak memiliki akses ke sumber daya ini.'
    ): Response {
        try {
            $authorizer($this->scopeService());

            return Response::allow();
        } catch (HttpExceptionInterface $exception) {
            return Response::deny($exception->getMessage() ?: $fallbackMessage);
        }
    }
}
