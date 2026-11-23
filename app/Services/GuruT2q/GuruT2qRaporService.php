<?php

namespace App\Services\GuruT2q;

use App\Models\Admin\DataMaster\AnggotaT2q;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Penilaian\Kkm;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Models\Admin\T2q\RaporT2q;
use App\Models\User;
use App\Services\Admin\T2q\T2qRaporResolverService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GuruT2qRaporService
{
    public function __construct(
        protected ?GuruT2qScopeService $scopeService = null,
        protected ?T2qRaporResolverService $resolverService = null,
    ) {
        $this->scopeService ??= new GuruT2qScopeService;
        $this->resolverService ??= new T2qRaporResolverService;
    }

    /**
     * Memuat halaman kerja Rekap & Deskripsi Rapor T2Q khusus siswa bimbingan Guru T2Q pada jenjang SD.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getWorkspaceData(User $user, array $filters = []): array
    {
        $scope = $this->scopeService->getActiveScopeForUser($user, true);
        $activeTa = $scope['activeTahunAjaran'];
        $nipy = (string) ($scope['nipy'] ?? '');
        $karyawanProfile = $scope['karyawanProfile'] ?? null;
        $periode = $this->resolverService->normalizePeriode((string) ($filters['periode'] ?? PeriodeTp::PERIODE_MID));

        if (! $scope['isEligible'] || ! $activeTa instanceof TahunAjaran) {
            return [
                'isEligible' => false,
                'ineligibleReason' => $scope['ineligibleReason'],
                'nipy' => $nipy,
                'karyawanProfile' => $karyawanProfile,
                'activeTahunAjaran' => $activeTa,
                'periode' => $periode,
                'periodeOptions' => PeriodeTp::PERIODE_OPTIONS,
                'predikatRanges' => RaporT2q::predikatRanges(),
                'tingkatTabs' => [],
                'selectedTingkat' => '',
                'rows' => collect(),
                'nonSdCount' => 0,
                't2qFinalisasi' => [],
                'stats' => $this->emptyStats(),
            ];
        }

        $sdMembers = $this->filterSdMembers($scope['anggotaT2qList']);
        $nonSdCount = max(0, $scope['anggotaT2qList']->count() - $sdMembers->count());

        $tingkatTabs = [];
        $sdKelasIdsMap = [];

        foreach ($sdMembers as $member) {
            $tKey = AnggotaT2q::normalizeTingkat($member->tingkat);
            if ($tKey !== '') {
                $tingkatTabs[$tKey] = ($tingkatTabs[$tKey] ?? 0) + 1;
            }

            $kId = (int) ($member->anggotaKelas?->akademik_master_kelas_id ?? 0);
            if ($kId > 0) {
                $sdKelasIdsMap[$kId] = true;
            }
        }

        uksort($tingkatTabs, static fn (string $a, string $b): int => AnggotaT2q::compareTingkatOrder($a, $b));

        $requestedTingkat = AnggotaT2q::normalizeTingkat($filters['tingkat'] ?? '');
        $selectedTingkat = ($requestedTingkat !== '' && isset($tingkatTabs[$requestedTingkat]))
            ? $requestedTingkat
            : '';

        $resolvedByAnggota = $this->resolverService->resolveForAnggotaT2qList(
            $sdMembers,
            (int) $activeTa->id,
            $periode
        );

        $rows = [];
        $tahsinReady = 0;
        $tahfizhReady = 0;
        $deskripsiSavedCount = 0;
        $lockedCount = 0;
        $unlockedCount = 0;

        foreach ($sdMembers as $member) {
            $t2qId = (int) $member->id;
            $resolved = $resolvedByAnggota[$t2qId] ?? null;
            if ($resolved === null) {
                continue;
            }

            $tahsin = $resolved['tahsin'];
            $tahfizh = $resolved['tahfizh'];
            $isLocked = (bool) ($resolved['is_locked'] ?? false);

            if ($tahsin['has_data'] ?? false) {
                $tahsinReady++;
            }
            if ($tahfizh['has_data'] ?? false) {
                $tahfizhReady++;
            }
            if ($resolved['saved'] instanceof RaporT2q && (filled($resolved['saved']->tahsin_deskripsi) || filled($resolved['saved']->tahfizh_deskripsi))) {
                $deskripsiSavedCount++;
            }
            if ($isLocked) {
                $lockedCount++;
            } else {
                $unlockedCount++;
            }

            if ($selectedTingkat !== '' && AnggotaT2q::normalizeTingkat($member->tingkat) !== $selectedTingkat) {
                continue;
            }

            $rows[] = [
                'anggota' => $member,
                'siswa' => $member->anggotaKelas?->siswa,
                'saved' => $resolved['saved'],
                'is_locked' => $isLocked,
                'mid_cutoff_date' => $resolved['mid_cutoff_date'],
                'tahsin' => $tahsin,
                'tahfizh' => $tahfizh,
            ];
        }

        $t2qFinalisasi = $this->scopeService->getFinalisasiSummaryForUser(
            (int) $activeTa->id,
            array_keys($sdKelasIdsMap)
        );

        return [
            'isEligible' => true,
            'ineligibleReason' => $sdMembers->isEmpty() ? 'no_sd_students' : null,
            'nipy' => $nipy,
            'karyawanProfile' => $karyawanProfile,
            'activeTahunAjaran' => $activeTa,
            'periode' => $periode,
            'periodeOptions' => PeriodeTp::PERIODE_OPTIONS,
            'predikatRanges' => RaporT2q::predikatRanges(),
            'tingkatTabs' => $tingkatTabs,
            'selectedTingkat' => $selectedTingkat,
            'rows' => collect($rows),
            'nonSdCount' => $nonSdCount,
            't2qFinalisasi' => $t2qFinalisasi,
            'stats' => [
                'total_sd' => $sdMembers->count(),
                'tahsin_ready' => $tahsinReady,
                'tahfizh_ready' => $tahfizhReady,
                'deskripsi_saved' => $deskripsiSavedCount,
                'locked_count' => $lockedCount,
                'unlocked_count' => $unlockedCount,
            ],
        ];
    }

    /**
     * Menyimpan batch rekap nilai terakhir, aspek pengamatan, dan deskripsi rapor T2Q (khusus siswa SD bimbingan Guru T2Q).
     *
     * @param  array<string, mixed>  $validated
     * @return array{saved_count: int, periode_label: string}
     */
    public function saveBatch(User $user, array $validated): array
    {
        $scope = $this->scopeService->getActiveScopeForUser($user, true);
        $activeTa = $scope['activeTahunAjaran'];

        if (! $scope['isEligible'] || ! $activeTa instanceof TahunAjaran) {
            abort(403, 'Anda belum memiliki siswa bimbingan T2Q pada Tahun Ajaran aktif.');
        }

        $periode = $this->resolverService->normalizePeriode((string) ($validated['periode'] ?? PeriodeTp::PERIODE_MID));
        $periodeLabel = PeriodeTp::PERIODE_OPTIONS[$periode] ?? strtoupper($periode);
        $sdMembers = $this->filterSdMembers($scope['anggotaT2qList']);

        if ($sdMembers->isEmpty()) {
            throw ValidationException::withMessages([
                'rapor_t2q' => 'Belum ada siswa bimbingan jenjang SD pada Tahun Ajaran aktif.',
            ]);
        }

        $sdMap = $sdMembers->keyBy('id');
        $resolvedByAnggota = $this->resolverService->resolveForAnggotaT2qList($sdMembers, (int) $activeTa->id, $periode);

        $tahsinInputs = is_array($validated['tahsin'] ?? null) ? $validated['tahsin'] : [];
        $tahfizhInputs = is_array($validated['tahfizh'] ?? null) ? $validated['tahfizh'] : [];
        $rowIds = array_values(array_unique(array_map('intval', array_merge(array_keys($tahsinInputs), array_keys($tahfizhInputs)))));

        foreach ($rowIds as $submittedId) {
            if (! $sdMap->has($submittedId)) {
                abort(403, 'Anda hanya dapat menyimpan rekap rapor siswa T2Q jenjang SD bimbingan Anda.');
            }
        }

        $hasAnyUnlocked = collect($resolvedByAnggota)->contains(static fn (array $r): bool => ! ($r['is_locked'] ?? false));
        if (! $hasAnyUnlocked) {
            throw ValidationException::withMessages([
                'finalisasi' => "Rekap Rapor T2Q periode {$periodeLabel} sudah DIFINALISASI dan dikunci. Buka kunci terlebih dahulu untuk merevisi deskripsi.",
            ]);
        }

        $now = now();
        $upserts = [];

        foreach ($rowIds as $t2qId) {
            $resolved = $resolvedByAnggota[$t2qId] ?? null;
            if ($resolved === null) {
                continue;
            }

            if ($resolved['is_locked'] ?? false) {
                $nama = $sdMap->get($t2qId)?->anggotaKelas?->siswa?->nama_lengkap ?? "Anggota #{$t2qId}";
                throw ValidationException::withMessages([
                    'finalisasi' => "Data T2Q {$nama} untuk periode {$periodeLabel} sudah difinalisasi sehingga tidak dapat diubah.",
                ]);
            }

            $tahsinRow = $resolved['tahsin'];
            $tahfizhRow = $resolved['tahfizh'];
            /** @var RaporT2q|null $saved */
            $saved = $resolved['saved'];

            $tIn = is_array($tahsinInputs[$t2qId] ?? null) ? $tahsinInputs[$t2qId] : [];
            $fIn = is_array($tahfizhInputs[$t2qId] ?? null) ? $tahfizhInputs[$t2qId] : [];

            $tKeunggulan = array_key_exists('keunggulan', $tIn)
                ? $this->cleanOptionalText($tIn['keunggulan'])
                : $this->cleanOptionalText($tahsinRow['keunggulan']);
            $tPeningkatan = array_key_exists('peningkatan', $tIn)
                ? $this->cleanOptionalText($tIn['peningkatan'])
                : $this->cleanOptionalText($tahsinRow['peningkatan']);
            $tDeskripsi = $this->cleanOptionalText($tIn['deskripsi'] ?? null)
                ?? RaporT2q::buildAutoDeskripsi(RaporT2q::MODE_TAHSIN, $tahsinRow['nilai'], $tahsinRow['materi'], $tKeunggulan, $tPeningkatan);

            $fKeunggulan = array_key_exists('keunggulan', $fIn)
                ? $this->cleanOptionalText($fIn['keunggulan'])
                : $this->cleanOptionalText($tahfizhRow['keunggulan']);
            $fPeningkatan = array_key_exists('peningkatan', $fIn)
                ? $this->cleanOptionalText($fIn['peningkatan'])
                : $this->cleanOptionalText($tahfizhRow['peningkatan']);
            $fDeskripsi = $this->cleanOptionalText($fIn['deskripsi'] ?? null)
                ?? RaporT2q::buildAutoDeskripsi(RaporT2q::MODE_TAHFIZH, $tahfizhRow['nilai'], $tahfizhRow['materi'], $fKeunggulan, $fPeningkatan);

            $upserts[] = $this->resolverService->buildUpsertRow(
                (int) $activeTa->id,
                $t2qId,
                $periode,
                $tahsinRow,
                $tahfizhRow,
                $tKeunggulan,
                $tPeningkatan,
                $tDeskripsi,
                $fKeunggulan,
                $fPeningkatan,
                $fDeskripsi,
                $user->id,
                $saved,
                $now
            );
        }

        if ($upserts !== []) {
            DB::transaction(fn () => $this->resolverService->upsertRows($upserts));
        }

        return [
            'saved_count' => count($upserts),
            'periode_label' => $periodeLabel,
        ];
    }

    /**
     * Menarik ulang seluruh nilai terakhir Yaumiyah dan menyusun ulang draf deskripsi otomatis bagi siswa SD yang belum terkunci.
     *
     * @return array{generated_count: int, periode_label: string}
     */
    public function regenerateAllDrafts(User $user, string $periodeInput, ?string $tingkatFilter = null): array
    {
        $scope = $this->scopeService->getActiveScopeForUser($user, true);
        $activeTa = $scope['activeTahunAjaran'];

        if (! $scope['isEligible'] || ! $activeTa instanceof TahunAjaran) {
            abort(403, 'Anda belum memiliki siswa bimbingan T2Q pada Tahun Ajaran aktif.');
        }

        $periode = $this->resolverService->normalizePeriode($periodeInput);
        $periodeLabel = PeriodeTp::PERIODE_OPTIONS[$periode] ?? strtoupper($periode);
        $targetMembers = $this->filterSdMembers($scope['anggotaT2qList'], $tingkatFilter);

        if ($targetMembers->isEmpty()) {
            throw ValidationException::withMessages([
                'rapor_t2q' => 'Tidak ada siswa SD pada filter ini untuk disinkronkan.',
            ]);
        }

        $resolvedMap = $this->resolverService->resolveForAnggotaT2qList($targetMembers, (int) $activeTa->id, $periode);
        $now = now();
        $upserts = [];

        foreach ($targetMembers as $member) {
            $t2qId = (int) $member->id;
            $resolved = $resolvedMap[$t2qId] ?? null;
            if ($resolved === null || ($resolved['is_locked'] ?? false)) {
                continue;
            }

            $tahsin = $resolved['tahsin'];
            $tahfizh = $resolved['tahfizh'];
            if (! ($tahsin['has_data'] ?? false) && ! ($tahfizh['has_data'] ?? false)) {
                continue;
            }

            /** @var RaporT2q|null $saved */
            $saved = $resolved['saved'];

            $upserts[] = $this->resolverService->buildUpsertRow(
                (int) $activeTa->id,
                $t2qId,
                $periode,
                $tahsin,
                $tahfizh,
                $tahsin['keunggulan'],
                $tahsin['peningkatan'],
                $tahsin['auto_deskripsi'],
                $tahfizh['keunggulan'],
                $tahfizh['peningkatan'],
                $tahfizh['auto_deskripsi'],
                $user->id,
                $saved,
                $now
            );
        }

        if ($upserts === []) {
            $anyLocked = collect($resolvedMap)->contains(static fn (array $r): bool => (bool) ($r['is_locked'] ?? false));
            if ($anyLocked) {
                throw ValidationException::withMessages([
                    'finalisasi' => "Seluruh data siswa SD pada periode {$periodeLabel} sudah difinalisasi dan terkunci.",
                ]);
            }

            return ['generated_count' => 0, 'periode_label' => $periodeLabel];
        }

        DB::transaction(fn () => $this->resolverService->upsertRows($upserts));

        return [
            'generated_count' => count($upserts),
            'periode_label' => $periodeLabel,
        ];
    }

    /**
     * Menyaring daftar anggota T2Q bimbingan khusus jenjang SD (dan opsional per tingkat T2Q).
     *
     * @param  EloquentCollection<int, AnggotaT2q>  $members
     * @return EloquentCollection<int, AnggotaT2q>
     */
    private function filterSdMembers(EloquentCollection $members, ?string $tingkatFilter = null): EloquentCollection
    {
        $normTingkat = AnggotaT2q::normalizeTingkat($tingkatFilter);

        return new EloquentCollection(
            $members->filter(static function (AnggotaT2q $member) use ($normTingkat): bool {
                $jenjang = strtoupper(trim((string) ($member->anggotaKelas?->kelas?->jenjang_kelas ?? '')));
                if ($jenjang !== Kkm::TARGET_UNIT) {
                    return false;
                }

                if ($normTingkat !== '') {
                    return AnggotaT2q::normalizeTingkat($member->tingkat) === $normTingkat;
                }

                return true;
            })->values()->all()
        );
    }

    private function cleanOptionalText(mixed $raw): ?string
    {
        $text = trim(preg_replace('/\s+/', ' ', (string) ($raw ?? '')) ?? '');

        return $text !== '' ? $text : null;
    }

    /**
     * @return array<string, int>
     */
    private function emptyStats(): array
    {
        return [
            'total_sd' => 0,
            'tahsin_ready' => 0,
            'tahfizh_ready' => 0,
            'deskripsi_saved' => 0,
            'locked_count' => 0,
            'unlocked_count' => 0,
        ];
    }
}
