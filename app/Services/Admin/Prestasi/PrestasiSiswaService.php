<?php

namespace App\Services\Admin\Prestasi;

use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
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
     *     kategori: string,
     *     tingkat: string,
     *     siswa_nis: string,
     *     search: string
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
            'kategori' => $kategori,
            'tingkat' => $tingkat,
            'siswa_nis' => $siswaNis,
            'search' => $search,
        ];
    }

    /**
     * Mengecek apakah ada filter non-default yang sedang aktif.
     *
     * @param  array<string, string>  $filters
     */
    public function hasActiveFilters(array $filters): bool
    {
        return ($filters['tahun_ajaran_id'] ?? self::FILTER_ALL) !== self::FILTER_ALL
            || ($filters['kategori'] ?? self::FILTER_ALL) !== self::FILTER_ALL
            || ($filters['tingkat'] ?? self::FILTER_ALL) !== self::FILTER_ALL
            || ($filters['siswa_nis'] ?? '') !== ''
            || ($filters['search'] ?? '') !== '';
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

        return $query;
    }

    /**
     * Mengambil data lengkap halaman indeks Prestasi Siswa beserta statistik dan opsi filter.
     *
     * @param  array<string, mixed>  $input
     * @return array{
     *     prestasi: Collection<int, PrestasiSiswa>,
     *     statistics: array{total: int, akademik: int, non_akademik: int, beregu: int, dengan_foto: int},
     *     filters: array{tahun_ajaran_id: string, kategori: string, tingkat: string, siswa_nis: string, search: string},
     *     hasActiveFilter: bool,
     *     tahunAjaranOptions: Collection<int, TahunAjaran>,
     *     activeTahunAjaran: ?TahunAjaran,
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
        $prestasi = $this->buildIndexQuery($filters)->get();

        return [
            'prestasi' => $prestasi,
            'statistics' => $this->summarizeCollection($prestasi),
            'filters' => $filters,
            'hasActiveFilter' => $this->hasActiveFilters($filters),
            'tahunAjaranOptions' => $tahunAjaranOptions,
            'activeTahunAjaran' => $activeTahunAjaran,
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
