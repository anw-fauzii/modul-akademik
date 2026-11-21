<?php

namespace App\Services\WaliKelas;

use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Informasi\AgendaSekolah;
use App\Models\Admin\Informasi\Pengumuman;
use App\Models\Admin\Pelajaran\JadwalPelajaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Admin\Penilaian\Kkm;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Models\Admin\Penilaian\RekapMapel;
use App\Models\Admin\Presensi\PresensiSiswa;
use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use App\Models\User;
use App\Services\Admin\Penilaian\PenilaianFinalisasiService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class WaliKelasDashboardService
{
    public function __construct(
        protected ?WaliKelasScopeService $scopeService = null,
        protected ?PenilaianFinalisasiService $finalisasiService = null
    ) {
        $this->scopeService ??= new WaliKelasScopeService;
        $this->finalisasiService ??= new PenilaianFinalisasiService($this->scopeService);
    }

    /**
     * Mengambil daftar kelas pada tahun ajaran aktif di mana user bertugas sebagai Wali Kelas atau Pendamping.
     */
    public function getAssignedClassesForUser(User $user, ?TahunAjaran $activeTahunAjaran = null): EloquentCollection
    {
        return $this->scopeService->getAssignedClassesForUser($user, $activeTahunAjaran);
    }

    /**
     * Menentukan peran user di dalam kelas (Wali Kelas atau Guru Pendamping).
     */
    public function resolveRoleInKelas(Kelas $kelas, string $nipy): string
    {
        return $this->scopeService->resolveRoleInKelas($kelas, $nipy);
    }

    /**
     * Memastikan user benar-benar bertugas sebagai Wali Kelas atau Pendamping di tahun ajaran aktif.
     */
    public function isAssignedInActiveYear(User $user, ?int $kelasId = null): bool
    {
        return $this->scopeService->isAssignedInActiveYear($user, $kelasId);
    }

    /**
     * Menyusun seluruh data ringkasan Dashboard Wali Kelas berdasarkan kelas binaan di tahun ajaran aktif.
     *
     * @return array<string, mixed>
     */
    public function getDashboardData(User $user, ?int $requestedKelasId = null): array
    {
        $scope = $this->scopeService->resolveScopeContext($user, $requestedKelasId);
        $today = Carbon::now();

        if (! $scope['isEligible'] || $scope['selectedKelas'] === null || $scope['activeTahunAjaran'] === null) {
            return [
                ...$scope,
                'students' => new EloquentCollection,
                'studentStats' => $this->scopeService->summarizeStudents(new EloquentCollection),
                'todayDate' => $today,
                'todayPresensiByAnggota' => [],
                'presensiTodaySummary' => $this->emptyPresensiTodaySummary(),
                'presensiMonthSummary' => ['total' => 0, 'hadir_rate' => null, 'hadir_dan_terlambat' => 0],
                'todayDayNumber' => (int) $today->dayOfWeekIso,
                'todayDayLabel' => $this->resolveDayLabel((int) $today->dayOfWeekIso),
                'isWeekday' => $today->dayOfWeekIso <= 5,
                'jadwalHariIni' => new EloquentCollection,
                'jadwalMingguanCount' => 0,
                'penilaianSummary' => null,
                'raporReadiness' => null,
                'upcomingAgendas' => new EloquentCollection,
                'latestPengumuman' => new EloquentCollection,
            ];
        }

        $activeTahunAjaran = $scope['activeTahunAjaran'];
        $selectedKelas = $scope['selectedKelas'];

        if (
            $requestedKelasId !== null
            && $requestedKelasId > 0
            && ! $scope['assignedClasses']->contains('id', $requestedKelasId)
        ) {
            abort(403, 'Anda tidak memiliki akses ke dashboard kelas ini.');
        }

        $students = $this->scopeService->sortAnggotaByStudentName(
            AnggotaKelas::query()
                ->select(['id', 'siswa_nis', 'akademik_master_kelas_id', 'pendaftaran'])
                ->with(['siswa:nis,nama_lengkap,nisn,jenis_kelamin,status,avatar'])
                ->where('akademik_master_kelas_id', $selectedKelas->id)
                ->get()
        );

        $anggotaIds = $students->pluck('id')->all();
        $studentStats = $this->scopeService->summarizeStudents($students);
        $totalSiswa = $studentStats['total'];

        [$presensiTodaySummary, $todayPresensiByAnggota] = $this->buildTodayPresensiSummary($anggotaIds, $totalSiswa, $today);
        $presensiMonthSummary = $this->buildMonthPresensiSummary($anggotaIds, $today);

        $todayDayNumber = (int) $today->dayOfWeekIso;
        $isWeekday = $todayDayNumber >= JadwalPelajaran::HARI_SENIN && $todayDayNumber <= JadwalPelajaran::HARI_JUMAT;
        $jadwalHariIni = $isWeekday
            ? $this->getJadwalForDay($selectedKelas->id, $activeTahunAjaran->id, $todayDayNumber)
            : new EloquentCollection;
        $jadwalMingguanCount = JadwalPelajaran::query()
            ->where('akademik_master_kelas_id', $selectedKelas->id)
            ->where('akademik_master_tahun_ajaran_id', $activeTahunAjaran->id)
            ->where('status', true)
            ->count();

        $penilaianSummary = $this->buildSdPenilaianSummary(
            $selectedKelas,
            $activeTahunAjaran,
            $anggotaIds,
            $totalSiswa
        );
        $activePeriode = $penilaianSummary['periode'] ?? (str_contains(strtolower((string) $activeTahunAjaran->semester), 'mid')
            ? PeriodeTp::PERIODE_MID
            : PeriodeTp::PERIODE_SEMESTER);
        $raporReadiness = $this->finalisasiService->getClassReadiness($selectedKelas, $activePeriode, $user);

        $upcomingAgendas = $this->getRelevantAgendas($activeTahunAjaran->id, (string) $selectedKelas->jenjang_kelas, $today);
        $latestPengumuman = Pengumuman::query()
            ->forSiswa()
            ->where('akademik_master_tahun_ajaran_id', $activeTahunAjaran->id)
            ->orderedLatest()
            ->limit(4)
            ->get();

        return [
            ...$scope,
            'students' => $students,
            'studentStats' => $studentStats,
            'todayDate' => $today,
            'todayPresensiByAnggota' => $todayPresensiByAnggota,
            'presensiTodaySummary' => $presensiTodaySummary,
            'presensiMonthSummary' => $presensiMonthSummary,
            'todayDayNumber' => $todayDayNumber,
            'todayDayLabel' => $this->resolveDayLabel($todayDayNumber),
            'isWeekday' => $isWeekday,
            'jadwalHariIni' => $jadwalHariIni,
            'jadwalMingguanCount' => $jadwalMingguanCount,
            'penilaianSummary' => $penilaianSummary,
            'raporReadiness' => $raporReadiness,
            'upcomingAgendas' => $upcomingAgendas,
            'latestPengumuman' => $latestPengumuman,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function emptyPresensiTodaySummary(): array
    {
        return [
            'recorded' => 0,
            'hadir' => 0,
            'terlambat' => 0,
            'izin' => 0,
            'sakit' => 0,
            'alpa' => 0,
            'belum' => 0,
        ];
    }

    /**
     * @param  list<int>  $anggotaIds
     * @return array{0: array<string, int>, 1: array<int, PresensiSiswa>}
     */
    private function buildTodayPresensiSummary(array $anggotaIds, int $totalSiswa, Carbon $today): array
    {
        $summary = $this->emptyPresensiTodaySummary();
        $summary['belum'] = $totalSiswa;

        if ($anggotaIds === []) {
            return [$summary, []];
        }

        $rows = PresensiSiswa::query()
            ->select(['id', 'anggota_kelas_id', 'tanggal', 'jam_masuk', 'status', 'keterlambatan_menit'])
            ->whereIn('anggota_kelas_id', $anggotaIds)
            ->whereDate('tanggal', $today->toDateString())
            ->get();

        $byAnggota = [];
        foreach ($rows as $row) {
            $byAnggota[$row->anggota_kelas_id] = $row;
            $statusKey = strtolower(trim((string) $row->status));
            if (array_key_exists($statusKey, $summary)) {
                $summary[$statusKey]++;
            }
        }

        $summary['recorded'] = count($byAnggota);
        $summary['belum'] = max(0, $totalSiswa - $summary['recorded']);

        return [$summary, $byAnggota];
    }

    /**
     * @param  list<int>  $anggotaIds
     * @return array{total: int, hadir_rate: float|null, hadir_dan_terlambat: int}
     */
    private function buildMonthPresensiSummary(array $anggotaIds, Carbon $today): array
    {
        if ($anggotaIds === []) {
            return ['total' => 0, 'hadir_rate' => null, 'hadir_dan_terlambat' => 0];
        }

        $rows = PresensiSiswa::query()
            ->selectRaw('status, COUNT(*) as total')
            ->whereIn('anggota_kelas_id', $anggotaIds)
            ->whereBetween('tanggal', [
                $today->copy()->startOfMonth()->toDateString(),
                $today->copy()->endOfMonth()->toDateString(),
            ])
            ->groupBy('status')
            ->get();

        $total = 0;
        $hadirDanTerlambat = 0;
        foreach ($rows as $row) {
            $count = (int) $row->total;
            $total += $count;
            if (in_array(strtolower((string) $row->status), ['hadir', 'terlambat'], true)) {
                $hadirDanTerlambat += $count;
            }
        }

        return [
            'total' => $total,
            'hadir_rate' => $total > 0 ? round(($hadirDanTerlambat / $total) * 100, 1) : null,
            'hadir_dan_terlambat' => $hadirDanTerlambat,
        ];
    }

    private function getJadwalForDay(int $kelasId, int $tahunAjaranId, int $hari): EloquentCollection
    {
        $items = JadwalPelajaran::query()
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
                'jamPelajaran:id,jam_ke,unit,jam_mulai,jam_selesai,keterangan',
                'mataPelajaran:id,singkatan,nama_mata_pelajaran,sifat,unit',
                'guru:nipy,nama_lengkap,gelar',
            ])
            ->where('akademik_master_kelas_id', $kelasId)
            ->where('akademik_master_tahun_ajaran_id', $tahunAjaranId)
            ->where('hari', $hari)
            ->where('status', true)
            ->get();

        return new EloquentCollection(
            $items->sortBy(static fn (JadwalPelajaran $item): int => (int) ($item->jamPelajaran?->jam_ke ?? 999))
                ->values()
                ->all()
        );
    }

    /**
     * Meringkas progres Penilaian TP khusus kelas jenjang SD (per mata pelajaran aktif).
     *
     * @param  list<int>  $anggotaIds
     * @return array<string, mixed>|null
     */
    private function buildSdPenilaianSummary(
        Kelas $kelas,
        TahunAjaran $activeTahunAjaran,
        array $anggotaIds,
        int $totalSiswa
    ): ?array {
        if (strtoupper(trim((string) $kelas->jenjang_kelas)) !== Kkm::TARGET_UNIT) {
            return null;
        }

        $isMidSemester = str_contains(strtolower((string) $activeTahunAjaran->semester), 'mid');
        $activePeriode = $isMidSemester ? PeriodeTp::PERIODE_MID : PeriodeTp::PERIODE_SEMESTER;

        $mapels = MataPelajaran::query()
            ->select(['id', 'singkatan', 'nama_mata_pelajaran', 'sifat', 'urutan'])
            ->where('unit', Kkm::TARGET_UNIT)
            ->where('status', true)
            ->ordered()
            ->get();

        if ($mapels->isEmpty()) {
            return [
                'periode' => $activePeriode,
                'periode_label' => PeriodeTp::PERIODE_OPTIONS[$activePeriode] ?? 'Mid Semester',
                'mapels' => collect(),
                'total_mapel' => 0,
                'assessed_mapel' => 0,
            ];
        }

        $mapelIds = $mapels->pluck('id')->all();

        $tpCounts = TujuanPembelajaran::query()
            ->selectRaw('akademik_pelajaran_mata_pelajaran_id, COUNT(*) as total')
            ->where('akademik_master_tahun_ajaran_id', $activeTahunAjaran->id)
            ->where('tingkatan_kelas', (string) $kelas->tingkatan_kelas)
            ->where('status', true)
            ->whereIn('akademik_pelajaran_mata_pelajaran_id', $mapelIds)
            ->groupBy('akademik_pelajaran_mata_pelajaran_id')
            ->pluck('total', 'akademik_pelajaran_mata_pelajaran_id');

        $rekapByMapel = new Collection;
        if ($anggotaIds !== []) {
            $rekapByMapel = RekapMapel::query()
                ->select(['akademik_pelajaran_mata_pelajaran_id', 'nilai_akhir_rapor', 'tuntas_kkm'])
                ->where('akademik_master_tahun_ajaran_id', $activeTahunAjaran->id)
                ->whereIn('akademik_pelajaran_mata_pelajaran_id', $mapelIds)
                ->whereIn('anggota_kelas_id', $anggotaIds)
                ->where('periode', $activePeriode)
                ->whereNotNull('nilai_akhir_rapor')
                ->get()
                ->groupBy('akademik_pelajaran_mata_pelajaran_id');
        }

        $assessedMapels = 0;
        $rows = $mapels->map(function (MataPelajaran $mapel) use ($tpCounts, $rekapByMapel, $totalSiswa, &$assessedMapels): array {
            /** @var Collection<int, RekapMapel> $scores */
            $scores = $rekapByMapel->get($mapel->id, collect());
            $dinilaiCount = $scores->count();
            if ($dinilaiCount > 0) {
                $assessedMapels++;
            }
            $tuntasCount = $scores->where('tuntas_kkm', true)->count();
            $avgScore = $dinilaiCount > 0 ? round((float) $scores->avg('nilai_akhir_rapor'), 1) : null;
            $progressPct = $totalSiswa > 0 ? min(100, (int) round(($dinilaiCount / $totalSiswa) * 100)) : 0;

            return [
                'id' => $mapel->id,
                'singkatan' => $mapel->singkatan,
                'nama' => $mapel->nama_mata_pelajaran,
                'sifat' => $mapel->sifat,
                'tp_count' => (int) ($tpCounts->get($mapel->id, 0)),
                'dinilai_count' => $dinilaiCount,
                'tuntas_count' => $tuntasCount,
                'avg_rapor' => $avgScore,
                'progress_pct' => $progressPct,
            ];
        });

        return [
            'periode' => $activePeriode,
            'periode_label' => PeriodeTp::PERIODE_OPTIONS[$activePeriode] ?? 'Mid Semester',
            'mapels' => $rows,
            'total_mapel' => $mapels->count(),
            'assessed_mapel' => $assessedMapels,
        ];
    }

    private function getRelevantAgendas(int $tahunAjaranId, string $jenjangKelas, Carbon $today): EloquentCollection
    {
        $normalizedUnit = strtoupper(trim($jenjangKelas));
        $allowedUnits = ['Semua Unit'];
        if ($normalizedUnit !== '') {
            $allowedUnits[] = $normalizedUnit;
        }

        $upcoming = AgendaSekolah::query()
            ->forSiswa()
            ->where('akademik_master_tahun_ajaran_id', $tahunAjaranId)
            ->whereIn('unit', $allowedUnits)
            ->whereDate('tanggal', '>=', $today->toDateString())
            ->orderedChronologically()
            ->limit(5)
            ->get();

        if ($upcoming->isNotEmpty()) {
            return $upcoming;
        }

        return AgendaSekolah::query()
            ->forSiswa()
            ->where('akademik_master_tahun_ajaran_id', $tahunAjaranId)
            ->whereIn('unit', $allowedUnits)
            ->orderByDesc('tanggal')
            ->limit(5)
            ->get();
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
