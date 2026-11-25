<?php

namespace App\Http\Controllers\WaliKelas;

use App\Http\Controllers\Controller;
use App\Http\Requests\WaliKelas\SaveCatatanRaporWaliRequest;
use App\Http\Requests\WaliKelas\SaveSingleCatatanRaporWaliRequest;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\Penilaian\CatatanRaporWali;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Services\WaliKelas\RaporCatatanWaliService;
use App\Support\Sqid;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CatatanRaporController extends Controller
{
    public function __construct(
        private readonly RaporCatatanWaliService $service
    ) {
        $this->middleware('role:super admin|wali kelas');
    }

    /**
     * Menampilkan halaman pengisian Catatan/Komentar Rapor per kelas untuk Wali Kelas & Pendamping (Mid & Semester).
     */
    public function index(Request $request): View
    {
        Gate::forUser($request->user())->authorize('viewAny', CatatanRaporWali::class);

        $requestedKelasId = $request->integer('kelas_id') ?: null;
        $periode = $request->string('periode', PeriodeTp::PERIODE_MID)->toString();

        return view(
            'waliKelas.catatanRapor.index',
            $this->service->getWorkspaceData($request->user(), $requestedKelasId, $periode)
        );
    }

    /**
     * Menyimpan batch komentar rapor satu kelas untuk periode Mid Semester atau Semester.
     */
    public function store(SaveCatatanRaporWaliRequest $request): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('saveBatch', CatatanRaporWali::class);

        $validated = $request->validated();
        $kelasId = (int) $validated['kelas_id'];
        $periode = (string) $validated['periode'];

        $result = $this->service->saveBatch(
            $request->user(),
            $kelasId,
            $periode,
            $validated['catatan'] ?? [],
            $validated['komentar_rapor_id'] ?? []
        );

        $kelasSqid = Sqid::encodeFor(Kelas::class, $kelasId) ?? $kelasId;

        return redirect()
            ->route('wali-kelas.catatan-rapor.index', [
                'kelas_id' => $kelasSqid,
                'periode' => $periode,
            ])
            ->with(
                'success',
                "Catatan rapor periode {$result['periode_label']} berhasil disimpan ({$result['filled_count']} siswa terisi, tercatat sebagai {$result['actor_role_label']})."
            );
    }

    /**
     * Menyimpan catatan rapor untuk 1 siswa langsung dari halaman Detail Perkembangan Siswa.
     */
    public function updateSingle(SaveSingleCatatanRaporWaliRequest $request, AnggotaKelas $anggotaKelas): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('updateForAnggota', [CatatanRaporWali::class, $anggotaKelas]);

        $validated = $request->validated();
        $periode = (string) $validated['periode'];

        $this->service->saveSingle(
            $request->user(),
            $anggotaKelas,
            $periode,
            $validated['catatan'] ?? null,
            isset($validated['komentar_rapor_id']) ? (int) $validated['komentar_rapor_id'] : null
        );

        $periodeLabel = PeriodeTp::PERIODE_OPTIONS[$periode] ?? strtoupper($periode);

        return redirect()
            ->route('wali-kelas.perkembangan-siswa.show', [
                'anggota_kelas' => $anggotaKelas,
                'periode' => $periode,
            ])
            ->with('success', "Catatan Wali Kelas periode {$periodeLabel} untuk {$anggotaKelas->siswa?->nama_lengkap} berhasil diperbarui.");
    }
}
