<?php

namespace App\Services\Admin\Penilaian;

use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Admin\Penilaian\Kkm;
use App\Models\Admin\Penilaian\KkmPredikat;
use App\Models\Admin\Penilaian\RekapMapel;
use App\Models\Admin\Penilaian\RekapTp;
use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KkmService
{
    /**
     * Membangun query daftar KKM SD beserta rentang predikat tanpa N+1.
     *
     * @param  array{
     *     tahun_ajaran_id?: string|int|null,
     *     mata_pelajaran_id?: string|int|null,
     *     tingkatan_kelas?: string|null
     * }  $filters
     * @return Builder<Kkm>
     */
    public function buildIndexQuery(array $filters = []): Builder
    {
        $tahunAjaranId = $this->resolvePositiveInt($filters['tahun_ajaran_id'] ?? null);
        $mapelId = $this->resolvePositiveInt($filters['mata_pelajaran_id'] ?? null);
        $tingkatan = trim((string) ($filters['tingkatan_kelas'] ?? ''));

        return Kkm::query()
            ->select([
                'id',
                'akademik_master_tahun_ajaran_id',
                'akademik_pelajaran_mata_pelajaran_id',
                'tingkatan_kelas',
                'nilai_kkm',
                'bobot_tp',
                'bobot_ujian',
                'status',
                'updated_at',
            ])
            ->withMasterRelations()
            ->whereHas('mataPelajaran', static function (Builder $mpQuery): void {
                $mpQuery->where($mpQuery->qualifyColumn('unit'), Kkm::TARGET_UNIT);
            })
            ->when(
                $tahunAjaranId !== null,
                static fn (Builder $query) => $query->where(
                    $query->qualifyColumn('akademik_master_tahun_ajaran_id'),
                    $tahunAjaranId
                )
            )
            ->when(
                $mapelId !== null,
                static fn (Builder $query) => $query->where(
                    $query->qualifyColumn('akademik_pelajaran_mata_pelajaran_id'),
                    $mapelId
                )
            )
            ->when(
                $tingkatan !== '',
                static fn (Builder $query) => $query->where($query->qualifyColumn('tingkatan_kelas'), $tingkatan)
            )
            ->orderBy('tingkatan_kelas')
            ->orderBy('id');
    }

    /**
     * Mengambil data lengkap halaman indeks KKM & Rentang Predikat SD tanpa N+1.
     *
     * @param  array<string, mixed>  $input
     * @return array{
     *     kkmList: Collection<int, Kkm>,
     *     filters: array<string, string>,
     *     tahunAjaranOptions: Collection<int, TahunAjaran>,
     *     mataPelajaranOptions: Collection<int, MataPelajaran>,
     *     tingkatanOptions: list<string>
     * }
     */
    public function getIndexData(array $input = []): array
    {
        $tahunAjaranOptions = $this->getTahunAjaranOptions();
        $defaultTa = $this->resolveDefaultTahunAjaran($tahunAjaranOptions);

        $rawTa = $input['tahun_ajaran_id'] ?? null;
        $selectedTaId = $rawTa === 'all'
            ? ''
            : ($this->resolvePositiveInt($rawTa) !== null
                ? (string) $this->resolvePositiveInt($rawTa)
                : (string) ($defaultTa?->id ?? ''));

        $filters = [
            'tahun_ajaran_id' => $selectedTaId,
            'mata_pelajaran_id' => (string) ($this->resolvePositiveInt($input['mata_pelajaran_id'] ?? null) ?? ''),
            'tingkatan_kelas' => trim((string) ($input['tingkatan_kelas'] ?? '')),
        ];

        return [
            'kkmList' => $this->buildIndexQuery($filters)->get(),
            'filters' => $filters,
            'tahunAjaranOptions' => $tahunAjaranOptions,
            'mataPelajaranOptions' => $this->getSdMapelOptions(),
            'tingkatanOptions' => Kkm::SD_TINGKATAN_OPTIONS,
        ];
    }

    /**
     * Mengambil opsi form create/edit KKM (khusus SD).
     *
     * @param  array<string, mixed>  $input
     * @return array{
     *     tahunAjaranOptions: Collection<int, TahunAjaran>,
     *     mataPelajaranOptions: Collection<int, MataPelajaran>,
     *     tingkatanOptions: list<string>,
     *     defaultPredikats: list<array{
     *         predikat: string,
     *         keterangan: string,
     *         nilai_min: int,
     *         nilai_max: int,
     *         is_tuntas: bool,
     *         urutan: int
     *     }>,
     *     defaults: array<string, string>
     * }
     */
    public function getFormOptions(array $input = []): array
    {
        $tahunAjaranOptions = $this->getTahunAjaranOptions();
        $defaultTa = $this->resolveDefaultTahunAjaran($tahunAjaranOptions);

        return [
            'tahunAjaranOptions' => $tahunAjaranOptions,
            'mataPelajaranOptions' => $this->getSdMapelOptions(),
            'tingkatanOptions' => Kkm::SD_TINGKATAN_OPTIONS,
            'defaultPredikats' => Kkm::generateDefaultPredikats(Kkm::DEFAULT_KKM),
            'defaults' => [
                'tahun_ajaran_id' => (string) ($this->resolvePositiveInt($input['tahun_ajaran_id'] ?? null) ?? $defaultTa?->id ?? ''),
                'mata_pelajaran_id' => (string) ($this->resolvePositiveInt($input['mata_pelajaran_id'] ?? null) ?? ''),
                'tingkatan_kelas' => trim((string) ($input['tingkatan_kelas'] ?? '1')),
            ],
        ];
    }

    /**
     * Memvalidasi dan menormalkan rentang predikat agar kontinu menutup 0–100 tanpa tumpang tindih.
     *
     * @param  array<int, array<string, mixed>>  $rawPredikats
     * @return list<array{
     *     predikat: string,
     *     keterangan: string,
     *     nilai_min: float,
     *     nilai_max: float,
     *     is_tuntas: bool,
     *     urutan: int
     * }>
     *
     * @throws ValidationException
     */
    public function validateAndNormalizePredikats(array $rawPredikats): array
    {
        $cleaned = [];
        $seenCodes = [];

        foreach ($rawPredikats as $index => $row) {
            $kode = strtoupper(trim((string) ($row['predikat'] ?? '')));
            $keterangan = trim((string) ($row['keterangan'] ?? ''));

            if ($kode === '' || $keterangan === '') {
                throw ValidationException::withMessages([
                    'predikats' => 'Setiap baris rentang predikat wajib memiliki kode predikat dan keterangan.',
                ]);
            }

            if (isset($seenCodes[$kode])) {
                throw ValidationException::withMessages([
                    'predikats' => "Kode predikat \"{$kode}\" tidak boleh duplikat.",
                ]);
            }
            $seenCodes[$kode] = true;

            $min = isset($row['nilai_min']) && is_numeric($row['nilai_min']) ? round((float) $row['nilai_min'], 2) : null;
            $max = isset($row['nilai_max']) && is_numeric($row['nilai_max']) ? round((float) $row['nilai_max'], 2) : null;

            if ($min === null || $max === null || $min < 0 || $max > 100 || $min > $max) {
                throw ValidationException::withMessages([
                    'predikats' => "Rentang nilai predikat \"{$kode}\" harus valid dalam batas 0 sampai 100 (Min <= Max).",
                ]);
            }

            $cleaned[] = [
                'predikat' => $kode,
                'keterangan' => $keterangan,
                'nilai_min' => $min,
                'nilai_max' => $max,
                'is_tuntas' => filter_var($row['is_tuntas'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'original_index' => (int) $index,
            ];
        }

        if ($cleaned === []) {
            throw ValidationException::withMessages([
                'predikats' => 'Minimal harus ada satu rentang predikat nilai.',
            ]);
        }

        $sortedAsc = $cleaned;
        usort($sortedAsc, static fn (array $a, array $b): int => $a['nilai_min'] <=> $b['nilai_min']);

        if ($sortedAsc[0]['nilai_min'] > 0.01) {
            throw ValidationException::withMessages([
                'predikats' => 'Rentang terendah harus dimulai dari 0 agar semua nilai memiliki predikat.',
            ]);
        }

        $lastIndex = count($sortedAsc) - 1;
        if ($sortedAsc[$lastIndex]['nilai_max'] < 99.99) {
            throw ValidationException::withMessages([
                'predikats' => 'Rentang tertinggi harus berakhir di 100 agar nilai maksimal memiliki predikat.',
            ]);
        }

        for ($i = 1; $i <= $lastIndex; $i++) {
            $prevMax = $sortedAsc[$i - 1]['nilai_max'];
            $currMin = $sortedAsc[$i]['nilai_min'];

            if ($currMin <= $prevMax) {
                throw ValidationException::withMessages([
                    'predikats' => "Rentang predikat \"{$sortedAsc[$i]['predikat']}\" bertumpukan dengan \"{$sortedAsc[$i - 1]['predikat']}\".",
                ]);
            }

            if (($currMin - $prevMax) > 1.0001) {
                throw ValidationException::withMessages([
                    'predikats' => "Terdapat celah kosong antara nilai {$prevMax} dan {$currMin}. Pastikan rentang tersambung rapat 0–100.",
                ]);
            }
        }

        // Urutkan tertinggi ke terendah agar rapi di tampilan & rapor
        $sortedDesc = array_reverse($sortedAsc);
        $normalized = [];

        foreach ($sortedDesc as $order => $item) {
            $normalized[] = [
                'predikat' => $item['predikat'],
                'keterangan' => $item['keterangan'],
                'nilai_min' => $item['nilai_min'],
                'nilai_max' => $item['nilai_max'],
                'is_tuntas' => $item['is_tuntas'],
                'urutan' => $order + 1,
            ];
        }

        return $normalized;
    }

    /**
     * Menyimpan konfigurasi KKM & rentang predikat beserta penyegaran predikat rekap secara batch.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveWithPredikats(array $data, ?Kkm $existing = null): Kkm
    {
        $tahunAjaranId = (int) $data['akademik_master_tahun_ajaran_id'];
        $mapelId = (int) $data['akademik_pelajaran_mata_pelajaran_id'];
        $primaryTingkatan = trim((string) $data['tingkatan_kelas']);
        $nilaiKkm = round((float) $data['nilai_kkm'], 2);
        $bobotTp = (int) ($data['bobot_tp'] ?? Kkm::DEFAULT_BOBOT_TP);
        $bobotUjian = (int) ($data['bobot_ujian'] ?? Kkm::DEFAULT_BOBOT_UJIAN);
        $status = (bool) ($data['status'] ?? true);

        if (($bobotError = Kkm::validateBobotPair($bobotTp, $bobotUjian)) !== null) {
            throw ValidationException::withMessages(['bobot_ujian' => $bobotError]);
        }

        $predikats = $this->validateAndNormalizePredikats($data['predikats'] ?? []);

        $targetTingkatans = [$primaryTingkatan];
        $copyTingkatans = $data['copy_to_tingkatan'] ?? [];
        if ($existing === null && is_array($copyTingkatans)) {
            foreach ($copyTingkatans as $tingkat) {
                $trimmed = trim((string) $tingkat);
                if (in_array($trimmed, Kkm::SD_TINGKATAN_OPTIONS, true) && ! in_array($trimmed, $targetTingkatans, true)) {
                    $targetTingkatans[] = $trimmed;
                }
            }
        }

        return DB::transaction(function () use (
            $tahunAjaranId,
            $mapelId,
            $primaryTingkatan,
            $targetTingkatans,
            $nilaiKkm,
            $bobotTp,
            $bobotUjian,
            $status,
            $predikats,
            $existing
        ): Kkm {
            $primaryKkm = null;

            foreach ($targetTingkatans as $tingkatan) {
                $kkmModel = $existing !== null && $tingkatan === $primaryTingkatan
                    ? $existing
                    : Kkm::query()
                        ->where('akademik_master_tahun_ajaran_id', $tahunAjaranId)
                        ->where('akademik_pelajaran_mata_pelajaran_id', $mapelId)
                        ->where('tingkatan_kelas', $tingkatan)
                        ->first();

                if ($kkmModel === null) {
                    $kkmModel = Kkm::create([
                        'akademik_master_tahun_ajaran_id' => $tahunAjaranId,
                        'akademik_pelajaran_mata_pelajaran_id' => $mapelId,
                        'tingkatan_kelas' => $tingkatan,
                        'nilai_kkm' => $nilaiKkm,
                        'bobot_tp' => $bobotTp,
                        'bobot_ujian' => $bobotUjian,
                        'status' => $status,
                    ]);
                } else {
                    $kkmModel->update([
                        'akademik_master_tahun_ajaran_id' => $tahunAjaranId,
                        'akademik_pelajaran_mata_pelajaran_id' => $mapelId,
                        'tingkatan_kelas' => $tingkatan,
                        'nilai_kkm' => $nilaiKkm,
                        'bobot_tp' => $bobotTp,
                        'bobot_ujian' => $bobotUjian,
                        'status' => $status,
                    ]);
                    $kkmModel->predikats()->delete();
                }

                $now = now();
                $rows = array_map(
                    static fn (array $item): array => [
                        'kkm_id' => $kkmModel->id,
                        'predikat' => $item['predikat'],
                        'keterangan' => $item['keterangan'],
                        'nilai_min' => $item['nilai_min'],
                        'nilai_max' => $item['nilai_max'],
                        'is_tuntas' => $item['is_tuntas'],
                        'urutan' => $item['urutan'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    $predikats
                );

                KkmPredikat::query()->insert($rows);
                $kkmModel->load('predikats');

                if ($tingkatan === $primaryTingkatan) {
                    $primaryKkm = $kkmModel;
                }
            }

            $this->refreshExistingRekapPredicates(
                $tahunAjaranId,
                $mapelId,
                $targetTingkatans,
                $nilaiKkm,
                $bobotTp,
                $bobotUjian,
                $primaryKkm->predikats
            );

            return $primaryKkm;
        });
    }

    /**
     * Menghapus konfigurasi KKM beserta detail predikat (cascade).
     */
    public function delete(Kkm $kkm): bool
    {
        return (bool) $kkm->delete();
    }

    /**
     * Menyegarkan predikat & status tuntas pada tabel rekap setelah perubahan KKM tanpa N+1.
     *
     * @param  list<string>  $tingkatans
     * @param  Collection<int, KkmPredikat>  $predikats
     */
    protected function refreshExistingRekapPredicates(
        int $tahunAjaranId,
        int $mapelId,
        array $tingkatans,
        float $nilaiKkm,
        int $bobotTp,
        int $bobotUjian,
        Collection $predikats
    ): void {
        $kelasIds = Kelas::query()
            ->where('akademik_master_tahun_ajaran_id', $tahunAjaranId)
            ->where('jenjang_kelas', Kkm::TARGET_UNIT)
            ->whereIn('tingkatan_kelas', $tingkatans)
            ->pluck('id')
            ->all();

        if ($kelasIds === []) {
            return;
        }

        $anggotaIds = AnggotaKelas::query()
            ->whereIn('akademik_master_kelas_id', $kelasIds)
            ->pluck('id')
            ->all();

        if ($anggotaIds === []) {
            return;
        }

        $tpIds = TujuanPembelajaran::query()
            ->where('akademik_master_tahun_ajaran_id', $tahunAjaranId)
            ->where('akademik_pelajaran_mata_pelajaran_id', $mapelId)
            ->whereIn('tingkatan_kelas', $tingkatans)
            ->pluck('id')
            ->all();

        $now = now();

        if ($tpIds !== []) {
            $rekapTps = RekapTp::query()
                ->whereIn('tujuan_pembelajaran_id', $tpIds)
                ->whereIn('anggota_kelas_id', $anggotaIds)
                ->whereNotNull('nilai_akhir_tp')
                ->get();

            if ($rekapTps->isNotEmpty()) {
                $tpRows = [];
                foreach ($rekapTps as $rekap) {
                    $nilai = (float) $rekap->nilai_akhir_tp;
                    $matched = Kkm::resolvePredikatForNilai($nilai, $predikats);
                    $tpRows[] = [
                        'tujuan_pembelajaran_id' => $rekap->tujuan_pembelajaran_id,
                        'anggota_kelas_id' => $rekap->anggota_kelas_id,
                        'periode' => $rekap->periode,
                        'nilai_akhir_tp' => $rekap->nilai_akhir_tp,
                        'predikat' => $matched?->predikat,
                        'tuntas_kkm' => $matched !== null ? $matched->is_tuntas : ($nilai >= $nilaiKkm),
                        'is_manual_override' => $rekap->is_manual_override,
                        'created_at' => $rekap->created_at ?? $now,
                        'updated_at' => $now,
                    ];
                }

                RekapTp::query()->upsert(
                    $tpRows,
                    ['tujuan_pembelajaran_id', 'anggota_kelas_id', 'periode'],
                    ['predikat', 'tuntas_kkm', 'updated_at']
                );
            }
        }

        $rekapMapels = RekapMapel::query()
            ->where('akademik_master_tahun_ajaran_id', $tahunAjaranId)
            ->where('akademik_pelajaran_mata_pelajaran_id', $mapelId)
            ->whereIn('anggota_kelas_id', $anggotaIds)
            ->where(static function (Builder $query): void {
                $query->whereNotNull('rata_rata_tp')->orWhereNotNull('nilai_ujian');
            })
            ->get();

        if ($rekapMapels->isNotEmpty()) {
            $mapelRows = [];
            foreach ($rekapMapels as $rekapMapel) {
                $nilai = PenilaianTpService::computeNaRapor(
                    $rekapMapel->rata_rata_tp,
                    $rekapMapel->nilai_ujian,
                    $bobotTp,
                    $bobotUjian
                );
                $matched = $nilai !== null ? Kkm::resolvePredikatForNilai($nilai, $predikats) : null;
                $mapelRows[] = [
                    'akademik_master_tahun_ajaran_id' => $rekapMapel->akademik_master_tahun_ajaran_id,
                    'akademik_pelajaran_mata_pelajaran_id' => $rekapMapel->akademik_pelajaran_mata_pelajaran_id,
                    'anggota_kelas_id' => $rekapMapel->anggota_kelas_id,
                    'periode' => $rekapMapel->periode,
                    'rata_rata_tp' => $rekapMapel->rata_rata_tp,
                    'nilai_ujian' => $rekapMapel->nilai_ujian,
                    'nilai_akhir_rapor' => $nilai,
                    'jumlah_tp_dinilai' => $rekapMapel->jumlah_tp_dinilai,
                    'nilai_kkm_snapshot' => $nilaiKkm,
                    'bobot_tp_snapshot' => $bobotTp,
                    'bobot_ujian_snapshot' => $bobotUjian,
                    'predikat_rapor' => $matched?->predikat,
                    'keterangan_predikat' => $matched?->keterangan,
                    'tuntas_kkm' => $nilai !== null ? ($matched !== null ? $matched->is_tuntas : ($nilai >= $nilaiKkm)) : null,
                    'deskripsi_capaian' => $rekapMapel->deskripsi_capaian,
                    'created_at' => $rekapMapel->created_at ?? $now,
                    'updated_at' => $now,
                ];
            }

            RekapMapel::query()->upsert(
                $mapelRows,
                ['akademik_pelajaran_mata_pelajaran_id', 'anggota_kelas_id', 'periode'],
                [
                    'nilai_akhir_rapor',
                    'nilai_kkm_snapshot',
                    'bobot_tp_snapshot',
                    'bobot_ujian_snapshot',
                    'predikat_rapor',
                    'keterangan_predikat',
                    'tuntas_kkm',
                    'updated_at',
                ]
            );
        }
    }

    /**
     * @return Collection<int, TahunAjaran>
     */
    protected function getTahunAjaranOptions(): Collection
    {
        return TahunAjaran::query()
            ->select(['id', 'nama_tahun_ajaran', 'semester', 'is_active'])
            ->orderByDesc('is_active')
            ->orderByDesc('nama_tahun_ajaran')
            ->orderBy('semester')
            ->get();
    }

    /**
     * @return Collection<int, MataPelajaran>
     */
    protected function getSdMapelOptions(): Collection
    {
        return MataPelajaran::query()
            ->select(['id', 'singkatan', 'nama_mata_pelajaran', 'unit', 'sifat', 'urutan'])
            ->where('unit', Kkm::TARGET_UNIT)
            ->where('status', true)
            ->orderBy('urutan')
            ->orderBy('nama_mata_pelajaran')
            ->get();
    }

    /**
     * @param  Collection<int, TahunAjaran>  $tahunAjaranOptions
     */
    protected function resolveDefaultTahunAjaran(Collection $tahunAjaranOptions): ?TahunAjaran
    {
        return $tahunAjaranOptions->firstWhere('is_active', true) ?? $tahunAjaranOptions->first();
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
