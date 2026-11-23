<?php

namespace App\Services\Admin\T2q;

use App\Models\Admin\DataMaster\AnggotaT2q;
use App\Models\Admin\Penilaian\PenilaianFinalisasi;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Models\Admin\T2q\RaporT2q;
use App\Models\Admin\T2q\YaumiyahTahfizh;
use App\Models\Admin\T2q\YaumiyahTahsin;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class T2qRaporResolverService
{
    /**
     * Menyelesaikan data Rapor T2Q (nilai terakhir, materi, predikat, deskripsi, dan status kunci) untuk sekumpulan Anggota T2Q tanpa N+1.
     *
     * @param  EloquentCollection<int, AnggotaT2q>  $anggotaT2qList
     * @return array<int, array{
     *     saved: ?RaporT2q,
     *     is_locked: bool,
     *     mid_cutoff_date: ?string,
     *     tahsin: array<string, mixed>,
     *     tahfizh: array<string, mixed>
     * }>
     */
    public function resolveForAnggotaT2qList(
        EloquentCollection $anggotaT2qList,
        int $tahunAjaranId,
        string $periode = PeriodeTp::PERIODE_MID
    ): array {
        if ($anggotaT2qList->isEmpty()) {
            return [];
        }

        $normPeriode = $this->normalizePeriode($periode);
        $anggotaT2qIds = $anggotaT2qList->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->values()
            ->all();

        if ($anggotaT2qIds === []) {
            return [];
        }

        $kelasIds = [];
        $kelasByAnggotaT2q = [];
        foreach ($anggotaT2qList as $anggota) {
            $kelasId = $anggota->relationLoaded('anggotaKelas')
                ? (int) ($anggota->anggotaKelas?->akademik_master_kelas_id ?? 0)
                : 0;
            $kelasByAnggotaT2q[(int) $anggota->id] = $kelasId;
            if ($kelasId > 0) {
                $kelasIds[$kelasId] = true;
            }
        }
        $uniqueKelasIds = array_keys($kelasIds);

        $savedByAnggota = RaporT2q::query()
            ->whereIn('anggota_t2q_id', $anggotaT2qIds)
            ->where('periode', $normPeriode)
            ->get()
            ->keyBy('anggota_t2q_id');

        $finalisasiByKelasPeriode = ($tahunAjaranId > 0 && $uniqueKelasIds !== [])
            ? PenilaianFinalisasi::query()
                ->where('akademik_master_tahun_ajaran_id', $tahunAjaranId)
                ->where('jenis_komponen', PenilaianFinalisasi::JENIS_T2Q)
                ->whereIn('akademik_master_kelas_id', $uniqueKelasIds)
                ->whereIn('periode', [PeriodeTp::PERIODE_MID, PeriodeTp::PERIODE_SEMESTER])
                ->get()
                ->keyBy(static fn (PenilaianFinalisasi $row): string => "{$row->akademik_master_kelas_id}:{$row->periode}")
            : collect();

        $midCutoffByAnggota = [];
        $lockByAnggota = [];

        foreach ($anggotaT2qList as $anggota) {
            $t2qId = (int) $anggota->id;
            $kelasId = $kelasByAnggotaT2q[$t2qId] ?? 0;

            /** @var PenilaianFinalisasi|null $midFin */
            $midFin = $kelasId > 0 ? $finalisasiByKelasPeriode->get("{$kelasId}:".PeriodeTp::PERIODE_MID) : null;
            /** @var PenilaianFinalisasi|null $semFin */
            $semFin = $kelasId > 0 ? $finalisasiByKelasPeriode->get("{$kelasId}:".PeriodeTp::PERIODE_SEMESTER) : null;

            $isMidFinal = $midFin?->isFinal() ?? false;
            $isSemFinal = $semFin?->isFinal() ?? false;

            $midCutoffByAnggota[$t2qId] = ($normPeriode === PeriodeTp::PERIODE_MID && $isMidFinal)
                ? $midFin?->finalized_at?->toDateString()
                : null;

            $lockByAnggota[$t2qId] = $normPeriode === PeriodeTp::PERIODE_SEMESTER
                ? $isSemFinal
                : ($isMidFinal || $isSemFinal);
        }

        $latestTahsinByAnggota = $this->loadLatestTahsinByAnggota($anggotaT2qIds, $midCutoffByAnggota);
        $latestTahfizhByAnggota = $this->loadLatestTahfizhByAnggota($anggotaT2qIds, $midCutoffByAnggota);

        $resolved = [];
        foreach ($anggotaT2qList as $anggota) {
            $t2qId = (int) $anggota->id;
            /** @var RaporT2q|null $saved */
            $saved = $savedByAnggota->get($t2qId);
            $isLocked = $lockByAnggota[$t2qId] ?? false;

            /** @var YaumiyahTahsin|null $latestTahsin */
            $latestTahsin = $latestTahsinByAnggota->get($t2qId);
            /** @var YaumiyahTahfizh|null $latestTahfizh */
            $latestTahfizh = $latestTahfizhByAnggota->get($t2qId);

            $resolved[$t2qId] = [
                'saved' => $saved,
                'is_locked' => $isLocked,
                'mid_cutoff_date' => $midCutoffByAnggota[$t2qId] ?? null,
                'tahsin' => $this->resolveChannel(
                    RaporT2q::MODE_TAHSIN,
                    $saved,
                    $latestTahsin,
                    $isLocked
                ),
                'tahfizh' => $this->resolveChannel(
                    RaporT2q::MODE_TAHFIZH,
                    $saved,
                    $latestTahfizh,
                    $isLocked
                ),
            ];
        }

        return $resolved;
    }

    /**
     * Mematerialkan snapshot nilai & deskripsi Rapor T2Q ke DB bagi siswa yang belum tersimpan saat finalisasi T2Q dijalankan.
     *
     * @param  EloquentCollection<int, AnggotaT2q>  $anggotaT2qList
     */
    public function snapshotMissingOrOutdatedDrafts(
        EloquentCollection $anggotaT2qList,
        int $tahunAjaranId,
        string $periode,
        ?User $actor = null
    ): int {
        if ($anggotaT2qList->isEmpty() || $tahunAjaranId <= 0) {
            return 0;
        }

        $normPeriode = $this->normalizePeriode($periode);
        $resolvedMap = $this->resolveForAnggotaT2qList($anggotaT2qList, $tahunAjaranId, $normPeriode);
        $now = now();
        $upserts = [];

        foreach ($anggotaT2qList as $anggota) {
            $t2qId = (int) $anggota->id;
            $item = $resolvedMap[$t2qId] ?? null;
            if ($item === null || ($item['is_locked'] ?? false)) {
                continue;
            }

            $tahsin = $item['tahsin'];
            $tahfizh = $item['tahfizh'];
            if (! ($tahsin['has_data'] ?? false) && ! ($tahfizh['has_data'] ?? false)) {
                continue;
            }

            /** @var RaporT2q|null $saved */
            $saved = $item['saved'];
            $upserts[] = $this->buildUpsertRow(
                $tahunAjaranId,
                $t2qId,
                $normPeriode,
                $tahsin,
                $tahfizh,
                (string) ($tahsin['keunggulan'] ?? '') ?: null,
                (string) ($tahsin['peningkatan'] ?? '') ?: null,
                (string) ($tahsin['deskripsi'] ?? '') ?: null,
                (string) ($tahfizh['keunggulan'] ?? '') ?: null,
                (string) ($tahfizh['peningkatan'] ?? '') ?: null,
                (string) ($tahfizh['deskripsi'] ?? '') ?: null,
                $actor?->id ?? $saved?->updated_by_user_id,
                $saved,
                $now
            );
        }

        if ($upserts === []) {
            return 0;
        }

        $this->upsertRows($upserts);

        return count($upserts);
    }

    /**
     * Menyusun satu payload baris upsert `akademik_t2q_rekap_rapor` secara seragam (DRY).
     *
     * @param  array<string, mixed>  $tahsin
     * @param  array<string, mixed>  $tahfizh
     * @return array<string, mixed>
     */
    public function buildUpsertRow(
        int $tahunAjaranId,
        int $anggotaT2qId,
        string $periode,
        array $tahsin,
        array $tahfizh,
        ?string $tahsinKeunggulan,
        ?string $tahsinPeningkatan,
        ?string $tahsinDeskripsi,
        ?string $tahfizhKeunggulan,
        ?string $tahfizhPeningkatan,
        ?string $tahfizhDeskripsi,
        ?int $actorId,
        ?RaporT2q $saved,
        \DateTimeInterface $now
    ): array {
        return [
            'akademik_master_tahun_ajaran_id' => $tahunAjaranId,
            'anggota_t2q_id' => $anggotaT2qId,
            'periode' => $periode,
            'tahsin_yaumiyah_id' => $tahsin['yaumiyah_id'] ?? null,
            'tahsin_materi' => $tahsin['materi'] ?? null,
            'tahsin_nilai' => $tahsin['nilai'] ?? null,
            'tahsin_predikat' => $tahsin['predikat'] ?? null,
            'tahsin_keunggulan' => $tahsinKeunggulan,
            'tahsin_peningkatan' => $tahsinPeningkatan,
            'tahsin_deskripsi' => $tahsinDeskripsi,
            'tahfizh_yaumiyah_id' => $tahfizh['yaumiyah_id'] ?? null,
            'tahfizh_materi' => $tahfizh['materi'] ?? null,
            'tahfizh_nilai' => $tahfizh['nilai'] ?? null,
            'tahfizh_predikat' => $tahfizh['predikat'] ?? null,
            'tahfizh_keunggulan' => $tahfizhKeunggulan,
            'tahfizh_peningkatan' => $tahfizhPeningkatan,
            'tahfizh_deskripsi' => $tahfizhDeskripsi,
            'updated_by_user_id' => $actorId,
            'created_at' => $saved?->created_at ?? $now,
            'updated_at' => $now,
        ];
    }

    /**
     * Menyimpan atau memperbarui baris rekap rapor T2Q secara batch per (anggota_t2q_id, periode).
     *
     * @param  list<array<string, mixed>>  $upserts
     */
    public function upsertRows(array $upserts): void
    {
        foreach (array_chunk($upserts, 200) as $chunk) {
            RaporT2q::query()->upsert(
                $chunk,
                ['anggota_t2q_id', 'periode'],
                [
                    'akademik_master_tahun_ajaran_id',
                    'tahsin_yaumiyah_id',
                    'tahsin_materi',
                    'tahsin_nilai',
                    'tahsin_predikat',
                    'tahsin_keunggulan',
                    'tahsin_peningkatan',
                    'tahsin_deskripsi',
                    'tahfizh_yaumiyah_id',
                    'tahfizh_materi',
                    'tahfizh_nilai',
                    'tahfizh_predikat',
                    'tahfizh_keunggulan',
                    'tahfizh_peningkatan',
                    'tahfizh_deskripsi',
                    'updated_by_user_id',
                    'updated_at',
                ]
            );
        }
    }

    /**
     * Memuat entri Yaumiyah Tahsin terakhir per anggota T2Q dengan menghormati cut-off Mid Semester (anti-N+1).
     *
     * @param  list<int>  $anggotaT2qIds
     * @param  array<int, ?string>  $midCutoffByAnggota
     * @return Collection<int, YaumiyahTahsin>
     */
    private function loadLatestTahsinByAnggota(array $anggotaT2qIds, array $midCutoffByAnggota): Collection
    {
        $rows = YaumiyahTahsin::query()
            ->select([
                'id',
                'anggota_t2q_id',
                'akademik_master_t2q_daftar_jilid_id',
                'akademik_master_t2q_surah_id',
                'akademik_master_t2q_angka_arab_id',
                'tanggal',
                'nilai',
            ])
            ->with(YaumiyahTahsin::MASTER_RELATIONS)
            ->whereIn('anggota_t2q_id', $anggotaT2qIds)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get();

        return $this->pickLatestPerAnggota($rows, $midCutoffByAnggota);
    }

    /**
     * Memuat entri Yaumiyah Tahfidz terakhir per anggota T2Q dengan menghormati cut-off Mid Semester (anti-N+1).
     *
     * @param  list<int>  $anggotaT2qIds
     * @param  array<int, ?string>  $midCutoffByAnggota
     * @return Collection<int, YaumiyahTahfizh>
     */
    private function loadLatestTahfizhByAnggota(array $anggotaT2qIds, array $midCutoffByAnggota): Collection
    {
        $rows = YaumiyahTahfizh::query()
            ->select([
                'id',
                'anggota_t2q_id',
                'akademik_master_t2q_surah_id',
                'akademik_master_t2q_angka_arab_id',
                'tanggal',
                'nilai',
            ])
            ->with(YaumiyahTahfizh::MASTER_RELATIONS)
            ->whereIn('anggota_t2q_id', $anggotaT2qIds)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get();

        return $this->pickLatestPerAnggota($rows, $midCutoffByAnggota);
    }

    /**
     * @param  EloquentCollection<int, YaumiyahTahsin>|EloquentCollection<int, YaumiyahTahfizh>  $rows
     * @param  array<int, ?string>  $midCutoffByAnggota
     * @return Collection<int, YaumiyahTahsin|YaumiyahTahfizh>
     */
    private function pickLatestPerAnggota(EloquentCollection $rows, array $midCutoffByAnggota): Collection
    {
        $picked = [];

        foreach ($rows as $row) {
            $t2qId = (int) $row->anggota_t2q_id;
            if (isset($picked[$t2qId])) {
                continue;
            }

            $cutoff = $midCutoffByAnggota[$t2qId] ?? null;
            $rowDate = $row->tanggal?->toDateString();

            if ($cutoff !== null && $rowDate !== null && $rowDate > $cutoff) {
                continue;
            }

            $picked[$t2qId] = $row;
        }

        return collect($picked);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveChannel(
        string $mode,
        ?RaporT2q $saved,
        YaumiyahTahsin|YaumiyahTahfizh|null $latestYaumiyah,
        bool $isLocked
    ): array {
        $prefix = $mode === RaporT2q::MODE_TAHFIZH ? 'tahfizh' : 'tahsin';

        $savedMateri = trim((string) ($saved?->{"{$prefix}_materi"} ?? ''));
        $savedNilai = $saved?->{"{$prefix}_nilai"};
        $savedPredikat = $saved?->{"{$prefix}_predikat"};
        $savedKeunggulan = $saved?->{"{$prefix}_keunggulan"};
        $savedPeningkatan = $saved?->{"{$prefix}_peningkatan"};
        $savedDeskripsi = trim((string) ($saved?->{"{$prefix}_deskripsi"} ?? ''));
        $savedYaumiyahId = $saved?->{"{$prefix}_yaumiyah_id"};

        $hasSavedValues = $saved !== null && ($savedNilai !== null || $savedMateri !== '' || $savedDeskripsi !== '');

        if ($isLocked && $hasSavedValues) {
            $predikatMeta = RaporT2q::resolvePredikatMeta($savedNilai);
            $effectivePredikat = $savedPredikat ?: ($predikatMeta['predikat'] ?? null);
            $autoDeskripsi = RaporT2q::buildAutoDeskripsi($mode, $savedNilai, $savedMateri, $savedKeunggulan, $savedPeningkatan);

            return [
                'has_data' => true,
                'source' => 'snapshot_final',
                'has_newer_yaumiyah' => false,
                'yaumiyah_id' => $savedYaumiyahId,
                'tanggal' => $latestYaumiyah?->tanggal?->toDateString(),
                'tanggal_formatted' => $latestYaumiyah?->tanggal?->locale('id')->translatedFormat('d M Y'),
                'materi' => $savedMateri !== '' ? $savedMateri : null,
                'has_ayat' => $mode === RaporT2q::MODE_TAHFIZH
                    ? RaporT2q::parseTahfizhMateriContext($savedMateri)['has_ayat']
                    : false,
                'nilai' => $savedNilai !== null ? (float) $savedNilai : null,
                'nilai_bulat' => $savedNilai !== null ? (int) round((float) $savedNilai) : null,
                'predikat' => $effectivePredikat,
                'predikat_label' => $predikatMeta['keterangan'] ?? null,
                'keunggulan' => $savedKeunggulan,
                'peningkatan' => $savedPeningkatan,
                'deskripsi' => $savedDeskripsi !== '' ? $savedDeskripsi : $autoDeskripsi,
                'auto_deskripsi' => $autoDeskripsi,
            ];
        }

        if ($latestYaumiyah !== null) {
            $latestId = (int) $latestYaumiyah->id;
            $latestNilai = $latestYaumiyah->nilai !== null ? (float) $latestYaumiyah->nilai : null;
            $predikatMeta = RaporT2q::resolvePredikatMeta($latestNilai);

            if ($mode === RaporT2q::MODE_TAHFIZH) {
                $detail = RaporT2q::formatTahfizhMateriDetail(
                    $latestYaumiyah->relationLoaded('surah') ? $latestYaumiyah->surah : null,
                    $latestYaumiyah->relationLoaded('angkaArab') ? $latestYaumiyah->angkaArab : null
                );
                $latestMateri = $detail['label'];
                $hasAyat = $detail['has_ayat'];
            } else {
                $latestMateri = RaporT2q::formatTahsinMateri(
                    $latestYaumiyah->relationLoaded('daftarJilid') ? $latestYaumiyah->daftarJilid : null,
                    $latestYaumiyah->relationLoaded('surah') ? $latestYaumiyah->surah : null,
                    $latestYaumiyah->relationLoaded('angkaArab') ? $latestYaumiyah->angkaArab : null
                );
                $hasAyat = false;
            }

            $autoDeskripsi = RaporT2q::buildAutoDeskripsi(
                $mode,
                $latestNilai,
                $latestMateri,
                $savedKeunggulan,
                $savedPeningkatan
            );

            $sameYaumiyah = $saved !== null && (int) ($savedYaumiyahId ?? 0) === $latestId;
            $hasNewer = $saved !== null && (int) ($savedYaumiyahId ?? 0) > 0 && (int) $savedYaumiyahId !== $latestId;

            if ($sameYaumiyah && $savedDeskripsi !== '') {
                $deskripsi = $savedDeskripsi;
            } else {
                $deskripsi = $autoDeskripsi;
            }

            return [
                'has_data' => $latestNilai !== null || filled($latestMateri),
                'source' => $sameYaumiyah ? 'saved_draft' : 'latest_yaumiyah',
                'has_newer_yaumiyah' => $hasNewer,
                'yaumiyah_id' => $latestId,
                'tanggal' => $latestYaumiyah->tanggal?->toDateString(),
                'tanggal_formatted' => $latestYaumiyah->tanggal?->locale('id')->translatedFormat('d M Y'),
                'materi' => $latestMateri,
                'has_ayat' => $hasAyat,
                'nilai' => $latestNilai,
                'nilai_bulat' => $latestNilai !== null ? (int) round($latestNilai) : null,
                'predikat' => $predikatMeta['predikat'] ?? null,
                'predikat_label' => $predikatMeta['keterangan'] ?? null,
                'keunggulan' => $savedKeunggulan,
                'peningkatan' => $savedPeningkatan,
                'deskripsi' => $deskripsi,
                'auto_deskripsi' => $autoDeskripsi,
            ];
        }

        if ($hasSavedValues) {
            $predikatMeta = RaporT2q::resolvePredikatMeta($savedNilai);
            $effectivePredikat = $savedPredikat ?: ($predikatMeta['predikat'] ?? null);
            $autoDeskripsi = RaporT2q::buildAutoDeskripsi($mode, $savedNilai, $savedMateri, $savedKeunggulan, $savedPeningkatan);

            return [
                'has_data' => true,
                'source' => 'saved_only',
                'has_newer_yaumiyah' => false,
                'yaumiyah_id' => $savedYaumiyahId,
                'tanggal' => null,
                'tanggal_formatted' => null,
                'materi' => $savedMateri !== '' ? $savedMateri : null,
                'has_ayat' => $mode === RaporT2q::MODE_TAHFIZH
                    ? RaporT2q::parseTahfizhMateriContext($savedMateri)['has_ayat']
                    : false,
                'nilai' => $savedNilai !== null ? (float) $savedNilai : null,
                'nilai_bulat' => $savedNilai !== null ? (int) round((float) $savedNilai) : null,
                'predikat' => $effectivePredikat,
                'predikat_label' => $predikatMeta['keterangan'] ?? null,
                'keunggulan' => $savedKeunggulan,
                'peningkatan' => $savedPeningkatan,
                'deskripsi' => $savedDeskripsi !== '' ? $savedDeskripsi : $autoDeskripsi,
                'auto_deskripsi' => $autoDeskripsi,
            ];
        }

        return [
            'has_data' => false,
            'source' => 'empty',
            'has_newer_yaumiyah' => false,
            'yaumiyah_id' => null,
            'tanggal' => null,
            'tanggal_formatted' => null,
            'materi' => null,
            'has_ayat' => false,
            'nilai' => null,
            'nilai_bulat' => null,
            'predikat' => null,
            'predikat_label' => null,
            'keunggulan' => $savedKeunggulan,
            'peningkatan' => $savedPeningkatan,
            'deskripsi' => null,
            'auto_deskripsi' => null,
        ];
    }

    public function normalizePeriode(?string $periode): string
    {
        return strtolower(trim((string) $periode)) === PeriodeTp::PERIODE_SEMESTER
            ? PeriodeTp::PERIODE_SEMESTER
            : PeriodeTp::PERIODE_MID;
    }
}
