<?php

namespace App\Services\Admin\Presensi;

use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Presensi\PresensiSiswa;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PresensiSiswaService
{
    /**
     * Mengambil data daftar & matriks presensi siswa per kelas tanpa query N+1.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getIndexData(array $filters = []): array
    {
        $tanggal = trim((string) ($filters['tanggal'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $kelasId = (int) ($filters['kelas_id'] ?? 0);
        $rawBulan = trim((string) ($filters['bulan'] ?? ''));
        $hasExplicitFilter = array_key_exists('has_explicit_filter', $filters)
            ? (bool) $filters['has_explicit_filter']
            : ($tanggal !== ''
                || $status !== ''
                || $kelasId > 0
                || array_key_exists('tahun_ajaran_id', $filters)
                || $rawBulan !== '');
        $bulan = $this->resolveMonth($rawBulan);

        $tahunAjaranOptions = $this->getTahunAjaranOptions();
        $allKelasOptions = $this->getKelasOptions();
        $tahunAjaranId = $this->resolveDefaultTahunAjaranId(
            $filters,
            $kelasId,
            $tahunAjaranOptions,
            $allKelasOptions
        );
        $scopedKelasOptions = $this->scopeKelasByTahunAjaran($allKelasOptions, $tahunAjaranId);

        $selectedKelas = $kelasId > 0
            ? $this->resolveSelectedKelas($kelasId, $allKelasOptions)
            : $scopedKelasOptions->first();

        $effectiveKelasId = $selectedKelas?->id ?? ($kelasId > 0 ? $kelasId : null);

        $matrix = $this->buildClassMatrix($selectedKelas, $bulan, $status);
        $matrixSummary = $matrix['summary'];

        return [
            'hasFilter' => true,
            'hasExplicitFilter' => $hasExplicitFilter,
            'summary' => [
                'total' => (int) ($matrixSummary['total_catatan'] ?? 0),
                'hadir' => (int) ($matrixSummary['hadir'] ?? 0),
                'terlambat' => (int) ($matrixSummary['terlambat'] ?? 0),
                'izin' => (int) ($matrixSummary['izin'] ?? 0),
                'sakit' => (int) ($matrixSummary['sakit'] ?? 0),
                'alpa' => (int) ($matrixSummary['alpa'] ?? 0),
            ],
            'matrix' => $matrix,
            'kelasOptions' => $allKelasOptions,
            'tahunAjaranOptions' => $tahunAjaranOptions,
            'statusOptions' => PresensiSiswa::STATUS_OPTIONS,
            'filters' => [
                'tanggal' => $tanggal,
                'status' => $status,
                'kelas_id' => $effectiveKelasId,
                'tahun_ajaran_id' => $tahunAjaranId > 0 ? $tahunAjaranId : null,
                'bulan' => $bulan,
            ],
        ];
    }

    /**
     * Mengambil data opsi form create/edit presensi siswa tanpa N+1.
     *
     * @return array<string, mixed>
     */
    public function getFormData(): array
    {
        $anggotaKelasOptions = AnggotaKelas::query()
            ->select(['id', 'siswa_nis', 'akademik_master_kelas_id', 'pendaftaran'])
            ->with([
                'siswa:nis,nama_lengkap,nisn',
                'kelas:id,akademik_master_tahun_ajaran_id,nama_kelas,jenjang_kelas,tingkatan_kelas',
                'kelas.tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
            ])
            ->orderByDesc('id')
            ->get();

        return [
            'anggotaKelasOptions' => $anggotaKelasOptions,
            'statusOptions' => PresensiSiswa::STATUS_OPTIONS,
        ];
    }

    /**
     * Memperbarui presensi siswa.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(PresensiSiswa $presensiSiswa, array $data): PresensiSiswa
    {
        $presensiSiswa->update($this->normalizePayload($data));

        return $presensiSiswa;
    }

    /**
     * Menghapus presensi siswa.
     */
    public function delete(PresensiSiswa $presensiSiswa): void
    {
        $presensiSiswa->delete();
    }

    /**
     * Menyiapkan data form input presensi massal per kelas dan tanggal (tanpa N+1).
     *
     * @return array<string, mixed>
     */
    public function getBulkData(?int $kelasId, string $tanggal): array
    {
        $normalizedDate = $this->resolveDate($tanggal);
        $kelasOptions = $this->getKelasOptions();
        $selectedKelas = $kelasId && $kelasId > 0 ? $kelasOptions->firstWhere('id', $kelasId) : null;

        $rows = [];
        $existingCount = 0;

        if ($selectedKelas) {
            $anggotaList = $this->getSortedAnggotaByKelas((int) $selectedKelas->id);
            $anggotaIds = $anggotaList->pluck('id')->all();

            $existingRecords = $anggotaIds !== []
                ? PresensiSiswa::query()
                    ->select(['id', 'anggota_kelas_id', 'tanggal', 'jam_masuk', 'status', 'keterlambatan_menit'])
                    ->whereIn('anggota_kelas_id', $anggotaIds)
                    ->whereDate('tanggal', $normalizedDate)
                    ->get()
                    ->keyBy('anggota_kelas_id')
                : collect();

            foreach ($anggotaList as $anggota) {
                $existing = $existingRecords->get($anggota->id);
                if ($existing) {
                    $existingCount++;
                }

                $rows[] = [
                    'anggota' => $anggota,
                    'existing' => $existing,
                ];
            }
        }

        return [
            'kelasOptions' => $kelasOptions,
            'selectedKelas' => $selectedKelas,
            'selectedKelasId' => $selectedKelas?->id ?? ($kelasId ?? 0),
            'tanggal' => $normalizedDate,
            'tanggalLabel' => Carbon::createFromFormat('Y-m-d', $normalizedDate)->translatedFormat('l, d F Y'),
            'rows' => $rows,
            'existingCount' => $existingCount,
            'statusOptions' => PresensiSiswa::STATUS_OPTIONS,
        ];
    }

    /**
     * Menyimpan presensi siswa secara massal dalam 1 transaksi (upsert).
     *
     * @param  array<string, mixed>  $data
     */
    public function storeBulk(array $data): int
    {
        $tanggal = $this->resolveDate((string) ($data['tanggal'] ?? ''));
        $now = now();
        $payloadRows = [];

        foreach ($data['items'] ?? [] as $item) {
            $normalized = $this->normalizePayload([
                'anggota_kelas_id' => $item['anggota_kelas_id'],
                'tanggal' => $tanggal,
                'jam_masuk' => $item['jam_masuk'] ?? null,
                'status' => $item['status'] ?? 'hadir',
                'keterlambatan_menit' => $item['keterlambatan_menit'] ?? 0,
            ]);

            $normalized['created_at'] = $now;
            $normalized['updated_at'] = $now;
            $payloadRows[] = $normalized;
        }

        if ($payloadRows === []) {
            return 0;
        }

        DB::transaction(function () use ($payloadRows): void {
            PresensiSiswa::upsert(
                $payloadRows,
                ['anggota_kelas_id', 'tanggal'],
                ['jam_masuk', 'status', 'keterlambatan_menit', 'updated_at']
            );
        });

        return count($payloadRows);
    }

    /**
     * Menghapus seluruh catatan presensi siswa berdasarkan kelas dan tanggal tertentu.
     */
    public function deleteBulkByClassAndDate(int $kelasId, string $tanggal): int
    {
        if ($kelasId <= 0) {
            return 0;
        }

        $normalizedDate = $this->resolveDate($tanggal);
        $anggotaIds = AnggotaKelas::query()
            ->where('akademik_master_kelas_id', $kelasId)
            ->pluck('id')
            ->all();

        if ($anggotaIds === []) {
            return 0;
        }

        return (int) DB::transaction(fn (): int => PresensiSiswa::query()
            ->whereIn('anggota_kelas_id', $anggotaIds)
            ->whereDate('tanggal', $normalizedDate)
            ->delete());
    }

    /**
     * Mengambil data rekapitulasi dan persentase kehadiran siswa (tanpa N+1).
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getRekapData(array $filters = []): array
    {
        $kelasId = (int) ($filters['kelas_id'] ?? 0);
        $rawBulan = trim((string) ($filters['bulan'] ?? ''));
        $includeWeekend = filter_var($filters['include_weekend'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $hasExplicitFilter = array_key_exists('has_explicit_filter', $filters)
            ? (bool) $filters['has_explicit_filter']
            : (array_key_exists('tahun_ajaran_id', $filters)
                || $kelasId > 0
                || $rawBulan !== ''
                || $includeWeekend);

        $period = $this->resolveRekapPeriod($rawBulan);
        $tahunAjaranOptions = $this->getTahunAjaranOptions();
        $allKelasOptions = $this->getKelasOptions();
        $tahunAjaranId = $this->resolveDefaultTahunAjaranId(
            $filters,
            $kelasId,
            $tahunAjaranOptions,
            $allKelasOptions
        );
        $scopedKelasOptions = $this->scopeKelasByTahunAjaran($allKelasOptions, $tahunAjaranId);

        $basePayload = [
            'hasFilter' => true,
            'hasExplicitFilter' => $hasExplicitFilter,
            'period' => $period,
            'kelasOptions' => $allKelasOptions,
            'tahunAjaranOptions' => $tahunAjaranOptions,
            'filters' => [
                'tahun_ajaran_id' => $tahunAjaranId > 0 ? $tahunAjaranId : null,
                'kelas_id' => $kelasId > 0 ? $kelasId : null,
                'bulan' => $period['bulan'],
                'include_weekend' => $includeWeekend,
            ],
        ];

        if ($kelasId > 0) {
            return array_merge(
                $basePayload,
                $this->buildClassStudentRekap($kelasId, $allKelasOptions, $period, $includeWeekend)
            );
        }

        return array_merge(
            $basePayload,
            $this->buildAllClassesRekap($scopedKelasOptions, $period, $includeWeekend)
        );
    }

    /**
     * Mengambil statistik presensi siswa tahunan berbasis Tahun Ajaran (per semester, bulan, jenjang, kelas, dan siswa) tanpa N+1.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getTahunanData(array $filters = []): array
    {
        $tahunAjaranId = (int) ($filters['tahun_ajaran_id'] ?? 0);
        $kelasId = (int) ($filters['kelas_id'] ?? 0);
        $jenjang = strtoupper(trim((string) ($filters['jenjang'] ?? '')));
        $rawNamaTahunAjaran = trim((string) ($filters['nama_tahun_ajaran'] ?? ''));
        $selectedSemester = strtolower(trim((string) ($filters['semester'] ?? '')));
        if ($selectedSemester === 'all') {
            $selectedSemester = '';
        }
        $includeWeekend = filter_var($filters['include_weekend'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $hasExplicitFilter = array_key_exists('has_explicit_filter', $filters)
            ? (bool) $filters['has_explicit_filter']
            : ($rawNamaTahunAjaran !== ''
                || $tahunAjaranId > 0
                || $selectedSemester !== ''
                || $jenjang !== ''
                || $kelasId > 0
                || $includeWeekend);

        $tahunAjaranOptions = $this->getTahunAjaranOptions();
        $activeTahunAjaran = $tahunAjaranOptions->firstWhere('is_active', true);
        $namaTahunAjaranOptions = $tahunAjaranOptions
            ->pluck('nama_tahun_ajaran')
            ->filter(fn ($value): bool => trim((string) $value) !== '')
            ->map(fn ($value): string => trim((string) $value))
            ->unique()
            ->values();

        $shortcutTahunAjaran = $tahunAjaranId > 0 ? $tahunAjaranOptions->firstWhere('id', $tahunAjaranId) : null;
        if ($shortcutTahunAjaran !== null) {
            if ($rawNamaTahunAjaran === '') {
                $rawNamaTahunAjaran = trim((string) $shortcutTahunAjaran->nama_tahun_ajaran);
            }
            if ($selectedSemester === '' && ! array_key_exists('semester', $filters)) {
                $selectedSemester = strtolower(trim((string) $shortcutTahunAjaran->semester));
            }
        }

        if ($rawNamaTahunAjaran === '' && $shortcutTahunAjaran === null && $kelasId > 0) {
            $directKelas = Kelas::query()
                ->select(['id', 'akademik_master_tahun_ajaran_id'])
                ->with('tahunAjaran:id,nama_tahun_ajaran,semester')
                ->find($kelasId);
            if ($directKelas?->tahunAjaran !== null) {
                $rawNamaTahunAjaran = trim((string) $directKelas->tahunAjaran->nama_tahun_ajaran);
            }
        }

        $selectedNamaTahunAjaran = $rawNamaTahunAjaran !== ''
            ? $rawNamaTahunAjaran
            : trim((string) ($activeTahunAjaran?->nama_tahun_ajaran ?? $namaTahunAjaranOptions->first() ?? ''));

        $matchingTahunAjaranRows = $selectedNamaTahunAjaran !== ''
            ? $tahunAjaranOptions->filter(
                fn (TahunAjaran $ta): bool => trim((string) $ta->nama_tahun_ajaran) === $selectedNamaTahunAjaran
            )->values()
            : collect();

        $semesterOptions = TahunAjaran::SEMESTER_OPTIONS;
        foreach ($tahunAjaranOptions as $taRow) {
            $code = strtolower(trim((string) $taRow->semester));
            if ($code !== '' && ! isset($semesterOptions[$code])) {
                $semesterOptions[$code] = 'Semester '.strtoupper($code);
            }
        }

        $yearTaIds = $matchingTahunAjaranRows
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        $allKelasOptions = $this->getKelasOptions();

        $yearScopedKelas = $selectedNamaTahunAjaran !== ''
            ? $allKelasOptions->filter(
                fn (Kelas $kelas): bool => in_array((int) $kelas->akademik_master_tahun_ajaran_id, $yearTaIds, true)
            )->values()
            : $allKelasOptions;

        $jenjangOptions = $allKelasOptions
            ->pluck('jenjang_kelas')
            ->filter(fn ($value): bool => trim((string) $value) !== '')
            ->map(fn ($value): string => strtoupper(trim((string) $value)))
            ->unique()
            ->sort(fn (string $a, string $b): int => Kelas::compareJenjangOrder($a, $b))
            ->values();

        $semesterScopedKelas = $selectedSemester !== ''
            ? $yearScopedKelas->filter(
                fn (Kelas $kelas): bool => strtolower(trim((string) ($kelas->tahunAjaran?->semester ?? ''))) === $selectedSemester
            )->values()
            : $yearScopedKelas;

        $filteredKelasOptions = $jenjang !== ''
            ? $semesterScopedKelas->filter(
                fn (Kelas $kelas): bool => strtoupper(trim((string) $kelas->jenjang_kelas)) === $jenjang
            )->values()
            : $semesterScopedKelas;

        $selectedKelas = $kelasId > 0
            ? $filteredKelasOptions->firstWhere('id', $kelasId)
            : null;

        $sharedPayload = [
            'hasFilter' => true,
            'hasExplicitFilter' => $hasExplicitFilter,
            'namaTahunAjaranOptions' => $namaTahunAjaranOptions,
            'activeTahunAjaran' => $activeTahunAjaran,
            'semesterOptions' => $semesterOptions,
            'tahunAjaranOptions' => $tahunAjaranOptions,
            'jenjangOptions' => $jenjangOptions,
            'kelasOptions' => $allKelasOptions,
            'filters' => [
                'nama_tahun_ajaran' => $selectedNamaTahunAjaran,
                'semester' => $selectedSemester,
                'jenjang' => $jenjang !== '' ? $jenjang : null,
                'kelas_id' => $selectedKelas?->id,
                'include_weekend' => $includeWeekend,
            ],
        ];

        $targetKelasOptions = $selectedKelas !== null
            ? collect([$selectedKelas])
            : $filteredKelasOptions;

        $targetKelasIds = $targetKelasOptions->pluck('id')->all();

        $studentCountsByKelas = $targetKelasIds !== []
            ? AnggotaKelas::query()
                ->select('akademik_master_kelas_id')
                ->selectRaw('COUNT(*) as total_siswa')
                ->whereIn('akademik_master_kelas_id', $targetKelasIds)
                ->groupBy('akademik_master_kelas_id')
                ->pluck('total_siswa', 'akademik_master_kelas_id')
            : collect();

        $anggotaList = $selectedKelas !== null
            ? $this->getSortedAnggotaByKelas((int) $selectedKelas->id)
            : collect();

        $records = $targetKelasIds !== []
            ? PresensiSiswa::query()
                ->join('akademik_master_anggota_kelas as ak', 'ak.id', '=', 'akademik_presensi_siswa.anggota_kelas_id')
                ->whereIn('ak.akademik_master_kelas_id', $targetKelasIds)
                ->select([
                    'akademik_presensi_siswa.id',
                    'akademik_presensi_siswa.anggota_kelas_id',
                    'akademik_presensi_siswa.tanggal',
                    'akademik_presensi_siswa.status',
                    'akademik_presensi_siswa.keterlambatan_menit',
                    'ak.akademik_master_kelas_id as kelas_id',
                ])
                ->orderBy('akademik_presensi_siswa.tanggal')
                ->get()
            : collect();

        return array_merge(
            $sharedPayload,
            $this->assembleAnnualReport(
                selectedNamaTahunAjaran: $selectedNamaTahunAjaran,
                selectedSemester: $selectedSemester,
                selectedKelas: $selectedKelas,
                targetKelasOptions: $targetKelasOptions,
                records: $records,
                studentCountsByKelas: $studentCountsByKelas,
                anggotaList: $anggotaList,
                includeWeekend: $includeWeekend,
                modeOverride: $selectedKelas !== null ? 'class_detail' : null,
            ),
        );
    }

    /**
     * Membangun matriks presensi bulanan per kelas (Senin-Jumat saja, tanpa N+1).
     *
     * @return array<string, mixed>
     */
    private function buildClassMatrix(?Kelas $kelas, string $bulan, string $statusFilter = ''): array
    {
        $monthDate = Carbon::createFromFormat('Y-m', $bulan)->startOfMonth();
        $monthEnd = (clone $monthDate)->endOfMonth();
        $todayString = now()->format('Y-m-d');

        $days = [];
        foreach (CarbonPeriod::create($monthDate, $monthEnd) as $date) {
            if ($date->isWeekend()) {
                continue;
            }

            $days[] = [
                'day' => (int) $date->format('j'),
                'label' => $date->format('d'),
                'day_name' => $date->translatedFormat('D'),
                'date' => $date->format('Y-m-d'),
                'is_weekend' => false,
                'is_today' => $date->format('Y-m-d') === $todayString,
            ];
        }

        $dailyTotals = [];
        foreach ($days as $dayInfo) {
            $dailyTotals[$dayInfo['day']] = 0;
        }

        $classSummary = array_merge(['siswa' => 0], $this->emptyAttendanceCounts());

        if (! $kelas) {
            return [
                'kelas' => null,
                'bulan' => $bulan,
                'bulan_label' => $monthDate->translatedFormat('F Y'),
                'days' => $days,
                'rows' => [],
                'daily_totals' => $dailyTotals,
                'summary' => $this->finalizeAttendanceCounts($classSummary),
            ];
        }

        $anggotaList = $this->getSortedAnggotaByKelas((int) $kelas->id);
        $anggotaIds = $anggotaList->pluck('id')->all();

        $records = $anggotaIds !== []
            ? PresensiSiswa::query()
                ->select(['id', 'anggota_kelas_id', 'tanggal', 'jam_masuk', 'status', 'keterlambatan_menit'])
                ->whereIn('anggota_kelas_id', $anggotaIds)
                ->whereBetween('tanggal', [$monthDate->format('Y-m-d'), $monthEnd->format('Y-m-d')])
                ->when($statusFilter !== '' && array_key_exists($statusFilter, PresensiSiswa::STATUS_OPTIONS), fn (Builder $q) => $q->where('status', $statusFilter))
                ->get()
            : collect();

        $recordsMap = [];
        foreach ($records as $record) {
            if ($record->tanggal?->isWeekend()) {
                continue;
            }

            $dayNumber = (int) $record->tanggal->format('j');
            $recordsMap[$record->anggota_kelas_id][$dayNumber] = $record;
        }

        $rows = [];
        foreach ($anggotaList as $anggota) {
            $studentRecords = $recordsMap[$anggota->id] ?? [];
            $counts = $this->emptyAttendanceCounts();

            foreach ($studentRecords as $dayNumber => $record) {
                $this->accumulateRecordIntoCounts($counts, $record);
                $this->accumulateRecordIntoCounts($classSummary, $record);

                if (in_array((string) $record->status, ['hadir', 'terlambat'], true) && isset($dailyTotals[$dayNumber])) {
                    $dailyTotals[$dayNumber]++;
                }
            }

            $rows[] = [
                'anggota' => $anggota,
                'cells' => $studentRecords,
                'counts' => $this->finalizeAttendanceCounts($counts),
            ];
        }

        $classSummary['siswa'] = count($rows);

        return [
            'kelas' => $kelas,
            'bulan' => $bulan,
            'bulan_label' => $monthDate->translatedFormat('F Y'),
            'days' => $days,
            'rows' => $rows,
            'daily_totals' => $dailyTotals,
            'summary' => $this->finalizeAttendanceCounts($classSummary),
        ];
    }

    /**
     * Membangun rekap & persentase per siswa pada kelas terpilih (tanpa N+1).
     *
     * @param  array<string, mixed>  $period
     * @return array<string, mixed>
     */
    private function buildClassStudentRekap(
        int $kelasId,
        Collection $kelasOptions,
        array $period,
        bool $includeWeekend
    ): array {
        $selectedKelas = $this->resolveSelectedKelas($kelasId, $kelasOptions);
        $summaryCounts = array_merge(['siswa' => 0, 'jumlah_kelas' => $selectedKelas ? 1 : 0], $this->emptyAttendanceCounts());

        if (! $selectedKelas) {
            return [
                'mode' => 'class_detail',
                'selectedKelas' => null,
                'studentRows' => [],
                'classRows' => [],
                'summary' => $this->finalizeAttendanceCounts($summaryCounts),
            ];
        }

        $anggotaList = $this->getSortedAnggotaByKelas((int) $selectedKelas->id);
        $anggotaIds = $anggotaList->pluck('id')->all();

        $recordsByAnggota = $anggotaIds !== []
            ? PresensiSiswa::query()
                ->select(['id', 'anggota_kelas_id', 'tanggal', 'status', 'keterlambatan_menit'])
                ->whereIn('anggota_kelas_id', $anggotaIds)
                ->whereBetween('tanggal', [$period['start_date'], $period['end_date']])
                ->get()
                ->groupBy('anggota_kelas_id')
            : collect();

        $studentRows = [];
        foreach ($anggotaList as $anggota) {
            $counts = $this->emptyAttendanceCounts();

            foreach ($recordsByAnggota->get($anggota->id, collect()) as $record) {
                if (! $includeWeekend && $record->tanggal?->isWeekend()) {
                    continue;
                }

                $this->accumulateRecordIntoCounts($counts, $record);
                $this->accumulateRecordIntoCounts($summaryCounts, $record);
            }

            $studentRows[] = [
                'anggota' => $anggota,
                'counts' => $this->finalizeAttendanceCounts($counts),
            ];
        }

        $summaryCounts['siswa'] = count($studentRows);

        return [
            'mode' => 'class_detail',
            'selectedKelas' => $selectedKelas,
            'studentRows' => $studentRows,
            'classRows' => [],
            'summary' => $this->finalizeAttendanceCounts($summaryCounts),
        ];
    }

    /**
     * Membangun rekap & persentase antar-kelas dalam 2 query (tanpa N+1).
     *
     * @param  array<string, mixed>  $period
     * @return array<string, mixed>
     */
    private function buildAllClassesRekap(
        Collection $kelasOptions,
        array $period,
        bool $includeWeekend
    ): array {
        $summaryCounts = array_merge([
            'siswa' => 0,
            'jumlah_kelas' => $kelasOptions->count(),
        ], $this->emptyAttendanceCounts());

        $kelasIds = $kelasOptions->pluck('id')->all();
        if ($kelasIds === []) {
            return [
                'mode' => 'all_classes',
                'selectedKelas' => null,
                'studentRows' => [],
                'classRows' => [],
                'summary' => $this->finalizeAttendanceCounts($summaryCounts),
            ];
        }

        $studentCountsByKelas = AnggotaKelas::query()
            ->select('akademik_master_kelas_id')
            ->selectRaw('COUNT(*) as total_siswa')
            ->whereIn('akademik_master_kelas_id', $kelasIds)
            ->groupBy('akademik_master_kelas_id')
            ->pluck('total_siswa', 'akademik_master_kelas_id');

        $recordsByKelas = PresensiSiswa::query()
            ->join('akademik_master_anggota_kelas as ak', 'ak.id', '=', 'akademik_presensi_siswa.anggota_kelas_id')
            ->whereIn('ak.akademik_master_kelas_id', $kelasIds)
            ->whereBetween('akademik_presensi_siswa.tanggal', [$period['start_date'], $period['end_date']])
            ->select([
                'akademik_presensi_siswa.id',
                'akademik_presensi_siswa.tanggal',
                'akademik_presensi_siswa.status',
                'akademik_presensi_siswa.keterlambatan_menit',
                'ak.akademik_master_kelas_id as kelas_id',
            ])
            ->get()
            ->groupBy('kelas_id');

        $classRows = [];
        foreach ($kelasOptions as $kelas) {
            $counts = $this->emptyAttendanceCounts();

            foreach ($recordsByKelas->get($kelas->id, collect()) as $record) {
                if (! $includeWeekend && $record->tanggal?->isWeekend()) {
                    continue;
                }

                $this->accumulateRecordIntoCounts($counts, $record);
                $this->accumulateRecordIntoCounts($summaryCounts, $record);
            }

            $siswaCount = (int) ($studentCountsByKelas[$kelas->id] ?? 0);
            $summaryCounts['siswa'] += $siswaCount;

            $classRows[] = [
                'kelas' => $kelas,
                'siswa_count' => $siswaCount,
                'counts' => $this->finalizeAttendanceCounts($counts),
            ];
        }

        return [
            'mode' => 'all_classes',
            'selectedKelas' => null,
            'studentRows' => [],
            'classRows' => $classRows,
            'summary' => $this->finalizeAttendanceCounts($summaryCounts),
        ];
    }

    /**
     * Struktur awal penghitungan status presensi.
     *
     * @return array<string, mixed>
     */
    private function emptyAttendanceCounts(): array
    {
        return [
            'hadir' => 0,
            'terlambat' => 0,
            'izin' => 0,
            'sakit' => 0,
            'alpa' => 0,
            'total_telat_menit' => 0,
            'total_catatan' => 0,
            'hadir_efektif' => 0,
            'persentase_hadir' => 0.0,
            'persentase_tepat_waktu' => 0.0,
            'persentase_terlambat' => 0.0,
            'persentase_alpa' => 0.0,
        ];
    }

    /**
     * Mengakumulasi 1 baris PresensiSiswa ke array hitung.
     *
     * @param  array<string, mixed>  $counts
     */
    private function accumulateRecordIntoCounts(array &$counts, PresensiSiswa $record): void
    {
        $recordStatus = (string) $record->status;
        if (isset($counts[$recordStatus])) {
            $counts[$recordStatus]++;
        }

        $counts['total_telat_menit'] += (int) $record->keterlambatan_menit;
        $counts['total_catatan']++;
    }

    /**
     * Menghitung persentase kehadiran, keterlambatan, dan predikat dari hasil akumulasi.
     *
     * @param  array<string, mixed>  $counts
     * @return array<string, mixed>
     */
    private function finalizeAttendanceCounts(array $counts): array
    {
        $totalCatatan = (int) ($counts['total_catatan'] ?? 0);
        $hadirEfektif = (int) ($counts['hadir'] ?? 0) + (int) ($counts['terlambat'] ?? 0);
        $counts['hadir_efektif'] = $hadirEfektif;

        if ($totalCatatan > 0) {
            $counts['persentase_hadir'] = round(($hadirEfektif / $totalCatatan) * 100, 1);
            $counts['persentase_tepat_waktu'] = round((((int) ($counts['hadir'] ?? 0)) / $totalCatatan) * 100, 1);
            $counts['persentase_terlambat'] = round((((int) ($counts['terlambat'] ?? 0)) / $totalCatatan) * 100, 1);
            $counts['persentase_alpa'] = round((((int) ($counts['alpa'] ?? 0)) / $totalCatatan) * 100, 1);
        }

        $persentase = (float) $counts['persentase_hadir'];
        if ($totalCatatan === 0) {
            $counts['predikat'] = ['label' => 'Belum Ada Data', 'class' => 'bg-slate-100 text-slate-600 border-slate-200'];
        } elseif ($persentase >= 95) {
            $counts['predikat'] = ['label' => 'Sangat Baik', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'];
        } elseif ($persentase >= 85) {
            $counts['predikat'] = ['label' => 'Baik', 'class' => 'bg-sky-50 text-sky-700 border-sky-200'];
        } elseif ($persentase >= 75) {
            $counts['predikat'] = ['label' => 'Perlu Perhatian', 'class' => 'bg-amber-50 text-amber-800 border-amber-200'];
        } else {
            $counts['predikat'] = ['label' => 'Kritis', 'class' => 'bg-rose-50 text-rose-700 border-rose-200'];
        }

        return $counts;
    }

    /**
     * Menyelesaikan periode bulan rekap.
     *
     * @return array<string, mixed>
     */
    private function resolveRekapPeriod(string $rawBulan): array
    {
        $bulan = $this->resolveMonth($rawBulan);
        $start = Carbon::createFromFormat('Y-m', $bulan)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        return [
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'bulan' => $bulan,
            'label' => $start->translatedFormat('F Y'),
            'weekdays_count' => $this->countWeekdaysBetween($start, $end),
        ];
    }

    private function parseDateOrNull(string $rawDate): ?Carbon
    {
        $trimmed = trim($rawDate);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $trimmed) !== 1) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $trimmed);
        } catch (\Throwable) {
            return null;
        }
    }

    private function countWeekdaysBetween(Carbon $start, Carbon $end): int
    {
        $count = 0;
        foreach (CarbonPeriod::create($start, $end) as $date) {
            if ($date->isWeekday()) {
                $count++;
            }
        }

        return $count;
    }

    private function resolveSelectedKelas(int $kelasId, Collection $kelasOptions): ?Kelas
    {
        return $kelasOptions->firstWhere('id', $kelasId) ?? Kelas::query()
            ->select(['id', 'akademik_master_tahun_ajaran_id', 'karyawan_nipy', 'nama_kelas', 'jenjang_kelas', 'tingkatan_kelas'])
            ->with(['tahunAjaran:id,nama_tahun_ajaran,semester,is_active', 'waliKelas:nipy,nama_lengkap,gelar'])
            ->find($kelasId);
    }

    /**
     * Mengambil daftar anggota kelas terurut nama siswa dengan eager loading minimal.
     */
    private function getSortedAnggotaByKelas(int $kelasId): Collection
    {
        return AnggotaKelas::query()
            ->select(['id', 'siswa_nis', 'akademik_master_kelas_id', 'pendaftaran'])
            ->with(['siswa:nis,nama_lengkap,nisn,jenis_kelamin'])
            ->where('akademik_master_kelas_id', $kelasId)
            ->get()
            ->sortBy(fn (AnggotaKelas $anggota) => mb_strtolower((string) ($anggota->siswa?->nama_lengkap ?? '')))
            ->values();
    }

    /**
     * Menormalkan format tanggal Y-m-d.
     */
    private function resolveDate(string $tanggal): string
    {
        return ($this->parseDateOrNull($tanggal) ?? now())->format('Y-m-d');
    }

    /**
     * Menormalkan format bulan Y-m.
     */
    private function resolveMonth(string $bulan): string
    {
        if (preg_match('/^\d{4}-\d{2}$/', trim($bulan)) === 1) {
            try {
                return Carbon::createFromFormat('Y-m', trim($bulan))->format('Y-m');
            } catch (\Throwable) {
                // Fallback ke bulan berjalan
            }
        }

        return now()->format('Y-m');
    }

    /**
     * Menormalkan payload dan menghitung keterlambatan otomatis terhadap batas maksimal jam masuk.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizePayload(array $data): array
    {
        $status = (string) ($data['status'] ?? 'hadir');

        if (! in_array($status, ['hadir', 'terlambat'], true)) {
            return [
                'anggota_kelas_id' => (int) $data['anggota_kelas_id'],
                'tanggal' => $data['tanggal'],
                'jam_masuk' => null,
                'status' => $status,
                'keterlambatan_menit' => 0,
            ];
        }

        $rawJamMasuk = $data['jam_masuk'] ?? null;
        $jamMasuk = is_string($rawJamMasuk) && trim($rawJamMasuk) !== ''
            ? trim($rawJamMasuk)
            : null;

        $autoLateMinutes = PresensiSiswa::calculateKeterlambatanMenit($jamMasuk);
        $inputLateMinutes = max(0, (int) ($data['keterlambatan_menit'] ?? 0));

        if ($autoLateMinutes > 0) {
            $status = 'terlambat';
            $keterlambatanMenit = $inputLateMinutes > 0 ? $inputLateMinutes : $autoLateMinutes;
        } else {
            $keterlambatanMenit = $status === 'terlambat' ? $inputLateMinutes : 0;
        }

        return [
            'anggota_kelas_id' => (int) $data['anggota_kelas_id'],
            'tanggal' => $data['tanggal'],
            'jam_masuk' => $jamMasuk,
            'status' => $status,
            'keterlambatan_menit' => $keterlambatanMenit,
        ];
    }

    /**
     * Mengambil opsi kelas dengan filter ID tahun ajaran tunggal maupun jamak.
     *
     * @param  int|list<int>  $tahunAjaranIds
     */
    private function getKelasOptions(int|array $tahunAjaranIds = 0): Collection
    {
        $ids = is_array($tahunAjaranIds)
            ? array_values(array_filter(array_map('intval', $tahunAjaranIds), fn (int $id): bool => $id > 0))
            : ($tahunAjaranIds > 0 ? [$tahunAjaranIds] : []);

        return Kelas::query()
            ->select(['id', 'akademik_master_tahun_ajaran_id', 'karyawan_nipy', 'nama_kelas', 'jenjang_kelas', 'tingkatan_kelas'])
            ->with([
                'tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
                'waliKelas:nipy,nama_lengkap,gelar',
            ])
            ->when($ids !== [], fn (Builder $query) => $query->whereIn('akademik_master_tahun_ajaran_id', $ids))
            ->orderBy('jenjang_kelas')
            ->orderBy('tingkatan_kelas')
            ->orderBy('nama_kelas')
            ->get();
    }

    private function getTahunAjaranOptions(): Collection
    {
        return TahunAjaran::query()
            ->select(['id', 'nama_tahun_ajaran', 'semester', 'is_active'])
            ->orderByDesc('is_active')
            ->orderByDesc('nama_tahun_ajaran')
            ->get();
    }

    /**
     * Menentukan ID tahun ajaran efektif: menghormati filter eksplisit ("Semua" = 0),
     * mencocokkan tahun ajaran milik kelas terpilih bila ada, atau memilih tahun ajaran aktif.
     *
     * @param  array<string, mixed>  $filters
     */
    private function resolveDefaultTahunAjaranId(
        array $filters,
        int $kelasId,
        Collection $tahunAjaranOptions,
        Collection $allKelasOptions
    ): int {
        $hasExplicitTahunAjaran = array_key_exists('has_explicit_tahun_ajaran', $filters)
            ? (bool) $filters['has_explicit_tahun_ajaran']
            : array_key_exists('tahun_ajaran_id', $filters);

        if ($hasExplicitTahunAjaran) {
            return max(0, (int) ($filters['tahun_ajaran_id'] ?? 0));
        }

        if ($kelasId > 0) {
            $directKelas = $allKelasOptions->firstWhere('id', $kelasId);
            if ($directKelas !== null && (int) $directKelas->akademik_master_tahun_ajaran_id > 0) {
                return (int) $directKelas->akademik_master_tahun_ajaran_id;
            }
        }

        $active = $tahunAjaranOptions->firstWhere('is_active', true) ?? $tahunAjaranOptions->first();

        return (int) ($active?->id ?? 0);
    }

    /**
     * Menyaring koleksi kelas berdasarkan ID tahun ajaran (> 0) tanpa mutasi koleksi sumber.
     */
    private function scopeKelasByTahunAjaran(Collection $allKelasOptions, int $tahunAjaranId): Collection
    {
        if ($tahunAjaranId <= 0) {
            return $allKelasOptions;
        }

        return $allKelasOptions
            ->filter(fn (Kelas $kelas): bool => (int) $kelas->akademik_master_tahun_ajaran_id === $tahunAjaranId)
            ->values();
    }

    /**
     * Menghasilkan daftar kunci bulan (Y-m) sesuai tahun ajaran dan semester terpilih.
     *
     * @return list<string>
     */
    private function resolveAcademicMonthKeys(string $namaTahunAjaran, string $semester = ''): array
    {
        $range = TahunAjaran::parseYearRange($namaTahunAjaran);
        if ($range === null) {
            return [];
        }

        [$startYear, $endYear] = $range;
        $normalizedSemester = strtolower(trim($semester));

        $months = [];
        if (in_array($normalizedSemester, ['1', 'mid 1'], true)) {
            for ($m = 7; $m <= 12; $m++) {
                $months[] = sprintf('%04d-%02d', $startYear, $m);
            }

            return $months;
        }

        if (in_array($normalizedSemester, ['2', 'mid 2'], true)) {
            for ($m = 1; $m <= 6; $m++) {
                $months[] = sprintf('%04d-%02d', $endYear, $m);
            }

            return $months;
        }

        for ($m = 7; $m <= 12; $m++) {
            $months[] = sprintf('%04d-%02d', $startYear, $m);
        }
        for ($m = 1; $m <= 6; $m++) {
            $months[] = sprintf('%04d-%02d', $endYear, $m);
        }

        return $months;
    }

    /**
     * Merakit statistik tahunan berbasis Tahun Ajaran (per semester, per bulan, per jenjang, per kelas, dan per siswa).
     *
     * @return array<string, mixed>
     */
    private function assembleAnnualReport(
        string $selectedNamaTahunAjaran,
        string $selectedSemester,
        ?Kelas $selectedKelas,
        Collection $targetKelasOptions,
        Collection $records,
        Collection $studentCountsByKelas,
        Collection $anggotaList,
        bool $includeWeekend,
        ?string $modeOverride = null,
    ): array {
        $mode = $modeOverride ?? ($selectedKelas ? 'class_detail' : 'all_classes');
        $summaryCounts = array_merge([
            'siswa' => 0,
            'jumlah_kelas' => $selectedKelas ? 1 : $targetKelasOptions->count(),
        ], $this->emptyAttendanceCounts());

        $monthBuckets = [];
        foreach ($this->resolveAcademicMonthKeys($selectedNamaTahunAjaran, $selectedSemester) as $ym) {
            $monthBuckets[$ym] = $this->buildEmptyAnnualMonthBucket($ym, $includeWeekend);
        }

        $classBuckets = [];
        $kelasToTaId = [];
        $semesterAccum = [];
        foreach ($targetKelasOptions as $kelas) {
            $siswaCount = (int) ($studentCountsByKelas[$kelas->id] ?? 0);
            $summaryCounts['siswa'] += $siswaCount;

            $taModel = $kelas->tahunAjaran;
            $taId = (int) ($taModel?->id ?? $kelas->akademik_master_tahun_ajaran_id ?? 0);
            $kelasToTaId[(int) $kelas->id] = $taId;

            if ($taId > 0 && ! isset($semesterAccum[$taId])) {
                $semCode = strtolower(trim((string) ($taModel?->semester ?? '')));
                $semesterAccum[$taId] = [
                    'tahun_ajaran_id' => $taId,
                    'semester' => $semCode,
                    'semester_label' => $taModel?->semester_label ?? (TahunAjaran::SEMESTER_OPTIONS[$semCode] ?? 'Semester'),
                    'nama_tahun_ajaran' => (string) ($taModel?->nama_tahun_ajaran ?? $selectedNamaTahunAjaran),
                    'kelas_count' => 0,
                    'siswa_count' => 0,
                    'counts' => $this->emptyAttendanceCounts(),
                ];
            }
            if ($taId > 0) {
                $semesterAccum[$taId]['kelas_count']++;
                $semesterAccum[$taId]['siswa_count'] += $siswaCount;
            }

            $classBuckets[(int) $kelas->id] = [
                'kelas' => $kelas,
                'siswa_count' => $siswaCount,
                'counts' => $this->emptyAttendanceCounts(),
            ];
        }

        $studentBuckets = [];
        if ($selectedKelas !== null) {
            $summaryCounts['siswa'] = $anggotaList->count();
            foreach ($anggotaList as $anggota) {
                $studentBuckets[(int) $anggota->id] = [
                    'anggota' => $anggota,
                    'counts' => $this->emptyAttendanceCounts(),
                ];
            }
        }

        foreach ($records as $record) {
            $tanggal = $record->tanggal instanceof Carbon
                ? $record->tanggal
                : Carbon::parse((string) $record->tanggal);

            if (! $includeWeekend && $tanggal->isWeekend()) {
                continue;
            }

            $this->accumulateRecordIntoCounts($summaryCounts, $record);

            $ym = $tanggal->format('Y-m');
            if (! isset($monthBuckets[$ym])) {
                $monthBuckets[$ym] = $this->buildEmptyAnnualMonthBucket($ym, $includeWeekend);
            }
            $this->accumulateRecordIntoCounts($monthBuckets[$ym]['counts'], $record);

            $kelasKey = (int) ($record->kelas_id ?? $selectedKelas?->id ?? 0);
            if (isset($classBuckets[$kelasKey])) {
                $this->accumulateRecordIntoCounts($classBuckets[$kelasKey]['counts'], $record);
            }

            $taKey = $kelasToTaId[$kelasKey] ?? 0;
            if ($taKey > 0 && isset($semesterAccum[$taKey])) {
                $this->accumulateRecordIntoCounts($semesterAccum[$taKey]['counts'], $record);
            }

            $anggotaKey = (int) $record->anggota_kelas_id;
            if ($selectedKelas !== null && isset($studentBuckets[$anggotaKey])) {
                $this->accumulateRecordIntoCounts($studentBuckets[$anggotaKey]['counts'], $record);
            }
        }

        ksort($monthBuckets);

        $monthlyRows = array_values(array_map(
            fn (array $bucket): array => $this->finalizeMonthlyBucket($bucket),
            $monthBuckets
        ));

        $semesterOrder = array_keys(TahunAjaran::SEMESTER_OPTIONS);
        uasort($semesterAccum, function (array $a, array $b) use ($semesterOrder): int {
            $idxA = array_search($a['semester'], $semesterOrder, true);
            $idxB = array_search($b['semester'], $semesterOrder, true);

            return ($idxA === false ? 999 : $idxA) <=> ($idxB === false ? 999 : $idxB);
        });
        $semesterRows = array_values(array_map(function (array $row): array {
            $row['counts'] = $this->finalizeAttendanceCounts($row['counts']);

            return $row;
        }, $semesterAccum));

        $classRows = [];
        $jenjangAccum = [];
        foreach ($classBuckets as $bucket) {
            $finalCounts = $this->finalizeAttendanceCounts($bucket['counts']);
            $classRows[] = [
                'kelas' => $bucket['kelas'],
                'siswa_count' => $bucket['siswa_count'],
                'counts' => $finalCounts,
            ];

            $jenjangKey = strtoupper(trim((string) $bucket['kelas']->jenjang_kelas));
            if ($jenjangKey === '') {
                $jenjangKey = 'LAINNYA';
            }

            if (! isset($jenjangAccum[$jenjangKey])) {
                $jenjangAccum[$jenjangKey] = [
                    'jenjang' => $jenjangKey,
                    'kelas_count' => 0,
                    'siswa_count' => 0,
                    'counts' => $this->emptyAttendanceCounts(),
                ];
            }

            $jenjangAccum[$jenjangKey]['kelas_count']++;
            $jenjangAccum[$jenjangKey]['siswa_count'] += $bucket['siswa_count'];
            $this->sumCountsInto($jenjangAccum[$jenjangKey]['counts'], $bucket['counts']);
        }

        uksort($jenjangAccum, fn (string $a, string $b): int => Kelas::compareJenjangOrder($a, $b));
        $jenjangRows = array_values(array_map(function (array $group): array {
            $group['counts'] = $this->finalizeAttendanceCounts($group['counts']);

            return $group;
        }, $jenjangAccum));

        $studentRows = [];
        foreach ($studentBuckets as $bucket) {
            $studentRows[] = [
                'anggota' => $bucket['anggota'],
                'counts' => $this->finalizeAttendanceCounts($bucket['counts']),
            ];
        }

        $finalSummary = $this->finalizeAttendanceCounts($summaryCounts);
        $totalHariSekolah = array_sum(array_column($monthlyRows, 'weekdays_count'));

        $semesterSuffix = $selectedSemester !== ''
            ? ' • '.(TahunAjaran::SEMESTER_OPTIONS[$selectedSemester] ?? 'Semester '.strtoupper($selectedSemester))
            : ' • Semua Semester';
        $periodLabel = $selectedNamaTahunAjaran !== ''
            ? "Tahun Ajaran {$selectedNamaTahunAjaran}{$semesterSuffix}"
            : 'Statistik Tahun Ajaran';

        return [
            'mode' => $mode,
            'selectedKelas' => $selectedKelas,
            'period' => [
                'nama_tahun_ajaran' => $selectedNamaTahunAjaran,
                'semester' => $selectedSemester,
                'label' => $periodLabel,
                'weekdays_count' => $totalHariSekolah,
            ],
            'summary' => $finalSummary,
            'highlights' => $this->buildAnnualInsights($monthlyRows, $finalSummary, $totalHariSekolah),
            'semesterRows' => $selectedKelas === null ? $semesterRows : [],
            'monthlyRows' => $monthlyRows,
            'jenjangRows' => $selectedKelas === null ? $jenjangRows : [],
            'classRows' => $selectedKelas === null ? $classRows : [],
            'studentRows' => $selectedKelas !== null ? $studentRows : [],
        ];
    }

    /**
     * Membuat struktur bucket kosong untuk 1 bulan pada laporan tahunan.
     *
     * @return array<string, mixed>
     */
    private function buildEmptyAnnualMonthBucket(string $ym, bool $includeWeekend): array
    {
        $start = Carbon::createFromFormat('Y-m', $ym)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        return [
            'bulan' => $ym,
            'label' => $start->translatedFormat('F Y'),
            'short_label' => $start->translatedFormat('M'),
            'weekdays_count' => $includeWeekend ? $start->daysInMonth : $this->countWeekdaysBetween($start, $end),
            'counts' => $this->emptyAttendanceCounts(),
        ];
    }

    /**
     * Menjumlahkan bucket hitung status ke akumulator target.
     *
     * @param  array<string, mixed>  $target
     * @param  array<string, mixed>  $source
     */
    private function sumCountsInto(array &$target, array $source): void
    {
        foreach (['hadir', 'terlambat', 'izin', 'sakit', 'alpa', 'total_telat_menit', 'total_catatan'] as $field) {
            $target[$field] += (int) ($source[$field] ?? 0);
        }
    }

    /**
     * Memfinalisasi angka persentase, predikat, dan proporsi bar status untuk satu bulan.
     *
     * @param  array<string, mixed>  $bucket
     * @return array<string, mixed>
     */
    private function finalizeMonthlyBucket(array $bucket): array
    {
        $counts = $this->finalizeAttendanceCounts($bucket['counts']);
        $total = (int) $counts['total_catatan'];

        $bucket['counts'] = $counts;
        $bucket['shares'] = [];
        foreach (['hadir', 'terlambat', 'izin', 'sakit', 'alpa'] as $statusKey) {
            $bucket['shares'][$statusKey] = $total > 0
                ? round(((int) $counts[$statusKey] / $total) * 100, 1)
                : 0.0;
        }

        return $bucket;
    }

    /**
     * Menyusun highlight performa tahunan (bulan terbaik, puncak terlambat, rata-rata bulanan).
     *
     * @param  list<array<string, mixed>>  $monthlyRows
     * @param  array<string, mixed>  $summary
     * @return array<string, mixed>
     */
    private function buildAnnualInsights(array $monthlyRows, array $summary, int $totalHariSekolah): array
    {
        $activeMonths = array_values(array_filter(
            $monthlyRows,
            fn (array $row): bool => (int) ($row['counts']['total_catatan'] ?? 0) > 0
        ));

        $bulanTerbaik = null;
        foreach ($activeMonths as $row) {
            if ($bulanTerbaik === null) {
                $bulanTerbaik = $row;

                continue;
            }

            $curHadir = (float) $row['counts']['persentase_hadir'];
            $bestHadir = (float) $bulanTerbaik['counts']['persentase_hadir'];
            $curTelat = (float) $row['counts']['persentase_terlambat'];
            $bestTelat = (float) $bulanTerbaik['counts']['persentase_terlambat'];

            if ($curHadir > $bestHadir || ($curHadir === $bestHadir && $curTelat < $bestTelat)) {
                $bulanTerbaik = $row;
            }
        }

        $bulanTelatTertinggi = null;
        foreach ($activeMonths as $row) {
            if ((int) $row['counts']['terlambat'] <= 0) {
                continue;
            }

            if (
                $bulanTelatTertinggi === null
                || (int) $row['counts']['terlambat'] > (int) $bulanTelatTertinggi['counts']['terlambat']
                || (
                    (int) $row['counts']['terlambat'] === (int) $bulanTelatTertinggi['counts']['terlambat']
                    && (int) $row['counts']['total_telat_menit'] > (int) $bulanTelatTertinggi['counts']['total_telat_menit']
                )
            ) {
                $bulanTelatTertinggi = $row;
            }
        }

        $activeCount = count($activeMonths);
        $avgMonthlyAttendance = $activeCount > 0
            ? round(array_sum(array_map(fn (array $r): float => (float) $r['counts']['persentase_hadir'], $activeMonths)) / $activeCount, 1)
            : 0.0;

        $totalTelatCount = (int) ($summary['terlambat'] ?? 0);
        $avgLateMinutes = $totalTelatCount > 0
            ? round(((int) ($summary['total_telat_menit'] ?? 0)) / $totalTelatCount, 1)
            : 0.0;

        return [
            'total_hari_sekolah' => $totalHariSekolah,
            'bulan_aktif_count' => $activeCount,
            'rata_kehadiran_bulanan' => $avgMonthlyAttendance,
            'rata_menit_telat' => $avgLateMinutes,
            'bulan_terbaik' => $bulanTerbaik ? [
                'bulan' => $bulanTerbaik['bulan'],
                'label' => $bulanTerbaik['label'],
                'persentase_hadir' => $bulanTerbaik['counts']['persentase_hadir'],
                'total_catatan' => $bulanTerbaik['counts']['total_catatan'],
            ] : null,
            'bulan_telat_tertinggi' => $bulanTelatTertinggi ? [
                'bulan' => $bulanTelatTertinggi['bulan'],
                'label' => $bulanTelatTertinggi['label'],
                'terlambat' => $bulanTelatTertinggi['counts']['terlambat'],
                'total_telat_menit' => $bulanTelatTertinggi['counts']['total_telat_menit'],
                'persentase_terlambat' => $bulanTelatTertinggi['counts']['persentase_terlambat'],
            ] : null,
        ];
    }
}
