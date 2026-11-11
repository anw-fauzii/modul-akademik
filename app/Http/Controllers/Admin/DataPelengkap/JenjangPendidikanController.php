<?php

namespace App\Http\Controllers\Admin\DataPelengkap;

use App\Http\Controllers\Controller;
use App\Models\Admin\DataPelengkap\JenjangPendidikan;
use App\Http\Requests\Admin\DataPelengkap\JenjangPendidikanRequest;
use App\Services\Admin\DataPelengkap\JenjangPendidikanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class JenjangPendidikanController extends Controller
{
    public function __construct(
        protected JenjangPendidikanService $service
    ) {}

    public function index(): View
    {
        return view('admin.dataPelengkap.jenjangPendidikan.index', [
            'jenjangPendidikan' => $this->service->getAll()
        ]);
    }

    public function create(): View
    {
        return view('admin.dataPelengkap.jenjangPendidikan.create');
    }

    public function store(JenjangPendidikanRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()->route('jenjang-pendidikan.index')
            ->with('success', 'Jenjang Pendidikan berhasil disimpan');
    }

    public function edit(JenjangPendidikan $jenjangPendidikan): View
    {
        return view('admin.dataPelengkap.jenjangPendidikan.edit', compact('jenjangPendidikan'));
    }

    public function update(JenjangPendidikanRequest $request, JenjangPendidikan $jenjangPendidikan): RedirectResponse
    {
        $this->service->update($jenjangPendidikan, $request->validated());

        return redirect()->route('jenjang-pendidikan.index')
            ->with('success', 'Jenjang Pendidikan berhasil diupdate');
    }

    public function destroy(JenjangPendidikan $jenjangPendidikan): RedirectResponse
    {
        $this->service->delete($jenjangPendidikan);

        return redirect()->route('jenjang-pendidikan.index')
            ->with('success', 'Jenjang Pendidikan berhasil dihapus');
    }
}
