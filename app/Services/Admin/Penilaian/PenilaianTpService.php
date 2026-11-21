<?php

namespace App\Services\Admin\Penilaian;

use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Admin\Penilaian\AspekTp;
use App\Models\Admin\Penilaian\Kkm;
use App\Models\Admin\Penilaian\KkmPredikat;
use App\Models\Admin\Penilaian\NilaiAspek;
use App\Models\Admin\Penilaian\NilaiSumatifBab;
use App\Models\Admin\Penilaian\PenilaianFinalisasi;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Models\Admin\Penilaian\RekapMapel;
use App\Models\Admin\Penilaian\RekapTp;
use App\Models\Admin\Penilaian\SumatifBab;
use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PenilaianTpService
{
    public function __construct(
        protected ?PenilaianFinalisasiService $finalisasiService = null
    ) {
        $this->finalisasiService ??= new PenilaianFinalisasiService;
    }

    /**
     * Menghitung NA TP dari nilai-nilai aspek (mengabaikan sel NULL/kosong dan membulatkan bulat terdekat).
     *
     * @param  array<int|string, float|int|string|null>  $nilaiByKey
     * @param  array<int|string, int|float>  $weightsByKey
     */
    public static function computeNaTp(array $nilaiByKey, array $weightsByKey = []): ?float
    {
        $weightedSum = 0.0;
        $totalWeight = 0.0;
        $hasValidValue = false;

        foreach ($nilaiByKey as $key => $rawValue) {
            if ($rawValue === null || $rawValue === '') {
                continue;
            }

            if (! is_numeric($rawValue)) {
                continue;
            }

            $weight = isset($weightsByKey[$key]) && is_numeric($weightsByKey[$key]) && (float) $weightsByKey[$key] > 0
                ? (float) $weightsByKey[$key]
                : 100.0;

            $weightedSum += ((float) $rawValue) * $weight;
            $totalWeight += $weight;
            $hasValidValue = true;
        }

        if (! $hasValidValue || $totalWeight <= 0.0) {
            return null;
        }

        return (float) round($weightedSum / $totalWeight, 0);
    }

    /**
     * Menghitung rata-rata nilai Sumatif Bab (mengabaikan sel NULL/kosong dan membulatkan bulat terdekat).
     *
     * @param  array<int|string, float|int|string|null>  $nilaiByBab
     */
    public static function computeRataSumatif(array $nilaiByBab): ?float
    {
        return self::computeNaTp($nilaiByBab);
    }

    /**
     * Menghitung NA Rapor akhir mapel dari komponen aktif yang berbobot > 0 & bernilai
     * (Rata-rata NA TP, Rata-rata Sumatif Bab, dan Nilai Ujian PSTS/PSAS/PSAT).
     * Komponen yang berbobot 0% atau nilainya belum diisi otomatis dinormalisasi dari komponen aktif lainnya.
     */
    public static function computeNaRapor(
        ?float $rataRataTp,
        ?float $nilaiUjian,
        int $bobotTp = Kkm::DEFAULT_BOBOT_TP,
        int $bobotUjian = Kkm::DEFAULT_BOBOT_UJIAN,
        ?float $rataRataSumatif = null,
        int $bobotSumatif = Kkm::DEFAULT_BOBOT_SUMATIF
    ): ?float {
        $components = [
            ['value' => $rataRataTp, 'weight' => $bobotTp],
            ['value' => $rataRataSumatif, 'weight' => $bobotSumatif],
            ['value' => $nilaiUjian, 'weight' => $bobotUjian],
        ];

        $weightedSum = 0.0;
        $totalWeight = 0;

        foreach ($components as $component) {
            if ($component['value'] === null || $component['weight'] <= 0) {
                continue;
            }

            $weightedSum += $component['value'] * $component['weight'];
            $totalWeight += $component['weight'];
        }

        if ($totalWeight <= 0) {
            return null;
        }

        return (float) round($weightedSum / $totalWeight, 0);
    }

    /**
     * Menggabungkan daftar topik dengan tata bahasa Indonesia ("A", "A dan B", "A, B, dan C").
     *
     * @param  list<string>  $items
     */
    public static function formatDaftarTopik(array $items): string
    {
        $items = array_values(array_unique(array_filter(
            array_map(static fn (string $v): string => trim($v), $items),
            static fn (string $v): bool => $v !== ''
        )));

        $count = count($items);

        if ($count === 0) {
            return '';
        }

        if ($count === 1) {
            return $items[0];
        }

        if ($count === 2) {
            return "{$items[0]} dan {$items[1]}";
        }

        $last = array_pop($items);

        return implode(', ', $items).', dan '.$last;
    }

    /**
     * Menyusun draf deskripsi capaian rapor untuk seluruh TP yang bernilai NA TP,
     * dikelompokkan berdasarkan predikat KKM (topik dengan predikat sama digabung memakai koma / "dan").
     *
     * @param  list<array{kode_tp?: string, tujuan_pembelajaran: string, lingkup_materi?: ?string, nilai: float|int|null}>  $tpEntries
     * @param  Collection<int, KkmPredikat>|null  $predikats
     */
    public static function buildDeskripsiCapaian(
        array $tpEntries,
        float $nilaiKkm = Kkm::DEFAULT_KKM,
        ?Collection $predikats = null
    ): ?string {
        $validEntries = array_values(array_filter(
            $tpEntries,
            static fn (array $entry): bool => isset($entry['nilai']) && is_numeric($entry['nilai'])
        ));

        if ($validEntries === []) {
            return null;
        }

        $resolvedPredikats = $predikats !== null && $predikats->isNotEmpty()
            ? $predikats
            : new EloquentCollection(array_map(
                static fn (array $row): KkmPredikat => KkmPredikat::make($row),
                Kkm::generateDefaultPredikats($nilaiKkm)
            ));

        $formatTopic = static function (array $entry): string {
            $tpText = trim(preg_replace('/\s+/', ' ', (string) ($entry['tujuan_pembelajaran'] ?? '')) ?? '');
            if ($tpText !== '') {
                return rtrim($tpText, '.');
            }

            $lingkup = trim((string) ($entry['lingkup_materi'] ?? ''));

            return rtrim($lingkup, '.');
        };

        $groups = [];

        foreach ($validEntries as $entry) {
            $topic = $formatTopic($entry);
            if ($topic === '') {
                continue;
            }

            $score = (float) $entry['nilai'];
            $matched = Kkm::resolvePredikatForNilai($score, $resolvedPredikats);

            if ($matched !== null) {
                $groupKey = (string) $matched->predikat;
                $keterangan = trim((string) $matched->keterangan);
                $isTuntas = (bool) $matched->is_tuntas;
                $order = (int) ($matched->urutan ?? 999);
                $nilaiMax = (float) ($matched->nilai_max ?? $score);
            } else {
                $isTuntas = $score >= $nilaiKkm;
                $groupKey = $isTuntas ? '__TUNTAS__' : '__BELUM_TUNTAS__';
                $keterangan = $isTuntas ? 'Baik' : 'Perlu Bimbingan';
                $order = $isTuntas ? 1 : 999;
                $nilaiMax = $isTuntas ? 100.0 : max(0.0, $nilaiKkm - 1);
            }

            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'keterangan' => $keterangan,
                    'is_tuntas' => $isTuntas,
                    'urutan' => $order,
                    'nilai_max' => $nilaiMax,
                    'topics' => [],
                ];
            }

            $groups[$groupKey]['topics'][] = $topic;
        }

        if ($groups === []) {
            return null;
        }

        uasort(
            $groups,
            static fn (array $a, array $b): int => ($a['urutan'] <=> $b['urutan']) ?: ($b['nilai_max'] <=> $a['nilai_max'])
        );

        $sentences = [];
        foreach ($groups as $group) {
            $topicList = self::formatDaftarTopik($group['topics']);
            if ($topicList === '') {
                continue;
            }

            $lowerKeterangan = mb_strtolower($group['keterangan']);

            if ($group['is_tuntas']) {
                $label = $lowerKeterangan !== '' ? $lowerKeterangan : 'baik';
                $sentences[] = "Menunjukkan penguasaan yang {$label} dalam {$topicList}.";
            } elseif (str_contains($lowerKeterangan, 'bimbingan') || str_contains($lowerKeterangan, 'pendampingan')) {
                $sentences[] = "Perlu bimbingan dan pendampingan lebih lanjut dalam {$topicList}.";
            } else {
                $detail = $lowerKeterangan !== '' ? " ({$lowerKeterangan})" : '';
                $sentences[] = "Perlu pendampingan dan latihan lebih lanjut{$detail} dalam {$topicList}.";
            }
        }

        return $sentences !== [] ? implode(' ', $sentences) : null;
    }

    /**
     * Memuat seluruh data grid Penilaian TP (SD) dengan query datar tanpa N+1.
     *
     * @param  array<string, mixed>  $input
     * @param  ?EloquentCollection<int, Kelas>  $scopedKelasOptions
     * @param  ?EloquentCollection<int, MataPelajaran>  $scopedMapelOptions
     * @return array<string, mixed>
     */
    public function getMatrixData(
        array $input = [],
        ?EloquentCollection $scopedKelasOptions = null,
        ?EloquentCollection $scopedMapelOptions = null
    ): array {
        if ($scopedKelasOptions !== null) {
            $tahunAjaranOptions = new EloquentCollection(
                $scopedKelasOptions
                    ->pluck('tahunAjaran')
                    ->filter()
                    ->unique('id')
                    ->sortByDesc('is_active')
                    ->values()
                    ->all()
            );

            if ($tahunAjaranOptions->isEmpty()) {
                $activeTa = TahunAjaran::query()
                    ->select(['id', 'nama_tahun_ajaran', 'semester', 'is_active'])
                    ->active()
                    ->first();
                $tahunAjaranOptions = $activeTa !== null ? new EloquentCollection([$activeTa]) : new EloquentCollection;
            }
        } else {
            $tahunAjaranOptions = $this->getTahunAjaranOptions();
        }

        $defaultTa = $this->resolveDefaultTahunAjaran($tahunAjaranOptions);
        $tahunAjaranId = $this->resolvePositiveInt($input['tahun_ajaran_id'] ?? null) ?? $defaultTa?->id;
        $selectedTahunAjaran = $tahunAjaranId !== null ? $tahunAjaranOptions->firstWhere('id', $tahunAjaranId) : null;

        $periode = $this->normalizePeriode($input['periode'] ?? PeriodeTp::PERIODE_MID);
        $ujianLabel = PeriodeTp::ujianLabel($periode, $selectedTahunAjaran?->semester);
        $ujianFullLabel = PeriodeTp::ujianFullLabel($periode, $selectedTahunAjaran?->semester);

        if ($tahunAjaranId !== null) {
            $kelasOptions = $scopedKelasOptions !== null
                ? new EloquentCollection(
                    $scopedKelasOptions
                        ->filter(
                            static fn (Kelas $k): bool => (int) $k->akademik_master_tahun_ajaran_id === (int) $tahunAjaranId
                                && strtoupper(trim((string) $k->jenjang_kelas)) === Kkm::TARGET_UNIT
                        )
                        ->values()
                        ->all()
                )
                : $this->getSdKelasOptions($tahunAjaranId);
        } else {
            $kelasOptions = new EloquentCollection;
        }

        $mataPelajaranOptions = $scopedMapelOptions !== null
            ? $scopedMapelOptions->values()
            : $this->getSdMapelOptions();

        $requestedKelasId = $this->resolvePositiveInt($input['kelas_id'] ?? null);
        $selectedKelas = $requestedKelasId !== null
            ? ($kelasOptions->firstWhere('id', $requestedKelasId) ?? $kelasOptions->first())
            : $kelasOptions->first();

        $requestedMapelId = $this->resolvePositiveInt($input['mata_pelajaran_id'] ?? null);
        $selectedMapel = $requestedMapelId !== null
            ? ($mataPelajaranOptions->firstWhere('id', $requestedMapelId) ?? $mataPelajaranOptions->first())
            : $mataPelajaranOptions->first();

        $emptyState = [
            'tahunAjaranOptions' => $tahunAjaranOptions,
            'kelasOptions' => $kelasOptions,
            'mataPelajaranOptions' => $mataPelajaranOptions,
            'selectedTahunAjaranId' => $tahunAjaranId,
            'selectedTahunAjaran' => $selectedTahunAjaran,
            'selectedKelas' => $selectedKelas,
            'selectedMapel' => $selectedMapel,
            'periode' => $periode,
            'periodeOptions' => PeriodeTp::PERIODE_OPTIONS,
            'teknikOptions' => AspekTp::TEKNIK_OPTIONS,
            'tpList' => new EloquentCollection,
            'periodeSettingsByTp' => collect(),
            'aspeksByTp' => collect(),
            'midSumatifBabs' => new EloquentCollection,
            'carriedMidSumatifBabs' => new EloquentCollection,
            'activeSumatifBabs' => new EloquentCollection,
            'sumatifNilaiLookup' => [],
            'students' => new EloquentCollection,
            'nilaiLookup' => [],
            'rekapTpLookup' => [],
            'rekapMapelLookup' => [],
            'kkmNilai' => Kkm::DEFAULT_KKM,
            'kkmPredikats' => $this->buildVirtualDefaultPredikats(),
            'bobotTp' => Kkm::DEFAULT_BOBOT_TP,
            'bobotSumatif' => Kkm::DEFAULT_BOBOT_SUMATIF,
            'bobotUjian' => Kkm::DEFAULT_BOBOT_UJIAN,
            'ujianLabel' => $ujianLabel,
            'ujianFullLabel' => $ujianFullLabel,
            'hasKkmConfig' => false,
            'kkmModel' => null,
            'includedTpCount' => 0,
        ];

        if ($tahunAjaranId === null || $selectedKelas === null || $selectedMapel === null) {
            return $emptyState;
        }

        $tpList = TujuanPembelajaran::query()
            ->select([
                'id',
                'akademik_master_tahun_ajaran_id',
                'akademik_pelajaran_mata_pelajaran_id',
                'tingkatan_kelas',
                'fase',
                'elemen',
                'kode_tp',
                'tujuan_pembelajaran',
                'lingkup_materi',
                'urutan',
                'status',
            ])
            ->where('akademik_master_tahun_ajaran_id', $tahunAjaranId)
            ->where('akademik_pelajaran_mata_pelajaran_id', $selectedMapel->id)
            ->where('tingkatan_kelas', $selectedKelas->tingkatan_kelas)
            ->where('status', true)
            ->ordered()
            ->get();

        $allSumatifBabs = SumatifBab::query()
            ->select([
                'id',
                'akademik_master_tahun_ajaran_id',
                'akademik_pelajaran_mata_pelajaran_id',
                'tingkatan_kelas',
                'periode',
                'nama_bab',
                'keterangan',
                'urutan',
            ])
            ->where('akademik_master_tahun_ajaran_id', $tahunAjaranId)
            ->where('akademik_pelajaran_mata_pelajaran_id', $selectedMapel->id)
            ->where('tingkatan_kelas', (string) $selectedKelas->tingkatan_kelas)
            ->orderBy('urutan')
            ->orderBy('id')
            ->get();

        $midSumatifBabs = new EloquentCollection($allSumatifBabs->where('periode', PeriodeTp::PERIODE_MID)->values()->all());
        $semesterSumatifBabs = new EloquentCollection($allSumatifBabs->where('periode', PeriodeTp::PERIODE_SEMESTER)->values()->all());
        $activeSumatifBabs = $periode === PeriodeTp::PERIODE_SEMESTER ? $semesterSumatifBabs : $midSumatifBabs;
        $carriedMidSumatifBabs = $periode === PeriodeTp::PERIODE_SEMESTER ? $midSumatifBabs : new EloquentCollection;

        $kkmBundle = $this->resolveKkmBundle($tahunAjaranId, $selectedMapel->id, (string) $selectedKelas->tingkatan_kelas);
        $students = $this->getSortedStudentsInKelas($selectedKelas->id);

        if ($tpList->isEmpty() && $allSumatifBabs->isEmpty()) {
            return array_merge($emptyState, [
                'students' => $students,
                'kkmNilai' => $kkmBundle['nilai_kkm'],
                'kkmPredikats' => $kkmBundle['predikats'],
                'bobotTp' => $kkmBundle['bobot_tp'],
                'bobotSumatif' => $kkmBundle['bobot_sumatif'],
                'bobotUjian' => $kkmBundle['bobot_ujian'],
                'hasKkmConfig' => $kkmBundle['has_config'],
                'kkmModel' => $kkmBundle['model'],
            ]);
        }

        if ($tpList->isNotEmpty()) {
            [$periodeSettingsByTp, $aspeksByTp] = $this->ensureAndLoadTpConfigurations($tpList, $periode);
        } else {
            $periodeSettingsByTp = collect();
            $aspeksByTp = collect();
        }

        $tpIds = $tpList->pluck('id')->all();
        $aspekIds = $aspeksByTp->flatten(1)->pluck('id')->all();
        $anggotaIds = $students->pluck('id')->all();
        $displayedSumatifIds = $carriedMidSumatifBabs->pluck('id')
            ->merge($activeSumatifBabs->pluck('id'))
            ->unique()
            ->values()
            ->all();

        $nilaiLookup = [];
        if ($aspekIds !== [] && $anggotaIds !== []) {
            $nilaiRows = NilaiAspek::query()
                ->select(['id', 'aspek_tp_id', 'anggota_kelas_id', 'nilai', 'is_remedial', 'catatan'])
                ->whereIn('aspek_tp_id', $aspekIds)
                ->whereIn('anggota_kelas_id', $anggotaIds)
                ->get();

            foreach ($nilaiRows as $nilaiRow) {
                $nilaiLookup["{$nilaiRow->anggota_kelas_id}:{$nilaiRow->aspek_tp_id}"] = $nilaiRow;
            }
        }

        $sumatifNilaiLookup = [];
        if ($displayedSumatifIds !== [] && $anggotaIds !== []) {
            $sumatifRows = NilaiSumatifBab::query()
                ->select(['id', 'sumatif_bab_id', 'anggota_kelas_id', 'nilai'])
                ->whereIn('sumatif_bab_id', $displayedSumatifIds)
                ->whereIn('anggota_kelas_id', $anggotaIds)
                ->get();

            foreach ($sumatifRows as $sumatifRow) {
                $sumatifNilaiLookup["{$sumatifRow->anggota_kelas_id}:{$sumatifRow->sumatif_bab_id}"] = $sumatifRow;
            }
        }

        $rekapTpLookup = [];
        if ($tpIds !== [] && $anggotaIds !== []) {
            $rekapTps = RekapTp::query()
                ->select(['id', 'tujuan_pembelajaran_id', 'anggota_kelas_id', 'periode', 'nilai_akhir_tp', 'predikat', 'tuntas_kkm', 'is_manual_override'])
                ->whereIn('tujuan_pembelajaran_id', $tpIds)
                ->whereIn('anggota_kelas_id', $anggotaIds)
                ->whereIn('periode', [PeriodeTp::PERIODE_MID, PeriodeTp::PERIODE_SEMESTER])
                ->get();

            foreach ($rekapTps as $rekapTp) {
                $rekapTpLookup["{$rekapTp->anggota_kelas_id}:{$rekapTp->tujuan_pembelajaran_id}:{$rekapTp->periode}"] = $rekapTp;
            }
        }

        $rekapMapelLookup = [];
        if ($anggotaIds !== []) {
            $rekapMapels = RekapMapel::query()
                ->where('akademik_master_tahun_ajaran_id', $tahunAjaranId)
                ->where('akademik_pelajaran_mata_pelajaran_id', $selectedMapel->id)
                ->whereIn('anggota_kelas_id', $anggotaIds)
                ->whereIn('periode', [PeriodeTp::PERIODE_MID, PeriodeTp::PERIODE_SEMESTER])
                ->get();

            foreach ($rekapMapels as $rekapMapel) {
                $rekapMapelLookup["{$rekapMapel->anggota_kelas_id}:{$rekapMapel->periode}"] = $rekapMapel;
            }
        }

        $includedCount = $tpList->count();

        return [
            'tahunAjaranOptions' => $tahunAjaranOptions,
            'kelasOptions' => $kelasOptions,
            'mataPelajaranOptions' => $mataPelajaranOptions,
            'selectedTahunAjaranId' => $tahunAjaranId,
            'selectedTahunAjaran' => $selectedTahunAjaran,
            'selectedKelas' => $selectedKelas,
            'selectedMapel' => $selectedMapel,
            'periode' => $periode,
            'periodeOptions' => PeriodeTp::PERIODE_OPTIONS,
            'teknikOptions' => AspekTp::TEKNIK_OPTIONS,
            'tpList' => $tpList,
            'periodeSettingsByTp' => $periodeSettingsByTp,
            'aspeksByTp' => $aspeksByTp,
            'midSumatifBabs' => $midSumatifBabs,
            'carriedMidSumatifBabs' => $carriedMidSumatifBabs,
            'activeSumatifBabs' => $activeSumatifBabs,
            'sumatifNilaiLookup' => $sumatifNilaiLookup,
            'students' => $students,
            'nilaiLookup' => $nilaiLookup,
            'rekapTpLookup' => $rekapTpLookup,
            'rekapMapelLookup' => $rekapMapelLookup,
            'kkmNilai' => $kkmBundle['nilai_kkm'],
            'kkmPredikats' => $kkmBundle['predikats'],
            'bobotTp' => $kkmBundle['bobot_tp'],
            'bobotSumatif' => $kkmBundle['bobot_sumatif'],
            'bobotUjian' => $kkmBundle['bobot_ujian'],
            'ujianLabel' => $ujianLabel,
            'ujianFullLabel' => $ujianFullLabel,
            'hasKkmConfig' => $kkmBundle['has_config'],
            'kkmModel' => $kkmBundle['model'],
            'includedTpCount' => $includedCount,
            'finalisasi' => $this->finalisasiService->getStatusPayloadForComponent([
                'jenis_komponen' => PenilaianFinalisasi::JENIS_MAPEL,
                'tahun_ajaran_id' => $tahunAjaranId,
                'kelas_id' => $selectedKelas->id,
                'mata_pelajaran_id' => $selectedMapel->id,
                'periode' => $periode,
            ], auth()->user()),
        ];
    }

    /**
     * Memuat data halaman Rekap Rapor & Deskripsi Capaian (Mid vs Semester) tanpa N+1.
     *
     * @param  array<string, mixed>  $input
     * @param  ?EloquentCollection<int, Kelas>  $scopedKelasOptions
     * @param  ?EloquentCollection<int, MataPelajaran>  $scopedMapelOptions
     * @return array<string, mixed>
     */
    public function getRekapData(
        array $input = [],
        ?EloquentCollection $scopedKelasOptions = null,
        ?EloquentCollection $scopedMapelOptions = null
    ): array {
        $matrix = $this->getMatrixData($input, $scopedKelasOptions, $scopedMapelOptions);
        $periode = $matrix['periode'];
        $carriedMidSumatif = $matrix['carriedMidSumatifBabs'] ?? collect();
        $activeSumatif = $matrix['activeSumatifBabs'] ?? collect();
        $rekapSumatifCols = $periode === PeriodeTp::PERIODE_SEMESTER
            ? $carriedMidSumatif->merge($activeSumatif)->values()
            : $activeSumatif->values();
        $matrix['rekapSumatifCols'] = $rekapSumatifCols;

        if (($matrix['tpList']->isEmpty() && $rekapSumatifCols->isEmpty()) || $matrix['students']->isEmpty()) {
            $matrix['combinedTpSummaries'] = [];
            $matrix['combinedSumatifSummaries'] = [];

            return $matrix;
        }

        $tpList = $matrix['tpList'];
        $rekapTpLookup = $matrix['rekapTpLookup'];
        $sumatifNilaiLookup = $matrix['sumatifNilaiLookup'] ?? [];

        // Untuk periode semester, gabungkan nilai TP & Sumatif Bab dari periode mid + semester
        $combinedTpSummaries = [];
        $combinedSumatifSummaries = [];
        foreach ($matrix['students'] as $student) {
            $perTp = [];
            foreach ($tpList as $tp) {
                $isIncluded = true;

                $rekapSem = $rekapTpLookup["{$student->id}:{$tp->id}:".PeriodeTp::PERIODE_SEMESTER] ?? null;
                $rekapMid = $rekapTpLookup["{$student->id}:{$tp->id}:".PeriodeTp::PERIODE_MID] ?? null;

                if ($periode === PeriodeTp::PERIODE_MID) {
                    $chosen = $rekapMid;
                    $source = $rekapMid?->nilai_akhir_tp !== null ? 'Mid' : null;
                } elseif ($rekapSem !== null && $rekapSem->nilai_akhir_tp !== null) {
                    $chosen = $rekapSem;
                    $source = 'Semester';
                } elseif ($rekapMid !== null && $rekapMid->nilai_akhir_tp !== null) {
                    $chosen = $rekapMid;
                    $source = 'Mid';
                } else {
                    $chosen = null;
                    $source = null;
                }

                $perTp[$tp->id] = [
                    'included' => $isIncluded,
                    'nilai' => $chosen?->nilai_akhir_tp,
                    'predikat' => $chosen?->predikat,
                    'tuntas' => $chosen?->tuntas_kkm,
                    'source' => $source,
                ];
            }

            $perSumatif = [];
            foreach ($rekapSumatifCols as $bab) {
                $nilaiObj = $sumatifNilaiLookup["{$student->id}:{$bab->id}"] ?? null;
                $perSumatif[$bab->id] = [
                    'nilai' => $nilaiObj?->nilai,
                    'source' => $bab->periode === PeriodeTp::PERIODE_MID ? 'Mid' : 'Semester',
                ];
            }

            $combinedTpSummaries[$student->id] = $perTp;
            $combinedSumatifSummaries[$student->id] = $perSumatif;
        }

        $matrix['combinedTpSummaries'] = $combinedTpSummaries;
        $matrix['combinedSumatifSummaries'] = $combinedSumatifSummaries;

        return $matrix;
    }

    /**
     * Menyimpan atau memperbarui aspek penilaian pada suatu TP & periode (seragam per jenjang).
     *
     * @param  array<string, mixed>  $data
     */
    public function saveAspek(TujuanPembelajaran $tp, array $data, ?AspekTp $existing = null): AspekTp
    {
        $periode = $this->normalizePeriode($data['periode'] ?? PeriodeTp::PERIODE_MID);
        $this->finalisasiService->assertMapelStructureEditable(
            (int) $tp->akademik_master_tahun_ajaran_id,
            (int) $tp->akademik_pelajaran_mata_pelajaran_id,
            (string) $tp->tingkatan_kelas,
            $periode
        );
        $namaAspek = trim((string) $data['nama_aspek']);

        $duplicate = AspekTp::query()
            ->where('tujuan_pembelajaran_id', $tp->id)
            ->where('periode', $periode)
            ->whereRaw('LOWER(nama_aspek) = ?', [mb_strtolower($namaAspek)])
            ->when($existing !== null, static fn ($q) => $q->where('id', '!=', $existing->id))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'nama_aspek' => "Aspek \"{$namaAspek}\" sudah ada pada {$tp->kode_tp} untuk periode ini.",
            ]);
        }

        return DB::transaction(function () use ($tp, $periode, $namaAspek, $data, $existing): AspekTp {
            $nextOrder = $existing?->urutan ?? (
                (int) AspekTp::query()
                    ->where('tujuan_pembelajaran_id', $tp->id)
                    ->where('periode', $periode)
                    ->max('urutan') + 1
            );

            $attributes = [
                'tujuan_pembelajaran_id' => $tp->id,
                'periode' => $periode,
                'nama_aspek' => $namaAspek,
                'teknik_penilaian' => filled($data['teknik_penilaian'] ?? null) ? $data['teknik_penilaian'] : null,
                'bobot' => (int) ($data['bobot'] ?? 100),
                'masuk_hitung_na_tp' => (bool) ($data['masuk_hitung_na_tp'] ?? true),
                'urutan' => (int) ($data['urutan'] ?? max(1, $nextOrder)),
            ];

            if ($existing !== null) {
                $existing->update($attributes);
                $aspek = $existing->refresh();
            } else {
                $aspek = AspekTp::create($attributes);
            }

            $this->recalculateScope(
                $tp->akademik_master_tahun_ajaran_id,
                $tp->akademik_pelajaran_mata_pelajaran_id,
                (string) $tp->tingkatan_kelas
            );

            return $aspek;
        });
    }

    /**
     * Menghapus aspek TP (selama masih tersisa atau langsung menyegarkan NA TP setelah dihapus).
     */
    public function deleteAspek(AspekTp $aspek): void
    {
        $aspek->loadMissing('tujuanPembelajaran');
        $tp = $aspek->tujuanPembelajaran;

        if ($tp !== null) {
            $this->finalisasiService->assertMapelStructureEditable(
                (int) $tp->akademik_master_tahun_ajaran_id,
                (int) $tp->akademik_pelajaran_mata_pelajaran_id,
                (string) $tp->tingkatan_kelas,
                (string) $aspek->periode
            );
        }

        DB::transaction(function () use ($aspek, $tp): void {
            $aspek->delete();

            if ($tp !== null) {
                $this->recalculateScope(
                    $tp->akademik_master_tahun_ajaran_id,
                    $tp->akademik_pelajaran_mata_pelajaran_id,
                    (string) $tp->tingkatan_kelas
                );
            }
        });
    }

    /**
     * Menambah atau memperbarui kolom Sumatif Bab per TA, Mapel, Tingkatan Kelas, & Periode (seragam per jenjang).
     *
     * @param  array<string, mixed>  $data
     */
    public function saveSumatifBab(array $data, ?SumatifBab $existing = null): SumatifBab
    {
        $tahunAjaranId = (int) $data['tahun_ajaran_id'];
        $mapelId = (int) $data['mata_pelajaran_id'];
        $tingkatanKelas = trim((string) $data['tingkatan_kelas']);
        $periode = $this->normalizePeriode($data['periode'] ?? PeriodeTp::PERIODE_MID);
        $this->finalisasiService->assertMapelStructureEditable($tahunAjaranId, $mapelId, $tingkatanKelas, $periode);
        $namaBab = trim((string) $data['nama_bab']);
        $keterangan = filled($data['keterangan'] ?? null) ? trim((string) $data['keterangan']) : null;

        $duplicate = SumatifBab::query()
            ->where('akademik_master_tahun_ajaran_id', $tahunAjaranId)
            ->where('akademik_pelajaran_mata_pelajaran_id', $mapelId)
            ->where('tingkatan_kelas', $tingkatanKelas)
            ->where('periode', $periode)
            ->whereRaw('LOWER(nama_bab) = ?', [mb_strtolower($namaBab)])
            ->when($existing !== null, static fn ($q) => $q->where('id', '!=', $existing->id))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'nama_bab' => "Sumatif Bab \"{$namaBab}\" sudah ada pada mapel & periode ini.",
            ]);
        }

        return DB::transaction(function () use (
            $tahunAjaranId,
            $mapelId,
            $tingkatanKelas,
            $periode,
            $namaBab,
            $keterangan,
            $data,
            $existing
        ): SumatifBab {
            $nextOrder = $existing?->urutan ?? (
                (int) SumatifBab::query()
                    ->where('akademik_master_tahun_ajaran_id', $tahunAjaranId)
                    ->where('akademik_pelajaran_mata_pelajaran_id', $mapelId)
                    ->where('tingkatan_kelas', $tingkatanKelas)
                    ->where('periode', $periode)
                    ->max('urutan') + 1
            );

            $attributes = [
                'akademik_master_tahun_ajaran_id' => $tahunAjaranId,
                'akademik_pelajaran_mata_pelajaran_id' => $mapelId,
                'tingkatan_kelas' => $tingkatanKelas,
                'periode' => $periode,
                'nama_bab' => $namaBab,
                'keterangan' => $keterangan,
                'urutan' => (int) ($data['urutan'] ?? max(1, $nextOrder)),
            ];

            if ($existing !== null) {
                $existing->update($attributes);
                $sumatifBab = $existing->refresh();
            } else {
                $sumatifBab = SumatifBab::create($attributes);
            }

            $this->recalculateScope($tahunAjaranId, $mapelId, $tingkatanKelas);

            return $sumatifBab;
        });
    }

    /**
     * Menghapus kolom Sumatif Bab beserta seluruh nilai siswanya dan menghitung ulang NA Rapor jenjang.
     */
    public function deleteSumatifBab(SumatifBab $sumatifBab): void
    {
        $tahunAjaranId = $sumatifBab->akademik_master_tahun_ajaran_id;
        $mapelId = $sumatifBab->akademik_pelajaran_mata_pelajaran_id;
        $tingkatanKelas = (string) $sumatifBab->tingkatan_kelas;
        $this->finalisasiService->assertMapelStructureEditable(
            (int) $tahunAjaranId,
            (int) $mapelId,
            $tingkatanKelas,
            (string) $sumatifBab->periode
        );

        DB::transaction(function () use ($sumatifBab, $tahunAjaranId, $mapelId, $tingkatanKelas): void {
            $sumatifBab->delete();

            $this->recalculateScope($tahunAjaranId, $mapelId, $tingkatanKelas);
        });
    }

    /**
     * Memperbarui bobot persentase Rapor (NA TP, Sumatif Bab, dan Nilai Ujian PSTS/PSAS/PSAT) seragam per jenjang & menghitung ulang NA Rapor.
     */
    public function updateBobotRapor(
        Kelas $kelas,
        MataPelajaran $mapel,
        int $bobotTp,
        int $bobotUjian,
        int $bobotSumatif = Kkm::DEFAULT_BOBOT_SUMATIF
    ): Kkm {
        if (strtoupper(trim((string) $kelas->jenjang_kelas)) !== Kkm::TARGET_UNIT || strtoupper(trim((string) $mapel->unit)) !== Kkm::TARGET_UNIT) {
            throw ValidationException::withMessages([
                'kelas_id' => 'Modul Penilaian TP saat ini dikhususkan untuk jenjang SD.',
            ]);
        }

        if (($error = Kkm::validateBobotPair($bobotTp, $bobotUjian, $bobotSumatif)) !== null) {
            throw ValidationException::withMessages(['bobot_ujian' => $error]);
        }

        $this->finalisasiService->assertMapelStructureEditableAnyPeriode(
            (int) $kelas->akademik_master_tahun_ajaran_id,
            (int) $mapel->id,
            (string) $kelas->tingkatan_kelas
        );

        return DB::transaction(function () use ($kelas, $mapel, $bobotTp, $bobotSumatif, $bobotUjian): Kkm {
            $kkm = Kkm::query()
                ->where('akademik_master_tahun_ajaran_id', $kelas->akademik_master_tahun_ajaran_id)
                ->where('akademik_pelajaran_mata_pelajaran_id', $mapel->id)
                ->where('tingkatan_kelas', (string) $kelas->tingkatan_kelas)
                ->first();

            if ($kkm === null) {
                $kkm = Kkm::create([
                    'akademik_master_tahun_ajaran_id' => $kelas->akademik_master_tahun_ajaran_id,
                    'akademik_pelajaran_mata_pelajaran_id' => $mapel->id,
                    'tingkatan_kelas' => (string) $kelas->tingkatan_kelas,
                    'nilai_kkm' => Kkm::DEFAULT_KKM,
                    'bobot_tp' => $bobotTp,
                    'bobot_sumatif' => $bobotSumatif,
                    'bobot_ujian' => $bobotUjian,
                    'status' => true,
                ]);
            } else {
                $kkm->update([
                    'bobot_tp' => $bobotTp,
                    'bobot_sumatif' => $bobotSumatif,
                    'bobot_ujian' => $bobotUjian,
                    'status' => true,
                ]);
            }

            if (! $kkm->predikats()->exists()) {
                $now = now();
                $rows = array_map(
                    static fn (array $item): array => [
                        'kkm_id' => $kkm->id,
                        'predikat' => $item['predikat'],
                        'keterangan' => $item['keterangan'],
                        'nilai_min' => $item['nilai_min'],
                        'nilai_max' => $item['nilai_max'],
                        'is_tuntas' => $item['is_tuntas'],
                        'urutan' => $item['urutan'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    Kkm::generateDefaultPredikats((float) $kkm->nilai_kkm)
                );
                KkmPredikat::query()->insert($rows);
            }

            $this->recalculateScope(
                $kelas->akademik_master_tahun_ajaran_id,
                $mapel->id,
                (string) $kelas->tingkatan_kelas
            );

            return $kkm->refresh();
        });
    }

    /**
     * Menyimpan seluruh nilai grid aspek TP, Sumatif Bab, dan nilai ujian periode (PSTS/PSAS/PSAT) secara batch anti-N+1.
     *
     * @param  array<int|string, array<int|string, mixed>>  $nilaiMatrix  [anggota_kelas_id => [aspek_tp_id => nilai|null]]
     * @param  array<int|string, mixed>  $ujianScores  [anggota_kelas_id => nilai|null]
     * @param  array<int|string, array<int|string, mixed>>  $sumatifMatrix  [anggota_kelas_id => [sumatif_bab_id => nilai|null]]
     * @return array{saved_cells: int, updated_students: int}
     */
    public function saveNilaiGrid(
        Kelas $kelas,
        MataPelajaran $mapel,
        string $periode,
        array $nilaiMatrix,
        ?string $dinilaiOlehNipy = null,
        array $ujianScores = [],
        array $sumatifMatrix = []
    ): array {
        $periode = $this->normalizePeriode($periode);
        $this->finalisasiService->assertMapelGridEditable($kelas, $mapel, $periode);

        if (strtoupper(trim((string) $kelas->jenjang_kelas)) !== Kkm::TARGET_UNIT || strtoupper(trim((string) $mapel->unit)) !== Kkm::TARGET_UNIT) {
            throw ValidationException::withMessages([
                'kelas_id' => 'Modul Penilaian TP saat ini dikhususkan untuk jenjang SD.',
            ]);
        }

        $validAnggotaIds = AnggotaKelas::query()
            ->where('akademik_master_kelas_id', $kelas->id)
            ->pluck('id')
            ->all();
        $validAnggotaLookup = array_flip($validAnggotaIds);

        $tpIds = TujuanPembelajaran::query()
            ->where('akademik_master_tahun_ajaran_id', $kelas->akademik_master_tahun_ajaran_id)
            ->where('akademik_pelajaran_mata_pelajaran_id', $mapel->id)
            ->where('tingkatan_kelas', $kelas->tingkatan_kelas)
            ->where('status', true)
            ->pluck('id')
            ->all();

        $validAspekIds = $tpIds !== []
            ? AspekTp::query()
                ->whereIn('tujuan_pembelajaran_id', $tpIds)
                ->where('periode', $periode)
                ->pluck('id')
                ->all()
            : [];
        $validAspekLookup = array_flip($validAspekIds);

        $validSumatifIds = SumatifBab::query()
            ->where('akademik_master_tahun_ajaran_id', $kelas->akademik_master_tahun_ajaran_id)
            ->where('akademik_pelajaran_mata_pelajaran_id', $mapel->id)
            ->where('tingkatan_kelas', (string) $kelas->tingkatan_kelas)
            ->where('periode', $periode)
            ->pluck('id')
            ->all();
        $validSumatifLookup = array_flip($validSumatifIds);

        $now = now();
        $upsertRows = [];
        $sumatifUpsertRows = [];
        $touchedAnggotaIds = [];

        foreach ($nilaiMatrix as $rawAnggotaId => $aspekValues) {
            $anggotaId = (int) $rawAnggotaId;
            if (! isset($validAnggotaLookup[$anggotaId]) || ! is_array($aspekValues)) {
                continue;
            }

            foreach ($aspekValues as $rawAspekId => $rawNilai) {
                $aspekId = (int) $rawAspekId;
                if (! isset($validAspekLookup[$aspekId])) {
                    continue;
                }

                $upsertRows[] = [
                    'aspek_tp_id' => $aspekId,
                    'anggota_kelas_id' => $anggotaId,
                    'nilai' => $this->normalizeScoreValue($rawNilai),
                    'is_remedial' => false,
                    'catatan' => null,
                    'dinilai_oleh_nipy' => $dinilaiOlehNipy,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $touchedAnggotaIds[$anggotaId] = true;
            }
        }

        foreach ($sumatifMatrix as $rawAnggotaId => $babValues) {
            $anggotaId = (int) $rawAnggotaId;
            if (! isset($validAnggotaLookup[$anggotaId]) || ! is_array($babValues)) {
                continue;
            }

            foreach ($babValues as $rawBabId => $rawNilai) {
                $babId = (int) $rawBabId;
                if (! isset($validSumatifLookup[$babId])) {
                    continue;
                }

                $sumatifUpsertRows[] = [
                    'sumatif_bab_id' => $babId,
                    'anggota_kelas_id' => $anggotaId,
                    'nilai' => $this->normalizeScoreValue($rawNilai),
                    'dinilai_oleh_nipy' => $dinilaiOlehNipy,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $touchedAnggotaIds[$anggotaId] = true;
            }
        }

        $normalizedUjianByAnggota = [];
        $savedUjianCells = 0;
        foreach ($ujianScores as $rawAnggotaId => $rawUjian) {
            $anggotaId = (int) $rawAnggotaId;
            if (! isset($validAnggotaLookup[$anggotaId])) {
                continue;
            }

            $normalized = $this->normalizeScoreValue($rawUjian);
            $normalizedUjianByAnggota[$anggotaId] = $normalized;
            if ($normalized !== null) {
                $savedUjianCells++;
                $touchedAnggotaIds[$anggotaId] = true;
            }
        }

        if ($upsertRows === [] && $sumatifUpsertRows === [] && $normalizedUjianByAnggota === []) {
            return ['saved_cells' => 0, 'updated_students' => 0];
        }

        DB::transaction(function () use ($upsertRows, $sumatifUpsertRows, $kelas, $mapel, $periode, $normalizedUjianByAnggota): void {
            if ($upsertRows !== []) {
                foreach (array_chunk($upsertRows, 500) as $chunk) {
                    NilaiAspek::query()->upsert(
                        $chunk,
                        ['aspek_tp_id', 'anggota_kelas_id'],
                        ['nilai', 'dinilai_oleh_nipy', 'updated_at']
                    );
                }
            }

            if ($sumatifUpsertRows !== []) {
                foreach (array_chunk($sumatifUpsertRows, 500) as $chunk) {
                    NilaiSumatifBab::query()->upsert(
                        $chunk,
                        ['sumatif_bab_id', 'anggota_kelas_id'],
                        ['nilai', 'dinilai_oleh_nipy', 'updated_at']
                    );
                }
            }

            $this->recalculateScope(
                $kelas->akademik_master_tahun_ajaran_id,
                $mapel->id,
                (string) $kelas->tingkatan_kelas,
                [$kelas->id],
                false,
                $periode,
                $normalizedUjianByAnggota
            );
        });

        return [
            'saved_cells' => count($upsertRows) + count($sumatifUpsertRows) + $savedUjianCells,
            'updated_students' => count($touchedAnggotaIds),
        ];
    }

    /**
     * Memperbarui teks deskripsi capaian rapor per siswa secara manual.
     */
    public function updateDeskripsi(RekapMapel $rekapMapel, ?string $deskripsi): RekapMapel
    {
        $rekapMapel->loadMissing([
            'anggotaKelas:id,akademik_master_kelas_id',
            'anggotaKelas.kelas:id,nama_kelas',
            'mataPelajaran:id,nama_mata_pelajaran',
        ]);

        $this->finalisasiService->assertMapelGridEditableByIds(
            (int) $rekapMapel->akademik_master_tahun_ajaran_id,
            (int) ($rekapMapel->anggotaKelas?->akademik_master_kelas_id ?? 0),
            (int) $rekapMapel->akademik_pelajaran_mata_pelajaran_id,
            (string) $rekapMapel->periode,
            $rekapMapel->anggotaKelas?->kelas?->nama_kelas,
            $rekapMapel->mataPelajaran?->nama_mata_pelajaran
        );

        $rekapMapel->update([
            'deskripsi_capaian' => filled($deskripsi) ? trim($deskripsi) : null,
        ]);

        return $rekapMapel->refresh();
    }

    /**
     * Menghasilkan ulang seluruh deskripsi capaian rapor otomatis untuk satu kelas & periode.
     */
    public function regenerateAllDeskripsi(Kelas $kelas, MataPelajaran $mapel, string $periode): int
    {
        $periode = $this->normalizePeriode($periode);
        $this->finalisasiService->assertMapelGridEditable($kelas, $mapel, $periode);

        return DB::transaction(fn (): int => $this->recalculateScope(
            $kelas->akademik_master_tahun_ajaran_id,
            $mapel->id,
            (string) $kelas->tingkatan_kelas,
            [$kelas->id],
            true,
            null,
            [],
            $periode
        ));
    }

    /**
     * Menghitung ulang NA TP, NA Rapor Mid, NA Rapor Semester (gabungan Mid+Semester + PSAS/PSAT bila ada), dan predikat KKM secara batch.
     *
     * @param  list<int>|null  $onlyKelasIds
     * @param  array<int, float|null>  $ujianOverrides
     */
    public function recalculateScope(
        int $tahunAjaranId,
        int $mapelId,
        string $tingkatanKelas,
        ?array $onlyKelasIds = null,
        bool $overwriteDeskripsi = false,
        ?string $ujianPeriode = null,
        array $ujianOverrides = [],
        ?string $onlyPeriodeOverwrite = null
    ): int {
        $kelasIds = $onlyKelasIds ?? Kelas::query()
            ->where('akademik_master_tahun_ajaran_id', $tahunAjaranId)
            ->where('jenjang_kelas', Kkm::TARGET_UNIT)
            ->where('tingkatan_kelas', $tingkatanKelas)
            ->pluck('id')
            ->all();

        if ($kelasIds === []) {
            return 0;
        }

        $anggotaIds = AnggotaKelas::query()
            ->whereIn('akademik_master_kelas_id', $kelasIds)
            ->pluck('id')
            ->all();

        if ($anggotaIds === []) {
            return 0;
        }

        $tpList = TujuanPembelajaran::query()
            ->select(['id', 'kode_tp', 'tujuan_pembelajaran', 'lingkup_materi', 'urutan'])
            ->where('akademik_master_tahun_ajaran_id', $tahunAjaranId)
            ->where('akademik_pelajaran_mata_pelajaran_id', $mapelId)
            ->where('tingkatan_kelas', $tingkatanKelas)
            ->where('status', true)
            ->ordered()
            ->get();

        $allSumatifBabs = SumatifBab::query()
            ->select(['id', 'periode', 'urutan'])
            ->where('akademik_master_tahun_ajaran_id', $tahunAjaranId)
            ->where('akademik_pelajaran_mata_pelajaran_id', $mapelId)
            ->where('tingkatan_kelas', $tingkatanKelas)
            ->orderBy('urutan')
            ->orderBy('id')
            ->get();

        if ($tpList->isEmpty() && $allSumatifBabs->isEmpty()) {
            return 0;
        }

        $tpIds = $tpList->pluck('id')->all();
        $sumatifBabIds = $allSumatifBabs->pluck('id')->all();
        $midSumatifBabs = $allSumatifBabs->where('periode', PeriodeTp::PERIODE_MID)->values();
        $kkmBundle = $this->resolveKkmBundle($tahunAjaranId, $mapelId, $tingkatanKelas);
        $nilaiKkm = $kkmBundle['nilai_kkm'];
        $bobotTp = $kkmBundle['bobot_tp'];
        $bobotSumatif = $kkmBundle['bobot_sumatif'];
        $bobotUjian = $kkmBundle['bobot_ujian'];
        $predikats = $kkmBundle['predikats'];

        $periodeSettings = $tpIds !== []
            ? PeriodeTp::query()
                ->whereIn('tujuan_pembelajaran_id', $tpIds)
                ->get()
                ->keyBy(static fn (PeriodeTp $item): string => "{$item->tujuan_pembelajaran_id}:{$item->periode}")
            : collect();

        $aspekList = $tpIds !== []
            ? AspekTp::query()
                ->whereIn('tujuan_pembelajaran_id', $tpIds)
                ->orderBy('urutan')
                ->orderBy('id')
                ->get()
            : collect();

        $aspekByTpPeriode = [];
        foreach ($aspekList as $aspek) {
            if (! $aspek->masuk_hitung_na_tp) {
                continue;
            }
            $aspekByTpPeriode["{$aspek->tujuan_pembelajaran_id}:{$aspek->periode}"][] = $aspek;
        }

        $aspekIds = $aspekList->pluck('id')->all();
        $nilaiByAnggotaAspek = [];
        if ($aspekIds !== []) {
            $nilaiRows = NilaiAspek::query()
                ->select(['aspek_tp_id', 'anggota_kelas_id', 'nilai'])
                ->whereIn('aspek_tp_id', $aspekIds)
                ->whereIn('anggota_kelas_id', $anggotaIds)
                ->get();

            foreach ($nilaiRows as $row) {
                $nilaiByAnggotaAspek["{$row->anggota_kelas_id}:{$row->aspek_tp_id}"] = $row->nilai;
            }
        }

        $sumatifByAnggotaBab = [];
        if ($sumatifBabIds !== []) {
            $sumatifRows = NilaiSumatifBab::query()
                ->select(['sumatif_bab_id', 'anggota_kelas_id', 'nilai'])
                ->whereIn('sumatif_bab_id', $sumatifBabIds)
                ->whereIn('anggota_kelas_id', $anggotaIds)
                ->get();

            foreach ($sumatifRows as $row) {
                $sumatifByAnggotaBab["{$row->anggota_kelas_id}:{$row->sumatif_bab_id}"] = $row->nilai;
            }
        }

        $existingRekapTp = $tpIds !== []
            ? RekapTp::query()
                ->whereIn('tujuan_pembelajaran_id', $tpIds)
                ->whereIn('anggota_kelas_id', $anggotaIds)
                ->get()
                ->keyBy(static fn (RekapTp $item): string => "{$item->anggota_kelas_id}:{$item->tujuan_pembelajaran_id}:{$item->periode}")
            : collect();

        $existingRekapMapel = RekapMapel::query()
            ->where('akademik_master_tahun_ajaran_id', $tahunAjaranId)
            ->where('akademik_pelajaran_mata_pelajaran_id', $mapelId)
            ->whereIn('anggota_kelas_id', $anggotaIds)
            ->get()
            ->keyBy(static fn (RekapMapel $item): string => "{$item->anggota_kelas_id}:{$item->periode}");

        $now = now();
        $rekapTpUpserts = [];
        $computedTpMap = [];

        foreach ($anggotaIds as $anggotaId) {
            foreach ($tpList as $tp) {
                foreach ([PeriodeTp::PERIODE_MID, PeriodeTp::PERIODE_SEMESTER] as $periode) {
                    $key = "{$anggotaId}:{$tp->id}:{$periode}";
                    /** @var RekapTp|null $prevRekapTp */
                    $prevRekapTp = $existingRekapTp->get($key);

                    $aspeks = $aspekByTpPeriode["{$tp->id}:{$periode}"] ?? [];

                    if ($prevRekapTp !== null && $prevRekapTp->is_manual_override) {
                        $naTp = $prevRekapTp->nilai_akhir_tp;
                    } elseif ($aspeks === []) {
                        $naTp = null;
                    } else {
                        $values = [];
                        $weights = [];
                        foreach ($aspeks as $aspek) {
                            $values[$aspek->id] = $nilaiByAnggotaAspek["{$anggotaId}:{$aspek->id}"] ?? null;
                            $weights[$aspek->id] = $aspek->bobot;
                        }
                        $naTp = self::computeNaTp($values, $weights);
                    }

                    $matchedPredikat = $naTp !== null ? Kkm::resolvePredikatForNilai($naTp, $predikats) : null;
                    $tuntas = $naTp !== null
                        ? ($matchedPredikat !== null ? $matchedPredikat->is_tuntas : ($naTp >= $nilaiKkm))
                        : null;

                    $computedTpMap[$key] = $naTp;

                    $shouldWrite = $naTp !== null || $prevRekapTp !== null;
                    if ($shouldWrite) {
                        $rekapTpUpserts[] = [
                            'tujuan_pembelajaran_id' => $tp->id,
                            'anggota_kelas_id' => $anggotaId,
                            'periode' => $periode,
                            'nilai_akhir_tp' => $naTp,
                            'predikat' => $matchedPredikat?->predikat,
                            'tuntas_kkm' => $tuntas,
                            'is_manual_override' => $prevRekapTp?->is_manual_override ?? false,
                            'created_at' => $prevRekapTp?->created_at ?? $now,
                            'updated_at' => $now,
                        ];
                    }
                }
            }
        }

        if ($rekapTpUpserts !== []) {
            foreach (array_chunk($rekapTpUpserts, 500) as $chunk) {
                RekapTp::query()->upsert(
                    $chunk,
                    ['tujuan_pembelajaran_id', 'anggota_kelas_id', 'periode'],
                    ['nilai_akhir_tp', 'predikat', 'tuntas_kkm', 'updated_at']
                );
            }
        }

        $rekapMapelUpserts = [];
        $updatedRekapCount = 0;

        foreach ($anggotaIds as $anggotaId) {
            // 1. Periode Mid: seluruh TP yang memiliki nilai pada periode mid
            $midTpEntries = [];
            // 2. Periode Semester: menggabungkan seluruh NA TP dari mid + semester (prioritas nilai semester bila diisi ulang)
            $semesterTpEntries = [];

            foreach ($tpList as $tp) {
                $midScore = $computedTpMap["{$anggotaId}:{$tp->id}:".PeriodeTp::PERIODE_MID] ?? null;

                if ($midScore !== null) {
                    $midTpEntries[] = [
                        'kode_tp' => $tp->kode_tp,
                        'tujuan_pembelajaran' => $tp->tujuan_pembelajaran,
                        'lingkup_materi' => $tp->lingkup_materi,
                        'nilai' => $midScore,
                    ];
                }

                $semScore = $computedTpMap["{$anggotaId}:{$tp->id}:".PeriodeTp::PERIODE_SEMESTER] ?? null;
                $combinedScore = $semScore ?? $midScore;

                if ($combinedScore !== null) {
                    $semesterTpEntries[] = [
                        'kode_tp' => $tp->kode_tp,
                        'tujuan_pembelajaran' => $tp->tujuan_pembelajaran,
                        'lingkup_materi' => $tp->lingkup_materi,
                        'nilai' => $combinedScore,
                    ];
                }
            }

            $midSumatifValues = [];
            foreach ($midSumatifBabs as $midBab) {
                $midSumatifValues[$midBab->id] = $sumatifByAnggotaBab["{$anggotaId}:{$midBab->id}"] ?? null;
            }

            $semSumatifValues = [];
            foreach ($allSumatifBabs as $anyBab) {
                $semSumatifValues[$anyBab->id] = $sumatifByAnggotaBab["{$anggotaId}:{$anyBab->id}"] ?? null;
            }

            $rataSumatifByPeriode = [
                PeriodeTp::PERIODE_MID => self::computeRataSumatif($midSumatifValues),
                PeriodeTp::PERIODE_SEMESTER => self::computeRataSumatif($semSumatifValues),
            ];

            foreach ([PeriodeTp::PERIODE_MID => $midTpEntries, PeriodeTp::PERIODE_SEMESTER => $semesterTpEntries] as $periode => $entries) {
                /** @var RekapMapel|null $prevMapel */
                $prevMapel = $existingRekapMapel->get("{$anggotaId}:{$periode}");

                $hasUjianOverride = $ujianPeriode === $periode && array_key_exists($anggotaId, $ujianOverrides);
                $nilaiUjian = $hasUjianOverride
                    ? $ujianOverrides[$anggotaId]
                    : $prevMapel?->nilai_ujian;
                $rataRataSumatif = $rataSumatifByPeriode[$periode];

                if ($entries === [] && $rataRataSumatif === null && $prevMapel === null && $nilaiUjian === null) {
                    continue;
                }

                $countTp = count($entries);
                $rataRataTp = $countTp > 0
                    ? (float) round(array_sum(array_column($entries, 'nilai')) / $countTp, 0)
                    : null;

                $nilaiRapor = self::computeNaRapor(
                    $rataRataTp,
                    $nilaiUjian !== null ? (float) $nilaiUjian : null,
                    $bobotTp,
                    $bobotUjian,
                    $rataRataSumatif,
                    $bobotSumatif
                );
                $matched = $nilaiRapor !== null ? Kkm::resolvePredikatForNilai($nilaiRapor, $predikats) : null;
                $tuntas = $nilaiRapor !== null
                    ? ($matched !== null ? $matched->is_tuntas : ($nilaiRapor >= $nilaiKkm))
                    : null;

                $autoDeskripsi = self::buildDeskripsiCapaian($entries, $nilaiKkm, $predikats);
                $shouldOverwriteDeskripsi = $overwriteDeskripsi
                    && ($onlyPeriodeOverwrite === null || $onlyPeriodeOverwrite === $periode);
                $deskripsi = $shouldOverwriteDeskripsi || $prevMapel === null || ! filled($prevMapel->deskripsi_capaian)
                    ? $autoDeskripsi
                    : $prevMapel->deskripsi_capaian;

                $rekapMapelUpserts[] = [
                    'akademik_master_tahun_ajaran_id' => $tahunAjaranId,
                    'akademik_pelajaran_mata_pelajaran_id' => $mapelId,
                    'anggota_kelas_id' => $anggotaId,
                    'periode' => $periode,
                    'rata_rata_tp' => $rataRataTp,
                    'rata_rata_sumatif' => $rataRataSumatif,
                    'nilai_ujian' => $nilaiUjian,
                    'nilai_akhir_rapor' => $nilaiRapor,
                    'jumlah_tp_dinilai' => $countTp,
                    'nilai_kkm_snapshot' => $nilaiRapor !== null ? $nilaiKkm : null,
                    'bobot_tp_snapshot' => $bobotTp,
                    'bobot_sumatif_snapshot' => $bobotSumatif,
                    'bobot_ujian_snapshot' => $bobotUjian,
                    'predikat_rapor' => $matched?->predikat,
                    'keterangan_predikat' => $matched?->keterangan,
                    'tuntas_kkm' => $tuntas,
                    'deskripsi_capaian' => $deskripsi,
                    'created_at' => $prevMapel?->created_at ?? $now,
                    'updated_at' => $now,
                ];
                if ($onlyPeriodeOverwrite === null || $onlyPeriodeOverwrite === $periode) {
                    $updatedRekapCount++;
                }
            }
        }

        if ($rekapMapelUpserts !== []) {
            foreach (array_chunk($rekapMapelUpserts, 500) as $chunk) {
                RekapMapel::query()->upsert(
                    $chunk,
                    ['akademik_pelajaran_mata_pelajaran_id', 'anggota_kelas_id', 'periode'],
                    [
                        'rata_rata_tp',
                        'rata_rata_sumatif',
                        'nilai_ujian',
                        'nilai_akhir_rapor',
                        'jumlah_tp_dinilai',
                        'nilai_kkm_snapshot',
                        'bobot_tp_snapshot',
                        'bobot_sumatif_snapshot',
                        'bobot_ujian_snapshot',
                        'predikat_rapor',
                        'keterangan_predikat',
                        'tuntas_kkm',
                        'deskripsi_capaian',
                        'updated_at',
                    ]
                );
            }
        }

        return $updatedRekapCount;
    }

    /**
     * Memastikan setiap TP memiliki pengaturan periode & minimal 1 aspek default untuk periode aktif.
     *
     * @param  EloquentCollection<int, TujuanPembelajaran>  $tpList
     * @return array{0: Collection<int, PeriodeTp>, 1: Collection<int, Collection<int, AspekTp>>}
     */
    protected function ensureAndLoadTpConfigurations(EloquentCollection $tpList, string $periode): array
    {
        $tpIds = $tpList->pluck('id')->all();
        $now = now();

        $periodeSettings = PeriodeTp::query()
            ->whereIn('tujuan_pembelajaran_id', $tpIds)
            ->where('periode', $periode)
            ->get()
            ->keyBy('tujuan_pembelajaran_id');

        $missingPeriodeRows = [];
        foreach ($tpList as $tp) {
            if (! $periodeSettings->has($tp->id)) {
                $missingPeriodeRows[] = [
                    'tujuan_pembelajaran_id' => $tp->id,
                    'periode' => $periode,
                    'masuk_rapor' => true,
                    'urutan' => $tp->urutan ?? 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($missingPeriodeRows !== []) {
            PeriodeTp::query()->insertOrIgnore($missingPeriodeRows);
            $periodeSettings = PeriodeTp::query()
                ->whereIn('tujuan_pembelajaran_id', $tpIds)
                ->where('periode', $periode)
                ->get()
                ->keyBy('tujuan_pembelajaran_id');
        }

        $aspeks = AspekTp::query()
            ->whereIn('tujuan_pembelajaran_id', $tpIds)
            ->where('periode', $periode)
            ->orderBy('urutan')
            ->orderBy('id')
            ->get();

        $tpIdsWithAspek = $aspeks->pluck('tujuan_pembelajaran_id')->flip();
        $missingAspekRows = [];
        foreach ($tpIds as $tpId) {
            if (! $tpIdsWithAspek->has($tpId)) {
                $missingAspekRows[] = [
                    'tujuan_pembelajaran_id' => $tpId,
                    'periode' => $periode,
                    'nama_aspek' => AspekTp::DEFAULT_NAMA_ASPEK,
                    'teknik_penilaian' => null,
                    'bobot' => 100,
                    'masuk_hitung_na_tp' => true,
                    'urutan' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($missingAspekRows !== []) {
            AspekTp::query()->insertOrIgnore($missingAspekRows);
            $aspeks = AspekTp::query()
                ->whereIn('tujuan_pembelajaran_id', $tpIds)
                ->where('periode', $periode)
                ->orderBy('urutan')
                ->orderBy('id')
                ->get();
        }

        return [$periodeSettings, $aspeks->groupBy('tujuan_pembelajaran_id')];
    }

    /**
     * @return array{
     *     model: ?Kkm,
     *     has_config: bool,
     *     nilai_kkm: float,
     *     bobot_tp: int,
     *     bobot_sumatif: int,
     *     bobot_ujian: int,
     *     predikats: EloquentCollection<int, KkmPredikat>
     * }
     */
    protected function resolveKkmBundle(int $tahunAjaranId, int $mapelId, string $tingkatanKelas): array
    {
        $kkm = Kkm::query()
            ->where('akademik_master_tahun_ajaran_id', $tahunAjaranId)
            ->where('akademik_pelajaran_mata_pelajaran_id', $mapelId)
            ->where('tingkatan_kelas', $tingkatanKelas)
            ->where('status', true)
            ->with('predikats')
            ->first();

        $bobotTp = $kkm !== null ? (int) $kkm->bobot_tp : Kkm::DEFAULT_BOBOT_TP;
        $bobotSumatif = $kkm !== null ? (int) ($kkm->bobot_sumatif ?? Kkm::DEFAULT_BOBOT_SUMATIF) : Kkm::DEFAULT_BOBOT_SUMATIF;
        $bobotUjian = $kkm !== null ? (int) $kkm->bobot_ujian : Kkm::DEFAULT_BOBOT_UJIAN;

        if ($kkm !== null && $kkm->predikats->isNotEmpty()) {
            return [
                'model' => $kkm,
                'has_config' => true,
                'nilai_kkm' => (float) $kkm->nilai_kkm,
                'bobot_tp' => $bobotTp,
                'bobot_sumatif' => $bobotSumatif,
                'bobot_ujian' => $bobotUjian,
                'predikats' => $kkm->predikats,
            ];
        }

        return [
            'model' => $kkm,
            'has_config' => false,
            'nilai_kkm' => $kkm !== null ? (float) $kkm->nilai_kkm : Kkm::DEFAULT_KKM,
            'bobot_tp' => $bobotTp,
            'bobot_sumatif' => $bobotSumatif,
            'bobot_ujian' => $bobotUjian,
            'predikats' => $this->buildVirtualDefaultPredikats($kkm !== null ? (float) $kkm->nilai_kkm : Kkm::DEFAULT_KKM),
        ];
    }

    protected function normalizeScoreValue(mixed $rawValue): ?float
    {
        if ($rawValue === null || $rawValue === '') {
            return null;
        }

        $numeric = (float) str_replace(',', '.', (string) $rawValue);

        return round(max(0.0, min(100.0, $numeric)), 2);
    }

    /**
     * @return EloquentCollection<int, KkmPredikat>
     */
    protected function buildVirtualDefaultPredikats(float $kkmNilai = Kkm::DEFAULT_KKM): EloquentCollection
    {
        $items = array_map(
            static fn (array $row): KkmPredikat => KkmPredikat::make($row),
            Kkm::generateDefaultPredikats($kkmNilai)
        );

        return new EloquentCollection($items);
    }

    /**
     * @return EloquentCollection<int, AnggotaKelas>
     */
    protected function getSortedStudentsInKelas(int $kelasId): EloquentCollection
    {
        $rows = AnggotaKelas::query()
            ->select(['id', 'siswa_nis', 'akademik_master_kelas_id', 'pendaftaran'])
            ->with(['siswa:nis,nama_lengkap,nisn,jenis_kelamin,status'])
            ->where('akademik_master_kelas_id', $kelasId)
            ->get()
            ->sortBy(static fn (AnggotaKelas $anggota): string => mb_strtolower((string) ($anggota->siswa?->nama_lengkap ?? $anggota->siswa_nis)))
            ->values();

        return new EloquentCollection($rows->all());
    }

    /**
     * @return EloquentCollection<int, TahunAjaran>
     */
    protected function getTahunAjaranOptions(): EloquentCollection
    {
        return TahunAjaran::query()
            ->select(['id', 'nama_tahun_ajaran', 'semester', 'is_active'])
            ->orderByDesc('is_active')
            ->orderByDesc('nama_tahun_ajaran')
            ->orderBy('semester')
            ->get();
    }

    /**
     * @return EloquentCollection<int, Kelas>
     */
    protected function getSdKelasOptions(int $tahunAjaranId): EloquentCollection
    {
        return Kelas::query()
            ->select(['id', 'akademik_master_tahun_ajaran_id', 'tingkatan_kelas', 'nama_kelas', 'jenjang_kelas'])
            ->where('akademik_master_tahun_ajaran_id', $tahunAjaranId)
            ->where('jenjang_kelas', Kkm::TARGET_UNIT)
            ->orderBy('tingkatan_kelas')
            ->orderBy('nama_kelas')
            ->get();
    }

    /**
     * @return EloquentCollection<int, MataPelajaran>
     */
    protected function getSdMapelOptions(): EloquentCollection
    {
        return MataPelajaran::query()
            ->select(['id', 'singkatan', 'nama_mata_pelajaran', 'unit', 'urutan'])
            ->where('unit', Kkm::TARGET_UNIT)
            ->where('status', true)
            ->orderBy('urutan')
            ->orderBy('nama_mata_pelajaran')
            ->get();
    }

    /**
     * @param  EloquentCollection<int, TahunAjaran>  $tahunAjaranOptions
     */
    protected function resolveDefaultTahunAjaran(EloquentCollection $tahunAjaranOptions): ?TahunAjaran
    {
        return $tahunAjaranOptions->firstWhere('is_active', true) ?? $tahunAjaranOptions->first();
    }

    protected function normalizePeriode(mixed $periode): string
    {
        $normalized = strtolower(trim((string) $periode));

        return $normalized === PeriodeTp::PERIODE_SEMESTER
            ? PeriodeTp::PERIODE_SEMESTER
            : PeriodeTp::PERIODE_MID;
    }

    protected function resolvePositiveInt(mixed $value): ?int
    {
        if (is_int($value) && $value > 0) {
            return $value;
        }

        if (is_string($value) && ctype_digit(trim($value))) {
            $parsed = (int) trim($value);

            return $parsed > 0 ? $parsed : null;
        }

        return null;
    }
}
