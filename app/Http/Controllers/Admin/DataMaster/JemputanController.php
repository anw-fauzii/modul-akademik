<?php

namespace App\Http\Controllers\Admin\DataMaster;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DataMaster\JemputanRequest;
use App\Models\Admin\DataMaster\Jemputan;
use App\Services\Admin\DataMaster\JemputanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class JemputanController extends Controller
{
    public function __construct(
        protected JemputanService $service
    ) {}

    /**
     * Menampilkan daftar data jemputan.
     */
    public function index(): View
    {
        return view('admin.dataMaster.jemputan.index', [
            'jemputan' => $this->service->getAll(),
        ]);
    }

    /**
     * Menampilkan form tambah jemputan baru.
     */
    public function create(): View
    {
        return view('admin.dataMaster.jemputan.create', $this->service->getFormOptions());
    }

    /**
     * Menyimpan data jemputan baru ke database.
     */
    public function store(JemputanRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()->route('jemputan.index')
            ->with('success', 'Data Jemputan berhasil disimpan.');
    }

    /**
     * Menampilkan form edit jemputan.
     */
    public function edit(Jemputan $jemputan): View
    {
        return view('admin.dataMaster.jemputan.edit', [
            'jemputan' => $jemputan,
            ...$this->service->getFormOptions(),
        ]);
    }

    /**
     * Memperbarui data jemputan di database.
     */
    public function update(JemputanRequest $request, Jemputan $jemputan): RedirectResponse
    {
        $this->service->update($jemputan, $request->validated());

        return redirect()->route('jemputan.index')
            ->with('success', 'Data Jemputan berhasil diperbarui.');
    }

    /**
     * Menghapus data jemputan dari database.
     */
    public function destroy(Jemputan $jemputan): RedirectResponse
    {
        $this->service->delete($jemputan);

        return redirect()->route('jemputan.index')
            ->with('success', 'Data Jemputan berhasil dihapus.');
    }
}
