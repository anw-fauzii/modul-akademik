<?php

namespace App\Http\Controllers\WaliKelas;

use App\Http\Controllers\Controller;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Services\WaliKelas\WaliKelasAnggotaKelasService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnggotaKelasController extends Controller
{
    public function __construct(
        protected WaliKelasAnggotaKelasService $service
    ) {
        $this->middleware('role:super admin|wali kelas');
    }

    /**
     * Menampilkan daftar anggota kelas yang dibina Wali Kelas / Pendamping pada tahun ajaran aktif.
     */
    public function index(Request $request): View
    {
        $requestedKelasId = $request->integer('kelas_id') ?: null;

        return view(
            'waliKelas.anggotaKelas.index',
            $this->service->getIndexData($request->user(), $requestedKelasId)
        );
    }

    /**
     * Menampilkan detail biodata siswa anggota kelas binaan dengan proteksi kepemilikan kelas aktif.
     */
    public function show(AnggotaKelas $anggotaKelas, Request $request): View
    {
        $data = $this->service->getStudentDetail($request->user(), $anggotaKelas);

        return view('admin.dataMaster.siswa.show', [
            ...$data,
            'showAdminLinks' => false,
            'backUrl' => route('wali-kelas.anggota-kelas.index', ['kelas_id' => $data['selectedKelas']->id]),
            'backLabel' => 'Kembali ke Anggota Kelas',
        ]);
    }
}
