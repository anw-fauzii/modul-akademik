<?php

namespace App\Services\WaliKelas;

use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\TahunAjaran;
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
     * Menentukan peran user di dalam kelas (Wali Kelas atau Guru Pendamping).
     */
    public function resolveRoleInKelas(Kelas $kelas, string $nipy): string
    {
        $normalizedNipy = trim($nipy);

        if ($normalizedNipy !== '' && trim((string) $kelas->karyawan_nipy) === $normalizedNipy) {
            return 'Wali Kelas';
        }

        if ($normalizedNipy !== '' && trim((string) $kelas->pendamping_nipy) === $normalizedNipy) {
            return 'Guru Pendamping';
        }

        return 'Tidak Terdaftar';
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
        $activeTahunAjaran = TahunAjaran::query()->active()->first();
        $nipy = trim((string) ($user->no_induk ?? ''));
        $karyawanProfile = ($withKaryawanProfile && $nipy !== '')
            ? Karyawan::query()
                ->select(['nipy', 'nama_lengkap', 'gelar', 'jabatan', 'unit'])
                ->where('nipy', $nipy)
                ->first()
            : null;

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
     * Memastikan user memiliki hak akses atas entitas RekapMapel siswa di kelas SD binaan aktif.
     *
     * @return array{
     *     scope: array<string, mixed>,
     *     sdClasses: EloquentCollection<int, Kelas>,
     *     selectedKelas: Kelas
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

        return $this->authorizeSdClassScope(
            $user,
            $kelasId,
            (int) $rekapMapel->akademik_master_tahun_ajaran_id,
            null,
            'Anda tidak memiliki akses untuk memperbarui deskripsi capaian siswa kelas ini.'
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
