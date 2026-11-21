<?php

namespace App\Http\Controllers\GuruMapel;

use App\Http\Controllers\Controller;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Services\WaliKelas\WaliKelasPerkembanganService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PerkembanganSiswaController extends Controller
{
    public function __construct(
        private readonly WaliKelasPerkembanganService $service
    ) {
        $this->middleware('role:super admin|guru mapel sd');
        $this->middleware('can:viewAny,'.AnggotaKelas::class)->only(['index']);
        $this->middleware('can:view,anggota_kelas')->only(['show']);
    }

    /**
     * Menampilkan leger perkembangan siswa hanya untuk mata pelajaran yang diampu Guru Mapel SD.
     */
    public function index(Request $request): View
    {
        $requestedKelasId = $request->integer('kelas_id') ?: null;
        $periode = $request->string('periode', PeriodeTp::PERIODE_MID)->toString();

        return view(
            'waliKelas.perkembanganSiswa.index',
            $this->service->getIndexData($request->user(), $requestedKelasId, $periode, true)
        );
    }

    /**
     * Menampilkan rincian perkembangan satu siswa hanya untuk mata pelajaran yang diampu Guru Mapel SD.
     */
    public function show(AnggotaKelas $anggotaKelas, Request $request): View
    {
        $periode = $request->string('periode', PeriodeTp::PERIODE_MID)->toString();

        return view(
            'waliKelas.perkembanganSiswa.show',
            $this->service->getStudentDetailData($request->user(), $anggotaKelas, $periode, true)
        );
    }
}
