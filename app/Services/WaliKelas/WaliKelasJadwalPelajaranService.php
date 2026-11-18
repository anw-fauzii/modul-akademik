<?php

namespace App\Services\WaliKelas;

use App\Models\User;
use App\Services\Admin\Pelajaran\JadwalPelajaranService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class WaliKelasJadwalPelajaranService
{
    public function __construct(
        protected ?WaliKelasScopeService $scopeService = null,
        protected ?JadwalPelajaranService $jadwalService = null
    ) {
        $this->scopeService ??= new WaliKelasScopeService;
        $this->jadwalService ??= new JadwalPelajaranService;
    }

    /**
     * Memuat data jadwal pelajaran (hanya lihat / read-only) khusus kelas binaan Wali Kelas / Pendamping
     * pada Tahun Ajaran aktif dengan proteksi kepemilikan kelas & anti-N+1.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function getIndexData(User $user, array $input = []): array
    {
        $rawKelasId = trim((string) ($input['kelas_id'] ?? ''));
        $requestedKelasId = ($rawKelasId !== '' && ctype_digit($rawKelasId) && (int) $rawKelasId > 0)
            ? (int) $rawKelasId
            : null;

        $scope = $this->scopeService->resolveScopeContext($user, $requestedKelasId, true);

        if (! $scope['isEligible'] || $scope['selectedKelas'] === null || $scope['activeTahunAjaran'] === null) {
            return [
                ...$scope,
                'isEligible' => false,
            ];
        }

        if ($requestedKelasId !== null && ! $scope['assignedClasses']->contains('id', $requestedKelasId)) {
            abort(403, 'Anda hanya dapat melihat jadwal pelajaran untuk kelas binaan Anda.');
        }

        $activeTaId = (int) $scope['activeTahunAjaran']->id;
        $rawTa = trim((string) ($input['tahun_ajaran_id'] ?? ''));
        if (
            $rawTa !== ''
            && ($rawTa === JadwalPelajaranService::FILTER_ALL || ! ctype_digit($rawTa) || (int) $rawTa !== $activeTaId)
        ) {
            abort(403, 'Jadwal Pelajaran Wali Kelas hanya tersedia untuk Tahun Ajaran aktif.');
        }

        $selectedKelas = $scope['selectedKelas'];
        $scopedInput = [
            'tahun_ajaran_id' => (string) $activeTaId,
            'unit' => '',
            'kelas_id' => (string) $selectedKelas->id,
            'hari' => trim((string) ($input['hari'] ?? '')),
            'status' => trim((string) ($input['status'] ?? JadwalPelajaranService::FILTER_ALL)),
            'search' => trim((string) ($input['search'] ?? '')),
        ];

        $payload = $this->jadwalService->getIndexData(
            $scopedInput,
            $scope['assignedClasses'],
            new EloquentCollection([$scope['activeTahunAjaran']])
        );

        return [
            ...$scope,
            ...$payload,
            'isEligible' => true,
            'isWaliKelasScope' => true,
            'routePrefix' => 'wali-kelas.jadwal-pelajaran',
        ];
    }
}
