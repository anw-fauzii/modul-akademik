<?php

namespace App\Http\Controllers\WaliKelas;

use App\Http\Controllers\Controller;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Services\Admin\Penilaian\RaporPdfService;
use App\Services\WaliKelas\WaliKelasScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RaporController extends Controller
{
    public function __construct(
        private readonly RaporPdfService $raporPdfService,
        private readonly WaliKelasScopeService $scopeService
    ) {
        $this->middleware('role:super admin|wali kelas');
        $this->middleware('can:view,kelas')->only(['identitasKelas', 'nilaiKelas']);
        $this->middleware('can:view,anggota_kelas')->only(['identitasSiswa', 'nilaiSiswa']);
    }

    /**
     * Mencetak PDF Identitas Sekolah & Siswa untuk 1 kelas binaan Wali Kelas / Pendamping aktif.
     */
    public function identitasKelas(Request $request, Kelas $kelas): Response
    {
        $auth = $this->scopeService->authorizeKelasIdForUser($request->user(), (int) $kelas->id);

        return $this->raporPdfService->streamIdentitasPdf($auth['selectedKelas']);
    }

    /**
     * Mencetak PDF Identitas Sekolah & Siswa untuk 1 siswa di kelas binaan aktif.
     */
    public function identitasSiswa(Request $request, AnggotaKelas $anggotaKelas): Response
    {
        $auth = $this->scopeService->authorizeAnggotaKelasForUser($request->user(), $anggotaKelas);

        return $this->raporPdfService->streamIdentitasPdf($auth['selectedKelas'], $anggotaKelas);
    }

    /**
     * Mencetak PDF Nilai Hasil Pembelajaran SD untuk 1 kelas binaan Wali Kelas / Pendamping aktif.
     */
    public function nilaiKelas(Request $request, Kelas $kelas): Response|RedirectResponse
    {
        $auth = $this->scopeService->authorizeKelasIdForUser($request->user(), (int) $kelas->id);
        $periode = $request->string('periode', PeriodeTp::PERIODE_MID)->toString();

        try {
            return $this->raporPdfService->streamNilaiPdf($auth['selectedKelas'], $periode);
        } catch (HttpException $e) {
            if ($e->getStatusCode() === 422 && ! $request->expectsJson()) {
                return redirect()
                    ->route('wali-kelas.perkembangan-siswa.index', [
                        'kelas_id' => $auth['selectedKelas']->sqid,
                        'periode' => $periode,
                    ])
                    ->with('error', $e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Mencetak PDF Nilai Hasil Pembelajaran SD untuk 1 siswa di kelas binaan aktif.
     */
    public function nilaiSiswa(Request $request, AnggotaKelas $anggotaKelas): Response|RedirectResponse
    {
        $auth = $this->scopeService->authorizeAnggotaKelasForUser($request->user(), $anggotaKelas);
        $periode = $request->string('periode', PeriodeTp::PERIODE_MID)->toString();

        try {
            return $this->raporPdfService->streamNilaiPdf($auth['selectedKelas'], $periode, $anggotaKelas);
        } catch (HttpException $e) {
            if ($e->getStatusCode() === 422 && ! $request->expectsJson()) {
                return redirect()
                    ->route('wali-kelas.perkembangan-siswa.index', [
                        'kelas_id' => $auth['selectedKelas']->sqid,
                        'periode' => $periode,
                    ])
                    ->with('error', $e->getMessage());
            }

            throw $e;
        }
    }
}
