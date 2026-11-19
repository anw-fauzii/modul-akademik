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
use App\Services\WaliKelas\WaliKelasDashboardService;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected ?WaliKelasDashboardService $waliKelasDashboardService = null
    ) {
        $this->middleware('auth');
        $this->middleware('role:super admin|wali kelas');
        $this->middleware('can:viewAny,'.Kelas::class)->only(['index']);
        $this->waliKelasDashboardService ??= app(WaliKelasDashboardService::class);
    }

    /**
     * Menampilkan dashboard utama sesuai role pengguna (Admin atau Wali Kelas pada tahun ajaran aktif).
     */
    public function index(Request $request): Renderable
    {
        $user = $request->user();
        if ($user !== null && ! $user->hasRole('super admin') && $user->hasRole('wali kelas')) {
            return view(
                'waliKelas.dashboard',
                $this->waliKelasDashboardService->getDashboardData(
                    $user,
                    $request->integer('kelas_id') ?: null
                )
            );
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
