<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Requests\Admin\T2q\BaseYaumiyahT2qRequest;
use App\Services\Admin\T2q\BaseYaumiyahT2qService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

trait ManagesYaumiyahT2qActions
{
    abstract protected function yaumiyahService(): BaseYaumiyahT2qService;

    abstract protected function indexRouteName(): string;

    abstract protected function indexViewName(): string;

    abstract protected function matriksViewName(): string;

    abstract protected function statistikViewName(): string;

    abstract protected function labelJudul(): string;

    /**
     * Menampilkan workspace input harian yaumiyah T2Q per tanggal, guru, dan tab tingkat.
     */
    public function index(Request $request): View
    {
        return view(
            $this->indexViewName(),
            $this->yaumiyahService()->getWorkspaceData($this->extractFilterParams($request))
        );
    }

    /**
     * Menampilkan matriks catatan yaumiyah mingguan (Senin – Kamis) per guru dan tab tingkat.
     */
    public function matriks(Request $request): View
    {
        return view(
            $this->matriksViewName(),
            $this->yaumiyahService()->getMatrixData($this->extractFilterParams($request))
        );
    }

    /**
     * Menampilkan statistik yaumiyah T2Q berdasarkan tahun_ajaran_id, guru, dan tingkat.
     */
    public function statistik(Request $request): View
    {
        return view(
            $this->statistikViewName(),
            $this->yaumiyahService()->getStatistikData($request->only([
                'tahun_ajaran_id',
                'karyawan_nipy',
                'tingkat',
            ]))
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function extractFilterParams(Request $request): array
    {
        return $request->only([
            'tanggal',
            'karyawan_nipy',
            'tingkat',
        ]);
    }

    /**
     * Menyimpan catatan setoran yaumiyah harian secara massal lalu mengarahkan kembali ke tab yang sama.
     */
    protected function saveYaumiyahBatch(BaseYaumiyahT2qRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $savedCount = $this->yaumiyahService()->saveBatch(
            (string) ($validated['tanggal'] ?? $request->input('tanggal', now()->toDateString())),
            is_array($validated['rows'] ?? null) ? $validated['rows'] : []
        );

        return redirect()
            ->route($this->indexRouteName(), $this->extractRedirectQuery($request))
            ->with('success', "Berhasil menyimpan {$savedCount} setoran {$this->labelJudul()}.");
    }

    /**
     * Menghapus satu catatan yaumiyah harian dan mengembalikan pengguna ke tab yang sama.
     */
    protected function deleteYaumiyahEntry(Request $request, Model $entry): RedirectResponse
    {
        $this->yaumiyahService()->delete($entry);

        return redirect()
            ->route($this->indexRouteName(), $this->extractRedirectQuery($request))
            ->with('success', "Catatan {$this->labelJudul()} berhasil dihapus.");
    }

    /**
     * @return array<string, string>
     */
    private function extractRedirectQuery(Request $request): array
    {
        $params = [];

        foreach (['tanggal', 'karyawan_nipy', 'tingkat'] as $key) {
            $value = trim((string) $request->input($key, $request->query($key, '')));
            if ($value !== '') {
                $params[$key] = $value;
            }
        }

        return $params;
    }
}
