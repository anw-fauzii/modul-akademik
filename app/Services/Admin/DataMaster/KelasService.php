<?php

namespace App\Services\Admin\DataMaster;

use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Services\Admin\DataMaster\Concerns\ManagesTahunAjaranMembership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class KelasService
{
    use ManagesTahunAjaranMembership;

    public const string FILTER_TAHUN_AJARAN_ALL = 'all';

    public function __construct(
        protected ?AnggotaKelasService $anggotaKelasService = null
    ) {}

    private function anggotaService(): AnggotaKelasService
    {
        return $this->anggotaKelasService ??= app(AnggotaKelasService::class);
    }

    /**
     * Membangun query daftar kelas beserta relasi utama dan jumlah siswa tanpa N+1.
     *
     * @param  array{
     *     tahun_ajaran_id?: string|int|null,
     *     jenjang?: string|null
     * }  $filters
     * @return Builder<Kelas>
     */
    public function buildIndexQuery(array $filters = []): Builder
    {
        $tahunAjaranId = $filters['tahun_ajaran_id'] ?? null;
        $jenjang = strtoupper(trim((string) ($filters['jenjang'] ?? '')));

        $resolvedTahunAjaranId = is_int($tahunAjaranId)
            ? ($tahunAjaranId > 0 ? $tahunAjaranId : null)
            : (is_string($tahunAjaranId) && $tahunAjaranId !== self::FILTER_TAHUN_AJARAN_ALL && ctype_digit($tahunAjaranId) && (int) $tahunAjaranId > 0
                ? (int) $tahunAjaranId
                : null);

        return Kelas::query()
            ->select([
                'id',
                'akademik_master_tahun_ajaran_id',
                'karyawan_nipy',
                'pendamping_nipy',
                'tingkatan_kelas',
                'nama_kelas',
                'jenjang_kelas',
                'romawi',
            ])
            ->with([
                'tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
                'waliKelas:nipy,nama_lengkap,gelar',
                'pendamping:nipy,nama_lengkap,gelar',
            ])
            ->withCount('anggotaKelas')
            ->when(
                $resolvedTahunAjaranId !== null,
                static fn (Builder $query) => $query->where('akademik_master_tahun_ajaran_id', $resolvedTahunAjaranId)
            )
            ->when($jenjang !== '', function (Builder $query) use ($jenjang): void {
                $variants = array_values(array_unique([
                    $jenjang,
                    strtolower($jenjang),
                    ucfirst(strtolower($jenjang)),
                ]));
                $query->whereIn($query->qualifyColumn('jenjang_kelas'), $variants);
            })
            ->orderBy('jenjang_kelas')
            ->orderBy('tingkatan_kelas')
            ->orderBy('nama_kelas');
    }

    /**
     * Mengambil semua data kelas beserta relasi tahun ajaran, guru, dan jumlah siswa (filter opsional).
     *
     * @param  array{
     *     tahun_ajaran_id?: string|int|null,
     *     jenjang?: string|null
     * }  $filters
     * @return Collection<int, Kelas>
     */
    public function getAll(array $filters = []): Collection
    {
        return $this->buildIndexQuery($filters)->get();
    }

    /**
     * Mengambil data lengkap halaman indeks kelas: default tahun ajaran aktif, statistik jumlah tiap jenjang, dan opsi filter.
     *
     * @param  array<string, mixed>  $input
     * @return array{
     *     kelas: Collection<int, Kelas>,
     *     statistics: array{
     *         total_kelas: int,
     *         total_siswa: int,
     *         per_jenjang: array<string, array{kelas: int, siswa: int}>
     *     },
     *     filters: array{tahun_ajaran_id: string, jenjang: string},
     *     tahunAjaranOptions: Collection<int, TahunAjaran>,
     *     activeTahunAjaran: ?TahunAjaran,
     *     jenjangOptions: list<string>
     * }
     */
    public function getIndexData(array $input = []): array
    {
        $tahunAjaranOptions = TahunAjaran::query()
            ->select(['id', 'nama_tahun_ajaran', 'semester', 'is_active'])
            ->orderByDesc('is_active')
            ->orderByDesc('nama_tahun_ajaran')
            ->orderBy('semester')
            ->orderByDesc('id')
            ->get();

        $activeTahunAjaran = $tahunAjaranOptions->firstWhere('is_active', true);
        $filters = $this->resolveIndexFilters($input, $activeTahunAjaran);

        $selectedTahunAjaranInt = $filters['tahun_ajaran_id'] !== self::FILTER_TAHUN_AJARAN_ALL && ctype_digit($filters['tahun_ajaran_id'])
            ? (int) $filters['tahun_ajaran_id']
            : null;

        $statistics = $this->getJenjangStatistics($selectedTahunAjaranInt);

        $distinctJenjang = Kelas::query()
            ->select(['jenjang_kelas'])
            ->whereNotNull('jenjang_kelas')
            ->distinct()
            ->pluck('jenjang_kelas')
            ->filter(static fn (mixed $value): bool => is_string($value) && trim($value) !== '')
            ->map(static fn (string $value): string => strtoupper(trim($value)))
            ->merge(array_keys($statistics['per_jenjang']))
            ->unique()
            ->sort(static fn (string $a, string $b): int => Kelas::compareJenjangOrder($a, $b))
            ->values()
            ->all();

        return [
            'kelas' => $this->getAll($filters),
            'statistics' => $statistics,
            'filters' => $filters,
            'tahunAjaranOptions' => $tahunAjaranOptions,
            'activeTahunAjaran' => $activeTahunAjaran,
            'jenjangOptions' => $distinctJenjang,
        ];
    }

    /**
     * Menormalisasi input filter indeks kelas dengan default ke tahun ajaran aktif saat dibuka pertama kali.
     *
     * @param  array<string, mixed>  $input
     * @return array{tahun_ajaran_id: string, jenjang: string}
     */
    public function resolveIndexFilters(array $input, ?TahunAjaran $activeTahunAjaran = null): array
    {
        $defaultTahunAjaran = $activeTahunAjaran?->id
            ? (string) $activeTahunAjaran->id
            : self::FILTER_TAHUN_AJARAN_ALL;

        if (! array_key_exists('tahun_ajaran_id', $input)) {
            $tahunAjaranId = $defaultTahunAjaran;
        } else {
            $rawTahunAjaran = trim((string) ($input['tahun_ajaran_id'] ?? ''));

            if ($rawTahunAjaran === '' || $rawTahunAjaran === self::FILTER_TAHUN_AJARAN_ALL) {
                $tahunAjaranId = self::FILTER_TAHUN_AJARAN_ALL;
            } elseif (ctype_digit($rawTahunAjaran) && (int) $rawTahunAjaran > 0) {
                $tahunAjaranId = (string) (int) $rawTahunAjaran;
            } else {
                $tahunAjaranId = $defaultTahunAjaran;
            }
        }

        return [
            'tahun_ajaran_id' => $tahunAjaranId,
            'jenjang' => strtoupper(trim((string) ($input['jenjang'] ?? ''))),
        ];
    }

    /**
     * Menghitung ringkasan jumlah kelas dan jumlah siswa tiap jenjang dalam cakupan tahun ajaran terpilih.
     *
     * @return array{
     *     total_kelas: int,
     *     total_siswa: int,
     *     per_jenjang: array<string, array{kelas: int, siswa: int}>
     * }
     */
    public function getJenjangStatistics(?int $tahunAjaranId = null): array
    {
        $rows = Kelas::query()
            ->leftJoin(
                'akademik_master_anggota_kelas',
                'akademik_master_anggota_kelas.akademik_master_kelas_id',
                '=',
                'akademik_master_kelas.id'
            )
            ->when(
                $tahunAjaranId !== null,
                static fn (Builder $query) => $query->where('akademik_master_kelas.akademik_master_tahun_ajaran_id', $tahunAjaranId)
            )
            ->selectRaw('akademik_master_kelas.jenjang_kelas as jenjang, COUNT(DISTINCT akademik_master_kelas.id) as jumlah_kelas, COUNT(akademik_master_anggota_kelas.id) as jumlah_siswa')
            ->groupBy('akademik_master_kelas.jenjang_kelas')
            ->toBase()
            ->get();

        $perJenjang = [];
        $totalKelas = 0;
        $totalSiswa = 0;

        foreach ($rows as $row) {
            $rawJenjang = strtoupper(trim((string) ($row->jenjang ?? '')));
            $key = $rawJenjang !== '' ? $rawJenjang : 'LAINNYA';
            $kelasCount = (int) ($row->jumlah_kelas ?? 0);
            $siswaCount = (int) ($row->jumlah_siswa ?? 0);

            if (! isset($perJenjang[$key])) {
                $perJenjang[$key] = ['kelas' => 0, 'siswa' => 0];
            }

            $perJenjang[$key]['kelas'] += $kelasCount;
            $perJenjang[$key]['siswa'] += $siswaCount;
            $totalKelas += $kelasCount;
            $totalSiswa += $siswaCount;
        }

        uksort($perJenjang, static fn (string $a, string $b): int => Kelas::compareJenjangOrder($a, $b));

        return [
            'total_kelas' => $totalKelas,
            'total_siswa' => $totalSiswa,
            'per_jenjang' => $perJenjang,
        ];
    }

    /**
     * Mengambil data lengkap halaman detail kelas beserta daftar anggota,
     * statistik ringkas, dan kandidat siswa aktif yang belum masuk kelas di tahun ajaran ini.
     *
     * @return array{
     *     kelas: Kelas,
     *     anggotaKelas: Collection<int, AnggotaKelas>,
     *     stats: array{total: int, laki_laki: int, perempuan: int},
     *     kandidatSiswa: Collection<int, Siswa>,
     *     excludedTahunAjaranIds: list<int>,
     *     hiddenRegisteredCount: int,
     *     hiddenInactiveCount: int
     * }
     */
    public function getShowData(Kelas $kelas): array
    {
        $kelas->loadMissing([
            'tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
            'waliKelas:nipy,nama_lengkap,gelar',
            'pendamping:nipy,nama_lengkap,gelar',
        ]);

        $anggotaKelas = AnggotaKelas::query()
            ->select([
                'id',
                'siswa_nis',
                'akademik_master_kelas_id',
                'pendaftaran',
            ])
            ->with([
                'siswa:nis,nama_lengkap,nisn,jenis_kelamin,status',
            ])
            ->forKelas((int) $kelas->id)
            ->orderBy('siswa_nis')
            ->orderBy('id')
            ->get();

        $anggotaKelas->each(static function (AnggotaKelas $item) use ($kelas): void {
            $item->setRelation('kelas', $kelas);
        });

        $candidatePayload = $this->anggotaService()->getCandidateSiswaForKelas($kelas);

        return [
            'kelas' => $kelas,
            'anggotaKelas' => $anggotaKelas,
            'stats' => $this->computeMemberGenderStatsFrom(
                $anggotaKelas,
                static fn ($item): ?Siswa => $item instanceof AnggotaKelas ? $item->siswa : null
            ),
            'kandidatSiswa' => $candidatePayload['siswa'],
            'excludedTahunAjaranIds' => $candidatePayload['excludedTahunAjaranIds'],
            'hiddenRegisteredCount' => $candidatePayload['hiddenRegisteredCount'],
            'hiddenInactiveCount' => $candidatePayload['hiddenInactiveCount'],
        ];
    }

    /**
     * Menyimpan data kelas baru.
     */
    public function store(array $data): Kelas
    {
        return Kelas::create($data);
    }

    /**
     * Memperbarui data kelas.
     */
    public function update(Kelas $kelas, array $data): bool
    {
        return $kelas->update($data);
    }

    /**
     * Menghapus data kelas.
     */
    public function delete(Kelas $kelas): bool
    {
        return $kelas->delete();
    }
}
