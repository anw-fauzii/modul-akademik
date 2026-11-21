<?php

namespace App\Services\GuruMapel;

use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\Informasi\AgendaSekolah;
use App\Models\Admin\Informasi\Pengumuman;
use App\Models\Admin\Pelajaran\JadwalPelajaran;
use App\Models\Admin\Penilaian\Kkm;
use App\Models\Admin\Penilaian\PenilaianFinalisasi;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Models\Admin\Penilaian\RekapMapel;
use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use App\Models\User;
use App\Services\WaliKelas\WaliKelasEkstrakurikulerService;
use App\Services\WaliKelas\WaliKelasScopeService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class GuruMapelDashboardService
{
    public function __construct(
        protected ?WaliKelasScopeService $scopeService = null,
        protected ?WaliKelasEkstrakurikulerService $ekskulService = null
    ) {
        $this->scopeService ??= new WaliKelasScopeService;
        $this->ekskulService ??= new WaliKelasEkstrakurikulerService($this->scopeService);
    }

    /**
     * Menyusun ringkasan dashboard khusus Guru Mapel SD pada Tahun Ajaran aktif tanpa N+1.
     *
     * @return array<string, mixed>
     */
    public function getDashboardData(User $user): array
    {
        $today = Carbon::now();
        $todayDayNumber = (int) $today->dayOfWeekIso;
        $todayDayLabel = $this->resolveDayLabel($todayDayNumber);
        $isWeekday = $todayDayNumber >= JadwalPelajaran::HARI_SENIN && $todayDayNumber <= JadwalPelajaran::HARI_JUMAT;

        $taughtScope = $this->scopeService->getTaughtSdScopeForUser($user, null, true);
        $activeTahunAjaran = $taughtScope['activeTahunAjaran'];
        $nipy = (string) ($taughtScope['nipy'] ?? '');
        $karyawanProfile = $taughtScope['karyawanProfile'] ?? null;

        if ($activeTahunAjaran === null || $nipy === '') {
            return [
                'isEligible' => false,
                'ineligibleReason' => $activeTahunAjaran === null ? 'no_active_tahun_ajaran' : 'missing_nipy',
                'nipy' => $nipy,
                'karyawanProfile' => $karyawanProfile,
                'activeTahunAjaran' => $activeTahunAjaran,
                'todayDate' => $today,
                'todayDayLabel' => $todayDayLabel,
                'isWeekday' => $isWeekday,
                'periode' => PeriodeTp::PERIODE_MID,
                'periodeLabel' => PeriodeTp::PERIODE_OPTIONS[PeriodeTp::PERIODE_MID] ?? 'Mid Semester',
                'taughtClasses' => new EloquentCollection,
                'taughtAssignments' => collect(),
                'assignedEkskuls' => new EloquentCollection,
                'jadwalHariIni' => new EloquentCollection,
                'jadwalMingguanCount' => 0,
                'stats' => $this->emptyStats(),
                'upcomingAgendas' => new EloquentCollection,
                'latestPengumuman' => new EloquentCollection,
            ];
        }

        $isMidSemester = str_contains(strtolower((string) $activeTahunAjaran->semester), 'mid');
        $activePeriode = $isMidSemester ? PeriodeTp::PERIODE_MID : PeriodeTp::PERIODE_SEMESTER;
        $periodeLabel = PeriodeTp::PERIODE_OPTIONS[$activePeriode] ?? 'Mid Semester';

        $assignedEkskuls = new EloquentCollection(
            $this->ekskulService
                ->getAssignedEkstrakurikulerForUser($user, $activeTahunAjaran)
                ->reject(static fn (Ekstrakurikuler $eks): bool => $eks->isWajib())
                ->values()
                ->all()
        );

        /** @var EloquentCollection<int, Kelas> $taughtClasses */
        $taughtClasses = $taughtScope['taughtClasses'];
        $hasTeachingOrEkskul = $taughtClasses->isNotEmpty() || $assignedEkskuls->isNotEmpty();

        if (! $hasTeachingOrEkskul) {
            return [
                'isEligible' => false,
                'ineligibleReason' => 'not_assigned_active_year',
                'nipy' => $nipy,
                'karyawanProfile' => $karyawanProfile,
                'activeTahunAjaran' => $activeTahunAjaran,
                'todayDate' => $today,
                'todayDayLabel' => $todayDayLabel,
                'isWeekday' => $isWeekday,
                'periode' => $activePeriode,
                'periodeLabel' => $periodeLabel,
                'taughtClasses' => new EloquentCollection,
                'taughtAssignments' => collect(),
                'assignedEkskuls' => new EloquentCollection,
                'jadwalHariIni' => new EloquentCollection,
                'jadwalMingguanCount' => 0,
                'stats' => $this->emptyStats(),
                'upcomingAgendas' => new EloquentCollection,
                'latestPengumuman' => new EloquentCollection,
            ];
        }

        // Batch-load jadwal mengajar pribadi (SD) dalam 1 query
        $allSlots = JadwalPelajaran::query()
            ->select([
                'id',
                'akademik_master_tahun_ajaran_id',
                'akademik_master_kelas_id',
                'akademik_pelajaran_jam_pelajaran_id',
                'akademik_pelajaran_mata_pelajaran_id',
                'karyawan_nipy',
                'hari',
                'ruang',
                'keterangan',
                'status',
            ])
            ->with([
                'kelas:id,nama_kelas,tingkatan_kelas,jenjang_kelas',
                'jamPelajaran:id,jam_ke,unit,jam_mulai,jam_selesai,keterangan',
                'mataPelajaran:id,singkatan,nama_mata_pelajaran,unit,status',
            ])
            ->where('akademik_master_tahun_ajaran_id', $activeTahunAjaran->id)
            ->where('karyawan_nipy', $nipy)
            ->where('status', true)
            ->whereHas('kelas', static fn ($q) => $q->whereRaw('UPPER(jenjang_kelas) = ?', [Kkm::TARGET_UNIT]))
            ->get();

        $jadwalHariIni = new EloquentCollection(
            $isWeekday
                ? $allSlots
                    ->where('hari', $todayDayNumber)
                    ->sortBy(static fn (JadwalPelajaran $item): int => (int) ($item->jamPelajaran?->jam_ke ?? 999))
                    ->values()
                    ->all()
                : []
        );

        $kelasIds = $taughtClasses->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all();
        $mapelIds = $taughtScope['taughtMapelsByClass']
            ->flatten(1)
            ->pluck('id')
            ->unique()
            ->map(static fn ($id): int => (int) $id)
            ->values()
            ->all();
        $tingkatans = $taughtClasses->pluck('tingkatan_kelas')->filter()->unique()->values()->all();

        $studentCountByKelas = [];
        $rekapStatsByPair = [];
        $tpCountByTingkatanMapel = [];
        $finalByPair = [];

        if ($kelasIds !== [] && $mapelIds !== []) {
            $studentCounts = AnggotaKelas::query()
                ->selectRaw('akademik_master_kelas_id, COUNT(*) as total')
                ->whereIn('akademik_master_kelas_id', $kelasIds)
                ->groupBy('akademik_master_kelas_id')
                ->pluck('total', 'akademik_master_kelas_id');

            foreach ($studentCounts as $kId => $cnt) {
                $studentCountByKelas[(int) $kId] = (int) $cnt;
            }

            $tpCounts = TujuanPembelajaran::query()
                ->selectRaw('tingkatan_kelas, akademik_pelajaran_mata_pelajaran_id, COUNT(*) as total')
                ->where('akademik_master_tahun_ajaran_id', $activeTahunAjaran->id)
                ->whereIn('tingkatan_kelas', $tingkatans)
                ->whereIn('akademik_pelajaran_mata_pelajaran_id', $mapelIds)
                ->where('status', true)
                ->groupBy('tingkatan_kelas', 'akademik_pelajaran_mata_pelajaran_id')
                ->get();

            foreach ($tpCounts as $tpRow) {
                $tpCountByTingkatanMapel["{$tpRow->tingkatan_kelas}:{$tpRow->akademik_pelajaran_mata_pelajaran_id}"] = (int) $tpRow->total;
            }

            $rekapRows = RekapMapel::query()
                ->select([
                    'rek.akademik_pelajaran_mata_pelajaran_id',
                    'ak.akademik_master_kelas_id',
                    'rek.nilai_akhir_rapor',
                    'rek.tuntas_kkm',
                ])
                ->from('akademik_penilaian_rekap_mapel as rek')
                ->join('akademik_master_anggota_kelas as ak', 'ak.id', '=', 'rek.anggota_kelas_id')
                ->where('rek.akademik_master_tahun_ajaran_id', $activeTahunAjaran->id)
                ->whereIn('ak.akademik_master_kelas_id', $kelasIds)
                ->whereIn('rek.akademik_pelajaran_mata_pelajaran_id', $mapelIds)
                ->where('rek.periode', $activePeriode)
                ->whereNotNull('rek.nilai_akhir_rapor')
                ->get();

            $buckets = [];
            foreach ($rekapRows as $r) {
                $pairKey = "{$r->akademik_master_kelas_id}:{$r->akademik_pelajaran_mata_pelajaran_id}";
                $buckets[$pairKey]['sum'] = ($buckets[$pairKey]['sum'] ?? 0.0) + (float) $r->nilai_akhir_rapor;
                $buckets[$pairKey]['count'] = ($buckets[$pairKey]['count'] ?? 0) + 1;
                if ($r->tuntas_kkm) {
                    $buckets[$pairKey]['tuntas'] = ($buckets[$pairKey]['tuntas'] ?? 0) + 1;
                }
            }
            foreach ($buckets as $pairKey => $b) {
                $rekapStatsByPair[$pairKey] = [
                    'dinilai' => $b['count'],
                    'tuntas' => $b['tuntas'] ?? 0,
                    'avg' => $b['count'] > 0 ? round($b['sum'] / $b['count'], 1) : null,
                ];
            }

            $finalRecords = PenilaianFinalisasi::query()
                ->where('akademik_master_tahun_ajaran_id', $activeTahunAjaran->id)
                ->where('jenis_komponen', PenilaianFinalisasi::JENIS_MAPEL)
                ->where('periode', $activePeriode)
                ->whereIn('akademik_master_kelas_id', $kelasIds)
                ->whereIn('akademik_pelajaran_mata_pelajaran_id', $mapelIds)
                ->where('status', PenilaianFinalisasi::STATUS_FINAL)
                ->get();

            foreach ($finalRecords as $rec) {
                $finalByPair["{$rec->akademik_master_kelas_id}:{$rec->akademik_pelajaran_mata_pelajaran_id}"] = true;
            }
        }

        $assignments = [];
        $finalCount = 0;
        $uniqueStudentCount = 0;
        foreach ($taughtClasses as $kelas) {
            $totalSiswa = $studentCountByKelas[(int) $kelas->id] ?? 0;
            $uniqueStudentCount += $totalSiswa;
            $classMapels = $taughtScope['taughtMapelsByClass']->get((int) $kelas->id, new EloquentCollection);

            foreach ($classMapels as $mp) {
                $pairKey = "{$kelas->id}:{$mp->id}";
                $stat = $rekapStatsByPair[$pairKey] ?? ['dinilai' => 0, 'tuntas' => 0, 'avg' => null];
                $isFinal = isset($finalByPair[$pairKey]);
                if ($isFinal) {
                    $finalCount++;
                }

                $assignments[] = [
                    'kelas' => $kelas,
                    'mapel' => $mp,
                    'total_siswa' => $totalSiswa,
                    'tp_count' => $tpCountByTingkatanMapel["{$kelas->tingkatan_kelas}:{$mp->id}"] ?? 0,
                    'dinilai_count' => $stat['dinilai'],
                    'tuntas_count' => $stat['tuntas'],
                    'avg_rapor' => $stat['avg'],
                    'progress_pct' => $totalSiswa > 0 ? min(100, (int) round(($stat['dinilai'] / $totalSiswa) * 100)) : 0,
                    'is_final' => $isFinal,
                ];
            }
        }

        $upcomingAgendas = AgendaSekolah::query()
            ->forSiswa()
            ->where('akademik_master_tahun_ajaran_id', $activeTahunAjaran->id)
            ->whereIn('unit', ['Semua Unit', Kkm::TARGET_UNIT])
            ->whereDate('tanggal', '>=', $today->toDateString())
            ->orderedChronologically()
            ->limit(4)
            ->get();

        if ($upcomingAgendas->isEmpty()) {
            $upcomingAgendas = AgendaSekolah::query()
                ->forSiswa()
                ->where('akademik_master_tahun_ajaran_id', $activeTahunAjaran->id)
                ->whereIn('unit', ['Semua Unit', Kkm::TARGET_UNIT])
                ->orderByDesc('tanggal')
                ->limit(4)
                ->get();
        }

        $latestPengumuman = Pengumuman::query()
            ->forSiswa()
            ->where('akademik_master_tahun_ajaran_id', $activeTahunAjaran->id)
            ->orderedLatest()
            ->limit(3)
            ->get();

        $totalAssignments = count($assignments);

        return [
            'isEligible' => true,
            'ineligibleReason' => null,
            'nipy' => $nipy,
            'karyawanProfile' => $karyawanProfile,
            'activeTahunAjaran' => $activeTahunAjaran,
            'todayDate' => $today,
            'todayDayLabel' => $todayDayLabel,
            'isWeekday' => $isWeekday,
            'periode' => $activePeriode,
            'periodeLabel' => $periodeLabel,
            'taughtClasses' => $taughtClasses,
            'taughtAssignments' => collect($assignments),
            'assignedEkskuls' => $assignedEkskuls,
            'jadwalHariIni' => $jadwalHariIni,
            'jadwalMingguanCount' => $allSlots->count(),
            'stats' => [
                'total_classes' => $taughtClasses->count(),
                'total_mapels' => count($mapelIds),
                'total_students' => $uniqueStudentCount,
                'total_assignments' => $totalAssignments,
                'final_assignments' => $finalCount,
                'draft_assignments' => max(0, $totalAssignments - $finalCount),
                'ekskul_count' => $assignedEkskuls->count(),
            ],
            'upcomingAgendas' => $upcomingAgendas,
            'latestPengumuman' => $latestPengumuman,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function emptyStats(): array
    {
        return [
            'total_classes' => 0,
            'total_mapels' => 0,
            'total_students' => 0,
            'total_assignments' => 0,
            'final_assignments' => 0,
            'draft_assignments' => 0,
            'ekskul_count' => 0,
        ];
    }

    private function resolveDayLabel(int $dayOfWeekIso): string
    {
        return JadwalPelajaran::HARI_OPTIONS[$dayOfWeekIso] ?? match ($dayOfWeekIso) {
            6 => 'Sabtu',
            7 => 'Minggu',
            default => '-',
        };
    }
}
