<?php

namespace App\Http\Controllers\GuruT2q;

use App\Http\Controllers\Controller;
use App\Http\Requests\GuruT2q\SaveCatatanRaporT2qRequest;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Models\Admin\T2q\RaporT2q;
use App\Services\GuruT2q\GuruT2qRaporService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CatatanRaporT2qController extends Controller
{
    public function __construct(
        protected GuruT2qRaporService $raporService
    ) {
        $this->middleware('role:super admin|guru t2q');
    }

    public function index(Request $request): View
    {
        Gate::forUser($request->user())->authorize('viewAny', RaporT2q::class);

        $periode = (string) $request->query('periode', PeriodeTp::PERIODE_MID);
        $tingkat = (string) $request->query('tingkat', '');

        $data = $this->raporService->getCatatanWorkspaceData($request->user(), $periode, $tingkat);

        return view('guruT2q.catatanRapor.index', $data);
    }

    public function store(SaveCatatanRaporT2qRequest $request): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('saveBatch', RaporT2q::class);

        $validated = $request->validated();
        $result = $this->raporService->saveCatatanBatch($request->user(), $validated);

        return redirect()
            ->route('guru-t2q.catatan-rapor.index', array_filter([
                'periode' => $result['periode'],
                'tingkat' => $validated['tingkat'] !== '' ? $validated['tingkat'] : null,
            ], static fn ($value): bool => $value !== null))
            ->with(
                'success',
                "Berhasil menyimpan {$result['saved_count']} catatan Guru T2Q (periode {$result['periode_label']})."
            );
    }
}
