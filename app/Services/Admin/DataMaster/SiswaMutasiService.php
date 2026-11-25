<?php

namespace App\Services\Admin\DataMaster;

use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\SiswaMutasi;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Penilaian\PenilaianFinalisasi;
use App\Models\User;
use App\Support\Sqid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class SiswaMutasiService
{
    private const array MUTASI_HEADER_KEYS = [
        'akademik_master_tahun_ajaran_id',
        'kelas_tujuan_id',
        'tanggal_efektif',
        'sekolah_asal_tujuan',
        'nomor_surat',
        'alasan',
    ];

    public function __construct(
        protected SiswaService $siswaService,
        protected AnggotaKelasService $anggotaKelasService
    ) {}

    /**
     * Menormalisasi input filter halaman indeks Mutasi & Kelulusan.
     *
     * @param  array<string, mixed>  $input
     * @return array{
     *     search: string,
     *     unit: string,
     *     tahun_ajaran_id: string,
     *     kategori: string,
     *     status_log: string,
     *     show_all: bool,
     *     hasActiveFilter: bool
     * }
     */
    public function normalizeFilters(array $input): array
    {
        $search = trim((string) ($input['search'] ?? ''));

        $unit = strtoupper(trim((string) ($input['unit'] ?? '')));
        if ($unit !== '' && ! preg_match('/^[A-Z0-9 _-]{1,20}$/', $unit)) {
            $unit = '';
        }

        $decodedTahunAjaran = $this->decodeIntegerValue($input['tahun_ajaran_id'] ?? null, TahunAjaran::class);
        $tahunAjaranId = $decodedTahunAjaran !== null ? (string) $decodedTahunAjaran : '';

        $kategori = trim((string) ($input['kategori'] ?? ''));
        if (! array_key_exists($kategori, SiswaMutasi::KATEGORI_OPTIONS)) {
            $kategori = '';
        }

        $statusLog = trim((string) ($input['status_log'] ?? 'aktif'));
        if (! in_array($statusLog, ['aktif', 'dibatalkan', 'all'], true)) {
            $statusLog = 'aktif';
        }

        $showAll = filter_var($input['show_all'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $hasActiveFilter = $search !== ''
            || $unit !== ''
            || $tahunAjaranId !== ''
            || $kategori !== ''
            || $statusLog !== 'aktif'
            || $showAll;

        return [
            'search' => $search,
            'unit' => $unit,
            'tahun_ajaran_id' => $tahunAjaranId,
            'kategori' => $kategori,
            'status_log' => $statusLog,
            'show_all' => $showAll,
            'hasActiveFilter' => $hasActiveFilter,
        ];
    }

    /**
     * Menghitung statistik mutasi & kelulusan dalam 1 query agregat SQL tanpa N+1.
     *
     * @param  array<string, mixed>  $filters
     * @return array{
     *     total_aktif: int,
     *     total_lulus: int,
     *     total_pindah: int,
     *     total_keluar: int,
     *     total_nonaktif: int,
     *     total_masuk: int,
     *     total_dibatalkan: int,
     *     by_unit: array<string, int>
     * }
     */
    public function getStatistics(array $filters = []): array
    {
        $empty = [
            'total_aktif' => 0,
            'total_lulus' => 0,
            'total_pindah' => 0,
            'total_keluar' => 0,
            'total_nonaktif' => 0,
            'total_masuk' => 0,
            'total_dibatalkan' => 0,
            'by_unit' => [],
        ];

        if (! Schema::hasTable('akademik_siswa_mutasi')) {
            return $empty;
        }

        $query = DB::table('akademik_siswa_mutasi')
            ->selectRaw("
                COALESCE(NULLIF(TRIM(unit), ''), 'Lainnya') AS unit_key,
                SUM(CASE WHEN dibatalkan_at IS NULL THEN 1 ELSE 0 END) AS total_aktif,
                SUM(CASE WHEN dibatalkan_at IS NULL AND kategori = ? THEN 1 ELSE 0 END) AS total_lulus,
                SUM(CASE WHEN dibatalkan_at IS NULL AND kategori = ? THEN 1 ELSE 0 END) AS total_pindah,
                SUM(CASE WHEN dibatalkan_at IS NULL AND kategori = ? THEN 1 ELSE 0 END) AS total_keluar,
                SUM(CASE WHEN dibatalkan_at IS NULL AND kategori = ? THEN 1 ELSE 0 END) AS total_nonaktif,
                SUM(CASE WHEN dibatalkan_at IS NULL AND kategori = ? THEN 1 ELSE 0 END) AS total_masuk,
                SUM(CASE WHEN dibatalkan_at IS NOT NULL THEN 1 ELSE 0 END) AS total_dibatalkan
            ", [
                SiswaMutasi::KATEGORI_LULUS,
                SiswaMutasi::KATEGORI_PINDAH,
                SiswaMutasi::KATEGORI_KELUAR,
                SiswaMutasi::KATEGORI_NONAKTIF,
                SiswaMutasi::KATEGORI_MASUK,
            ]);

        $tahunAjaranId = trim((string) ($filters['tahun_ajaran_id'] ?? ''));
        if ($tahunAjaranId !== '' && ctype_digit($tahunAjaranId)) {
            $query->where('akademik_master_tahun_ajaran_id', (int) $tahunAjaranId);
        }

        $rows = $query->groupBy('unit_key')->get();

        $byUnit = [];
        foreach ($rows as $row) {
            $empty['total_aktif'] += (int) ($row->total_aktif ?? 0);
            $empty['total_lulus'] += (int) ($row->total_lulus ?? 0);
            $empty['total_pindah'] += (int) ($row->total_pindah ?? 0);
            $empty['total_keluar'] += (int) ($row->total_keluar ?? 0);
            $empty['total_nonaktif'] += (int) ($row->total_nonaktif ?? 0);
            $empty['total_masuk'] += (int) ($row->total_masuk ?? 0);
            $empty['total_dibatalkan'] += (int) ($row->total_dibatalkan ?? 0);

            $aktifUnit = (int) ($row->total_aktif ?? 0);
            if ($aktifUnit > 0) {
                $unitName = (string) ($row->unit_key ?? 'Lainnya');
                $byUnit[$unitName] = ($byUnit[$unitName] ?? 0) + $aktifUnit;
            }
        }

        uksort($byUnit, static function (string $a, string $b): int {
            if ($a === 'Lainnya') {
                return 1;
            }
            if ($b === 'Lainnya') {
                return -1;
            }

            return Kelas::compareJenjangOrder($a, $b);
        });

        $empty['by_unit'] = $byUnit;

        return $empty;
    }

    /**
     * Mengambil opsi filter Tahun Ajaran dan Unit jenjang tanpa N+1.
     *
     * @return array{
     *     tahunAjaranOptions: Collection<int, TahunAjaran>,
     *     unitOptions: list<string>
     * }
     */
    public function getFilterOptions(): array
    {
        $tahunAjaranOptions = TahunAjaran::query()
            ->select(['id', 'nama_tahun_ajaran', 'semester', 'is_active'])
            ->orderByDesc('is_active')
            ->orderByDesc('nama_tahun_ajaran')
            ->orderByDesc('semester')
            ->get();

        $unitList = Kelas::query()
            ->whereNotNull('jenjang_kelas')
            ->where('jenjang_kelas', '!=', '')
            ->distinct()
            ->pluck('jenjang_kelas')
            ->map(static fn (mixed $value): string => strtoupper(trim((string) $value)))
            ->filter(static fn (string $value): bool => $value !== '')
            ->unique()
            ->values()
            ->all();

        usort($unitList, static fn (string $a, string $b): int => Kelas::compareJenjangOrder($a, $b));

        return [
            'tahunAjaranOptions' => $tahunAjaranOptions,
            'unitOptions' => $unitList,
        ];
    }

    /**
     * Mengambil seluruh data untuk halaman index Mutasi & Kelulusan dengan pola Filter-First.
     *
     * @param  array<string, mixed>  $input
     * @return array{
     *     filters: array{
     *         search: string,
     *         unit: string,
     *         tahun_ajaran_id: string,
     *         kategori: string,
     *         status_log: string,
     *         show_all: bool,
     *         hasActiveFilter: bool
     *     },
     *     hasActiveFilter: bool,
     *     mutasiList: Collection<int, SiswaMutasi>,
     *     statistics: array{
     *         total_aktif: int,
     *         total_lulus: int,
     *         total_pindah: int,
     *         total_keluar: int,
     *         total_nonaktif: int,
     *         total_masuk: int,
     *         total_dibatalkan: int,
     *         by_unit: array<string, int>
     *     },
     *     tahunAjaranOptions: Collection<int, TahunAjaran>,
     *     unitOptions: list<string>,
     *     kategoriOptions: array<string, string>
     * }
     */
    public function getIndexData(array $input): array
    {
        $filters = $this->normalizeFilters($input);
        $options = $this->getFilterOptions();
        $statistics = $this->getStatistics($filters);

        $mutasiList = new Collection;

        if ($filters['hasActiveFilter'] && Schema::hasTable('akademik_siswa_mutasi')) {
            $mutasiList = $this->buildFilteredQuery($filters)
                ->terbaru()
                ->get();
        }

        return [
            'filters' => $filters,
            'hasActiveFilter' => $filters['hasActiveFilter'],
            'mutasiList' => $mutasiList,
            'statistics' => $statistics,
            'tahunAjaranOptions' => $options['tahunAjaranOptions'],
            'unitOptions' => $options['unitOptions'],
            'kategoriOptions' => SiswaMutasi::KATEGORI_OPTIONS,
        ];
    }

    /**
     * Menyiapkan data form Kelulusan Massal per kelas beserta guardrail kesiapan rapor SD.
     *
     * @param  array<string, mixed>  $input
     * @return array{
     *     tahunAjaranOptions: Collection<int, TahunAjaran>,
     *     selectedTahunAjaranId: int|null,
     *     kelasOptions: Collection<int, Kelas>,
     *     selectedKelas: Kelas|null,
     *     candidates: Collection<int, Siswa>,
     *     finalisasiSummary: array{is_sd: bool, mapel_final_count: int}
     * }
     */
    public function getKelulusanFormData(array $input = []): array
    {
        $tahunAjaranOptions = TahunAjaran::query()
            ->select(['id', 'nama_tahun_ajaran', 'semester', 'is_active'])
            ->orderByDesc('is_active')
            ->orderByDesc('nama_tahun_ajaran')
            ->orderByDesc('semester')
            ->get();

        $requestedTaId = $this->decodeIntegerValue($input['tahun_ajaran_id'] ?? null, TahunAjaran::class);
        $activeTa = $tahunAjaranOptions->firstWhere('is_active', true) ?? $tahunAjaranOptions->first();
        $selectedTahunAjaranId = $requestedTaId !== null && $tahunAjaranOptions->contains('id', $requestedTaId)
            ? $requestedTaId
            : ($activeTa !== null ? (int) $activeTa->id : null);

        $kelasOptions = new Collection;
        if ($selectedTahunAjaranId !== null) {
            $kelasOptions = Kelas::query()
                ->select(['id', 'akademik_master_tahun_ajaran_id', 'nama_kelas', 'jenjang_kelas', 'tingkatan_kelas'])
                ->where('akademik_master_tahun_ajaran_id', $selectedTahunAjaranId)
                ->orderByDesc('tingkatan_kelas')
                ->orderBy('jenjang_kelas')
                ->orderBy('nama_kelas')
                ->get();
        }

        $requestedKelasId = $this->decodeIntegerValue($input['kelas_id'] ?? null, Kelas::class);
        /** @var Kelas|null $selectedKelas */
        $selectedKelas = $requestedKelasId !== null
            ? $kelasOptions->firstWhere('id', $requestedKelasId)
            : null;

        $candidates = new Collection;
        $finalisasiSummary = [
            'is_sd' => false,
            'mapel_final_count' => 0,
        ];

        if ($selectedKelas !== null) {
            $kelasId = (int) $selectedKelas->id;

            $candidates = Siswa::query()
                ->select(['nis', 'akademik_master_kelas_id', 'nama_lengkap', 'jenis_kelamin', 'nisn', 'status'])
                ->active()
                ->where(function (Builder $query) use ($kelasId): void {
                    $query->where('akademik_master_kelas_id', $kelasId)
                        ->orWhereHas('anggotaKelas', static function (Builder $akQuery) use ($kelasId): void {
                            $akQuery->where('akademik_master_kelas_id', $kelasId);
                        });
                })
                ->with(['kelas:id,nama_kelas,jenjang_kelas,tingkatan_kelas'])
                ->orderBy('nama_lengkap')
                ->get();

            $isSd = strtoupper(trim((string) $selectedKelas->jenjang_kelas)) === 'SD';
            $finalisasiSummary['is_sd'] = $isSd;

            if ($isSd && $selectedTahunAjaranId !== null && Schema::hasTable('akademik_penilaian_finalisasi')) {
                $finalisasiSummary['mapel_final_count'] = PenilaianFinalisasi::query()
                    ->where('akademik_master_tahun_ajaran_id', $selectedTahunAjaranId)
                    ->where('akademik_master_kelas_id', $kelasId)
                    ->where('jenis_komponen', PenilaianFinalisasi::JENIS_MAPEL)
                    ->where('status', PenilaianFinalisasi::STATUS_FINAL)
                    ->count();
            }
        }

        return [
            'tahunAjaranOptions' => $tahunAjaranOptions,
            'selectedTahunAjaranId' => $selectedTahunAjaranId,
            'kelasOptions' => $kelasOptions,
            'selectedKelas' => $selectedKelas,
            'candidates' => $candidates,
            'finalisasiSummary' => $finalisasiSummary,
        ];
    }

    /**
     * Menyiapkan data form Mutasi Keluar (Pindah, Keluar, Nonaktif) secara hemat query.
     *
     * @return array{
     *     tahunAjaranOptions: Collection<int, TahunAjaran>,
     *     activeTahunAjaranId: int|null,
     *     kelasOptions: Collection<int, Kelas>,
     *     kategoriOptions: array<string, string>,
     *     activeStudents: Collection<int, Siswa>
     * }
     */
    public function getMutasiKeluarFormOptions(): array
    {
        $tahunAjaranOptions = TahunAjaran::query()
            ->select(['id', 'nama_tahun_ajaran', 'semester', 'is_active'])
            ->orderByDesc('is_active')
            ->orderByDesc('nama_tahun_ajaran')
            ->orderByDesc('semester')
            ->get();

        $activeTa = $tahunAjaranOptions->firstWhere('is_active', true) ?? $tahunAjaranOptions->first();

        $kelasOptions = Kelas::query()
            ->select(['id', 'akademik_master_tahun_ajaran_id', 'nama_kelas', 'jenjang_kelas', 'tingkatan_kelas'])
            ->with(['tahunAjaran:id,nama_tahun_ajaran,semester,is_active'])
            ->orderBy('jenjang_kelas')
            ->orderBy('tingkatan_kelas')
            ->orderBy('nama_kelas')
            ->get();

        $activeStudents = Siswa::query()
            ->select(['nis', 'akademik_master_kelas_id', 'nama_lengkap', 'jenis_kelamin', 'status'])
            ->active()
            ->with(['kelas:id,nama_kelas,jenjang_kelas,akademik_master_tahun_ajaran_id'])
            ->orderBy('nama_lengkap')
            ->get();

        return [
            'tahunAjaranOptions' => $tahunAjaranOptions,
            'activeTahunAjaranId' => $activeTa !== null ? (int) $activeTa->id : null,
            'kelasOptions' => $kelasOptions,
            'kategoriOptions' => SiswaMutasi::KATEGORI_KELUAR_OPTIONS,
            'activeStudents' => $activeStudents,
        ];
    }

    /**
     * Menyiapkan seluruh opsi dropdown form Mutasi Masuk (mewarisi opsi SiswaService + Tahun Ajaran).
     *
     * @return array<string, mixed>
     */
    public function getMutasiMasukFormOptions(): array
    {
        $tahunAjaranOptions = TahunAjaran::query()
            ->select(['id', 'nama_tahun_ajaran', 'semester', 'is_active'])
            ->orderByDesc('is_active')
            ->orderByDesc('nama_tahun_ajaran')
            ->orderByDesc('semester')
            ->get();

        $activeTa = $tahunAjaranOptions->firstWhere('is_active', true) ?? $tahunAjaranOptions->first();

        return array_merge($this->siswaService->getFormOptions(), [
            'tahunAjaranOptions' => $tahunAjaranOptions,
            'activeTahunAjaranId' => $activeTa !== null ? (int) $activeTa->id : null,
        ]);
    }

    /**
     * Memproses Kelulusan Massal siswa dalam satu transaksi atomik.
     *
     * @param  array<string, mixed>  $data
     */
    public function prosesKelulusanMassal(array $data, ?User $actor = null): int
    {
        $tahunAjaranId = (int) $data['akademik_master_tahun_ajaran_id'];
        $kelasId = (int) $data['kelas_id'];

        $kelas = Kelas::query()
            ->select(['id', 'akademik_master_tahun_ajaran_id', 'nama_kelas', 'jenjang_kelas'])
            ->find($kelasId);

        if ($kelas === null || (int) $kelas->akademik_master_tahun_ajaran_id !== $tahunAjaranId) {
            throw ValidationException::withMessages([
                'kelas_id' => ['Kelas yang dipilih tidak berada pada Tahun Ajaran kelulusan tersebut.'],
            ]);
        }

        return $this->executeBatchStatusTransition(
            $data['siswa_nis'] ?? [],
            SiswaMutasi::KATEGORI_LULUS,
            [
                'akademik_master_tahun_ajaran_id' => $tahunAjaranId,
                'fallback_kelas' => $kelas,
                'tanggal_efektif' => $data['tanggal_efektif'],
                'nomor_surat' => $data['nomor_surat'] ?? null,
                'alasan' => $data['alasan'] ?? 'Kelulusan akhir jenjang kelas '.$kelas->nama_kelas,
                'nomor_ijazah_map' => is_array($data['nomor_ijazah'] ?? null) ? $data['nomor_ijazah'] : [],
            ],
            $actor
        );
    }

    /**
     * Memproses Mutasi Keluar (Pindah, Keluar, Nonaktif) untuk 1 atau banyak siswa sekaligus.
     *
     * @param  array<string, mixed>  $data
     */
    public function prosesMutasiKeluar(array $data, ?User $actor = null): int
    {
        $kategori = (string) ($data['kategori'] ?? '');
        if (! array_key_exists($kategori, SiswaMutasi::KATEGORI_KELUAR_OPTIONS)) {
            throw ValidationException::withMessages([
                'kategori' => ['Kategori mutasi keluar tidak valid.'],
            ]);
        }

        return $this->executeBatchStatusTransition(
            $data['siswa_nis'] ?? [],
            $kategori,
            [
                'akademik_master_tahun_ajaran_id' => (int) $data['akademik_master_tahun_ajaran_id'],
                'tanggal_efektif' => $data['tanggal_efektif'],
                'sekolah_asal_tujuan' => $data['sekolah_asal_tujuan'] ?? null,
                'nomor_surat' => $data['nomor_surat'] ?? null,
                'alasan' => $data['alasan'] ?? null,
            ],
            $actor
        );
    }

    /**
     * Mencatat Siswa Pindahan Baru (Mutasi Masuk) dengan mendelegasikan pembuatan biodata & penempatan kelas.
     *
     * @param  array<string, mixed>  $data
     */
    public function prosesMutasiMasuk(array $data, ?UploadedFile $avatarFile = null, ?User $actor = null): SiswaMutasi
    {
        $kelasTujuanId = ! empty($data['kelas_tujuan_id']) ? (int) $data['kelas_tujuan_id'] : null;

        return DB::transaction(function () use ($data, $avatarFile, $actor, $kelasTujuanId): SiswaMutasi {
            $siswaPayload = Arr::except($data, self::MUTASI_HEADER_KEYS);
            $siswaPayload['status'] = Siswa::STATUS_AKTIF;
            $siswaPayload['jenis_pendaftaran'] = $siswaPayload['jenis_pendaftaran'] ?: 'Pindahan';
            $siswaPayload['akademik_master_kelas_id'] = $kelasTujuanId;

            $siswa = $this->siswaService->store($siswaPayload, $avatarFile);

            $unit = null;
            if ($kelasTujuanId !== null) {
                $this->anggotaKelasService->store([
                    'siswa_nis' => [$siswa->nis],
                    'akademik_master_kelas_id' => $kelasTujuanId,
                    'pendaftaran' => 'Pindahan',
                ]);

                $kelasTujuan = Kelas::query()
                    ->select(['id', 'jenjang_kelas'])
                    ->find($kelasTujuanId);
                $unit = $this->normalizeUnitFromKelas($kelasTujuan);
            }

            return SiswaMutasi::create([
                'siswa_nis' => $siswa->nis,
                'akademik_master_tahun_ajaran_id' => (int) $data['akademik_master_tahun_ajaran_id'],
                'kelas_asal_id' => null,
                'kelas_tujuan_id' => $kelasTujuanId,
                'unit' => $unit,
                'kategori' => SiswaMutasi::KATEGORI_MASUK,
                'status_sebelum' => null,
                'status_sesudah' => Siswa::STATUS_AKTIF,
                'tanggal_efektif' => $data['tanggal_efektif'],
                'nomor_surat' => $data['nomor_surat'] ?? null,
                'nomor_ijazah' => null,
                'sekolah_asal_tujuan' => $data['sekolah_asal_tujuan'],
                'alasan' => $data['alasan'] ?? 'Siswa pindahan masuk.',
                'created_by_user_id' => $actor?->id,
                'created_by_name' => $actor?->name,
            ]);
        });
    }

    /**
     * Membatalkan transaksi mutasi/kelulusan secara aman (*soft reversal*) dan memulihkan status siswa.
     */
    public function batalkanMutasi(SiswaMutasi $mutasi, string $alasanBatal, ?User $actor = null): SiswaMutasi
    {
        return DB::transaction(function () use ($mutasi, $alasanBatal, $actor): SiswaMutasi {
            /** @var SiswaMutasi $lockedMutasi */
            $lockedMutasi = SiswaMutasi::query()
                ->whereKey($mutasi->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedMutasi->dibatalkan_at !== null) {
                throw ValidationException::withMessages([
                    'alasan_batal' => ['Catatan mutasi ini sudah pernah dibatalkan sebelumnya.'],
                ]);
            }

            /** @var Siswa $lockedSiswa */
            $lockedSiswa = Siswa::query()
                ->where('nis', $lockedMutasi->siswa_nis)
                ->lockForUpdate()
                ->firstOrFail();

            $hasNewerActiveEvent = SiswaMutasi::query()
                ->where('siswa_nis', $lockedSiswa->nis)
                ->aktif()
                ->whereKeyNot($lockedMutasi->getKey())
                ->where(function (Builder $query) use ($lockedMutasi): void {
                    $tanggal = $lockedMutasi->tanggal_efektif?->toDateString() ?? $lockedMutasi->getRawOriginal('tanggal_efektif');
                    $query->whereDate('tanggal_efektif', '>', $tanggal)
                        ->orWhere(function (Builder $sameDayQuery) use ($tanggal, $lockedMutasi): void {
                            $sameDayQuery->whereDate('tanggal_efektif', $tanggal)
                                ->where('id', '>', $lockedMutasi->getKey());
                        });
                })
                ->exists();

            if ($hasNewerActiveEvent) {
                throw ValidationException::withMessages([
                    'alasan_batal' => ['Terdapat transaksi mutasi/kelulusan yang lebih baru untuk siswa ini. Batalkan transaksi terbaru terlebih dahulu.'],
                ]);
            }

            $restoredStatus = $lockedMutasi->kategori === SiswaMutasi::KATEGORI_MASUK
                ? Siswa::STATUS_NONAKTIF
                : ($lockedMutasi->status_sebelum ?: Siswa::STATUS_AKTIF);

            $lockedSiswa->update([
                'status' => $restoredStatus,
            ]);

            $lockedMutasi->update([
                'dibatalkan_at' => now(),
                'dibatalkan_by_user_id' => $actor?->id,
                'dibatalkan_by_name' => $actor?->name,
                'alasan_batal' => trim($alasanBatal),
            ]);

            return $lockedMutasi;
        });
    }

    /**
     * Engine DRY terpusat untuk transisi status batch (Mutasi Keluar & Kelulusan Massal) dalam O(1) query.
     *
     * @param  list<string>  $nisList
     * @param  array<string, mixed>  $meta
     */
    protected function executeBatchStatusTransition(
        array $nisList,
        string $kategori,
        array $meta,
        ?User $actor = null
    ): int {
        $normalizedNisList = array_values(array_unique(array_filter(
            array_map(static fn (mixed $item): string => trim((string) $item), $nisList),
            static fn (string $nis): bool => $nis !== ''
        )));

        if ($normalizedNisList === []) {
            throw ValidationException::withMessages([
                'siswa_nis' => ['Pilih minimal satu siswa aktif terlebih dahulu.'],
            ]);
        }

        if ($kategori === SiswaMutasi::KATEGORI_MASUK || ! isset(SiswaMutasi::KATEGORI_TARGET_STATUS_MAP[$kategori])) {
            throw ValidationException::withMessages([
                'kategori' => ['Kategori transaksi status tidak valid.'],
            ]);
        }

        $targetStatus = SiswaMutasi::KATEGORI_TARGET_STATUS_MAP[$kategori];
        $nomorIjazahMap = is_array($meta['nomor_ijazah_map'] ?? null) ? $meta['nomor_ijazah_map'] : [];
        /** @var Kelas|null $fallbackKelas */
        $fallbackKelas = $meta['fallback_kelas'] ?? null;

        return DB::transaction(function () use (
            $normalizedNisList,
            $kategori,
            $targetStatus,
            $meta,
            $nomorIjazahMap,
            $fallbackKelas,
            $actor
        ): int {
            $students = Siswa::query()
                ->select(['nis', 'akademik_master_kelas_id', 'nama_lengkap', 'status'])
                ->whereIn('nis', $normalizedNisList)
                ->lockForUpdate()
                ->with(['kelas:id,nama_kelas,jenjang_kelas,akademik_master_tahun_ajaran_id'])
                ->get()
                ->keyBy('nis');

            if ($students->count() !== count($normalizedNisList)) {
                throw ValidationException::withMessages([
                    'siswa_nis' => ['Sebagian NIS siswa tidak ditemukan di database.'],
                ]);
            }

            $nonActiveLabels = [];
            foreach ($normalizedNisList as $nis) {
                /** @var Siswa $student */
                $student = $students->get($nis);
                if ($student->status !== Siswa::STATUS_AKTIF) {
                    $nonActiveLabels[] = $student->nama_lengkap.' ('.$student->nis.' - '.$student->status.')';
                }
            }

            if ($nonActiveLabels !== []) {
                throw ValidationException::withMessages([
                    'siswa_nis' => [
                        'Proses ditolak karena siswa berikut tidak berstatus Aktif: '.implode(', ', $nonActiveLabels).'.',
                    ],
                ]);
            }

            $now = now();
            $rows = [];
            foreach ($normalizedNisList as $nis) {
                /** @var Siswa $student */
                $student = $students->get($nis);
                $kelasAsal = $student->kelas ?? $fallbackKelas;

                $rows[] = [
                    'siswa_nis' => $nis,
                    'akademik_master_tahun_ajaran_id' => $meta['akademik_master_tahun_ajaran_id'] ?? null,
                    'kelas_asal_id' => $student->akademik_master_kelas_id ?? $fallbackKelas?->id,
                    'kelas_tujuan_id' => null,
                    'unit' => $this->normalizeUnitFromKelas($kelasAsal),
                    'kategori' => $kategori,
                    'status_sebelum' => $student->status,
                    'status_sesudah' => $targetStatus,
                    'tanggal_efektif' => $meta['tanggal_efektif'],
                    'nomor_surat' => $meta['nomor_surat'] ?? null,
                    'nomor_ijazah' => isset($nomorIjazahMap[$nis]) && trim((string) $nomorIjazahMap[$nis]) !== ''
                        ? trim((string) $nomorIjazahMap[$nis])
                        : null,
                    'sekolah_asal_tujuan' => $meta['sekolah_asal_tujuan'] ?? null,
                    'alasan' => $meta['alasan'] ?? null,
                    'created_by_user_id' => $actor?->id,
                    'created_by_name' => $actor?->name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            SiswaMutasi::insert($rows);

            Siswa::query()
                ->whereIn('nis', $normalizedNisList)
                ->update([
                    'status' => $targetStatus,
                    'updated_at' => $now,
                ]);

            return count($rows);
        });
    }

    /**
     * Membangun query ledger terfilter dengan eager load anti-N+1.
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<SiswaMutasi>
     */
    private function buildFilteredQuery(array $filters): Builder
    {
        $query = SiswaMutasi::query()
            ->select([
                'id',
                'siswa_nis',
                'akademik_master_tahun_ajaran_id',
                'kelas_asal_id',
                'kelas_tujuan_id',
                'unit',
                'kategori',
                'status_sebelum',
                'status_sesudah',
                'tanggal_efektif',
                'nomor_surat',
                'nomor_ijazah',
                'sekolah_asal_tujuan',
                'alasan',
                'dibatalkan_at',
                'dibatalkan_by_name',
                'alasan_batal',
                'created_by_name',
                'created_at',
            ])
            ->with(SiswaMutasi::INDEX_RELATIONS);

        if ($filters['status_log'] === 'aktif') {
            $query->aktif();
        } elseif ($filters['status_log'] === 'dibatalkan') {
            $query->dibatalkan();
        }

        if ($filters['unit'] !== '') {
            $query->where('unit', $filters['unit']);
        }

        if ($filters['tahun_ajaran_id'] !== '') {
            $query->where('akademik_master_tahun_ajaran_id', (int) $filters['tahun_ajaran_id']);
        }

        if ($filters['kategori'] !== '') {
            $query->where('kategori', $filters['kategori']);
        }

        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function (Builder $subQuery) use ($search): void {
                $subQuery->where('siswa_nis', 'like', "%{$search}%")
                    ->orWhere('nomor_surat', 'like', "%{$search}%")
                    ->orWhere('nomor_ijazah', 'like', "%{$search}%")
                    ->orWhere('sekolah_asal_tujuan', 'like', "%{$search}%")
                    ->orWhere('alasan', 'like', "%{$search}%")
                    ->orWhereHas('siswa', static function (Builder $siswaQuery) use ($search): void {
                        $siswaQuery->where('nama_lengkap', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    /**
     * Mendekode parameter ID integer baik dalam bentuk SQID maupun angka positif mentah.
     *
     * @param  class-string<Model>  $modelClass
     */
    private function decodeIntegerValue(mixed $rawValue, string $modelClass): ?int
    {
        if ($rawValue === null || Sqid::isSentinelValue($rawValue)) {
            return null;
        }

        $trimmed = trim((string) $rawValue);
        if ($trimmed === '') {
            return null;
        }

        $decoded = Sqid::decodeFor($modelClass, $trimmed);
        if ($decoded !== null) {
            return (int) $decoded;
        }

        if (ctype_digit($trimmed) && (int) $trimmed > 0) {
            return (int) $trimmed;
        }

        return null;
    }

    /**
     * Mengambil snapshot kode unit jenjang dari model Kelas secara konsisten.
     */
    private function normalizeUnitFromKelas(?Kelas $kelas): ?string
    {
        if ($kelas === null) {
            return null;
        }

        $unit = strtoupper(trim((string) $kelas->jenjang_kelas));

        return $unit !== '' ? $unit : null;
    }
}

