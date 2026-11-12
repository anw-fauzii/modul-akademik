<?php

namespace App\Http\Controllers\Admin\Informasi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Informasi\AgendaSekolahRequest;
use App\Models\Admin\Informasi\AgendaSekolah;
use App\Services\Admin\Informasi\AgendaSekolahService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgendaSekolahController extends Controller
{
    public function __construct(
        protected AgendaSekolahService $service
    ) {}

    /**
     * Menampilkan daftar agenda sekolah beserta filter tahun ajaran dan unit.
     */
    public function index(Request $request): View
    {
        return view('admin.informasi.agendaSekolah.index', $this->service->getIndexData($request->query()));
    }

    /**
     * Menampilkan form tambah agenda sekolah baru.
     */
    public function create(): View
    {
        return view('admin.informasi.agendaSekolah.create', $this->service->getFormOptions());
    }

    /**
     * Menyimpan data agenda sekolah baru ke database.
     */
    public function store(AgendaSekolahRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()->route('agenda-sekolah.index')
            ->with('success', 'Data Agenda Sekolah berhasil disimpan.');
    }

    /**
     * Menampilkan form edit agenda sekolah.
     */
    public function edit(AgendaSekolah $agendaSekolah): View
    {
        return view('admin.informasi.agendaSekolah.edit', [
            'agendaSekolah' => $agendaSekolah,
            ...$this->service->getFormOptions(),
        ]);
    }

    /**
     * Memperbarui data agenda sekolah di database.
     */
    public function update(AgendaSekolahRequest $request, AgendaSekolah $agendaSekolah): RedirectResponse
    {
        $this->service->update($agendaSekolah, $request->validated());

        return redirect()->route('agenda-sekolah.index')
            ->with('success', 'Data Agenda Sekolah berhasil diperbarui.');
    }

    /**
     * Menghapus data agenda sekolah dari database.
     */
    public function destroy(AgendaSekolah $agendaSekolah): RedirectResponse
    {
        $this->service->delete($agendaSekolah);

        return redirect()->route('agenda-sekolah.index')
            ->with('success', 'Data Agenda Sekolah berhasil dihapus.');
    }
}
