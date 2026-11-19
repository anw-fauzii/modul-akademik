<?php

namespace App\Http\Controllers\Admin\Penilaian;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Penilaian\KkmRequest;
use App\Models\Admin\Penilaian\Kkm;
use App\Services\Admin\Penilaian\KkmService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KkmController extends Controller
{
    public function __construct(
        protected KkmService $service
    ) {
        $this->middleware('role:super admin');
        $this->authorizeResource(Kkm::class, 'kkm');
    }

    /**
     * Menampilkan daftar pengaturan KKM dan rentang predikat jenjang SD.
     */
    public function index(Request $request): View
    {
        $data = $this->service->getIndexData($request->only([
            'tahun_ajaran_id',
            'mata_pelajaran_id',
            'tingkatan_kelas',
        ]));

        return view('admin.penilaian.kkm.index', $data);
    }

    /**
     * Menampilkan form tambah KKM dan rentang predikat SD.
     */
    public function create(Request $request): View
    {
        $options = $this->service->getFormOptions($request->only([
            'tahun_ajaran_id',
            'mata_pelajaran_id',
            'tingkatan_kelas',
        ]));

        return view('admin.penilaian.kkm.create', $options);
    }

    /**
     * Menyimpan KKM dan rentang predikat beserta penyegaran rekap nilai terkait.
     */
    public function store(KkmRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $this->service->saveWithPredikats($validated);

        $mapelIds = $validated['mata_pelajaran_ids'] ?? array_filter([$validated['akademik_pelajaran_mata_pelajaran_id'] ?? null]);
        $mapelCount = count(is_array($mapelIds) ? $mapelIds : []);
        $primaryTingkat = $validated['tingkatan_kelas'] ?? null;
        $extraTingkat = array_diff(
            $validated['copy_to_tingkatan'] ?? [],
            $primaryTingkat !== null ? [$primaryTingkat] : []
        );
        $tingkatCount = 1 + count(array_unique($extraTingkat));

        $message = $mapelCount > 1 || $tingkatCount > 1
            ? "Pengaturan KKM & rentang predikat berhasil disimpan untuk {$mapelCount} mata pelajaran ({$tingkatCount} tingkatan kelas)."
            : 'Pengaturan KKM & rentang predikat berhasil disimpan.';

        return redirect()->route('penilaian-kkm.index')
            ->with('success', $message);
    }

    /**
     * Menampilkan form ubah KKM & rentang predikat.
     */
    public function edit(Kkm $kkm): View
    {
        $kkm->loadMissing(['tahunAjaran:id,nama_tahun_ajaran,semester', 'mataPelajaran:id,singkatan,nama_mata_pelajaran,unit', 'predikats']);
        $options = $this->service->getFormOptions();

        return view('admin.penilaian.kkm.edit', array_merge($options, [
            'kkm' => $kkm,
        ]));
    }

    /**
     * Memperbarui KKM dan rentang predikat serta menyegarkan predikat rekap TP & rapor.
     */
    public function update(KkmRequest $request, Kkm $kkm): RedirectResponse
    {
        $this->service->saveWithPredikats($request->validated(), $kkm);

        return redirect()->route('penilaian-kkm.index')
            ->with('success', 'Pengaturan KKM & rentang predikat berhasil diperbarui.');
    }

    /**
     * Menghapus pengaturan KKM beserta daftar rentang predikatnya.
     */
    public function destroy(Kkm $kkm): RedirectResponse
    {
        $this->service->delete($kkm);

        return redirect()->route('penilaian-kkm.index')
            ->with('success', 'Pengaturan KKM berhasil dihapus.');
    }
}
