<?php

namespace App\Services\Admin\Prestasi;

use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Admin\Prestasi\PrestasiSiswa;
use App\Support\Sqid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PrestasiSiswaService
{
    public const FILTER_ALL = 'all';

    /**
     * Menormalisasi input filter daftar prestasi siswa.
     *
     * @param  array<string, mixed>  $input
     * @return array{
     *     tahun_ajaran_id: string,
     *     unit: string,
     *     kategori: string,
     *     tingkat: string,
     *     siswa_nis: string,
     *     search: string,
     *     has_foto: bool,
     *     show_all: bool
     * }
     */
    public function normalizeFilters(array $input = []): array
    {
        $rawTa = trim((string) ($input['tahun_ajaran_id'] ?? self::FILTER_ALL));
        $tahunAjaranId = self::FILTER_ALL;
        if ($rawTa !== '' && $rawTa !== self::FILTER_ALL) {
            $decodedTa = Sqid::decodeFor(TahunAjaran::class, $rawTa);
            if ($decodedTa !== null) {
                $tahunAjaranId = (string) $decodedTa;
            } elseif (ctype_digit($rawTa) && (int) $rawTa > 0) {
                $tahunAjaranId = (string) ((int) $rawTa);
            }
        }

        $rawUnit = trim((string) ($input['unit'] ?? self::FILTER_ALL));
        $unit = self::FILTER_ALL;
        if ($rawUnit !== '' && strcasecmp($rawUnit, self::FILTER_ALL) !== 0) {
            $normalizedUnit = strtoupper(preg_replace('/\s+/', ' ', $rawUnit) ?? '');
            if ($normalizedUnit !== '') {
                $unit = $normalizedUnit;
            }
        }

        $rawKategori = strtolower(trim((string) ($input['kategori'] ?? self::FILTER_ALL)));
        $kategori = array_key_exists($rawKategori, PrestasiSiswa::KATEGORI_OPTIONS)
            ? $rawKategori
            : self::FILTER_ALL;

        $rawTingkat = trim((string) ($input['tingkat'] ?? self::FILTER_ALL));
        $tingkat = $rawTingkat === '' ? self::FILTER_ALL : $rawTingkat;

        $siswaNis = $this->resolveSiswaNis($input['siswa_nis'] ?? null) ?? '';
        $search = trim((string) ($input['search'] ?? ''));

        return [
            'tahun_ajaran_id' => $tahunAjaranId,
            'unit' => $unit,
            'kategori' => $kategori,
            'tingkat' => $tingkat,
            'siswa_nis' => $siswaNis,
            'search' => $search,
            'has_foto' => filter_var($input['has_foto'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'show_all' => filter_var($input['show_all'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];
    }

    /**
     * Mengecek apakah ada filter non-default atau opsi tampilkan semua yang sedang aktif.
     *
     * @param  array{
     *     tahun_ajaran_id?: string,
     *     unit?: string,
     *     kategori?: string,
     *     tingkat?: string,
     *     siswa_nis?: string,
     *     search?: string,
     *     has_foto?: bool,
     *     show_all?: bool
     * }  $filters
     */
    public function hasActiveFilters(array $filters): bool
    {
        return ($filters['tahun_ajaran_id'] ?? self::FILTER_ALL) !== self::FILTER_ALL
            || ($filters['unit'] ?? self::FILTER_ALL) !== self::FILTER_ALL
            || ($filters['kategori'] ?? self::FILTER_ALL) !== self::FILTER_ALL
            || ($filters['tingkat'] ?? self::FILTER_ALL) !== self::FILTER_ALL
            || ($filters['siswa_nis'] ?? '') !== ''
            || ($filters['search'] ?? '') !== ''
            || (bool) ($filters['has_foto'] ?? false)
            || (bool) ($filters['show_all'] ?? false);
    }

    /**
     * Membangun query daftar prestasi siswa dengan kolom minimal dan eager loading anti-N+1.
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<PrestasiSiswa>
     */
    public function buildIndexQuery(array $filters = []): Builder
    {
        $normalized = $this->normalizeFilters($filters);

        $query = PrestasiSiswa::query()
            ->select([
                'id',
                'akademik_master_tahun_ajaran_id',
                'nama_prestasi',
                'kategori',
                'tingkat',
                'peringkat',
                'penyelenggara',
                'tanggal',
                'keterangan',
                'foto',
                'created_at',
            ])
            ->withMasterRelations()
            ->orderedLatest();

        if ($normalized['tahun_ajaran_id'] !== self::FILTER_ALL && ctype_digit($normalized['tahun_ajaran_id'])) {
            $query->where('akademik_master_tahun_ajaran_id', (int) $normalized['tahun_ajaran_id']);
        }

        if ($normalized['unit'] !== self::FILTER_ALL) {
            $unitCode = $normalized['unit'];
            $unitVariants = array_values(array_unique([
                $unitCode,
                strtolower($unitCode),
                ucfirst(strtolower($unitCode)),
            ]));

            $query->whereHas('siswa.kelas', static function (Builder $kelasQuery) use ($unitVariants): void {
                $kelasQuery->whereIn('jenjang_kelas', $unitVariants);
            });
        }

        if ($normalized['kategori'] !== self::FILTER_ALL) {
            $query->where('kategori', $normalized['kategori']);
        }

        if ($normalized['tingkat'] !== self::FILTER_ALL) {
            $query->where('tingkat', $normalized['tingkat']);
        }

        if ($normalized['siswa_nis'] !== '') {
            $targetNis = $normalized['siswa_nis'];
            $query->whereHas('siswa', static function (Builder $siswaQuery) use ($targetNis): void {
                $siswaQuery->where('akademik_master_siswa.nis', $targetNis);
            });
        }

        if ($normalized['search'] !== '') {
            $like = '%'.$normalized['search'].'%';
            $query->where(static function (Builder $sub) use ($like): void {
                $sub->where('nama_prestasi', 'like', $like)
                    ->orWhere('peringkat', 'like', $like)
                    ->orWhere('penyelenggara', 'like', $like)
                    ->orWhere('tingkat', 'like', $like)
                    ->orWhere('keterangan', 'like', $like)
                    ->orWhereHas('siswa', static function (Builder $siswaQuery) use ($like): void {
                        $siswaQuery->where('nama_lengkap', 'like', $like)
                            ->orWhere('akademik_master_siswa.nis', 'like', $like);
                    });
            });
        }

        if (! empty($normalized['has_foto'])) {
            $query->whereNotNull('foto')
                ->where('foto', '!=', '');
        }

        return $query;
    }

    /**
     * Mengambil data prestasi siswa sesuai filter aktif (kosong jika belum ada filter).
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, PrestasiSiswa>
     */
    public function getFiltered(array $filters = []): Collection
    {
        $normalized = $this->normalizeFilters($filters);
        if (! $this->hasActiveFilters($normalized)) {
            return new Collection;
        }

        return $this->buildIndexQuery($normalized)->get();
    }

    /**
     * Mengambil statistik ringkas seluruh prestasi serta distribusi per unit (PG, TK, SD) secara agregat ringan.
     *
     * @return array{
     *     total: int,
     *     akademik: int,
     *     non_akademik: int,
     *     beregu: int,
     *     dengan_foto: int,
     *     per_unit: array<string, int>
     * }
     */
    public function getStatistics(): array
    {
        $anggotaCountsSub = DB::table('akademik_prestasi_siswa_anggota')
            ->selectRaw('prestasi_siswa_id, COUNT(*) as anggota_count')
            ->groupBy('prestasi_siswa_id');

        $summary = PrestasiSiswa::query()
            ->leftJoinSub($anggotaCountsSub, 'anggota_counts', static function ($join): void {
                $join->on('anggota_counts.prestasi_siswa_id', '=', 'akademik_prestasi_siswa.id');
            })
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN akademik_prestasi_siswa.kategori = ? THEN 1 ELSE 0 END) as akademik,
                SUM(CASE WHEN akademik_prestasi_siswa.kategori = ? THEN 1 ELSE 0 END) as non_akademik,
                SUM(CASE WHEN COALESCE(anggota_counts.anggota_count, 0) > 1 THEN 1 ELSE 0 END) as beregu,
                SUM(CASE WHEN akademik_prestasi_siswa.foto IS NOT NULL AND TRIM(akademik_prestasi_siswa.foto) <> '' THEN 1 ELSE 0 END) as dengan_foto
            ", [
                PrestasiSiswa::KATEGORI_AKADEMIK,
                PrestasiSiswa::KATEGORI_NON_AKADEMIK,
            ])
            ->toBase()
            ->first();

        $unitRows = DB::table('akademik_prestasi_siswa_anggota')
            ->join('akademik_master_siswa', 'akademik_master_siswa.nis', '=', 'akademik_prestasi_siswa_anggota.siswa_nis')
            ->leftJoin('akademik_master_kelas', 'akademik_master_kelas.id', '=', 'akademik_master_siswa.akademik_master_kelas_id')
            ->selectRaw('akademik_master_kelas.jenjang_kelas as jenjang, COUNT(DISTINCT akademik_prestasi_siswa_anggota.prestasi_siswa_id) as total')
            ->groupBy('akademik_master_kelas.jenjang_kelas')
            ->get();

        $perUnit = array_fill_keys(MataPelajaran::UNIT_OPTIONS, 0);
        foreach ($unitRows as $row) {
            $unitCode = strtoupper(trim((string) ($row->jenjang ?? '')));
            if ($unitCode === '') {
                continue;
            }
            $perUnit[$unitCode] = ($perUnit[$unitCode] ?? 0) + (int) ($row->total ?? 0);
        }
        uksort($perUnit, static fn (string $a, string $b): int => Kelas::compareJenjangOrder($a, $b));

        return [
            'total' => (int) ($summary->total ?? 0),
            'akademik' => (int) ($summary->akademik ?? 0),
            'non_akademik' => (int) ($summary->non_akademik ?? 0),
            'beregu' => (int) ($summary->beregu ?? 0),
            'dengan_foto' => (int) ($summary->dengan_foto ?? 0),
            'per_unit' => $perUnit,
        ];
    }

    /**
     * Mengambil data halaman indeks Prestasi Siswa dengan pola filter-first agar ringan saat data besar.
     *
     * @param  array<string, mixed>  $input
     * @return array{
     *     prestasi: Collection<int, PrestasiSiswa>,
     *     statistics: array{
     *         total: int,
     *         akademik: int,
     *         non_akademik: int,
     *         beregu: int,
     *         dengan_foto: int,
     *         per_unit: array<string, int>
     *     },
     *     filters: array{
     *         tahun_ajaran_id: string,
     *         unit: string,
     *         kategori: string,
     *         tingkat: string,
     *         siswa_nis: string,
     *         search: string,
     *         has_foto: bool,
     *         show_all: bool
     *     },
     *     hasActiveFilter: bool,
     *     tahunAjaranOptions: Collection<int, TahunAjaran>,
     *     activeTahunAjaran: ?TahunAjaran,
     *     unitOptions: list<string>,
     *     kategoriOptions: array<string, string>,
     *     tingkatOptions: list<string>
     * }
     */
    public function getIndexData(array $input = []): array
    {
        $tahunAjaranOptions = $this->getTahunAjaranOptions();
        /** @var TahunAjaran|null $activeTahunAjaran */
        $activeTahunAjaran = $tahunAjaranOptions->firstWhere('is_active', true);

        $filters = $this->normalizeFilters($input);
        $hasActiveFilter = $this->hasActiveFilters($filters);
        $prestasi = $hasActiveFilter ? $this->getFiltered($filters) : new Collection;
        $statistics = $this->getStatistics();

        return [
            'prestasi' => $prestasi,
            'statistics' => $statistics,
            'filters' => $filters,
            'hasActiveFilter' => $hasActiveFilter,
            'tahunAjaranOptions' => $tahunAjaranOptions,
            'activeTahunAjaran' => $activeTahunAjaran,
            'unitOptions' => array_keys($statistics['per_unit']),
            'kategoriOptions' => PrestasiSiswa::KATEGORI_OPTIONS,
            'tingkatOptions' => PrestasiSiswa::TINGKAT_PRESETS,
        ];
    }

    /**
     * Menghitung statistik ringkas dari koleksi prestasi yang sudah dimuat di memori.
     *
     * @param  Collection<int, PrestasiSiswa>  $items
     * @return array{total: int, akademik: int, non_akademik: int, beregu: int, dengan_foto: int}
     */
    public function summarizeCollection(Collection $items): array
    {
        return [
            'total' => $items->count(),
            'akademik' => $items->where('kategori', PrestasiSiswa::KATEGORI_AKADEMIK)->count(),
            'non_akademik' => $items->where('kategori', PrestasiSiswa::KATEGORI_NON_AKADEMIK)->count(),
            'beregu' => $items->filter(static fn (PrestasiSiswa $p): bool => $p->is_beregu)->count(),
            'dengan_foto' => $items->filter(static fn (PrestasiSiswa $p): bool => filled($p->foto))->count(),
        ];
    }

    /**
     * Mengambil opsi dropdown untuk halaman tambah & edit prestasi siswa tanpa N+1.
     *
     * @return array{
     *     siswaOptions: Collection<int, Siswa>,
     *     tahunAjaranOptions: Collection<int, TahunAjaran>,
     *     activeTahunAjaran: ?TahunAjaran,
     *     kategoriOptions: array<string, string>,
     *     tingkatPresets: list<string>,
     *     peringkatPresets: list<string>,
     *     prefillSiswaNis: ?string
     * }
     */
    public function getFormOptions(?string $rawSiswaNis = null): array
    {
        $tahunAjaranOptions = $this->getTahunAjaranOptions();
        /** @var TahunAjaran|null $activeTahunAjaran */
        $activeTahunAjaran = $tahunAjaranOptions->firstWhere('is_active', true);

        $siswaOptions = Siswa::query()
            ->select(['nis', 'akademik_master_kelas_id', 'nama_lengkap', 'jenis_kelamin', 'status'])
            ->with(['kelas:id,nama_kelas,jenjang_kelas'])
            ->orderBy('nama_lengkap')
            ->get();

        return [
            'siswaOptions' => $siswaOptions,
            'tahunAjaranOptions' => $tahunAjaranOptions,
            'activeTahunAjaran' => $activeTahunAjaran,
            'kategoriOptions' => PrestasiSiswa::KATEGORI_OPTIONS,
            'tingkatPresets' => PrestasiSiswa::TINGKAT_PRESETS,
            'peringkatPresets' => PrestasiSiswa::PERINGKAT_PRESETS,
            'prefillSiswaNis' => $this->resolveSiswaNis($rawSiswaNis),
        ];
    }

    /**
     * Memuat seluruh relasi detail pada satu entitas prestasi untuk mencegah lazy loading.
     */
    public function loadDetail(PrestasiSiswa $prestasiSiswa): PrestasiSiswa
    {
        return $prestasiSiswa->load(PrestasiSiswa::MASTER_RELATIONS);
    }

    /**
     * Menyimpan data prestasi baru beserta daftar anggota siswa & unggahan foto dokumentasi.
     *
     * @param  array<string, mixed>  $data
     */
    public function store(array $data, ?UploadedFile $fotoFile = null): PrestasiSiswa
    {
        $siswaNisList = $this->extractSiswaNisList($data['siswa_nis'] ?? []);
        $payload = Arr::except($data, ['siswa_nis', 'foto', 'remove_foto']);

        if ($fotoFile !== null) {
            $payload['foto'] = $fotoFile->store('prestasi/foto', 'public');
        }

        return DB::transaction(static function () use ($payload, $siswaNisList): PrestasiSiswa {
            /** @var PrestasiSiswa $prestasi */
            $prestasi = PrestasiSiswa::create($payload);
            $prestasi->siswa()->sync($siswaNisList);

            return $prestasi;
        });
    }

    /**
     * Memperbarui data prestasi, daftar siswa penerima, serta penggantian/penghapusan foto secara aman.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(
        PrestasiSiswa $prestasiSiswa,
        array $data,
        ?UploadedFile $fotoFile = null,
        bool $removeFoto = false
    ): bool {
        $siswaNisList = $this->extractSiswaNisList($data['siswa_nis'] ?? []);
        $payload = Arr::except($data, ['siswa_nis', 'foto', 'remove_foto']);
        $oldFoto = $prestasiSiswa->foto;

        if ($fotoFile !== null) {
            $payload['foto'] = $fotoFile->store('prestasi/foto', 'public');
        } elseif ($removeFoto) {
            $payload['foto'] = null;
        }

        $updated = DB::transaction(static function () use ($prestasiSiswa, $payload, $siswaNisList): bool {
            $ok = $prestasiSiswa->update($payload);
            $prestasiSiswa->siswa()->sync($siswaNisList);

            return $ok;
        });

        if ($updated && ($fotoFile !== null || $removeFoto) && $oldFoto) {
            Storage::disk('public')->delete($oldFoto);
        }

        return $updated;
    }

    /**
     * Menghapus data prestasi siswa beserta berkas foto terkait.
     */
    public function delete(PrestasiSiswa $prestasiSiswa): bool
    {
        $oldFoto = $prestasiSiswa->foto;
        $deleted = (bool) $prestasiSiswa->delete();

        if ($deleted && $oldFoto) {
            Storage::disk('public')->delete($oldFoto);
        }

        return $deleted;
    }

    /**
     * Mengambil opsi tahun ajaran terurut dari aktif dan terbaru.
     *
     * @return Collection<int, TahunAjaran>
     */
    public function getTahunAjaranOptions(): Collection
    {
        return TahunAjaran::query()
            ->select(['id', 'nama_tahun_ajaran', 'semester', 'is_active'])
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Menormalisasi daftar NIS penerima prestasi menjadi list unik bersih.
     *
     * @return list<string>
     */
    public function extractSiswaNisList(mixed $rawInput): array
    {
        $items = is_array($rawInput) ? $rawInput : [$rawInput];
        $result = [];

        foreach ($items as $item) {
            $nis = $this->resolveSiswaNis($item);
            if ($nis !== null) {
                $result[] = $nis;
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * Men-decode parameter siswa_nis (dapat berupa Sqid Siswa atau NIS mentah).
     */
    public function resolveSiswaNis(mixed $rawValue): ?string
    {
        if (! is_string($rawValue) && ! is_int($rawValue)) {
            return null;
        }

        $trimmed = trim((string) $rawValue);
        if ($trimmed === '' || $trimmed === self::FILTER_ALL) {
            return null;
        }

        $decoded = Sqid::decodeFor(Siswa::class, $trimmed);

        return $decoded !== null ? (string) $decoded : $trimmed;
    }
}
