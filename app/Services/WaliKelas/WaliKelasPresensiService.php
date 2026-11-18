<?php

namespace App\Services\WaliKelas;

use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\Presensi\PresensiSiswa;
use App\Models\User;
use App\Services\Admin\Presensi\PresensiSiswaService;

class WaliKelasPresensiService
{
    public function __construct(
        protected ?WaliKelasScopeService $scopeService = null,
        protected ?PresensiSiswaService $presensiSiswaService = null
    ) {
        $this->scopeService ??= new WaliKelasScopeService;
        $this->presensiSiswaService ??= new PresensiSiswaService;
    }

    /**
     * Menyelesaikan konteks penugasan aktif dan menolak kelas di luar cakupan Wali Kelas / Pendamping.
     *
     * @return array<string, mixed>
     */
    private function resolveAuthorizedScope(User $user, ?int $requestedKelasId = null): array
    {
        $scope = $this->scopeService->resolveScopeContext($user, $requestedKelasId);

        if (
            $scope['isEligible']
            && $requestedKelasId !== null
            && $requestedKelasId > 0
            && ! $scope['assignedClasses']->contains('id', $requestedKelasId)
        ) {
            abort(403, 'Anda tidak memiliki akses untuk melihat presensi kelas ini pada Tahun Ajaran aktif.');
        }

        return $scope;
    }

    /**
     * Menyiapkan payload halaman matriks & log presensi khusus kelas binaan aktif tanpa N+1.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getIndexPageData(User $user, array $filters = []): array
    {
        $requestedKelasId = (int) ($filters['kelas_id'] ?? 0) ?: null;
        $scope = $this->resolveAuthorizedScope($user, $requestedKelasId);

        if (! $scope['isEligible']) {
            return $scope;
        }

        return [
            ...$this->presensiSiswaService->getIndexData($filters, $scope['assignedClasses']),
            'isEligible' => true,
            'presensiRoutePrefix' => 'wali-kelas.presensi-siswa',
            'isWaliKelasScope' => true,
            'assignedClasses' => $scope['assignedClasses'],
            'peranDiKelas' => $scope['peranDiKelas'],
        ];
    }

    /**
     * Menyiapkan payload halaman input presensi massal khusus kelas binaan aktif.
     *
     * @return array<string, mixed>
     */
    public function getBulkPageData(User $user, ?int $requestedKelasId, string $tanggal): array
    {
        $scope = $this->resolveAuthorizedScope($user, $requestedKelasId);

        if (! $scope['isEligible']) {
            return $scope;
        }

        $effectiveKelasId = $requestedKelasId ?? $scope['selectedKelas']?->id;

        return [
            ...$this->presensiSiswaService->getBulkData($effectiveKelasId, $tanggal, $scope['assignedClasses']),
            'isEligible' => true,
            'presensiRoutePrefix' => 'wali-kelas.presensi-siswa',
            'isWaliKelasScope' => true,
            'assignedClasses' => $scope['assignedClasses'],
            'peranDiKelas' => $scope['peranDiKelas'],
        ];
    }

    /**
     * Menyimpan input presensi massal setelah memverifikasi kelas milik Wali Kelas / Pendamping aktif.
     *
     * @param  array<string, mixed>  $validated
     */
    public function storeBulkForUser(User $user, array $validated): int
    {
        $this->scopeService->authorizeKelasIdForUser(
            $user,
            (int) ($validated['kelas_id'] ?? 0),
            'Anda tidak memiliki akses untuk menyimpan presensi kelas ini pada Tahun Ajaran aktif.'
        );

        return $this->presensiSiswaService->storeBulk($validated);
    }

    /**
     * Menghapus presensi massal per tanggal setelah memverifikasi kepemilikan kelas aktif.
     */
    public function deleteBulkForUser(User $user, int $kelasId, string $tanggal): int
    {
        $this->scopeService->authorizeKelasIdForUser(
            $user,
            $kelasId,
            'Anda tidak memiliki akses untuk menghapus presensi kelas ini pada Tahun Ajaran aktif.'
        );

        return $this->presensiSiswaService->deleteBulkByClassAndDate($kelasId, $tanggal);
    }

    /**
     * Menyiapkan data form edit presensi satu siswa dalam kelas binaan aktif tanpa N+1.
     *
     * @return array<string, mixed>
     */
    public function getEditPageData(User $user, PresensiSiswa $presensiSiswa): array
    {
        $scope = $this->scopeService->authorizePresensiSiswaForUser($user, $presensiSiswa, true);

        return [
            'presensiSiswa' => $presensiSiswa,
            ...$this->presensiSiswaService->getFormData($scope['assignedClasses']),
            'presensiRoutePrefix' => 'wali-kelas.presensi-siswa',
            'isWaliKelasScope' => true,
        ];
    }

    /**
     * Memperbarui catatan presensi siswa setelah memverifikasi kepemilikan asal dan tujuan.
     *
     * @param  array<string, mixed>  $validated
     */
    public function updateForUser(User $user, PresensiSiswa $presensiSiswa, array $validated): PresensiSiswa
    {
        $this->scopeService->authorizePresensiSiswaForUser($user, $presensiSiswa);

        $targetAnggotaId = (int) ($validated['anggota_kelas_id'] ?? 0);
        if ($targetAnggotaId > 0 && $targetAnggotaId !== (int) $presensiSiswa->anggota_kelas_id) {
            $targetAnggota = AnggotaKelas::query()
                ->select(['id', 'akademik_master_kelas_id'])
                ->find($targetAnggotaId);

            if (! $targetAnggota instanceof AnggotaKelas) {
                abort(403, 'Siswa anggota kelas tujuan tidak ditemukan.');
            }

            $this->scopeService->authorizeAnggotaKelasForUser($user, $targetAnggota);
        }

        return $this->presensiSiswaService->update($presensiSiswa, $validated);
    }

    /**
     * Menghapus catatan presensi siswa setelah memverifikasi kepemilikan kelas binaan aktif.
     */
    public function deleteForUser(User $user, PresensiSiswa $presensiSiswa): void
    {
        $this->scopeService->authorizePresensiSiswaForUser($user, $presensiSiswa);

        $this->presensiSiswaService->delete($presensiSiswa);
    }

    /**
     * Menyiapkan data rekap & persentase kehadiran kelas binaan aktif tanpa N+1.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getRekapPageData(User $user, array $filters = [], bool $hasExplicitKelasParam = false): array
    {
        $requestedKelasId = (int) ($filters['kelas_id'] ?? 0) ?: null;
        $scope = $this->resolveAuthorizedScope($user, $requestedKelasId);

        if (! $scope['isEligible']) {
            return $scope;
        }

        if (! $hasExplicitKelasParam && $requestedKelasId === null && $scope['assignedClasses']->count() === 1) {
            $filters['kelas_id'] = $scope['assignedClasses']->first()->id;
        }

        return [
            ...$this->presensiSiswaService->getRekapData($filters, $scope['assignedClasses']),
            'isEligible' => true,
            'presensiRoutePrefix' => 'wali-kelas.presensi-siswa',
            'isWaliKelasScope' => true,
            'assignedClasses' => $scope['assignedClasses'],
            'peranDiKelas' => $scope['peranDiKelas'],
        ];
    }

    /**
     * Menyiapkan statistik presensi tahunan khusus kelas binaan aktif tanpa N+1.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getTahunanPageData(User $user, array $filters = [], bool $hasExplicitKelasParam = false): array
    {
        $requestedKelasId = (int) ($filters['kelas_id'] ?? 0) ?: null;
        $scope = $this->resolveAuthorizedScope($user, $requestedKelasId);

        if (! $scope['isEligible']) {
            return $scope;
        }

        if (! $hasExplicitKelasParam && $requestedKelasId === null && $scope['assignedClasses']->count() === 1) {
            $filters['kelas_id'] = $scope['assignedClasses']->first()->id;
        }

        return [
            ...$this->presensiSiswaService->getTahunanData($filters, $scope['assignedClasses']),
            'isEligible' => true,
            'presensiRoutePrefix' => 'wali-kelas.presensi-siswa',
            'isWaliKelasScope' => true,
            'assignedClasses' => $scope['assignedClasses'],
            'peranDiKelas' => $scope['peranDiKelas'],
        ];
    }
}

