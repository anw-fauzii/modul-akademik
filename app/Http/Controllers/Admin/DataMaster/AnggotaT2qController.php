<?php

namespace App\Http\Controllers\Admin\DataMaster;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DataMaster\AnggotaT2qRequest;
use App\Models\Admin\DataMaster\AnggotaT2q;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\Penilaian\PenilaianFinalisasi;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Services\Admin\DataMaster\AnggotaT2qService;
use App\Services\Admin\Penilaian\PenilaianFinalisasiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnggotaT2qController extends Controller
{
    public function __construct(
        protected AnggotaT2qService $service,
        protected ?PenilaianFinalisasiService $finalisasiService = null
    ) {
        $this->middleware('role:super admin');
        $this->authorizeResource(AnggotaT2q::class, 'anggota_t2q');
        $this->finalisasiService ??= new PenilaianFinalisasiService;
    }

    /**
     * Menampilkan daftar anggota T2Q dengan filter tahun ajaran, guru T2Q, tingkat, dan kelas opsional.
     */
    public function index(Request $request): View
    {
        $tahunAjaranId = $request->integer('tahun_ajaran_id') ?: null;
        $karyawanNipy = trim($request->string('karyawan_nipy')->toString()) ?: null;
        $tingkat = AnggotaT2q::normalizeTingkat($request->string('tingkat')->toString()) ?: null;
        $kelasId = $request->integer('kelas_id') ?: null;

        $t2qFinalisasi = null;
        $selectedKelasObj = $kelasId !== null ? Kelas::query()->select(['id', 'akademik_master_tahun_ajaran_id', 'nama_kelas'])->find($kelasId) : null;
        if ($selectedKelasObj !== null) {
            $t2qFinalisasi = [
                'kelas' => $selectedKelasObj,
                'mid' => $this->finalisasiService->getStatusPayloadForComponent([
                    'jenis_komponen' => PenilaianFinalisasi::JENIS_T2Q,
                    'tahun_ajaran_id' => $selectedKelasObj->akademik_master_tahun_ajaran_id,
                    'kelas_id' => $selectedKelasObj->id,
                    'periode' => PeriodeTp::PERIODE_MID,
                ], $request->user()),
                'semester' => $this->finalisasiService->getStatusPayloadForComponent([
                    'jenis_komponen' => PenilaianFinalisasi::JENIS_T2Q,
                    'tahun_ajaran_id' => $selectedKelasObj->akademik_master_tahun_ajaran_id,
                    'kelas_id' => $selectedKelasObj->id,
                    'periode' => PeriodeTp::PERIODE_SEMESTER,
                ], $request->user()),
            ];
        }

        return view('admin.dataMaster.anggotaT2q.index', [
            'anggotaT2q' => $this->service->getAll($tahunAjaranId, $karyawanNipy, $tingkat, $kelasId),
            'selectedTahunAjaranId' => $tahunAjaranId,
            'selectedKaryawanNipy' => $karyawanNipy,
            'selectedTingkat' => $tingkat,
            'selectedKelasId' => $kelasId,
            't2qFinalisasi' => $t2qFinalisasi,
            ...$this->service->getFilterOptions(),
        ]);
    }

    /**
     * Menampilkan form tambah anggota T2Q baru.
     */
    public function create(Request $request): View
    {
        $oldTahunAjaranId = $request->old('tahun_ajaran_id');
        $targetTahunAjaranId = $request->integer('tahun_ajaran_id')
            ?: (is_numeric($oldTahunAjaranId) ? (int) $oldTahunAjaranId : null);

        return view('admin.dataMaster.anggotaT2q.create', [
            ...$this->service->getCreateFormOptions($targetTahunAjaranId),
            'preselectedKaryawanNipy' => trim($request->string('karyawan_nipy')->toString()) ?: null,
            'preselectedTingkat' => AnggotaT2q::normalizeTingkat($request->string('tingkat')->toString()) ?: null,
            'preselectedPendaftaran' => $request->string('pendaftaran')->toString() ?: null,
            'preselectedKeterangan' => $request->string('keterangan')->toString() ?: null,
        ]);
    }

    /**
     * Menyimpan anggota T2Q baru ke database.
     */
    public function store(AnggotaT2qRequest $request): RedirectResponse
    {
        $created = $this->service->store($request->validated());
        $jumlah = $created->count();

        return redirect()->route('anggota-t2q.index')
            ->with('success', "{$jumlah} Anggota T2Q berhasil disimpan.");
    }

    /**
     * Menampilkan form edit anggota T2Q.
     */
    public function edit(AnggotaT2q $anggotaT2q): View
    {
        $anggotaT2q->loadMissing([
            'anggotaKelas:id,siswa_nis,akademik_master_kelas_id',
            'anggotaKelas.kelas:id,akademik_master_tahun_ajaran_id,nama_kelas,jenjang_kelas,tingkatan_kelas',
            'guruT2q:nipy,nama_lengkap,gelar',
        ]);

        $kelas = $anggotaT2q->anggotaKelas?->kelas;
        $t2qMidIsFinal = $kelas !== null && $this->finalisasiService->isFinal(
            (int) $kelas->akademik_master_tahun_ajaran_id,
            PenilaianFinalisasi::JENIS_T2Q,
            (int) $kelas->id,
            null,
            null,
            PeriodeTp::PERIODE_MID
        );
        $t2qSemIsFinal = $kelas !== null && $this->finalisasiService->isFinal(
            (int) $kelas->akademik_master_tahun_ajaran_id,
            PenilaianFinalisasi::JENIS_T2Q,
            (int) $kelas->id,
            null,
            null,
            PeriodeTp::PERIODE_SEMESTER
        );

        return view('admin.dataMaster.anggotaT2q.edit', [
            'anggotaT2q' => $anggotaT2q,
            't2qMidIsFinal' => $t2qMidIsFinal,
            't2qSemIsFinal' => $t2qSemIsFinal,
            ...$this->service->getFormOptions($anggotaT2q),
        ]);
    }

    /**
     * Memperbarui data anggota T2Q di database.
     */
    public function update(
        AnggotaT2qRequest $request,
        AnggotaT2q $anggotaT2q
    ): RedirectResponse {
        $this->service->update($anggotaT2q, $request->validated());

        return redirect()->route('anggota-t2q.index')
            ->with('success', 'Data Anggota T2Q berhasil diperbarui.');
    }

    /**
     * Menghapus data anggota T2Q dari database.
     */
    public function destroy(AnggotaT2q $anggotaT2q): RedirectResponse
    {
        $this->service->delete($anggotaT2q);

        return redirect()->route('anggota-t2q.index')
            ->with('success', 'Data Anggota T2Q berhasil dihapus.');
    }
}
