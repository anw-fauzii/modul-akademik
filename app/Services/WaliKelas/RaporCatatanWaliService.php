<?php

namespace App\Services\WaliKelas;

use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\AnggotaT2q;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\KomentarRapor;
use App\Models\Admin\Penilaian\CatatanRaporWali;
use App\Models\Admin\Penilaian\Kkm;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Models\Admin\Penilaian\RekapMapel;
use App\Models\Admin\Presensi\PresensiSiswa;
use App\Models\User;
use App\Services\Admin\DataMaster\AnggotaKelasService;
use App\Services\Admin\T2q\T2qRaporResolverService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class RaporCatatanWaliService
{
    public function __construct(
        protected ?WaliKelasScopeService $scopeService = null,
        protected ?AnggotaKelasService $anggotaKelasService = null,
        protected ?T2qRaporResolverService $t2qRaporResolver = null
    ) {
        $this->scopeService ??= new WaliKelasScopeService;
        $this->anggotaKelasService ??= new AnggotaKelasService;
        $this->t2qRaporResolver ??= new T2qRaporResolverService;
    }

    public function normalizePeriode(?string $periode): string
    {
        return strtolower(trim((string) $periode)) === PeriodeTp::PERIODE_SEMESTER
            ? PeriodeTp::PERIODE_SEMESTER
            : PeriodeTp::PERIODE_MID;
    }

    /**
     * Menentukan label peran aktor yang menyimpan catatan (Wali Kelas vs Guru Pendamping vs Super Admin).
     */
    public function resolveActorRoleLabel(User $user, Kelas $kelas): string
    {
        $nipy = trim((string) ($user->no_induk ?? ''));

        if ($nipy !== '' && trim((string) $kelas->pendamping_nipy) === $nipy) {
            return 'Guru Pendamping';
        }

        if ($nipy !== '' && trim((string) $kelas->karyawan_nipy) === $nipy) {
            return 'Wali Kelas';
        }

        if ($user->hasRole('super admin')) {
            return 'Super Admin';
        }

        return 'Wali Kelas';
    }

    /**
     * Mengambil template komentar aktif sesuai jenjang kelas (fallback ke template default bila DB belum di-seed).
     *
     * @return EloquentCollection<int, KomentarRapor>
     */
    public function loadActiveTemplates(?string $jenjang = null): EloquentCollection
    {
        $cleanJenjang = strtoupper(trim((string) ($jenjang ?? '')));

        if (Schema::hasTable('akademik_master_komentar_rapor')) {
            $query = KomentarRapor::query()
                ->select(['id', 'judul', 'kategori', 'jenjang', 'isi_komentar', 'is_active', 'urutan'])
                ->active()
                ->ordered();

            if ($cleanJenjang !== '') {
                $query->where(static function ($sub) use ($cleanJenjang): void {
                    $sub->whereNull('jenjang')
                        ->orWhere('jenjang', '')
                        ->orWhere('jenjang', $cleanJenjang);
                });
            }

            $rows = $query->get();
            if ($rows->isNotEmpty()) {
                return $rows;
            }
        }

        $fallback = new EloquentCollection;
        foreach (KomentarRapor::DEFAULT_TEMPLATES as $tpl) {
            $model = new KomentarRapor;
            $model->setRawAttributes([
                'judul' => $tpl['judul'],
                'kategori' => $tpl['kategori'],
                'jenjang' => $tpl['jenjang'],
                'isi_komentar' => $tpl['isi_komentar'],
                'is_active' => 1,
                'urutan' => $tpl['urutan'],
            ]);
            $fallback->push($model);
        }

        return $fallback;
    }

    /**
     * Memuat peta CatatanRaporWali untuk sekumpulan anggota kelas dalam 1 query (anti-N+1).
     *
     * @param  list<int>  $anggotaIds
     * @return array<int, CatatanRaporWali>
     */
    public function loadCatatanMapForAnggotaIds(array $anggotaIds, string $periode): array
    {
        if ($anggotaIds === [] || ! Schema::hasTable('akademik_rapor_catatan_wali')) {
            return [];
        }

        $normPeriode = $this->normalizePeriode($periode);

        return CatatanRaporWali::query()
            ->select([
                'id',
                'akademik_master_tahun_ajaran_id',
                'anggota_kelas_id',
                'periode',
                'catatan',
                'komentar_rapor_id',
                'updated_by_user_id',
                'updated_by_name',
                'updated_by_role_label',
                'updated_at',
            ])
            ->whereIn('anggota_kelas_id', $anggotaIds)
            ->where('periode', $normPeriode)
            ->get()
            ->keyBy('anggota_kelas_id')
            ->all();
    }

    /**
     * Menghitung peta boolean anggota kelas yang sudah terisi catatan wali kelas pada periode tertentu (1 query).
     *
     * @param  list<int>  $anggotaIds
     * @return array<int, bool>
     */
    public function loadFilledStatusByAnggotaIds(array $anggotaIds, string $periode): array
    {
        if ($anggotaIds === [] || ! Schema::hasTable('akademik_rapor_catatan_wali')) {
            return [];
        }

        $normPeriode = $this->normalizePeriode($periode);

        return CatatanRaporWali::query()
            ->whereIn('anggota_kelas_id', $anggotaIds)
            ->where('periode', $normPeriode)
            ->whereNotNull('catatan')
            ->whereRaw("TRIM(catatan) != ''")
            ->pluck('anggota_kelas_id')
            ->mapWithKeys(static fn ($id): array => [(int) $id => true])
            ->all();
    }

    /**
     * Memuat ruang kerja pengisian komentar/catatan rapor batch 1 kelas untuk Wali Kelas / Guru Pendamping.
     *
     * @return array<string, mixed>
     */
    public function getWorkspaceData(
        User $user,
        ?int $requestedKelasId = null,
        string $periodeInput = PeriodeTp::PERIODE_MID
    ): array {
        $scope = $this->scopeService->resolveScopeContext($user, $requestedKelasId);
        $periode = $this->normalizePeriode($periodeInput);
        $periodeOptions = PeriodeTp::PERIODE_OPTIONS;

        if (! $scope['isEligible'] || ! $scope['selectedKelas'] instanceof Kelas || $scope['activeTahunAjaran'] === null) {
            return [
                ...$scope,
                'periode' => $periode,
                'periodeOptions' => $periodeOptions,
                'isSd' => false,
                'templates' => new EloquentCollection,
                'studentRows' => collect(),
                'stats' => [
                    'total' => 0,
                    'filled' => 0,
                    'empty' => 0,
                    'pct' => 0,
                ],
                'actorRoleLabel' => 'Wali Kelas',
            ];
        }

        /** @var Kelas $selectedKelas */
        $selectedKelas = $scope['selectedKelas'];
        $activeTa = $scope['activeTahunAjaran'];
        $isSd = strtoupper(trim((string) $selectedKelas->jenjang_kelas)) === Kkm::TARGET_UNIT;

        $students = $this->scopeService->sortAnggotaByStudentName(
            $this->anggotaKelasService
                ->buildIndexQuery((int) $activeTa->id, (int) $selectedKelas->id)
                ->with(['siswa:nis,nama_lengkap,nisn,jenis_kelamin,avatar'])
                ->get()
        );

        $anggotaIds = $students->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all();

        $catatanByAnggota = $this->loadCatatanMapForAnggotaIds($anggotaIds, $periode);
        $presensiByAnggota = $this->buildPresensiSummary($anggotaIds);
        $avgRaporByAnggota = ($isSd && $anggotaIds !== [])
            ? $this->buildAvgRaporByAnggota((int) $activeTa->id, $anggotaIds, $periode)
            : [];
        $t2qByAnggota = $this->buildT2qSummaryByAnggota($anggotaIds, (int) $activeTa->id, $periode);

        $filledCount = 0;
        $studentRows = $students->map(function (AnggotaKelas $anggota) use (
            $catatanByAnggota,
            $presensiByAnggota,
            $avgRaporByAnggota,
            $t2qByAnggota,
            &$filledCount
        ): array {
            /** @var CatatanRaporWali|null $catatanModel */
            $catatanModel = $catatanByAnggota[$anggota->id] ?? null;
            $hasCatatan = $catatanModel !== null && trim((string) $catatanModel->catatan) !== '';
            if ($hasCatatan) {
                $filledCount++;
            }

            return [
                'anggota' => $anggota,
                'siswa' => $anggota->siswa,
                'catatan' => $catatanModel,
                'has_catatan' => $hasCatatan,
                'presensi' => $presensiByAnggota[$anggota->id] ?? ['sakit' => 0, 'izin' => 0, 'alpa' => 0],
                'avg_rapor' => $avgRaporByAnggota[$anggota->id] ?? null,
                't2q_summary' => $t2qByAnggota[$anggota->id] ?? null,
            ];
        })->values();

        $total = $studentRows->count();
        $emptyCount = max(0, $total - $filledCount);

        return [
            ...$scope,
            'periode' => $periode,
            'periodeOptions' => $periodeOptions,
            'isSd' => $isSd,
            'isStorageReady' => Schema::hasTable('akademik_rapor_catatan_wali'),
            'templates' => $this->loadActiveTemplates((string) $selectedKelas->jenjang_kelas),
            'studentRows' => $studentRows,
            'stats' => [
                'total' => $total,
                'filled' => $filledCount,
                'empty' => $emptyCount,
                'pct' => $total > 0 ? (int) round(($filledCount / $total) * 100) : 0,
            ],
            'actorRoleLabel' => $this->resolveActorRoleLabel($user, $selectedKelas),
        ];
    }

    /**
     * Menyimpan batch komentar rapor siswa dalam 1 transaksi atomik (O(1) query).
     *
     * @param  array<int|string, ?string>  $catatanMap
     * @param  array<int|string, int|string|null>  $templateIdMap
     * @return array{saved_count: int, filled_count: int, periode_label: string, actor_role_label: string}
     */
    public function saveBatch(
        User $user,
        int $kelasId,
        string $periodeInput,
        array $catatanMap,
        array $templateIdMap = []
    ): array {
        if (! Schema::hasTable('akademik_rapor_catatan_wali')) {
            throw ValidationException::withMessages([
                'catatan' => ['Tabel penyimpanan catatan rapor (akademik_rapor_catatan_wali) belum tersedia di database. Jalankan php artisan migrate terlebih dahulu.'],
            ]);
        }

        $auth = $this->scopeService->authorizeKelasIdForUser($user, $kelasId);
        /** @var Kelas $selectedKelas */
        $selectedKelas = $auth['selectedKelas'];
        $activeTa = $auth['activeTahunAjaran'];

        if ($activeTa === null) {
            abort(403, 'Tahun ajaran aktif tidak ditemukan.');
        }

        $periode = $this->normalizePeriode($periodeInput);
        $periodeLabel = PeriodeTp::PERIODE_OPTIONS[$periode] ?? strtoupper($periode);
        $roleLabel = $this->resolveActorRoleLabel($user, $selectedKelas);

        $validAnggotaIds = AnggotaKelas::query()
            ->where('akademik_master_kelas_id', $selectedKelas->id)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->flip()
            ->all();

        $submittedTemplateIds = array_values(array_unique(array_filter(
            array_map(static fn (mixed $v): int => (int) $v, $templateIdMap),
            static fn (int $id): bool => $id > 0
        )));
        $validTemplateIds = ($submittedTemplateIds !== [] && Schema::hasTable('akademik_master_komentar_rapor'))
            ? KomentarRapor::query()
                ->whereIn('id', $submittedTemplateIds)
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->flip()
                ->all()
            : [];

        $existingByAnggota = CatatanRaporWali::query()
            ->whereIn('anggota_kelas_id', array_keys($validAnggotaIds) ?: [0])
            ->where('periode', $periode)
            ->get()
            ->keyBy('anggota_kelas_id');

        $now = now();
        $upserts = [];
        $filledCount = 0;

        foreach ($catatanMap as $rawAnggotaId => $rawText) {
            $anggotaId = (int) $rawAnggotaId;
            if (! isset($validAnggotaIds[$anggotaId])) {
                continue;
            }

            $cleanText = $this->cleanText($rawText);
            /** @var CatatanRaporWali|null $existing */
            $existing = $existingByAnggota->get($anggotaId);

            if ($cleanText === null && $existing === null) {
                continue;
            }

            if ($cleanText !== null) {
                $filledCount++;
            }

            $rawTemplateId = isset($templateIdMap[$anggotaId]) ? (int) $templateIdMap[$anggotaId] : 0;
            $templateId = ($rawTemplateId > 0 && isset($validTemplateIds[$rawTemplateId]))
                ? $rawTemplateId
                : ($existing?->komentar_rapor_id && isset($validTemplateIds[(int) $existing->komentar_rapor_id])
                    ? (int) $existing->komentar_rapor_id
                    : null);

            $upserts[] = [
                'akademik_master_tahun_ajaran_id' => (int) $activeTa->id,
                'anggota_kelas_id' => $anggotaId,
                'periode' => $periode,
                'catatan' => $cleanText,
                'komentar_rapor_id' => $cleanText !== null ? $templateId : null,
                'updated_by_user_id' => $user->id,
                'updated_by_name' => $user->name,
                'updated_by_role_label' => $roleLabel,
                'created_at' => $existing?->created_at ?? $now,
                'updated_at' => $now,
            ];
        }

        if ($upserts !== []) {
            DB::transaction(static function () use ($upserts): void {
                foreach (array_chunk($upserts, 200) as $chunk) {
                    CatatanRaporWali::query()->upsert(
                        $chunk,
                        ['anggota_kelas_id', 'periode'],
                        [
                            'akademik_master_tahun_ajaran_id',
                            'catatan',
                            'komentar_rapor_id',
                            'updated_by_user_id',
                            'updated_by_name',
                            'updated_by_role_label',
                            'updated_at',
                        ]
                    );
                }
            });
        }

        return [
            'saved_count' => count($upserts),
            'filled_count' => $filledCount,
            'periode_label' => $periodeLabel,
            'actor_role_label' => $roleLabel,
        ];
    }

    /**
     * Menyimpan catatan rapor tunggal untuk 1 anggota kelas (dipanggil dari Detail Perkembangan Siswa).
     */
    public function saveSingle(
        User $user,
        AnggotaKelas $anggotaKelas,
        string $periodeInput,
        ?string $catatan,
        ?int $komentarRaporId = null
    ): ?CatatanRaporWali {
        if (! Schema::hasTable('akademik_rapor_catatan_wali')) {
            throw ValidationException::withMessages([
                'catatan' => ['Tabel penyimpanan catatan rapor (akademik_rapor_catatan_wali) belum tersedia di database. Jalankan php artisan migrate terlebih dahulu.'],
            ]);
        }

        $auth = $this->scopeService->authorizeAnggotaKelasForUser($user, $anggotaKelas);
        /** @var Kelas $selectedKelas */
        $selectedKelas = $auth['selectedKelas'];
        $activeTa = $auth['activeTahunAjaran'];

        if ($activeTa === null) {
            abort(403, 'Tahun ajaran aktif tidak ditemukan.');
        }

        $periode = $this->normalizePeriode($periodeInput);
        $cleanText = $this->cleanText($catatan);
        $roleLabel = $this->resolveActorRoleLabel($user, $selectedKelas);

        $safeTemplateId = ($komentarRaporId !== null && $komentarRaporId > 0 && Schema::hasTable('akademik_master_komentar_rapor'))
            ? (KomentarRapor::query()->whereKey($komentarRaporId)->exists() ? $komentarRaporId : null)
            : null;

        if ($cleanText === null) {
            CatatanRaporWali::query()
                ->where('anggota_kelas_id', $anggotaKelas->id)
                ->where('periode', $periode)
                ->update([
                    'catatan' => null,
                    'komentar_rapor_id' => null,
                    'updated_by_user_id' => $user->id,
                    'updated_by_name' => $user->name,
                    'updated_by_role_label' => $roleLabel,
                ]);

            return null;
        }

        return CatatanRaporWali::query()->updateOrCreate(
            [
                'anggota_kelas_id' => (int) $anggotaKelas->id,
                'periode' => $periode,
            ],
            [
                'akademik_master_tahun_ajaran_id' => (int) $activeTa->id,
                'catatan' => $cleanText,
                'komentar_rapor_id' => $safeTemplateId,
                'updated_by_user_id' => $user->id,
                'updated_by_name' => $user->name,
                'updated_by_role_label' => $roleLabel,
            ]
        );
    }

    /**
     * @param  list<int>  $anggotaIds
     * @return array<int, array{sakit: int, izin: int, alpa: int}>
     */
    private function buildPresensiSummary(array $anggotaIds): array
    {
        if ($anggotaIds === []) {
            return [];
        }

        return PresensiSiswa::query()
            ->selectRaw("
                anggota_kelas_id,
                SUM(CASE WHEN status = 'sakit' THEN 1 ELSE 0 END) as sakit_count,
                SUM(CASE WHEN status = 'izin' THEN 1 ELSE 0 END) as izin_count,
                SUM(CASE WHEN status = 'alpa' THEN 1 ELSE 0 END) as alpa_count
            ")
            ->whereIn('anggota_kelas_id', $anggotaIds)
            ->groupBy('anggota_kelas_id')
            ->get()
            ->mapWithKeys(static fn (PresensiSiswa $row): array => [
                (int) $row->anggota_kelas_id => [
                    'sakit' => (int) ($row->sakit_count ?? 0),
                    'izin' => (int) ($row->izin_count ?? 0),
                    'alpa' => (int) ($row->alpa_count ?? 0),
                ],
            ])
            ->all();
    }

    /**
     * @param  list<int>  $anggotaIds
     * @return array<int, array{avg: float, count: int}>
     */
    private function buildAvgRaporByAnggota(int $tahunAjaranId, array $anggotaIds, string $periode): array
    {
        if ($tahunAjaranId <= 0 || $anggotaIds === []) {
            return [];
        }

        return RekapMapel::query()
            ->selectRaw('anggota_kelas_id, ROUND(AVG(nilai_akhir_rapor), 1) as avg_nilai, COUNT(*) as mapel_count')
            ->where('akademik_master_tahun_ajaran_id', $tahunAjaranId)
            ->whereIn('anggota_kelas_id', $anggotaIds)
            ->where('periode', $periode)
            ->whereNotNull('nilai_akhir_rapor')
            ->groupBy('anggota_kelas_id')
            ->get()
            ->mapWithKeys(static fn (RekapMapel $row): array => [
                (int) $row->anggota_kelas_id => [
                    'avg' => (float) ($row->avg_nilai ?? 0),
                    'count' => (int) ($row->mapel_count ?? 0),
                ],
            ])
            ->all();
    }

    /**
     * @param  list<int>  $anggotaIds
     * @return array<int, array{tingkat: string, tahsin_nilai: ?float, tahfizh_nilai: ?float, catatan_t2q: ?string}>
     */
    private function buildT2qSummaryByAnggota(array $anggotaIds, int $tahunAjaranId, string $periode): array
    {
        if ($anggotaIds === [] || $tahunAjaranId <= 0) {
            return [];
        }

        $t2qRows = AnggotaT2q::query()
            ->select(['id', 'anggota_kelas_id', 'tingkat'])
            ->with(['anggotaKelas:id,akademik_master_kelas_id'])
            ->whereIn('anggota_kelas_id', $anggotaIds)
            ->get();

        if ($t2qRows->isEmpty()) {
            return [];
        }

        $resolved = $this->t2qRaporResolver->resolveForAnggotaT2qList($t2qRows, $tahunAjaranId, $periode);
        $summary = [];

        foreach ($t2qRows as $t2q) {
            $item = $resolved[(int) $t2q->id] ?? null;
            if ($item === null) {
                continue;
            }

            $summary[(int) $t2q->anggota_kelas_id] = [
                'tingkat' => (string) $t2q->tingkat,
                'tahsin_nilai' => $item['tahsin']['nilai'] ?? null,
                'tahfizh_nilai' => $item['tahfizh']['nilai'] ?? null,
                'catatan_t2q' => $item['catatan_t2q'] ?? null,
            ];
        }

        return $summary;
    }

    private function cleanText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $clean = trim($value);

        return $clean !== '' ? $clean : null;
    }
}
