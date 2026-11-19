<?php

namespace App\Http\Controllers\WaliKelas;

use App\Http\Controllers\Controller;
use App\Models\Admin\T2q\YaumiyahTahsin;
use App\Services\WaliKelas\WaliKelasT2qService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class YaumiyahT2qController extends Controller
{
    public function __construct(
        private readonly WaliKelasT2qService $service,
    ) {
        $this->middleware('role:super admin|wali kelas');
        $this->middleware('can:viewMatriks,'.YaumiyahTahsin::class)->only(['matriksTahsin', 'matriksTahfizh']);
        $this->middleware('can:viewStatistik,'.YaumiyahTahsin::class)->only(['statistikTahsin', 'statistikTahfizh']);
    }

    /**
     * Menampilkan Matriks Catatan Yaumiyah Tahsin (Senin – Kamis) bersifat read-only untuk kelas binaan.
     */
    public function matriksTahsin(Request $request): View
    {
        return $this->renderScopedView(
            'admin.t2q.yaumiyahTahsin.matriks',
            $this->service->getMatriksPageData($request->user(), 'tahsin', $this->extractMatriksFilters($request)),
            'Matriks Catatan Tahsin Wali Kelas',
            'Matriks Catatan Tahsin Kelas Binaan'
        );
    }

    /**
     * Menampilkan Matriks Catatan Yaumiyah Tahfizh (Senin – Kamis) bersifat read-only untuk kelas binaan.
     */
    public function matriksTahfizh(Request $request): View
    {
        return $this->renderScopedView(
            'admin.t2q.yaumiyahTahfizh.matriks',
            $this->service->getMatriksPageData($request->user(), 'tahfizh', $this->extractMatriksFilters($request)),
            'Matriks Catatan Tahfizh Wali Kelas',
            'Matriks Catatan Tahfizh Kelas Binaan'
        );
    }

    /**
     * Menampilkan Statistik Tahun Ajaran Yaumiyah Tahsin bersifat read-only untuk kelas binaan.
     */
    public function statistikTahsin(Request $request): View
    {
        return $this->renderScopedView(
            'admin.t2q.yaumiyahTahsin.statistik',
            $this->service->getStatistikPageData($request->user(), 'tahsin', $this->extractStatistikFilters($request)),
            'Statistik Yaumiyah Tahsin Wali Kelas',
            'Statistik Yaumiyah Tahsin Kelas Binaan'
        );
    }

    /**
     * Menampilkan Statistik Tahun Ajaran Yaumiyah Tahfizh bersifat read-only untuk kelas binaan.
     */
    public function statistikTahfizh(Request $request): View
    {
        return $this->renderScopedView(
            'admin.t2q.yaumiyahTahfizh.statistik',
            $this->service->getStatistikPageData($request->user(), 'tahfizh', $this->extractStatistikFilters($request)),
            'Statistik Yaumiyah Tahfizh Wali Kelas',
            'Statistik Yaumiyah Tahfizh Kelas Binaan'
        );
    }

    /**
     * Mengekstrak parameter filter untuk halaman matriks mingguan (DRY).
     *
     * @return array<string, mixed>
     */
    private function extractMatriksFilters(Request $request): array
    {
        return [
            'kelas_id' => $request->query('kelas_id'),
            'tanggal' => $request->query('tanggal'),
            'karyawan_nipy' => $request->query('karyawan_nipy'),
            'tingkat' => $request->query('tingkat'),
        ];
    }

    /**
     * Mengekstrak parameter filter untuk halaman statistik Tahun Ajaran (DRY).
     *
     * @return array<string, mixed>
     */
    private function extractStatistikFilters(Request $request): array
    {
        return [
            'tahun_ajaran_id' => $request->query('tahun_ajaran_id'),
            'kelas_id' => $request->query('kelas_id'),
            'karyawan_nipy' => $request->query('karyawan_nipy'),
            'tingkat' => $request->query('tingkat'),
        ];
    }

    /**
     * Merender view T2Q bersama saat eligible atau halaman informasi penugasan jika tidak eligible.
     *
     * @param  array<string, mixed>  $data
     */
    private function renderScopedView(string $view, array $data, string $pageTitle, string $heroTitle): View
    {
        if (! ($data['isEligible'] ?? false)) {
            return view('waliKelas.t2q.ineligible', [
                ...$data,
                'pageTitle' => $pageTitle,
                'heroTitle' => $heroTitle,
            ]);
        }

        return view($view, $data);
    }
}
