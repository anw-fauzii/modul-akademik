<?php

namespace App\Http\Controllers\Concerns;

use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait ManagesPresensiSiswaActions
{
    /**
     * Mengekstrak filter request untuk halaman matriks & log presensi.
     *
     * @return array<string, mixed>
     */
    protected function extractIndexFilters(Request $request): array
    {
        $filters = $request->only([
            'tanggal',
            'status',
            'kelas_id',
            'tahun_ajaran_id',
            'bulan',
        ]);
        $filters['has_explicit_tahun_ajaran'] = $request->exists('tahun_ajaran_id');
        $filters['has_explicit_filter'] = $request->hasAny([
            'tanggal',
            'status',
            'kelas_id',
            'tahun_ajaran_id',
            'bulan',
        ]);

        return $filters;
    }

    /**
     * Mengekstrak filter request untuk halaman rekap & persentase presensi.
     *
     * @return array<string, mixed>
     */
    protected function extractRekapFilters(Request $request): array
    {
        $filters = $request->only([
            'tahun_ajaran_id',
            'kelas_id',
            'bulan',
            'include_weekend',
        ]);
        $filters['has_explicit_tahun_ajaran'] = $request->exists('tahun_ajaran_id');
        $filters['has_explicit_filter'] = $request->hasAny([
            'tahun_ajaran_id',
            'kelas_id',
            'bulan',
            'include_weekend',
        ]);

        return $filters;
    }

    /**
     * Mengekstrak filter request untuk halaman statistik tahunan presensi.
     *
     * @return array<string, mixed>
     */
    protected function extractTahunanFilters(Request $request): array
    {
        $filters = $request->only([
            'nama_tahun_ajaran',
            'tahun_ajaran_id',
            'semester',
            'jenjang',
            'kelas_id',
            'include_weekend',
        ]);
        $filters['has_explicit_filter'] = $request->hasAny([
            'nama_tahun_ajaran',
            'tahun_ajaran_id',
            'semester',
            'jenjang',
            'kelas_id',
            'include_weekend',
        ]);

        return $filters;
    }

    /**
     * Mengambil parameter query redirect yang dipertahankan saat kembali ke index.
     *
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    protected function extractIndexRedirectQuery(Request $request, array $defaults = []): array
    {
        $redirectQuery = $defaults;
        if ($request->exists('redirect_tahun_ajaran_id')) {
            $redirectQuery['tahun_ajaran_id'] = (string) $request->input('redirect_tahun_ajaran_id', '');
        }
        foreach (['kelas_id', 'bulan', 'status', 'tanggal'] as $key) {
            if ($request->filled("redirect_{$key}")) {
                $redirectQuery[$key] = $request->input("redirect_{$key}");
            }
        }

        return $redirectQuery;
    }

    /**
     * Menyusun redirect respons standar sesudah hapus massal per tanggal dan kelas.
     */
    protected function buildBulkDestroyRedirect(
        Request $request,
        int $kelasId,
        string $tanggal,
        int $deletedCount,
        string $routePrefix
    ): RedirectResponse {
        $flashKey = $deletedCount > 0 ? 'success' : 'warning';
        $flashMessage = $deletedCount > 0
            ? "Berhasil menghapus {$deletedCount} data presensi siswa pada {$tanggal}."
            : "Tidak ada data presensi pada tanggal {$tanggal} untuk kelas yang dipilih.";

        if ($request->input('redirect_to') === 'bulk') {
            return redirect()
                ->route("{$routePrefix}.bulk", [
                    'kelas_id' => $kelasId,
                    'tanggal' => $tanggal,
                ])
                ->with($flashKey, $flashMessage);
        }

        $redirectQuery = $this->extractIndexRedirectQuery($request, [
            'kelas_id' => $kelasId,
            'bulan' => Carbon::parse($tanggal)->format('Y-m'),
        ]);

        return redirect()
            ->route("{$routePrefix}.index", $redirectQuery)
            ->with($flashKey, $flashMessage);
    }
}

