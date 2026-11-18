<?php

namespace App\Services\Admin\Penilaian;

use App\Models\Admin\DataMaster\AnggotaEkstrakurikuler;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\AnggotaT2q;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Sekolah;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Admin\Penilaian\Kkm;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Models\Admin\Penilaian\RekapMapel;
use App\Models\Admin\Presensi\PresensiSiswa;
use App\Services\Admin\DataMaster\AnggotaKelasService;
use App\Services\Admin\DataMaster\SekolahService;
use App\Services\Admin\DataMaster\TitimangsaService;
use App\Services\WaliKelas\WaliKelasScopeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class RaporPdfService
{
    /**
     * Kolom biodata lengkap siswa yang dimuat untuk PDF Identitas Peserta Didik.
     *
     * @var list<string>
     */
    private const array BIODATA_SISWA_COLUMNS = [
        'nis',
        'nama_lengkap',
        'jenis_kelamin',
        'nisn',
        'nik',
        'tempat_lahir',
        'tanggal_lahir',
        'agama',
        'anak_ke',
        'jumlah_saudara',
        'alamat',
        'rt',
        'rw',
        'desa',
        'kecamatan',
        'kabupaten',
        'provinsi',
        'kode_pos',
        'nomor_hp',
        'whatsapp',
        'nama_ayah',
        'nama_ibu',
        'nama_wali',
        'akademik_pelengkap_pekerjaan_ayah_id',
        'akademik_pelengkap_pekerjaan_ibu_id',
        'akademik_pelengkap_pekerjaan_wali_id',
        'avatar',
        'jenis_pendaftaran',
    ];

    public function __construct(
        protected ?SekolahService $sekolahService = null,
        protected ?TitimangsaService $titimangsaService = null,
        protected ?AnggotaKelasService $anggotaKelasService = null,
        protected ?WaliKelasScopeService $scopeService = null
    ) {
        $this->sekolahService ??= new SekolahService;
        $this->titimangsaService ??= new TitimangsaService;
        $this->anggotaKelasService ??= new AnggotaKelasService;
        $this->scopeService ??= new WaliKelasScopeService;
    }

    /**
     * Memuat data halaman manajemen cetak PDF rapor untuk Super Admin tanpa N+1.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function getAdminIndexData(array $input = []): array
    {
        $tahunAjaranOptions = TahunAjaran::query()
            ->select(['id', 'nama_tahun_ajaran', 'semester', 'is_active'])
            ->orderByDesc('is_active')
            ->orderByDesc('nama_tahun_ajaran')
            ->orderBy('semester')
            ->orderByDesc('id')
            ->get();

        $requestedTaId = $this->resolvePositiveInt($input['tahun_ajaran_id'] ?? null);
        $selectedTa = $requestedTaId !== null
            ? ($tahunAjaranOptions->firstWhere('id', $requestedTaId) ?? $tahunAjaranOptions->firstWhere('is_active', true) ?? $tahunAjaranOptions->first())
            : ($tahunAjaranOptions->firstWhere('is_active', true) ?? $tahunAjaranOptions->first());

        $periode = $this->normalizePeriode($input['periode'] ?? PeriodeTp::PERIODE_MID);

        $kelasOptions = $selectedTa !== null
            ? Kelas::query()
                ->select(['id', 'akademik_master_tahun_ajaran_id', 'karyawan_nipy', 'pendamping_nipy', 'tingkatan_kelas', 'nama_kelas', 'jenjang_kelas', 'romawi'])
                ->with(['tahunAjaran:id,nama_tahun_ajaran,semester,is_active', 'waliKelas:nipy,nama_lengkap,gelar', 'pendamping:nipy,nama_lengkap,gelar'])
                ->where('akademik_master_tahun_ajaran_id', $selectedTa->id)
                ->get()
                ->sort(static function (Kelas $a, Kelas $b): int {
                    $aSd = strtoupper(trim((string) $a->jenjang_kelas)) === Kkm::TARGET_UNIT ? 0 : 1;
                    $bSd = strtoupper(trim((string) $b->jenjang_kelas)) === Kkm::TARGET_UNIT ? 0 : 1;
                    if ($aSd !== $bSd) {
                        return $aSd <=> $bSd;
                    }

                    $jenjangCmp = Kelas::compareJenjangOrder((string) $a->jenjang_kelas, (string) $b->jenjang_kelas);

                    return $jenjangCmp !== 0
                        ? $jenjangCmp
                        : strnatcasecmp((string) $a->nama_kelas, (string) $b->nama_kelas);
                })
                ->values()
            : new EloquentCollection;

        $requestedKelasId = $this->resolvePositiveInt($input['kelas_id'] ?? null);
        $selectedKelas = $requestedKelasId !== null
            ? ($kelasOptions->firstWhere('id', $requestedKelasId) ?? $kelasOptions->first())
            : $kelasOptions->first();

        if (! $selectedKelas instanceof Kelas || $selectedTa === null) {
            return [
                'tahunAjaranOptions' => $tahunAjaranOptions,
                'kelasOptions' => $kelasOptions,
                'selectedTahunAjaranId' => $selectedTa?->id,
                'selectedTahunAjaran' => $selectedTa,
                'selectedKelas' => null,
                'periode' => $periode,
                'periodeOptions' => PeriodeTp::PERIODE_OPTIONS,
                'isSd' => false,
                'sekolah' => null,
                'titimangsa' => null,
                'students' => new EloquentCollection,
                'rekapCountByAnggota' => [],
                'ekskulCountByAnggota' => [],
                't2qCountByAnggota' => [],
                'presensiByAnggota' => [],
                'totalSdMapels' => 0,
            ];
        }

        $isSd = strtoupper(trim((string) $selectedKelas->jenjang_kelas)) === Kkm::TARGET_UNIT;
        $students = $this->scopeService->sortAnggotaByStudentName(
            $this->anggotaKelasService
                ->buildIndexQuery($selectedTa->id, $selectedKelas->id)
                ->with(['siswa:nis,nama_lengkap,nisn,jenis_kelamin,status'])
                ->get()
        );
        $anggotaIds = $students->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all();

        $totalSdMapels = $isSd
            ? MataPelajaran::query()->where('unit', Kkm::TARGET_UNIT)->where('status', true)->count()
            : 0;

        $rekapCountByAnggota = [];
        if ($isSd && $anggotaIds !== []) {
            $rekapCountByAnggota = RekapMapel::query()
                ->selectRaw('anggota_kelas_id, COUNT(*) as total')
                ->where('akademik_master_tahun_ajaran_id', $selectedTa->id)
                ->whereIn('anggota_kelas_id', $anggotaIds)
                ->where('periode', $periode)
                ->whereNotNull('nilai_akhir_rapor')
                ->groupBy('anggota_kelas_id')
                ->pluck('total', 'anggota_kelas_id')
                ->map(static fn ($c): int => (int) $c)
                ->all();
        }

        $ekskulCountByAnggota = $anggotaIds !== []
            ? AnggotaEkstrakurikuler::query()
                ->selectRaw('anggota_kelas_id, COUNT(*) as total')
                ->whereIn('anggota_kelas_id', $anggotaIds)
                ->groupBy('anggota_kelas_id')
                ->pluck('total', 'anggota_kelas_id')
                ->map(static fn ($c): int => (int) $c)
                ->all()
            : [];

        $t2qCountByAnggota = $anggotaIds !== []
            ? AnggotaT2q::query()
                ->selectRaw('anggota_kelas_id, COUNT(*) as total')
                ->whereIn('anggota_kelas_id', $anggotaIds)
                ->groupBy('anggota_kelas_id')
                ->pluck('total', 'anggota_kelas_id')
                ->map(static fn ($c): int => (int) $c)
                ->all()
            : [];

        return [
            'tahunAjaranOptions' => $tahunAjaranOptions,
            'kelasOptions' => $kelasOptions,
            'selectedTahunAjaranId' => $selectedTa->id,
            'selectedTahunAjaran' => $selectedTa,
            'selectedKelas' => $selectedKelas,
            'periode' => $periode,
            'periodeOptions' => PeriodeTp::PERIODE_OPTIONS,
            'isSd' => $isSd,
            'sekolah' => $this->sekolahService->resolveByJenjang((string) $selectedKelas->jenjang_kelas),
            'titimangsa' => $this->titimangsaService->resolveForKelas($selectedKelas, $periode),
            'students' => $students,
            'rekapCountByAnggota' => $rekapCountByAnggota,
            'ekskulCountByAnggota' => $ekskulCountByAnggota,
            't2qCountByAnggota' => $t2qCountByAnggota,
            'presensiByAnggota' => $this->buildPresensiSummaryByAnggota($anggotaIds),
            'totalSdMapels' => $totalSdMapels,
        ];
    }

    /**
     * Menyusun payload PDF Identitas Sekolah & Keterangan Diri Siswa (batch 1 kelas atau 1 siswa) tanpa N+1.
     *
     * @return array<string, mixed>
     */
    public function buildIdentitasPdfPayload(Kelas $kelas, ?AnggotaKelas $singleAnggota = null): array
    {
        $kelas->loadMissing([
            'tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
            'waliKelas:nipy,nama_lengkap,gelar',
        ]);

        if ($singleAnggota !== null) {
            if ((int) $singleAnggota->akademik_master_kelas_id !== (int) $kelas->id) {
                abort(404, 'Siswa tidak terdaftar pada kelas yang dipilih.');
            }

            $singleAnggota->loadMissing([
                'siswa:'.implode(',', self::BIODATA_SISWA_COLUMNS),
                'siswa.pekerjaanAyah:id,nama_pekerjaan',
                'siswa.pekerjaanIbu:id,nama_pekerjaan',
                'siswa.pekerjaanWali:id,nama_pekerjaan',
            ]);
            $anggotaList = new EloquentCollection([$singleAnggota]);
        } else {
            $rawAnggota = AnggotaKelas::query()
                ->select(['id', 'siswa_nis', 'akademik_master_kelas_id', 'pendaftaran'])
                ->with([
                    'siswa:'.implode(',', self::BIODATA_SISWA_COLUMNS),
                    'siswa.pekerjaanAyah:id,nama_pekerjaan',
                    'siswa.pekerjaanIbu:id,nama_pekerjaan',
                    'siswa.pekerjaanWali:id,nama_pekerjaan',
                ])
                ->where('akademik_master_kelas_id', $kelas->id)
                ->get();

            $anggotaList = $this->scopeService->sortAnggotaByStudentName($rawAnggota);
        }

        $sekolah = $this->sekolahService->resolveByJenjang((string) $kelas->jenjang_kelas);
        $titimangsa = $this->titimangsaService->resolveForKelas($kelas, PeriodeTp::PERIODE_SEMESTER)
            ?? $this->titimangsaService->resolveForKelas($kelas, PeriodeTp::PERIODE_MID);

        $entries = $anggotaList->map(function (AnggotaKelas $anggota): array {
            $siswa = $anggota->siswa;

            return [
                'anggota' => $anggota,
                'siswa' => $siswa,
                'avatarDataUri' => $siswa instanceof Siswa ? $this->resolveStudentAvatarDataUri($siswa) : null,
            ];
        })->values()->all();

        return [
            'kelas' => $kelas,
            'tahunAjaran' => $kelas->tahunAjaran,
            'sekolah' => $sekolah,
            'titimangsa' => $titimangsa,
            'logoDataUri' => $this->resolveLogoDataUri(),
            'entries' => $entries,
        ];
    }

    /**
     * Menyusun payload PDF Nilai Hasil Pembelajaran SD (batch 1 kelas atau 1 siswa) tanpa N+1.
     *
     * @return array<string, mixed>
     */
    public function buildNilaiPdfPayload(
        Kelas $kelas,
        string $periodeInput = PeriodeTp::PERIODE_MID,
        ?AnggotaKelas $singleAnggota = null
    ): array {
        if (strtoupper(trim((string) $kelas->jenjang_kelas)) !== Kkm::TARGET_UNIT) {
            abort(422, 'Cetak PDF Nilai Rapor saat ini tersedia khusus untuk jenjang SD.');
        }

        $periode = $this->normalizePeriode($periodeInput);
        $kelas->loadMissing([
            'tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
            'waliKelas:nipy,nama_lengkap,gelar',
            'pendamping:nipy,nama_lengkap,gelar',
        ]);

        if ($singleAnggota !== null) {
            if ((int) $singleAnggota->akademik_master_kelas_id !== (int) $kelas->id) {
                abort(404, 'Siswa tidak terdaftar pada kelas yang dipilih.');
            }

            $singleAnggota->loadMissing(['siswa:nis,nama_lengkap,nisn,jenis_kelamin']);
            $anggotaList = new EloquentCollection([$singleAnggota]);
        } else {
            $rawAnggota = AnggotaKelas::query()
                ->select(['id', 'siswa_nis', 'akademik_master_kelas_id', 'pendaftaran'])
                ->with(['siswa:nis,nama_lengkap,nisn,jenis_kelamin'])
                ->where('akademik_master_kelas_id', $kelas->id)
                ->get();

            $anggotaList = $this->scopeService->sortAnggotaByStudentName($rawAnggota);
        }

        $anggotaIds = $anggotaList->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all();

        $mapels = MataPelajaran::query()
            ->select(['id', 'akademik_pelajaran_kelompok_pelajaran_id', 'singkatan', 'nama_mata_pelajaran', 'sifat', 'urutan'])
            ->with(['kelompokPelajaran:id,kode,kelompok,urutan'])
            ->where('unit', Kkm::TARGET_UNIT)
            ->where('status', true)
            ->ordered()
            ->get();

        $rekapLookup = [];
        $ekskulByAnggota = collect();
        $t2qByAnggota = collect();

        if ($anggotaIds !== []) {
            $rekapRows = RekapMapel::query()
                ->select([
                    'id',
                    'akademik_pelajaran_mata_pelajaran_id',
                    'anggota_kelas_id',
                    'periode',
                    'rata_rata_tp',
                    'rata_rata_sumatif',
                    'nilai_ujian',
                    'nilai_akhir_rapor',
                    'nilai_kkm_snapshot',
                    'predikat_rapor',
                    'keterangan_predikat',
                    'tuntas_kkm',
                    'deskripsi_capaian',
                ])
                ->where('akademik_master_tahun_ajaran_id', $kelas->akademik_master_tahun_ajaran_id)
                ->whereIn('anggota_kelas_id', $anggotaIds)
                ->where('periode', $periode)
                ->get();

            foreach ($rekapRows as $rekap) {
                $rekapLookup["{$rekap->anggota_kelas_id}:{$rekap->akademik_pelajaran_mata_pelajaran_id}"] = $rekap;
            }

            $ekskulByAnggota = AnggotaEkstrakurikuler::query()
                ->select(['id', 'anggota_kelas_id', 'akademik_master_ekstrakuler_id', 'keterangan'])
                ->with(['ekstrakurikuler:id,nama_ekstrakurikuler'])
                ->whereIn('anggota_kelas_id', $anggotaIds)
                ->get()
                ->groupBy('anggota_kelas_id');

            $t2qByAnggota = AnggotaT2q::query()
                ->select(['id', 'anggota_kelas_id', 'karyawan_nipy', 'tingkat', 'keterangan'])
                ->with(['guruT2q:nipy,nama_lengkap,gelar'])
                ->whereIn('anggota_kelas_id', $anggotaIds)
                ->get()
                ->keyBy('anggota_kelas_id');
        }

        $presensiByAnggota = $this->buildPresensiSummaryByAnggota($anggotaIds);
        $sekolah = $this->sekolahService->resolveByJenjang((string) $kelas->jenjang_kelas);
        $titimangsa = $this->titimangsaService->resolveForKelas($kelas, $periode);
        $fase = $this->resolveFase((string) $kelas->tingkatan_kelas);

        $entries = $anggotaList->map(function (AnggotaKelas $anggota) use (
            $mapels,
            $rekapLookup,
            $ekskulByAnggota,
            $t2qByAnggota,
            $presensiByAnggota
        ): array {
            $mapelRows = $mapels->map(static function (MataPelajaran $mapel) use ($anggota, $rekapLookup): array {
                return [
                    'mapel' => $mapel,
                    'rekap' => $rekapLookup["{$anggota->id}:{$mapel->id}"] ?? null,
                ];
            })->values()->all();

            return [
                'anggota' => $anggota,
                'siswa' => $anggota->siswa,
                'mapelRows' => $mapelRows,
                'ekskulRows' => $ekskulByAnggota->get($anggota->id, collect())->values()->all(),
                't2q' => $t2qByAnggota->get($anggota->id),
                'presensi' => $presensiByAnggota[$anggota->id] ?? ['sakit' => 0, 'izin' => 0, 'alpa' => 0],
            ];
        })->values()->all();

        return [
            'kelas' => $kelas,
            'tahunAjaran' => $kelas->tahunAjaran,
            'periode' => $periode,
            'periodeLabel' => PeriodeTp::PERIODE_OPTIONS[$periode] ?? strtoupper($periode),
            'fase' => $fase,
            'sekolah' => $sekolah,
            'titimangsa' => $titimangsa,
            'logoDataUri' => $this->resolveLogoDataUri(),
            'entries' => $entries,
        ];
    }

    /**
     * Merender PDF Identitas Sekolah & Siswa ke response stream DomPDF A4 Portrait.
     */
    public function streamIdentitasPdf(Kelas $kelas, ?AnggotaKelas $singleAnggota = null): Response
    {
        $payload = $this->buildIdentitasPdfPayload($kelas, $singleAnggota);
        $filename = $this->buildFilename('identitas-rapor', $kelas, null, $singleAnggota);

        return Pdf::loadView('pdf.rapor.identitas', $payload)
            ->setPaper('a4', 'portrait')
            ->stream($filename);
    }

    /**
     * Merender PDF Nilai Hasil Pembelajaran ke response stream DomPDF A4 Portrait.
     */
    public function streamNilaiPdf(
        Kelas $kelas,
        string $periode = PeriodeTp::PERIODE_MID,
        ?AnggotaKelas $singleAnggota = null
    ): Response {
        $payload = $this->buildNilaiPdfPayload($kelas, $periode, $singleAnggota);
        $filename = $this->buildFilename('rapor-nilai', $kelas, $payload['periode'], $singleAnggota);

        return Pdf::loadView('pdf.rapor.nilai', $payload)
            ->setPaper('a4', 'portrait')
            ->stream($filename);
    }

    private function normalizePeriode(mixed $value): string
    {
        return strtolower(trim((string) $value)) === PeriodeTp::PERIODE_SEMESTER
            ? PeriodeTp::PERIODE_SEMESTER
            : PeriodeTp::PERIODE_MID;
    }

    private function resolveFase(string $tingkatanKelas): string
    {
        return match (trim($tingkatanKelas)) {
            '1', '2' => 'A',
            '3', '4' => 'B',
            '5', '6' => 'C',
            default => '-',
        };
    }

    /**
     * @param  list<int>  $anggotaIds
     * @return array<int, array{sakit: int, izin: int, alpa: int}>
     */
    private function buildPresensiSummaryByAnggota(array $anggotaIds): array
    {
        if ($anggotaIds === []) {
            return [];
        }

        $rows = PresensiSiswa::query()
            ->selectRaw('anggota_kelas_id, status, COUNT(*) as total')
            ->whereIn('anggota_kelas_id', $anggotaIds)
            ->whereIn('status', ['sakit', 'izin', 'alpa'])
            ->groupBy('anggota_kelas_id', 'status')
            ->get();

        $summary = [];
        foreach ($rows as $row) {
            $anggotaId = (int) $row->anggota_kelas_id;
            $status = strtolower(trim((string) $row->status));
            if (! isset($summary[$anggotaId])) {
                $summary[$anggotaId] = ['sakit' => 0, 'izin' => 0, 'alpa' => 0];
            }
            if (array_key_exists($status, $summary[$anggotaId])) {
                $summary[$anggotaId][$status] = (int) $row->total;
            }
        }

        return $summary;
    }

    private function buildFilename(
        string $prefix,
        Kelas $kelas,
        ?string $periode = null,
        ?AnggotaKelas $singleAnggota = null
    ): string {
        $parts = [$prefix];
        if ($periode !== null) {
            $parts[] = $periode;
        }
        $parts[] = Str::slug((string) $kelas->nama_kelas);
        if ($singleAnggota?->siswa instanceof Siswa) {
            $parts[] = Str::slug((string) $singleAnggota->siswa->nama_lengkap);
        } else {
            $parts[] = 'satu-kelas';
        }

        return implode('-', array_filter($parts)).'.pdf';
    }

    private function resolveLogoDataUri(): ?string
    {
        return $this->encodeImageToDataUri(public_path('storage/logo/samping.png'));
    }

    private function resolveStudentAvatarDataUri(Siswa $siswa): ?string
    {
        $avatar = ltrim(trim((string) ($siswa->avatar ?? '')), '/');
        if ($avatar === '') {
            return null;
        }

        return $this->encodeImageToDataUri(storage_path('app/public/'.$avatar));
    }

    private function encodeImageToDataUri(string $path): ?string
    {
        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        $binary = @file_get_contents($path);
        if ($binary === false || $binary === '') {
            return null;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        return 'data:'.$mime.';base64,'.base64_encode($binary);
    }

    private function resolvePositiveInt(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (is_string($value) && $value !== '' && ctype_digit($value) && (int) $value > 0) {
            return (int) $value;
        }

        return null;
    }
}
