<?php

namespace App\Http\Controllers\Admin\DataMaster;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DataMaster\EkstrakurikulerRequest;
use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Services\Admin\DataMaster\EkstrakurikulerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EkstrakurikulerController extends Controller
{
    public function __construct(
        protected EkstrakurikulerService $service
    ) {}

    /**
     * Menampilkan daftar data ekstrakurikuler.
     */
    public function index(): View
    {
        return view('admin.dataMaster.ekstrakurikuler.index', [
            'ekstrakurikuler' => $this->service->getAll(),
        ]);
    }

    /**
     * Menampilkan form tambah ekstrakurikuler baru.
     */
    public function create(): View
    {
        return view('admin.dataMaster.ekstrakurikuler.create', $this->service->getFormOptions());
    }

    /**
     * Menyimpan data ekstrakurikuler baru ke database.
     */
    public function store(EkstrakurikulerRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()->route('ekstrakurikuler.index')
            ->with('success', 'Data Ekstrakurikuler berhasil disimpan.');
    }

    /**
     * Menampilkan form edit ekstrakurikuler.
     */
    public function edit(Ekstrakurikuler $ekstrakurikuler): View
    {
        return view('admin.dataMaster.ekstrakurikuler.edit', [
            'ekstrakurikuler' => $ekstrakurikuler,
            ...$this->service->getFormOptions(),
        ]);
    }

    /**
     * Memperbarui data ekstrakurikuler di database.
     */
    public function update(EkstrakurikulerRequest $request, Ekstrakurikuler $ekstrakurikuler): RedirectResponse
    {
        $this->service->update($ekstrakurikuler, $request->validated());

        return redirect()->route('ekstrakurikuler.index')
            ->with('success', 'Data Ekstrakurikuler berhasil diperbarui.');
    }

    /**
     * Menghapus data ekstrakurikuler dari database.
     */
    public function destroy(Ekstrakurikuler $ekstrakurikuler): RedirectResponse
    {
        $this->service->delete($ekstrakurikuler);

        return redirect()->route('ekstrakurikuler.index')
            ->with('success', 'Data Ekstrakurikuler berhasil dihapus.');
    }
}
