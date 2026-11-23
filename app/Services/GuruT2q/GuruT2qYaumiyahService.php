<?php

namespace App\Services\GuruT2q;

use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\User;
use App\Services\Admin\T2q\BaseYaumiyahT2qService;
use App\Services\Admin\T2q\YaumiyahTahfizhService;
use App\Services\Admin\T2q\YaumiyahTahsinService;

class GuruT2qYaumiyahService
{
    public function __construct(
        protected ?GuruT2qScopeService $scopeService = null,
        protected ?YaumiyahTahsinService $tahsinService = null,
        protected ?YaumiyahTahfizhService $tahfizhService = null,
    ) {
        $this->scopeService ??= new GuruT2qScopeService;
        $this->tahsinService ??= new YaumiyahTahsinService;
        $this->tahfizhService ??= new YaumiyahTahfizhService;
    }

    /**
     * Menyiapkan payload workspace input harian Yaumiyah Tahsin/Tahfizh khusus bimbingan Guru T2Q.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getWorkspacePageData(User $user, string $mode, array $filters = []): array
    {
        $scope = $this->scopeService->getActiveScopeForUser($user, false);
        $commonMeta = [
            'isGuruT2qScope' => true,
            'isWaliKelasScope' => false,
            'isReadOnly' => false,
            'routePrefix' => 'guru-t2q',
            'nipy' => $scope['nipy'],
            'karyawanProfile' => $scope['karyawanProfile'],
        ];

        if (! $scope['isEligible'] || ! $scope['activeTahunAjaran'] instanceof TahunAjaran) {
            return [
                ...$commonMeta,
                'isEligible' => false,
                'ineligibleReason' => $scope['ineligibleReason'],
                'activeTahunAjaran' => $scope['activeTahunAjaran'],
                't2qFinalisasi' => [],
            ];
        }

        $payload = $this->resolveModeService($mode)->getWorkspaceData($filters, $scope['nipy']);
        $t2qFinalisasi = $this->scopeService->getFinalisasiSummaryForUser(
            (int) $scope['activeTahunAjaran']->id,
            $scope['supervisedKelasIds']
        );

        return [
            ...$payload,
            ...$commonMeta,
            'isEligible' => true,
            'ineligibleReason' => null,
            't2qFinalisasi' => $t2qFinalisasi,
        ];
    }

    /**
     * Menyiapkan payload matriks mingguan (Senin – Kamis) khusus siswa bimbingan Guru T2Q.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getMatriksPageData(User $user, string $mode, array $filters = []): array
    {
        $scope = $this->scopeService->getActiveScopeForUser($user, false);
        $commonMeta = [
            'isGuruT2qScope' => true,
            'isWaliKelasScope' => false,
            'isReadOnly' => false,
            'routePrefix' => 'guru-t2q',
            'nipy' => $scope['nipy'],
            'karyawanProfile' => $scope['karyawanProfile'],
        ];

        if (! $scope['isEligible']) {
            return [
                ...$commonMeta,
                'isEligible' => false,
                'ineligibleReason' => $scope['ineligibleReason'],
                'activeTahunAjaran' => $scope['activeTahunAjaran'],
            ];
        }

        $payload = $this->resolveModeService($mode)->getMatrixData($filters, null, $scope['nipy']);

        return [
            ...$payload,
            ...$commonMeta,
            'isEligible' => true,
            'ineligibleReason' => null,
        ];
    }

    /**
     * Menyiapkan payload statistik Yaumiyah Tahsin/Tahfizh lintas Tahun Ajaran khusus siswa bimbingan Guru T2Q.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getStatistikPageData(User $user, string $mode, array $filters = []): array
    {
        $scope = $this->scopeService->getActiveScopeForUser($user, false);
        $historicalTas = $this->scopeService->getHistoricalTahunAjaranOptionsForUser($user);
        $commonMeta = [
            'isGuruT2qScope' => true,
            'isWaliKelasScope' => false,
            'isReadOnly' => false,
            'routePrefix' => 'guru-t2q',
            'nipy' => $scope['nipy'],
            'karyawanProfile' => $scope['karyawanProfile'],
        ];

        if ($scope['nipy'] === '' || (! $scope['isEligible'] && $historicalTas->isEmpty())) {
            return [
                ...$commonMeta,
                'isEligible' => false,
                'ineligibleReason' => $scope['ineligibleReason'] ?? 'not_assigned_active_year',
                'activeTahunAjaran' => $scope['activeTahunAjaran'],
            ];
        }

        $payload = $this->resolveModeService($mode)->getStatistikData(
            $filters,
            null,
            $historicalTas,
            $scope['nipy']
        );

        return [
            ...$payload,
            ...$commonMeta,
            'isEligible' => true,
            'ineligibleReason' => null,
        ];
    }

    private function resolveModeService(string $mode): BaseYaumiyahT2qService
    {
        return strtolower(trim($mode)) === 'tahfizh'
            ? $this->tahfizhService
            : $this->tahsinService;
    }
}
