<?php

namespace App\Services\WaliKelas;

use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Pelajaran\JadwalPelajaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Admin\Penilaian\Kkm;
use App\Models\Admin\Penilaian\RekapMapel;
use App\Models\Admin\Presensi\PresensiSiswa;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class WaliKelasScopeService
{
    /**
     * Daftar kolom minimal yang dipilih saat memuat kelas binaan Wali Kelas / Pendamping.
     *
     * @var list<string>
     */
    private const KELAS_SELECT_COLUMNS = [
        'id',
        'akademik_master_tahun_ajaran_id',
        'karyawan_nipy',
        'pendamping_nipy',
        'tingkatan_kelas',
        'nama_kelas',
        'jenjang_kelas',
        'romawi',
    ];

    /**
     * Relasi eager loading untuk kelas binaan Wali Kelas / Pendamping guna mencegah N+1.
     *
     * @var list<string>
     */
    private const KELAS_EAGER_RELATIONS = [
        'tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
        'waliKelas:nipy,nama_lengkap,gelar',
        'pendamping:nipy,nama_lengkap,gelar',
    ];

    /**
     * Mengambil daftar kelas pada tahun ajaran aktif di mana user bertugas sebagai Wali Kelas atau Pendamping.
     */
    public function getAssignedClassesForUser(User $user, ?TahunAjaran $activeTahunAjaran = null): EloquentCollection
    {
        $tahunAjaran = $activeTahunAjaran ?? TahunAjaran::query()->active()->first();
        $nipy = trim((string) ($user->no_induk ?? ''));

        if ($tahunAjaran === null || $nipy === '') {
            return new EloquentCollection;
        }

        $classes = Kelas::query()
            ->select(self::KELAS_SELECT_COLUMNS)
            ->with(self::KELAS_EAGER_RELATIONS)
            ->withCount('anggotaKelas')
            ->where('akademik_master_tahun_ajaran_id', $tahunAjaran->id)
            ->where(function (Builder $query) use ($nipy): void {
                $query->where('karyawan_nipy', $nipy)
                    ->orWhere('pendamping_nipy', $nipy);
            })
            ->get();

        // Prioritaskan kelas tempat user menjadi Wali Kelas utama, lalu urutkan jenjang & nama kelas.
        return new EloquentCollection(
            $classes->sort(function (Kelas $a, Kelas $b) use ($nipy): int {
                $aPrimary = trim((string) $a->karyawan_nipy) === $nipy ? 0 : 1;
                $bPrimary = trim((string) $b->karyawan_nipy) === $nipy ? 0 : 1;
                if ($aPrimary !== $bPrimary) {
                    return $aPrimary <=> $bPrimary;
                }

                $jenjangCmp = Kelas::compareJenjangOrder((string) $a->jenjang_kelas, (string) $b->jenjang_kelas);
                if ($jenjangCmp !== 0) {
                    return $jenjangCmp;
                }

                return strnatcasecmp((string) $a->nama_kelas, (string) $b->nama_kelas);
            })->values()->all()
        );
    }

    /**
     * Mengambil seluruh kelas lintas Tahun Ajaran di mana user bertugas sebagai Wali Kelas atau Pendamping tanpa N+1.
     *
     * @return EloquentCollection<int, Kelas>
     */
    public function getAllAssignedClassesForUser(User $user): EloquentCollection
    {
        $nipy = trim((string) ($user->no_induk ?? ''));
        if ($nipy === '') {
            return new EloquentCollection;
        }

        $classes = Kelas::query()
            ->select(self::KELAS_SELECT_COLUMNS)
            ->with(self::KELAS_EAGER_RELATIONS)
            ->withCount('anggotaKelas')
            ->where(static function (Builder $query) use ($nipy): void {
                $query->where('karyawan_nipy', $nipy)
                    ->orWhere('pendamping_nipy', $nipy);
            })
            ->get();

        return new EloquentCollection(
            $classes->sort(static function (Kelas $a, Kelas $b) use ($nipy): int {
                $aActive = ($a->relationLoaded('tahunAjaran') && $a->tahunAjaran?->is_active) ? 0 : 1;
                $bActive = ($b->relationLoaded('tahunAjaran') && $b->tahunAjaran?->is_active) ? 0 : 1;
                if ($aActive !== $bActive) {
                    return $aActive <=> $bActive;
                }

                $aTaName = $a->relationLoaded('tahunAjaran') ? (string) ($a->tahunAjaran?->nama_tahun_ajaran ?? '') : '';
                $bTaName = $b->relationLoaded('tahunAjaran') ? (string) ($b->tahunAjaran?->nama_tahun_ajaran ?? '') : '';
                $taNameCmp = strnatcasecmp($bTaName, $aTaName);
                if ($taNameCmp !== 0) {
                    return $taNameCmp;
                }

                $aPrimary = trim((string) $a->karyawan_nipy) === $nipy ? 0 : 1;
                $bPrimary = trim((string) $b->karyawan_nipy) === $nipy ? 0 : 1;
                if ($aPrimary !== $bPrimary) {
                    return $aPrimary <=> $bPrimary;
                }

                $jenjangCmp = Kelas::compareJenjangOrder((string) $a->jenjang_kelas, (string) $b->jenjang_kelas);
                if ($jenjangCmp !== 0) {
                    return $jenjangCmp;
                }

                return strnatcasecmp((string) $a->nama_kelas, (string) $b->nama_kelas);
            })->values()->all()
        );
    }

    /**
     * Menentukan peran user di dalam kelas (Wali Kelas, Guru Pendamping, atau Guru Pengampu opsional).
     */
    public function resolveRoleInKelas(Kelas $kelas, string $nipy, bool $asGuruPengampu = false): string
    {
        $normalizedNipy = trim($nipy);

        if ($normalizedNipy !== '' && trim((string) $kelas->karyawan_nipy) === $normalizedNipy) {
            return 'Wali Kelas';
        }

        if ($normalizedNipy !== '' && trim((string) $kelas->pendamping_nipy) === $normalizedNipy) {
            return 'Guru Pendamping';
        }

        return $asGuruPengampu ? 'Guru Pengampu' : 'Tidak Terdaftar';
    }

    /**
     * Menyelesaikan konteks aktor (Tahun Ajaran aktif, NIPY, dan profil karyawan opsional) secara terpusat (DRY).
     *
     * @return array{
     *     activeTahunAjaran: ?TahunAjaran,
     *     nipy: string,
     *     karyawanProfile: ?Karyawan
     * }
     */
    private function resolveActorContext(
        User $user,
        ?TahunAjaran $activeTahunAjaran = null,
        bool $withKaryawanProfile = true
    ): array {
        $tahunAjaran = $activeTahunAjaran ?? TahunAjaran::query()->active()->first();
        $nipy = trim((string) ($user->no_induk ?? ''));
        $karyawanProfile = ($withKaryawanProfile && $nipy !== '')
            ? Karyawan::query()
                ->select(['nipy', 'nama_lengkap', 'gelar', 'jabatan', 'unit'])
                ->where('nipy', $nipy)
                ->first()
            : null;

        return [
            'activeTahunAjaran' => $tahunAjaran,
            'nipy' => $nipy,
            'karyawanProfile' => $karyawanProfile,
        ];
    }

    /**
     * Mengurutkan peta MataPelajaran menjadi EloquentCollection berdasarkan urutan lalu nama mapel.
     *
     * @param  array<int, MataPelajaran>  $mapels
     * @return EloquentCollection<int, MataPelajaran>
     */
    private function sortMapelCollection(array $mapels): EloquentCollection
    {
        $sorted = collect($mapels)
            ->sort(static function (MataPelajaran $a, MataPelajaran $b): int {
                $urutanCmp = ((int) ($a->urutan ?? 999)) <=> ((int) ($b->urutan ?? 999));

                return $urutanCmp !== 0
                    ? $urutanCmp
                    : strnatcasecmp((string) $a->nama_mata_pelajaran, (string) $b->nama_mata_pelajaran);
            })
            ->values()
            ->all();

        return new EloquentCollection($sorted);
    }

    /**
     * Memastikan user benar-benar bertugas sebagai Wali Kelas atau Pendamping di tahun ajaran aktif.
     */
    public function isAssignedInActiveYear(User $user, ?int $kelasId = null): bool
    {
        $assignedClasses = $this->getAssignedClassesForUser($user);
        if ($assignedClasses->isEmpty()) {
            return false;
        }

        return $kelasId === null || $assignedClasses->contains('id', $kelasId);
    }

    /**
     * Menyelesaikan konteks penugasan aktif Wali Kelas / Pendamping secara terpusat (DRY).
     *
     * @return array{
     *     isEligible: bool,
     *     ineligibleReason: ?string,
     *     nipy: string,
     *     karyawanProfile: ?Karyawan,
     *     activeTahunAjaran: ?TahunAjaran,
     *     assignedClasses: EloquentCollection<int, Kelas>,
     *     selectedKelas: ?Kelas,
     *     peranDiKelas: ?string
     * }
     */
    public function resolveScopeContext(
        User $user,
        ?int $requestedKelasId = null,
        bool $withKaryawanProfile = true
    ): array {
        $actor = $this->resolveActorContext($user, null, $withKaryawanProfile);
        $activeTahunAjaran = $actor['activeTahunAjaran'];
        $nipy = $actor['nipy'];
        $karyawanProfile = $actor['karyawanProfile'];

        $assignedClasses = $this->getAssignedClassesForUser($user, $activeTahunAjaran);

        if ($activeTahunAjaran === null || $nipy === '' || $assignedClasses->isEmpty()) {
            $reason = $activeTahunAjaran === null
                ? 'no_active_tahun_ajaran'
                : ($nipy === '' ? 'missing_nipy' : 'not_assigned_active_year');

            return [
                'isEligible' => false,
                'ineligibleReason' => $reason,
                'nipy' => $nipy,
                'karyawanProfile' => $karyawanProfile,
                'activeTahunAjaran' => $activeTahunAjaran,
                'assignedClasses' => $assignedClasses,
                'selectedKelas' => null,
                'peranDiKelas' => null,
            ];
        }

        $selectedKelas = $requestedKelasId !== null
            ? ($assignedClasses->firstWhere('id', $requestedKelasId) ?? $assignedClasses->first())
            : $assignedClasses->first();

        return [
            'isEligible' => true,
            'ineligibleReason' => null,
            'nipy' => $nipy,
            'karyawanProfile' => $karyawanProfile,
            'activeTahunAjaran' => $activeTahunAjaran,
            'assignedClasses' => $assignedClasses,
            'selectedKelas' => $selectedKelas,
            'peranDiKelas' => $this->resolveRoleInKelas($selectedKelas, $nipy),
        ];
    }

    /**
     * Memastikan user memiliki hak akses atas kelas tertentu pada Tahun Ajaran aktif.
     *
     * @return array{
     *     isEligible: bool,
     *     ineligibleReason: ?string,
     *     nipy: string,
     *     karyawanProfile: ?Karyawan,
     *     activeTahunAjaran: ?TahunAjaran,
     *     assignedClasses: EloquentCollection<int, Kelas>,
     *     selectedKelas: Kelas,
     *     peranDiKelas: string
     * }
     */
    public function authorizeKelasIdForUser(
        User $user,
        int $kelasId,
        string $errorMessage = 'Anda tidak memiliki akses ke kelas ini pada Tahun Ajaran aktif.'
    ): array {
        if ($kelasId <= 0) {
            abort(403, $errorMessage);
        }

        $scope = $this->resolveScopeContext($user, $kelasId, false);

        if (
            ! $scope['isEligible']
            || $scope['selectedKelas'] === null
            || (int) $scope['selectedKelas']->id !== $kelasId
        ) {
            abort(403, $errorMessage);
        }

        return $scope;
    }

    /**
     * Memastikan user memiliki hak akses atas entitas AnggotaKelas pada Tahun Ajaran aktif.
     *
     * @return array{
     *     isEligible: bool,
     *     ineligibleReason: ?string,
     *     nipy: string,
     *     karyawanProfile: ?Karyawan,
     *     activeTahunAjaran: ?TahunAjaran,
     *     assignedClasses: EloquentCollection<int, Kelas>,
     *     selectedKelas: Kelas,
     *     peranDiKelas: string
     * }
     */
    public function authorizeAnggotaKelasForUser(User $user, AnggotaKelas $anggotaKelas): array
    {
        return $this->authorizeKelasIdForUser(
            $user,
            (int) $anggotaKelas->akademik_master_kelas_id,
            'Anda tidak memiliki akses untuk melihat anggota kelas ini pada Tahun Ajaran aktif.'
        );
    }

    /**
     * Memastikan user memiliki hak akses atas entitas PresensiSiswa pada kelas binaan aktif tanpa lazy loading.
     *
     * @return array{
     *     isEligible: bool,
     *     ineligibleReason: ?string,
     *     nipy: string,
     *     karyawanProfile: ?Karyawan,
     *     activeTahunAjaran: ?TahunAjaran,
     *     assignedClasses: EloquentCollection<int, Kelas>,
     *     selectedKelas: Kelas,
     *     peranDiKelas: string
     * }
     */
    public function authorizePresensiSiswaForUser(
        User $user,
        PresensiSiswa $presensiSiswa,
        bool $loadMasterRelations = false
    ): array {
        $presensiSiswa->loadMissing(
            $loadMasterRelations
                ? PresensiSiswa::MASTER_RELATIONS
                : ['anggotaKelas:id,akademik_master_kelas_id']
        );

        $kelasId = $presensiSiswa->relationLoaded('anggotaKelas')
            ? (int) ($presensiSiswa->getRelation('anggotaKelas')?->akademik_master_kelas_id ?? 0)
            : 0;

        return $this->authorizeKelasIdForUser(
            $user,
            $kelasId,
            'Anda tidak memiliki akses untuk mengelola presensi kelas ini pada Tahun Ajaran aktif.'
        );
    }

    /**
     * Menyaring koleksi kelas binaan hanya untuk jenjang SD (modul Penilaian TP SD).
     *
     * @param  EloquentCollection<int, Kelas>  $assignedClasses
     * @return EloquentCollection<int, Kelas>
     */
    public function filterSdClasses(EloquentCollection $assignedClasses): EloquentCollection
    {
        return new EloquentCollection(
            $assignedClasses->filter(
                static fn (Kelas $kelas): bool => strtoupper(trim((string) $kelas->jenjang_kelas)) === Kkm::TARGET_UNIT
            )->values()->all()
        );
    }

    /**
     * Memastikan user memiliki hak akses atas cakupan Penilaian TP SD (kelas, tahun ajaran aktif, dan tingkatan).
     *
     * @return array{
     *     scope: array<string, mixed>,
     *     sdClasses: EloquentCollection<int, Kelas>,
     *     selectedKelas: Kelas
     * }
     */
    public function authorizeSdClassScope(
        User $user,
        ?int $kelasId = null,
        ?int $tahunAjaranId = null,
        ?string $tingkatanKelas = null,
        string $errorMessage = 'Anda tidak memiliki akses untuk mengelola penilaian kelas ini pada Tahun Ajaran aktif.'
    ): array {
        $scope = $this->resolveScopeContext($user, $kelasId, false);
        $sdClasses = $this->filterSdClasses($scope['assignedClasses']);
        $activeTaId = (int) ($scope['activeTahunAjaran']?->id ?? 0);

        if (! $scope['isEligible'] || $sdClasses->isEmpty() || $activeTaId <= 0) {
            abort(403, $errorMessage);
        }

        if ($tahunAjaranId !== null && $tahunAjaranId > 0 && $tahunAjaranId !== $activeTaId) {
            abort(403, $errorMessage);
        }

        $selectedKelas = $sdClasses->first();
        if ($kelasId !== null && $kelasId > 0) {
            $matched = $sdClasses->firstWhere('id', $kelasId);
            if (! $matched instanceof Kelas) {
                abort(403, $errorMessage);
            }
            $selectedKelas = $matched;
        }

        if ($tingkatanKelas !== null && trim($tingkatanKelas) !== '') {
            $targetTingkatan = trim($tingkatanKelas);
            if ($kelasId !== null && $kelasId > 0) {
                if (trim((string) $selectedKelas->tingkatan_kelas) !== $targetTingkatan) {
                    abort(403, $errorMessage);
                }
            } else {
                $matchingGradeClass = $sdClasses->first(
                    static fn (Kelas $kelas): bool => trim((string) $kelas->tingkatan_kelas) === $targetTingkatan
                );
                if (! $matchingGradeClass instanceof Kelas) {
                    abort(403, $errorMessage);
                }
                $selectedKelas = $matchingGradeClass;
            }
        }

        return [
            'scope' => $scope,
            'sdClasses' => $sdClasses,
            'selectedKelas' => $selectedKelas,
        ];
    }

    /**
     * Memuat seluruh kelas SD dan mata pelajaran SD aktif yang diampu user pada Jadwal Pelajaran
     * tahun ajaran aktif, baik sebagai Wali Kelas, Guru Pendamping, maupun Guru Mapel di kelas lain (anti-N+1).
     *
     * @return array{
     *     isEligible: bool,
     *     ineligibleReason: ?string,
     *     nipy: string,
     *     karyawanProfile: ?Karyawan,
     *     activeTahunAjaran: ?TahunAjaran,
     *     taughtClasses: EloquentCollection<int, Kelas>,
     *     taughtMapelsByClass: Collection<int, EloquentCollection<int, MataPelajaran>>
     * }
     */
    public function getTaughtSdScopeForUser(
        User $user,
        ?TahunAjaran $activeTahunAjaran = null,
        bool $withKaryawanProfile = false
    ): array {
        $actor = $this->resolveActorContext($user, $activeTahunAjaran, $withKaryawanProfile);
        $tahunAjaran = $actor['activeTahunAjaran'];
        $nipy = $actor['nipy'];
        $karyawanProfile = $actor['karyawanProfile'];

        if ($tahunAjaran === null || $nipy === '') {
            return [
                'isEligible' => false,
                'ineligibleReason' => $tahunAjaran === null ? 'no_active_tahun_ajaran' : 'missing_nipy',
                'nipy' => $nipy,
                'karyawanProfile' => $karyawanProfile,
                'activeTahunAjaran' => $tahunAjaran,
                'taughtClasses' => new EloquentCollection,
                'taughtMapelsByClass' => collect(),
            ];
        }

        $rows = JadwalPelajaran::query()
            ->select([
                'akademik_master_tahun_ajaran_id',
                'akademik_master_kelas_id',
                'akademik_pelajaran_mata_pelajaran_id',
            ])
            ->with([
                'kelas:id,akademik_master_tahun_ajaran_id,karyawan_nipy,pendamping_nipy,tingkatan_kelas,nama_kelas,jenjang_kelas,romawi',
                'kelas.tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
                'kelas.waliKelas:nipy,nama_lengkap,gelar',
                'kelas.pendamping:nipy,nama_lengkap,gelar',
                'mataPelajaran:id,singkatan,nama_mata_pelajaran,unit,urutan,status',
            ])
            ->where('akademik_master_tahun_ajaran_id', $tahunAjaran->id)
            ->where('karyawan_nipy', $nipy)
            ->where('status', true)
            ->whereNotNull('akademik_pelajaran_mata_pelajaran_id')
            ->get();

        $classesById = [];
        $groupedMapels = [];

        foreach ($rows as $row) {
            $kelas = $row->kelas;
            $mapel = $row->mataPelajaran;

            if (
                ! $kelas instanceof Kelas
                || (int) $kelas->akademik_master_tahun_ajaran_id !== (int) $tahunAjaran->id
                || strtoupper(trim((string) $kelas->jenjang_kelas)) !== Kkm::TARGET_UNIT
                || ! $mapel instanceof MataPelajaran
                || ! $mapel->status
                || strtoupper(trim((string) $mapel->unit)) !== Kkm::TARGET_UNIT
            ) {
                continue;
            }

            $classesById[$kelas->id] = $kelas;
            $groupedMapels[$kelas->id][$mapel->id] = $mapel;
        }

        if ($classesById === []) {
            return [
                'isEligible' => false,
                'ineligibleReason' => 'not_assigned_active_year',
                'nipy' => $nipy,
                'karyawanProfile' => $karyawanProfile,
                'activeTahunAjaran' => $tahunAjaran,
                'taughtClasses' => new EloquentCollection,
                'taughtMapelsByClass' => collect(),
            ];
        }

        $sortedClasses = new EloquentCollection(
            collect($classesById)->sort(static function (Kelas $a, Kelas $b) use ($nipy): int {
                $rolePriority = static function (Kelas $k) use ($nipy): int {
                    if (trim((string) $k->karyawan_nipy) === $nipy) {
                        return 0;
                    }

                    return trim((string) $k->pendamping_nipy) === $nipy ? 1 : 2;
                };

                $priorityCmp = $rolePriority($a) <=> $rolePriority($b);
                if ($priorityCmp !== 0) {
                    return $priorityCmp;
                }

                $tingkatanCmp = strnatcasecmp((string) $a->tingkatan_kelas, (string) $b->tingkatan_kelas);
                if ($tingkatanCmp !== 0) {
                    return $tingkatanCmp;
                }

                return strnatcasecmp((string) $a->nama_kelas, (string) $b->nama_kelas);
            })->values()->all()
        );

        $taughtMapelsByClass = collect($groupedMapels)->map(
            fn (array $mapels): EloquentCollection => $this->sortMapelCollection($mapels)
        );

        return [
            'isEligible' => true,
            'ineligibleReason' => null,
            'nipy' => $nipy,
            'karyawanProfile' => $karyawanProfile,
            'activeTahunAjaran' => $tahunAjaran,
            'taughtClasses' => $sortedClasses,
            'taughtMapelsByClass' => $taughtMapelsByClass,
        ];
    }

    /**
     * Memetakan mata pelajaran SD aktif yang diampu user pada jadwal setiap kelas SD (anti-N+1).
     *
     * @param  EloquentCollection<int, Kelas>  $sdClasses
     * @return Collection<int, EloquentCollection<int, MataPelajaran>>
     */
    public function getTaughtSdMapelsByClassForUser(User $user, EloquentCollection $sdClasses): Collection
    {
        $nipy = trim((string) ($user->no_induk ?? ''));
        $classIds = $sdClasses->pluck('id')->map(static fn ($id): int => (int) $id)->filter()->values()->all();
        $taIds = $sdClasses->pluck('akademik_master_tahun_ajaran_id')->map(static fn ($id): int => (int) $id)->filter()->unique()->values()->all();

        if ($nipy === '' || $classIds === [] || $taIds === []) {
            return collect();
        }

        $rows = JadwalPelajaran::query()
            ->select([
                'akademik_master_kelas_id',
                'akademik_pelajaran_mata_pelajaran_id',
            ])
            ->with(['mataPelajaran:id,singkatan,nama_mata_pelajaran,unit,urutan,status'])
            ->whereIn('akademik_master_tahun_ajaran_id', $taIds)
            ->whereIn('akademik_master_kelas_id', $classIds)
            ->where('karyawan_nipy', $nipy)
            ->where('status', true)
            ->whereNotNull('akademik_pelajaran_mata_pelajaran_id')
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $mapel = $row->mataPelajaran;
            if (
                ! $mapel instanceof MataPelajaran
                || ! $mapel->status
                || strtoupper(trim((string) $mapel->unit)) !== Kkm::TARGET_UNIT
            ) {
                continue;
            }

            $grouped[(int) $row->akademik_master_kelas_id][$mapel->id] = $mapel;
        }

        return collect($grouped)->map(
            fn (array $mapels): EloquentCollection => $this->sortMapelCollection($mapels)
        );
    }

    /**
     * Memastikan user mengampu mata pelajaran SD tertentu pada jadwal Tahun Ajaran aktif
     * (baik di kelas binaan sendiri maupun kelas lain yang dijadwalkan untuknya).
     *
     * @return array{
     *     scope: array<string, mixed>,
     *     activeTahunAjaran: TahunAjaran,
     *     taughtClasses: EloquentCollection<int, Kelas>,
     *     sdClasses: EloquentCollection<int, Kelas>,
     *     selectedKelas: Kelas,
     *     taughtMapelsByClass: Collection<int, EloquentCollection<int, MataPelajaran>>,
     *     allowedMapels: EloquentCollection<int, MataPelajaran>
     * }
     */
    public function authorizeSdMapelScope(
        User $user,
        ?int $kelasId = null,
        ?int $mapelId = null,
        ?int $tahunAjaranId = null,
        ?string $tingkatanKelas = null,
        string $errorMessage = 'Anda hanya dapat mengelola Penilaian TP untuk kelas dan mata pelajaran yang Anda ampu pada jadwal Tahun Ajaran aktif.'
    ): array {
        $scope = $this->getTaughtSdScopeForUser($user, null, false);
        /** @var EloquentCollection<int, Kelas> $taughtClasses */
        $taughtClasses = $scope['taughtClasses'];
        /** @var Collection<int, EloquentCollection<int, MataPelajaran>> $taughtMapelsByClass */
        $taughtMapelsByClass = $scope['taughtMapelsByClass'];
        $activeTahunAjaran = $scope['activeTahunAjaran'];
        $activeTaId = (int) ($activeTahunAjaran?->id ?? 0);

        if (! $scope['isEligible'] || $taughtClasses->isEmpty() || $activeTaId <= 0 || ! $activeTahunAjaran instanceof TahunAjaran) {
            abort(403, $errorMessage);
        }

        if ($tahunAjaranId !== null && $tahunAjaranId > 0 && $tahunAjaranId !== $activeTaId) {
            abort(403, $errorMessage);
        }

        if ($kelasId !== null && $kelasId > 0) {
            $selectedKelas = $taughtClasses->firstWhere('id', $kelasId);
            if (! $selectedKelas instanceof Kelas) {
                abort(403, $errorMessage);
            }

            if ($tingkatanKelas !== null && trim($tingkatanKelas) !== '' && trim((string) $selectedKelas->tingkatan_kelas) !== trim($tingkatanKelas)) {
                abort(403, $errorMessage);
            }

            $allowedMapels = $taughtMapelsByClass->get((int) $selectedKelas->id, new EloquentCollection);
        } elseif ($tingkatanKelas !== null && trim($tingkatanKelas) !== '') {
            $targetTingkatan = trim($tingkatanKelas);
            $matchingClasses = $taughtClasses
                ->filter(static fn (Kelas $k): bool => trim((string) $k->tingkatan_kelas) === $targetTingkatan)
                ->values();

            if ($matchingClasses->isEmpty()) {
                abort(403, $errorMessage);
            }

            $merged = [];
            foreach ($matchingClasses as $kelasItem) {
                foreach ($taughtMapelsByClass->get((int) $kelasItem->id, collect()) as $mp) {
                    $merged[$mp->id] = $mp;
                }
            }

            $selectedKelas = ($mapelId !== null && $mapelId > 0)
                ? ($matchingClasses->first(
                    static fn (Kelas $k): bool => $taughtMapelsByClass->get((int) $k->id, collect())->contains('id', $mapelId)
                ) ?? $matchingClasses->first())
                : $matchingClasses->first();
            $allowedMapels = $this->sortMapelCollection($merged);
        } else {
            $merged = [];
            foreach ($taughtMapelsByClass as $collection) {
                foreach ($collection as $mp) {
                    $merged[$mp->id] = $mp;
                }
            }

            $selectedKelas = ($mapelId !== null && $mapelId > 0)
                ? ($taughtClasses->first(
                    static fn (Kelas $k): bool => $taughtMapelsByClass->get((int) $k->id, collect())->contains('id', $mapelId)
                ) ?? $taughtClasses->first())
                : $taughtClasses->first();
            $allowedMapels = $this->sortMapelCollection($merged);
        }

        if (
            ! $selectedKelas instanceof Kelas
            || $allowedMapels->isEmpty()
            || ($mapelId !== null && $mapelId > 0 && ! $allowedMapels->contains('id', $mapelId))
        ) {
            abort(403, $errorMessage);
        }

        return [
            'scope' => $scope,
            'activeTahunAjaran' => $activeTahunAjaran,
            'taughtClasses' => $taughtClasses,
            'sdClasses' => $taughtClasses,
            'selectedKelas' => $selectedKelas,
            'taughtMapelsByClass' => $taughtMapelsByClass,
            'allowedMapels' => $allowedMapels,
        ];
    }

    /**
     * Memastikan user memiliki hak akses atas entitas RekapMapel siswa di kelas & mapel SD yang diampu.
     *
     * @return array{
     *     scope: array<string, mixed>,
     *     sdClasses: EloquentCollection<int, Kelas>,
     *     selectedKelas: Kelas,
     *     taughtMapelsByClass: Collection<int, EloquentCollection<int, MataPelajaran>>,
     *     allowedMapels: EloquentCollection<int, MataPelajaran>
     * }
     */
    public function authorizeRekapMapelForUser(User $user, RekapMapel $rekapMapel): array
    {
        $rekapMapel->loadMissing('anggotaKelas:id,akademik_master_kelas_id');

        $kelasId = $rekapMapel->relationLoaded('anggotaKelas')
            ? (int) ($rekapMapel->getRelation('anggotaKelas')?->akademik_master_kelas_id ?? 0)
            : 0;

        if ($kelasId <= 0) {
            abort(403, 'Anda tidak memiliki akses untuk memperbarui deskripsi capaian kelas ini.');
        }

        return $this->authorizeSdMapelScope(
            $user,
            $kelasId,
            (int) $rekapMapel->akademik_pelajaran_mata_pelajaran_id,
            (int) $rekapMapel->akademik_master_tahun_ajaran_id,
            null,
            'Anda hanya dapat memperbarui deskripsi capaian untuk mata pelajaran yang Anda ampu di kelas ini.'
        );
    }

    /**
     * Mengurutkan koleksi anggota kelas secara natural berdasarkan nama lengkap siswa.
     *
     * @param  EloquentCollection<int, AnggotaKelas>  $students
     * @return EloquentCollection<int, AnggotaKelas>
     */
    public function sortAnggotaByStudentName(EloquentCollection $students): EloquentCollection
    {
        return new EloquentCollection(
            $students->sortBy(
                static fn (AnggotaKelas $anggota): string => mb_strtolower((string) ($anggota->siswa?->nama_lengkap ?? $anggota->siswa_nis)),
                SORT_NATURAL
            )->values()->all()
        );
    }

    /**
     * Menormalisasi nilai jenis kelamin ke 'L', 'P', atau null tanpa query tambahan.
     */
    public function normalizeGender(?string $jenisKelamin): ?string
    {
        $normalized = strtoupper(trim((string) $jenisKelamin));

        if ($normalized === 'L' || $normalized === 'LAKI-LAKI') {
            return 'L';
        }

        if ($normalized === 'P' || $normalized === 'PEREMPUAN') {
            return 'P';
        }

        return null;
    }

    /**
     * Menghitung ringkasan demografi anggota kelas dari koleksi yang sudah dimuat (anti-N+1).
     *
     * @param  Collection<int, AnggotaKelas>  $students
     * @return array{
     *     total: int,
     *     laki: int,
     *     perempuan: int,
     *     aktif: int,
     *     by_pendaftaran: array<string, int>
     * }
     */
    public function summarizeStudents(Collection $students): array
    {
        $laki = 0;
        $perempuan = 0;
        $aktif = 0;
        $byPendaftaran = [];

        foreach ($students as $anggota) {
            $siswa = $anggota->siswa;
            $gender = $this->normalizeGender($siswa?->jenis_kelamin);

            if ($gender === 'L') {
                $laki++;
            } elseif ($gender === 'P') {
                $perempuan++;
            }

            if (strtolower(trim((string) ($siswa?->status ?? ''))) === 'aktif') {
                $aktif++;
            }

            $pendaftaran = trim((string) ($anggota->pendaftaran ?? ''));
            $pendaftaranKey = $pendaftaran !== '' ? $pendaftaran : 'Lainnya';
            $byPendaftaran[$pendaftaranKey] = ($byPendaftaran[$pendaftaranKey] ?? 0) + 1;
        }

        return [
            'total' => $students->count(),
            'laki' => $laki,
            'perempuan' => $perempuan,
            'aktif' => $aktif,
            'by_pendaftaran' => $byPendaftaran,
        ];
    }
}
