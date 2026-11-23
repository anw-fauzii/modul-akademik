<?php

namespace App\Http\Controllers\Admin\Penilaian;

use App\Http\Controllers\Controller;
use App\Models\Admin\Penilaian\PenilaianFinalisasi;
use App\Services\Admin\Penilaian\PenilaianFinalisasiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class FinalisasiController extends Controller
{
    public function __construct(
        private readonly PenilaianFinalisasiService $finalisasiService
    ) {
        $this->middleware('role:super admin|wali kelas|guru mapel sd|guru t2q');
    }

    /**
     * Memfinalkan komponen penilaian (Mapel, Ekskul Wajib/Pilihan, T2Q, atau Presensi) untuk 1 periode.
     */
    public function finalize(Request $request): RedirectResponse
    {
        $validated = $this->validateComponentRequest($request);
        Gate::authorize('manageComponent', [PenilaianFinalisasi::class, $validated]);

        $record = $this->finalisasiService->finalize($request->user(), $validated);
        $label = PenilaianFinalisasi::JENIS_OPTIONS[$record->jenis_komponen] ?? 'Komponen penilaian';
        $periodeLabel = $this->finalisasiService->resolvePeriodeLabel($record->periode);

        return back()->with(
            'success',
            "{$label} periode {$periodeLabel} berhasil DIFINALISASI dan dikunci."
        );
    }

    /**
     * Membuka kunci komponen penilaian yang sudah final dengan alasan wajib (audit log).
     */
    public function unlock(Request $request): RedirectResponse
    {
        $validated = $this->validateComponentRequest($request, true);
        Gate::authorize('manageComponent', [PenilaianFinalisasi::class, $validated]);

        $record = $this->finalisasiService->unlock(
            $request->user(),
            $validated,
            (string) $validated['unlock_reason']
        );
        $label = PenilaianFinalisasi::JENIS_OPTIONS[$record->jenis_komponen] ?? 'Komponen penilaian';
        $periodeLabel = $this->finalisasiService->resolvePeriodeLabel($record->periode);

        return back()->with(
            'success',
            "Kunci {$label} periode {$periodeLabel} berhasil dibuka (status kembali ke Draft)."
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validateComponentRequest(Request $request, bool $requireReason = false): array
    {
        $rules = [
            'jenis_komponen' => [
                'required',
                'string',
                Rule::in([
                    PenilaianFinalisasi::JENIS_MAPEL,
                    PenilaianFinalisasi::JENIS_EKSKUL_WAJIB,
                    PenilaianFinalisasi::JENIS_EKSKUL_PILIHAN,
                    PenilaianFinalisasi::JENIS_T2Q,
                    PenilaianFinalisasi::JENIS_PRESENSI,
                ]),
            ],
            'tahun_ajaran_id' => ['required', 'integer', 'exists:akademik_master_tahun_ajaran,id'],
            'kelas_id' => ['nullable', 'integer', 'exists:akademik_master_kelas,id'],
            'mata_pelajaran_id' => ['nullable', 'integer', 'exists:akademik_pelajaran_mata_pelajaran,id'],
            'ekstrakurikuler_id' => ['nullable', 'integer', 'exists:akademik_master_ekstrakuler,id'],
            'periode' => ['required', 'string', Rule::in(['mid', 'semester'])],
        ];

        if ($requireReason) {
            $rules['unlock_reason'] = ['required', 'string', 'min:5', 'max:500'];
        }

        return $request->validate($rules, [
            'unlock_reason.required' => 'Alasan pembukaan kunci wajib diisi.',
            'unlock_reason.min' => 'Alasan pembukaan kunci minimal 5 karakter.',
            'unlock_reason.max' => 'Alasan pembukaan kunci maksimal 500 karakter.',
        ]);
    }
}
