<?php

namespace App\Http\Controllers\Admin\DataMaster;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DataMaster\BatalkanMutasiRequest;
use App\Http\Requests\Admin\DataMaster\KelulusanBatchRequest;
use App\Http\Requests\Admin\DataMaster\MutasiKeluarRequest;
use App\Http\Requests\Admin\DataMaster\MutasiMasukRequest;
use App\Models\Admin\DataMaster\SiswaMutasi;
use App\Services\Admin\DataMaster\SiswaMutasiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiswaMutasiController extends Controller
{
    public function __construct(
        protected SiswaMutasiService $service
    ) {
        $this->middleware('role:super admin');
    }

    /**
     * Menampilkan ledger Mutasi & Kelulusan Siswa dengan statistik dan pola Filter-First.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', SiswaMutasi::class);

        return view(
            'admin.dataMaster.siswaMutasi.index',
            $this->service->getIndexData($request->only([
                'search',
                'unit',
                'tahun_ajaran_id',
                'kategori',
                'status_log',
                'show_all',
            ]))
        );
    }

    /**
     * Menampilkan form proses Kelulusan Massal per kelas.
     */
    public function kelulusanForm(Request $request): View
    {
        $this->authorize('create', SiswaMutasi::class);

        return view(
            'admin.dataMaster.siswaMutasi.kelulusan',
            $this->service->getKelulusanFormData($request->only([
                'tahun_ajaran_id',
                'kelas_id',
            ]))
        );
    }

    /**
     * Memproses Kelulusan Massal siswa terpilih menjadi Alumni (Lulus).
     */
    public function kelulusanStore(KelulusanBatchRequest $request): RedirectResponse
    {
        $this->authorize('create', SiswaMutasi::class);

        $count = $this->service->prosesKelulusanMassal($request->validated(), $request->user());

        return redirect()
            ->route('siswa-mutasi.index', ['kategori' => SiswaMutasi::KATEGORI_LULUS])
            ->with('success', "{$count} siswa berhasil diluluskan dan dicatat ke dalam ledger mutasi.");
    }

    /**
     * Menampilkan form Mutasi Keluar (Pindah Sekolah, Keluar, atau Nonaktif).
     */
    public function mutasiKeluarForm(): View
    {
        $this->authorize('create', SiswaMutasi::class);

        return view(
            'admin.dataMaster.siswaMutasi.mutasi-keluar',
            $this->service->getMutasiKeluarFormOptions()
        );
    }

    /**
     * Memproses pencatatan Mutasi Keluar secara tunggal maupun massal.
     */
    public function mutasiKeluarStore(MutasiKeluarRequest $request): RedirectResponse
    {
        $this->authorize('create', SiswaMutasi::class);

        $validated = $request->validated();
        $count = $this->service->prosesMutasiKeluar($validated, $request->user());

        return redirect()
            ->route('siswa-mutasi.index', ['kategori' => $validated['kategori']])
            ->with('success', "Mutasi keluar berhasil diproses untuk {$count} siswa.");
    }

    /**
     * Menampilkan form pencatatan Siswa Pindahan Masuk (Mutasi Masuk).
     */
    public function mutasiMasukForm(): View
    {
        $this->authorize('create', SiswaMutasi::class);

        return view(
            'admin.dataMaster.siswaMutasi.mutasi-masuk',
            $this->service->getMutasiMasukFormOptions()
        );
    }

    /**
     * Menyimpan biodata siswa baru pindahan masuk sekaligus penempatan kelas dan log mutasi.
     */
    public function mutasiMasukStore(MutasiMasukRequest $request): RedirectResponse
    {
        $this->authorize('create', SiswaMutasi::class);

        $mutasi = $this->service->prosesMutasiMasuk(
            $request->validated(),
            $request->file('avatar'),
            $request->user()
        );

        return redirect()
            ->route('siswa-mutasi.index', ['kategori' => SiswaMutasi::KATEGORI_MASUK])
            ->with('success', "Mutasi masuk untuk NIS {$mutasi->siswa_nis} berhasil dicatat.");
    }

    /**
     * Membatalkan transaksi mutasi/kelulusan secara aman dan memulihkan status siswa.
     */
    public function batalkan(BatalkanMutasiRequest $request, SiswaMutasi $siswaMutasi): RedirectResponse
    {
        $this->authorize('update', $siswaMutasi);

        $this->service->batalkanMutasi(
            $siswaMutasi,
            $request->validated('alasan_batal'),
            $request->user()
        );

        return back()->with('success', 'Catatan mutasi berhasil dibatalkan dan status siswa telah dipulihkan.');
    }
}

