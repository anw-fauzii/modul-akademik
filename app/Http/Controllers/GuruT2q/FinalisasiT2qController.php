<?php

namespace App\Http\Controllers\GuruT2q;

use App\Http\Controllers\Controller;
use App\Models\Admin\Penilaian\PenilaianFinalisasi;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Services\GuruT2q\GuruT2qScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class FinalisasiT2qController extends Controller
{
    public function __construct(
        private readonly GuruT2qScopeService $scopeService
    ) {
        $this->middleware('role:super admin|guru t2q');
    }

    /**
     * Memfinalkan program T2Q periode tertentu bagi seluruh kelompok bimbingan Guru T2Q aktif.
     */
    public function finalize(Request $request): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('viewAny', PenilaianFinalisasi::class);

        $validated = $request->validate([
            'periode' => ['required', 'string', Rule::in([PeriodeTp::PERIODE_MID, PeriodeTp::PERIODE_SEMESTER])],
        ]);

        $periode = (string) $validated['periode'];
        $this->scopeService->finalizeBatchForUser($request->user(), $periode);
        $periodeLabel = PeriodeTp::PERIODE_OPTIONS[$periode] ?? strtoupper($periode);

        return back()->with(
            'success',
            "Program T2Q periode {$periodeLabel} berhasil DIFINALISASI dan dikunci."
        );
    }

    /**
     * Membuka kunci finalisasi program T2Q periode tertentu dengan alasan wajib (audit log).
     */
    public function unlock(Request $request): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('viewAny', PenilaianFinalisasi::class);

        $validated = $request->validate([
            'periode' => ['required', 'string', Rule::in([PeriodeTp::PERIODE_MID, PeriodeTp::PERIODE_SEMESTER])],
            'unlock_reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'unlock_reason.required' => 'Alasan pembukaan kunci wajib diisi.',
            'unlock_reason.min' => 'Alasan pembukaan kunci minimal 5 karakter.',
            'unlock_reason.max' => 'Alasan pembukaan kunci maksimal 500 karakter.',
        ]);

        $periode = (string) $validated['periode'];
        $this->scopeService->unlockBatchForUser($request->user(), $periode, (string) $validated['unlock_reason']);
        $periodeLabel = PeriodeTp::PERIODE_OPTIONS[$periode] ?? strtoupper($periode);

        return back()->with(
            'success',
            "Kunci Program T2Q periode {$periodeLabel} berhasil dibuka (status kembali ke Draft)."
        );
    }
}
