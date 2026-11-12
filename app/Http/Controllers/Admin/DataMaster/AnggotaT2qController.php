<?php

namespace App\Http\Controllers\Admin\DataMaster;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DataMaster\AnggotaT2qRequest;
use App\Models\Admin\DataMaster\AnggotaT2q;
use App\Services\Admin\DataMaster\AnggotaT2qService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnggotaT2qController extends Controller
{
    public function __construct(
        protected AnggotaT2qService $service
    ) {}

    /**
     * Menampilkan daftar anggota T2Q dengan filter tahun ajaran, guru T2Q, tingkat, dan kelas opsional.
     */
    public function index(Request $request): View
    {
        $tahunAjaranId = $request->integer('tahun_ajaran_id') ?: null;
        $karyawanNipy = trim($request->string('karyawan_nipy')->toString()) ?: null;
        $tingkat = AnggotaT2q::normalizeTingkat($request->string('tingkat')->toString()) ?: null;
        $kelasId = $request->integer('kelas_id') ?: null;

        return view('admin.dataMaster.anggotaT2q.index', [
            'anggotaT2q' => $this->service->getAll($tahunAjaranId, $karyawanNipy, $tingkat, $kelasId),
            'selectedTahunAjaranId' => $tahunAjaranId,
            'selectedKaryawanNipy' => $karyawanNipy,
            'selectedTingkat' => $tingkat,
            'selectedKelasId' => $kelasId,
            ...$this->service->getFilterOptions(),
        ]);
    }

    /**
     * Menampilkan form tambah anggota T2Q baru.
     */
    public function create(Request $request): View
    {
        $oldTahunAjaranId = $request->old('tahun_ajaran_id');
        $targetTahunAjaranId = $request->integer('tahun_ajaran_id')
            ?: (is_numeric($oldTahunAjaranId) ? (int) $oldTahunAjaranId : null);

        return view('admin.dataMaster.anggotaT2q.create', [
            ...$this->service->getCreateFormOptions($targetTahunAjaranId),
            'preselectedKaryawanNipy' => trim($request->string('karyawan_nipy')->toString()) ?: null,
            'preselectedTingkat' => AnggotaT2q::normalizeTingkat($request->string('tingkat')->toString()) ?: null,
            'preselectedPendaftaran' => $request->string('pendaftaran')->toString() ?: null,
            'preselectedKeterangan' => $request->string('keterangan')->toString() ?: null,
        ]);
    }

    /**
     * Menyimpan anggota T2Q baru ke database.
     */
    public function store(AnggotaT2qRequest $request): RedirectResponse
    {
        $created = $this->service->store($request->validated());
        $jumlah = $created->count();

        return redirect()->route('anggota-t2q.index')
            ->with('success', "{$jumlah} Anggota T2Q berhasil disimpan.");
    }

    /**
     * Menampilkan form edit anggota T2Q.
     */
    public function edit(AnggotaT2q $anggotaT2q): View
    {
        $anggotaT2q->loadMissing([
            'anggotaKelas:id,siswa_nis,akademik_master_kelas_id',
            'anggotaKelas.kelas:id,akademik_master_tahun_ajaran_id,nama_kelas,jenjang_kelas,tingkatan_kelas',
            'guruT2q:nipy,nama_lengkap,gelar',
        ]);

        return view('admin.dataMaster.anggotaT2q.edit', [
            'anggotaT2q' => $anggotaT2q,
            ...$this->service->getFormOptions($anggotaT2q),
        ]);
    }

    /**
     * Memperbarui data anggota T2Q di database.
     */
    public function update(
        AnggotaT2qRequest $request,
        AnggotaT2q $anggotaT2q
    ): RedirectResponse {
        $this->service->update($anggotaT2q, $request->validated());

        return redirect()->route('anggota-t2q.index')
            ->with('success', 'Data Anggota T2Q berhasil diperbarui.');
    }

    /**
     * Menghapus data anggota T2Q dari database.
     */
    public function destroy(AnggotaT2q $anggotaT2q): RedirectResponse
    {
        $this->service->delete($anggotaT2q);

        return redirect()->route('anggota-t2q.index')
            ->with('success', 'Data Anggota T2Q berhasil dihapus.');
    }
}
