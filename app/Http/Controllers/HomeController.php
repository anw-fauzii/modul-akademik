<?php

namespace App\Http\Controllers;

use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\DataPelengkap\BerkebutuhanKhusus;
use App\Models\Admin\DataPelengkap\JenjangPendidikan;
use App\Models\Admin\DataPelengkap\Pekerjaan;
use App\Models\Admin\DataPelengkap\Penghasilan;
use App\Models\Admin\DataPelengkap\Transportasi;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Menampilkan dashboard utama akademik dengan ringkasan data master dan data pelengkap.
     */
    public function index(): Renderable
    {
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
