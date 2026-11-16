<?php

namespace App\Http\Controllers\Admin\TujuanPembelajaran;

use App\Exports\Admin\TujuanPembelajaran\TujuanPembelajaranExport;
use App\Exports\Admin\TujuanPembelajaran\TujuanPembelajaranTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TujuanPembelajaran\ImportTujuanPembelajaranRequest;
use App\Http\Requests\Admin\TujuanPembelajaran\TujuanPembelajaranRequest;
use App\Imports\Admin\TujuanPembelajaran\TujuanPembelajaranImport;
use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use App\Services\Admin\TujuanPembelajaran\TujuanPembelajaranService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TujuanPembelajaranController extends Controller
{
    public function __construct(
        protected TujuanPembelajaranService $service
    ) {}

    /**
     * Menampilkan daftar tujuan pembelajaran (TP) beserta filter tahun ajaran/semester, unit, mapel, dan tingkat.
     */
    public function index(Request $request): View
    {
        return view(
            'admin.tujuanPembelajaran.tujuanPembelajaran.index',
            $this->service->getIndexData($request->only([
                'tahun_ajaran_id',
                'unit',
                'mata_pelajaran_id',
                'tingkatan_kelas',
                'fase',
                'status',
                'search',
            ]))
        );
    }

    /**
     * Menampilkan form tambah tujuan pembelajaran baru.
     */
    public function create(Request $request): View
    {
        return view(
            'admin.tujuanPembelajaran.tujuanPembelajaran.create',
            $this->service->getFormOptions($request->only([
                'tahun_ajaran_id',
                'mata_pelajaran_id',
                'tingkatan_kelas',
            ]))
        );
    }

    /**
     * Menyimpan data tujuan pembelajaran baru ke database.
     */
    public function store(TujuanPembelajaranRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $this->service->store($validated);

        return redirect()->route('tujuan-pembelajaran.index', array_filter([
            'tahun_ajaran_id' => $validated['akademik_master_tahun_ajaran_id'] ?? null,
            'mata_pelajaran_id' => $validated['akademik_pelajaran_mata_pelajaran_id'] ?? null,
            'tingkatan_kelas' => $validated['tingkatan_kelas'] ?? null,
        ]))->with('success', 'Data Tujuan Pembelajaran berhasil disimpan.');
    }

    /**
     * Menampilkan form edit tujuan pembelajaran.
     */
    public function edit(TujuanPembelajaran $tujuanPembelajaran): View
    {
        return view('admin.tujuanPembelajaran.tujuanPembelajaran.edit', [
            'tujuanPembelajaran' => $tujuanPembelajaran,
            ...$this->service->getFormOptions(),
        ]);
    }

    /**
     * Memperbarui data tujuan pembelajaran di database.
     */
    public function update(TujuanPembelajaranRequest $request, TujuanPembelajaran $tujuanPembelajaran): RedirectResponse
    {
        $validated = $request->validated();
        $this->service->update($tujuanPembelajaran, $validated);

        return redirect()->route('tujuan-pembelajaran.index', array_filter([
            'tahun_ajaran_id' => $validated['akademik_master_tahun_ajaran_id'] ?? null,
            'mata_pelajaran_id' => $validated['akademik_pelajaran_mata_pelajaran_id'] ?? null,
            'tingkatan_kelas' => $validated['tingkatan_kelas'] ?? null,
        ]))->with('success', 'Data Tujuan Pembelajaran berhasil diperbarui.');
    }

    /**
     * Menghapus data tujuan pembelajaran dari database.
     */
    public function destroy(TujuanPembelajaran $tujuanPembelajaran): RedirectResponse
    {
        $this->service->delete($tujuanPembelajaran);

        return redirect()->route('tujuan-pembelajaran.index')
            ->with('success', 'Data Tujuan Pembelajaran berhasil dihapus.');
    }

    /**
     * Mengubah status aktif/nonaktif tujuan pembelajaran secara cepat.
     */
    public function toggleStatus(TujuanPembelajaran $tujuanPembelajaran): RedirectResponse
    {
        $this->service->toggleStatus($tujuanPembelajaran);

        $statusText = $tujuanPembelajaran->fresh()?->status ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('tujuan-pembelajaran.index')
            ->with('success', "Status Tujuan Pembelajaran berhasil {$statusText}.");
    }

    /**
     * Mengekspor daftar tujuan pembelajaran sesuai filter aktif ke file Excel.
     */
    public function export(Request $request): BinaryFileResponse
    {
        $filters = $request->only([
            'tahun_ajaran_id',
            'unit',
            'mata_pelajaran_id',
            'tingkatan_kelas',
            'fase',
            'status',
            'search',
        ]);

        $filename = 'tujuan-pembelajaran-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(new TujuanPembelajaranExport($filters), $filename);
    }

    /**
     * Mengunduh template Excel beserta referensi Tahun Ajaran dan Mata Pelajaran untuk import TP.
     */
    public function template(): BinaryFileResponse
    {
        return Excel::download(new TujuanPembelajaranTemplateExport, 'template-import-tujuan-pembelajaran.xlsx');
    }

    /**
     * Mengimpor data tujuan pembelajaran dari file Excel/CSV secara batch tanpa N+1.
     */
    public function import(ImportTujuanPembelajaranRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $defaultTaId = isset($validated['default_tahun_ajaran_id']) && $validated['default_tahun_ajaran_id'] !== null
            ? (int) $validated['default_tahun_ajaran_id']
            : null;

        $import = new TujuanPembelajaranImport($defaultTaId);
        Excel::import($import, $request->file('file'));

        if ($import->getErrors() !== []) {
            return redirect()->route('tujuan-pembelajaran.index')
                ->with('error', 'Import TP gagal. Silakan periksa daftar baris bermasalah di bawah ini.')
                ->with('import_errors', array_slice($import->getErrors(), 0, 15));
        }

        return redirect()->route('tujuan-pembelajaran.index')
            ->with(
                'success',
                "Import TP berhasil: {$import->getCreatedCount()} data baru ditambahkan, {$import->getUpdatedCount()} data diperbarui."
            );
    }
}
