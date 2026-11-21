<?php

namespace App\Services\Admin\Penilaian;

use App\Models\Admin\DataMaster\AnggotaEkstrakurikuler;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\AnggotaT2q;
use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Pelajaran\JadwalPelajaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Admin\Penilaian\Kkm;
use App\Models\Admin\Penilaian\PenilaianFinalisasi;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Models\Admin\Penilaian\RekapMapel;
use App\Models\User;
use App\Services\WaliKelas\WaliKelasScopeService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PenilaianFinalisasiService
{
    public function __construct(
        protected ?WaliKelasScopeService $scopeService = null
    ) {
        $this->scopeService ??= new WaliKelasScopeService;
    }

    public function normalizePeriode(mixed $periode): string
    {
        return strtolower(trim((string) $periode)) === PeriodeTp::PERIODE_SEMESTER
            ? PeriodeTp::PERIODE_SEMESTER
            : PeriodeTp::PERIODE_MID;
    }

    public function resolvePeriodeLabel(string $periode): string
    {
        $norm = $this->normalizePeriode($periode);

        return PeriodeTp::PERIODE_OPTIONS[$norm] ?? strtoupper($norm);
    }

    public function buildScopeKey(
        int $tahunAjaranId,
        string $jenisKomponen,
        ?int $kelasId,
        ?int $mapelId,
        ?int $ekskulId,
        string $periode
    ): string {
        return PenilaianFinalisasi::buildScopeKey(
            $tahunAjaranId,
            $jenisKomponen,
            $kelasId,
            $mapelId,
            $ekskulId,
            $this->normalizePeriode($periode)
        );
    }

    public function findRecord(
        int $tahunAjaranId,
        string $jenisKomponen,
        ?int $kelasId,
        ?int $mapelId,
        ?int $ekskulId,
        string $periode
    ): ?PenilaianFinalisasi {
        $scopeKey = $this->buildScopeKey(
            $tahunAjaranId,
            $jenisKomponen,
            $kelasId,
            $mapelId,
            $ekskulId,
            $periode
        );

        return PenilaianFinalisasi::query()
            ->where('scope_key', $scopeKey)
            ->first();
    }

    public function isFinal(
        int $tahunAjaranId,
        string $jenisKomponen,
        ?int $kelasId,
        ?int $mapelId,
        ?int $ekskulId,
        string $periode
    ): bool {
        $scopeKey = $this->buildScopeKey(
            $tahunAjaranId,
            $jenisKomponen,
            $kelasId,
            $mapelId,
            $ekskulId,
            $periode
        );

        return PenilaianFinalisasi::query()
            ->where('scope_key', $scopeKey)
            ->where('status', PenilaianFinalisasi::STATUS_FINAL)
            ->exists();
    }

    /**
     * Memeriksa apakah user saat ini berhak memfinalkan atau membuka kunci komponen tertentu.
     *
     * @param  array<string, mixed>  $params
     */
    public function canUserManageComponent(User $user, array $params): bool
    {
        if ($user->hasRole('super admin')) {
            return true;
        }

        try {
            $this->authorizeActorForComponent($user, $params);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Memastikan aktor berhak memfinalisasi / membuka kunci komponen sesuai kepemilikan (Opsi C).
     *
     * @param  array<string, mixed>  $params
     */
    public function authorizeActorForComponent(User $user, array $params): void
    {
        if ($user->hasRole('super admin')) {
            return;
        }

        $jenis = trim((string) ($params['jenis_komponen'] ?? ''));
        $tahunAjaranId = (int) ($params['tahun_ajaran_id'] ?? 0);
        $kelasId = isset($params['kelas_id']) && (int) $params['kelas_id'] > 0 ? (int) $params['kelas_id'] : null;
        $mapelId = isset($params['mata_pelajaran_id']) && (int) $params['mata_pelajaran_id'] > 0 ? (int) $params['mata_pelajaran_id'] : null;
        $ekskulId = isset($params['ekstrakurikuler_id']) && (int) $params['ekstrakurikuler_id'] > 0 ? (int) $params['ekstrakurikuler_id'] : null;

        if ($tahunAjaranId <= 0) {
            abort(403, 'Tahun Ajaran tidak valid untuk otorisasi finalisasi.');
        }

        if ($jenis === PenilaianFinalisasi::JENIS_MAPEL) {
            if ($kelasId === null || $mapelId === null) {
                abort(403, 'Konteks kelas dan mata pelajaran harus ditentukan.');
            }

            $this->scopeService->authorizeSdMapelScope(
                $user,
                $kelasId,
                $mapelId,
                $tahunAjaranId,
                null,
                'Hanya guru pengampu mata pelajaran ini (atau Super Admin) yang dapat memfinalkan atau membuka kunci nilai mapel.'
            );

            return;
        }

        if ($jenis === PenilaianFinalisasi::JENIS_EKSKUL_WAJIB || $jenis === PenilaianFinalisasi::JENIS_PRESENSI) {
            if ($kelasId === null) {
                abort(403, 'Konteks kelas harus ditentukan.');
            }

            $scope = $this->scopeService->authorizeKelasIdForUser(
                $user,
                $kelasId,
                'Hanya Wali Kelas / Guru Pendamping kelas ini pada Tahun Ajaran aktif (atau Super Admin) yang dapat mengelola finalisasi ini.'
            );

            if ((int) ($scope['activeTahunAjaran']?->id ?? 0) !== $tahunAjaranId) {
                abort(403, 'Finalisasi oleh Wali Kelas hanya berlaku pada Tahun Ajaran aktif.');
            }

            return;
        }

        if ($jenis === PenilaianFinalisasi::JENIS_EKSKUL_PILIHAN) {
            if ($ekskulId === null) {
                abort(403, 'Ekstrakurikuler pilihan harus ditentukan.');
            }

            $activeTa = TahunAjaran::query()->active()->first();
            $nipy = trim((string) ($user->no_induk ?? ''));
            $ekskul = Ekstrakurikuler::query()
                ->select(['id', 'akademik_master_tahun_ajaran_id', 'karyawan_nipy', 'sifat'])
                ->findOrFail($ekskulId);

            if (
                $activeTa === null
                || (int) $activeTa->id !== $tahunAjaranId
                || (int) $ekskul->akademik_master_tahun_ajaran_id !== (int) $activeTa->id
                || $nipy === ''
                || trim((string) $ekskul->karyawan_nipy) !== $nipy
            ) {
                abort(403, 'Hanya Pembina ekstrakurikuler yang bersangkutan pada Tahun Ajaran aktif (atau Super Admin) yang dapat mengelola finalisasi ini.');
            }

            return;
        }

        if ($jenis === PenilaianFinalisasi::JENIS_T2Q) {
            if ($kelasId === null) {
                abort(403, 'Konteks kelas harus ditentukan untuk finalisasi T2Q.');
            }

            $activeTa = TahunAjaran::query()->active()->first();
            $nipy = trim((string) ($user->no_induk ?? ''));
            $isGuruT2qKelas = $activeTa !== null
                && (int) $activeTa->id === $tahunAjaranId
                && $nipy !== ''
                && AnggotaT2q::query()
                    ->forTahunAjaran($tahunAjaranId)
                    ->forKelas($kelasId)
                    ->forKaryawan($nipy)
                    ->exists();

            if (! $isGuruT2qKelas) {
                abort(403, 'Hanya Guru Pembimbing T2Q kelas ini (atau Super Admin) yang dapat mengelola finalisasi T2Q.');
            }

            return;
        }

        abort(403, 'Jenis komponen finalisasi tidak dikenali.');
    }

    /**
     * Memfinalkan satu komponen penilaian (terpisah antara Mid Semester & Semester).
     *
     * @param  array<string, mixed>  $params
     */
    public function finalize(User $user, array $params): PenilaianFinalisasi
    {
        $normalized = $this->normalizeComponentParams($params);
        $this->authorizeActorForComponent($user, $normalized);

        $actorName = $this->resolveActorDisplayName($user);
        $scopeKey = $this->buildScopeKey(
            $normalized['tahun_ajaran_id'],
            $normalized['jenis_komponen'],
            $normalized['kelas_id'],
            $normalized['mata_pelajaran_id'],
            $normalized['ekstrakurikuler_id'],
            $normalized['periode']
        );

        return DB::transaction(function () use ($normalized, $user, $actorName, $scopeKey): PenilaianFinalisasi {
            $record = PenilaianFinalisasi::query()->updateOrCreate(
                ['scope_key' => $scopeKey],
                [
                    'akademik_master_tahun_ajaran_id' => $normalized['tahun_ajaran_id'],
                    'jenis_komponen' => $normalized['jenis_komponen'],
                    'akademik_master_kelas_id' => $normalized['kelas_id'],
                    'akademik_pelajaran_mata_pelajaran_id' => $normalized['mata_pelajaran_id'],
                    'akademik_master_ekstrakuler_id' => $normalized['ekstrakurikuler_id'],
                    'periode' => $normalized['periode'],
                    'status' => PenilaianFinalisasi::STATUS_FINAL,
                    'finalized_by_user_id' => $user->id,
                    'finalized_by_name' => $actorName,
                    'finalized_at' => now(),
                ]
            );

            return $record->refresh();
        });
    }

    /**
     * Membuka kunci komponen penilaian yang sudah difinalkan beserta catatan alasan wajib.
     *
     * @param  array<string, mixed>  $params
     */
    public function unlock(User $user, array $params, string $reason): PenilaianFinalisasi
    {
        $normalized = $this->normalizeComponentParams($params);
        $this->authorizeActorForComponent($user, $normalized);

        $cleanReason = trim($reason);
        if (mb_strlen($cleanReason) < 5) {
            throw ValidationException::withMessages([
                'unlock_reason' => 'Alasan buka kunci wajib diisi minimal 5 karakter sebagai catatan audit.',
            ]);
        }

        $record = $this->findRecord(
            $normalized['tahun_ajaran_id'],
            $normalized['jenis_komponen'],
            $normalized['kelas_id'],
            $normalized['mata_pelajaran_id'],
            $normalized['ekstrakurikuler_id'],
            $normalized['periode']
        );

        if ($record === null || ! $record->isFinal()) {
            throw ValidationException::withMessages([
                'finalisasi' => 'Komponen ini belum berstatus Final sehingga tidak perlu dibuka kuncinya.',
            ]);
        }

        $actorName = $this->resolveActorDisplayName($user);

        return DB::transaction(function () use ($record, $user, $actorName, $cleanReason): PenilaianFinalisasi {
            $record->update([
                'status' => PenilaianFinalisasi::STATUS_DRAFT,
                'unlocked_by_user_id' => $user->id,
                'unlocked_by_name' => $actorName,
                'unlocked_at' => now(),
                'unlock_reason' => $cleanReason,
            ]);

            return $record->refresh();
        });
    }

    /**
     * Membangun payload status finalisasi siap pakai di Blade View untuk satu komponen.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function getStatusPayloadForComponent(array $params, ?User $user = null): array
    {
        $normalized = $this->normalizeComponentParams($params);
        $record = $this->findRecord(
            $normalized['tahun_ajaran_id'],
            $normalized['jenis_komponen'],
            $normalized['kelas_id'],
            $normalized['mata_pelajaran_id'],
            $normalized['ekstrakurikuler_id'],
            $normalized['periode']
        );

        $isFinal = $record?->isFinal() ?? false;
        $canManage = $user !== null ? $this->canUserManageComponent($user, $normalized) : false;

        return [
            'scope_key' => $this->buildScopeKey(
                $normalized['tahun_ajaran_id'],
                $normalized['jenis_komponen'],
                $normalized['kelas_id'],
                $normalized['mata_pelajaran_id'],
                $normalized['ekstrakurikuler_id'],
                $normalized['periode']
            ),
            'jenis_komponen' => $normalized['jenis_komponen'],
            'jenis_label' => PenilaianFinalisasi::JENIS_OPTIONS[$normalized['jenis_komponen']] ?? $normalized['jenis_komponen'],
            'tahun_ajaran_id' => $normalized['tahun_ajaran_id'],
            'kelas_id' => $normalized['kelas_id'],
            'mata_pelajaran_id' => $normalized['mata_pelajaran_id'],
            'ekstrakurikuler_id' => $normalized['ekstrakurikuler_id'],
            'periode' => $normalized['periode'],
            'periode_label' => $this->resolvePeriodeLabel($normalized['periode']),
            'status' => $isFinal ? PenilaianFinalisasi::STATUS_FINAL : PenilaianFinalisasi::STATUS_DRAFT,
            'is_final' => $isFinal,
            'record' => $record,
            'finalized_by_name' => $record?->finalized_by_name,
            'finalized_at' => $record?->finalized_at,
            'unlocked_by_name' => $record?->unlocked_by_name,
            'unlocked_at' => $record?->unlocked_at,
            'unlock_reason' => $record?->unlock_reason,
            'can_finalize' => $canManage && ! $isFinal,
            'can_unlock' => $canManage && $isFinal,
        ];
    }

    /**
     * Memastikan nilai grid / deskripsi mapel pada kelas & periode belum difinalkan.
     */
    public function assertMapelGridEditable(Kelas $kelas, MataPelajaran $mapel, string $periode): void
    {
        $this->assertMapelGridEditableByIds(
            (int) $kelas->akademik_master_tahun_ajaran_id,
            (int) $kelas->id,
            (int) $mapel->id,
            $periode,
            $kelas->nama_kelas,
            $mapel->nama_mata_pelajaran
        );
    }

    public function assertMapelGridEditableByIds(
        int $tahunAjaranId,
        ?int $kelasId,
        int $mapelId,
        string $periode,
        ?string $kelasName = null,
        ?string $mapelName = null
    ): void {
        if ($tahunAjaranId <= 0 || $kelasId === null || $kelasId <= 0 || $mapelId <= 0) {
            return;
        }

        $record = $this->findRecord(
            $tahunAjaranId,
            PenilaianFinalisasi::JENIS_MAPEL,
            $kelasId,
            $mapelId,
            null,
            $periode
        );

        if ($record !== null && $record->isFinal()) {
            $periodeLabel = $this->resolvePeriodeLabel($periode);
            $target = trim(implode(' ', array_filter([$mapelName, $kelasName ? "Kelas {$kelasName}" : null])));
            $by = $record->finalized_by_name ? " oleh {$record->finalized_by_name}" : '';

            throw ValidationException::withMessages([
                'finalisasi' => "Nilai {$target} periode {$periodeLabel} sudah DIFINALISASI{$by}. Buka kunci terlebih dahulu jika ingin mengubah nilai atau deskripsi.",
            ]);
        }
    }

    /**
     * Memastikan struktur TP / Aspek / Sumatif Bab pada satu tingkatan & periode belum difinalkan di kelas mana pun dalam tingkatan tersebut.
     */
    public function assertMapelStructureEditable(
        int $tahunAjaranId,
        int $mapelId,
        string $tingkatanKelas,
        string $periode
    ): void {
        if ($tahunAjaranId <= 0 || $mapelId <= 0 || trim($tingkatanKelas) === '') {
            return;
        }

        $kelasIds = Kelas::query()
            ->where('akademik_master_tahun_ajaran_id', $tahunAjaranId)
            ->where('tingkatan_kelas', trim($tingkatanKelas))
            ->pluck('nama_kelas', 'id');

        if ($kelasIds->isEmpty()) {
            return;
        }

        $locked = PenilaianFinalisasi::query()
            ->where('akademik_master_tahun_ajaran_id', $tahunAjaranId)
            ->where('jenis_komponen', PenilaianFinalisasi::JENIS_MAPEL)
            ->where('akademik_pelajaran_mata_pelajaran_id', $mapelId)
            ->where('periode', $this->normalizePeriode($periode))
            ->where('status', PenilaianFinalisasi::STATUS_FINAL)
            ->whereIn('akademik_master_kelas_id', $kelasIds->keys()->all())
            ->first();

        if ($locked !== null) {
            $kelasName = $kelasIds->get($locked->akademik_master_kelas_id) ?? 'tingkatan '.$tingkatanKelas;
            $periodeLabel = $this->resolvePeriodeLabel($periode);
            $by = $locked->finalized_by_name ? " oleh {$locked->finalized_by_name}" : '';

            throw ValidationException::withMessages([
                'finalisasi' => "Struktur Aspek TP / Sumatif Bab periode {$periodeLabel} tidak dapat diubah karena Kelas {$kelasName} sudah DIFINALISASI{$by}. Buka kunci kelas tersebut terlebih dahulu.",
            ]);
        }
    }

    /**
     * Memastikan bobot rapor (memengaruhi Mid & Semester sekaligus) belum difinalkan di periode mana pun pada tingkatan tersebut.
     */
    public function assertMapelStructureEditableAnyPeriode(
        int $tahunAjaranId,
        int $mapelId,
        string $tingkatanKelas
    ): void {
        foreach ([PeriodeTp::PERIODE_MID, PeriodeTp::PERIODE_SEMESTER] as $p) {
            $this->assertMapelStructureEditable($tahunAjaranId, $mapelId, $tingkatanKelas, $p);
        }
    }

    public function assertEkskulWajibEditable(Kelas $kelas, string $periode): void
    {
        $normPeriode = $this->normalizePeriode($periode);
        $record = $this->findRecord(
            (int) $kelas->akademik_master_tahun_ajaran_id,
            PenilaianFinalisasi::JENIS_EKSKUL_WAJIB,
            (int) $kelas->id,
            null,
            null,
            $normPeriode
        );

        if ($record !== null && $record->isFinal()) {
            $periodeLabel = $this->resolvePeriodeLabel($normPeriode);
            $by = $record->finalized_by_name ? " oleh {$record->finalized_by_name}" : '';

            throw ValidationException::withMessages([
                'finalisasi' => "Penilaian Ekstrakurikuler Wajib (Pramuka) Kelas {$kelas->nama_kelas} periode {$periodeLabel} sudah DIFINALISASI{$by}. Buka kunci terlebih dahulu untuk mengubah nilai.",
            ]);
        }

        if ($normPeriode !== PeriodeTp::PERIODE_SEMESTER) {
            $semesterRecord = $this->findRecord(
                (int) $kelas->akademik_master_tahun_ajaran_id,
                PenilaianFinalisasi::JENIS_EKSKUL_WAJIB,
                (int) $kelas->id,
                null,
                null,
                PeriodeTp::PERIODE_SEMESTER
            );
            if ($semesterRecord !== null && $semesterRecord->isFinal()) {
                throw ValidationException::withMessages([
                    'finalisasi' => "Penilaian Pramuka Kelas {$kelas->nama_kelas} periode Semester sudah DIFINALISASI sehingga data tidak dapat diubah.",
                ]);
            }
        }
    }

    public function assertEkskulPilihanEditable(Ekstrakurikuler $ekstrakurikuler, string $periode): void
    {
        $normPeriode = $this->normalizePeriode($periode);
        $record = $this->findRecord(
            (int) $ekstrakurikuler->akademik_master_tahun_ajaran_id,
            PenilaianFinalisasi::JENIS_EKSKUL_PILIHAN,
            null,
            null,
            (int) $ekstrakurikuler->id,
            $normPeriode
        );

        if ($record !== null && $record->isFinal()) {
            $periodeLabel = $this->resolvePeriodeLabel($normPeriode);
            $by = $record->finalized_by_name ? " oleh {$record->finalized_by_name}" : '';

            throw ValidationException::withMessages([
                'finalisasi' => "Penilaian Ekstrakurikuler {$ekstrakurikuler->nama_ekstrakurikuler} periode {$periodeLabel} sudah DIFINALISASI{$by}. Buka kunci terlebih dahulu untuk mengubah nilai.",
            ]);
        }

        if ($normPeriode !== PeriodeTp::PERIODE_SEMESTER) {
            $semesterRecord = $this->findRecord(
                (int) $ekstrakurikuler->akademik_master_tahun_ajaran_id,
                PenilaianFinalisasi::JENIS_EKSKUL_PILIHAN,
                null,
                null,
                (int) $ekstrakurikuler->id,
                PeriodeTp::PERIODE_SEMESTER
            );
            if ($semesterRecord !== null && $semesterRecord->isFinal()) {
                throw ValidationException::withMessages([
                    'finalisasi' => "Penilaian Ekstrakurikuler {$ekstrakurikuler->nama_ekstrakurikuler} periode Semester sudah DIFINALISASI sehingga data tidak dapat diubah.",
                ]);
            }
        }
    }

    /**
     * Penjagaan presensi per tanggal:
     * - Jika Semester sudah Final -> seluruh tanggal diblokir.
     * - Jika Mid Semester sudah Final -> tanggal pada/sebelum tanggal finalisasi Mid diblokir, sedangkan tanggal baru pasca-Mid tetap bisa diinput untuk Semester.
     */
    public function assertPresensiEditable(int $tahunAjaranId, int $kelasId, string $tanggal): void
    {
        $this->assertDateAwareComponentEditable(
            $tahunAjaranId,
            $kelasId,
            PenilaianFinalisasi::JENIS_PRESENSI,
            $tanggal,
            'Presensi siswa'
        );
    }

    public function assertYaumiyahEditable(int $tahunAjaranId, int $kelasId, string $tanggal): void
    {
        $this->assertDateAwareComponentEditable(
            $tahunAjaranId,
            $kelasId,
            PenilaianFinalisasi::JENIS_T2Q,
            $tanggal,
            'Catatan Yaumiyah T2Q'
        );
    }

    public function assertAnggotaT2qEditable(int $tahunAjaranId, int $kelasId, bool $forSemesterUpdate = false): void
    {
        if ($tahunAjaranId <= 0 || $kelasId <= 0) {
            return;
        }

        $semRecord = $this->findRecord(
            $tahunAjaranId,
            PenilaianFinalisasi::JENIS_T2Q,
            $kelasId,
            null,
            null,
            PeriodeTp::PERIODE_SEMESTER
        );

        if ($semRecord !== null && $semRecord->isFinal()) {
            $by = $semRecord->finalized_by_name ? " oleh {$semRecord->finalized_by_name}" : '';
            throw ValidationException::withMessages([
                'finalisasi' => "Data T2Q kelas ini untuk periode Semester sudah DIFINALISASI{$by}. Buka kunci terlebih dahulu untuk mengubah data Anggota T2Q.",
            ]);
        }

        if (! $forSemesterUpdate) {
            $midRecord = $this->findRecord(
                $tahunAjaranId,
                PenilaianFinalisasi::JENIS_T2Q,
                $kelasId,
                null,
                null,
                PeriodeTp::PERIODE_MID
            );

            if ($midRecord !== null && $midRecord->isFinal()) {
                $by = $midRecord->finalized_by_name ? " oleh {$midRecord->finalized_by_name}" : '';
                throw ValidationException::withMessages([
                    'finalisasi' => "Data T2Q kelas ini untuk periode Mid Semester sudah DIFINALISASI{$by}. Buka kunci Mid Semester jika ingin merevisi, atau centang opsi pembaruan periode Semester.",
                ]);
            }
        }
    }

    /**
     * Menghitung kesiapan cetak PDF Nilai Rapor suatu kelas pada periode tertentu (Opsi C penuh & anti-N+1).
     *
     * @return array{
     *     periode: string,
     *     periode_label: string,
     *     is_sd: bool,
     *     is_ready: bool,
     *     total_components: int,
     *     finalized_count: int,
     *     pending_count: int,
     *     progress_pct: int,
     *     groups: array<string, list<array<string, mixed>>>,
     *     components: list<array<string, mixed>>,
     *     pending_components: list<array<string, mixed>>,
     *     final_mapel_ids: list<int>
     * }
     */
    public function getClassReadiness(Kelas $kelas, string $periode = PeriodeTp::PERIODE_MID, ?User $user = null): array
    {
        $normPeriode = $this->normalizePeriode($periode);
        $kelas->loadMissing([
            'tahunAjaran:id,nama_tahun_ajaran,semester',
            'waliKelas:nipy,nama_lengkap,gelar',
            'pendamping:nipy,nama_lengkap,gelar',
        ]);

        $taId = (int) $kelas->akademik_master_tahun_ajaran_id;
        $isSd = strtoupper(trim((string) $kelas->jenjang_kelas)) === Kkm::TARGET_UNIT;
        $userNipy = trim((string) ($user?->no_induk ?? ''));
        $isAdmin = $user?->hasRole('super admin') ?? false;
        $isWaliKelasOrPendamping = $user !== null && (
            $isAdmin
            || ($userNipy !== '' && (
                trim((string) $kelas->karyawan_nipy) === $userNipy
                || trim((string) $kelas->pendamping_nipy) === $userNipy
            ))
        );

        $anggotaIds = AnggotaKelas::query()
            ->where('akademik_master_kelas_id', $kelas->id)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        // 1. Mata Pelajaran Intrakurikuler (khusus SD): Jadwal aktif UNION RekapMapel bernilai pada periode terkait
        $mapelComponents = [];
        $scheduleOwnersByMapel = [];
        $scheduleNipysByMapel = [];
        $scheduledMapelIds = [];

        if ($isSd) {
            $jadwalRows = JadwalPelajaran::query()
                ->select(['akademik_pelajaran_mata_pelajaran_id', 'karyawan_nipy'])
                ->with(['guru:nipy,nama_lengkap,gelar'])
                ->where('akademik_master_tahun_ajaran_id', $taId)
                ->where('akademik_master_kelas_id', $kelas->id)
                ->where('status', true)
                ->get();

            foreach ($jadwalRows as $row) {
                $mId = (int) $row->akademik_pelajaran_mata_pelajaran_id;
                if ($mId <= 0) {
                    continue;
                }
                $scheduledMapelIds[$mId] = true;
                $nipy = trim((string) $row->karyawan_nipy);
                if ($nipy !== '') {
                    $scheduleNipysByMapel[$mId][$nipy] = true;
                    $name = $row->guru?->nama_gelar ?? $nipy;
                    $scheduleOwnersByMapel[$mId][$nipy] = $name;
                }
            }

            $scoredMapelIds = $anggotaIds !== []
                ? RekapMapel::query()
                    ->where('akademik_master_tahun_ajaran_id', $taId)
                    ->whereIn('anggota_kelas_id', $anggotaIds)
                    ->where('periode', $normPeriode)
                    ->whereNotNull('nilai_akhir_rapor')
                    ->distinct()
                    ->pluck('akademik_pelajaran_mata_pelajaran_id')
                    ->map(static fn ($id): int => (int) $id)
                    ->all()
                : [];

            $requiredMapelIds = array_values(array_unique(array_merge(array_keys($scheduledMapelIds), $scoredMapelIds)));
            $requiredMapels = $requiredMapelIds !== []
                ? MataPelajaran::query()
                    ->select(['id', 'singkatan', 'nama_mata_pelajaran', 'urutan'])
                    ->whereIn('id', $requiredMapelIds)
                    ->where('unit', Kkm::TARGET_UNIT)
                    ->where('status', true)
                    ->ordered()
                    ->get()
                : new EloquentCollection;
        } else {
            $requiredMapels = new EloquentCollection;
        }

        // 2. Ekskul Pilihan yang diikuti siswa kelas ini
        $pilihanEkskuls = new EloquentCollection;
        if ($anggotaIds !== []) {
            $ekskulIdsInKelas = AnggotaEkstrakurikuler::query()
                ->whereIn('anggota_kelas_id', $anggotaIds)
                ->distinct()
                ->pluck('akademik_master_ekstrakuler_id')
                ->map(static fn ($id): int => (int) $id)
                ->filter(static fn (int $id): bool => $id > 0)
                ->values()
                ->all();

            if ($ekskulIdsInKelas !== []) {
                $pilihanEkskuls = Ekstrakurikuler::query()
                    ->select(['id', 'nama_ekstrakurikuler', 'karyawan_nipy', 'sifat'])
                    ->with(['pembina:nipy,nama_lengkap,gelar'])
                    ->whereIn('id', $ekskulIdsInKelas)
                    ->where('sifat', '!=', Ekstrakurikuler::SIFAT_WAJIB)
                    ->orderBy('nama_ekstrakurikuler')
                    ->get();
            }
        }

        // 3. Program T2Q (aktif jika minimal 1 siswa di kelas terdaftar pada AnggotaT2q)
        $t2qRows = $anggotaIds !== []
            ? AnggotaT2q::query()
                ->select(['id', 'karyawan_nipy'])
                ->with(['guruT2q:nipy,nama_lengkap,gelar'])
                ->whereIn('anggota_kelas_id', $anggotaIds)
                ->get()
            : new EloquentCollection;
        $hasT2q = $t2qRows->isNotEmpty();
        $t2qOwners = [];
        $t2qNipys = [];
        foreach ($t2qRows as $row) {
            $nipy = trim((string) $row->karyawan_nipy);
            if ($nipy !== '') {
                $t2qNipys[$nipy] = true;
                $t2qOwners[$nipy] = $row->guruT2q?->nama_gelar ?? $nipy;
            }
        }

        // 4. Batch-load seluruh record PenilaianFinalisasi yang relevan untuk TA & periode ini (1 query!)
        $pilihanEkskulIds = $pilihanEkskuls->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $finalisasiRecords = PenilaianFinalisasi::query()
            ->where('akademik_master_tahun_ajaran_id', $taId)
            ->where('periode', $normPeriode)
            ->where(function ($query) use ($kelas, $pilihanEkskulIds): void {
                $query->where('akademik_master_kelas_id', $kelas->id);
                if ($pilihanEkskulIds !== []) {
                    $query->orWhere(function ($q) use ($pilihanEkskulIds): void {
                        $q->where('jenis_komponen', PenilaianFinalisasi::JENIS_EKSKUL_PILIHAN)
                            ->whereIn('akademik_master_ekstrakuler_id', $pilihanEkskulIds);
                    });
                }
            })
            ->get()
            ->keyBy('scope_key');

        $waliOwnerNames = array_values(array_filter([
            $kelas->waliKelas?->nama_gelar,
            $kelas->pendamping?->nama_gelar,
        ]));
        $waliOwnerLabel = $waliOwnerNames !== [] ? implode(' / ', $waliOwnerNames) : 'Wali Kelas';
        $waliNipys = array_values(array_filter([
            trim((string) $kelas->karyawan_nipy),
            trim((string) $kelas->pendamping_nipy),
        ]));

        // Rakit item Mapel
        $finalMapelIds = [];
        if ($isSd) {
            if ($requiredMapels->isEmpty()) {
                $mapelComponents[] = [
                    'scope_key' => null,
                    'jenis_komponen' => PenilaianFinalisasi::JENIS_MAPEL,
                    'jenis_label' => 'Mata Pelajaran',
                    'nama' => 'Belum ada mata pelajaran dijadwalkan atau dinilai',
                    'owner_label' => 'Admin / Jadwal Pelajaran',
                    'owner_nipys' => [],
                    'is_owned_by_user' => false,
                    'is_final' => false,
                    'status' => PenilaianFinalisasi::STATUS_DRAFT,
                    'record' => null,
                    'finalized_by_name' => null,
                    'finalized_at' => null,
                    'unlocked_by_name' => null,
                    'unlocked_at' => null,
                    'unlock_reason' => null,
                    'tahun_ajaran_id' => $taId,
                    'kelas_id' => (int) $kelas->id,
                    'mata_pelajaran_id' => null,
                    'ekstrakurikuler_id' => null,
                    'periode' => $normPeriode,
                    'is_placeholder' => true,
                ];
            } else {
                foreach ($requiredMapels as $mp) {
                    $scopeKey = $this->buildScopeKey(
                        $taId,
                        PenilaianFinalisasi::JENIS_MAPEL,
                        (int) $kelas->id,
                        (int) $mp->id,
                        null,
                        $normPeriode
                    );
                    /** @var PenilaianFinalisasi|null $rec */
                    $rec = $finalisasiRecords->get($scopeKey);
                    $isFinal = $rec?->isFinal() ?? false;
                    if ($isFinal) {
                        $finalMapelIds[] = (int) $mp->id;
                    }
                    $nipys = array_keys($scheduleNipysByMapel[$mp->id] ?? []);
                    $owners = array_values($scheduleOwnersByMapel[$mp->id] ?? []);
                    $isOwned = $isAdmin || ($userNipy !== '' && isset($scheduleNipysByMapel[$mp->id][$userNipy]));

                    $mapelComponents[] = [
                        'scope_key' => $scopeKey,
                        'jenis_komponen' => PenilaianFinalisasi::JENIS_MAPEL,
                        'jenis_label' => 'Mata Pelajaran',
                        'nama' => '['.($mp->singkatan ?: 'MP').'] '.$mp->nama_mata_pelajaran,
                        'owner_label' => $owners !== [] ? implode(', ', $owners) : 'Belum dijadwalkan',
                        'owner_nipys' => $nipys,
                        'is_owned_by_user' => $isOwned,
                        'is_final' => $isFinal,
                        'status' => $isFinal ? PenilaianFinalisasi::STATUS_FINAL : PenilaianFinalisasi::STATUS_DRAFT,
                        'record' => $rec,
                        'finalized_by_name' => $rec?->finalized_by_name,
                        'finalized_at' => $rec?->finalized_at,
                        'unlocked_by_name' => $rec?->unlocked_by_name,
                        'unlocked_at' => $rec?->unlocked_at,
                        'unlock_reason' => $rec?->unlock_reason,
                        'tahun_ajaran_id' => $taId,
                        'kelas_id' => (int) $kelas->id,
                        'mata_pelajaran_id' => (int) $mp->id,
                        'ekstrakurikuler_id' => null,
                        'periode' => $normPeriode,
                        'is_placeholder' => false,
                    ];
                }
            }
        }

        // Rakit item Ekskul Wajib (Pramuka)
        $wajibKey = $this->buildScopeKey(
            $taId,
            PenilaianFinalisasi::JENIS_EKSKUL_WAJIB,
            (int) $kelas->id,
            null,
            null,
            $normPeriode
        );
        /** @var PenilaianFinalisasi|null $wajibRec */
        $wajibRec = $finalisasiRecords->get($wajibKey);
        $wajibIsFinal = $wajibRec?->isFinal() ?? false;
        $ekskulWajibComponents = [[
            'scope_key' => $wajibKey,
            'jenis_komponen' => PenilaianFinalisasi::JENIS_EKSKUL_WAJIB,
            'jenis_label' => 'Ekskul Wajib',
            'nama' => 'Pramuka (Wajib)',
            'owner_label' => $waliOwnerLabel,
            'owner_nipys' => $waliNipys,
            'is_owned_by_user' => $isWaliKelasOrPendamping,
            'is_final' => $wajibIsFinal,
            'status' => $wajibIsFinal ? PenilaianFinalisasi::STATUS_FINAL : PenilaianFinalisasi::STATUS_DRAFT,
            'record' => $wajibRec,
            'finalized_by_name' => $wajibRec?->finalized_by_name,
            'finalized_at' => $wajibRec?->finalized_at,
            'unlocked_by_name' => $wajibRec?->unlocked_by_name,
            'unlocked_at' => $wajibRec?->unlocked_at,
            'unlock_reason' => $wajibRec?->unlock_reason,
            'tahun_ajaran_id' => $taId,
            'kelas_id' => (int) $kelas->id,
            'mata_pelajaran_id' => null,
            'ekstrakurikuler_id' => null,
            'periode' => $normPeriode,
            'is_placeholder' => false,
        ]];

        // Rakit item Ekskul Pilihan
        $ekskulPilihanComponents = [];
        foreach ($pilihanEkskuls as $eks) {
            $eksKey = $this->buildScopeKey(
                $taId,
                PenilaianFinalisasi::JENIS_EKSKUL_PILIHAN,
                null,
                null,
                (int) $eks->id,
                $normPeriode
            );
            /** @var PenilaianFinalisasi|null $eksRec */
            $eksRec = $finalisasiRecords->get($eksKey);
            $eksIsFinal = $eksRec?->isFinal() ?? false;
            $pembinaNipy = trim((string) $eks->karyawan_nipy);
            $isPembina = $isAdmin || ($userNipy !== '' && $pembinaNipy !== '' && $userNipy === $pembinaNipy);

            $ekskulPilihanComponents[] = [
                'scope_key' => $eksKey,
                'jenis_komponen' => PenilaianFinalisasi::JENIS_EKSKUL_PILIHAN,
                'jenis_label' => 'Ekskul Pilihan',
                'nama' => $eks->nama_ekstrakurikuler,
                'owner_label' => $eks->pembina?->nama_gelar ?? ($pembinaNipy !== '' ? $pembinaNipy : 'Pembina belum ditentukan'),
                'owner_nipys' => $pembinaNipy !== '' ? [$pembinaNipy] : [],
                'is_owned_by_user' => $isPembina,
                'is_final' => $eksIsFinal,
                'status' => $eksIsFinal ? PenilaianFinalisasi::STATUS_FINAL : PenilaianFinalisasi::STATUS_DRAFT,
                'record' => $eksRec,
                'finalized_by_name' => $eksRec?->finalized_by_name,
                'finalized_at' => $eksRec?->finalized_at,
                'unlocked_by_name' => $eksRec?->unlocked_by_name,
                'unlocked_at' => $eksRec?->unlocked_at,
                'unlock_reason' => $eksRec?->unlock_reason,
                'tahun_ajaran_id' => $taId,
                'kelas_id' => null,
                'mata_pelajaran_id' => null,
                'ekstrakurikuler_id' => (int) $eks->id,
                'periode' => $normPeriode,
                'is_placeholder' => false,
            ];
        }

        // Rakit item T2Q (jika kelas memiliki anggota T2Q)
        $t2qComponents = [];
        if ($hasT2q) {
            $t2qKey = $this->buildScopeKey(
                $taId,
                PenilaianFinalisasi::JENIS_T2Q,
                (int) $kelas->id,
                null,
                null,
                $normPeriode
            );
            /** @var PenilaianFinalisasi|null $t2qRec */
            $t2qRec = $finalisasiRecords->get($t2qKey);
            $t2qIsFinal = $t2qRec?->isFinal() ?? false;
            $t2qNipyList = array_keys($t2qNipys);
            $isGuruT2q = $isAdmin || ($userNipy !== '' && isset($t2qNipys[$userNipy]));

            $t2qComponents[] = [
                'scope_key' => $t2qKey,
                'jenis_komponen' => PenilaianFinalisasi::JENIS_T2Q,
                'jenis_label' => 'Program T2Q',
                'nama' => 'Program T2Q ('.$t2qRows->count().' peserta)',
                'owner_label' => $t2qOwners !== [] ? implode(', ', array_values($t2qOwners)) : 'Guru T2Q / Admin',
                'owner_nipys' => $t2qNipyList,
                'is_owned_by_user' => $isGuruT2q,
                'is_final' => $t2qIsFinal,
                'status' => $t2qIsFinal ? PenilaianFinalisasi::STATUS_FINAL : PenilaianFinalisasi::STATUS_DRAFT,
                'record' => $t2qRec,
                'finalized_by_name' => $t2qRec?->finalized_by_name,
                'finalized_at' => $t2qRec?->finalized_at,
                'unlocked_by_name' => $t2qRec?->unlocked_by_name,
                'unlocked_at' => $t2qRec?->unlocked_at,
                'unlock_reason' => $t2qRec?->unlock_reason,
                'tahun_ajaran_id' => $taId,
                'kelas_id' => (int) $kelas->id,
                'mata_pelajaran_id' => null,
                'ekstrakurikuler_id' => null,
                'periode' => $normPeriode,
                'is_placeholder' => false,
            ];
        }

        // Rakit item Presensi
        $presensiKey = $this->buildScopeKey(
            $taId,
            PenilaianFinalisasi::JENIS_PRESENSI,
            (int) $kelas->id,
            null,
            null,
            $normPeriode
        );
        /** @var PenilaianFinalisasi|null $presensiRec */
        $presensiRec = $finalisasiRecords->get($presensiKey);
        $presensiIsFinal = $presensiRec?->isFinal() ?? false;
        $presensiComponents = [[
            'scope_key' => $presensiKey,
            'jenis_komponen' => PenilaianFinalisasi::JENIS_PRESENSI,
            'jenis_label' => 'Presensi',
            'nama' => 'Ketidakhadiran & Presensi Siswa',
            'owner_label' => $waliOwnerLabel,
            'owner_nipys' => $waliNipys,
            'is_owned_by_user' => $isWaliKelasOrPendamping,
            'is_final' => $presensiIsFinal,
            'status' => $presensiIsFinal ? PenilaianFinalisasi::STATUS_FINAL : PenilaianFinalisasi::STATUS_DRAFT,
            'record' => $presensiRec,
            'finalized_by_name' => $presensiRec?->finalized_by_name,
            'finalized_at' => $presensiRec?->finalized_at,
            'unlocked_by_name' => $presensiRec?->unlocked_by_name,
            'unlocked_at' => $presensiRec?->unlocked_at,
            'unlock_reason' => $presensiRec?->unlock_reason,
            'tahun_ajaran_id' => $taId,
            'kelas_id' => (int) $kelas->id,
            'mata_pelajaran_id' => null,
            'ekstrakurikuler_id' => null,
            'periode' => $normPeriode,
            'is_placeholder' => false,
        ]];

        $allComponents = array_merge(
            $mapelComponents,
            $ekskulWajibComponents,
            $ekskulPilihanComponents,
            $t2qComponents,
            $presensiComponents
        );

        $totalComponents = count($allComponents);
        $finalizedComponents = array_values(array_filter($allComponents, static fn (array $c): bool => $c['is_final'] === true));
        $pendingComponents = array_values(array_filter($allComponents, static fn (array $c): bool => $c['is_final'] !== true));
        $finalizedCount = count($finalizedComponents);
        $pendingCount = count($pendingComponents);

        return [
            'periode' => $normPeriode,
            'periode_label' => $this->resolvePeriodeLabel($normPeriode),
            'is_sd' => $isSd,
            'is_ready' => $totalComponents > 0 && $pendingCount === 0,
            'total_components' => $totalComponents,
            'finalized_count' => $finalizedCount,
            'pending_count' => $pendingCount,
            'progress_pct' => $totalComponents > 0 ? (int) round(($finalizedCount / $totalComponents) * 100) : 0,
            'groups' => [
                'mapel' => $mapelComponents,
                'ekskul_wajib' => $ekskulWajibComponents,
                'ekskul_pilihan' => $ekskulPilihanComponents,
                't2q' => $t2qComponents,
                'presensi' => $presensiComponents,
            ],
            'components' => $allComponents,
            'pending_components' => $pendingComponents,
            'final_mapel_ids' => $finalMapelIds,
        ];
    }

    /**
     * Memblokir cetak PDF Nilai Rapor secara keras jika ada komponen Opsi C yang belum berstatus Final.
     */
    public function assertClassReadyForPrint(Kelas $kelas, string $periode = PeriodeTp::PERIODE_MID): void
    {
        $readiness = $this->getClassReadiness($kelas, $periode);

        if ($readiness['is_ready']) {
            return;
        }

        $pendingNames = array_map(
            static fn (array $c): string => ($c['jenis_label'] ?? '').': '.($c['nama'] ?? ''),
            array_slice($readiness['pending_components'], 0, 5)
        );
        $extra = $readiness['pending_count'] > 5
            ? ' (+'.($readiness['pending_count'] - 5).' komponen lainnya)'
            : '';

        abort(
            422,
            "Cetak Nilai Rapor Kelas {$kelas->nama_kelas} periode {$readiness['periode_label']} diblokir sementara: "
            ."masih ada {$readiness['pending_count']} dari {$readiness['total_components']} komponen yang belum difinalkan ("
            .implode('; ', $pendingNames).$extra.').'
        );
    }

    /**
     * Penjagaan tanggal untuk Presensi & Yaumiyah T2Q:
     * - Semester Final => memblokir seluruh tanggal di kelas & tahun ajaran tersebut.
     * - Mid Semester Final => memblokir tanggal <= tanggal finalisasi Mid (pasca-Mid tetap bisa diinput untuk Semester).
     */
    private function assertDateAwareComponentEditable(
        int $tahunAjaranId,
        int $kelasId,
        string $jenisKomponen,
        string $tanggal,
        string $label
    ): void {
        if ($tahunAjaranId <= 0 || $kelasId <= 0) {
            return;
        }

        $semRecord = $this->findRecord(
            $tahunAjaranId,
            $jenisKomponen,
            $kelasId,
            null,
            null,
            PeriodeTp::PERIODE_SEMESTER
        );

        if ($semRecord !== null && $semRecord->isFinal()) {
            $by = $semRecord->finalized_by_name ? " oleh {$semRecord->finalized_by_name}" : '';
            throw ValidationException::withMessages([
                'finalisasi' => "{$label} kelas ini untuk periode Semester sudah DIFINALISASI{$by}. Buka kunci terlebih dahulu untuk mengubah data.",
            ]);
        }

        $midRecord = $this->findRecord(
            $tahunAjaranId,
            $jenisKomponen,
            $kelasId,
            null,
            null,
            PeriodeTp::PERIODE_MID
        );

        if ($midRecord !== null && $midRecord->isFinal()) {
            $cutoffDate = $midRecord->finalized_at?->toDateString();
            $targetDate = $this->safeDateString($tanggal);

            if ($cutoffDate !== null && $targetDate <= $cutoffDate) {
                $by = $midRecord->finalized_by_name ? " oleh {$midRecord->finalized_by_name}" : '';
                throw ValidationException::withMessages([
                    'finalisasi' => "{$label} tanggal {$targetDate} masuk rentang Mid Semester yang sudah DIFINALISASI{$by} pada {$cutoffDate}. Buka kunci Mid Semester jika ingin merevisi tanggal tersebut.",
                ]);
            }
        }
    }

    private function safeDateString(string $rawDate): string
    {
        try {
            return Carbon::parse($rawDate)->toDateString();
        } catch (\Throwable) {
            return now()->toDateString();
        }
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{
     *     tahun_ajaran_id: int,
     *     jenis_komponen: string,
     *     kelas_id: ?int,
     *     mata_pelajaran_id: ?int,
     *     ekstrakurikuler_id: ?int,
     *     periode: string
     * }
     */
    private function normalizeComponentParams(array $params): array
    {
        return [
            'tahun_ajaran_id' => (int) ($params['tahun_ajaran_id'] ?? 0),
            'jenis_komponen' => trim((string) ($params['jenis_komponen'] ?? '')),
            'kelas_id' => isset($params['kelas_id']) && (int) $params['kelas_id'] > 0 ? (int) $params['kelas_id'] : null,
            'mata_pelajaran_id' => isset($params['mata_pelajaran_id']) && (int) $params['mata_pelajaran_id'] > 0 ? (int) $params['mata_pelajaran_id'] : null,
            'ekstrakurikuler_id' => isset($params['ekstrakurikuler_id']) && (int) $params['ekstrakurikuler_id'] > 0 ? (int) $params['ekstrakurikuler_id'] : null,
            'periode' => $this->normalizePeriode($params['periode'] ?? PeriodeTp::PERIODE_MID),
        ];
    }

    private function resolveActorDisplayName(User $user): string
    {
        $nipy = trim((string) ($user->no_induk ?? ''));
        if ($nipy !== '') {
            $karyawan = Karyawan::query()
                ->select(['nipy', 'nama_lengkap', 'gelar'])
                ->where('nipy', $nipy)
                ->first();

            if ($karyawan instanceof Karyawan) {
                return $karyawan->nama_gelar;
            }
        }

        return trim((string) ($user->name ?? 'Pengguna'));
    }
}
