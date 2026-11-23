<?php

namespace App\Http\Controllers;

use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\DataPelengkap\BerkebutuhanKhusus;
use App\Models\Admin\DataPelengkap\JenjangPendidikan;
use App\Models\Admin\DataPelengkap\Pekerjaan;
use App\Models\Admin\DataPelengkap\Penghasilan;
use App\Models\Admin\DataPelengkap\Transportasi;
use App\Services\GuruMapel\GuruMapelDashboardService;
use App\Services\GuruT2q\GuruT2qDashboardService;
use App\Services\WaliKelas\WaliKelasDashboardService;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected ?WaliKelasDashboardService $waliKelasDashboardService = null,
        protected ?GuruMapelDashboardService $guruMapelDashboardService = null,
        protected ?GuruT2qDashboardService $guruT2qDashboardService = null
    ) {
        $this->middleware('auth');
        $this->middleware('role:super admin|wali kelas|guru mapel sd|guru t2q');
        $this->middleware('can:viewAny,'.Kelas::class)->only(['index']);
        $this->waliKelasDashboardService ??= app(WaliKelasDashboardService::class);
        $this->guruMapelDashboardService ??= app(GuruMapelDashboardService::class);
        $this->guruT2qDashboardService ??= app(GuruT2qDashboardService::class);
    }

    /**
     * Menampilkan dashboard utama sesuai role pengguna (Admin, Wali Kelas, Guru Mapel SD, atau Guru T2Q).
     */
    public function index(Request $request): Renderable
    {
        $user = $request->user();
        if ($user !== null && ! $user->hasRole('super admin')) {
            if ($user->hasRole('wali kelas')) {
                return view(
                    'waliKelas.dashboard',
                    $this->waliKelasDashboardService->getDashboardData(
                        $user,
                        $request->integer('kelas_id') ?: null
                    )
                );
            }

            if ($user->hasRole('guru mapel sd')) {
                return view(
                    'guruMapel.dashboard',
                    $this->guruMapelDashboardService->getDashboardData($user)
                );
            }

            if ($user->hasRole('guru t2q')) {
                return view(
                    'guruT2q.dashboard',
                    $this->guruT2qDashboardService->getDashboardData($user)
                );
            }
        }
        // 1. DATA MASTER
        $activeTahunAjaran = TahunAjaran::where('is_active', true)->first();
        $totalTahunAjaran = TahunAjaran::count();
        $totalKaryawan = Karyawan::count();
        $listTahunAjaran = TahunAjaran::orderByDesc('nama_tahun_ajaran')->orderBy('semester')->take(5)->get();

        // 2. STATISTIK DATA PELENGKAP
        $totalBerkebutuhanKhusus = BerkebutuhanKhusus::count();
        $totalJenjangPendidikan = JenjangPendidikan::count();
        $totalPekerjaan = Pekerjaan::count();
        $totalPenghasilan = Penghasilan::count();
        $totalTransportasi = Transportasi::count();

        // 3. SAMPLE / PREVIEW DATA TERBARU
        $latestBerkebutuhanKhusus = BerkebutuhanKhusus::latest()->take(5)->get();
        $latestJenjangPendidikan = JenjangPendidikan::latest()->take(5)->get();
        $latestPekerjaan = Pekerjaan::latest()->take(5)->get();
        $latestPenghasilan = Penghasilan::latest()->take(5)->get();
        $latestTransportasi = Transportasi::latest()->take(5)->get();

        $totalDataPelengkap = $totalBerkebutuhanKhusus + $totalJenjangPendidikan + $totalPekerjaan + $totalPenghasilan + $totalTransportasi;

        return view('home', compact(
            'activeTahunAjaran',
            'totalTahunAjaran',
            'totalKaryawan',
            'listTahunAjaran',
            'totalBerkebutuhanKhusus',
            'totalJenjangPendidikan',
            'totalPekerjaan',
            'totalPenghasilan',
            'totalTransportasi',
            'totalDataPelengkap',
            'latestBerkebutuhanKhusus',
            'latestJenjangPendidikan',
            'latestPekerjaan',
            'latestPenghasilan',
            'latestTransportasi'
        ));
    }
}
