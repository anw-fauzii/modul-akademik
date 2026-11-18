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
     * Memuat data matriks atau rekap Penilaian TP khusus kelas SD binaan Wali Kelas / Pendamping aktif.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getPageData(User $user, array $filters = [], bool $isRekap = false): array
    {
        $requestedKelasId = isset($filters['kelas_id']) && (int) $filters['kelas_id'] > 0
            ? (int) $filters['kelas_id']
            : null;
        $requestedTaId = isset($filters['tahun_ajaran_id']) && (int) $filters['tahun_ajaran_id'] > 0
            ? (int) $filters['tahun_ajaran_id']
            : null;

        $scope = $this->scopeService->resolveScopeContext($user, $requestedKelasId);
        $sdClasses = $this->scopeService->filterSdClasses($scope['assignedClasses']);

        if (! $scope['isEligible']) {
            return [
                ...$scope,
                'isEligible' => false,
                'hasSdClasses' => false,
                'guardReason' => $scope['ineligibleReason'],
            ];
        }

        if ($sdClasses->isEmpty()) {
            return [
                ...$scope,
                'isEligible' => false,
                'hasSdClasses' => false,
                'guardReason' => 'non_sd_only',
            ];
        }

        if ($requestedKelasId !== null && ! $sdClasses->contains('id', $requestedKelasId)) {
            abort(403, 'Anda tidak memiliki akses untuk melihat penilaian kelas ini pada Tahun Ajaran aktif.');
        }

        $activeTaId = (int) ($scope['activeTahunAjaran']?->id ?? 0);
        if ($requestedTaId !== null && $requestedTaId !== $activeTaId) {
            abort(403, 'Penilaian Wali Kelas hanya tersedia untuk Tahun Ajaran aktif.');
        }

        $filters['tahun_ajaran_id'] = $activeTaId;

        $payload = $isRekap
            ? $this->penilaianTpService->getRekapData($filters, $sdClasses)
            : $this->penilaianTpService->getMatrixData($filters, $sdClasses);

        return [
            ...$payload,
            'isEligible' => true,
            'hasSdClasses' => true,
            'tpRoutePrefix' => 'wali-kelas.penilaian-tp',
            'rekapRoutePrefix' => 'wali-kelas.penilaian-rekap',
            'isWaliKelasScope' => true,
            'assignedClasses' => $sdClasses,
            'peranDiKelas' => $scope['peranDiKelas'],
        ];
    }

    /**
     * Menyimpan seluruh nilai grid setelah memverifikasi kepemilikan kelas SD aktif.
     *
     * @param  array<string, mixed>  $validated
     * @return array{kelas: Kelas, mapel: MataPelajaran, result: array{saved_cells: int, updated_students: int}}
     */
    public function saveNilaiGridForUser(User $user, array $validated): array
    {
        $requestedTaId = isset($validated['tahun_ajaran_id']) ? (int) $validated['tahun_ajaran_id'] : null;
        $auth = $this->scopeService->authorizeSdClassScope($user, (int) $validated['kelas_id'], $requestedTaId);

        $kelas = $auth['selectedKelas'];
        $mapel = MataPelajaran::query()->findOrFail((int) $validated['mata_pelajaran_id']);
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
     * Memperbarui bobot Rapor setelah verifikasi kepemilikan kelas SD aktif.
     *
     * @param  array<string, mixed>  $validated
     * @return array{kelas: Kelas, mapel: MataPelajaran}
     */
    public function updateBobotForUser(User $user, array $validated): array
    {
        $requestedTaId = isset($validated['tahun_ajaran_id']) ? (int) $validated['tahun_ajaran_id'] : null;
        $auth = $this->scopeService->authorizeSdClassScope($user, (int) $validated['kelas_id'], $requestedTaId);

        $kelas = $auth['selectedKelas'];
        $mapel = MataPelajaran::query()->findOrFail((int) $validated['mata_pelajaran_id']);

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
     * Menyimpan atau memperbarui kolom Sumatif Bab pada kelas/tingkatan SD binaan aktif.
     *
     * @param  array<string, mixed>  $validated
     */
    public function saveSumatifBabForUser(User $user, array $validated, ?SumatifBab $existing = null): SumatifBab
    {
        $requestedKelasId = isset($validated['kelas_id']) ? (int) $validated['kelas_id'] : null;

        if ($existing !== null) {
            $this->scopeService->authorizeSdClassScope(
                $user,
                null,
                (int) $existing->akademik_master_tahun_ajaran_id,
                (string) $existing->tingkatan_kelas
            );
        }

        $this->scopeService->authorizeSdClassScope(
            $user,
            $requestedKelasId,
            (int) $validated['tahun_ajaran_id'],
            (string) $validated['tingkatan_kelas']
        );

        return $this->penilaianTpService->saveSumatifBab($validated, $existing);
    }

    /**
     * Menghapus kolom Sumatif Bab setelah verifikasi kepemilikan kelas/tingkatan SD aktif.
     */
    public function deleteSumatifBabForUser(User $user, SumatifBab $sumatifBab, ?int $kelasId = null): void
    {
        $this->scopeService->authorizeSdClassScope(
            $user,
            $kelasId,
            (int) $sumatifBab->akademik_master_tahun_ajaran_id,
            (string) $sumatifBab->tingkatan_kelas
        );

        $this->penilaianTpService->deleteSumatifBab($sumatifBab);
    }

    /**
     * Mengubah status keikutsertaan TP pada Rapor untuk tingkatan SD yang diampu.
     */
    public function toggleMasukRaporForUser(
        User $user,
        TujuanPembelajaran $tp,
        string $periode,
        bool $masukRapor,
        ?int $kelasId = null
    ): PeriodeTp {
        $this->scopeService->authorizeSdClassScope(
            $user,
            $kelasId,
            (int) $tp->akademik_master_tahun_ajaran_id,
            (string) $tp->tingkatan_kelas
        );

        return $this->penilaianTpService->toggleMasukRapor($tp, $periode, $masukRapor);
    }

    /**
     * Menambahkan atau memperbarui aspek TP untuk tingkatan SD yang diampu.
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
            $existing->loadMissing('tujuanPembelajaran:id,akademik_master_tahun_ajaran_id,tingkatan_kelas');
            $existingTp = $existing->tujuanPembelajaran;
            $this->scopeService->authorizeSdClassScope(
                $user,
                null,
                (int) ($existingTp?->akademik_master_tahun_ajaran_id ?? 0),
                (string) ($existingTp?->tingkatan_kelas ?? '')
            );
        }

        $this->scopeService->authorizeSdClassScope(
            $user,
            $requestedKelasId,
            (int) $tp->akademik_master_tahun_ajaran_id,
            (string) $tp->tingkatan_kelas
        );

        $aspek = $this->penilaianTpService->saveAspek($tp, $validated, $existing);

        return compact('tp', 'aspek');
    }

    /**
     * Menghapus aspek TP setelah verifikasi kepemilikan kelas/tingkatan SD aktif.
     */
    public function deleteAspekForUser(User $user, AspekTp $aspekTp, ?int $kelasId = null): ?TujuanPembelajaran
    {
        $aspekTp->loadMissing('tujuanPembelajaran');
        $tp = $aspekTp->tujuanPembelajaran;

        $this->scopeService->authorizeSdClassScope(
            $user,
            $kelasId,
            (int) ($tp?->akademik_master_tahun_ajaran_id ?? 0),
            (string) ($tp?->tingkatan_kelas ?? '')
        );

        $this->penilaianTpService->deleteAspek($aspekTp);

        return $tp;
    }

    /**
     * Membuat instance export Excel matriks nilai TP yang terkurung pada kelas SD binaan aktif.
     *
     * @param  array<string, mixed>  $filters
     */
    public function createExportForUser(User $user, array $filters): PenilaianTpExport
    {
        $requestedKelasId = isset($filters['kelas_id']) && (int) $filters['kelas_id'] > 0
            ? (int) $filters['kelas_id']
            : null;
        $requestedTaId = isset($filters['tahun_ajaran_id']) && (int) $filters['tahun_ajaran_id'] > 0
            ? (int) $filters['tahun_ajaran_id']
            : null;

        $auth = $this->scopeService->authorizeSdClassScope($user, $requestedKelasId, $requestedTaId);
        $filters['tahun_ajaran_id'] = $auth['scope']['activeTahunAjaran']?->id;

        return new PenilaianTpExport($filters, $auth['sdClasses']);
    }

    /**
     * Mengimpor nilai TP dari file Excel setelah verifikasi kepemilikan kelas SD aktif.
     *
     * @param  array<string, mixed>  $validated
     * @return array{kelas: Kelas, mapel: MataPelajaran, import: PenilaianTpImport}
     */
    public function importForUser(User $user, array $validated, mixed $file): array
    {
        $requestedTaId = isset($validated['tahun_ajaran_id']) ? (int) $validated['tahun_ajaran_id'] : null;
        $auth = $this->scopeService->authorizeSdClassScope($user, (int) $validated['kelas_id'], $requestedTaId);

        $kelas = $auth['selectedKelas'];
        $mapel = MataPelajaran::query()->findOrFail((int) $validated['mata_pelajaran_id']);
        $periode = (string) $validated['periode'];

        $import = new PenilaianTpImport($kelas, $mapel, $periode, $user->no_induk);
        Excel::import($import, $file);

        return compact('kelas', 'mapel', 'import');
    }

    /**
     * Memperbarui deskripsi capaian rapor siswa pada kelas SD binaan aktif.
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
     * Menghasilkan ulang seluruh deskripsi capaian rapor otomatis pada kelas SD binaan aktif.
     *
     * @param  array<string, mixed>  $validated
     * @return array{kelas: Kelas, mapel: MataPelajaran, count: int}
     */
    public function regenerateDeskripsiForUser(User $user, array $validated): array
    {
        $auth = $this->scopeService->authorizeSdClassScope($user, (int) $validated['kelas_id']);
        $kelas = $auth['selectedKelas'];
        $mapel = MataPelajaran::query()->findOrFail((int) $validated['mata_pelajaran_id']);
        $count = $this->penilaianTpService->regenerateAllDeskripsi($kelas, $mapel, (string) $validated['periode']);

        return compact('kelas', 'mapel', 'count');
    }
}

