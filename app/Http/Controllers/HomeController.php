<?php

namespace App\Http\Controllers;

use App\Models\Admin\DataMaster\Kelas;
use App\Services\Admin\Dashboard\AdminDashboardService;
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
        protected ?GuruT2qDashboardService $guruT2qDashboardService = null,
        protected ?AdminDashboardService $adminDashboardService = null
    ) {
        $this->middleware('auth');
        $this->middleware('role:super admin|wali kelas|guru mapel sd|guru t2q');
        $this->middleware('can:viewAny,'.Kelas::class)->only(['index']);
        $this->waliKelasDashboardService ??= app(WaliKelasDashboardService::class);
        $this->guruMapelDashboardService ??= app(GuruMapelDashboardService::class);
        $this->guruT2qDashboardService ??= app(GuruT2qDashboardService::class);
        $this->adminDashboardService ??= app(AdminDashboardService::class);
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

        return view('home', $this->adminDashboardService->getDashboardData());
    }
}
