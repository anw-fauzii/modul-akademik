<?php

namespace App\Http\Controllers\GuruMapel;

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
        $this->middleware('role:super admin|guru mapel sd');
        $this->middleware('can:viewPenilaian,'.Ekstrakurikuler::class)->only(['index']);
        $this->middleware('can:inputNilai,ekstrakurikuler')->only(['store']);
    }

    /**
     * Menampilkan halaman Penilaian Ekstrakurikuler Pilihan yang dibina oleh Guru Mapel SD.
     */
    public function index(Request $request): View
    {
        $ekskulId = $request->integer('ekstrakurikuler_id') ?: null;
        $kelasId = $request->integer('kelas_id') ?: null;
        $mode = $request->string('mode')->toString() ?: null;
        $periode = $request->string('periode')->toString() ?: null;

        return view(
            'waliKelas.ekstrakurikuler.index',
            $this->service->getPageData($request->user(), $ekskulId, $kelasId, $mode, $periode, true, 'guru-mapel')
        );
    }

    /**
     * Menyimpan nilai angka seluruh anggota Ekstrakurikuler Pilihan yang dibina Guru Mapel SD.
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

        return redirect()
            ->route('guru-mapel.ekstrakurikuler.index', [
                'mode' => 'pilihan',
                'ekstrakurikuler_id' => $ekstrakurikuler->sqid,
                'periode' => $periode,
            ])
            ->with('success', 'Nilai, Predikat (SAB/BSH/SB/MB), dan Deskripsi Rapor Ekstrakurikuler berhasil disimpan.');
    }
}
