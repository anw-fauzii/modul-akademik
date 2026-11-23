<?php

namespace App\Http\Controllers\GuruT2q;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\T2q\BaseYaumiyahT2qRequest;
use App\Services\Admin\T2q\BaseYaumiyahT2qService;
use App\Services\GuruT2q\GuruT2qYaumiyahService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

abstract class BaseGuruT2qYaumiyahController extends Controller
{
    abstract protected function modeKey(): string;

    abstract protected function indexRouteName(): string;

    abstract protected function indexViewName(): string;

    abstract protected function matriksViewName(): string;

    abstract protected function statistikViewName(): string;

    abstract protected function labelJudul(): string;

    abstract protected function yaumiyahWriteService(): BaseYaumiyahT2qService;

    abstract protected function yaumiyahFacade(): GuruT2qYaumiyahService;

    abstract protected function ineligibleHeroTitle(): string;

    /**
     * Menampilkan workspace input harian Yaumiyah T2Q khusus siswa bimbingan Guru T2Q aktif.
     */
    public function index(Request $request): View
    {
        $data = $this->yaumiyahFacade()->getWorkspacePageData(
            $request->user(),
            $this->modeKey(),
            $this->extractWorkspaceFilters($request)
        );

        return $this->renderScopedView($this->indexViewName(), $data);
    }

    /**
     * Menampilkan matriks mingguan (Senin – Kamis) Yaumiyah T2Q siswa bimbingan Guru T2Q.
     */
    public function matriks(Request $request): View
    {
        $data = $this->yaumiyahFacade()->getMatriksPageData(
            $request->user(),
            $this->modeKey(),
            $this->extractWorkspaceFilters($request)
        );

        return $this->renderScopedView($this->matriksViewName(), $data);
    }

    /**
     * Menampilkan statistik Tahun Ajaran Yaumiyah T2Q siswa bimbingan Guru T2Q.
     */
    public function statistik(Request $request): View
    {
        $data = $this->yaumiyahFacade()->getStatistikPageData(
            $request->user(),
            $this->modeKey(),
            $request->only(['tahun_ajaran_id', 'tingkat'])
        );

        return $this->renderScopedView($this->statistikViewName(), $data);
    }

    /**
     * Menyimpan batch setoran harian khusus siswa bimbingan Guru T2Q (anti ID-tampering).
     */
    protected function saveScopedBatch(BaseYaumiyahT2qRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $nipy = trim((string) ($request->user()?->no_induk ?? ''));

        $savedCount = $this->yaumiyahWriteService()->saveBatch(
            (string) ($validated['tanggal'] ?? $request->input('tanggal', now()->toDateString())),
            is_array($validated['rows'] ?? null) ? $validated['rows'] : [],
            $nipy
        );

        return redirect()
            ->route($this->indexRouteName(), $this->extractRedirectQuery($request))
            ->with('success', "Berhasil menyimpan {$savedCount} setoran {$this->labelJudul()}.");
    }

    /**
     * Menghapus satu entri yaumiyah milik siswa bimbingan Guru T2Q aktif.
     */
    protected function deleteScopedEntry(Request $request, Model $entry): RedirectResponse
    {
        $this->yaumiyahWriteService()->delete($entry);

        return redirect()
            ->route($this->indexRouteName(), $this->extractRedirectQuery($request))
            ->with('success', "Catatan {$this->labelJudul()} berhasil dihapus.");
    }

    /**
     * @return array<string, mixed>
     */
    private function extractWorkspaceFilters(Request $request): array
    {
        return $request->only([
            'tanggal',
            'tingkat',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function extractRedirectQuery(Request $request): array
    {
        $params = [];

        foreach (['tanggal', 'tingkat'] as $key) {
            $value = trim((string) $request->input($key, $request->query($key, '')));
            if ($value !== '') {
                $params[$key] = $value;
            }
        }

        return $params;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function renderScopedView(string $viewName, array $data): View
    {
        if (! ($data['isEligible'] ?? false)) {
            return view('guruT2q.ineligible', [
                ...$data,
                'pageTitle' => $this->labelJudul().' Guru T2Q',
                'heroTitle' => $this->ineligibleHeroTitle(),
            ]);
        }

        return view($viewName, $data);
    }
}
