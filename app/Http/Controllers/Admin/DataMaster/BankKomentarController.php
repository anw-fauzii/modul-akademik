<?php

namespace App\Http\Controllers\Admin\DataMaster;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DataMaster\KomentarRaporRequest;
use App\Http\Requests\Admin\DataMaster\KomentarT2qRequest;
use App\Models\Admin\DataMaster\KomentarRapor;
use App\Models\Admin\DataMaster\KomentarT2q;
use App\Services\Admin\DataMaster\BankKomentarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BankKomentarController extends Controller
{
    public function __construct(
        protected BankKomentarService $service
    ) {
        $this->middleware('role:super admin');
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', KomentarRapor::class);

        return view(
            'admin.dataMaster.bankKomentar.index',
            $this->service->getIndexData($request->only([
                'tab',
                'kategori',
                'jenjang',
                'status',
                'search',
                'sort',
            ]))
        );
    }

    public function storeWali(KomentarRaporRequest $request): RedirectResponse
    {
        $this->authorize('create', KomentarRapor::class);

        $this->service->storeTemplateWali($request->validated());

        return redirect()
            ->route('bank-komentar.index', ['tab' => 'wali'])
            ->with('success', 'Template komentar Wali Kelas berhasil ditambahkan.');
    }

    public function updateWali(KomentarRaporRequest $request, KomentarRapor $komentarRapor): RedirectResponse
    {
        $this->authorize('update', $komentarRapor);

        $this->service->updateTemplateWali($komentarRapor, $request->validated());

        return redirect()
            ->route('bank-komentar.index', ['tab' => 'wali'])
            ->with('success', 'Template komentar Wali Kelas berhasil diperbarui.');
    }

    public function toggleWali(KomentarRapor $komentarRapor): RedirectResponse
    {
        $this->authorize('update', $komentarRapor);

        $this->service->toggleTemplateWaliStatus($komentarRapor);

        return redirect()
            ->route('bank-komentar.index', ['tab' => 'wali'])
            ->with('success', 'Status keaktifan template komentar berhasil diperbarui.');
    }

    public function destroyWali(KomentarRapor $komentarRapor): RedirectResponse
    {
        $this->authorize('delete', $komentarRapor);

        $this->service->deleteTemplateWali($komentarRapor);

        return redirect()
            ->route('bank-komentar.index', ['tab' => 'wali'])
            ->with('success', 'Template komentar Wali Kelas berhasil dihapus.');
    }

    public function storeT2q(KomentarT2qRequest $request): RedirectResponse
    {
        $this->authorize('create', KomentarT2q::class);

        $this->service->storeKomentarT2q($request->validated());

        return redirect()
            ->route('bank-komentar.index', ['tab' => 't2q'])
            ->with('success', 'Poin komentar T2Q berhasil ditambahkan.');
    }

    public function updateT2q(KomentarT2qRequest $request, KomentarT2q $komentarT2q): RedirectResponse
    {
        $this->authorize('update', $komentarT2q);

        $this->service->updateKomentarT2q($komentarT2q, $request->validated());

        return redirect()
            ->route('bank-komentar.index', ['tab' => 't2q'])
            ->with('success', 'Poin komentar T2Q berhasil diperbarui.');
    }

    public function destroyT2q(KomentarT2q $komentarT2q): RedirectResponse
    {
        $this->authorize('delete', $komentarT2q);

        $this->service->deleteKomentarT2q($komentarT2q);

        return redirect()
            ->route('bank-komentar.index', ['tab' => 't2q'])
            ->with('success', 'Poin komentar T2Q berhasil dihapus.');
    }
}
