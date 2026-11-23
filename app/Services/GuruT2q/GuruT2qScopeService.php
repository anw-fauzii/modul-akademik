<?php

namespace App\Services\GuruT2q;

use App\Models\Admin\DataMaster\AnggotaT2q;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Penilaian\PenilaianFinalisasi;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Models\User;
use App\Services\Admin\Penilaian\PenilaianFinalisasiService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GuruT2qScopeService
{
    public function __construct(
        protected ?PenilaianFinalisasiService $finalisasiService = null
    ) {
        $this->finalisasiService ??= new PenilaianFinalisasiService;
    }

    /**
     * Mengambil konteks bimbingan T2Q aktif milik Guru T2Q (lintas jenjang, berbasis NIPY, anti-N+1).
     *
     * @return array{
     *     isEligible: bool,
     *     ineligibleReason: ?string,
     *     nipy: string,
     *     karyawanProfile: ?Karyawan,
     *     activeTahunAjaran: ?TahunAjaran,
     *     anggotaT2qList: EloquentCollection<int, AnggotaT2q>,
     *     supervisedKelasIds: list<int>,
     *     tingkatOptions: list<string>
     * }
     */
    public function getActiveScopeForUser(User $user, bool $withMembers = true): array
    {
        $nipy = trim((string) ($user->no_induk ?? ''));
        $karyawanProfile = $nipy !== ''
            ? Karyawan::query()->select(['nipy', 'nama_lengkap', 'gelar'])->where('nipy', $nipy)->first()
            : null;
        $activeTahunAjaran = TahunAjaran::query()->active()->orderedForDropdown()->first();

        if ($activeTahunAjaran === null) {
            return $this->emptyScope($nipy, $karyawanProfile, null, 'no_active_tahun_ajaran');
        }

        if ($nipy === '') {
            return $this->emptyScope('', $karyawanProfile, $activeTahunAjaran, 'missing_nipy');
        }

        $query = AnggotaT2q::query()
            ->select([
                'id',
                'anggota_kelas_id',
                'karyawan_nipy',
                'tingkat',
                'pendaftaran',
                'keterangan',
            ])
            ->forTahunAjaran((int) $activeTahunAjaran->id)
            ->forKaryawan($nipy);

        if ($withMembers) {
            $query->withMasterRelations();
        } else {
            $query->with(['anggotaKelas:id,akademik_master_kelas_id']);
        }

        $rawMembers = $query->get();
        if ($rawMembers->isEmpty()) {
            return $this->emptyScope($nipy, $karyawanProfile, $activeTahunAjaran, 'not_assigned_active_year');
        }

        $sortedMembers = new EloquentCollection(
            $rawMembers->sort(static function (AnggotaT2q $a, AnggotaT2q $b): int {
                $tingkatCmp = AnggotaT2q::compareTingkatOrder((string) $a->tingkat, (string) $b->tingkat);
                if ($tingkatCmp !== 0) {
                    return $tingkatCmp;
                }

                $nameA = $a->relationLoaded('anggotaKelas') && $a->anggotaKelas?->relationLoaded('siswa')
                    ? (string) ($a->anggotaKelas->siswa?->nama_lengkap ?? '')
                    : '';
                $nameB = $b->relationLoaded('anggotaKelas') && $b->anggotaKelas?->relationLoaded('siswa')
                    ? (string) ($b->anggotaKelas->siswa?->nama_lengkap ?? '')
                    : '';

                return strnatcasecmp($nameA, $nameB);
            })->values()->all()
        );

        $kelasIdsMap = [];
        $tingkatMap = [];

        foreach ($sortedMembers as $anggota) {
            $kelasId = $anggota->relationLoaded('anggotaKelas')
                ? (int) ($anggota->anggotaKelas?->akademik_master_kelas_id ?? 0)
                : 0;
            if ($kelasId > 0) {
                $kelasIdsMap[$kelasId] = true;
            }

            $tingkat = AnggotaT2q::normalizeTingkat($anggota->tingkat ?? '');
            if ($tingkat !== '') {
                $tingkatMap[$tingkat] = $tingkat;
            }
        }

        $tingkatOptions = array_values($tingkatMap);
        usort($tingkatOptions, static fn (string $a, string $b): int => AnggotaT2q::compareTingkatOrder($a, $b));

        return [
            'isEligible' => true,
            'ineligibleReason' => null,
            'nipy' => $nipy,
            'karyawanProfile' => $karyawanProfile,
            'activeTahunAjaran' => $activeTahunAjaran,
            'anggotaT2qList' => $sortedMembers,
            'supervisedKelasIds' => array_keys($kelasIdsMap),
            'tingkatOptions' => $tingkatOptions,
        ];
    }

    /**
     * Mengambil daftar Tahun Ajaran tempat Guru T2Q memiliki anggota bimbingan untuk filter statistik historis.
     *
     * @return EloquentCollection<int, TahunAjaran>
     */
    public function getHistoricalTahunAjaranOptionsForUser(User $user): EloquentCollection
    {
        $nipy = trim((string) ($user->no_induk ?? ''));
        if ($nipy === '') {
            return new EloquentCollection;
        }

        $taIds = AnggotaT2q::query()
            ->join('akademik_master_anggota_kelas as ak', 'ak.id', '=', 'akademik_master_anggota_t2q.anggota_kelas_id')
            ->join('akademik_master_kelas as k', 'k.id', '=', 'ak.akademik_master_kelas_id')
            ->where('akademik_master_anggota_t2q.karyawan_nipy', $nipy)
            ->distinct()
            ->pluck('k.akademik_master_tahun_ajaran_id')
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->values()
            ->all();

        $activeTa = TahunAjaran::query()->active()->first();
        if ($activeTa !== null && ! in_array((int) $activeTa->id, $taIds, true)) {
            $taIds[] = (int) $activeTa->id;
        }

        if ($taIds === []) {
            return new EloquentCollection;
        }

        return TahunAjaran::query()
            ->whereIn('id', $taIds)
            ->orderedForDropdown()
            ->get();
    }

    /**
     * Merangkum status finalisasi T2Q (Mid & Semester) secara terpadu untuk siswa bimbingan Guru T2Q tanpa N+1.
     * Di database tetap tersimpan per kelas siswa bimbingan agar kompatibel penuh dengan kesiapan cetak rapor.
     *
     * @param  list<int>  $supervisedKelasIds
     * @return array<string, array<string, mixed>>
     */
    public function getFinalisasiSummaryForUser(int $tahunAjaranId, array $supervisedKelasIds): array
    {
        $recordsByScope = ($tahunAjaranId > 0 && $supervisedKelasIds !== [])
            ? PenilaianFinalisasi::query()
                ->where('akademik_master_tahun_ajaran_id', $tahunAjaranId)
                ->where('jenis_komponen', PenilaianFinalisasi::JENIS_T2Q)
                ->whereIn('akademik_master_kelas_id', $supervisedKelasIds)
                ->get()
                ->keyBy('scope_key')
            : collect();

        $summary = [];
        foreach ([PeriodeTp::PERIODE_MID, PeriodeTp::PERIODE_SEMESTER] as $periode) {
            $totalCount = count($supervisedKelasIds);
            $finalCount = 0;
            $latestFinalRecord = null;
            $latestUnlockRecord = null;

            foreach ($supervisedKelasIds as $kelasId) {
                $scopeKey = PenilaianFinalisasi::buildScopeKey(
                    $tahunAjaranId,
                    PenilaianFinalisasi::JENIS_T2Q,
                    (int) $kelasId,
                    null,
                    null,
                    $periode
                );
                /** @var PenilaianFinalisasi|null $rec */
                $rec = $recordsByScope->get($scopeKey);
                if ($rec === null) {
                    continue;
                }

                if ($rec->isFinal()) {
                    $finalCount++;
                    if ($latestFinalRecord === null || ($rec->finalized_at && $rec->finalized_at > $latestFinalRecord->finalized_at)) {
                        $latestFinalRecord = $rec;
                    }
                } elseif ($rec->unlocked_at !== null) {
                    if ($latestUnlockRecord === null || $rec->unlocked_at > $latestUnlockRecord->unlocked_at) {
                        $latestUnlockRecord = $rec;
                    }
                }
            }

            $isFinal = $totalCount > 0 && $finalCount === $totalCount;
            $isPartial = $finalCount > 0 && $finalCount < $totalCount;

            $summary[$periode] = [
                'periode' => $periode,
                'periode_label' => PeriodeTp::PERIODE_OPTIONS[$periode] ?? strtoupper($periode),
                'tahun_ajaran_id' => $tahunAjaranId,
                'total_count' => $totalCount,
                'final_count' => $finalCount,
                'draft_count' => max(0, $totalCount - $finalCount),
                'is_final' => $isFinal,
                'is_partial' => $isPartial,
                'status_label' => $isFinal
                    ? 'FINAL & TERKUNCI'
                    : ($isPartial ? "SEBAGIAN FINAL ({$finalCount}/{$totalCount})" : 'DRAFT (BELUM FINAL)'),
                'finalized_by_name' => $latestFinalRecord?->finalized_by_name,
                'finalized_at' => $latestFinalRecord?->finalized_at,
                'unlocked_by_name' => $latestUnlockRecord?->unlocked_by_name,
                'unlocked_at' => $latestUnlockRecord?->unlocked_at,
                'unlock_reason' => $latestUnlockRecord?->unlock_reason,
                'can_finalize' => $totalCount > 0 && $finalCount < $totalCount,
                'can_unlock' => $finalCount > 0,
            ];
        }

        return $summary;
    }

    /**
     * Memfinalkan program T2Q periode tertentu bagi seluruh kelompok kelas siswa bimbingan Guru T2Q aktif.
     */
    public function finalizeBatchForUser(User $user, string $periode): int
    {
        $scope = $this->getActiveScopeForUser($user, false);
        $activeTa = $scope['activeTahunAjaran'];

        if (! $scope['isEligible'] || ! $activeTa instanceof TahunAjaran || $scope['supervisedKelasIds'] === []) {
            abort(403, 'Anda belum memiliki siswa bimbingan T2Q pada Tahun Ajaran aktif untuk difinalisasi.');
        }

        $normPeriode = $this->finalisasiService->normalizePeriode($periode);
        $count = 0;

        DB::transaction(function () use ($user, $activeTa, $scope, $normPeriode, &$count): void {
            foreach ($scope['supervisedKelasIds'] as $kelasId) {
                $this->finalisasiService->finalize($user, [
                    'jenis_komponen' => PenilaianFinalisasi::JENIS_T2Q,
                    'tahun_ajaran_id' => (int) $activeTa->id,
                    'kelas_id' => (int) $kelasId,
                    'periode' => $normPeriode,
                ]);
                $count++;
            }
        });

        return $count;
    }

    /**
     * Membuka kunci finalisasi program T2Q periode tertentu untuk catatan bimbingan Guru T2Q aktif.
     */
    public function unlockBatchForUser(User $user, string $periode, string $reason): int
    {
        $scope = $this->getActiveScopeForUser($user, false);
        $activeTa = $scope['activeTahunAjaran'];

        if (! $scope['isEligible'] || ! $activeTa instanceof TahunAjaran || $scope['supervisedKelasIds'] === []) {
            abort(403, 'Anda belum memiliki siswa bimbingan T2Q pada Tahun Ajaran aktif.');
        }

        $normPeriode = $this->finalisasiService->normalizePeriode($periode);
        $finalKelasIds = PenilaianFinalisasi::query()
            ->where('akademik_master_tahun_ajaran_id', (int) $activeTa->id)
            ->where('jenis_komponen', PenilaianFinalisasi::JENIS_T2Q)
            ->where('periode', $normPeriode)
            ->where('status', PenilaianFinalisasi::STATUS_FINAL)
            ->whereIn('akademik_master_kelas_id', $scope['supervisedKelasIds'])
            ->pluck('akademik_master_kelas_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        if ($finalKelasIds === []) {
            throw ValidationException::withMessages([
                'finalisasi' => 'Program T2Q pada periode ini belum berstatus Final.',
            ]);
        }

        $count = 0;
        DB::transaction(function () use ($user, $activeTa, $finalKelasIds, $normPeriode, $reason, &$count): void {
            foreach ($finalKelasIds as $kelasId) {
                $this->finalisasiService->unlock($user, [
                    'jenis_komponen' => PenilaianFinalisasi::JENIS_T2Q,
                    'tahun_ajaran_id' => (int) $activeTa->id,
                    'kelas_id' => (int) $kelasId,
                    'periode' => $normPeriode,
                ], $reason);
                $count++;
            }
        });

        return $count;
    }

    /**
     * @return array{
     *     isEligible: bool,
     *     ineligibleReason: string,
     *     nipy: string,
     *     karyawanProfile: ?Karyawan,
     *     activeTahunAjaran: ?TahunAjaran,
     *     anggotaT2qList: EloquentCollection<int, AnggotaT2q>,
     *     supervisedKelasIds: list<int>,
     *     tingkatOptions: list<string>
     * }
     */
    private function emptyScope(
        string $nipy,
        ?Karyawan $karyawanProfile,
        ?TahunAjaran $activeTahunAjaran,
        string $reason
    ): array {
        return [
            'isEligible' => false,
            'ineligibleReason' => $reason,
            'nipy' => $nipy,
            'karyawanProfile' => $karyawanProfile,
            'activeTahunAjaran' => $activeTahunAjaran,
            'anggotaT2qList' => new EloquentCollection,
            'supervisedKelasIds' => [],
            'tingkatOptions' => [],
        ];
    }
}
