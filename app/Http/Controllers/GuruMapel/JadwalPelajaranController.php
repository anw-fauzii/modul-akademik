<?php

namespace App\Http\Controllers\GuruMapel;

use App\Http\Controllers\Controller;
use App\Models\Admin\Pelajaran\JadwalPelajaran;
use App\Services\GuruMapel\GuruMapelJadwalPelajaranService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JadwalPelajaranController extends Controller
{
    public function __construct(
        private readonly GuruMapelJadwalPelajaranService $service
    ) {
        $this->middleware('role:super admin|guru mapel sd');
        $this->middleware('can:viewAny,'.JadwalPelajaran::class)->only(['index']);
    }

    /**
     * Menampilkan jadwal mengajar pribadi Guru Mapel SD pada Tahun Ajaran aktif.
     */
    public function index(Request $request): View
    {
        $data = $this->service->getIndexData($request->user(), $request->only([
            'kelas_id',
            'hari',
            'search',
        ]));

        return view('guruMapel.jadwalPelajaran.index', $data);
    }
}
