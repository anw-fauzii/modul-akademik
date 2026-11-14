<?php

namespace App\Services\Admin\Pelajaran;

use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Pelajaran\JadwalPelajaran;
use App\Models\Admin\Pelajaran\JamPelajaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Validator;

class JadwalPelajaranService
{
    public const string FILTER_ALL = 'all';

    /**
     * Membangun query daftar jadwal pelajaran dengan kolom minimal dan eager loading lengkap tanpa N+1.
     *
     * @param  array{
     *     tahun_ajaran_id?: string|int|null,
     *     unit?: string|null,
     *     kelas_id?: string|int|null,
     *     hari?: string|int|null,
     *     status?: string|null,
     *     search?: string|null
     * }  $filters
     * @return Builder<JadwalPelajaran>
     */
    public function buildIndexQuery(array $filters = []): Builder
    {
        $tahunAjaranId = $this->resolvePositiveInt($filters['tahun_ajaran_id'] ?? null);
        $unit = strtoupper(trim((string) ($filters['unit'] ?? '')));
        $kelasId = $this->resolvePositiveInt($filters['kelas_id'] ?? null);
        $hari = $this->resolvePositiveInt($filters['hari'] ?? null);
        $status = trim((string) ($filters['status'] ?? self::FILTER_ALL));
        $search = trim((string) ($filters['search'] ?? ''));

        return JadwalPelajaran::query()
            ->select([
                'id',
                'akademik_master_tahun_ajaran_id',
                'akademik_master_kelas_id',
                'akademik_pelajaran_jam_pelajaran_id',
                'akademik_pelajaran_mata_pelajaran_id',
                'karyawan_nipy',
                'hari',
                'ruang',
                'keterangan',
                'status',
                'created_at',
            ])
            ->withMasterRelations()
            ->when(
                $tahunAjaranId !== null,
                static fn (Builder $query) => $query->where(
                    $query->qualifyColumn('akademik_master_tahun_ajaran_id'),
                    $tahunAjaranId
                )
            )
            ->when($unit !== '' && $unit !== 'ALL', function (Builder $query) use ($unit): void {
                $variants = $this->unitVariants($unit);
                $query->whereHas('kelas', static function (Builder $kelasQuery) use ($variants): void {
                    $kelasQuery->whereIn($kelasQuery->qualifyColumn('jenjang_kelas'), $variants);
                });
            })
            ->when(
                $kelasId !== null,
                static fn (Builder $query) => $query->where(
                    $query->qualifyColumn('akademik_master_kelas_id'),
                    $kelasId
                )
            )
            ->when(
                $hari !== null && array_key_exists($hari, JadwalPelajaran::HARI_OPTIONS),
                static fn (Builder $query) => $query->where($query->qualifyColumn('hari'), $hari)
            )
            ->when(
                $status === '1' || $status === '0',
                static fn (Builder $query) => $query->where($query->qualifyColumn('status'), $status === '1')
            )
            ->when($search !== '', static function (Builder $query) use ($search): void {
                $escaped = addcslashes($search, '%_\\');
                $likePattern = '%'.$escaped.'%';

                $query->where(static function (Builder $nested) use ($likePattern): void {
                    $nested->where($nested->qualifyColumn('keterangan'), 'like', $likePattern)
                        ->orWhere($nested->qualifyColumn('ruang'), 'like', $likePattern)
                        ->orWhereHas('mataPelajaran', static function (Builder $mpQuery) use ($likePattern): void {
                            $mpQuery->where('singkatan', 'like', $likePattern)
                                ->orWhere('nama_mata_pelajaran', 'like', $likePattern);
                        })
                        ->orWhereHas('guru', static function (Builder $guruQuery) use ($likePattern): void {
                            $guruQuery->where('nama_lengkap', 'like', $likePattern)
                                ->orWhere('nipy', 'like', $likePattern);
                        })
                        ->orWhereHas('kelas', static function (Builder $kelasQuery) use ($likePattern): void {
                            $kelasQuery->where('nama_kelas', 'like', $likePattern);
                        });
                });
            })
            ->ordered();
    }

    /**
     * Mengambil semua data jadwal pelajaran sesuai filter opsional tanpa N+1.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, JadwalPelajaran>
     */
    public function getAll(array $filters = []): Collection
    {
        return $this->buildIndexQuery($filters)->get();
    }

    /**
     * Mengambil data lengkap halaman indeks jadwal pelajaran:
     * default tahun ajaran aktif, statistik per hari, opsi filter, serta matriks mingguan bila 1 kelas dipilih.
     *
     * @param  array<string, mixed>  $input
     * @return array{
     *     jadwalPelajaran: Collection<int, JadwalPelajaran>,
     *     summary: array{total: int, aktif: int, nonaktif: int, per_hari: array<int, int>},
     *     filters: array{tahun_ajaran_id: string, unit: string, kelas_id: string, hari: string, status: string, search: string},
     *     tahunAjaranOptions: Collection<int, TahunAjaran>,
     *     kelasOptions: Collection<int, Kelas>,
     *     unitOptions: list<string>,
     *     hariOptions: array<int, string>,
     *     selectedKelas: ?Kelas,
     *     weeklyMatrix: array{jamSlots: Collection<int, JamPelajaran>, grid: array<int, array<int, JadwalPelajaran>>}|null
     * }
     */
    public function getIndexData(array $input = []): array
    {
        $tahunAjaranOptions = $this->getTahunAjaranOptions();
        $activeTahunAjaran = $tahunAjaranOptions->firstWhere('is_active', true);

        $rawTa = array_key_exists('tahun_ajaran_id', $input) ? trim((string) $input['tahun_ajaran_id']) : '';
        if ($rawTa === self::FILTER_ALL) {
            $effectiveTa = self::FILTER_ALL;
        } elseif ($rawTa !== '' && ctype_digit($rawTa) && (int) $rawTa > 0) {
            $effectiveTa = $rawTa;
        } elseif ($activeTahunAjaran) {
            $effectiveTa = (string) $activeTahunAjaran->id;
        } else {
            $effectiveTa = self::FILTER_ALL;
        }

        $rawStatus = trim((string) ($input['status'] ?? self::FILTER_ALL));
        $filters = [
            'tahun_ajaran_id' => $effectiveTa,
            'unit' => strtoupper(trim((string) ($input['unit'] ?? ''))),
            'kelas_id' => trim((string) ($input['kelas_id'] ?? '')),
            'hari' => trim((string) ($input['hari'] ?? '')),
            'status' => in_array($rawStatus, ['1', '0', self::FILTER_ALL], true) ? $rawStatus : self::FILTER_ALL,
            'search' => trim((string) ($input['search'] ?? '')),
        ];

        $kelasOptions = $this->getKelasOptions(
            $this->resolvePositiveInt($filters['tahun_ajaran_id']),
            $filters['unit']
        );

        $jadwalPelajaran = $this->getAll($filters);
        $sortedJadwal = $jadwalPelajaran->sortBy(static function (JadwalPelajaran $row): string {
            $hari = str_pad((string) $row->hari, 2, '0', STR_PAD_LEFT);
            $jamKe = str_pad((string) ($row->jamPelajaran?->jam_ke ?? 99), 3, '0', STR_PAD_LEFT);
            $kelasNama = (string) ($row->kelas?->nama_kelas ?? '');

            return "{$hari}-{$kelasNama}-{$jamKe}-{$row->id}";
        })->values();

        $selectedKelasId = $this->resolvePositiveInt($filters['kelas_id']);
        $selectedKelas = $selectedKelasId !== null ? $kelasOptions->firstWhere('id', $selectedKelasId) : null;
        $weeklyMatrix = $selectedKelas !== null
            ? $this->buildWeeklyMatrix($selectedKelas, $jadwalPelajaran)
            : null;

        return [
            'jadwalPelajaran' => $sortedJadwal,
            'summary' => $this->getHariStatistics($filters),
            'filters' => $filters,
            'tahunAjaranOptions' => $tahunAjaranOptions,
            'kelasOptions' => $kelasOptions,
            'unitOptions' => JamPelajaran::UNIT_OPTIONS,
            'hariOptions' => JadwalPelajaran::HARI_OPTIONS,
            'selectedKelas' => $selectedKelas,
            'weeklyMatrix' => $weeklyMatrix,
        ];
    }

    /**
     * Mengambil opsi dropdown untuk form tambah dan edit jadwal pelajaran dalam query minimal tanpa N+1.
     *
     * @param  array<string, mixed>  $defaults
     * @return array{
     *     tahunAjaranOptions: Collection<int, TahunAjaran>,
     *     kelasOptions: Collection<int, Kelas>,
     *     jamPelajaranOptions: Collection<int, JamPelajaran>,
     *     mataPelajaranOptions: Collection<int, MataPelajaran>,
     *     guruOptions: Collection<int, Karyawan>,
     *     hariOptions: array<int, string>,
     *     nonMapelPresets: list<string>,
     *     defaults: array{tahun_ajaran_id: string, kelas_id: string, jam_pelajaran_id: string, hari: string}
     * }
     */
    public function getFormOptions(array $defaults = []): array
    {
        $tahunAjaranOptions = $this->getTahunAjaranOptions();
        $activeTahunAjaran = $tahunAjaranOptions->firstWhere('is_active', true);

        $defaultTa = $this->resolvePositiveInt($defaults['tahun_ajaran_id'] ?? null)
            ?? $activeTahunAjaran?->id;

        return [
            'tahunAjaranOptions' => $tahunAjaranOptions,
            'kelasOptions' => Kelas::query()
                ->select(['id', 'akademik_master_tahun_ajaran_id', 'nama_kelas', 'jenjang_kelas', 'tingkatan_kelas'])
                ->orderBy('jenjang_kelas')
                ->orderBy('tingkatan_kelas')
                ->orderBy('nama_kelas')
                ->get(),
            'jamPelajaranOptions' => JamPelajaran::query()
                ->select(['id', 'jam_ke', 'unit', 'jam_mulai', 'jam_selesai', 'keterangan'])
                ->ordered()
                ->get(),
            'mataPelajaranOptions' => MataPelajaran::query()
                ->select(['id', 'singkatan', 'nama_mata_pelajaran', 'sifat', 'unit', 'urutan', 'status'])
                ->orderByDesc('status')
                ->orderBy('unit')
                ->orderBy('urutan')
                ->orderBy('nama_mata_pelajaran')
                ->get(),
            'guruOptions' => Karyawan::query()
                ->select(['nipy', 'nama_lengkap', 'gelar', 'unit', 'jabatan'])
                ->orderBy('nama_lengkap')
                ->get(),
            'hariOptions' => JadwalPelajaran::HARI_OPTIONS,
            'nonMapelPresets' => JadwalPelajaran::NON_MAPEL_PRESETS,
            'defaults' => [
                'tahun_ajaran_id' => $defaultTa !== null ? (string) $defaultTa : '',
                'kelas_id' => trim((string) ($defaults['kelas_id'] ?? '')),
                'jam_pelajaran_id' => trim((string) ($defaults['jam_pelajaran_id'] ?? '')),
                'hari' => trim((string) ($defaults['hari'] ?? '')),
            ],
        ];
    }

    /**
     * Memvalidasi aturan konsistensi tahun ajaran, kesesuaian unit, serta anti-bentrok jadwal guru.
     *
     * @param  array<string, mixed>  $data
     */
    public function validateScheduleRules(Validator $validator, array $data, int|string|null $ignoreId = null): void
    {
        $tahunAjaranId = (int) ($data['akademik_master_tahun_ajaran_id'] ?? 0);
        $kelasId = (int) ($data['akademik_master_kelas_id'] ?? 0);
        $jamId = (int) ($data['akademik_pelajaran_jam_pelajaran_id'] ?? 0);
        $mapelId = isset($data['akademik_pelajaran_mata_pelajaran_id']) && $data['akademik_pelajaran_mata_pelajaran_id'] !== null
            ? (int) $data['akademik_pelajaran_mata_pelajaran_id']
            : null;
        $guruNipy = isset($data['karyawan_nipy']) ? trim((string) $data['karyawan_nipy']) : '';
        $hari = (int) ($data['hari'] ?? 0);
        $isActive = (bool) ($data['status'] ?? true);

        $kelas = Kelas::query()
            ->select(['id', 'akademik_master_tahun_ajaran_id', 'nama_kelas', 'jenjang_kelas'])
            ->find($kelasId);
        $jam = JamPelajaran::query()
            ->select(['id', 'jam_ke', 'unit', 'jam_mulai', 'jam_selesai'])
            ->find($jamId);
        $mapel = $mapelId !== null
            ? MataPelajaran::query()->select(['id', 'unit', 'nama_mata_pelajaran'])->find($mapelId)
            : null;

        if (! $kelas || ! $jam) {
            return;
        }

        if ((int) $kelas->akademik_master_tahun_ajaran_id !== $tahunAjaranId) {
            $validator->errors()->add(
                'akademik_master_kelas_id',
                'Kelas yang dipilih tidak termasuk dalam tahun ajaran tersebut.'
            );
        }

        $kelasUnit = strtoupper(trim((string) $kelas->jenjang_kelas));
        $jamUnit = strtoupper(trim((string) $jam->unit));

        if ($kelasUnit !== '' && $jamUnit !== '' && $kelasUnit !== $jamUnit) {
            $validator->errors()->add(
                'akademik_pelajaran_jam_pelajaran_id',
                "Jam pelajaran unit {$jamUnit} tidak sesuai dengan unit kelas ({$kelasUnit})."
            );
        }

        if ($mapel !== null) {
            $mapelUnit = strtoupper(trim((string) $mapel->unit));
            if ($kelasUnit !== '' && $mapelUnit !== '' && $kelasUnit !== $mapelUnit) {
                $validator->errors()->add(
                    'akademik_pelajaran_mata_pelajaran_id',
                    "Mata pelajaran unit {$mapelUnit} tidak sesuai dengan unit kelas ({$kelasUnit})."
                );
            }
        }

        if (! $isActive || $guruNipy === '') {
            return;
        }

        $targetMulai = JamPelajaran::formatTimeShort($jam->jam_mulai);
        $targetSelesai = JamPelajaran::formatTimeShort($jam->jam_selesai);

        $existingTeacherSchedules = JadwalPelajaran::query()
            ->select([
                'id',
                'akademik_master_kelas_id',
                'akademik_pelajaran_jam_pelajaran_id',
                'karyawan_nipy',
                'hari',
                'status',
            ])
            ->with([
                'kelas:id,nama_kelas,jenjang_kelas',
                'jamPelajaran:id,jam_ke,unit,jam_mulai,jam_selesai',
            ])
            ->where('akademik_master_tahun_ajaran_id', $tahunAjaranId)
            ->where('hari', $hari)
            ->where('karyawan_nipy', $guruNipy)
            ->where('status', true)
            ->when($ignoreId !== null, static fn (Builder $q) => $q->where('id', '!=', $ignoreId))
            ->get();

        foreach ($existingTeacherSchedules as $existing) {
            if (! $existing->jamPelajaran) {
                continue;
            }

            $existingMulai = JamPelajaran::formatTimeShort($existing->jamPelajaran->jam_mulai);
            $existingSelesai = JamPelajaran::formatTimeShort($existing->jamPelajaran->jam_selesai);

            if (self::hasTimeOverlap($targetMulai, $targetSelesai, $existingMulai, $existingSelesai)) {
                $conflictKelas = $existing->kelas?->nama_kelas ?? 'kelas lain';
                $validator->errors()->add(
                    'karyawan_nipy',
                    "Jadwal guru bentrok dengan kelas {$conflictKelas} pada pukul {$existingMulai} - {$existingSelesai}."
                );
                break;
            }
        }
    }

    /**
     * Memeriksa apakah dua rentang waktu (H:i) saling beririsan.
     */
    public static function hasTimeOverlap(string $startA, string $endA, string $startB, string $endB): bool
    {
        if (
            preg_match('/^\d{2}:\d{2}$/', $startA) !== 1
            || preg_match('/^\d{2}:\d{2}$/', $endA) !== 1
            || preg_match('/^\d{2}:\d{2}$/', $startB) !== 1
            || preg_match('/^\d{2}:\d{2}$/', $endB) !== 1
        ) {
            return false;
        }

        return $startA < $endB && $endA > $startB;
    }

    /**
     * Menyimpan data jadwal pelajaran baru.
     *
     * @param  array<string, mixed>  $data
     */
    public function store(array $data): JadwalPelajaran
    {
        return JadwalPelajaran::create($data);
    }

    /**
     * Memperbarui data jadwal pelajaran.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(JadwalPelajaran $jadwalPelajaran, array $data): bool
    {
        return $jadwalPelajaran->update($data);
    }

    /**
     * Menghapus data jadwal pelajaran.
     */
    public function delete(JadwalPelajaran $jadwalPelajaran): bool
    {
        return (bool) $jadwalPelajaran->delete();
    }

    /**
     * Mengubah status aktif/nonaktif jadwal pelajaran secara cepat.
     */
    public function toggleStatus(JadwalPelajaran $jadwalPelajaran): bool
    {
        return $jadwalPelajaran->update([
            'status' => ! (bool) $jadwalPelajaran->status,
        ]);
    }

    /**
     * Menghitung ringkasan jumlah jadwal per hari dan status dalam satu query agregat tanpa N+1.
     *
     * @param  array<string, mixed>  $filters
     * @return array{total: int, aktif: int, nonaktif: int, per_hari: array<int, int>}
     */
    private function getHariStatistics(array $filters): array
    {
        $tahunAjaranId = $this->resolvePositiveInt($filters['tahun_ajaran_id'] ?? null);
        $unit = strtoupper(trim((string) ($filters['unit'] ?? '')));
        $kelasId = $this->resolvePositiveInt($filters['kelas_id'] ?? null);
        $status = trim((string) ($filters['status'] ?? self::FILTER_ALL));

        $rows = JadwalPelajaran::query()
            ->selectRaw('hari, COUNT(*) as total, SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as total_aktif')
            ->when(
                $tahunAjaranId !== null,
                static fn (Builder $query) => $query->where('akademik_master_tahun_ajaran_id', $tahunAjaranId)
            )
            ->when($unit !== '' && $unit !== 'ALL', function (Builder $query) use ($unit): void {
                $variants = $this->unitVariants($unit);
                $query->whereHas('kelas', static function (Builder $kelasQuery) use ($variants): void {
                    $kelasQuery->whereIn('jenjang_kelas', $variants);
                });
            })
            ->when(
                $kelasId !== null,
                static fn (Builder $query) => $query->where('akademik_master_kelas_id', $kelasId)
            )
            ->when(
                $status === '1' || $status === '0',
                static fn (Builder $query) => $query->where('status', $status === '1')
            )
            ->groupBy('hari')
            ->get();

        $perHari = array_fill_keys(array_keys(JadwalPelajaran::HARI_OPTIONS), 0);
        $total = 0;
        $aktif = 0;

        foreach ($rows as $row) {
            $hariCode = (int) ($row->hari ?? 0);
            $hariTotal = (int) ($row->total ?? 0);
            $hariAktif = (int) ($row->total_aktif ?? 0);

            if (array_key_exists($hariCode, $perHari)) {
                $perHari[$hariCode] = $hariTotal;
            }
            $total += $hariTotal;
            $aktif += $hariAktif;
        }

        return [
            'total' => $total,
            'aktif' => $aktif,
            'nonaktif' => max(0, $total - $aktif),
            'per_hari' => $perHari,
        ];
    }

    /**
     * Membangun matriks mingguan (Senin-Jumat per Jam Ke) di memori tanpa query tambahan per sel.
     *
     * @param  Collection<int, JadwalPelajaran>  $jadwalPelajaran
     * @return array{jamSlots: Collection<int, JamPelajaran>, grid: array<int, array<int, JadwalPelajaran>>}
     */
    private function buildWeeklyMatrix(Kelas $selectedKelas, Collection $jadwalPelajaran): array
    {
        $unit = strtoupper(trim((string) $selectedKelas->jenjang_kelas));

        $jamSlots = JamPelajaran::query()
            ->select(['id', 'jam_ke', 'unit', 'jam_mulai', 'jam_selesai', 'keterangan'])
            ->when($unit !== '', static fn (Builder $q) => $q->where('unit', $unit))
            ->ordered()
            ->get();

        $grid = [];
        foreach ($jadwalPelajaran as $item) {
            if ((int) $item->akademik_master_kelas_id !== (int) $selectedKelas->id) {
                continue;
            }
            $grid[(int) $item->akademik_pelajaran_jam_pelajaran_id][(int) $item->hari] = $item;
        }

        return [
            'jamSlots' => $jamSlots,
            'grid' => $grid,
        ];
    }

    /**
     * @return Collection<int, TahunAjaran>
     */
    private function getTahunAjaranOptions(): Collection
    {
        return TahunAjaran::query()
            ->select(['id', 'nama_tahun_ajaran', 'semester', 'is_active'])
            ->orderByDesc('is_active')
            ->orderByDesc('nama_tahun_ajaran')
            ->orderBy('semester')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return Collection<int, Kelas>
     */
    private function getKelasOptions(?int $tahunAjaranId, string $unit): Collection
    {
        $normalizedUnit = strtoupper(trim($unit));

        return Kelas::query()
            ->select(['id', 'akademik_master_tahun_ajaran_id', 'nama_kelas', 'jenjang_kelas', 'tingkatan_kelas'])
            ->when(
                $tahunAjaranId !== null,
                static fn (Builder $query) => $query->where('akademik_master_tahun_ajaran_id', $tahunAjaranId)
            )
            ->when($normalizedUnit !== '' && $normalizedUnit !== 'ALL', function (Builder $query) use ($normalizedUnit): void {
                $query->whereIn($query->qualifyColumn('jenjang_kelas'), $this->unitVariants($normalizedUnit));
            })
            ->orderBy('jenjang_kelas')
            ->orderBy('tingkatan_kelas')
            ->orderBy('nama_kelas')
            ->get();
    }

    /**
     * @return list<string>
     */
    private function unitVariants(string $unit): array
    {
        $upper = strtoupper(trim($unit));

        return array_values(array_unique([
            $upper,
            strtolower($upper),
            ucfirst(strtolower($upper)),
        ]));
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
