<?php

namespace App\Http\Controllers\WaliKelas;

use App\Http\Controllers\Controller;
use App\Services\WaliKelas\WaliKelasJadwalPelajaranService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JadwalPelajaranController extends Controller
{
    public function __construct(
        private readonly WaliKelasJadwalPelajaranService $service
    ) {
        $this->middleware('role:super admin|wali kelas');
    }

    /**
     * Menampilkan jadwal pelajaran kelas binaan secara read-only untuk Wali Kelas / Guru Pendamping aktif.
     */
    public function index(Request $request): View
    {
        $data = $this->service->getIndexData($request->user(), $request->only([
            'tahun_ajaran_id',
            'kelas_id',
            'hari',
            'status',
            'search',
        ]));

        if (! ($data['isEligible'] ?? false)) {
            return view('waliKelas.jadwalPelajaran.ineligible', $data);
        }

        return view('waliKelas.jadwalPelajaran.index', $data);
    }
}
