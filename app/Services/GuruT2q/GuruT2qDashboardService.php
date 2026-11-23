<?php

namespace App\Services\GuruT2q;

use App\Models\Admin\DataMaster\AnggotaT2q;
use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Models\Admin\DataMaster\TargetCapaian;
use App\Models\Admin\Informasi\AgendaSekolah;
use App\Models\Admin\Informasi\Pengumuman;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Models\Admin\T2q\YaumiyahTahfizh;
use App\Models\Admin\T2q\YaumiyahTahsin;
use App\Models\User;
use App\Services\WaliKelas\WaliKelasEkstrakurikulerService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class GuruT2qDashboardService
{
    public function __construct(
        protected ?GuruT2qScopeService $scopeService = null,
        protected ?WaliKelasEkstrakurikulerService $ekskulService = null
    ) {
        $this->scopeService ??= new GuruT2qScopeService;
        $this->ekskulService ??= new WaliKelasEkstrakurikulerService;
    }

    /**
     * Menyusun ringkasan dashboard khusus Guru T2Q (lintas jenjang, tanpa relasi menu kelas, anti-N+1).
     *
     * @return array<string, mixed>
     */
    public function getDashboardData(User $user): array
    {
        $today = Carbon::now();
        $todayStr = $today->toDateString();
        $scope = $this->scopeService->getActiveScopeForUser($user, true);
        $activeTahunAjaran = $scope['activeTahunAjaran'];
        $nipy = (string) ($scope['nipy'] ?? '');
        $karyawanProfile = $scope['karyawanProfile'] ?? null;

        if ($activeTahunAjaran === null || $nipy === '') {
            return [
                'isEligible' => false,
                'ineligibleReason' => $activeTahunAjaran === null ? 'no_active_tahun_ajaran' : 'missing_nipy',
                'nipy' => $nipy,
                'karyawanProfile' => $karyawanProfile,
                'activeTahunAjaran' => $activeTahunAjaran,
                'todayDate' => $today,
                'activePeriode' => PeriodeTp::PERIODE_MID,
                'tingkatSummaries' => collect(),
                'studentRows' => collect(),
                'assignedEkskuls' => new EloquentCollection,
                't2qFinalisasi' => [],
                'stats' => $this->emptyStats(),
                'upcomingAgendas' => new EloquentCollection,
                'latestPengumuman' => new EloquentCollection,
            ];
        }

        $assignedEkskuls = new EloquentCollection(
            $this->ekskulService
                ->getAssignedEkstrakurikulerForUser($user, $activeTahunAjaran)
                ->reject(static fn (Ekstrakurikuler $eks): bool => $eks->isWajib())
                ->values()
                ->all()
        );

        if (! $scope['isEligible'] && $assignedEkskuls->isEmpty()) {
            return [
                'isEligible' => false,
                'ineligibleReason' => $scope['ineligibleReason'] ?? 'not_assigned_active_year',
                'nipy' => $nipy,
                'karyawanProfile' => $karyawanProfile,
                'activeTahunAjaran' => $activeTahunAjaran,
                'todayDate' => $today,
                'activePeriode' => PeriodeTp::PERIODE_MID,
                'tingkatSummaries' => collect(),
                'studentRows' => collect(),
                'assignedEkskuls' => new EloquentCollection,
                't2qFinalisasi' => [],
                'stats' => $this->emptyStats(),
                'upcomingAgendas' => new EloquentCollection,
                'latestPengumuman' => new EloquentCollection,
            ];
        }

        $isMidSemester = str_contains(strtolower((string) $activeTahunAjaran->semester), 'mid');
        $activePeriode = $isMidSemester ? PeriodeTp::PERIODE_MID : PeriodeTp::PERIODE_SEMESTER;

        /** @var EloquentCollection<int, AnggotaT2q> $members */
        $members = $scope['anggotaT2qList'];
        $anggotaIds = $members->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all();
        $tingkatOptions = $scope['tingkatOptions'];

        $todayTahsin = YaumiyahTahsin::query()
            ->withMasterRelations()
            ->forAnggotaT2qIds($anggotaIds)
            ->forTanggal($todayStr)
            ->get()
            ->keyBy('anggota_t2q_id');

        $todayTahfizh = YaumiyahTahfizh::query()
            ->withMasterRelations()
            ->forAnggotaT2qIds($anggotaIds)
            ->forTanggal($todayStr)
            ->get()
            ->keyBy('anggota_t2q_id');

        $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEnd = $today->copy()->startOfWeek(Carbon::MONDAY)->addDays(3)->toDateString();

        $weekTahsin = YaumiyahTahsin::query()
            ->select(['id', 'anggota_t2q_id', 'nilai', 'tanggal'])
            ->forAnggotaT2qIds($anggotaIds)
            ->forDateRange($weekStart, $weekEnd)
            ->get();

        $weekTahfizh = YaumiyahTahfizh::query()
            ->select(['id', 'anggota_t2q_id', 'nilai', 'tanggal'])
            ->forAnggotaT2qIds($anggotaIds)
            ->forDateRange($weekStart, $weekEnd)
            ->get();

        $weekScoreSum = 0.0;
        $weekScoreCount = 0;
        foreach ([$weekTahsin, $weekTahfizh] as $collection) {
            foreach ($collection as $item) {
                if ($item->nilai !== null) {
                    $weekScoreSum += (float) $item->nilai;
                    $weekScoreCount++;
                }
            }
        }
        $weekAvgScore = $weekScoreCount > 0 ? round($weekScoreSum / $weekScoreCount, 1) : null;

        $targetsByTingkat = $tingkatOptions !== []
            ? TargetCapaian::query()
                ->with([
                    'daftarJilid:id,jilid_latin,jilid_arab,jenis,urutan',
                    'surah:id,nama_surah,nama_surah_arab,jumlah_ayat',
                ])
                ->forTahunAjaran((int) $activeTahunAjaran->id)
                ->whereIn('tingkat', $tingkatOptions)
                ->orderBy('id')
                ->get()
                ->groupBy(static fn (TargetCapaian $tc): string => AnggotaT2q::normalizeTingkat($tc->tingkat))
            : collect();

        $baruCount = 0;
        $lanjutanCount = 0;
        $tingkatBuckets = [];
        $studentRows = [];

        foreach ($members as $anggota) {
            $pendaftaran = trim((string) ($anggota->pendaftaran ?? ''));
            if (strcasecmp($pendaftaran, 'Baru') === 0) {
                $baruCount++;
            } else {
                $lanjutanCount++;
            }

            $tingkat = AnggotaT2q::normalizeTingkat($anggota->tingkat ?? '') ?: '-';
            $tahsinEntry = $todayTahsin->get($anggota->id);
            $tahfizhEntry = $todayTahfizh->get($anggota->id);

            if (! isset($tingkatBuckets[$tingkat])) {
                $targets = $targetsByTingkat->get($tingkat, collect())
                    ->map(static fn (TargetCapaian $tc): string => $tc->ringkasan_target)
                    ->filter(static fn (string $label): bool => $label !== '-')
                    ->values()
                    ->all();

                $tingkatBuckets[$tingkat] = [
                    'tingkat' => $tingkat,
                    'student_count' => 0,
                    'tahsin_today' => 0,
                    'tahfizh_today' => 0,
                    'targets' => $targets,
                ];
            }

            $tingkatBuckets[$tingkat]['student_count']++;
            if ($tahsinEntry !== null) {
                $tingkatBuckets[$tingkat]['tahsin_today']++;
            }
            if ($tahfizhEntry !== null) {
                $tingkatBuckets[$tingkat]['tahfizh_today']++;
            }

            $siswa = $anggota->relationLoaded('anggotaKelas') && $anggota->anggotaKelas?->relationLoaded('siswa')
                ? $anggota->anggotaKelas->siswa
                : null;

            $studentRows[] = [
                'anggota' => $anggota,
                'nama_siswa' => $siswa?->nama_lengkap ?? ($anggota->anggotaKelas?->siswa_nis ?? 'Siswa #'.$anggota->id),
                'nis' => $anggota->anggotaKelas?->siswa_nis ?? '-',
                'tingkat' => $tingkat,
                'pendaftaran' => $pendaftaran !== '' ? $pendaftaran : '-',
                'tahsin_today' => $tahsinEntry,
                'tahfizh_today' => $tahfizhEntry,
                'pending_today' => $tahsinEntry === null || $tahfizhEntry === null,
            ];
        }

        // Prioritaskan siswa yang belum lengkap setor hari ini agar langsung terlihat Guru T2Q
        usort($studentRows, static function (array $a, array $b): int {
            if ($a['pending_today'] !== $b['pending_today']) {
                return $a['pending_today'] ? -1 : 1;
            }

            return strnatcasecmp((string) $a['nama_siswa'], (string) $b['nama_siswa']);
        });

        $t2qFinalisasi = $scope['supervisedKelasIds'] !== []
            ? $this->scopeService->getFinalisasiSummaryForUser(
                (int) $activeTahunAjaran->id,
                $scope['supervisedKelasIds']
            )
            : [];

        $upcomingAgendas = AgendaSekolah::query()
            ->forSiswa()
            ->where('akademik_master_tahun_ajaran_id', $activeTahunAjaran->id)
            ->whereDate('tanggal', '>=', $todayStr)
            ->orderedChronologically()
            ->limit(4)
            ->get();

        if ($upcomingAgendas->isEmpty()) {
            $upcomingAgendas = AgendaSekolah::query()
                ->forSiswa()
                ->where('akademik_master_tahun_ajaran_id', $activeTahunAjaran->id)
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

        $totalStudents = $members->count();
        $tahsinTodayCount = $todayTahsin->count();
        $tahfizhTodayCount = $todayTahfizh->count();

        return [
            'isEligible' => true,
            'ineligibleReason' => null,
            'nipy' => $nipy,
            'karyawanProfile' => $karyawanProfile,
            'activeTahunAjaran' => $activeTahunAjaran,
            'todayDate' => $today,
            'weekRangeLabel' => Carbon::parse($weekStart)->locale('id')->translatedFormat('d M').' – '.Carbon::parse($weekEnd)->locale('id')->translatedFormat('d M Y'),
            'activePeriode' => $activePeriode,
            'tingkatSummaries' => collect(array_values($tingkatBuckets)),
            'studentRows' => collect($studentRows),
            'assignedEkskuls' => $assignedEkskuls,
            't2qFinalisasi' => $t2qFinalisasi,
            'stats' => [
                'total_students' => $totalStudents,
                'baru_count' => $baruCount,
                'lanjutan_count' => $lanjutanCount,
                'tingkat_count' => count($tingkatBuckets),
                'tahsin_today_count' => $tahsinTodayCount,
                'tahfizh_today_count' => $tahfizhTodayCount,
                'tahsin_today_pct' => $totalStudents > 0 ? (int) round(($tahsinTodayCount / $totalStudents) * 100) : 0,
                'tahfizh_today_pct' => $totalStudents > 0 ? (int) round(($tahfizhTodayCount / $totalStudents) * 100) : 0,
                'week_recorded_count' => $weekTahsin->count() + $weekTahfizh->count(),
                'week_avg_score' => $weekAvgScore,
                'ekskul_count' => $assignedEkskuls->count(),
            ],
            'upcomingAgendas' => $upcomingAgendas,
            'latestPengumuman' => $latestPengumuman,
        ];
    }

    /**
     * @return array<string, int|float|null>
     */
    private function emptyStats(): array
    {
        return [
            'total_students' => 0,
            'baru_count' => 0,
            'lanjutan_count' => 0,
            'tingkat_count' => 0,
            'tahsin_today_count' => 0,
            'tahfizh_today_count' => 0,
            'tahsin_today_pct' => 0,
            'tahfizh_today_pct' => 0,
            'week_recorded_count' => 0,
            'week_avg_score' => null,
            'ekskul_count' => 0,
        ];
    }
}
