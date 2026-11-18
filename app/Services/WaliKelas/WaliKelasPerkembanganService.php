<?php

namespace App\Services\WaliKelas;

use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\Pelajaran\JadwalPelajaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Admin\Penilaian\Kkm;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Models\Admin\Penilaian\RekapMapel;
use App\Models\Admin\Penilaian\RekapTp;
use App\Models\Admin\Presensi\PresensiSiswa;
use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use App\Models\User;
use App\Services\Admin\DataMaster\AnggotaKelasService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class WaliKelasPerkembanganService
{
    public function __construct(
        protected ?WaliKelasScopeService $scopeService = null,
        protected ?AnggotaKelasService $anggotaKelasService = null
    ) {
        $this->scopeService ??= new WaliKelasScopeService;
        $this->anggotaKelasService ??= new AnggotaKelasService;
    }

    /**
     * Memuat leger perkembangan seluruh siswa pada kelas binaan untuk seluruh mata pelajaran & presensi (anti-N+1).
     *
     * @return array<string, mixed>
     */
    public function getIndexData(
        User $user,
        ?int $requestedKelasId = null,
        string $periodeInput = PeriodeTp::PERIODE_MID
    ): array {
        $scope = $this->scopeService->resolveScopeContext($user, $requestedKelasId);
        $periode = $this->normalizePeriode($periodeInput);

        if (! $scope['isEligible'] || $scope['selectedKelas'] === null || $scope['activeTahunAjaran'] === null) {
            return [
                ...$scope,
                'periode' => $periode,
                'isSd' => false,
                'students' => new EloquentCollection,
                'studentStats' => $this->scopeService->summarizeStudents(new EloquentCollection),
                'mapels' => new EloquentCollection,
                'mapelSummaries' => collect(),
                'studentRows' => collect(),
                'overviewStats' => $this->emptyOverviewStats(),
            ];
        }

        $selectedKelas = $scope['selectedKelas'];
        $activeTahunAjaran = $scope['activeTahunAjaran'];

        if (
            $requestedKelasId !== null
            && $requestedKelasId > 0
            && ! $scope['assignedClasses']->contains('id', $requestedKelasId)
        ) {
            abort(403, 'Anda tidak memiliki akses untuk melihat perkembangan siswa kelas ini.');
        }

        $students = $this->scopeService->sortAnggotaByStudentName(
            $this->anggotaKelasService
                ->buildIndexQuery($activeTahunAjaran->id, $selectedKelas->id)
                ->with(['siswa:nis,nama_lengkap,nisn,jenis_kelamin,status,avatar'])
                ->get()
        );

        $studentStats = $this->scopeService->summarizeStudents($students);
        $anggotaIds = $students->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all();
        $presensiByAnggota = $this->buildPresensiSummaryByAnggota($anggotaIds);

        $isSd = strtoupper(trim((string) $selectedKelas->jenjang_kelas)) === Kkm::TARGET_UNIT;
        if (! $isSd) {
            $studentRows = $students->map(function (AnggotaKelas $anggota) use ($presensiByAnggota): array {
                return [
                    'anggota' => $anggota,
                    'presensi' => $presensiByAnggota[$anggota->id] ?? $this->emptyPresensiCounts(),
                    'mapel_cells' => [],
                    'dinilai_count' => 0,
                    'tuntas_count' => 0,
                    'belum_tuntas_count' => 0,
                    'rata_rapor' => null,
                ];
            });

            return [
                ...$scope,
                'periode' => $periode,
                'isSd' => false,
                'students' => $students,
                'studentStats' => $studentStats,
                'mapels' => new EloquentCollection,
                'mapelSummaries' => collect(),
                'studentRows' => $studentRows,
                'overviewStats' => [
                    ...$this->emptyOverviewStats(),
                    'avg_kehadiran' => $this->computeClassAttendanceRate($presensiByAnggota),
                ],
            ];
        }

        $mapels = $this->getActiveSdMapels();
        $kkmByMapel = $this->getKkmByMapel($activeTahunAjaran->id, (string) $selectedKelas->tingkatan_kelas);
        [$teachersByMapel, $userTaughtMapelIds] = $this->getTeachersByMapel(
            $activeTahunAjaran->id,
            $selectedKelas->id,
            $scope['nipy']
        );

        $rekapLookup = [];
        if ($anggotaIds !== []) {
            $rekapRows = RekapMapel::query()
                ->select([
                    'id',
                    'akademik_pelajaran_mata_pelajaran_id',
                    'anggota_kelas_id',
                    'periode',
                    'rata_rata_tp',
                    'rata_rata_sumatif',
                    'nilai_ujian',
                    'nilai_akhir_rapor',
                    'nilai_kkm_snapshot',
                    'predikat_rapor',
                    'tuntas_kkm',
                ])
                ->where('akademik_master_tahun_ajaran_id', $activeTahunAjaran->id)
                ->whereIn('anggota_kelas_id', $anggotaIds)
                ->where('periode', $periode)
                ->get();

            foreach ($rekapRows as $rekap) {
                $rekapLookup["{$rekap->anggota_kelas_id}:{$rekap->akademik_pelajaran_mata_pelajaran_id}"] = $rekap;
            }
        }

        $mapelScoreSums = [];
        $mapelScoredCounts = [];
        $mapelTuntasCounts = [];
        $allScores = [];
        $totalTuntasEntries = 0;
        $totalScoredEntries = 0;
        $studentsWithAnyLowKkm = 0;

        $studentRows = $students->map(function (AnggotaKelas $anggota) use (
            $mapels,
            $rekapLookup,
            $presensiByAnggota,
            &$mapelScoreSums,
            &$mapelScoredCounts,
            &$mapelTuntasCounts,
            &$allScores,
            &$totalTuntasEntries,
            &$totalScoredEntries,
            &$studentsWithAnyLowKkm
        ): array {
            $cells = [];
            $studentScores = [];
            $tuntasCount = 0;
            $belumTuntasCount = 0;

            foreach ($mapels as $mapel) {
                $rekap = $rekapLookup["{$anggota->id}:{$mapel->id}"] ?? null;
                $nilaiRapor = $rekap?->nilai_akhir_rapor;
                $tuntas = $rekap?->tuntas_kkm;

                if ($nilaiRapor !== null) {
                    $floatVal = (float) $nilaiRapor;
                    $studentScores[] = $floatVal;
                    $allScores[] = $floatVal;
                    $totalScoredEntries++;

                    $mapelScoreSums[$mapel->id] = ($mapelScoreSums[$mapel->id] ?? 0.0) + $floatVal;
                    $mapelScoredCounts[$mapel->id] = ($mapelScoredCounts[$mapel->id] ?? 0) + 1;

                    if ($tuntas === true) {
                        $tuntasCount++;
                        $totalTuntasEntries++;
                        $mapelTuntasCounts[$mapel->id] = ($mapelTuntasCounts[$mapel->id] ?? 0) + 1;
                    } elseif ($tuntas === false) {
                        $belumTuntasCount++;
                    }
                }

                $cells[$mapel->id] = [
                    'nilai' => $nilaiRapor,
                    'predikat' => $rekap?->predikat_rapor,
                    'tuntas' => $tuntas,
                ];
            }

            if ($belumTuntasCount > 0) {
                $studentsWithAnyLowKkm++;
            }

            return [
                'anggota' => $anggota,
                'presensi' => $presensiByAnggota[$anggota->id] ?? $this->emptyPresensiCounts(),
                'mapel_cells' => $cells,
                'dinilai_count' => count($studentScores),
                'tuntas_count' => $tuntasCount,
                'belum_tuntas_count' => $belumTuntasCount,
                'rata_rapor' => $studentScores !== [] ? round(array_sum($studentScores) / count($studentScores), 1) : null,
            ];
        });

        $mapelSummaries = $mapels->map(function (MataPelajaran $mapel) use (
            $kkmByMapel,
            $teachersByMapel,
            $userTaughtMapelIds,
            $mapelScoreSums,
            $mapelScoredCounts,
            $mapelTuntasCounts
        ): array {
            $dinilai = $mapelScoredCounts[$mapel->id] ?? 0;
            $tuntas = $mapelTuntasCounts[$mapel->id] ?? 0;

            return [
                'mapel' => $mapel,
                'kkm' => (float) ($kkmByMapel->get($mapel->id)?->nilai_kkm ?? Kkm::DEFAULT_KKM),
                'teachers' => $teachersByMapel[$mapel->id] ?? [],
                'is_taught_by_user' => isset($userTaughtMapelIds[$mapel->id]),
                'dinilai_count' => $dinilai,
                'tuntas_count' => $tuntas,
                'belum_tuntas_count' => max(0, $dinilai - $tuntas),
                'avg_rapor' => $dinilai > 0 ? round($mapelScoreSums[$mapel->id] / $dinilai, 1) : null,
            ];
        });

        return [
            ...$scope,
            'periode' => $periode,
            'isSd' => true,
            'students' => $students,
            'studentStats' => $studentStats,
            'mapels' => $mapels,
            'mapelSummaries' => $mapelSummaries,
            'studentRows' => $studentRows,
            'overviewStats' => [
                'avg_rapor' => $allScores !== [] ? round(array_sum($allScores) / count($allScores), 1) : null,
                'tuntas_rate' => $totalScoredEntries > 0 ? round(($totalTuntasEntries / $totalScoredEntries) * 100, 1) : null,
                'scored_entries' => $totalScoredEntries,
                'tuntas_entries' => $totalTuntasEntries,
                'need_attention_students' => $studentsWithAnyLowKkm,
                'avg_kehadiran' => $this->computeClassAttendanceRate($presensiByAnggota),
            ],
        ];
    }

    /**
     * Memuat detail perkembangan lengkap satu siswa di kelas binaan pada seluruh mapel, TP, dan presensi (anti-N+1).
     *
     * @return array<string, mixed>
     */
    public function getStudentDetailData(
        User $user,
        AnggotaKelas $anggotaKelas,
        string $periodeInput = PeriodeTp::PERIODE_MID
    ): array {
        $scope = $this->scopeService->authorizeAnggotaKelasForUser($user, $anggotaKelas);
        $periode = $this->normalizePeriode($periodeInput);
        $selectedKelas = $scope['selectedKelas'];
        $activeTahunAjaran = $scope['activeTahunAjaran'];

        $anggotaKelas->setRelation('kelas', $selectedKelas);

        $siswa = $anggotaKelas->relationLoaded('siswa')
            ? $anggotaKelas->getRelation('siswa')
            : Siswa::query()
                ->select(['nis', 'nama_lengkap', 'nisn', 'jenis_kelamin', 'status', 'avatar', 'whatsapp', 'nomor_hp', 'nama_ayah', 'nama_ibu'])
                ->where('nis', $anggotaKelas->siswa_nis)
                ->first();

        if (! $siswa instanceof Siswa) {
            abort(404, 'Data siswa tidak ditemukan.');
        }
        $anggotaKelas->setRelation('siswa', $siswa);

        $presensiMap = $this->buildPresensiSummaryByAnggota([(int) $anggotaKelas->id]);
        $presensiStats = $presensiMap[$anggotaKelas->id] ?? $this->emptyPresensiCounts();

        $isSd = strtoupper(trim((string) $selectedKelas->jenjang_kelas)) === Kkm::TARGET_UNIT;
        if (! $isSd || $activeTahunAjaran === null) {
            return [
                ...$scope,
                'periode' => $periode,
                'isSd' => false,
                'anggotaKelas' => $anggotaKelas,
                'siswa' => $siswa,
                'presensiStats' => $presensiStats,
                'subjectDetails' => collect(),
                'summary' => [
                    'total_mapel' => 0,
                    'dinilai_count' => 0,
                    'tuntas_count' => 0,
                    'belum_tuntas_count' => 0,
                    'rata_rapor' => null,
                ],
            ];
        }

        $mapels = $this->getActiveSdMapels();
        $kkmByMapel = $this->getKkmByMapel($activeTahunAjaran->id, (string) $selectedKelas->tingkatan_kelas);
        [$teachersByMapel, $userTaughtMapelIds] = $this->getTeachersByMapel(
            $activeTahunAjaran->id,
            $selectedKelas->id,
            $scope['nipy']
        );

        $rekapMapels = RekapMapel::query()
            ->select([
                'id',
                'akademik_pelajaran_mata_pelajaran_id',
                'anggota_kelas_id',
                'periode',
                'rata_rata_tp',
                'rata_rata_sumatif',
                'nilai_ujian',
                'nilai_akhir_rapor',
                'jumlah_tp_dinilai',
                'nilai_kkm_snapshot',
                'bobot_tp_snapshot',
                'bobot_sumatif_snapshot',
                'bobot_ujian_snapshot',
                'predikat_rapor',
                'keterangan_predikat',
                'tuntas_kkm',
                'deskripsi_capaian',
            ])
            ->where('akademik_master_tahun_ajaran_id', $activeTahunAjaran->id)
            ->where('anggota_kelas_id', $anggotaKelas->id)
            ->whereIn('periode', [PeriodeTp::PERIODE_MID, PeriodeTp::PERIODE_SEMESTER])
            ->get();

        $rekapMapelLookup = [];
        foreach ($rekapMapels as $rm) {
            $rekapMapelLookup["{$rm->akademik_pelajaran_mata_pelajaran_id}:{$rm->periode}"] = $rm;
        }

        $tpsByMapel = TujuanPembelajaran::query()
            ->select([
                'id',
                'akademik_pelajaran_mata_pelajaran_id',
                'kode_tp',
                'tujuan_pembelajaran',
                'lingkup_materi',
                'urutan',
            ])
            ->where('akademik_master_tahun_ajaran_id', $activeTahunAjaran->id)
            ->where('tingkatan_kelas', (string) $selectedKelas->tingkatan_kelas)
            ->where('status', true)
            ->ordered()
            ->get()
            ->groupBy('akademik_pelajaran_mata_pelajaran_id');

        $rekapTpLookup = [];
        $rekapTpRows = RekapTp::query()
            ->select(['id', 'tujuan_pembelajaran_id', 'periode', 'nilai_akhir_tp', 'predikat', 'tuntas_kkm'])
            ->where('anggota_kelas_id', $anggotaKelas->id)
            ->whereIn('periode', [PeriodeTp::PERIODE_MID, PeriodeTp::PERIODE_SEMESTER])
            ->get();

        foreach ($rekapTpRows as $rekapTp) {
            $rekapTpLookup["{$rekapTp->tujuan_pembelajaran_id}:{$rekapTp->periode}"] = $rekapTp;
        }

        $scores = [];
        $tuntasCount = 0;
        $belumTuntasCount = 0;

        $subjectDetails = $mapels->map(function (MataPelajaran $mapel) use (
            $periode,
            $kkmByMapel,
            $teachersByMapel,
            $userTaughtMapelIds,
            $rekapMapelLookup,
            $tpsByMapel,
            $rekapTpLookup,
            &$scores,
            &$tuntasCount,
            &$belumTuntasCount
        ): array {
            $rekapMid = $rekapMapelLookup["{$mapel->id}:".PeriodeTp::PERIODE_MID] ?? null;
            $rekapSem = $rekapMapelLookup["{$mapel->id}:".PeriodeTp::PERIODE_SEMESTER] ?? null;
            $selectedRekap = $periode === PeriodeTp::PERIODE_SEMESTER ? $rekapSem : $rekapMid;

            if ($selectedRekap?->nilai_akhir_rapor !== null) {
                $scores[] = (float) $selectedRekap->nilai_akhir_rapor;
                if ($selectedRekap->tuntas_kkm === true) {
                    $tuntasCount++;
                } elseif ($selectedRekap->tuntas_kkm === false) {
                    $belumTuntasCount++;
                }
            }

            $tpItems = $tpsByMapel->get($mapel->id, collect())->map(static function (TujuanPembelajaran $tp) use ($rekapTpLookup): array {
                return [
                    'tp' => $tp,
                    'mid' => $rekapTpLookup["{$tp->id}:".PeriodeTp::PERIODE_MID] ?? null,
                    'semester' => $rekapTpLookup["{$tp->id}:".PeriodeTp::PERIODE_SEMESTER] ?? null,
                ];
            })->values();

            return [
                'mapel' => $mapel,
                'kkm' => (float) ($selectedRekap?->nilai_kkm_snapshot ?? $kkmByMapel->get($mapel->id)?->nilai_kkm ?? Kkm::DEFAULT_KKM),
                'teachers' => $teachersByMapel[$mapel->id] ?? [],
                'is_taught_by_user' => isset($userTaughtMapelIds[$mapel->id]),
                'rekap_mid' => $rekapMid,
                'rekap_semester' => $rekapSem,
                'selected_rekap' => $selectedRekap,
                'tp_items' => $tpItems,
            ];
        });

        return [
            ...$scope,
            'periode' => $periode,
            'isSd' => true,
            'anggotaKelas' => $anggotaKelas,
            'siswa' => $siswa,
            'presensiStats' => $presensiStats,
            'subjectDetails' => $subjectDetails,
            'summary' => [
                'total_mapel' => $mapels->count(),
                'dinilai_count' => count($scores),
                'tuntas_count' => $tuntasCount,
                'belum_tuntas_count' => $belumTuntasCount,
                'rata_rapor' => $scores !== [] ? round(array_sum($scores) / count($scores), 1) : null,
            ],
        ];
    }

    private function normalizePeriode(string $periodeInput): string
    {
        return strtolower(trim($periodeInput)) === PeriodeTp::PERIODE_SEMESTER
            ? PeriodeTp::PERIODE_SEMESTER
            : PeriodeTp::PERIODE_MID;
    }

    /**
     * @return EloquentCollection<int, MataPelajaran>
     */
    private function getActiveSdMapels(): EloquentCollection
    {
        return MataPelajaran::query()
            ->select(['id', 'singkatan', 'nama_mata_pelajaran', 'sifat', 'urutan'])
            ->where('unit', Kkm::TARGET_UNIT)
            ->where('status', true)
            ->ordered()
            ->get();
    }

    /**
     * @return Collection<int, Kkm>
     */
    private function getKkmByMapel(int $tahunAjaranId, string $tingkatanKelas): Collection
    {
        return Kkm::query()
            ->select(['id', 'akademik_pelajaran_mata_pelajaran_id', 'nilai_kkm'])
            ->where('akademik_master_tahun_ajaran_id', $tahunAjaranId)
            ->where('tingkatan_kelas', $tingkatanKelas)
            ->get()
            ->keyBy('akademik_pelajaran_mata_pelajaran_id');
    }

    /**
     * @return array{0: array<int, list<string>>, 1: array<int, bool>}
     */
    private function getTeachersByMapel(int $tahunAjaranId, int $kelasId, string $userNipy): array
    {
        $rows = JadwalPelajaran::query()
            ->select(['akademik_pelajaran_mata_pelajaran_id', 'karyawan_nipy'])
            ->with(['guru:nipy,nama_lengkap,gelar'])
            ->where('akademik_master_tahun_ajaran_id', $tahunAjaranId)
            ->where('akademik_master_kelas_id', $kelasId)
            ->whereNotNull('akademik_pelajaran_mata_pelajaran_id')
            ->where('status', true)
            ->get();

        $teachersByMapel = [];
        $userTaughtMapelIds = [];

        foreach ($rows as $row) {
            $mapelId = (int) $row->akademik_pelajaran_mata_pelajaran_id;
            $nipy = trim((string) ($row->karyawan_nipy ?? ''));
            $teacherName = $row->guru?->nama_gelar ?? ($nipy !== '' ? $nipy : null);

            if ($teacherName !== null && ! in_array($teacherName, $teachersByMapel[$mapelId] ?? [], true)) {
                $teachersByMapel[$mapelId][] = $teacherName;
            }

            if ($userNipy !== '' && $nipy === $userNipy) {
                $userTaughtMapelIds[$mapelId] = true;
            }
        }

        return [$teachersByMapel, $userTaughtMapelIds];
    }

    /**
     * @param  list<int>  $anggotaIds
     * @return array<int, array<string, int|float|null>>
     */
    private function buildPresensiSummaryByAnggota(array $anggotaIds): array
    {
        if ($anggotaIds === []) {
            return [];
        }

        $rows = PresensiSiswa::query()
            ->selectRaw('anggota_kelas_id, status, COUNT(*) as total')
            ->whereIn('anggota_kelas_id', $anggotaIds)
            ->groupBy('anggota_kelas_id', 'status')
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $anggotaId = (int) $row->anggota_kelas_id;
            if (! isset($grouped[$anggotaId])) {
                $grouped[$anggotaId] = $this->emptyPresensiCounts();
            }

            $count = (int) $row->total;
            $statusKey = strtolower(trim((string) $row->status));
            $grouped[$anggotaId]['total'] += $count;

            if (array_key_exists($statusKey, $grouped[$anggotaId])) {
                $grouped[$anggotaId][$statusKey] += $count;
            }
        }

        foreach ($grouped as $anggotaId => $counts) {
            $total = (int) $counts['total'];
            $efektif = (int) $counts['hadir'] + (int) $counts['terlambat'];
            $grouped[$anggotaId]['hadir_rate'] = $total > 0 ? round(($efektif / $total) * 100, 1) : null;
        }

        return $grouped;
    }

    /**
     * @return array<string, int|float|null>
     */
    private function emptyPresensiCounts(): array
    {
        return [
            'total' => 0,
            'hadir' => 0,
            'terlambat' => 0,
            'izin' => 0,
            'sakit' => 0,
            'alpa' => 0,
            'hadir_rate' => null,
        ];
    }

    /**
     * @param  array<int, array<string, int|float|null>>  $presensiByAnggota
     */
    private function computeClassAttendanceRate(array $presensiByAnggota): ?float
    {
        $total = 0;
        $efektif = 0;

        foreach ($presensiByAnggota as $counts) {
            $total += (int) ($counts['total'] ?? 0);
            $efektif += (int) ($counts['hadir'] ?? 0) + (int) ($counts['terlambat'] ?? 0);
        }

        return $total > 0 ? round(($efektif / $total) * 100, 1) : null;
    }

    /**
     * @return array<string, int|float|null>
     */
    private function emptyOverviewStats(): array
    {
        return [
            'avg_rapor' => null,
            'tuntas_rate' => null,
            'scored_entries' => 0,
            'tuntas_entries' => 0,
            'need_attention_students' => 0,
            'avg_kehadiran' => null,
        ];
    }
}

