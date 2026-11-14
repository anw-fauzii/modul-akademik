<?php

namespace App\Http\Controllers\Admin\Pelajaran;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Pelajaran\JadwalPelajaranRequest;
use App\Models\Admin\Pelajaran\JadwalPelajaran;
use App\Services\Admin\Pelajaran\JadwalPelajaranService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JadwalPelajaranController extends Controller
{
    public function __construct(
        protected JadwalPelajaranService $service
    ) {}

    /**
     * Menampilkan daftar jadwal pelajaran beserta filter dan pratinjau matriks mingguan per kelas.
     */
    public function index(Request $request): View
    {
        return view(
            'admin.pelajaran.jadwalPelajaran.index',
            $this->service->getIndexData($request->only([
                'tahun_ajaran_id',
                'unit',
                'kelas_id',
                'hari',
                'status',
                'search',
            ]))
        );
    }

    /**
     * Menampilkan form tambah jadwal pelajaran baru.
     */
    public function create(Request $request): View
    {
        return view(
            'admin.pelajaran.jadwalPelajaran.create',
            $this->service->getFormOptions($request->only([
                'tahun_ajaran_id',
                'kelas_id',
                'jam_pelajaran_id',
                'hari',
            ]))
        );
    }

    /**
     * Menyimpan jadwal pelajaran baru ke database.
     */
    public function store(JadwalPelajaranRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $this->service->store($validated);

        return redirect()->route('jadwal-pelajaran.index', array_filter([
            'tahun_ajaran_id' => $validated['akademik_master_tahun_ajaran_id'] ?? null,
            'kelas_id' => $validated['akademik_master_kelas_id'] ?? null,
        ]))->with('success', 'Data Jadwal Pelajaran berhasil disimpan.');
    }

    /**
     * Menampilkan form edit jadwal pelajaran.
     */
    public function edit(JadwalPelajaran $jadwalPelajaran): View
    {
        return view('admin.pelajaran.jadwalPelajaran.edit', [
            'jadwalPelajaran' => $jadwalPelajaran,
            ...$this->service->getFormOptions(),
        ]);
    }

    /**
     * Memperbarui data jadwal pelajaran di database.
     */
    public function update(JadwalPelajaranRequest $request, JadwalPelajaran $jadwalPelajaran): RedirectResponse
    {
        $validated = $request->validated();
        $this->service->update($jadwalPelajaran, $validated);

        return redirect()->route('jadwal-pelajaran.index', array_filter([
            'tahun_ajaran_id' => $validated['akademik_master_tahun_ajaran_id'] ?? null,
            'kelas_id' => $validated['akademik_master_kelas_id'] ?? null,
        ]))->with('success', 'Data Jadwal Pelajaran berhasil diperbarui.');
    }

    /**
     * Menghapus data jadwal pelajaran dari database.
     */
    public function destroy(JadwalPelajaran $jadwalPelajaran): RedirectResponse
    {
        $this->service->delete($jadwalPelajaran);

        return redirect()->route('jadwal-pelajaran.index')
            ->with('success', 'Data Jadwal Pelajaran berhasil dihapus.');
    }

    /**
     * Mengubah status aktif/nonaktif jadwal pelajaran secara cepat.
     */
    public function toggleStatus(JadwalPelajaran $jadwalPelajaran): RedirectResponse
    {
        $this->service->toggleStatus($jadwalPelajaran);

        $statusText = $jadwalPelajaran->fresh()?->status ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('jadwal-pelajaran.index')
            ->with('success', "Status Jadwal Pelajaran berhasil {$statusText}.");
    }
}
