<?php

namespace App\Http\Controllers\Admin\Pelajaran;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Pelajaran\JamPelajaranRequest;
use App\Models\Admin\Pelajaran\JamPelajaran;
use App\Services\Admin\Pelajaran\JamPelajaranService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JamPelajaranController extends Controller
{
    public function __construct(
        protected JamPelajaranService $service
    ) {}

    /**
     * Menampilkan daftar jam pelajaran beserta filter unit dan pencarian.
     */
    public function index(Request $request): View
    {
        return view(
            'admin.pelajaran.jamPelajaran.index',
            $this->service->getIndexData($request->only([
                'unit',
                'search',
            ]))
        );
    }

    /**
     * Menampilkan form tambah jam pelajaran baru.
     */
    public function create(): View
    {
        return view('admin.pelajaran.jamPelajaran.create', $this->service->getFormOptions());
    }

    /**
     * Menyimpan jam pelajaran baru ke database.
     */
    public function store(JamPelajaranRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()->route('jam-pelajaran.index')
            ->with('success', 'Data Jam Pelajaran berhasil disimpan.');
    }

    /**
     * Menampilkan form edit jam pelajaran.
     */
    public function edit(JamPelajaran $jamPelajaran): View
    {
        return view('admin.pelajaran.jamPelajaran.edit', [
            'jamPelajaran' => $jamPelajaran,
            ...$this->service->getFormOptions(),
        ]);
    }

    /**
     * Memperbarui data jam pelajaran di database.
     */
    public function update(JamPelajaranRequest $request, JamPelajaran $jamPelajaran): RedirectResponse
    {
        $this->service->update($jamPelajaran, $request->validated());

        return redirect()->route('jam-pelajaran.index')
            ->with('success', 'Data Jam Pelajaran berhasil diperbarui.');
    }

    /**
     * Menghapus data jam pelajaran dari database.
     */
    public function destroy(JamPelajaran $jamPelajaran): RedirectResponse
    {
        $this->service->delete($jamPelajaran);

        return redirect()->route('jam-pelajaran.index')
            ->with('success', 'Data Jam Pelajaran berhasil dihapus.');
    }
}
