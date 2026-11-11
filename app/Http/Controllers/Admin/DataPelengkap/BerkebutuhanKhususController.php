<?php

namespace App\Http\Controllers\Admin\DataPelengkap;

use App\Http\Controllers\Controller;
use App\Models\Admin\DataPelengkap\BerkebutuhanKhusus;
use App\Http\Requests\Admin\DataPelengkap\BerkebutuhanKhususRequest;
use App\Services\Admin\DataPelengkap\BerkebutuhanKhususService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BerkebutuhanKhususController extends Controller
{
    public function __construct(
        protected BerkebutuhanKhususService $service
    ) {}

    public function index(): View
    {
        return view('admin.dataPelengkap.berkebutuhanKhusus.index', [
            'berkebutuhanKhusus' => $this->service->getAll()
        ]);
    }

    public function create(): View
    {
        return view('admin.dataPelengkap.berkebutuhanKhusus.create');
    }   

    public function store(BerkebutuhanKhususRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()->route('berkebutuhan-khusus.index')
            ->with('success', 'Berkebutuhan Khusus berhasil disimpan');
    }

    public function edit(BerkebutuhanKhusus $berkebutuhanKhusus): View
    {
        return view('admin.dataPelengkap.berkebutuhanKhusus.edit', compact('berkebutuhanKhusus'));
    }

    public function update(BerkebutuhanKhususRequest $request, BerkebutuhanKhusus $berkebutuhanKhusus): RedirectResponse
    {
        $this->service->update($berkebutuhanKhusus, $request->validated());

        return redirect()->route('berkebutuhan-khusus.index')
            ->with('success', 'Berkebutuhan Khusus berhasil diupdate');
    }

    public function destroy(BerkebutuhanKhusus $berkebutuhanKhusus): RedirectResponse
    {
        $this->service->delete($berkebutuhanKhusus);

        return redirect()->route('berkebutuhan-khusus.index')
            ->with('success', 'Berkebutuhan Khusus berhasil dihapus');
    }
}
