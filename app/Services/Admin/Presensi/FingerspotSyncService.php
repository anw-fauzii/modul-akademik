<?php

namespace App\Services\Admin\Presensi;

use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Penilaian\PenilaianFinalisasi;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Models\Admin\Presensi\PresensiSiswa;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class FingerspotSyncService
{
    public const TIMEZONE = 'Asia/Jakarta';

    public const MAX_SYNC_RANGE_DAYS = 31;

    private const INSERT_CHUNK_SIZE = 500;

    /**
     * Menormalkan NIS / PIN agar bebas spasi, NBSP (\u00A0), zero-width space, dan BOM.
     */
    public static function normalizeNis(?string $value): string
    {
        if (! is_string($value) || $value === '') {
            return '';
        }

        $cleaned = preg_replace('/[\s\x{00A0}\x{200B}-\x{200D}\x{FEFF}]/u', '', trim($value));

        return is_string($cleaned) ? $cleaned : '';
    }

    /**
     * Menyinkronkan data presensi Fingerspot untuk hari ini (zona waktu Asia/Jakarta).
     *
     * @return array{
     *     start_date: string,
     *     end_date: string,
     *     clouds_total: int,
     *     clouds_success: int,
     *     failed_clouds: list<array{cloud_id: string, status: int|null}>,
     *     logs_fetched: int,
     *     unique_student_days: int,
     *     inserted: int,
     *     hadir: int,
     *     terlambat: int,
     *     skipped_existing: int,
     *     skipped_locked: int,
     *     unmatched_pins: list<string>
     * }
     */
    public function syncToday(): array
    {
        $today = Carbon::now(self::TIMEZONE)->toDateString();

        return $this->syncDateRange($today, $today);
    }

    /**
     * Menyinkronkan log presensi dari API Fingerspot ke tabel akademik_presensi_siswa
     * dalam satu batch transaksi yang aman dari SQL injection dan N+1 query.
     *
     * @return array{
     *     start_date: string,
     *     end_date: string,
     *     clouds_total: int,
     *     clouds_success: int,
     *     failed_clouds: list<array{cloud_id: string, status: int|null}>,
     *     logs_fetched: int,
     *     unique_student_days: int,
     *     inserted: int,
     *     hadir: int,
     *     terlambat: int,
     *     skipped_existing: int,
     *     skipped_locked: int,
     *     unmatched_pins: list<string>
     * }
     */
    public function syncDateRange(?string $startDate = null, ?string $endDate = null): array
    {
        [$resolvedStart, $resolvedEnd] = $this->resolveDateRange($startDate, $endDate);
        $config = $this->resolveConfiguration();

        $fetchResult = $this->fetchEarliestScanLogs($config, $resolvedStart, $resolvedEnd);
        $persistResult = $this->persistLogsToDatabase($fetchResult['earliest_logs']);

        return [
            'start_date' => $resolvedStart,
            'end_date' => $resolvedEnd,
            'clouds_total' => $fetchResult['clouds_total'],
            'clouds_success' => $fetchResult['clouds_success'],
            'failed_clouds' => $fetchResult['failed_clouds'],
            'logs_fetched' => $fetchResult['logs_fetched'],
            'unique_student_days' => $fetchResult['unique_student_days'],
            'inserted' => $persistResult['inserted'],
            'hadir' => $persistResult['hadir'],
            'terlambat' => $persistResult['terlambat'],
            'skipped_existing' => $persistResult['skipped_existing'],
            'skipped_locked' => $persistResult['skipped_locked'],
            'unmatched_pins' => $persistResult['unmatched_pins'],
        ];
    }

    /**
     * Memeriksa kesiapan konfigurasi Fingerspot di config/services & .env untuk ditampilkan di UI Admin.
     *
     * @return array{
     *     is_configured: bool,
     *     has_token: bool,
     *     cloud_count: int,
     *     endpoint_url: string
     * }
     */
    public function getConfigurationStatus(): array
    {
        $raw = config('services.fingerspot', []);
        $token = trim((string) ($raw['api_token'] ?? ''));
        $cloudIds = $this->parseCloudIds($raw['cloud_ids'] ?? []);
        $baseUrl = rtrim((string) ($raw['base_url'] ?? 'https://developer.fingerspot.io/api'), '/');
        $endpointPath = '/'.ltrim((string) ($raw['endpoint_path'] ?? 'get_attlog'), '/');

        return [
            'is_configured' => $token !== '' && $cloudIds !== [],
            'has_token' => $token !== '',
            'cloud_count' => count($cloudIds),
            'endpoint_url' => $baseUrl.$endpointPath,
        ];
    }

    /**
     * Memvalidasi & menormalkan konfigurasi API Fingerspot dari environment/config.
     *
     * @return array{
     *     endpoint_url: string,
     *     api_token: string,
     *     cloud_ids: list<string>,
     *     timeout: int,
     *     retry_times: int,
     *     retry_sleep_ms: int
     * }
     */
    private function resolveConfiguration(): array
    {
        $raw = config('services.fingerspot', []);
        $baseUrl = rtrim(trim((string) ($raw['base_url'] ?? 'https://developer.fingerspot.io/api')), '/');
        $endpointPath = '/'.ltrim(trim((string) ($raw['endpoint_path'] ?? 'get_attlog')), '/');
        $endpointUrl = $baseUrl.$endpointPath;

        $parsedScheme = strtolower((string) parse_url($endpointUrl, PHP_URL_SCHEME));
        if (! in_array($parsedScheme, ['https', 'http'], true) || filter_var($endpointUrl, FILTER_VALIDATE_URL) === false) {
            throw ValidationException::withMessages([
                'fingerspot' => 'URL API Fingerspot pada konfigurasi (.env) tidak valid.',
            ]);
        }

        $apiToken = trim((string) ($raw['api_token'] ?? ''));
        if ($apiToken === '') {
            throw ValidationException::withMessages([
                'fingerspot' => 'FINGERSPOT_API_TOKEN belum diatur di file .env.',
            ]);
        }

        $cloudIds = $this->parseCloudIds($raw['cloud_ids'] ?? []);
        if ($cloudIds === []) {
            throw ValidationException::withMessages([
                'fingerspot' => 'FINGERSPOT_CLOUD_IDS belum diatur atau formatnya tidak valid di file .env.',
            ]);
        }

        return [
            'endpoint_url' => $endpointUrl,
            'api_token' => $apiToken,
            'cloud_ids' => $cloudIds,
            'timeout' => max(5, min(120, (int) ($raw['timeout'] ?? 30))),
            'retry_times' => max(1, min(5, (int) ($raw['retry_times'] ?? 3))),
            'retry_sleep_ms' => max(100, min(5000, (int) ($raw['retry_sleep_ms'] ?? 1000))),
        ];
    }

    /**
     * Mem-parsing & menyaring daftar Cloud ID perangkat Fingerspot secara ketat.
     *
     * @return list<string>
     */
    private function parseCloudIds(mixed $rawCloudIds): array
    {
        $candidates = is_array($rawCloudIds)
            ? $rawCloudIds
            : (preg_split('/[\s,;]+/', (string) $rawCloudIds) ?: []);

        $valid = [];
        foreach ($candidates as $candidate) {
            $id = trim((string) $candidate);
            if ($id === '' || preg_match('/^[A-Za-z0-9_-]{4,64}$/', $id) !== 1) {
                continue;
            }
            $valid[$id] = $id;
        }

        return array_values($valid);
    }

    /**
     * Memvalidasi rentang tanggal sinkronisasi agar selalu valid dan terukur.
     *
     * @return array{0: string, 1: string}
     */
    private function resolveDateRange(?string $startDate, ?string $endDate): array
    {
        $today = Carbon::now(self::TIMEZONE)->toDateString();

        $start = $this->parseDateSafely($startDate) ?? $today;
        $end = $this->parseDateSafely($endDate) ?? $start;

        $startCarbon = Carbon::createFromFormat('Y-m-d', $start, self::TIMEZONE)->startOfDay();
        $endCarbon = Carbon::createFromFormat('Y-m-d', $end, self::TIMEZONE)->startOfDay();

        if ($endCarbon->lt($startCarbon)) {
            throw ValidationException::withMessages([
                'end_date' => 'Tanggal akhir sinkronisasi tidak boleh lebih awal dari tanggal mulai.',
            ]);
        }

        $diffDays = (int) $startCarbon->diffInDays($endCarbon) + 1;
        if ($diffDays > self::MAX_SYNC_RANGE_DAYS) {
            throw ValidationException::withMessages([
                'end_date' => 'Rentang sinkronisasi Fingerspot maksimal '.self::MAX_SYNC_RANGE_DAYS.' hari per proses.',
            ]);
        }

        return [$startCarbon->toDateString(), $endCarbon->toDateString()];
    }

    private function parseDateSafely(?string $rawDate): ?string
    {
        if (! is_string($rawDate) || trim($rawDate) === '') {
            return null;
        }

        try {
            return Carbon::parse(trim($rawDate), self::TIMEZONE)->toDateString();
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'start_date' => 'Format tanggal sinkronisasi tidak valid.',
            ]);
        }
    }

    /**
     * Mengambil log kehadiran dari seluruh mesin Fingerspot terkonfigurasi lalu menyimpan scan paling awal per siswa per hari.
     *
     * @param  array{
     *     endpoint_url: string,
     *     api_token: string,
     *     cloud_ids: list<string>,
     *     timeout: int,
     *     retry_times: int,
     *     retry_sleep_ms: int
     * }  $config
     * @return array{
     *     earliest_logs: array<string, array<string, Carbon>>,
     *     clouds_total: int,
     *     clouds_success: int,
     *     failed_clouds: list<array{cloud_id: string, status: int|null}>,
     *     logs_fetched: int,
     *     unique_student_days: int
     * }
     */
    private function fetchEarliestScanLogs(array $config, string $startDate, string $endDate): array
    {
        $earliestLogs = [];
        $failedClouds = [];
        $cloudsSuccess = 0;
        $logsFetched = 0;

        foreach ($config['cloud_ids'] as $cloudId) {
            try {
                $response = Http::retry($config['retry_times'], $config['retry_sleep_ms'], null, false)
                    ->timeout($config['timeout'])
                    ->acceptJson()
                    ->asJson()
                    ->withToken($config['api_token'])
                    ->post($config['endpoint_url'], [
                        'trans_id' => Str::uuid()->toString(),
                        'cloud_id' => $cloudId,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                    ]);
            } catch (Throwable $exception) {
                Log::error('Fingerspot gagal terhubung ke API untuk Cloud ID', [
                    'cloud_id' => $cloudId,
                    'exception' => $exception->getMessage(),
                ]);
                $failedClouds[] = ['cloud_id' => $cloudId, 'status' => null];

                continue;
            }

            if (! $response->successful()) {
                Log::error('Fingerspot mengembalikan status gagal', [
                    'cloud_id' => $cloudId,
                    'status' => $response->status(),
                    'body' => Str::limit((string) $response->body(), 500),
                ]);
                $failedClouds[] = ['cloud_id' => $cloudId, 'status' => $response->status()];

                continue;
            }

            $cloudsSuccess++;
            $entries = $response->json('data', []);
            if (! is_array($entries)) {
                continue;
            }

            foreach ($entries as $log) {
                if (! is_array($log) || ! isset($log['pin'], $log['scan_date'])) {
                    continue;
                }

                $nis = self::normalizeNis((string) $log['pin']);
                if ($nis === '') {
                    continue;
                }

                $waktu = $this->parseScanTimestamp((string) $log['scan_date']);
                if ($waktu === null) {
                    continue;
                }

                $logsFetched++;
                $tanggalKey = $waktu->toDateString();

                if (! isset($earliestLogs[$nis][$tanggalKey]) || $waktu->lt($earliestLogs[$nis][$tanggalKey])) {
                    $earliestLogs[$nis][$tanggalKey] = $waktu;
                }
            }
        }

        $uniqueStudentDays = 0;
        foreach ($earliestLogs as $days) {
            $uniqueStudentDays += count($days);
        }

        return [
            'earliest_logs' => $earliestLogs,
            'clouds_total' => count($config['cloud_ids']),
            'clouds_success' => $cloudsSuccess,
            'failed_clouds' => $failedClouds,
            'logs_fetched' => $logsFetched,
            'unique_student_days' => $uniqueStudentDays,
        ];
    }

    private function parseScanTimestamp(string $rawScanDate): ?Carbon
    {
        $trimmed = trim($rawScanDate);
        if ($trimmed === '') {
            return null;
        }

        try {
            return Carbon::parse($trimmed, self::TIMEZONE)->setTimezone(self::TIMEZONE);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Memetakan log ke AnggotaKelas aktif & menyimpan presensi baru secara batch (O(1) query, anti-N+1 & anti-SQLi).
     *
     * @param  array<string, array<string, Carbon>>  $earliestLogs
     * @return array{
     *     inserted: int,
     *     hadir: int,
     *     terlambat: int,
     *     skipped_existing: int,
     *     skipped_locked: int,
     *     unmatched_pins: list<string>
     * }
     */
    private function persistLogsToDatabase(array $earliestLogs): array
    {
        if ($earliestLogs === []) {
            return [
                'inserted' => 0,
                'hadir' => 0,
                'terlambat' => 0,
                'skipped_existing' => 0,
                'skipped_locked' => 0,
                'unmatched_pins' => [],
            ];
        }

        $activeTahunAjaran = TahunAjaran::query()
            ->select(['id', 'nama_tahun_ajaran', 'semester'])
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first();

        if ($activeTahunAjaran === null) {
            throw ValidationException::withMessages([
                'fingerspot' => 'Tidak ada Tahun Ajaran aktif untuk memetakan anggota kelas siswa.',
            ]);
        }

        $activeTaId = (int) $activeTahunAjaran->id;

        // 1 query: muat seluruh anggota kelas pada TA aktif lalu indeks berdasarkan NIS ternormalisasi di memori (bebas whereRaw & bebas N+1).
        $anggotaRows = AnggotaKelas::query()
            ->select(['id', 'siswa_nis', 'akademik_master_kelas_id'])
            ->whereHas('kelas', static function (Builder $kelasQuery) use ($activeTaId): void {
                $kelasQuery->where('akademik_master_tahun_ajaran_id', $activeTaId);
            })
            ->orderByDesc('id')
            ->get();

        $anggotaByNis = [];
        foreach ($anggotaRows as $row) {
            $normNis = self::normalizeNis((string) $row->siswa_nis);
            if ($normNis !== '' && ! isset($anggotaByNis[$normNis])) {
                $anggotaByNis[$normNis] = $row;
            }
        }

        $matchedAnggotaIds = [];
        $matchedKelasIds = [];
        $allDateKeys = [];
        $unmatchedPins = [];

        foreach ($earliestLogs as $nis => $dailyLogs) {
            $anggota = $anggotaByNis[$nis] ?? null;
            if ($anggota === null) {
                $unmatchedPins[] = $nis;

                continue;
            }

            $matchedAnggotaIds[(int) $anggota->id] = (int) $anggota->id;
            $matchedKelasIds[(int) $anggota->akademik_master_kelas_id] = (int) $anggota->akademik_master_kelas_id;

            foreach (array_keys($dailyLogs) as $tanggalKey) {
                $allDateKeys[$tanggalKey] = $tanggalKey;
            }
        }

        if ($unmatchedPins !== []) {
            Log::warning('Sinkronisasi Fingerspot: sebagian PIN/NIS belum terdaftar sebagai anggota kelas aktif', [
                'count' => count($unmatchedPins),
                'pins' => array_slice($unmatchedPins, 0, 50),
            ]);
        }

        if ($matchedAnggotaIds === [] || $allDateKeys === []) {
            return [
                'inserted' => 0,
                'hadir' => 0,
                'terlambat' => 0,
                'skipped_existing' => 0,
                'skipped_locked' => 0,
                'unmatched_pins' => array_values($unmatchedPins),
            ];
        }

        $anggotaIdList = array_values($matchedAnggotaIds);
        $kelasIdList = array_values($matchedKelasIds);
        $dateKeyList = array_values($allDateKeys);

        // 1 query: cek kunci finalisasi presensi (Mid / Semester) sekaligus untuk semua kelas terkait.
        [$semesterFinalKelasIds, $midFinalCutoffByKelasId] = $this->loadFinalisasiLocks($activeTaId, $kelasIdList);

        // 1 query: muat pasangan (anggota_kelas_id, tanggal) yang sudah memiliki catatan presensi.
        $existingRecords = PresensiSiswa::query()
            ->select(['anggota_kelas_id', 'tanggal'])
            ->whereIn('anggota_kelas_id', $anggotaIdList)
            ->whereIn('tanggal', $dateKeyList)
            ->get();

        $existingLookup = [];
        foreach ($existingRecords as $record) {
            $tgl = $record->tanggal?->toDateString();
            if ($tgl !== null) {
                $existingLookup[(int) $record->anggota_kelas_id.'|'.$tgl] = true;
            }
        }

        $now = now();
        $rowsToInsert = [];
        $hadirCount = 0;
        $terlambatCount = 0;
        $skippedExisting = 0;
        $skippedLocked = 0;

        foreach ($earliestLogs as $nis => $dailyLogs) {
            $anggota = $anggotaByNis[$nis] ?? null;
            if ($anggota === null) {
                continue;
            }

            $anggotaId = (int) $anggota->id;
            $kelasId = (int) $anggota->akademik_master_kelas_id;

            foreach ($dailyLogs as $tanggalKey => $waktuMasuk) {
                if ($this->isDateLockedForClass($kelasId, $tanggalKey, $semesterFinalKelasIds, $midFinalCutoffByKelasId)) {
                    $skippedLocked++;

                    continue;
                }

                if (isset($existingLookup[$anggotaId.'|'.$tanggalKey])) {
                    $skippedExisting++;

                    continue;
                }

                $jamMasuk = $waktuMasuk->format('H:i:s');
                $keterlambatanMenit = PresensiSiswa::calculateKeterlambatanMenit($jamMasuk);
                $status = $keterlambatanMenit > 0 ? 'terlambat' : 'hadir';

                if ($status === 'terlambat') {
                    $terlambatCount++;
                } else {
                    $hadirCount++;
                }

                $rowsToInsert[] = [
                    'anggota_kelas_id' => $anggotaId,
                    'tanggal' => $tanggalKey,
                    'jam_masuk' => $jamMasuk,
                    'status' => $status,
                    'keterlambatan_menit' => $keterlambatanMenit,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($rowsToInsert === []) {
            return [
                'inserted' => 0,
                'hadir' => 0,
                'terlambat' => 0,
                'skipped_existing' => $skippedExisting,
                'skipped_locked' => $skippedLocked,
                'unmatched_pins' => array_values($unmatchedPins),
            ];
        }

        $insertedCount = DB::transaction(static function () use ($rowsToInsert): int {
            $affected = 0;
            foreach (array_chunk($rowsToInsert, self::INSERT_CHUNK_SIZE) as $chunk) {
                $affected += (int) PresensiSiswa::query()->insertOrIgnore($chunk);
            }

            return $affected;
        });

        return [
            'inserted' => $insertedCount,
            'hadir' => $hadirCount,
            'terlambat' => $terlambatCount,
            'skipped_existing' => $skippedExisting,
            'skipped_locked' => $skippedLocked,
            'unmatched_pins' => array_values($unmatchedPins),
        ];
    }

    /**
     * Memuat seluruh status finalisasi Presensi per kelas dalam 1 query batch.
     *
     * @param  list<int>  $kelasIds
     * @return array{0: array<int, true>, 1: array<int, string>}
     */
    private function loadFinalisasiLocks(int $tahunAjaranId, array $kelasIds): array
    {
        if ($kelasIds === []) {
            return [[], []];
        }

        $locks = PenilaianFinalisasi::query()
            ->select(['kelas_id', 'periode', 'finalized_at'])
            ->where('akademik_master_tahun_ajaran_id', $tahunAjaranId)
            ->where('jenis_komponen', PenilaianFinalisasi::JENIS_PRESENSI)
            ->where('status', PenilaianFinalisasi::STATUS_FINAL)
            ->whereIn('kelas_id', $kelasIds)
            ->get();

        $semesterLocked = [];
        $midCutoffs = [];

        foreach ($locks as $lock) {
            $kId = (int) $lock->kelas_id;
            if ($kId <= 0) {
                continue;
            }

            if ($lock->periode === PeriodeTp::PERIODE_SEMESTER) {
                $semesterLocked[$kId] = true;
            } elseif ($lock->periode === PeriodeTp::PERIODE_MID && $lock->finalized_at !== null) {
                $midCutoffs[$kId] = $lock->finalized_at->toDateString();
            }
        }

        return [$semesterLocked, $midCutoffs];
    }

    /**
     * @param  array<int, true>  $semesterFinalKelasIds
     * @param  array<int, string>  $midFinalCutoffByKelasId
     */
    private function isDateLockedForClass(
        int $kelasId,
        string $tanggalYmd,
        array $semesterFinalKelasIds,
        array $midFinalCutoffByKelasId
    ): bool {
        if (isset($semesterFinalKelasIds[$kelasId])) {
            return true;
        }

        $cutoff = $midFinalCutoffByKelasId[$kelasId] ?? null;

        return $cutoff !== null && $tanggalYmd <= $cutoff;
    }
}
