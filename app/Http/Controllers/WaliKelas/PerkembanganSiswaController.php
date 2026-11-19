<?php

namespace App\Http\Controllers\WaliKelas;

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
        $this->middleware('role:super admin|wali kelas');
        $this->middleware('can:viewAny,'.AnggotaKelas::class)->only(['index']);
        $this->middleware('can:view,anggota_kelas')->only(['show']);
    }

    /**
     * Menampilkan rekapitulasi perkembangan seluruh siswa di kelas binaan pada seluruh mapel & presensi.
     */
    public function index(Request $request): View
    {
        $requestedKelasId = $request->integer('kelas_id') ?: null;
        $periode = $request->string('periode', PeriodeTp::PERIODE_MID)->toString();

        return view(
            'waliKelas.perkembanganSiswa.index',
            $this->service->getIndexData($request->user(), $requestedKelasId, $periode)
        );
    }

    /**
     * Menampilkan rincian perkembangan satu siswa across seluruh mata pelajaran, TP, deskripsi, dan presensi.
     */
    public function show(AnggotaKelas $anggotaKelas, Request $request): View
    {
        $periode = $request->string('periode', PeriodeTp::PERIODE_MID)->toString();

        return view(
            'waliKelas.perkembanganSiswa.show',
            $this->service->getStudentDetailData($request->user(), $anggotaKelas, $periode)
        );
    }
}
