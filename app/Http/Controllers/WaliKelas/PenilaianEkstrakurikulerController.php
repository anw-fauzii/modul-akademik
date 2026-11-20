<?php

namespace App\Http\Controllers\WaliKelas;

use App\Http\Controllers\Controller;
use App\Models\Admin\DataMaster\Ekstrakurikuler;
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
    }

    /**
     * Menampilkan halaman Penilaian Ekstrakurikuler untuk Pembimbing (atau alert jika tidak membimbing ekskul).
     */
    public function index(Request $request): View
    {
        $ekskulId = $request->integer('ekstrakurikuler_id') ?: null;
        $data = $this->service->getPageData($request->user(), $ekskulId);

        return view('waliKelas.ekstrakurikuler.index', $data);
    }

    /**
     * Menyimpan pengaturan KKM & Materi beserta nilai angka seluruh anggota ekstrakurikuler yang dibimbing.
     */
    public function store(Request $request, Ekstrakurikuler $ekstrakurikuler): RedirectResponse
    {
        $this->service->authorizeEkstrakurikulerForUser($request->user(), $ekstrakurikuler);

        $validated = $request->validate([
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
        $this->service->savePenilaian($ekstrakurikuler, $validated, $regenerate);

        $redirectToAdmin = $request->boolean('from_admin_show') && $request->user()?->hasRole('super admin');
        if ($redirectToAdmin) {
            return redirect()
                ->route('ekstrakurikuler.show', $ekstrakurikuler)
                ->with('success', 'Nilai, Predikat (SAB/BSH/SB/MB), dan Deskripsi Rapor Ekstrakurikuler berhasil disimpan.');
        }

        return redirect()
            ->route('wali-kelas.ekstrakurikuler.index', ['ekstrakurikuler_id' => $ekstrakurikuler->sqid])
            ->with('success', 'Nilai, Predikat (SAB/BSH/SB/MB), dan Deskripsi Rapor Ekstrakurikuler berhasil disimpan.');
    }
}
