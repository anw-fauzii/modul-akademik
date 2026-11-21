<?php

namespace App\Http\Controllers\Admin\Penilaian;

use App\Http\Controllers\Controller;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Services\Admin\Penilaian\RaporPdfService;
use App\Support\Sqid;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RaporController extends Controller
{
    public function __construct(
        protected RaporPdfService $service
    ) {
        $this->middleware('role:super admin');
        $this->middleware('can:viewAny,'.Kelas::class)->only(['index']);
        $this->middleware('can:view,kelas')->only(['identitasKelas', 'nilaiKelas']);
        $this->middleware('can:view,anggota_kelas')->only(['identitasSiswa', 'nilaiSiswa']);
    }

    /**
     * Menampilkan halaman pusat cetak PDF Identitas & Nilai Rapor untuk Super Admin.
     */
    public function index(Request $request): View
    {
        return view(
            'admin.penilaian.rapor.index',
            $this->service->getAdminIndexData($request->only([
                'tahun_ajaran_id',
                'kelas_id',
                'periode',
            ]))
        );
    }

    /**
     * Mencetak PDF Identitas Sekolah & Keterangan Diri seluruh siswa dalam 1 kelas.
     */
    public function identitasKelas(Kelas $kelas): Response
    {
        return $this->service->streamIdentitasPdf($kelas);
    }

    /**
     * Mencetak PDF Identitas Sekolah & Keterangan Diri 1 siswa.
     */
    public function identitasSiswa(AnggotaKelas $anggotaKelas): Response
    {
        $anggotaKelas->loadMissing('kelas');
        $kelas = $anggotaKelas->kelas;
        abort_unless($kelas instanceof Kelas, 404, 'Kelas siswa tidak ditemukan.');

        return $this->service->streamIdentitasPdf($kelas, $anggotaKelas);
    }

    /**
     * Mencetak PDF Nilai Hasil Pembelajaran seluruh siswa dalam 1 kelas SD.
     */
    public function nilaiKelas(Request $request, Kelas $kelas): Response|RedirectResponse
    {
        $periode = $request->string('periode', PeriodeTp::PERIODE_MID)->toString();

        try {
            return $this->service->streamNilaiPdf($kelas, $periode);
        } catch (HttpException $e) {
            if ($e->getStatusCode() === 422 && ! $request->expectsJson()) {
                return redirect()
                    ->route('penilaian-rapor.index', Sqid::encodeQueryParams(array_filter([
                        'tahun_ajaran_id' => $kelas->akademik_master_tahun_ajaran_id,
                        'kelas_id' => $kelas->id,
                        'periode' => $periode,
                    ], static fn ($v): bool => $v !== null && $v !== '')))
                    ->with('error', $e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Mencetak PDF Nilai Hasil Pembelajaran untuk 1 siswa SD.
     */
    public function nilaiSiswa(Request $request, AnggotaKelas $anggotaKelas): Response|RedirectResponse
    {
        $anggotaKelas->loadMissing('kelas');
        $kelas = $anggotaKelas->kelas;
        abort_unless($kelas instanceof Kelas, 404, 'Kelas siswa tidak ditemukan.');

        $periode = $request->string('periode', PeriodeTp::PERIODE_MID)->toString();

        try {
            return $this->service->streamNilaiPdf($kelas, $periode, $anggotaKelas);
        } catch (HttpException $e) {
            if ($e->getStatusCode() === 422 && ! $request->expectsJson()) {
                return redirect()
                    ->route('penilaian-rapor.index', Sqid::encodeQueryParams(array_filter([
                        'tahun_ajaran_id' => $kelas->akademik_master_tahun_ajaran_id,
                        'kelas_id' => $kelas->id,
                        'periode' => $periode,
                    ], static fn ($v): bool => $v !== null && $v !== '')))
                    ->with('error', $e->getMessage());
            }

            throw $e;
        }
    }
}
