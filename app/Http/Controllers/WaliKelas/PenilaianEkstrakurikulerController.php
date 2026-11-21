<?php

namespace App\Http\Controllers\WaliKelas;

use App\Http\Controllers\Controller;
use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Models\Admin\DataMaster\Kelas;
use App\Services\WaliKelas\WaliKelasEkstrakurikulerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PenilaianEkstrakurikulerController extends Controller
{
    public function __construct(
        protected WaliKelasEkstrakurikulerService $service
    ) {
        $this->middleware('role:super admin|wali kelas');
        $this->middleware('can:viewPenilaian,'.Ekstrakurikuler::class)->only(['index']);
        $this->middleware('can:inputNilai,ekstrakurikuler')->only(['store']);
        $this->middleware('can:view,kelas')->only(['storeWajib']);
    }

    /**
     * Menampilkan halaman Penilaian Ekstrakurikuler:
     * - Tab Pramuka (Wajib) untuk Wali Kelas / Guru Pendamping sesuai kelas binaannya.
     * - Tab Ekskul Pilihan (Minat) untuk Pembimbing Ekstrakurikuler.
     */
    public function index(Request $request): View
    {
        $ekskulId = $request->integer('ekstrakurikuler_id') ?: null;
        $kelasId = $request->integer('kelas_id') ?: null;
        $mode = $request->string('mode')->toString() ?: null;
        $periode = $request->string('periode')->toString() ?: null;

        $data = ($kelasId !== null || $mode !== null || $periode !== null)
            ? $this->service->getPageData($request->user(), $ekskulId, $kelasId, $mode, $periode)
            : $this->service->getPageData($request->user(), $ekskulId);

        return view('waliKelas.ekstrakurikuler.index', $data);
    }

    /**
     * Menyimpan nilai angka seluruh anggota Ekstrakurikuler Pilihan yang dibimbing.
     */
    public function store(Request $request, Ekstrakurikuler $ekstrakurikuler): RedirectResponse
    {
        $this->service->authorizeEkstrakurikulerForUser($request->user(), $ekstrakurikuler);

        $validated = $request->validate([
            'periode' => ['nullable', 'string', 'in:mid,semester'],
            'kkm' => ['nullable', 'integer', 'min:1', 'max:100'],
            'materi' => ['nullable', 'string', 'max:500'],
            'nilai' => ['nullable', 'array'],
            'nilai.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'keterangan' => ['nullable', 'array'],
            'keterangan.*' => ['nullable', 'string', 'max:255'],
            'regenerate_deskripsi' => ['nullable', 'boolean'],
        ], [
            'kkm.integer' => 'KKM harus berupa angka bulat.',
            'kkm.min' => 'KKM minimal 1.',
            'kkm.max' => 'KKM maksimal 100.',
            'nilai.*.numeric' => 'Nilai siswa harus berupa angka.',
            'nilai.*.min' => 'Nilai siswa minimal 0.',
            'nilai.*.max' => 'Nilai siswa maksimal 100.',
        ]);

        $regenerate = $request->boolean('regenerate_deskripsi');
        $periode = $validated['periode'] ?? 'mid';
        $this->service->savePenilaian($ekstrakurikuler, $validated, $regenerate);

        $redirectToAdmin = $request->boolean('from_admin_show') && $request->user()?->hasRole('super admin');
        if ($redirectToAdmin) {
            return redirect()
                ->route('ekstrakurikuler.show', $ekstrakurikuler)
                ->with('success', 'Nilai, Predikat (SAB/BSH/SB/MB), dan Deskripsi Rapor Ekstrakurikuler berhasil disimpan.');
        }

        return redirect()
            ->route('wali-kelas.ekstrakurikuler.index', [
                'mode' => 'pilihan',
                'ekstrakurikuler_id' => $ekstrakurikuler->sqid,
                'periode' => $periode,
            ])
            ->with('success', 'Nilai, Predikat (SAB/BSH/SB/MB), dan Deskripsi Rapor Ekstrakurikuler berhasil disimpan.');
    }

    /**
     * Menyimpan nilai Pramuka (Ekstrakurikuler Wajib) untuk seluruh siswa kelas binaan Wali Kelas.
     */
    public function storeWajib(Request $request, Kelas $kelas): RedirectResponse
    {
        $this->service->authorizeWajibKelasForUser($request->user(), $kelas);

        $validated = $request->validate([
            'periode' => ['nullable', 'string', 'in:mid,semester'],
            'nilai' => ['nullable', 'array'],
            'nilai.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'nilai.*.numeric' => 'Nilai Pramuka siswa harus berupa angka.',
            'nilai.*.min' => 'Nilai Pramuka siswa minimal 0.',
            'nilai.*.max' => 'Nilai Pramuka siswa maksimal 100.',
        ]);

        $periode = $validated['periode'] ?? 'mid';
        $this->service->savePenilaianWajibKelas($kelas, $validated);

        return redirect()
            ->route('wali-kelas.ekstrakurikuler.index', [
                'mode' => 'wajib',
                'kelas_id' => $kelas->sqid,
                'periode' => $periode,
            ])
            ->with('success', 'Nilai Pramuka (Wajib), Predikat (SAB/BSH/SB/MB), dan Deskripsi Rapor berhasil disimpan.');
    }
}
