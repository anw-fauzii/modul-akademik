<?php

namespace App\Services\WaliKelas;

use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\User;
use App\Services\Admin\T2q\BaseYaumiyahT2qService;
use App\Services\Admin\T2q\YaumiyahTahfizhService;
use App\Services\Admin\T2q\YaumiyahTahsinService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class WaliKelasT2qService
{
    public function __construct(
        protected ?WaliKelasScopeService $scopeService = null,
        protected ?YaumiyahTahsinService $tahsinService = null,
        protected ?YaumiyahTahfizhService $tahfizhService = null,
    ) {
        $this->scopeService ??= new WaliKelasScopeService;
        $this->tahsinService ??= new YaumiyahTahsinService;
        $this->tahfizhService ??= new YaumiyahTahfizhService;
    }

    /**
     * Menyiapkan payload halaman Matriks Yaumiyah T2Q (Senin – Kamis) bersifat read-only
     * khusus anggota kelas binaan Wali Kelas / Pendamping pada Tahun Ajaran aktif tanpa N+1.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getMatriksPageData(User $user, string $mode, array $filters = []): array
    {
        $requestedKelasId = (int) ($filters['kelas_id'] ?? 0) ?: null;
        $scope = $this->resolveAuthorizedActiveScope($user, $requestedKelasId);

        if (! $scope['isEligible'] || $scope['selectedKelas'] === null) {
            return $scope;
        }

        /** @var Kelas $selectedKelas */
        $selectedKelas = $scope['selectedKelas'];
        $scopedKelasIds = [(int) $selectedKelas->id];

        $payload = $this->resolveModeService($mode)->getMatrixData($filters, $scopedKelasIds);

        return [
            ...$payload,
            'isEligible' => true,
            'isReadOnly' => true,
            'isWaliKelasScope' => true,
            'routePrefix' => 'wali-kelas.t2q',
            'assignedClasses' => $scope['assignedClasses'],
            'selectedKelas' => $selectedKelas,
            'selectedKelasId' => (int) $selectedKelas->id,
            'peranDiKelas' => $scope['peranDiKelas'],
        ];
    }

    /**
     * Menyiapkan payload halaman Statistik Yaumiyah T2Q lintas Tahun Ajaran bersifat read-only
     * khusus anggota kelas yang diampu Wali Kelas / Pendamping tanpa N+1.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getStatistikPageData(User $user, string $mode, array $filters = []): array
    {
        $allAssignedClasses = $this->scopeService->getAllAssignedClassesForUser($user);

        if ($allAssignedClasses->isEmpty()) {
            return $this->scopeService->resolveScopeContext($user);
        }

        $tahunAjaranMap = [];
        foreach ($allAssignedClasses as $kelas) {
            $ta = $kelas->relationLoaded('tahunAjaran') ? $kelas->tahunAjaran : null;
            if ($ta instanceof TahunAjaran && ! isset($tahunAjaranMap[(int) $ta->id])) {
                $tahunAjaranMap[(int) $ta->id] = $ta;
            }
        }

        if ($tahunAjaranMap === []) {
            return $this->scopeService->resolveScopeContext($user);
        }

        $scopedTahunAjaranOptions = new EloquentCollection(array_values($tahunAjaranMap));
        $requestedTaId = (int) ($filters['tahun_ajaran_id'] ?? 0);

        if ($requestedTaId > 0 && ! isset($tahunAjaranMap[$requestedTaId])) {
            abort(403, 'Anda tidak memiliki penugasan Wali Kelas atau Guru Pendamping pada Tahun Ajaran tersebut.');
        }

        /** @var TahunAjaran $selectedTahunAjaran */
        $selectedTahunAjaran = $requestedTaId > 0
            ? $tahunAjaranMap[$requestedTaId]
            : ($scopedTahunAjaranOptions->firstWhere('is_active', true) ?? $scopedTahunAjaranOptions->first());

        $classesInSelectedTa = new EloquentCollection(
            $allAssignedClasses
                ->filter(static fn (Kelas $kelas): bool => (int) $kelas->akademik_master_tahun_ajaran_id === (int) $selectedTahunAjaran->id)
                ->values()
                ->all()
        );

        $requestedKelasId = (int) ($filters['kelas_id'] ?? 0);
        if ($requestedKelasId > 0 && ! $allAssignedClasses->contains('id', $requestedKelasId)) {
            abort(403, 'Anda tidak memiliki akses untuk melihat statistik Yaumiyah T2Q kelas ini.');
        }

        $selectedKelas = null;
        if ($requestedKelasId > 0) {
            $selectedKelas = $classesInSelectedTa->firstWhere('id', $requestedKelasId);
        }

        if ($selectedKelas === null && $classesInSelectedTa->count() === 1) {
            $selectedKelas = $classesInSelectedTa->first();
        }

        $scopedKelasIds = $selectedKelas !== null
            ? [(int) $selectedKelas->id]
            : $classesInSelectedTa->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all();

        $effectiveFilters = [
            ...$filters,
            'tahun_ajaran_id' => (int) $selectedTahunAjaran->id,
        ];

        $payload = $this->resolveModeService($mode)->getStatistikData(
            $effectiveFilters,
            $scopedKelasIds,
            $scopedTahunAjaranOptions
        );

        $nipy = trim((string) ($user->no_induk ?? ''));

        return [
            ...$payload,
            'isEligible' => true,
            'isReadOnly' => true,
            'isWaliKelasScope' => true,
            'routePrefix' => 'wali-kelas.t2q',
            'assignedClasses' => $classesInSelectedTa,
            'selectedKelas' => $selectedKelas,
            'selectedKelasId' => (int) ($selectedKelas?->id ?? 0),
            'peranDiKelas' => $selectedKelas !== null
                ? $this->scopeService->resolveRoleInKelas($selectedKelas, $nipy)
                : null,
        ];
    }

    /**
     * Menyelesaikan konteks penugasan aktif dan menolak kelas di luar cakupan Wali Kelas / Pendamping.
     *
     * @return array<string, mixed>
     */
    private function resolveAuthorizedActiveScope(User $user, ?int $requestedKelasId = null): array
    {
        $scope = $this->scopeService->resolveScopeContext($user, $requestedKelasId);

        if (
            $scope['isEligible']
            && $requestedKelasId !== null
            && $requestedKelasId > 0
            && ! $scope['assignedClasses']->contains('id', $requestedKelasId)
        ) {
            abort(403, 'Anda tidak memiliki akses untuk melihat matriks Yaumiyah T2Q kelas ini pada Tahun Ajaran aktif.');
        }

        return $scope;
    }

    /**
     * Menentukan service Yaumiyah sesuai mode ('tahsin' atau 'tahfizh').
     */
    private function resolveModeService(string $mode): BaseYaumiyahT2qService
    {
        return strtolower(trim($mode)) === 'tahfizh'
            ? $this->tahfizhService
            : $this->tahsinService;
    }
}

