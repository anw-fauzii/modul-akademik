<?php

namespace App\Services\Admin\T2q;

use App\Models\Admin\DataMaster\AnggotaT2q;
use App\Models\Admin\DataMaster\AngkaArab;
use App\Models\Admin\DataMaster\DaftarJilid;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Surah;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\DataMaster\TargetCapaian;
use App\Services\Admin\Penilaian\PenilaianFinalisasiService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

abstract class BaseYaumiyahT2qService
{
    /**
     * Kelas model Eloquent untuk jenis yaumiyah terkait.
     *
     * @return class-string<Model>
     */
    abstract protected function modelClass(): string;

    /**
     * Kunci mode yaumiyah ('tahsin' atau 'tahfizh').
     */
    abstract public function modeKey(): string;

    /**
     * Apakah jenis yaumiyah ini menggunakan master daftar jilid.
     */
    abstract public function usesDaftarJilid(): bool;

    /**
     * Menyusun payload tersanitasi untuk satu baris input atau null jika dilewati.
     *
     * @param  array<string, mixed>  $rowInput
     * @param  array{
     *     jilidJenisMap: array<int, string>,
     *     surahAyatMap: array<int, int>,
     *     angkaLatinMap: array<int, string>
     * }  $lookupMaps
     * @return array<string, mixed>|null
     */
    abstract protected function buildRowPayload(array $rowInput, array $lookupMaps): ?array;

    /**
     * Mengambil data lengkap halaman kerja input yaumiyah T2Q harian tanpa N+1.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function getWorkspaceData(array $input = []): array
    {
        $context = $this->resolveBaseContext($input);
        $masterOptions = $this->getMasterOptions();

        if ($context['activeTahunAjaran'] === null) {
            return [
                ...$context['payload'],
                'todayEntries' => new EloquentCollection,
                'previousEntries' => new EloquentCollection,
                'savedCount' => 0,
                ...$masterOptions,
            ];
        }

        $todayEntries = $this->getTodayEntriesByAnggota($context['anggotaIds'], $context['payload']['tanggal']);
        $previousEntries = $this->getLatestPreviousEntriesByAnggota($context['anggotaIds'], $context['payload']['tanggal']);

        return [
            ...$context['payload'],
            'todayEntries' => $todayEntries,
            'previousEntries' => $previousEntries,
            'savedCount' => $todayEntries->count(),
            ...$masterOptions,
        ];
    }

    /**
     * Mengambil data matriks catatan mingguan (Senin – Kamis) untuk Yaumiyah Tahsin/Tahfizh tanpa N+1.
     *
     * @param  array<string, mixed>  $input
     * @param  list<int>|null  $scopedKelasIds
     * @return array<string, mixed>
     */
    public function getMatrixData(array $input = [], ?array $scopedKelasIds = null): array
    {
        $context = $this->resolveBaseContext($input, $scopedKelasIds);
        $period = $this->resolveWeekPeriod($context['payload']['tanggal']);

        $entriesByAnggotaDate = ($context['activeTahunAjaran'] !== null && $context['anggotaIds'] !== [])
            ? $this->getEntriesByAnggotaAndDate($context['anggotaIds'], $period['week_start'], $period['week_end'])
            : [];

        $matrix = $this->buildWeeklyMatrix($context['payload']['students'], $entriesByAnggotaDate, $period);

        return [
            ...$context['payload'],
            'period' => $period,
            'matrix' => $matrix,
        ];
    }

    /**
     * Mengambil data statistik Yaumiyah T2Q berdasarkan tahun_ajaran_id tanpa N+1.
     *
     * @param  array<string, mixed>  $input
     * @param  list<int>|null  $scopedKelasIds
     * @param  EloquentCollection<int, TahunAjaran>|null  $scopedTahunAjaranOptions
     * @return array<string, mixed>
     */
    public function getStatistikData(
        array $input = [],
        ?array $scopedKelasIds = null,
        ?EloquentCollection $scopedTahunAjaranOptions = null
    ): array {
        $tahunAjaranOptions = $scopedTahunAjaranOptions ?? TahunAjaran::query()
            ->orderedForDropdown()
            ->get();

        $requestedTaId = (int) ($input['tahun_ajaran_id'] ?? 0);
        $selectedTahunAjaran = $requestedTaId > 0
            ? ($tahunAjaranOptions->firstWhere('id', $requestedTaId)
                ?? $tahunAjaranOptions->firstWhere('is_active', true)
                ?? $tahunAjaranOptions->first())
            : ($tahunAjaranOptions->firstWhere('is_active', true) ?? $tahunAjaranOptions->first());

        $selectedGuruNipy = trim((string) ($input['karyawan_nipy'] ?? ''));
        $requestedTingkat = AnggotaT2q::normalizeTingkat($input['tingkat'] ?? '');

        if ($selectedTahunAjaran === null) {
            return $this->emptyStatistikPayload($tahunAjaranOptions, $selectedGuruNipy, $requestedTingkat);
        }

        $guruOptions = $this->getGuruOptions((int) $selectedTahunAjaran->id, $scopedKelasIds);
        if ($selectedGuruNipy !== '' && ! $guruOptions->contains('nipy', $selectedGuruNipy)) {
            $selectedGuruNipy = '';
        }

        $tingkatTabs = $this->getTingkatSummary((int) $selectedTahunAjaran->id, $selectedGuruNipy, $scopedKelasIds);
        $selectedTingkat = $this->resolveOptionalTingkat($requestedTingkat, array_keys($tingkatTabs));

        $students = $this->getStudents((int) $selectedTahunAjaran->id, $selectedGuruNipy, $selectedTingkat, false, $scopedKelasIds);
        $anggotaIds = $students->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all();

        $targetCapaian = TargetCapaian::query()
            ->with([
                'daftarJilid:id,jilid_latin,jilid_arab,jenis,urutan',
                'surah:id,nama_surah,nama_surah_arab,jumlah_ayat',
            ])
            ->forTahunAjaran((int) $selectedTahunAjaran->id)
            ->when($selectedTingkat !== '', static fn (Builder $q) => $q->forTingkat($selectedTingkat))
            ->orderBy('id')
            ->get()
            ->groupBy(static fn (TargetCapaian $tc): string => AnggotaT2q::normalizeTingkat($tc->tingkat));

        $entries = $this->getEntriesForStatistik($anggotaIds);
        $stats = $this->buildAnnualStatistik($selectedTahunAjaran, $students, $entries, $targetCapaian);

        return [
            'mode' => $this->modeKey(),
            'usesDaftarJilid' => $this->usesDaftarJilid(),
            'tahunAjaranOptions' => $tahunAjaranOptions,
            'selectedTahunAjaran' => $selectedTahunAjaran,
            'selectedTahunAjaranId' => (int) $selectedTahunAjaran->id,
            'guruOptions' => $guruOptions,
            'selectedGuruNipy' => $selectedGuruNipy,
            'tingkatOptions' => $tingkatTabs,
            'selectedTingkat' => $selectedTingkat,
            'stats' => $stats,
        ];
    }

    /**
     * Menyimpan input yaumiyah harian secara massal dalam satu transaksi atomik (1 hari 1 setoran).
     *
     * @param  array<int|string, array<string, mixed>>  $rows
     */
    public function saveBatch(string $tanggal, array $rows): int
    {
        $activeTahunAjaran = $this->resolveActiveTahunAjaran();
        if ($activeTahunAjaran === null || $rows === []) {
            return 0;
        }

        $candidateIds = collect(array_keys($rows))
            ->filter(static fn ($id): bool => ctype_digit((string) $id) && (int) $id > 0)
            ->map(static fn ($id): int => (int) $id)
            ->values()
            ->all();

        if ($candidateIds === []) {
            return 0;
        }

        $allowedIds = AnggotaT2q::query()
            ->whereIn('id', $candidateIds)
            ->forTahunAjaran($activeTahunAjaran->id)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->flip()
            ->all();

        $lookupMaps = $this->loadValidationLookupMaps();
        $normalizedDate = $this->resolveDate($tanggal);

        if ($allowedIds !== []) {
            $touchedAnggotaIds = [];
            foreach ($rows as $rawAnggotaId => $rowInput) {
                $anggotaId = (int) $rawAnggotaId;
                if (isset($allowedIds[$anggotaId]) && is_array($rowInput)) {
                    $touchedAnggotaIds[] = $anggotaId;
                }
            }

            if ($touchedAnggotaIds !== []) {
                $kelasIds = AnggotaT2q::query()
                    ->join('akademik_master_anggota_kelas as ak', 'ak.id', '=', 'akademik_master_anggota_t2q.anggota_kelas_id')
                    ->whereIn('akademik_master_anggota_t2q.id', $touchedAnggotaIds)
                    ->distinct()
                    ->pluck('ak.akademik_master_kelas_id')
                    ->map(static fn ($id): int => (int) $id)
                    ->all();

                $finalisasiService = app(PenilaianFinalisasiService::class);
                foreach ($kelasIds as $kelasId) {
                    $finalisasiService->assertYaumiyahEditable((int) $activeTahunAjaran->id, $kelasId, $normalizedDate);
                }
            }
        }

        $modelClass = $this->modelClass();
        $savedCount = 0;

        DB::transaction(function () use ($rows, $allowedIds, $lookupMaps, $normalizedDate, $modelClass, &$savedCount): void {
            foreach ($rows as $rawAnggotaId => $rowInput) {
                $anggotaId = (int) $rawAnggotaId;
                if (! isset($allowedIds[$anggotaId]) || ! is_array($rowInput)) {
                    continue;
                }

                $payload = $this->buildRowPayload($rowInput, $lookupMaps);
                if ($payload === null) {
                    continue;
                }

                $modelClass::query()->updateOrCreate(
                    [
                        'anggota_t2q_id' => $anggotaId,
                        'tanggal' => $normalizedDate,
                    ],
                    $payload
                );
                $savedCount++;
            }
        });

        return $savedCount;
    }

    /**
     * Menghapus satu catatan yaumiyah T2Q.
     */
    public function delete(Model $entry): bool
    {
        $entry->loadMissing('anggotaT2q.anggotaKelas.kelas:id,akademik_master_tahun_ajaran_id');
        $kelas = $entry->anggotaT2q?->anggotaKelas?->kelas;
        if ($kelas !== null) {
            $tanggal = method_exists($entry->tanggal ?? null, 'toDateString')
                ? $entry->tanggal->toDateString()
                : (string) ($entry->tanggal ?? now()->toDateString());

            app(PenilaianFinalisasiService::class)->assertYaumiyahEditable(
                (int) $kelas->akademik_master_tahun_ajaran_id,
                (int) $kelas->id,
                $tanggal
            );
        }

        return (bool) $entry->delete();
    }

    /**
     * Mengambil satu Tahun Ajaran aktif saat ini.
     */
    public function resolveActiveTahunAjaran(): ?TahunAjaran
    {
        return TahunAjaran::query()
            ->active()
            ->orderedForDropdown()
            ->first();
    }

    /**
     * Menormalisasi input tanggal ke format Y-m-d (default hari ini).
     */
    public function resolveDate(mixed $rawDate): string
    {
        $raw = trim((string) ($rawDate ?? ''));
        if ($raw === '') {
            return Carbon::today()->toDateString();
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $raw)->toDateString();
        } catch (\Throwable) {
            return Carbon::today()->toDateString();
        }
    }

    /**
     * Mengambil seluruh guru T2Q yang bertugas pada Tahun Ajaran aktif tanpa N+1.
     *
     * @param  list<int>|null  $scopedKelasIds
     * @return EloquentCollection<int, Karyawan>
     */
    private function getGuruOptions(int $tahunAjaranId, ?array $scopedKelasIds = null): EloquentCollection
    {
        $nipys = AnggotaT2q::query()
            ->select('karyawan_nipy')
            ->forTahunAjaran($tahunAjaranId)
            ->when($scopedKelasIds !== null, static fn (Builder $query) => $query->forKelasIds($scopedKelasIds))
            ->whereNotNull('karyawan_nipy')
            ->where('karyawan_nipy', '!=', '')
            ->distinct()
            ->pluck('karyawan_nipy')
            ->all();

        if ($nipys === []) {
            return new EloquentCollection;
        }

        return Karyawan::query()
            ->select(['nipy', 'nama_lengkap', 'gelar'])
            ->whereIn('nipy', $nipys)
            ->orderBy('nama_lengkap')
            ->get();
    }

    /**
     * Menghitung jumlah siswa per tingkat untuk tab navigasi dalam satu query GROUP BY.
     *
     * @param  list<int>|null  $scopedKelasIds
     * @return array<string, int>
     */
    private function getTingkatSummary(int $tahunAjaranId, string $karyawanNipy = '', ?array $scopedKelasIds = null): array
    {
        $rows = AnggotaT2q::query()
            ->selectRaw('tingkat, COUNT(*) as total')
            ->forTahunAjaran($tahunAjaranId)
            ->when($scopedKelasIds !== null, static fn (Builder $query) => $query->forKelasIds($scopedKelasIds))
            ->when($karyawanNipy !== '', static fn (Builder $query) => $query->forKaryawan($karyawanNipy))
            ->groupBy('tingkat')
            ->get();

        $summary = [];
        foreach ($rows as $row) {
            $tingkat = AnggotaT2q::normalizeTingkat($row->tingkat ?? '');
            if ($tingkat === '') {
                continue;
            }
            $summary[$tingkat] = (int) ($row->total ?? 0);
        }

        uksort($summary, static fn (string $a, string $b): int => AnggotaT2q::compareTingkatOrder($a, $b));

        return $summary;
    }

    /**
     * Mengambil daftar siswa Anggota T2Q dengan eager loading minimal anti-N+1.
     *
     * @param  list<int>|null  $scopedKelasIds
     * @return EloquentCollection<int, AnggotaT2q>
     */
    private function getStudents(
        int $tahunAjaranId,
        string $karyawanNipy = '',
        string $tingkat = '',
        bool $requireTingkat = true,
        ?array $scopedKelasIds = null
    ): EloquentCollection {
        if ($requireTingkat && $tingkat === '') {
            return new EloquentCollection;
        }

        $items = AnggotaT2q::query()
            ->select([
                'id',
                'anggota_kelas_id',
                'karyawan_nipy',
                'tingkat',
                'pendaftaran',
                'keterangan',
            ])
            ->withMasterRelations()
            ->forTahunAjaran($tahunAjaranId)
            ->when($scopedKelasIds !== null, static fn (Builder $query) => $query->forKelasIds($scopedKelasIds))
            ->when($karyawanNipy !== '', static fn (Builder $query) => $query->forKaryawan($karyawanNipy))
            ->when($tingkat !== '', static fn (Builder $query) => $query->forTingkat($tingkat))
            ->get();

        return new EloquentCollection(
            $items->sort(static function (AnggotaT2q $a, AnggotaT2q $b): int {
                $nameA = (string) ($a->relationLoaded('anggotaKelas') && $a->anggotaKelas?->relationLoaded('siswa')
                    ? ($a->anggotaKelas->siswa?->nama_lengkap ?? '')
                    : '');
                $nameB = (string) ($b->relationLoaded('anggotaKelas') && $b->anggotaKelas?->relationLoaded('siswa')
                    ? ($b->anggotaKelas->siswa?->nama_lengkap ?? '')
                    : '');

                return strnatcasecmp($nameA, $nameB);
            })->values()->all()
        );
    }

    /**
     * Memuat entri yaumiyah pada tanggal yang dipilih, di-key by anggota_t2q_id tanpa N+1.
     *
     * @param  list<int>  $anggotaIds
     * @return EloquentCollection<int, Model>
     */
    private function getTodayEntriesByAnggota(array $anggotaIds, string $tanggal): EloquentCollection
    {
        if ($anggotaIds === []) {
            return new EloquentCollection;
        }

        $modelClass = $this->modelClass();

        return $modelClass::query()
            ->withMasterRelations()
            ->forAnggotaT2qIds($anggotaIds)
            ->forTanggal($tanggal)
            ->get()
            ->keyBy('anggota_t2q_id');
    }

    /**
     * Mengambil catatan terakhir sebelum tanggal dipilih untuk auto-prefill form tanpa N+1.
     *
     * @param  list<int>  $anggotaIds
     * @return EloquentCollection<int, Model>
     */
    private function getLatestPreviousEntriesByAnggota(array $anggotaIds, string $beforeDate): EloquentCollection
    {
        if ($anggotaIds === []) {
            return new EloquentCollection;
        }

        $modelClass = $this->modelClass();

        $latestDateByAnggota = $modelClass::query()
            ->selectRaw('anggota_t2q_id, MAX(tanggal) as max_tanggal')
            ->whereIn('anggota_t2q_id', $anggotaIds)
            ->whereDate('tanggal', '<', $beforeDate)
            ->groupBy('anggota_t2q_id')
            ->pluck('max_tanggal', 'anggota_t2q_id');

        if ($latestDateByAnggota->isEmpty()) {
            return new EloquentCollection;
        }

        $normalizedTargetDates = [];
        $distinctDates = [];

        foreach ($latestDateByAnggota as $anggotaId => $rawDate) {
            $dateStr = substr((string) $rawDate, 0, 10);
            if ($dateStr === '') {
                continue;
            }
            $normalizedTargetDates[(int) $anggotaId] = $dateStr;
            $distinctDates[$dateStr] = true;
        }

        if ($normalizedTargetDates === []) {
            return new EloquentCollection;
        }

        $candidates = $modelClass::query()
            ->withMasterRelations()
            ->whereIn('anggota_t2q_id', array_keys($normalizedTargetDates))
            ->whereIn('tanggal', array_keys($distinctDates))
            ->get();

        $matched = new EloquentCollection;
        foreach ($candidates as $candidate) {
            $anggotaId = (int) $candidate->anggota_t2q_id;
            $candidateDate = $candidate->tanggal?->toDateString() ?? substr((string) $candidate->tanggal, 0, 10);

            if (($normalizedTargetDates[$anggotaId] ?? null) === $candidateDate) {
                $matched->put($anggotaId, $candidate);
            }
        }

        return $matched;
    }

    /**
     * Memuat seluruh daftar opsi master dengan kolom minimal dalam satu query per tabel.
     *
     * @return array{
     *     daftarJilidList: EloquentCollection<int, DaftarJilid>,
     *     surahList: EloquentCollection<int, Surah>,
     *     angkaArabList: EloquentCollection<int, AngkaArab>
     * }
     */
    private function getMasterOptions(): array
    {
        return [
            'daftarJilidList' => $this->usesDaftarJilid()
                ? DaftarJilid::query()
                    ->select(['id', 'jilid_latin', 'jilid_arab', 'jenis', 'urutan'])
                    ->orderBy('urutan')
                    ->orderBy('id')
                    ->get()
                : new EloquentCollection,
            'surahList' => Surah::query()
                ->select(['id', 'nama_surah', 'nama_surah_arab', 'jumlah_ayat'])
                ->orderBy('id')
                ->get(),
            'angkaArabList' => AngkaArab::query()
                ->select(['id', 'angka_latin', 'angka_arab'])
                ->orderBy('id')
                ->get(),
        ];
    }

    /**
     * Memuat peta validasi ID master satu kali sebelum loop penyimpanan massal.
     *
     * @return array{
     *     jilidJenisMap: array<int, string>,
     *     surahAyatMap: array<int, int>,
     *     angkaLatinMap: array<int, string>
     * }
     */
    private function loadValidationLookupMaps(): array
    {
        return [
            'jilidJenisMap' => $this->usesDaftarJilid()
                ? DaftarJilid::query()->pluck('jenis', 'id')->all()
                : [],
            'surahAyatMap' => Surah::query()->pluck('jumlah_ayat', 'id')->all(),
            'angkaLatinMap' => AngkaArab::query()->pluck('angka_latin', 'id')->all(),
        ];
    }

    /**
     * @param  list<string>  $availableTingkatKeys
     */
    private function resolveSelectedTingkat(string $requestedTingkat, array $availableTingkatKeys): string
    {
        if ($availableTingkatKeys === []) {
            return '';
        }

        if ($requestedTingkat !== '') {
            foreach ($availableTingkatKeys as $key) {
                if (strcasecmp($key, $requestedTingkat) === 0) {
                    return $key;
                }
            }
        }

        return $availableTingkatKeys[0];
    }

    /**
     * Memuat konteks filter bersama (tahun ajaran aktif, guru, tingkat, daftar siswa, dan target capaian).
     *
     * @param  array<string, mixed>  $input
     * @param  list<int>|null  $scopedKelasIds
     * @return array{
     *     activeTahunAjaran: ?TahunAjaran,
     *     anggotaIds: list<int>,
     *     payload: array<string, mixed>
     * }
     */
    private function resolveBaseContext(array $input, ?array $scopedKelasIds = null): array
    {
        $activeTahunAjaran = $this->resolveActiveTahunAjaran();
        $tanggal = $this->resolveDate($input['tanggal'] ?? null);
        $selectedGuruNipy = trim((string) ($input['karyawan_nipy'] ?? ''));
        $requestedTingkat = AnggotaT2q::normalizeTingkat($input['tingkat'] ?? '');

        if ($activeTahunAjaran === null) {
            return [
                'activeTahunAjaran' => null,
                'anggotaIds' => [],
                'payload' => [
                    'mode' => $this->modeKey(),
                    'usesDaftarJilid' => $this->usesDaftarJilid(),
                    'activeTahunAjaran' => null,
                    'tanggal' => $tanggal,
                    'tanggalFormatted' => Carbon::parse($tanggal)->locale('id')->translatedFormat('l, d F Y'),
                    'guruOptions' => new EloquentCollection,
                    'selectedGuruNipy' => $selectedGuruNipy,
                    'tingkatTabs' => [],
                    'selectedTingkat' => $requestedTingkat,
                    'students' => new EloquentCollection,
                    'targetCapaian' => new EloquentCollection,
                ],
            ];
        }

        $guruOptions = $this->getGuruOptions($activeTahunAjaran->id, $scopedKelasIds);
        if ($selectedGuruNipy !== '' && ! $guruOptions->contains('nipy', $selectedGuruNipy)) {
            $selectedGuruNipy = '';
        }

        $tingkatTabs = $this->getTingkatSummary($activeTahunAjaran->id, $selectedGuruNipy, $scopedKelasIds);
        $selectedTingkat = $this->resolveSelectedTingkat($requestedTingkat, array_keys($tingkatTabs));

        $students = $this->getStudents($activeTahunAjaran->id, $selectedGuruNipy, $selectedTingkat, true, $scopedKelasIds);
        $anggotaIds = $students->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all();

        $targetCapaian = $selectedTingkat !== ''
            ? TargetCapaian::query()
                ->with([
                    'daftarJilid:id,jilid_latin,jilid_arab,jenis',
                    'surah:id,nama_surah,nama_surah_arab,jumlah_ayat',
                ])
                ->forTahunAjaran($activeTahunAjaran->id)
                ->forTingkat($selectedTingkat)
                ->orderBy('id')
                ->get()
            : new EloquentCollection;

        return [
            'activeTahunAjaran' => $activeTahunAjaran,
            'anggotaIds' => $anggotaIds,
            'payload' => [
                'mode' => $this->modeKey(),
                'usesDaftarJilid' => $this->usesDaftarJilid(),
                'activeTahunAjaran' => $activeTahunAjaran,
                'tanggal' => $tanggal,
                'tanggalFormatted' => Carbon::parse($tanggal)->locale('id')->translatedFormat('l, d F Y'),
                'guruOptions' => $guruOptions,
                'selectedGuruNipy' => $selectedGuruNipy,
                'tingkatTabs' => $tingkatTabs,
                'selectedTingkat' => $selectedTingkat,
                'students' => $students,
                'targetCapaian' => $targetCapaian,
            ],
        ];
    }

    /**
     * Menghitung periode efektif T2Q (Senin – Kamis) dari tanggal acuan yang dipilih.
     *
     * @return array{
     *     anchor_date: string,
     *     week_start: string,
     *     week_end: string,
     *     prev_week_date: string,
     *     next_week_date: string,
     *     current_week_date: string,
     *     is_current_week: bool,
     *     range_label: string,
     *     days: list<array{
     *         date: string,
     *         day_name: string,
     *         short_day: string,
     *         date_label: string,
     *         full_label: string,
     *         is_today: bool
     *     }>
     * }
     */
    public function resolveWeekPeriod(string $anchorDate): array
    {
        $anchor = Carbon::parse($this->resolveDate($anchorDate));
        $weekStart = $anchor->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->addDays(3);
        $todayDate = Carbon::today()->toDateString();
        $currentWeekStart = Carbon::today()->startOfWeek(Carbon::MONDAY)->toDateString();

        $days = [];
        for ($i = 0; $i < 4; $i++) {
            $day = $weekStart->copy()->addDays($i);
            $dayLocalized = $day->copy()->locale('id');
            $dateStr = $day->toDateString();

            $days[] = [
                'date' => $dateStr,
                'day_name' => $dayLocalized->translatedFormat('l'),
                'short_day' => $dayLocalized->translatedFormat('D'),
                'date_label' => $dayLocalized->translatedFormat('d M'),
                'full_label' => $dayLocalized->translatedFormat('l, d M Y'),
                'is_today' => $dateStr === $todayDate,
            ];
        }

        return [
            'anchor_date' => $weekStart->toDateString(),
            'week_start' => $weekStart->toDateString(),
            'week_end' => $weekEnd->toDateString(),
            'prev_week_date' => $weekStart->copy()->subWeek()->toDateString(),
            'next_week_date' => $weekStart->copy()->addWeek()->toDateString(),
            'current_week_date' => $currentWeekStart,
            'is_current_week' => $weekStart->toDateString() === $currentWeekStart,
            'range_label' => $this->formatWeekRangeLabel($weekStart, $weekEnd),
            'days' => $days,
        ];
    }

    /**
     * Memuat seluruh entri yaumiyah dalam rentang Senin–Kamis dalam satu query dan mengindeks berdasarkan [anggota_t2q_id][tanggal].
     *
     * @param  list<int>  $anggotaIds
     * @return array<int, array<string, Model>>
     */
    private function getEntriesByAnggotaAndDate(array $anggotaIds, string $startDate, string $endDate): array
    {
        if ($anggotaIds === []) {
            return [];
        }

        $modelClass = $this->modelClass();
        $records = $modelClass::query()
            ->withMasterRelations()
            ->forAnggotaT2qIds($anggotaIds)
            ->forDateRange($startDate, $endDate)
            ->get();

        $indexed = [];
        foreach ($records as $record) {
            $dateStr = $record->tanggal?->toDateString() ?? substr((string) $record->tanggal, 0, 10);
            if ($dateStr === '') {
                continue;
            }
            $indexed[(int) $record->anggota_t2q_id][$dateStr] = $record;
        }

        return $indexed;
    }

    /**
     * Menyusun baris dan ringkasan matriks Senin–Kamis dari koleksi siswa & entri tanpa lazy loading.
     *
     * @param  EloquentCollection<int, AnggotaT2q>  $students
     * @param  array<int, array<string, Model>>  $entriesByAnggotaDate
     * @param  array<string, mixed>  $period
     * @return array<string, mixed>
     */
    private function buildWeeklyMatrix(EloquentCollection $students, array $entriesByAnggotaDate, array $period): array
    {
        $days = $period['days'];
        $dayFullLabelMap = [];
        $dailyTotals = [];

        foreach ($days as $dayInfo) {
            $dateKey = $dayInfo['date'];
            $dayFullLabelMap[$dateKey] = $dayInfo['full_label'];
            $dailyTotals[$dateKey] = [
                'recorded_count' => 0,
                'score_sum' => 0.0,
                'score_count' => 0,
                'avg_formatted' => '-',
            ];
        }

        $rows = [];
        $totalRecorded = 0;
        $overallScoreSum = 0.0;
        $overallScoreCount = 0;
        $modelClass = $this->modelClass();

        foreach ($students as $anggota) {
            $studentEntries = $entriesByAnggotaDate[(int) $anggota->id] ?? [];
            $cells = [];
            $studentRecorded = 0;
            $studentScoreSum = 0.0;
            $studentScoreCount = 0;

            foreach ($days as $dayInfo) {
                $dateKey = $dayInfo['date'];
                $entry = $studentEntries[$dateKey] ?? null;

                if ($entry === null) {
                    $cells[$dateKey] = null;

                    continue;
                }

                $cell = $this->buildMatrixCellPayload($entry, $dayFullLabelMap[$dateKey] ?? $dateKey);
                $cells[$dateKey] = $cell;
                $studentRecorded++;
                $totalRecorded++;
                $dailyTotals[$dateKey]['recorded_count']++;

                if ($cell['nilai'] !== null) {
                    $score = (float) $cell['nilai'];
                    $studentScoreSum += $score;
                    $studentScoreCount++;
                    $dailyTotals[$dateKey]['score_sum'] += $score;
                    $dailyTotals[$dateKey]['score_count']++;
                    $overallScoreSum += $score;
                    $overallScoreCount++;
                }
            }

            $studentAvg = $studentScoreCount > 0 ? round($studentScoreSum / $studentScoreCount, 2) : null;

            $rows[] = [
                'anggota' => $anggota,
                'cells' => $cells,
                'recorded_count' => $studentRecorded,
                'avg_nilai' => $studentAvg,
                'avg_formatted' => $this->formatScore($studentAvg),
                'avg_tier' => $studentAvg !== null ? $modelClass::tierFromScore($studentAvg) : 'neutral',
            ];
        }

        $studentCount = count($rows);
        foreach ($dailyTotals as $dateKey => $dayStat) {
            $dayAvg = $dayStat['score_count'] > 0
                ? round($dayStat['score_sum'] / $dayStat['score_count'], 1)
                : null;
            $dailyTotals[$dateKey]['avg_formatted'] = $this->formatScore($dayAvg);
            $dailyTotals[$dateKey]['completion_pct'] = $studentCount > 0
                ? round(($dayStat['recorded_count'] / $studentCount) * 100, 0)
                : 0;
        }

        $possibleSlots = $studentCount * count($days);
        $overallAvg = $overallScoreCount > 0 ? round($overallScoreSum / $overallScoreCount, 1) : null;

        return [
            'days' => $days,
            'rows' => $rows,
            'daily_totals' => $dailyTotals,
            'summary' => [
                'total_students' => $studentCount,
                'total_recorded' => $totalRecorded,
                'possible_slots' => $possibleSlots,
                'completion_pct' => $possibleSlots > 0 ? round(($totalRecorded / $possibleSlots) * 100, 1) : 0.0,
                'overall_avg_formatted' => $this->formatScore($overallAvg),
                'overall_avg_tier' => $overallAvg !== null ? $modelClass::tierFromScore($overallAvg) : 'neutral',
            ],
        ];
    }

    /**
     * Menyusun payload terstruktur untuk satu sel catatan matriks tanpa memicu lazy loading.
     *
     * @return array<string, mixed>
     */
    private function buildMatrixCellPayload(Model $entry, string $fullDateLabel): array
    {
        $daftarJilid = (method_exists($entry, 'daftarJilid') && $entry->relationLoaded('daftarJilid'))
            ? $entry->daftarJilid
            : null;
        $surah = $entry->relationLoaded('surah') ? $entry->surah : null;
        $angkaArab = $entry->relationLoaded('angkaArab') ? $entry->angkaArab : null;

        $isQuran = ($surah !== null)
            || ($daftarJilid !== null && strtolower(trim((string) $daftarJilid->jenis)) === 'quran');

        if ($this->usesDaftarJilid()) {
            $primaryLabel = ($isQuran && $surah !== null)
                ? (string) $surah->nama_surah
                : (string) ($daftarJilid?->jilid_latin ?? 'Setoran Tahsin');
            $contextLabel = ($isQuran && $surah !== null && $daftarJilid !== null)
                ? (string) $daftarJilid->jilid_latin
                : '';
            $arabTitle = ($isQuran && $surah !== null)
                ? (string) ($surah->nama_surah_arab ?? '')
                : (string) ($daftarJilid?->jilid_arab ?? '');
        } else {
            $primaryLabel = (string) ($surah?->nama_surah ?? 'Setoran Tahfizh');
            $contextLabel = '';
            $arabTitle = (string) ($surah?->nama_surah_arab ?? '');
        }

        $unit = $isQuran ? 'Ayat' : 'Hal.';
        $secondaryLabel = ($angkaArab !== null && filled($angkaArab->angka_latin))
            ? "{$unit} {$angkaArab->angka_latin}"
            : '';

        return [
            'id' => (int) ($entry->id ?? 0),
            'tanggal' => $entry->tanggal?->toDateString() ?? substr((string) $entry->tanggal, 0, 10),
            'tanggal_label' => $fullDateLabel,
            'context_label' => $contextLabel,
            'primary_label' => $primaryLabel,
            'arab_title' => $arabTitle,
            'secondary_label' => $secondaryLabel,
            'arab_number' => (string) ($angkaArab?->angka_arab ?? ''),
            'ringkasan' => $entry->ringkasan_capaian,
            'nilai' => $entry->nilai !== null ? (float) $entry->nilai : null,
            'nilai_formatted' => $entry->nilai_formatted,
            'nilai_tier' => $entry->nilai_tier,
            'nilai_label' => $entry->nilai_label,
        ];
    }

    private function formatScore(?float $score): string
    {
        if ($score === null) {
            return '-';
        }

        return rtrim(rtrim(number_format($score, 2, '.', ''), '0'), '.');
    }

    private function formatWeekRangeLabel(Carbon $weekStart, Carbon $weekEnd): string
    {
        $startId = $weekStart->copy()->locale('id');
        $endId = $weekEnd->copy()->locale('id');

        if ($weekStart->format('Y-m') === $weekEnd->format('Y-m')) {
            return $startId->format('d').' – '.$endId->translatedFormat('d F Y');
        }

        if ($weekStart->format('Y') === $weekEnd->format('Y')) {
            return $startId->translatedFormat('d M').' – '.$endId->translatedFormat('d M Y');
        }

        return $startId->translatedFormat('d M Y').' – '.$endId->translatedFormat('d M Y');
    }

    /**
     * Memvalidasi tingkat opsional (mengembalikan '' jika semua tingkat).
     *
     * @param  list<string>  $availableTingkatKeys
     */
    private function resolveOptionalTingkat(string $requestedTingkat, array $availableTingkatKeys): string
    {
        if ($requestedTingkat === '') {
            return '';
        }

        foreach ($availableTingkatKeys as $key) {
            if (strcasecmp($key, $requestedTingkat) === 0) {
                return $key;
            }
        }

        return '';
    }

    /**
     * Memuat seluruh entri yaumiyah untuk himpunan anggota T2Q dalam satu query tanpa N+1.
     *
     * @param  list<int>  $anggotaIds
     * @return EloquentCollection<int, Model>
     */
    private function getEntriesForStatistik(array $anggotaIds): EloquentCollection
    {
        if ($anggotaIds === []) {
            return new EloquentCollection;
        }

        $columns = $this->usesDaftarJilid()
            ? [
                'id',
                'anggota_t2q_id',
                'akademik_master_t2q_daftar_jilid_id',
                'akademik_master_t2q_surah_id',
                'akademik_master_t2q_angka_arab_id',
                'tanggal',
                'nilai',
            ]
            : [
                'id',
                'anggota_t2q_id',
                'akademik_master_t2q_surah_id',
                'akademik_master_t2q_angka_arab_id',
                'tanggal',
                'nilai',
            ];

        $modelClass = $this->modelClass();

        return $modelClass::query()
            ->select($columns)
            ->withMasterRelations()
            ->forAnggotaT2qIds($anggotaIds)
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();
    }

    /**
     * Merakit statistik komprehensif Tahun Ajaran (ringkasan, tren bulanan, distribusi predikat & materi, per tingkat, per guru, dan per siswa).
     *
     * @param  EloquentCollection<int, AnggotaT2q>  $students
     * @param  EloquentCollection<int, Model>  $entries
     * @param  Collection<string, Collection<int, TargetCapaian>>  $targetCapaianByTingkat
     * @return array<string, mixed>
     */
    private function buildAnnualStatistik(
        TahunAjaran $tahunAjaran,
        EloquentCollection $students,
        EloquentCollection $entries,
        Collection $targetCapaianByTingkat
    ): array {
        $modelClass = $this->modelClass();
        $monthBuckets = $this->initializeMonthlyStatBuckets($tahunAjaran);

        $studentMeta = [];
        $tingkatBuckets = [];
        $guruBuckets = [];
        $studentBuckets = [];

        foreach ($students as $anggota) {
            $anggotaId = (int) $anggota->id;
            $tingkat = AnggotaT2q::normalizeTingkat($anggota->tingkat ?? '');
            $tingkatKey = $tingkat !== '' ? $tingkat : '-';

            $nipy = trim((string) ($anggota->karyawan_nipy ?? ''));
            $guru = $anggota->relationLoaded('guruT2q') ? $anggota->guruT2q : null;
            $guruKey = $nipy !== '' ? $nipy : '__none__';

            if (! isset($tingkatBuckets[$tingkatKey])) {
                $targetsForTingkat = $targetCapaianByTingkat->get($tingkatKey, collect())
                    ->map(static fn (TargetCapaian $tc): string => $tc->ringkasan_target)
                    ->filter(static fn (string $label): bool => $label !== '-')
                    ->values()
                    ->all();

                $tingkatBuckets[$tingkatKey] = [
                    'tingkat' => $tingkatKey,
                    'target_labels' => $targetsForTingkat,
                    'student_count' => 0,
                    'active_students_map' => [],
                    'student_scores' => [],
                    'achieved_count' => 0,
                    'unachieved_count' => 0,
                    'no_target_count' => 0,
                    'counter' => $this->initStatCounter(),
                ];
            }
            $tingkatBuckets[$tingkatKey]['student_count']++;

            if (! isset($guruBuckets[$guruKey])) {
                $guruBuckets[$guruKey] = [
                    'nipy' => $nipy,
                    'nama_guru' => $guru?->nama_gelar ?? ($nipy !== '' ? $nipy : 'Belum Ditentukan'),
                    'tingkat_set' => [],
                    'student_count' => 0,
                    'active_students_map' => [],
                    'student_scores' => [],
                    'counter' => $this->initStatCounter(),
                ];
            }
            $guruBuckets[$guruKey]['student_count']++;
            if ($tingkat !== '') {
                $guruBuckets[$guruKey]['tingkat_set'][$tingkat] = true;
            }

            $studentMeta[$anggotaId] = [
                'tingkat' => $tingkatKey,
                'guru_key' => $guruKey,
            ];

            $studentBuckets[$anggotaId] = [
                'anggota' => $anggota,
                'tingkat' => $tingkatKey,
                'counter' => $this->initStatCounter(),
                'min_nilai' => null,
                'max_nilai' => null,
                'latest_entry' => null,
                'latest_sort_key' => '',
                'recorded_jilid_ids' => [],
                'highest_jilid_urutan' => 0,
                'highest_jilid_label' => '',
                'recorded_surah_ids' => [],
            ];
        }

        $overallStudentScores = [];
        $activeDates = [];
        $materialBuckets = [];
        $totalRecordedCount = 0;

        foreach ($entries as $entry) {
            $anggotaId = (int) $entry->anggota_t2q_id;
            if (! isset($studentBuckets[$anggotaId])) {
                continue;
            }

            $totalRecordedCount++;
            $dateStr = $entry->tanggal?->toDateString() ?? substr((string) $entry->tanggal, 0, 10);
            $score = $entry->nilai !== null ? (float) $entry->nilai : null;
            $tier = $score !== null ? $modelClass::tierFromScore($score) : null;

            $this->accumulateStudentScore($overallStudentScores, $anggotaId, $score);
            if ($dateStr !== '') {
                $activeDates[$dateStr] = true;
            }

            // Month bucket
            $ym = strlen($dateStr) >= 7 ? substr($dateStr, 0, 7) : '';
            if ($ym !== '') {
                if (! isset($monthBuckets[$ym])) {
                    $monthBuckets[$ym] = $this->createMonthlyStatBucket($ym);
                }
                $this->accumulateStatCounter($monthBuckets[$ym]['counter'], $score, $tier);
                $this->accumulateStudentScore($monthBuckets[$ym]['student_scores'], $anggotaId, $score);
                $monthBuckets[$ym]['unique_students'][$anggotaId] = true;
            }

            // Tingkat & Guru buckets
            $meta = $studentMeta[$anggotaId];
            $tKey = $meta['tingkat'];
            $gKey = $meta['guru_key'];

            $this->accumulateStatCounter($tingkatBuckets[$tKey]['counter'], $score, $tier);
            $this->accumulateStudentScore($tingkatBuckets[$tKey]['student_scores'], $anggotaId, $score);
            $tingkatBuckets[$tKey]['active_students_map'][$anggotaId] = true;

            $this->accumulateStatCounter($guruBuckets[$gKey]['counter'], $score, $tier);
            $this->accumulateStudentScore($guruBuckets[$gKey]['student_scores'], $anggotaId, $score);
            $guruBuckets[$gKey]['active_students_map'][$anggotaId] = true;

            // Student bucket
            $this->accumulateStatCounter($studentBuckets[$anggotaId]['counter'], $score, $tier);
            if ($score !== null) {
                $currMin = $studentBuckets[$anggotaId]['min_nilai'];
                $currMax = $studentBuckets[$anggotaId]['max_nilai'];
                $studentBuckets[$anggotaId]['min_nilai'] = $currMin === null ? $score : min($currMin, $score);
                $studentBuckets[$anggotaId]['max_nilai'] = $currMax === null ? $score : max($currMax, $score);
            }

            $sortKey = sprintf('%s-%010d', $dateStr, (int) ($entry->id ?? 0));
            if ($sortKey >= $studentBuckets[$anggotaId]['latest_sort_key']) {
                $studentBuckets[$anggotaId]['latest_sort_key'] = $sortKey;
                $studentBuckets[$anggotaId]['latest_entry'] = $entry;
            }

            if ($this->usesDaftarJilid() && method_exists($entry, 'daftarJilid') && $entry->relationLoaded('daftarJilid') && $entry->daftarJilid !== null) {
                $jId = (int) $entry->daftarJilid->id;
                $jUrutan = (int) ($entry->daftarJilid->urutan ?? 0);
                $studentBuckets[$anggotaId]['recorded_jilid_ids'][$jId] = true;
                if ($jUrutan >= $studentBuckets[$anggotaId]['highest_jilid_urutan']) {
                    $studentBuckets[$anggotaId]['highest_jilid_urutan'] = $jUrutan;
                    $studentBuckets[$anggotaId]['highest_jilid_label'] = (string) $entry->daftarJilid->jilid_latin;
                }
            }

            if (! $this->usesDaftarJilid() && $entry->relationLoaded('surah') && $entry->surah !== null) {
                $studentBuckets[$anggotaId]['recorded_surah_ids'][(int) $entry->surah->id] = true;
            }

            // Material (Jilid for Tahsin, Surah for Tahfizh)
            $this->accumulateMaterialStat($materialBuckets, $entry, $anggotaId, $score, $tier);
        }

        // Hitung distribusi posisi jilid terkini siswa (khusus Tahsin) & evaluasi pencapaian target per siswa
        $studentRows = [];
        $activeStudentsCount = 0;
        $achievedStudents = [];
        $unachievedStudents = [];
        $achievedCount = 0;
        $unachievedCount = 0;
        $noTargetCount = 0;

        foreach ($studentBuckets as $studentStat) {
            $latest = $studentStat['latest_entry'];
            if ($latest !== null && $this->usesDaftarJilid() && method_exists($latest, 'daftarJilid') && $latest->relationLoaded('daftarJilid') && $latest->daftarJilid !== null) {
                $mKey = 'jilid:'.(int) $latest->daftarJilid->id;
                if (isset($materialBuckets[$mKey])) {
                    $materialBuckets[$mKey]['latest_student_count']++;
                }
            }

            $counter = $this->finalizeStatCounter($studentStat['counter']);
            if ($counter['recorded_count'] > 0) {
                $activeStudentsCount++;
            }

            $tKey = $studentStat['tingkat'];
            $targetsForStudent = $targetCapaianByTingkat->get($tKey, collect());
            $targetEval = $this->evaluateStudentTarget($studentStat, $targetsForStudent);

            if ($targetEval['status'] === 'tercapai') {
                $achievedCount++;
                $tingkatBuckets[$tKey]['achieved_count']++;
            } elseif ($targetEval['status'] === 'belum_tercapai') {
                $unachievedCount++;
                $tingkatBuckets[$tKey]['unachieved_count']++;
            } else {
                $noTargetCount++;
                $tingkatBuckets[$tKey]['no_target_count']++;
            }

            $rowPayload = [
                'anggota' => $studentStat['anggota'],
                'recorded_count' => $counter['recorded_count'],
                'avg_formatted' => $counter['avg_formatted'],
                'avg_tier' => $counter['avg_tier'],
                'avg_label' => $counter['avg_label'],
                'min_formatted' => $this->formatScore($studentStat['min_nilai']),
                'max_formatted' => $this->formatScore($studentStat['max_nilai']),
                'latest_ringkasan' => $latest?->ringkasan_capaian ?? '-',
                'latest_nilai_formatted' => $latest?->nilai_formatted ?? '-',
                'latest_nilai_tier' => $latest?->nilai_tier ?? 'neutral',
                'latest_tanggal_label' => $latest?->tanggal?->copy()->locale('id')->translatedFormat('d M Y') ?? '-',
                'target_status' => $targetEval['status'],
                'target_status_label' => $targetEval['status_label'],
                'target_progress_pct' => $targetEval['progress_pct'],
                'target_summary' => $targetEval['target_summary'],
                'target_detail' => $targetEval['detail_text'],
            ];

            $studentRows[] = $rowPayload;

            if ($targetEval['status'] === 'tercapai') {
                $achievedStudents[] = $rowPayload;
            } elseif ($targetEval['status'] === 'belum_tercapai') {
                $unachievedStudents[] = $rowPayload;
            }
        }

        $monthlyRows = $this->finalizeMonthlyStatRows($monthBuckets);
        $tingkatRows = array_map(function (array $row): array {
            $total = max(0, (int) $row['student_count']);
            $row['achieved_pct'] = $total > 0 ? round(($row['achieved_count'] / $total) * 100, 1) : 0.0;
            $row['unachieved_pct'] = $total > 0 ? round(($row['unachieved_count'] / $total) * 100, 1) : 0.0;
            $row['no_target_pct'] = $total > 0 ? round(($row['no_target_count'] / $total) * 100, 1) : 0.0;

            return $row;
        }, $this->finalizeGroupStatRows($tingkatBuckets, static fn (array $a, array $b): int => AnggotaT2q::compareTingkatOrder($a['tingkat'], $b['tingkat'])));
        $guruRows = $this->finalizeGroupStatRows($guruBuckets, static function (array $a, array $b): int {
            $cmp = $b['student_count'] <=> $a['student_count'];

            return $cmp !== 0 ? $cmp : strnatcasecmp($a['nama_guru'], $b['nama_guru']);
        }, true);

        $totalStudents = $students->count();
        $overallStudentAvg = $this->finalizeStudentAvgScores($overallStudentScores);
        $scoredStudentsCount = $overallStudentAvg['scored_students'];
        $tierCounts = $overallStudentAvg['tier_counts'];

        $tierRows = [];
        foreach (['excellent', 'good', 'fair', 'poor'] as $tierKey) {
            $count = $tierCounts[$tierKey];
            $tierRows[] = [
                'key' => $tierKey,
                'label' => $modelClass::labelForTier($tierKey),
                'count' => $count,
                'pct' => $scoredStudentsCount > 0 ? round(($count / $scoredStudentsCount) * 100, 1) : 0.0,
            ];
        }

        $materialRows = $this->finalizeMaterialRows($materialBuckets);
        $bestMonth = collect($monthlyRows)
            ->filter(static fn (array $row): bool => $row['recorded_count'] > 0)
            ->sortByDesc('recorded_count')
            ->first();

        $evaluatedCount = $achievedCount + $unachievedCount;
        $targetAchievement = [
            'achieved_count' => $achievedCount,
            'unachieved_count' => $unachievedCount,
            'no_target_count' => $noTargetCount,
            'evaluated_count' => $evaluatedCount,
            'achieved_pct' => $totalStudents > 0 ? round(($achievedCount / $totalStudents) * 100, 1) : 0.0,
            'evaluated_achieved_pct' => $evaluatedCount > 0 ? round(($achievedCount / $evaluatedCount) * 100, 1) : 0.0,
            'donut_segments' => $this->buildTargetDonutSegments($achievedCount, $unachievedCount, $noTargetCount, $totalStudents),
            'achieved_students' => $achievedStudents,
            'unachieved_students' => $unachievedStudents,
        ];

        return [
            'summary' => [
                'total_students' => $totalStudents,
                'active_students' => $activeStudentsCount,
                'scored_students' => $scoredStudentsCount,
                'inactive_students' => max(0, $totalStudents - $activeStudentsCount),
                'student_activity_pct' => $totalStudents > 0 ? round(($activeStudentsCount / $totalStudents) * 100, 1) : 0.0,
                'total_recorded' => $totalRecordedCount,
                'active_days_count' => count($activeDates),
                'avg_per_student' => $totalStudents > 0 ? round($totalRecordedCount / $totalStudents, 1) : 0.0,
                'overall_avg_formatted' => $overallStudentAvg['avg_formatted'],
                'overall_avg_tier' => $overallStudentAvg['avg_tier'],
                'overall_avg_label' => $overallStudentAvg['avg_label'],
                'excellent_count' => $tierCounts['excellent'],
                'excellent_pct' => $overallStudentAvg['excellent_pct'],
                'best_month_label' => $bestMonth['month_full'] ?? '-',
                'best_month_count' => (int) ($bestMonth['recorded_count'] ?? 0),
            ],
            'target_achievement' => $targetAchievement,
            'tier_rows' => $tierRows,
            'monthly_rows' => $monthlyRows,
            'material_rows' => $materialRows,
            'tingkat_rows' => $tingkatRows,
            'guru_rows' => $guruRows,
            'student_rows' => $studentRows,
        ];
    }

    /**
     * @return array{recorded_count: int, score_sum: float, score_count: int, excellent_count: int}
     */
    private function initStatCounter(): array
    {
        return [
            'recorded_count' => 0,
            'score_sum' => 0.0,
            'score_count' => 0,
            'excellent_count' => 0,
        ];
    }

    /**
     * @param  array{recorded_count: int, score_sum: float, score_count: int, excellent_count: int}  $counter
     */
    private function accumulateStatCounter(array &$counter, ?float $score, ?string $tier): void
    {
        $counter['recorded_count']++;
        if ($score !== null) {
            $counter['score_sum'] += $score;
            $counter['score_count']++;
        }
        if ($tier === 'excellent') {
            $counter['excellent_count']++;
        }
    }

    /**
     * @param  array{recorded_count: int, score_sum: float, score_count: int, excellent_count: int}  $counter
     * @return array{recorded_count: int, avg_nilai: ?float, avg_formatted: string, avg_tier: string, avg_label: string, excellent_pct: float}
     */
    private function finalizeStatCounter(array $counter): array
    {
        $modelClass = $this->modelClass();
        $avg = $counter['score_count'] > 0 ? round($counter['score_sum'] / $counter['score_count'], 1) : null;
        $tier = $avg !== null ? $modelClass::tierFromScore($avg) : 'neutral';

        return [
            'recorded_count' => $counter['recorded_count'],
            'avg_nilai' => $avg,
            'avg_formatted' => $this->formatScore($avg),
            'avg_tier' => $tier,
            'avg_label' => $avg !== null ? $modelClass::labelForTier($tier) : 'Belum Ada Data',
            'excellent_pct' => $counter['score_count'] > 0
                ? round(($counter['excellent_count'] / $counter['score_count']) * 100, 1)
                : 0.0,
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function initializeMonthlyStatBuckets(TahunAjaran $tahunAjaran): array
    {
        $buckets = [];
        foreach ($tahunAjaran->academicMonthKeys() as $ym) {
            $buckets[$ym] = $this->createMonthlyStatBucket($ym);
        }

        return $buckets;
    }

    /**
     * Mengakumulasi skor harian ke dalam bucket per siswa agar rata-rata dihitung per siswa terlebih dahulu.
     *
     * @param  array<int, array{sum: float, count: int}>  $studentScores
     */
    private function accumulateStudentScore(array &$studentScores, int $anggotaId, ?float $score): void
    {
        if ($score === null) {
            return;
        }

        if (! isset($studentScores[$anggotaId])) {
            $studentScores[$anggotaId] = ['sum' => 0.0, 'count' => 0];
        }

        $studentScores[$anggotaId]['sum'] += $score;
        $studentScores[$anggotaId]['count']++;
    }

    /**
     * Menghitung rata-rata nilai dan distribusi predikat berbasis rata-rata tiap siswa terlebih dahulu (bukan per hari).
     *
     * @param  array<int, array{sum: float, count: int}>  $studentScores
     * @return array{
     *     scored_students: int,
     *     avg_nilai: ?float,
     *     avg_formatted: string,
     *     avg_tier: string,
     *     avg_label: string,
     *     tier_counts: array{excellent: int, good: int, fair: int, poor: int},
     *     excellent_pct: float
     * }
     */
    private function finalizeStudentAvgScores(array $studentScores): array
    {
        $modelClass = $this->modelClass();
        $tierCounts = ['excellent' => 0, 'good' => 0, 'fair' => 0, 'poor' => 0];
        $sumOfStudentAverages = 0.0;
        $scoredStudents = 0;

        foreach ($studentScores as $scoreBucket) {
            if ($scoreBucket['count'] <= 0) {
                continue;
            }

            $studentAvg = $scoreBucket['sum'] / $scoreBucket['count'];
            $sumOfStudentAverages += $studentAvg;
            $scoredStudents++;
            $tierCounts[$modelClass::tierFromScore($studentAvg)]++;
        }

        $avg = $scoredStudents > 0 ? round($sumOfStudentAverages / $scoredStudents, 1) : null;
        $tier = $avg !== null ? $modelClass::tierFromScore($avg) : 'neutral';

        return [
            'scored_students' => $scoredStudents,
            'avg_nilai' => $avg,
            'avg_formatted' => $this->formatScore($avg),
            'avg_tier' => $tier,
            'avg_label' => $avg !== null ? $modelClass::labelForTier($tier) : 'Belum Ada Data',
            'tier_counts' => $tierCounts,
            'excellent_pct' => $scoredStudents > 0
                ? round(($tierCounts['excellent'] / $scoredStudents) * 100, 1)
                : 0.0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createMonthlyStatBucket(string $ym): array
    {
        $date = Carbon::createFromFormat('Y-m', $ym)->startOfMonth()->locale('id');

        return [
            'month_key' => $ym,
            'month_label' => $date->translatedFormat('M'),
            'month_full' => $date->translatedFormat('F Y'),
            'unique_students' => [],
            'student_scores' => [],
            'counter' => $this->initStatCounter(),
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $monthBuckets
     * @return list<array<string, mixed>>
     */
    private function finalizeMonthlyStatRows(array $monthBuckets): array
    {
        ksort($monthBuckets);

        $maxRecorded = 0;
        foreach ($monthBuckets as $bucket) {
            $maxRecorded = max($maxRecorded, (int) $bucket['counter']['recorded_count']);
        }

        $rows = [];
        foreach ($monthBuckets as $bucket) {
            $recorded = (int) $bucket['counter']['recorded_count'];
            $studentAvg = $this->finalizeStudentAvgScores($bucket['student_scores'] ?? []);
            $rows[] = [
                'month_key' => $bucket['month_key'],
                'month_label' => $bucket['month_label'],
                'month_full' => $bucket['month_full'],
                'recorded_count' => $recorded,
                'unique_students_count' => count($bucket['unique_students']),
                'avg_formatted' => $studentAvg['avg_formatted'],
                'avg_tier' => $studentAvg['avg_tier'],
                'bar_height_pct' => $maxRecorded > 0 ? max($recorded > 0 ? 8 : 0, (int) round(($recorded / $maxRecorded) * 100)) : 0,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, array<string, mixed>>  $buckets
     * @param  callable(array<string, mixed>, array<string, mixed>): int  $sorter
     * @return list<array<string, mixed>>
     */
    private function finalizeGroupStatRows(array $buckets, callable $sorter, bool $withTingkatList = false): array
    {
        uasort($buckets, $sorter);

        $rows = [];
        foreach ($buckets as $bucket) {
            $studentAvg = $this->finalizeStudentAvgScores($bucket['student_scores'] ?? []);
            $item = [
                ...$bucket,
                'active_students' => count($bucket['active_students_map']),
                'recorded_count' => (int) $bucket['counter']['recorded_count'],
                'avg_nilai' => $studentAvg['avg_nilai'],
                'avg_formatted' => $studentAvg['avg_formatted'],
                'avg_tier' => $studentAvg['avg_tier'],
                'avg_label' => $studentAvg['avg_label'],
                'excellent_pct' => $studentAvg['excellent_pct'],
            ];
            unset($item['active_students_map'], $item['student_scores'], $item['counter']);

            if ($withTingkatList) {
                $tingkats = array_keys($bucket['tingkat_set']);
                usort($tingkats, static fn (string $a, string $b): int => AnggotaT2q::compareTingkatOrder($a, $b));
                $item['tingkat_list'] = $tingkats;
                unset($item['tingkat_set']);
            }

            $rows[] = $item;
        }

        return $rows;
    }

    /**
     * @param  array<string, array<string, mixed>>  $materialBuckets
     */
    private function accumulateMaterialStat(array &$materialBuckets, Model $entry, int $anggotaId, ?float $score, ?string $tier): void
    {
        if ($this->usesDaftarJilid() && method_exists($entry, 'daftarJilid') && $entry->relationLoaded('daftarJilid') && $entry->daftarJilid !== null) {
            $jilid = $entry->daftarJilid;
            $key = 'jilid:'.(int) $jilid->id;
            if (! isset($materialBuckets[$key])) {
                $materialBuckets[$key] = [
                    'key' => $key,
                    'label' => (string) $jilid->jilid_latin,
                    'arab_label' => (string) ($jilid->jilid_arab ?? ''),
                    'order' => (int) ($jilid->urutan ?? 9999),
                    'latest_student_count' => 0,
                    'student_scores' => [],
                    'counter' => $this->initStatCounter(),
                ];
            }
            $this->accumulateStatCounter($materialBuckets[$key]['counter'], $score, $tier);
            $this->accumulateStudentScore($materialBuckets[$key]['student_scores'], $anggotaId, $score);

            return;
        }

        if ($entry->relationLoaded('surah') && $entry->surah !== null) {
            $surah = $entry->surah;
            $key = 'surah:'.(int) $surah->id;
            if (! isset($materialBuckets[$key])) {
                $materialBuckets[$key] = [
                    'key' => $key,
                    'label' => (string) $surah->nama_surah,
                    'arab_label' => (string) ($surah->nama_surah_arab ?? ''),
                    'order' => (int) $surah->id,
                    'latest_student_count' => 0,
                    'student_scores' => [],
                    'counter' => $this->initStatCounter(),
                ];
            }
            $this->accumulateStatCounter($materialBuckets[$key]['counter'], $score, $tier);
            $this->accumulateStudentScore($materialBuckets[$key]['student_scores'], $anggotaId, $score);
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $materialBuckets
     * @return list<array<string, mixed>>
     */
    private function finalizeMaterialRows(array $materialBuckets): array
    {
        if ($materialBuckets === []) {
            return [];
        }

        $isTahsin = $this->usesDaftarJilid();
        uasort($materialBuckets, static function (array $a, array $b) use ($isTahsin): int {
            if ($isTahsin) {
                return ($a['order'] <=> $b['order']) ?: strnatcasecmp($a['label'], $b['label']);
            }

            return (count($b['student_scores']) <=> count($a['student_scores']))
                ?: ($b['counter']['recorded_count'] <=> $a['counter']['recorded_count'])
                ?: ($a['order'] <=> $b['order']);
        });

        $sliced = $isTahsin ? $materialBuckets : array_slice($materialBuckets, 0, 8, true);
        $maxStudents = 0;
        foreach ($sliced as $item) {
            $maxStudents = max($maxStudents, count($item['student_scores']));
        }

        $rows = [];
        foreach ($sliced as $item) {
            $studentAvg = $this->finalizeStudentAvgScores($item['student_scores']);
            $uniqueStudents = count($item['student_scores']);
            $rows[] = [
                'label' => $item['label'],
                'arab_label' => $item['arab_label'],
                'unique_students_count' => $uniqueStudents,
                'latest_student_count' => $item['latest_student_count'],
                'recorded_count' => (int) $item['counter']['recorded_count'],
                'avg_formatted' => $studentAvg['avg_formatted'],
                'avg_tier' => $studentAvg['avg_tier'],
                'width_pct' => $maxStudents > 0 ? max(6, (int) round(($uniqueStudents / $maxStudents) * 100)) : 0,
            ];
        }

        return $rows;
    }

    /**
     * Mengevaluasi apakah seorang siswa sudah mencapai target capaian pada tingkatnya tanpa lazy loading.
     *
     * @param  array<string, mixed>  $studentStat
     * @param  Collection<int, TargetCapaian>  $targetsForTingkat
     * @return array{
     *     status: string,
     *     status_label: string,
     *     progress_pct: ?int,
     *     target_summary: string,
     *     detail_text: string
     * }
     */
    private function evaluateStudentTarget(array $studentStat, Collection $targetsForTingkat): array
    {
        if ($this->usesDaftarJilid()) {
            return $this->evaluateTahsinTargets($studentStat, $targetsForTingkat);
        }

        return $this->evaluateTahfizhTargets($studentStat, $targetsForTingkat);
    }

    /**
     * @param  array<string, mixed>  $studentStat
     * @param  Collection<int, TargetCapaian>  $targetsForTingkat
     * @return array{status: string, status_label: string, progress_pct: ?int, target_summary: string, detail_text: string}
     */
    private function evaluateTahsinTargets(array $studentStat, Collection $targetsForTingkat): array
    {
        $targetJilids = $targetsForTingkat
            ->filter(static fn (TargetCapaian $tc): bool => $tc->relationLoaded('daftarJilid') && $tc->daftarJilid !== null)
            ->map(static fn (TargetCapaian $tc): DaftarJilid => $tc->daftarJilid)
            ->unique('id')
            ->values();

        if ($targetJilids->isEmpty()) {
            return [
                'status' => 'tanpa_target',
                'status_label' => 'Belum Ada Target',
                'progress_pct' => null,
                'target_summary' => 'Belum ada target jilid',
                'detail_text' => 'Target jilid belum diatur pada tingkat ini',
            ];
        }

        $recordedJilidIds = $studentStat['recorded_jilid_ids'] ?? [];
        $highestUrutan = (int) ($studentStat['highest_jilid_urutan'] ?? 0);
        $highestLabel = (string) ($studentStat['highest_jilid_label'] ?? '');
        $recordedCount = (int) ($studentStat['counter']['recorded_count'] ?? 0);

        $matchedCount = 0;
        $maxTargetUrutan = 0;
        $targetNames = [];

        foreach ($targetJilids as $targetJilid) {
            $tId = (int) $targetJilid->id;
            $tUrutan = (int) ($targetJilid->urutan ?? 0);
            $maxTargetUrutan = max($maxTargetUrutan, $tUrutan);
            $targetNames[] = (string) $targetJilid->jilid_latin;

            if (isset($recordedJilidIds[$tId]) || ($tUrutan > 0 && $highestUrutan >= $tUrutan)) {
                $matchedCount++;
            }
        }

        $totalTargets = $targetJilids->count();
        $achieved = $matchedCount >= $totalTargets;
        $targetSummary = implode(', ', $targetNames);

        if ($achieved) {
            $progressPct = 100;
        } elseif ($maxTargetUrutan > 0 && $highestUrutan > 0) {
            $progressPct = min(99, max(5, (int) round(($highestUrutan / $maxTargetUrutan) * 100)));
        } elseif ($matchedCount > 0) {
            $progressPct = min(99, (int) round(($matchedCount / $totalTargets) * 100));
        } else {
            $progressPct = 0;
        }

        $latest = $studentStat['latest_entry'] ?? null;
        $latestJilid = ($latest !== null && method_exists($latest, 'daftarJilid') && $latest->relationLoaded('daftarJilid'))
            ? (string) ($latest->daftarJilid?->jilid_latin ?? '')
            : '';
        $displayPosition = $highestLabel !== '' ? $highestLabel : $latestJilid;

        if ($recordedCount === 0) {
            $detailText = "Belum ada setoran • Target: {$targetSummary}";
        } elseif ($achieved) {
            $detailText = "Mencapai {$displayPosition} • Target: {$targetSummary}";
        } else {
            $detailText = ($displayPosition !== '' ? "Posisi: {$displayPosition}" : 'Dalam proses')." • Target: {$targetSummary}";
        }

        return [
            'status' => $achieved ? 'tercapai' : 'belum_tercapai',
            'status_label' => $achieved ? 'Sudah Tercapai' : 'Belum Tercapai',
            'progress_pct' => $progressPct,
            'target_summary' => $targetSummary,
            'detail_text' => $detailText,
        ];
    }

    /**
     * @param  array<string, mixed>  $studentStat
     * @param  Collection<int, TargetCapaian>  $targetsForTingkat
     * @return array{status: string, status_label: string, progress_pct: ?int, target_summary: string, detail_text: string}
     */
    private function evaluateTahfizhTargets(array $studentStat, Collection $targetsForTingkat): array
    {
        $targetSurahs = $targetsForTingkat
            ->filter(static fn (TargetCapaian $tc): bool => $tc->relationLoaded('surah') && $tc->surah !== null)
            ->map(static fn (TargetCapaian $tc): Surah => $tc->surah)
            ->unique('id')
            ->values();

        if ($targetSurahs->isEmpty()) {
            return [
                'status' => 'tanpa_target',
                'status_label' => 'Belum Ada Target',
                'progress_pct' => null,
                'target_summary' => 'Belum ada target surah',
                'detail_text' => 'Target surah belum diatur pada tingkat ini',
            ];
        }

        $recordedSurahIds = $studentStat['recorded_surah_ids'] ?? [];
        $recordedCount = (int) ($studentStat['counter']['recorded_count'] ?? 0);

        $matchedCount = 0;
        $targetNames = [];

        foreach ($targetSurahs as $targetSurah) {
            $targetNames[] = (string) $targetSurah->nama_surah;
            if (isset($recordedSurahIds[(int) $targetSurah->id])) {
                $matchedCount++;
            }
        }

        $totalTargets = $targetSurahs->count();
        $achieved = $matchedCount >= $totalTargets;
        $progressPct = $achieved ? 100 : (int) round(($matchedCount / $totalTargets) * 100);
        $targetSummary = implode(', ', $targetNames);

        if ($recordedCount === 0) {
            $detailText = "Belum ada setoran • Target: {$targetSummary}";
        } elseif ($achieved) {
            $detailText = "Terpenuhi ({$matchedCount}/{$totalTargets} surah target)";
        } else {
            $detailText = "{$matchedCount}/{$totalTargets} surah target • Target: {$targetSummary}";
        }

        return [
            'status' => $achieved ? 'tercapai' : 'belum_tercapai',
            'status_label' => $achieved ? 'Sudah Tercapai' : 'Belum Tercapai',
            'progress_pct' => $progressPct,
            'target_summary' => $targetSummary,
            'detail_text' => $detailText,
        ];
    }

    /**
     * Menghitung segmen lingkaran SVG donut pencapaian target (radius 46, keliling 289.03).
     *
     * @return list<array{key: string, label: string, color: string, count: int, pct: float, dash: float, offset: float}>
     */
    private function buildTargetDonutSegments(int $achievedCount, int $unachievedCount, int $noTargetCount, int $totalStudents): array
    {
        $circumference = 289.03;
        $rawSegments = [
            ['key' => 'tercapai', 'label' => 'Sudah Mencapai Target', 'color' => '#10b981', 'count' => $achievedCount],
            ['key' => 'belum_tercapai', 'label' => 'Belum Mencapai Target', 'color' => '#f59e0b', 'count' => $unachievedCount],
            ['key' => 'tanpa_target', 'label' => 'Belum Ada Target', 'color' => '#cbd5e1', 'count' => $noTargetCount],
        ];

        $segments = [];
        $runningDash = 0.0;

        foreach ($rawSegments as $segment) {
            $count = $segment['count'];
            $pct = $totalStudents > 0 ? round(($count / $totalStudents) * 100, 1) : 0.0;
            $dash = ($totalStudents > 0 && $count > 0) ? round(($count / $totalStudents) * $circumference, 2) : 0.0;

            $segments[] = [
                ...$segment,
                'pct' => $pct,
                'dash' => $dash,
                'offset' => round(-$runningDash, 2),
            ];
            $runningDash += $dash;
        }

        return $segments;
    }

    /**
     * @param  EloquentCollection<int, TahunAjaran>  $tahunAjaranOptions
     * @return array<string, mixed>
     */
    private function emptyStatistikPayload(EloquentCollection $tahunAjaranOptions, string $selectedGuruNipy, string $requestedTingkat): array
    {
        return [
            'mode' => $this->modeKey(),
            'usesDaftarJilid' => $this->usesDaftarJilid(),
            'tahunAjaranOptions' => $tahunAjaranOptions,
            'selectedTahunAjaran' => null,
            'selectedTahunAjaranId' => 0,
            'guruOptions' => new EloquentCollection,
            'selectedGuruNipy' => $selectedGuruNipy,
            'tingkatOptions' => [],
            'selectedTingkat' => $requestedTingkat,
            'stats' => [
                'summary' => [
                    'total_students' => 0,
                    'active_students' => 0,
                    'scored_students' => 0,
                    'inactive_students' => 0,
                    'student_activity_pct' => 0.0,
                    'total_recorded' => 0,
                    'active_days_count' => 0,
                    'avg_per_student' => 0.0,
                    'overall_avg_formatted' => '-',
                    'overall_avg_tier' => 'neutral',
                    'overall_avg_label' => 'Belum Ada Data',
                    'excellent_count' => 0,
                    'excellent_pct' => 0.0,
                    'best_month_label' => '-',
                    'best_month_count' => 0,
                ],
                'target_achievement' => [
                    'achieved_count' => 0,
                    'unachieved_count' => 0,
                    'no_target_count' => 0,
                    'evaluated_count' => 0,
                    'achieved_pct' => 0.0,
                    'evaluated_achieved_pct' => 0.0,
                    'donut_segments' => $this->buildTargetDonutSegments(0, 0, 0, 0),
                    'achieved_students' => [],
                    'unachieved_students' => [],
                ],
                'tier_rows' => [],
                'monthly_rows' => [],
                'material_rows' => [],
                'tingkat_rows' => [],
                'guru_rows' => [],
                'student_rows' => [],
            ],
        ];
    }
}
