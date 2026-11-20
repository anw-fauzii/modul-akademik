<?php

namespace App\Services\WaliKelas;

use App\Models\Admin\DataMaster\AnggotaEkstrakurikuler;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WaliKelasEkstrakurikulerService
{
    public function __construct(
        protected ?WaliKelasScopeService $scopeService = null
    ) {
        $this->scopeService ??= new WaliKelasScopeService;
    }

    /**
     * Mengambil daftar ekstrakurikuler pada tahun ajaran aktif yang dibimbing oleh user (berdasarkan karyawan_nipy).
     *
     * @return EloquentCollection<int, Ekstrakurikuler>
     */
    public function getAssignedEkstrakurikulerForUser(
        User $user,
        ?TahunAjaran $activeTahunAjaran = null
    ): EloquentCollection {
        $tahunAjaran = $activeTahunAjaran ?? TahunAjaran::query()->active()->first();
        $nipy = trim((string) ($user->no_induk ?? ''));

        if ($tahunAjaran === null || $nipy === '') {
            return new EloquentCollection;
        }

        return Ekstrakurikuler::query()
            ->select([
                'id',
                'akademik_master_tahun_ajaran_id',
                'karyawan_nipy',
                'nama_ekstrakurikuler',
                'biaya',
                'unit',
                'sifat',
                'kkm',
                'materi',
            ])
            ->with([
                'tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
                'pembina:nipy,nama_lengkap,gelar',
            ])
            ->withCount('anggotaEkstrakurikuler')
            ->where('akademik_master_tahun_ajaran_id', $tahunAjaran->id)
            ->where('karyawan_nipy', $nipy)
            ->orderBy('unit')
            ->orderBy('nama_ekstrakurikuler')
            ->orderBy('id')
            ->get();
    }

    /**
     * Mencari atau membangun entitas master Ekstrakurikuler Wajib (Pramuka) untuk Tahun Ajaran & Unit kelas.
     */
    public function resolveOrBuildWajibEkstrakurikuler(
        TahunAjaran $tahunAjaran,
        string $unit,
        bool $persistIfMissing = false
    ): Ekstrakurikuler {
        $normalizedUnit = strtoupper(trim($unit)) ?: 'SD';

        $existing = Ekstrakurikuler::query()
            ->select([
                'id',
                'akademik_master_tahun_ajaran_id',
                'karyawan_nipy',
                'nama_ekstrakurikuler',
                'biaya',
                'unit',
                'sifat',
                'kkm',
                'materi',
            ])
            ->with([
                'tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
                'pembina:nipy,nama_lengkap,gelar',
            ])
            ->where('akademik_master_tahun_ajaran_id', $tahunAjaran->id)
            ->where('unit', $normalizedUnit)
            ->where(static function (Builder $query): void {
                $query->where('sifat', Ekstrakurikuler::SIFAT_WAJIB)
                    ->orWhereRaw('LOWER(nama_ekstrakurikuler) LIKE ?', ['%pramuka%']);
            })
            ->orderByRaw("CASE WHEN sifat = 'Wajib' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();

        if ($existing instanceof Ekstrakurikuler) {
            if ($persistIfMissing && $existing->sifat !== Ekstrakurikuler::SIFAT_WAJIB) {
                $existing->update(['sifat' => Ekstrakurikuler::SIFAT_WAJIB]);
            }

            return $existing;
        }

        $attributes = [
            'akademik_master_tahun_ajaran_id' => $tahunAjaran->id,
            'karyawan_nipy' => null,
            'nama_ekstrakurikuler' => 'Pramuka',
            'biaya' => 0,
            'unit' => $normalizedUnit,
            'sifat' => Ekstrakurikuler::SIFAT_WAJIB,
            'kkm' => Ekstrakurikuler::DEFAULT_KKM,
            'materi' => 'kegiatan kepramukaan, kedisiplinan, dan kemandirian',
        ];

        if ($persistIfMissing) {
            $created = Ekstrakurikuler::create($attributes);
            $created->setRelation('tahunAjaran', $tahunAjaran);
            $created->setRelation('pembina', null);

            return $created;
        }

        $virtual = Ekstrakurikuler::make($attributes);
        $virtual->setRelation('tahunAjaran', $tahunAjaran);
        $virtual->setRelation('pembina', null);

        return $virtual;
    }

    /**
     * Memuat data halaman penilaian ekstrakurikuler:
     * - Mode 'wajib' (Pramuka): otomatis untuk Wali Kelas / Guru Pendamping bagi seluruh siswa kelas binaannya.
     * - Mode 'pilihan' (Minat): untuk Pembina Ekstrakurikuler berdasarkan karyawan_nipy.
     *
     * @return array<string, mixed>
     */
    public function getPageData(
        User $user,
        ?int $requestedEkskulId = null,
        ?int $requestedKelasId = null,
        ?string $requestedMode = null
    ): array {
        $activeTahunAjaran = TahunAjaran::query()->active()->first();
        $nipy = trim((string) ($user->no_induk ?? ''));
        $karyawanProfile = $nipy !== ''
            ? Karyawan::query()
                ->select(['nipy', 'nama_lengkap', 'gelar', 'jabatan', 'unit'])
                ->where('nipy', $nipy)
                ->first()
            : null;

        if ($activeTahunAjaran === null) {
            return $this->buildIneligiblePayload('no_active_tahun_ajaran', $nipy, $karyawanProfile, null);
        }

        if ($nipy === '') {
            return $this->buildIneligiblePayload('missing_nipy', '', null, $activeTahunAjaran);
        }

        $assignedClasses = $this->scopeService->getAssignedClassesForUser($user, $activeTahunAjaran);
        $assignedEkskuls = $this->getAssignedEkstrakurikulerForUser($user, $activeTahunAjaran);

        if ($assignedClasses->isEmpty() && $assignedEkskuls->isEmpty()) {
            return $this->buildIneligiblePayload('no_assigned_ekskul', $nipy, $karyawanProfile, $activeTahunAjaran);
        }

        if ($requestedEkskulId !== null && $requestedEkskulId > 0) {
            $mode = 'pilihan';
        } elseif ($requestedKelasId !== null && $requestedKelasId > 0) {
            $mode = 'wajib';
        } elseif ($requestedMode === 'pilihan' && $assignedEkskuls->isNotEmpty()) {
            $mode = 'pilihan';
        } elseif ($requestedMode === 'wajib' && $assignedClasses->isNotEmpty()) {
            $mode = 'wajib';
        } elseif ($assignedClasses->isNotEmpty()) {
            $mode = 'wajib';
        } else {
            $mode = 'pilihan';
        }

        if ($mode === 'wajib') {
            if ($assignedClasses->isEmpty()) {
                abort(403, 'Anda belum memiliki penugasan Wali Kelas atau Pendamping untuk menilai Pramuka Wajib.');
            }

            if ($requestedKelasId !== null && $requestedKelasId > 0 && ! $assignedClasses->contains('id', $requestedKelasId)) {
                abort(403, 'Anda hanya dapat menilai Pramuka Wajib untuk kelas binaan Anda.');
            }

            /** @var Kelas $selectedKelas */
            $selectedKelas = ($requestedKelasId !== null && $requestedKelasId > 0)
                ? $assignedClasses->firstWhere('id', $requestedKelasId)
                : $assignedClasses->first();

            $wajibEkskul = $this->resolveOrBuildWajibEkstrakurikuler(
                $activeTahunAjaran,
                (string) $selectedKelas->jenjang_kelas,
                false
            );

            $members = $this->loadWajibClassMembers($selectedKelas, $wajibEkskul);
            $dinilaiCount = $members->filter(static fn (AnggotaEkstrakurikuler $m): bool => $m->nilai !== null)->count();

            return [
                'isEligible' => true,
                'mode' => 'wajib',
                'guardReason' => null,
                'ineligibleReason' => null,
                'nipy' => $nipy,
                'karyawanProfile' => $karyawanProfile,
                'activeTahunAjaran' => $activeTahunAjaran,
                'assignedClasses' => $assignedClasses,
                'selectedKelas' => $selectedKelas,
                'peranDiKelas' => $this->scopeService->resolveRoleInKelas($selectedKelas, $nipy),
                'assignedEkskuls' => $assignedEkskuls,
                'selectedEkskul' => $wajibEkskul,
                'members' => $members,
                'predikatRanges' => $wajibEkskul->predikatRanges(),
                'stats' => [
                    'total' => $members->count(),
                    'dinilai' => $dinilaiCount,
                    'belum_dinilai' => max(0, $members->count() - $dinilaiCount),
                ],
            ];
        }

        if ($requestedEkskulId !== null && $requestedEkskulId > 0 && ! $assignedEkskuls->contains('id', $requestedEkskulId)) {
            abort(403, 'Anda hanya dapat mengakses penilaian ekstrakurikuler yang Anda bimbing.');
        }

        /** @var Ekstrakurikuler $selectedEkskul */
        $selectedEkskul = ($requestedEkskulId !== null && $requestedEkskulId > 0)
            ? $assignedEkskuls->firstWhere('id', $requestedEkskulId)
            : $assignedEkskuls->first();

        $members = $this->loadSortedMembers($selectedEkskul);
        $dinilaiCount = $members->filter(static fn (AnggotaEkstrakurikuler $m): bool => $m->nilai !== null)->count();

        return [
            'isEligible' => true,
            'mode' => 'pilihan',
            'guardReason' => null,
            'ineligibleReason' => null,
            'nipy' => $nipy,
            'karyawanProfile' => $karyawanProfile,
            'activeTahunAjaran' => $activeTahunAjaran,
            'assignedClasses' => $assignedClasses,
            'selectedKelas' => null,
            'peranDiKelas' => null,
            'assignedEkskuls' => $assignedEkskuls,
            'selectedEkskul' => $selectedEkskul,
            'members' => $members,
            'predikatRanges' => $selectedEkskul->predikatRanges(),
            'stats' => [
                'total' => $members->count(),
                'dinilai' => $dinilaiCount,
                'belum_dinilai' => max(0, $members->count() - $dinilaiCount),
            ],
        ];
    }

    /**
     * Memastikan user berhak menginput/memperbarui nilai pada ekstrakurikuler pilihan tersebut.
     */
    public function authorizeEkstrakurikulerForUser(User $user, Ekstrakurikuler $ekstrakurikuler): void
    {
        if ($user->hasRole('super admin')) {
            return;
        }

        $activeTa = TahunAjaran::query()->active()->first();
        $nipy = trim((string) ($user->no_induk ?? ''));

        if ($activeTa === null || $nipy === '') {
            abort(403, 'Akun Anda belum terhubung dengan NIPY aktif.');
        }

        if ((int) $ekstrakurikuler->akademik_master_tahun_ajaran_id !== (int) $activeTa->id
            || trim((string) $ekstrakurikuler->karyawan_nipy) !== $nipy) {
            abort(403, 'Anda hanya dapat menginput nilai pada ekstrakurikuler yang Anda bimbing di Tahun Ajaran aktif.');
        }
    }

    /**
     * Memastikan user berhak menginput nilai Pramuka Wajib untuk kelas binaan tersebut.
     */
    public function authorizeWajibKelasForUser(User $user, Kelas $kelas): void
    {
        if ($user->hasRole('super admin')) {
            return;
        }

        if (! $this->scopeService->isAssignedInActiveYear($user, (int) $kelas->id)) {
            abort(403, 'Anda hanya dapat menginput nilai Pramuka untuk kelas binaan Anda pada Tahun Ajaran aktif.');
        }
    }

    /**
     * Menyimpan nilai Pramuka (Ekstrakurikuler Wajib) untuk seluruh siswa dalam 1 kelas binaan Wali Kelas
     * tanpa mengharuskan Admin mendaftarkan anggota Pramuka terlebih dahulu.
     *
     * @param  array<string, mixed>  $payload
     */
    public function savePenilaianWajibKelas(Kelas $kelas, array $payload): int
    {
        $kelas->loadMissing('tahunAjaran:id,nama_tahun_ajaran,semester,is_active');
        $tahunAjaran = $kelas->tahunAjaran ?? TahunAjaran::query()->findOrFail($kelas->akademik_master_tahun_ajaran_id);

        $nilaiInputs = isset($payload['nilai']) && is_array($payload['nilai']) ? $payload['nilai'] : [];

        foreach ($nilaiInputs as $anggotaKelasId => $rawNilai) {
            if ($rawNilai === null || $rawNilai === '') {
                continue;
            }
            if (! is_numeric($rawNilai) || (float) $rawNilai < 0 || (float) $rawNilai > 100) {
                throw ValidationException::withMessages([
                    "nilai.{$anggotaKelasId}" => 'Nilai Pramuka harus berupa angka antara 0 sampai 100.',
                ]);
            }
        }

        return DB::transaction(function () use ($kelas, $tahunAjaran, $nilaiInputs): int {
            $wajibEkskul = $this->resolveOrBuildWajibEkstrakurikuler(
                $tahunAjaran,
                (string) $kelas->jenjang_kelas,
                true
            );

            $kkm = (int) ($wajibEkskul->kkm ?: Ekstrakurikuler::DEFAULT_KKM);
            $materi = $wajibEkskul->materi;

            $anggotaKelasList = AnggotaKelas::query()
                ->select(['id', 'siswa_nis', 'akademik_master_kelas_id', 'pendaftaran'])
                ->where('akademik_master_kelas_id', $kelas->id)
                ->get();

            $anggotaIds = $anggotaKelasList->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all();

            $existingMemberships = $anggotaIds !== []
                ? AnggotaEkstrakurikuler::query()
                    ->where('akademik_master_ekstrakuler_id', $wajibEkskul->id)
                    ->whereIn('anggota_kelas_id', $anggotaIds)
                    ->get()
                    ->keyBy('anggota_kelas_id')
                : new EloquentCollection;

            $updatedCount = 0;

            foreach ($anggotaKelasList as $ak) {
                $idKey = (string) $ak->id;
                $hasKey = array_key_exists($idKey, $nilaiInputs) || array_key_exists($ak->id, $nilaiInputs);
                if (! $hasKey) {
                    continue;
                }

                $rawNilai = $nilaiInputs[$ak->id] ?? ($nilaiInputs[$idKey] ?? null);
                $newNilai = ($rawNilai === null || $rawNilai === '') ? null : (int) round((float) $rawNilai);

                /** @var AnggotaEkstrakurikuler|null $existing */
                $existing = $existingMemberships->get($ak->id);

                if ($newNilai === null) {
                    if ($existing !== null && ($existing->nilai !== null || $existing->predikat !== null)) {
                        $existing->update([
                            'nilai' => null,
                            'predikat' => null,
                            'keterangan' => null,
                        ]);
                        $updatedCount++;
                    }

                    continue;
                }

                $resolvedRange = Ekstrakurikuler::resolvePredikatForNilai($newNilai, $kkm);
                $newPredikat = $resolvedRange['predikat'] ?? null;
                $autoDeskripsi = Ekstrakurikuler::buildAutoDeskripsi(
                    $newPredikat,
                    $materi,
                    $wajibEkskul->nama_ekstrakurikuler
                );

                if ($existing !== null) {
                    if ($existing->nilai !== $newNilai
                        || $existing->predikat !== $newPredikat
                        || $existing->keterangan !== $autoDeskripsi) {
                        $existing->update([
                            'nilai' => $newNilai,
                            'predikat' => $newPredikat,
                            'keterangan' => $autoDeskripsi,
                        ]);
                        $updatedCount++;
                    }
                } else {
                    AnggotaEkstrakurikuler::create([
                        'anggota_kelas_id' => $ak->id,
                        'akademik_master_ekstrakuler_id' => $wajibEkskul->id,
                        'pendaftaran' => 'Baru',
                        'nilai' => $newNilai,
                        'predikat' => $newPredikat,
                        'keterangan' => $autoDeskripsi,
                    ]);
                    $updatedCount++;
                }
            }

            return $updatedCount;
        });
    }

    /**
     * Menyimpan pengaturan KKM & Materi ekstrakurikuler serta nilai massal seluruh anggota.
     * Ketika nilai diisi, predikat (SAB, BSH, SB, MB) dan deskripsi rapor otomatis diperbarui.
     *
     * @param  array<string, mixed>  $payload
     */
    public function savePenilaian(
        Ekstrakurikuler $ekstrakurikuler,
        array $payload,
        bool $regenerateAllDescriptions = false
    ): int {
        $kkm = isset($payload['kkm']) && is_numeric($payload['kkm'])
            ? max(1, min(100, (int) $payload['kkm']))
            : ((int) ($ekstrakurikuler->kkm ?: Ekstrakurikuler::DEFAULT_KKM));

        $materi = array_key_exists('materi', $payload)
            ? (trim((string) ($payload['materi'] ?? '')) ?: null)
            : $ekstrakurikuler->materi;

        $nilaiInputs = isset($payload['nilai']) && is_array($payload['nilai']) ? $payload['nilai'] : [];
        $keteranganInputs = isset($payload['keterangan']) && is_array($payload['keterangan']) ? $payload['keterangan'] : [];

        foreach ($nilaiInputs as $anggotaEkskulId => $rawNilai) {
            if ($rawNilai === null || $rawNilai === '') {
                continue;
            }
            if (! is_numeric($rawNilai) || (float) $rawNilai < 0 || (float) $rawNilai > 100) {
                throw ValidationException::withMessages([
                    "nilai.{$anggotaEkskulId}" => 'Nilai ekstrakurikuler harus berupa angka antara 0 sampai 100.',
                ]);
            }
        }

        return DB::transaction(function () use (
            $ekstrakurikuler,
            $kkm,
            $materi,
            $nilaiInputs,
            $keteranganInputs,
            $regenerateAllDescriptions
        ): int {
            $materiChanged = trim((string) $ekstrakurikuler->materi) !== trim((string) $materi);
            $kkmChanged = (int) ($ekstrakurikuler->kkm ?: Ekstrakurikuler::DEFAULT_KKM) !== $kkm;

            $ekstrakurikuler->update([
                'kkm' => $kkm,
                'materi' => $materi,
            ]);

            $members = $ekstrakurikuler->anggotaEkstrakurikuler()
                ->select([
                    'id',
                    'anggota_kelas_id',
                    'akademik_master_ekstrakuler_id',
                    'pendaftaran',
                    'nilai',
                    'predikat',
                    'keterangan',
                ])
                ->get();

            $updatedCount = 0;

            foreach ($members as $member) {
                $idKey = (string) $member->id;
                $hasNilaiKey = array_key_exists($idKey, $nilaiInputs) || array_key_exists($member->id, $nilaiInputs);
                $rawNilai = $nilaiInputs[$member->id] ?? ($nilaiInputs[$idKey] ?? null);

                $newNilai = $hasNilaiKey
                    ? (($rawNilai === null || $rawNilai === '') ? null : (int) round((float) $rawNilai))
                    : $member->nilai;

                $resolvedRange = Ekstrakurikuler::resolvePredikatForNilai($newNilai, $kkm);
                $newPredikat = $resolvedRange['predikat'] ?? null;

                $autoDeskripsi = Ekstrakurikuler::buildAutoDeskripsi(
                    $newPredikat,
                    $materi,
                    $ekstrakurikuler->nama_ekstrakurikuler
                );

                $hasKeteranganKey = array_key_exists($idKey, $keteranganInputs) || array_key_exists($member->id, $keteranganInputs);
                $submittedKeterangan = $hasKeteranganKey
                    ? trim((string) ($keteranganInputs[$member->id] ?? ($keteranganInputs[$idKey] ?? '')))
                    : null;

                $oldAutoDeskripsi = Ekstrakurikuler::buildAutoDeskripsi(
                    $member->predikat,
                    $ekstrakurikuler->getOriginal('materi') ?? $materi,
                    $ekstrakurikuler->nama_ekstrakurikuler
                );

                if ($newNilai === null) {
                    $newKeterangan = $submittedKeterangan !== null && $submittedKeterangan !== '' ? $submittedKeterangan : null;
                } elseif ($regenerateAllDescriptions) {
                    $newKeterangan = $autoDeskripsi;
                } elseif ($submittedKeterangan !== null && $submittedKeterangan !== '' && $submittedKeterangan !== $oldAutoDeskripsi) {
                    $newKeterangan = $submittedKeterangan;
                } else {
                    $newKeterangan = $autoDeskripsi;
                }

                if ($member->nilai !== $newNilai
                    || $member->predikat !== $newPredikat
                    || $member->keterangan !== $newKeterangan
                    || $materiChanged
                    || $kkmChanged) {
                    $member->update([
                        'nilai' => $newNilai,
                        'predikat' => $newPredikat,
                        'keterangan' => $newKeterangan,
                    ]);
                    $updatedCount++;
                }
            }

            return $updatedCount;
        });
    }

    /**
     * Memuat seluruh siswa dalam kelas binaan Wali Kelas sebagai baris penilaian Pramuka Wajib tanpa N+1.
     *
     * @return EloquentCollection<int, AnggotaEkstrakurikuler>
     */
    public function loadWajibClassMembers(Kelas $kelas, Ekstrakurikuler $wajibEkskul): EloquentCollection
    {
        $rawAnggota = AnggotaKelas::query()
            ->select(['id', 'siswa_nis', 'akademik_master_kelas_id', 'pendaftaran'])
            ->with(['siswa:nis,nama_lengkap,nisn,jenis_kelamin,status,avatar'])
            ->where('akademik_master_kelas_id', $kelas->id)
            ->get();

        $rawAnggota->each(static function (AnggotaKelas $ak) use ($kelas): void {
            $ak->setRelation('kelas', $kelas);
        });

        $sortedAnggota = $this->scopeService->sortAnggotaByStudentName($rawAnggota);
        $anggotaIds = $sortedAnggota->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all();

        $existingByAnggotaId = ($wajibEkskul->id && $anggotaIds !== [])
            ? AnggotaEkstrakurikuler::query()
                ->select([
                    'id',
                    'anggota_kelas_id',
                    'akademik_master_ekstrakuler_id',
                    'pendaftaran',
                    'nilai',
                    'predikat',
                    'keterangan',
                ])
                ->where('akademik_master_ekstrakuler_id', $wajibEkskul->id)
                ->whereIn('anggota_kelas_id', $anggotaIds)
                ->get()
                ->keyBy('anggota_kelas_id')
            : collect();

        $rows = $sortedAnggota->map(static function (AnggotaKelas $ak) use ($existingByAnggotaId, $wajibEkskul): AnggotaEkstrakurikuler {
            /** @var AnggotaEkstrakurikuler|null $existing */
            $existing = $existingByAnggotaId->get($ak->id);

            if ($existing instanceof AnggotaEkstrakurikuler) {
                $existing->setRelation('anggotaKelas', $ak);
                $existing->setRelation('ekstrakurikuler', $wajibEkskul);

                return $existing;
            }

            $placeholder = AnggotaEkstrakurikuler::make([
                'anggota_kelas_id' => $ak->id,
                'akademik_master_ekstrakuler_id' => (int) ($wajibEkskul->id ?? 0),
                'pendaftaran' => 'Baru',
                'nilai' => null,
                'predikat' => null,
                'keterangan' => null,
            ]);
            // Gunakan anggota_kelas_id sebagai key input form pada mode wajib
            $placeholder->id = (int) $ak->id;
            $placeholder->setRelation('anggotaKelas', $ak);
            $placeholder->setRelation('ekstrakurikuler', $wajibEkskul);

            return $placeholder;
        })->values()->all();

        return new EloquentCollection($rows);
    }

    /**
     * Memuat anggota ekstrakurikuler lengkap dengan siswa dan kelas tanpa N+1, diurutkan berdasarkan kelas lalu nama siswa.
     *
     * @return EloquentCollection<int, AnggotaEkstrakurikuler>
     */
    public function loadSortedMembers(Ekstrakurikuler $ekstrakurikuler): EloquentCollection
    {
        $members = $ekstrakurikuler->anggotaEkstrakurikuler()
            ->select([
                'id',
                'anggota_kelas_id',
                'akademik_master_ekstrakuler_id',
                'pendaftaran',
                'nilai',
                'predikat',
                'keterangan',
            ])
            ->with([
                'anggotaKelas:id,siswa_nis,akademik_master_kelas_id,pendaftaran',
                'anggotaKelas.siswa:nis,nama_lengkap,nisn,jenis_kelamin,status,avatar',
                'anggotaKelas.kelas:id,akademik_master_tahun_ajaran_id,nama_kelas,jenjang_kelas,tingkatan_kelas',
            ])
            ->get();

        $members->each(static function (AnggotaEkstrakurikuler $member) use ($ekstrakurikuler): void {
            $member->setRelation('ekstrakurikuler', $ekstrakurikuler);
        });

        return new EloquentCollection(
            $members->sort(static function (AnggotaEkstrakurikuler $a, AnggotaEkstrakurikuler $b): int {
                $kelasA = (string) ($a->anggotaKelas?->kelas?->nama_kelas ?? '');
                $kelasB = (string) ($b->anggotaKelas?->kelas?->nama_kelas ?? '');
                $kelasCmp = strnatcasecmp($kelasA, $kelasB);
                if ($kelasCmp !== 0) {
                    return $kelasCmp;
                }

                $namaA = (string) ($a->anggotaKelas?->siswa?->nama_lengkap ?? $a->anggotaKelas?->siswa_nis ?? '');
                $namaB = (string) ($b->anggotaKelas?->siswa?->nama_lengkap ?? $b->anggotaKelas?->siswa_nis ?? '');

                return strnatcasecmp($namaA, $namaB);
            })->values()->all()
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildIneligiblePayload(
        string $reason,
        string $nipy,
        ?Karyawan $karyawanProfile,
        ?TahunAjaran $activeTahunAjaran
    ): array {
        return [
            'isEligible' => false,
            'mode' => 'pilihan',
            'guardReason' => $reason,
            'ineligibleReason' => $reason,
            'nipy' => $nipy,
            'karyawanProfile' => $karyawanProfile,
            'activeTahunAjaran' => $activeTahunAjaran,
            'assignedClasses' => new EloquentCollection,
            'selectedKelas' => null,
            'peranDiKelas' => null,
            'assignedEkskuls' => new EloquentCollection,
            'selectedEkskul' => null,
            'members' => new EloquentCollection,
            'predikatRanges' => [],
            'stats' => ['total' => 0, 'dinilai' => 0, 'belum_dinilai' => 0],
        ];
    }
}
