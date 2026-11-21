<?php

namespace App\Http\Controllers\GuruMapel;

use App\Http\Controllers\Controller;
use App\Models\Admin\DataMaster\Kelas;
use App\Services\GuruMapel\GuruMapelDashboardService;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected GuruMapelDashboardService $service
    ) {
        $this->middleware('role:super admin|guru mapel sd');
        $this->middleware('can:viewAny,'.Kelas::class)->only(['index']);
    }

    /**
     * Menampilkan dashboard khusus Guru Mapel SD sesuai jadwal mengajar & ekskul pilihan aktif.
     */
    public function index(Request $request): Renderable
    {
        return view(
            'guruMapel.dashboard',
            $this->service->getDashboardData($request->user())
        );
    }
}
