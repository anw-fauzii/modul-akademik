<?php

namespace App\Services\WaliKelas;

use App\Exports\Admin\Penilaian\PenilaianTpExport;
use App\Imports\Admin\Penilaian\PenilaianTpImport;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Admin\Penilaian\AspekTp;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Models\Admin\Penilaian\RekapMapel;
use App\Models\Admin\Penilaian\SumatifBab;
use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use App\Models\User;
use App\Services\Admin\Penilaian\PenilaianTpService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Maatwebsite\Excel\Facades\Excel;

class WaliKelasPenilaianService
{
    public function __construct(
        protected ?WaliKelasScopeService $scopeService = null,
        protected ?PenilaianTpService $penilaianTpService = null
    ) {
        $this->scopeService ??= new WaliKelasScopeService;
        $this->penilaianTpService ??= new PenilaianTpService;
    }

    /**
     * Memuat data matriks atau rekap Penilaian TP khusus kelas & mata pelajaran SD yang diampu user.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getPageData(User $user, array $filters = [], bool $isRekap = false): array
    {
        $requestedKelasId = isset($filters['kelas_id']) && (int) $filters['kelas_id'] > 0
            ? (int) $filters['kelas_id']
            : null;
        $requestedMapelId = isset($filters['mata_pelajaran_id']) && (int) $filters['mata_pelajaran_id'] > 0
            ? (int) $filters['mata_pelajaran_id']
            : null;
        $requestedTaId = isset($filters['tahun_ajaran_id']) && (int) $filters['tahun_ajaran_id'] > 0
            ? (int) $filters['tahun_ajaran_id']
            : null;

        $taughtScope = $this->scopeService->getTaughtSdScopeForUser($user, null, true);

        if (! $taughtScope['isEligible']) {
            $reason = $taughtScope['ineligibleReason'];
            if ($reason === 'no_active_tahun_ajaran' || $reason === 'missing_nipy') {
                return [
                    ...$taughtScope,
                    'isEligible' => false,
                    'hasSdClasses' => false,
                    'guardReason' => $reason,
                ];
            }

            $assignedClasses = $this->scopeService->getAssignedClassesForUser($user, $taughtScope['activeTahunAjaran']);
            $sdClasses = $this->scopeService->filterSdClasses($assignedClasses);
            $hasNonSdOnly = $assignedClasses->isNotEmpty() && $sdClasses->isEmpty();
            $guardReason = $hasNonSdOnly
                ? 'non_sd_only'
                : ($sdClasses->isNotEmpty() ? 'no_taught_mapel' : 'not_assigned_active_year');

            return [
                ...$taughtScope,
                'assignedClasses' => $assignedClasses,
                'isEligible' => false,
                'hasSdClasses' => $sdClasses->isNotEmpty(),
                'guardReason' => $guardReason,
            ];
        }

        $activeTaId = (int) ($taughtScope['activeTahunAjaran']?->id ?? 0);
        if ($requestedTaId !== null && $requestedTaId !== $activeTaId) {
            abort(403, 'Penilaian Wali Kelas hanya tersedia untuk Tahun Ajaran aktif.');
        }

        /** @var EloquentCollection<int, Kelas> $taughtSdClasses */
        $taughtSdClasses = $taughtScope['taughtClasses'];

        if ($requestedKelasId !== null && ! $taughtSdClasses->contains('id', $requestedKelasId)) {
            abort(403, 'Anda hanya dapat mengakses Penilaian TP pada kelas yang memiliki mata pelajaran yang Anda ampu.');
        }

        $selectedKelas = $requestedKelasId !== null
            ? $taughtSdClasses->firstWhere('id', $requestedKelasId)
            : $taughtSdClasses->first();

        $allowedMapels = $taughtScope['taughtMapelsByClass']->get((int) $selectedKelas->id, new EloquentCollection);
        if ($requestedMapelId !== null && ! $allowedMapels->contains('id', $requestedMapelId)) {
            abort(403, 'Anda hanya dapat mengakses Penilaian TP untuk mata pelajaran yang Anda ampu pada jadwal kelas ini.');
        }

        $filters['tahun_ajaran_id'] = $activeTaId;
        $filters['kelas_id'] = $selectedKelas->id;

        $payload = $isRekap
            ? $this->penilaianTpService->getRekapData($filters, $taughtSdClasses, $allowedMapels)
            : $this->penilaianTpService->getMatrixData($filters, $taughtSdClasses, $allowedMapels);

        return [
            ...$payload,
            'isEligible' => true,
            'hasSdClasses' => true,
            'tpRoutePrefix' => 'wali-kelas.penilaian-tp',
            'rekapRoutePrefix' => 'wali-kelas.penilaian-rekap',
            'isWaliKelasScope' => true,
            'assignedClasses' => $taughtSdClasses,
            'peranDiKelas' => $this->scopeService->resolveRoleInKelas(
                $selectedKelas,
                (string) ($taughtScope['nipy'] ?? ''),
                true
            ),
        ];
    }

    /**
     * Menyimpan seluruh nilai grid setelah memverifikasi kepemilikan kelas & mapel yang diampu.
     *
     * @param  array<string, mixed>  $validated
     * @return array{kelas: Kelas, mapel: MataPelajaran, result: array{saved_cells: int, updated_students: int}}
     */
    public function saveNilaiGridForUser(User $user, array $validated): array
    {
        $requestedTaId = isset($validated['tahun_ajaran_id']) ? (int) $validated['tahun_ajaran_id'] : null;
        $mapelId = (int) $validated['mata_pelajaran_id'];
        $auth = $this->scopeService->authorizeSdMapelScope($user, (int) $validated['kelas_id'], $mapelId, $requestedTaId);

        $kelas = $auth['selectedKelas'];
        $mapel = $auth['allowedMapels']->firstWhere('id', $mapelId) ?? MataPelajaran::query()->findOrFail($mapelId);
        $periode = (string) $validated['periode'];

        $result = $this->penilaianTpService->saveNilaiGrid(
            $kelas,
            $mapel,
            $periode,
            $validated['nilai'] ?? [],
            $user->no_induk,
            $validated['ujian'] ?? [],
            $validated['sumatif'] ?? []
        );

        return compact('kelas', 'mapel', 'result');
    }

    /**
     * Memperbarui bobot Rapor setelah verifikasi kepemilikan kelas & mapel yang diampu.
     *
     * @param  array<string, mixed>  $validated
     * @return array{kelas: Kelas, mapel: MataPelajaran}
     */
    public function updateBobotForUser(User $user, array $validated): array
    {
        $requestedTaId = isset($validated['tahun_ajaran_id']) ? (int) $validated['tahun_ajaran_id'] : null;
        $mapelId = (int) $validated['mata_pelajaran_id'];
        $auth = $this->scopeService->authorizeSdMapelScope($user, (int) $validated['kelas_id'], $mapelId, $requestedTaId);

        $kelas = $auth['selectedKelas'];
        $mapel = $auth['allowedMapels']->firstWhere('id', $mapelId) ?? MataPelajaran::query()->findOrFail($mapelId);

        $this->penilaianTpService->updateBobotRapor(
            $kelas,
            $mapel,
            (int) $validated['bobot_tp'],
            (int) $validated['bobot_ujian'],
            (int) ($validated['bobot_sumatif'] ?? 0)
        );

        return compact('kelas', 'mapel');
    }

    /**
     * Menyimpan atau memperbarui kolom Sumatif Bab pada kelas/tingkatan & mapel yang diampu.
     *
     * @param  array<string, mixed>  $validated
     */
    public function saveSumatifBabForUser(User $user, array $validated, ?SumatifBab $existing = null): SumatifBab
    {
        $requestedKelasId = isset($validated['kelas_id']) ? (int) $validated['kelas_id'] : null;

        if ($existing !== null) {
            $this->scopeService->authorizeSdMapelScope(
                $user,
                null,
                (int) $existing->akademik_pelajaran_mata_pelajaran_id,
                (int) $existing->akademik_master_tahun_ajaran_id,
                (string) $existing->tingkatan_kelas
            );
        }

        $this->scopeService->authorizeSdMapelScope(
            $user,
            $requestedKelasId,
            (int) $validated['mata_pelajaran_id'],
            (int) $validated['tahun_ajaran_id'],
            (string) $validated['tingkatan_kelas']
        );

        return $this->penilaianTpService->saveSumatifBab($validated, $existing);
    }

    /**
     * Menghapus kolom Sumatif Bab setelah verifikasi kepemilikan kelas/tingkatan & mapel yang diampu.
     */
    public function deleteSumatifBabForUser(User $user, SumatifBab $sumatifBab, ?int $kelasId = null): void
    {
        $this->scopeService->authorizeSdMapelScope(
            $user,
            $kelasId,
            (int) $sumatifBab->akademik_pelajaran_mata_pelajaran_id,
            (int) $sumatifBab->akademik_master_tahun_ajaran_id,
            (string) $sumatifBab->tingkatan_kelas
        );

        $this->penilaianTpService->deleteSumatifBab($sumatifBab);
    }

    /**
     * Mengubah status masuk rapor per TP untuk kelas/tingkatan & mapel yang diampu.
     */
    public function toggleMasukRaporForUser(
        User $user,
        TujuanPembelajaran $tp,
        string $periode,
        bool $masukRapor,
        ?int $kelasId = null
    ): PeriodeTp {
        $this->scopeService->authorizeSdMapelScope(
            $user,
            $kelasId,
            (int) $tp->akademik_pelajaran_mata_pelajaran_id,
            (int) $tp->akademik_master_tahun_ajaran_id,
            (string) $tp->tingkatan_kelas
        );

        return $this->penilaianTpService->toggleMasukRapor($tp, $periode, $masukRapor);
    }

    /**
     * Menambahkan atau memperbarui aspek TP untuk tingkatan & mapel yang diampu.
     *
     * @param  array<string, mixed>  $validated
     * @return array{tp: TujuanPembelajaran, aspek: AspekTp}
     */
    public function saveAspekForUser(
        User $user,
        TujuanPembelajaran $tp,
        array $validated,
        ?AspekTp $existing = null
    ): array {
        $requestedKelasId = isset($validated['kelas_id']) ? (int) $validated['kelas_id'] : null;

        if ($existing !== null) {
            $existing->loadMissing('tujuanPembelajaran:id,akademik_master_tahun_ajaran_id,akademik_pelajaran_mata_pelajaran_id,tingkatan_kelas');
            $existingTp = $existing->tujuanPembelajaran;
            $this->scopeService->authorizeSdMapelScope(
                $user,
                null,
                (int) ($existingTp?->akademik_pelajaran_mata_pelajaran_id ?? 0),
                (int) ($existingTp?->akademik_master_tahun_ajaran_id ?? 0),
                (string) ($existingTp?->tingkatan_kelas ?? '')
            );
        }

        $this->scopeService->authorizeSdMapelScope(
            $user,
            $requestedKelasId,
            (int) $tp->akademik_pelajaran_mata_pelajaran_id,
            (int) $tp->akademik_master_tahun_ajaran_id,
            (string) $tp->tingkatan_kelas
        );

        $aspek = $this->penilaianTpService->saveAspek($tp, $validated, $existing);

        return compact('tp', 'aspek');
    }

    /**
     * Menghapus aspek TP setelah verifikasi kepemilikan kelas & mapel yang diampu.
     */
    public function deleteAspekForUser(User $user, AspekTp $aspekTp, ?int $kelasId = null): ?TujuanPembelajaran
    {
        $aspekTp->loadMissing('tujuanPembelajaran');
        $tp = $aspekTp->tujuanPembelajaran;

        $this->scopeService->authorizeSdMapelScope(
            $user,
            $kelasId,
            (int) ($tp?->akademik_pelajaran_mata_pelajaran_id ?? 0),
            (int) ($tp?->akademik_master_tahun_ajaran_id ?? 0),
            (string) ($tp?->tingkatan_kelas ?? '')
        );

        $this->penilaianTpService->deleteAspek($aspekTp);

        return $tp;
    }

    /**
     * Membuat instance export Excel matriks nilai TP khusus kelas & mapel yang diampu.
     *
     * @param  array<string, mixed>  $filters
     */
    public function createExportForUser(User $user, array $filters): PenilaianTpExport
    {
        $requestedKelasId = isset($filters['kelas_id']) && (int) $filters['kelas_id'] > 0
            ? (int) $filters['kelas_id']
            : null;
        $requestedMapelId = isset($filters['mata_pelajaran_id']) && (int) $filters['mata_pelajaran_id'] > 0
            ? (int) $filters['mata_pelajaran_id']
            : null;
        $requestedTaId = isset($filters['tahun_ajaran_id']) && (int) $filters['tahun_ajaran_id'] > 0
            ? (int) $filters['tahun_ajaran_id']
            : null;

        $auth = $this->scopeService->authorizeSdMapelScope($user, $requestedKelasId, $requestedMapelId, $requestedTaId);
        $filters['tahun_ajaran_id'] = $auth['activeTahunAjaran']?->id ?? $auth['scope']['activeTahunAjaran']?->id;

        return new PenilaianTpExport($filters, $auth['taughtClasses'], $auth['allowedMapels']);
    }

    /**
     * Mengimpor nilai TP dari Excel/CSV khusus kelas & mapel yang diampu.
     *
     * @param  array<string, mixed>  $validated
     * @return array{kelas: Kelas, mapel: MataPelajaran, import: PenilaianTpImport}
     */
    public function importForUser(User $user, array $validated, mixed $file): array
    {
        $requestedTaId = isset($validated['tahun_ajaran_id']) ? (int) $validated['tahun_ajaran_id'] : null;
        $mapelId = (int) $validated['mata_pelajaran_id'];
        $auth = $this->scopeService->authorizeSdMapelScope($user, (int) $validated['kelas_id'], $mapelId, $requestedTaId);

        $kelas = $auth['selectedKelas'];
        $mapel = $auth['allowedMapels']->firstWhere('id', $mapelId) ?? MataPelajaran::query()->findOrFail($mapelId);
        $periode = (string) $validated['periode'];

        $import = new PenilaianTpImport($kelas, $mapel, $periode, $user->no_induk);
        Excel::import($import, $file);

        return compact('kelas', 'mapel', 'import');
    }

    /**
     * Memperbarui deskripsi capaian rapor siswa pada kelas & mapel yang diampu.
     */
    public function updateDeskripsiForUser(
        User $user,
        RekapMapel $rekapMapel,
        ?string $deskripsi,
        ?int $kelasId = null
    ): RekapMapel {
        $auth = $this->scopeService->authorizeRekapMapelForUser($user, $rekapMapel);

        if ($kelasId !== null && $kelasId > 0 && $kelasId !== (int) $auth['selectedKelas']->id) {
            abort(403, 'Anda tidak memiliki akses untuk memperbarui deskripsi siswa kelas ini.');
        }

        return $this->penilaianTpService->updateDeskripsi($rekapMapel, $deskripsi);
    }

    /**
     * Menghasilkan ulang seluruh draf deskripsi capaian rapor otomatis pada kelas & mapel yang diampu.
     *
     * @param  array<string, mixed>  $validated
     * @return array{kelas: Kelas, mapel: MataPelajaran, count: int}
     */
    public function regenerateDeskripsiForUser(User $user, array $validated): array
    {
        $mapelId = (int) $validated['mata_pelajaran_id'];
        $auth = $this->scopeService->authorizeSdMapelScope($user, (int) $validated['kelas_id'], $mapelId);

        $kelas = $auth['selectedKelas'];
        $mapel = $auth['allowedMapels']->firstWhere('id', $mapelId) ?? MataPelajaran::query()->findOrFail($mapelId);
        $count = $this->penilaianTpService->regenerateAllDeskripsi($kelas, $mapel, (string) $validated['periode']);

        return compact('kelas', 'mapel', 'count');
    }
}
