<?php

namespace App\Http\Controllers\WaliKelas;

use App\Http\Controllers\Controller;
use App\Services\WaliKelas\WaliKelasDashboardService;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected WaliKelasDashboardService $service
    ) {
        $this->middleware('role:super admin|wali kelas');
    }

    /**
     * Menampilkan dashboard khusus Wali Kelas / Guru Pendamping sesuai penugasan pada tahun ajaran aktif.
     */
    public function index(Request $request): Renderable
    {
        return view(
            'waliKelas.dashboard',
            $this->service->getDashboardData(
                $request->user(),
                $request->integer('kelas_id') ?: null
            )
        );
    }
}
