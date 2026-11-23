<?php

namespace App\Http\Controllers\GuruT2q;

use App\Http\Controllers\Controller;
use App\Http\Requests\GuruT2q\SaveRaporT2qRequest;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Models\Admin\T2q\RaporT2q;
use App\Services\GuruT2q\GuruT2qRaporService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RaporT2qController extends Controller
{
    public function __construct(
        private readonly GuruT2qRaporService $raporService
    ) {
        $this->middleware('role:super admin|guru t2q');
    }

    /**
     * Menampilkan halaman Rekap Nilai Terakhir & Deskripsi Rapor T2Q (jenjang SD).
     */
    public function index(Request $request): View
    {
        Gate::forUser($request->user())->authorize('viewAny', RaporT2q::class);

        $data = $this->raporService->getWorkspaceData(
            $request->user(),
            $request->only(['periode', 'tingkat'])
        );

        return view('guruT2q.rapor.index', $data);
    }

    /**
     * Menyimpan batch aspek pengamatan dan deskripsi Rapor T2Q.
     */
    public function store(SaveRaporT2qRequest $request): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('saveBatch', RaporT2q::class);

        $validated = $request->validated();
        $result = $this->raporService->saveBatch($request->user(), $validated);

        return redirect()
            ->route('guru-t2q.rapor.index', array_filter([
                'periode' => $validated['periode'] ?? PeriodeTp::PERIODE_MID,
                'tingkat' => $validated['tingkat'] ?? null,
            ], static fn ($v): bool => $v !== null && $v !== ''))
            ->with(
                'success',
                "Rekap & Deskripsi Rapor T2Q periode {$result['periode_label']} untuk {$result['saved_count']} siswa SD berhasil disimpan."
            );
    }

    /**
     * Menarik ulang nilai terakhir Yaumiyah & menyusun ulang draf deskripsi Rapor T2Q otomatis.
     */
    public function generate(Request $request): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('saveBatch', RaporT2q::class);

        $validated = $request->validate([
            'periode' => ['required', 'string', Rule::in([PeriodeTp::PERIODE_MID, PeriodeTp::PERIODE_SEMESTER])],
            'tingkat' => ['nullable', 'string', 'max:20'],
        ]);

        $periode = (string) $validated['periode'];
        $tingkat = isset($validated['tingkat']) ? (string) $validated['tingkat'] : null;

        $result = $this->raporService->regenerateAllDrafts($request->user(), $periode, $tingkat);

        return redirect()
            ->route('guru-t2q.rapor.index', array_filter([
                'periode' => $periode,
                'tingkat' => $tingkat,
            ], static fn ($v): bool => $v !== null && $v !== ''))
            ->with(
                'success',
                $result['generated_count'] > 0
                    ? "Nilai terakhir & draf deskripsi Rapor T2Q periode {$result['periode_label']} untuk {$result['generated_count']} siswa SD berhasil diperbarui otomatis."
                    : "Belum ada setoran Yaumiyah baru untuk disinkronkan pada periode {$result['periode_label']}."
            );
    }
}
