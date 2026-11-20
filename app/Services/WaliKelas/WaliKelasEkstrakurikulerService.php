<?php

namespace App\Services\WaliKelas;

use App\Models\Admin\DataMaster\AnggotaEkstrakurikuler;
use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\User;
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
     * Memuat data halaman penilaian ekstrakurikuler untuk pembimbing (atau menampilkan state ineligible jika tidak membimbing ekskul).
     *
     * @return array<string, mixed>
     */
    public function getPageData(User $user, ?int $requestedEkskulId = null): array
    {
        $activeTahunAjaran = TahunAjaran::query()->active()->first();
        $nipy = trim((string) ($user->no_induk ?? ''));
        $karyawanProfile = $nipy !== ''
            ? Karyawan::query()
                ->select(['nipy', 'nama_lengkap', 'gelar', 'jabatan', 'unit'])
                ->where('nipy', $nipy)
                ->first()
            : null;

        if ($activeTahunAjaran === null) {
            return [
                'isEligible' => false,
                'guardReason' => 'no_active_tahun_ajaran',
                'ineligibleReason' => 'no_active_tahun_ajaran',
                'nipy' => $nipy,
                'karyawanProfile' => $karyawanProfile,
                'activeTahunAjaran' => null,
                'assignedEkskuls' => new EloquentCollection,
                'selectedEkskul' => null,
                'members' => new EloquentCollection,
                'predikatRanges' => [],
                'stats' => ['total' => 0, 'dinilai' => 0, 'belum_dinilai' => 0],
            ];
        }

        if ($nipy === '') {
            return [
                'isEligible' => false,
                'guardReason' => 'missing_nipy',
                'ineligibleReason' => 'missing_nipy',
                'nipy' => '',
                'karyawanProfile' => null,
                'activeTahunAjaran' => $activeTahunAjaran,
                'assignedEkskuls' => new EloquentCollection,
                'selectedEkskul' => null,
                'members' => new EloquentCollection,
                'predikatRanges' => [],
                'stats' => ['total' => 0, 'dinilai' => 0, 'belum_dinilai' => 0],
            ];
        }

        $assignedEkskuls = $this->getAssignedEkstrakurikulerForUser($user, $activeTahunAjaran);

        if ($assignedEkskuls->isEmpty()) {
            return [
                'isEligible' => false,
                'guardReason' => 'no_assigned_ekskul',
                'ineligibleReason' => 'no_assigned_ekskul',
                'nipy' => $nipy,
                'karyawanProfile' => $karyawanProfile,
                'activeTahunAjaran' => $activeTahunAjaran,
                'assignedEkskuls' => $assignedEkskuls,
                'selectedEkskul' => null,
                'members' => new EloquentCollection,
                'predikatRanges' => [],
                'stats' => ['total' => 0, 'dinilai' => 0, 'belum_dinilai' => 0],
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
            'guardReason' => null,
            'ineligibleReason' => null,
            'nipy' => $nipy,
            'karyawanProfile' => $karyawanProfile,
            'activeTahunAjaran' => $activeTahunAjaran,
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
     * Memastikan user berhak menginput/memperbarui nilai pada ekstrakurikuler tersebut.
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
                    // Jika guru mengubah nilai/KKM/materi tapi kolom keterangan di form masih berisi deskripsi otomatis lama,
                    // perbarui ke deskripsi otomatis yang baru; jika diketik manual berbeda, pertahankan manualnya.
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
                'anggotaKelas.siswa:nis,nama_lengkap,nisn,jenis_kelamin,status',
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
}
