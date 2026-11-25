<?php

namespace App\Services\Admin\Dashboard;

use App\Models\Admin\DataMaster\AnggotaT2q;
use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Sekolah;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Informasi\AgendaSekolah;
use App\Models\Admin\Informasi\Pengumuman;
use App\Models\Admin\Pelajaran\JadwalPelajaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Admin\Penilaian\PenilaianFinalisasi;
use App\Models\Admin\Presensi\PresensiSiswa;
use App\Models\Admin\Prestasi\PrestasiSiswa;
use App\Models\Admin\T2q\YaumiyahTahfizh;
use App\Models\Admin\T2q\YaumiyahTahsin;
use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use App\Services\Admin\DataMaster\SiswaService;
use App\Services\Admin\Prestasi\PrestasiSiswaService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Schema;

class AdminDashboardService
{
    public function __construct(
        protected ?SiswaService $siswaService = null,
        protected ?PrestasiSiswaService $prestasiSiswaService = null
    ) {
        $this->siswaService ??= app(SiswaService::class);
        $this->prestasiSiswaService ??= app(PrestasiSiswaService::class);
    }

    /**
     * Menyusun ringkasan dashboard admin berbasis query agregat & eager-loading konstan (anti-N+1) tanpa modul Data Pelengkap.
     *
     * @return array{
     *     todayDate: Carbon,
     *     activeTahunAjaran: ?TahunAjaran,
     *     totalTahunAjaran: int,
     *     tahunAjaranList: EloquentCollection<int, TahunAjaran>,
     *     studentStats: array<string, mixed>,
     *     totalKaryawan: int,
     *     totalSekolahAktif: int,
     *     activeClasses: EloquentCollection<int, Kelas>,
     *     kelasSummary: array{
     *         total_kelas: int,
     *         total_siswa_rombel: int,
     *         per_unit: array<string, array{kelas: int, siswa: int}>
     *     },
     *     presensiTodaySummary: array{
     *         total: int,
     *         hadir: int,
     *         terlambat: int,
     *         izin: int,
     *         sakit: int,
     *         alpa: int,
     *         belum: int,
     *         hadir_rate: ?float
     *     },
     *     operationalSummary: array{
     *         mapel_aktif: int,
     *         tp_aktif: int,
     *         jadwal_aktif: int,
     *         ekskul_aktif: int,
     *         anggota_t2q: int,
     *         t2q_tahsin_hari_ini: int,
     *         t2q_tahfizh_hari_ini: int,
     *         finalisasi_final: int
     *     },
     *     prestasiSummary: array{
     *         table_ready: bool,
     *         total: int,
     *         akademik: int,
     *         non_akademik: int,
     *         beregu: int,
     *         dengan_foto: int,
     *         per_unit: array<string, int>
     *     },
     *     recentPrestasi: EloquentCollection<int, PrestasiSiswa>,
     *     upcomingAgendas: EloquentCollection<int, AgendaSekolah>,
     *     latestPengumuman: EloquentCollection<int, Pengumuman>
     * }
     */
    public function getDashboardData(): array
    {
        $today = Carbon::now('Asia/Jakarta');
        $todayStr = $today->toDateString();

        $tahunAjaranList = TahunAjaran::query()
            ->orderedForDropdown()
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        /** @var TahunAjaran|null $activeTahunAjaran */
        $activeTahunAjaran = $tahunAjaranList->firstWhere('is_active', true)
            ?? TahunAjaran::query()
                ->select(['id', 'nama_tahun_ajaran', 'semester', 'is_active'])
                ->active()
                ->orderByDesc('id')
                ->first();

        $activeTaId = $activeTahunAjaran !== null ? (int) $activeTahunAjaran->id : null;

        $totalTahunAjaran = TahunAjaran::query()->count();
        $studentStats = $this->siswaService->getStatistics();
        $totalKaryawan = Karyawan::query()->count();
        $totalSekolahAktif = Sekolah::query()->where('status', true)->count();

        $activeClasses = $this->loadActiveClasses($activeTaId);
        $kelasSummary = $this->summarizeClasses($activeClasses);

        $presensiTodaySummary = $this->buildTodayPresensiSummary(
            $activeTaId,
            $todayStr,
            $kelasSummary['total_siswa_rombel']
        );

        $operationalSummary = $this->buildOperationalSummary($activeTaId, $todayStr);
        [$prestasiSummary, $recentPrestasi] = $this->buildPrestasiSection();

        $upcomingAgendas = AgendaSekolah::query()
            ->select(['id', 'akademik_master_tahun_ajaran_id', 'nama_agenda', 'tanggal', 'jam', 'unit', 'jenis'])
            ->when($activeTaId !== null, static fn (Builder $query) => $query->where('akademik_master_tahun_ajaran_id', $activeTaId))
            ->whereDate('tanggal', '>=', $todayStr)
            ->orderedChronologically()
            ->limit(5)
            ->get();

        $latestPengumuman = Pengumuman::query()
            ->select(['id', 'akademik_master_tahun_ajaran_id', 'judul', 'isi', 'jenis', 'created_at'])
            ->when($activeTaId !== null, static fn (Builder $query) => $query->where('akademik_master_tahun_ajaran_id', $activeTaId))
            ->orderedLatest()
            ->limit(4)
            ->get();

        return [
            'todayDate' => $today,
            'activeTahunAjaran' => $activeTahunAjaran,
            'totalTahunAjaran' => $totalTahunAjaran,
            'tahunAjaranList' => $tahunAjaranList,
            'studentStats' => $studentStats,
            'totalKaryawan' => $totalKaryawan,
            'totalSekolahAktif' => $totalSekolahAktif,
            'activeClasses' => $activeClasses,
            'kelasSummary' => $kelasSummary,
            'presensiTodaySummary' => $presensiTodaySummary,
            'operationalSummary' => $operationalSummary,
            'prestasiSummary' => $prestasiSummary,
            'recentPrestasi' => $recentPrestasi,
            'upcomingAgendas' => $upcomingAgendas,
            'latestPengumuman' => $latestPengumuman,
        ];
    }

    /**
     * Memuat seluruh kelas pada Tahun Ajaran aktif dengan eager-load guru dan count siswa tanpa N+1.
     *
     * @return EloquentCollection<int, Kelas>
     */
    private function loadActiveClasses(?int $activeTaId): EloquentCollection
    {
        if ($activeTaId === null) {
            return new EloquentCollection;
        }

        $classes = Kelas::query()
            ->select([
                'id',
                'akademik_master_tahun_ajaran_id',
                'karyawan_nipy',
                'pendamping_nipy',
                'tingkatan_kelas',
                'nama_kelas',
                'jenjang_kelas',
                'romawi',
            ])
            ->with([
                'waliKelas:nipy,nama_lengkap,gelar',
                'pendamping:nipy,nama_lengkap,gelar',
            ])
            ->withCount('anggotaKelas')
            ->where('akademik_master_tahun_ajaran_id', $activeTaId)
            ->orderBy('jenjang_kelas')
            ->orderBy('tingkatan_kelas')
            ->orderBy('nama_kelas')
            ->get();

        $sorted = $classes->sort(static function (Kelas $a, Kelas $b): int {
            $jenjangCmp = Kelas::compareJenjangOrder((string) $a->jenjang_kelas, (string) $b->jenjang_kelas);
            if ($jenjangCmp !== 0) {
                return $jenjangCmp;
            }

            $tingkatCmp = strnatcasecmp((string) $a->tingkatan_kelas, (string) $b->tingkatan_kelas);
            if ($tingkatCmp !== 0) {
                return $tingkatCmp;
            }

            return strnatcasecmp((string) $a->nama_kelas, (string) $b->nama_kelas);
        })->values();

        return new EloquentCollection($sorted->all());
    }

    /**
     * Menghitung ringkasan jumlah kelas dan siswa terpetakan per unit dari koleksi di memori.
     *
     * @param  EloquentCollection<int, Kelas>  $classes
     * @return array{
     *     total_kelas: int,
     *     total_siswa_rombel: int,
     *     per_unit: array<string, array{kelas: int, siswa: int}>
     * }
     */
    private function summarizeClasses(EloquentCollection $classes): array
    {
        $perUnit = [];
        $totalSiswaRombel = 0;

        foreach ($classes as $kelas) {
            $unitCode = strtoupper(trim((string) ($kelas->jenjang_kelas ?? '')));
            if ($unitCode === '') {
                $unitCode = 'LAINNYA';
            }

            $siswaCount = (int) ($kelas->anggota_kelas_count ?? 0);
            $totalSiswaRombel += $siswaCount;

            if (! isset($perUnit[$unitCode])) {
                $perUnit[$unitCode] = ['kelas' => 0, 'siswa' => 0];
            }

            $perUnit[$unitCode]['kelas']++;
            $perUnit[$unitCode]['siswa'] += $siswaCount;
        }

        uksort($perUnit, static fn (string $a, string $b): int => Kelas::compareJenjangOrder($a, $b));

        return [
            'total_kelas' => $classes->count(),
            'total_siswa_rombel' => $totalSiswaRombel,
            'per_unit' => $perUnit,
        ];
    }

    /**
     * Menghitung ringkasan presensi hari ini dalam 1 query agregat SQL.
     *
     * @return array{
     *     total: int,
     *     hadir: int,
     *     terlambat: int,
     *     izin: int,
     *     sakit: int,
     *     alpa: int,
     *     belum: int,
     *     hadir_rate: ?float
     * }
     */
    private function buildTodayPresensiSummary(?int $activeTaId, string $todayStr, int $totalTargetSiswa): array
    {
        $summary = PresensiSiswa::query()
            ->join('akademik_master_anggota_kelas as ak', 'ak.id', '=', 'akademik_presensi_siswa.anggota_kelas_id')
            ->join('akademik_master_kelas as k', 'k.id', '=', 'ak.akademik_master_kelas_id')
            ->when($activeTaId !== null, static fn (Builder $query) => $query->where('k.akademik_master_tahun_ajaran_id', $activeTaId))
            ->whereDate('akademik_presensi_siswa.tanggal', $todayStr)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN akademik_presensi_siswa.status = 'hadir' THEN 1 ELSE 0 END) as hadir,
                SUM(CASE WHEN akademik_presensi_siswa.status = 'terlambat' THEN 1 ELSE 0 END) as terlambat,
                SUM(CASE WHEN akademik_presensi_siswa.status = 'izin' THEN 1 ELSE 0 END) as izin,
                SUM(CASE WHEN akademik_presensi_siswa.status = 'sakit' THEN 1 ELSE 0 END) as sakit,
                SUM(CASE WHEN akademik_presensi_siswa.status = 'alpa' THEN 1 ELSE 0 END) as alpa
            ")
            ->toBase()
            ->first();

        $total = (int) ($summary->total ?? 0);
        $hadir = (int) ($summary->hadir ?? 0);
        $terlambat = (int) ($summary->terlambat ?? 0);
        $izin = (int) ($summary->izin ?? 0);
        $sakit = (int) ($summary->sakit ?? 0);
        $alpa = (int) ($summary->alpa ?? 0);
        $belum = $totalTargetSiswa > 0 ? max(0, $totalTargetSiswa - $total) : 0;
        $hadirRate = $totalTargetSiswa > 0
            ? round((($hadir + $terlambat) / $totalTargetSiswa) * 100, 1)
            : null;

        return [
            'total' => $total,
            'hadir' => $hadir,
            'terlambat' => $terlambat,
            'izin' => $izin,
            'sakit' => $sakit,
            'alpa' => $alpa,
            'belum' => $belum,
            'hadir_rate' => $hadirRate,
        ];
    }

    /**
     * Menghitung ringkasan kurikulum, program, T2Q, dan finalisasi dalam query terarah.
     *
     * @return array{
     *     mapel_aktif: int,
     *     tp_aktif: int,
     *     jadwal_aktif: int,
     *     ekskul_aktif: int,
     *     anggota_t2q: int,
     *     t2q_tahsin_hari_ini: int,
     *     t2q_tahfizh_hari_ini: int,
     *     finalisasi_final: int
     * }
     */
    private function buildOperationalSummary(?int $activeTaId, string $todayStr): array
    {
        $mapelAktif = MataPelajaran::query()->where('status', true)->count();

        $tpAktif = TujuanPembelajaran::query()
            ->when($activeTaId !== null, static fn (Builder $query) => $query->where('akademik_master_tahun_ajaran_id', $activeTaId))
            ->where('status', true)
            ->count();

        $jadwalAktif = JadwalPelajaran::query()
            ->when($activeTaId !== null, static fn (Builder $query) => $query->where('akademik_master_tahun_ajaran_id', $activeTaId))
            ->where('status', true)
            ->count();

        $ekskulAktif = Ekstrakurikuler::query()
            ->when($activeTaId !== null, static fn (Builder $query) => $query->where('akademik_master_tahun_ajaran_id', $activeTaId))
            ->count();

        $anggotaT2q = AnggotaT2q::query()
            ->when($activeTaId !== null, static function (Builder $query) use ($activeTaId): void {
                $query->whereHas('anggotaKelas.kelas', static function (Builder $kelasQuery) use ($activeTaId): void {
                    $kelasQuery->where('akademik_master_tahun_ajaran_id', $activeTaId);
                });
            })
            ->count();

        $tahsinToday = YaumiyahTahsin::query()
            ->whereDate('tanggal', $todayStr)
            ->count();

        $tahfizhToday = YaumiyahTahfizh::query()
            ->whereDate('tanggal', $todayStr)
            ->count();

        $finalisasiFinal = PenilaianFinalisasi::query()
            ->when($activeTaId !== null, static fn (Builder $query) => $query->where('akademik_master_tahun_ajaran_id', $activeTaId))
            ->where('status', PenilaianFinalisasi::STATUS_FINAL)
            ->count();

        return [
            'mapel_aktif' => $mapelAktif,
            'tp_aktif' => $tpAktif,
            'jadwal_aktif' => $jadwalAktif,
            'ekskul_aktif' => $ekskulAktif,
            'anggota_t2q' => $anggotaT2q,
            't2q_tahsin_hari_ini' => $tahsinToday,
            't2q_tahfizh_hari_ini' => $tahfizhToday,
            'finalisasi_final' => $finalisasiFinal,
        ];
    }

    /**
     * Mengambil ringkasan dan prestasi terbaru secara aman (siap meskipun tabel prestasi belum di-migrate).
     *
     * @return array{
     *     0: array{
     *         table_ready: bool,
     *         total: int,
     *         akademik: int,
     *         non_akademik: int,
     *         beregu: int,
     *         dengan_foto: int,
     *         per_unit: array<string, int>
     *     },
     *     1: EloquentCollection<int, PrestasiSiswa>
     * }
     */
    private function buildPrestasiSection(): array
    {
        if (! Schema::hasTable('akademik_prestasi_siswa')) {
            return [
                [
                    'table_ready' => false,
                    'total' => 0,
                    'akademik' => 0,
                    'non_akademik' => 0,
                    'beregu' => 0,
                    'dengan_foto' => 0,
                    'per_unit' => array_fill_keys(MataPelajaran::UNIT_OPTIONS, 0),
                ],
                new EloquentCollection,
            ];
        }

        $stats = $this->prestasiSiswaService->getStatistics();
        $recent = PrestasiSiswa::query()
            ->select([
                'id',
                'akademik_master_tahun_ajaran_id',
                'nama_prestasi',
                'kategori',
                'tingkat',
                'peringkat',
                'penyelenggara',
                'tanggal',
                'keterangan',
                'foto',
            ])
            ->withMasterRelations()
            ->orderedLatest()
            ->limit(4)
            ->get();

        return [
            [
                'table_ready' => true,
                ...$stats,
            ],
            $recent,
        ];
    }
}
