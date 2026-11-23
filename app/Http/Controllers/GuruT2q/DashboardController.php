<?php

namespace App\Http\Controllers\GuruT2q;

use App\Http\Controllers\Controller;
use App\Services\GuruT2q\GuruT2qDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly GuruT2qDashboardService $dashboardService
    ) {
        $this->middleware('role:super admin|guru t2q');
    }

    /**
     * Menampilkan dashboard khusus Guru T2Q (ringkasan Yaumiyah Tahsin/Tahfizh & finalisasi).
     */
    public function index(Request $request): View
    {
        return view(
            'guruT2q.dashboard',
            $this->dashboardService->getDashboardData($request->user())
        );
    }
}
