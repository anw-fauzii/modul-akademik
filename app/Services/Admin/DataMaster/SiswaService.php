<?php

namespace App\Services\Admin\DataMaster;

use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Models\Admin\DataMaster\Jemputan;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataPelengkap\BerkebutuhanKhusus;
use App\Models\Admin\DataPelengkap\JenjangPendidikan;
use App\Models\Admin\DataPelengkap\Pekerjaan;
use App\Models\Admin\DataPelengkap\Penghasilan;
use App\Models\Admin\DataPelengkap\Transportasi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class SiswaService
{
    public const FILTER_STATUS_INACTIVE = '__inactive__';

    public const FILTER_KELAS_UNASSIGNED = '__unassigned__';

    private const JENJANG_ORDER = ['PAUD', 'TK', 'SD', 'SMP', 'SMA', 'SMK'];

    /**
     * Menormalisasi input filter indeks siswa dari query string.
     *
     * @param  array<string, mixed>  $input
     * @return array{
     *     search: string,
     *     status: string,
     *     kelas_id: string,
     *     jenjang: string,
     *     jenis_kelamin: string,
     *     show_all: bool
     * }
     */
    public function normalizeIndexFilters(array $input): array
    {
        $rawKelasId = trim((string) ($input['kelas_id'] ?? ''));
        $kelasId = $rawKelasId === self::FILTER_KELAS_UNASSIGNED || (ctype_digit($rawKelasId) && (int) $rawKelasId > 0)
            ? $rawKelasId
            : '';

        $rawJk = strtoupper(trim((string) ($input['jenis_kelamin'] ?? '')));
        $jenisKelamin = in_array($rawJk, ['L', 'P'], true) ? $rawJk : '';

        return [
            'search' => trim((string) ($input['search'] ?? '')),
            'status' => trim((string) ($input['status'] ?? '')),
            'kelas_id' => $kelasId,
            'jenjang' => strtoupper(trim((string) ($input['jenjang'] ?? ''))),
            'jenis_kelamin' => $jenisKelamin,
            'show_all' => filter_var($input['show_all'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];
    }

    /**
     * Menentukan apakah pengguna sudah menerapkan filter pada halaman indeks siswa.
     *
     * @param  array{
     *     search?: string,
     *     status?: string,
     *     kelas_id?: string,
     *     jenjang?: string,
     *     jenis_kelamin?: string,
     *     show_all?: bool
     * }  $filters
     */
    public function hasActiveFilters(array $filters): bool
    {
        return ($filters['search'] ?? '') !== ''
            || ($filters['status'] ?? '') !== ''
            || ($filters['kelas_id'] ?? '') !== ''
            || ($filters['jenjang'] ?? '') !== ''
            || ($filters['jenis_kelamin'] ?? '') !== ''
            || (bool) ($filters['show_all'] ?? false);
    }

    /**
     * Membangun query daftar siswa beserta relasi utama tanpa N+1 dengan filter opsional.
     *
     * @param  array{
     *     search?: string,
     *     status?: string,
     *     kelas_id?: string,
     *     jenjang?: string,
     *     jenis_kelamin?: string,
     *     show_all?: bool
     * }  $filters
     * @return Builder<Siswa>
     */
    public function buildIndexQuery(array $filters = []): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $kelasId = trim((string) ($filters['kelas_id'] ?? ''));
        $jenjang = strtoupper(trim((string) ($filters['jenjang'] ?? '')));
        $jenisKelamin = strtoupper(trim((string) ($filters['jenis_kelamin'] ?? '')));

        return Siswa::query()
            ->select([
                'nis',
                'akademik_master_kelas_id',
                'karyawan_nipy',
                'akademik_master_ekstrakuler_id',
                'akademik_master_jemputan_id',
                'nama_lengkap',
                'jenis_kelamin',
                'nisn',
                'avatar',
                'status',
            ])
            ->withMasterRelations()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = "%{$search}%";
                $query->where(static function (Builder $searchQuery) use ($like): void {
                    $searchQuery->where('nama_lengkap', 'like', $like)
                        ->orWhere('nis', 'like', $like)
                        ->orWhere('nisn', 'like', $like);
                });
            })
            ->when($status !== '', function (Builder $query) use ($status): void {
                if ($status === self::FILTER_STATUS_INACTIVE) {
                    $query->inactive();

                    return;
                }

                if (strcasecmp($status, Siswa::STATUS_AKTIF) === 0) {
                    $query->active();

                    return;
                }

                $query->whereIn(
                    $query->qualifyColumn('status'),
                    array_values(array_unique([$status, ucfirst(strtolower($status)), strtolower($status)]))
                );
            })
            ->when($kelasId !== '', function (Builder $query) use ($kelasId): void {
                if ($kelasId === self::FILTER_KELAS_UNASSIGNED) {
                    $query->whereNull('akademik_master_kelas_id');

                    return;
                }

                if (ctype_digit($kelasId) && (int) $kelasId > 0) {
                    $query->where('akademik_master_kelas_id', (int) $kelasId);
                }
            })
            ->when($jenjang !== '', function (Builder $query) use ($jenjang): void {
                $variants = array_values(array_unique([$jenjang, strtolower($jenjang), ucfirst(strtolower($jenjang))]));
                $query->whereHas(
                    'kelas',
                    fn (Builder $kelasQuery) => $kelasQuery->whereIn('jenjang_kelas', $variants)
                );
            })
            ->when(in_array($jenisKelamin, ['L', 'P'], true), function (Builder $query) use ($jenisKelamin): void {
                $query->whereIn('jenis_kelamin', [$jenisKelamin, strtolower($jenisKelamin)]);
            })
            ->orderBy('nama_lengkap')
            ->orderBy('nis');
    }

    /**
     * Mengambil semua data siswa beserta relasi utama tanpa N+1.
     *
     * @return Collection<int, Siswa>
     */
    public function getAll(): Collection
    {
        return $this->buildIndexQuery()->get();
    }

    /**
     * Mengambil data siswa sesuai filter yang diterapkan (kosong jika belum difilter).
     *
     * @param  array{
     *     search?: string,
     *     status?: string,
     *     kelas_id?: string,
     *     jenjang?: string,
     *     jenis_kelamin?: string,
     *     show_all?: bool
     * }  $filters
     * @return Collection<int, Siswa>
     */
    public function getFiltered(array $filters = []): Collection
    {
        if (! $this->hasActiveFilters($filters)) {
            return new Collection;
        }

        return $this->buildIndexQuery($filters)->get();
    }

    /**
     * Mengambil ringkasan statistik siswa secara ringan tanpa memuat model siswa.
     *
     * @return array{
     *     total: int,
     *     aktif: int,
     *     lulus: int,
     *     pindah: int,
     *     nonaktif_keluar: int,
     *     laki_laki: int,
     *     perempuan: int,
     *     aktif_laki_laki: int,
     *     aktif_perempuan: int,
     *     punya_kelas: int,
     *     belum_punya_kelas: int,
     *     jenjang_aktif: array<string, int>
     * }
     */
    public function getStatistics(): array
    {
        $summary = Siswa::query()
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN LOWER(TRIM(COALESCE(status, ''))) = 'aktif' THEN 1 ELSE 0 END) as aktif,
                SUM(CASE WHEN LOWER(TRIM(COALESCE(status, ''))) = 'lulus' THEN 1 ELSE 0 END) as lulus,
                SUM(CASE WHEN LOWER(TRIM(COALESCE(status, ''))) = 'pindah' THEN 1 ELSE 0 END) as pindah,
                SUM(CASE WHEN LOWER(TRIM(COALESCE(status, ''))) IN ('nonaktif', 'keluar') THEN 1 ELSE 0 END) as nonaktif_keluar,
                SUM(CASE WHEN UPPER(TRIM(COALESCE(jenis_kelamin, ''))) = 'L' THEN 1 ELSE 0 END) as laki_laki,
                SUM(CASE WHEN UPPER(TRIM(COALESCE(jenis_kelamin, ''))) = 'P' THEN 1 ELSE 0 END) as perempuan,
                SUM(CASE WHEN LOWER(TRIM(COALESCE(status, ''))) = 'aktif' AND UPPER(TRIM(COALESCE(jenis_kelamin, ''))) = 'L' THEN 1 ELSE 0 END) as aktif_laki_laki,
                SUM(CASE WHEN LOWER(TRIM(COALESCE(status, ''))) = 'aktif' AND UPPER(TRIM(COALESCE(jenis_kelamin, ''))) = 'P' THEN 1 ELSE 0 END) as aktif_perempuan,
                SUM(CASE WHEN akademik_master_kelas_id IS NOT NULL THEN 1 ELSE 0 END) as punya_kelas,
                SUM(CASE WHEN akademik_master_kelas_id IS NULL THEN 1 ELSE 0 END) as belum_punya_kelas
            ")
            ->toBase()
            ->first();

        $jenjangRows = Siswa::query()
            ->active()
            ->leftJoin('akademik_master_kelas', 'akademik_master_kelas.id', '=', 'akademik_master_siswa.akademik_master_kelas_id')
            ->selectRaw('akademik_master_kelas.jenjang_kelas as jenjang, COUNT(*) as total')
            ->groupBy('akademik_master_kelas.jenjang_kelas')
            ->toBase()
            ->get();

        $jenjangCounts = [];
        foreach ($jenjangRows as $row) {
            $rawJenjang = trim((string) ($row->jenjang ?? ''));
            $key = $rawJenjang !== '' ? strtoupper($rawJenjang) : 'BELUM_DITEMPATKAN';
            $jenjangCounts[$key] = ($jenjangCounts[$key] ?? 0) + (int) ($row->total ?? 0);
        }

        uksort($jenjangCounts, static function (string $a, string $b): int {
            if ($a === 'BELUM_DITEMPATKAN') {
                return 1;
            }
            if ($b === 'BELUM_DITEMPATKAN') {
                return -1;
            }

            $posA = array_search($a, self::JENJANG_ORDER, true);
            $posB = array_search($b, self::JENJANG_ORDER, true);

            if ($posA !== false && $posB !== false) {
                return $posA <=> $posB;
            }
            if ($posA !== false) {
                return -1;
            }
            if ($posB !== false) {
                return 1;
            }

            return $a <=> $b;
        });

        return [
            'total' => (int) ($summary->total ?? 0),
            'aktif' => (int) ($summary->aktif ?? 0),
            'lulus' => (int) ($summary->lulus ?? 0),
            'pindah' => (int) ($summary->pindah ?? 0),
            'nonaktif_keluar' => (int) ($summary->nonaktif_keluar ?? 0),
            'laki_laki' => (int) ($summary->laki_laki ?? 0),
            'perempuan' => (int) ($summary->perempuan ?? 0),
            'aktif_laki_laki' => (int) ($summary->aktif_laki_laki ?? 0),
            'aktif_perempuan' => (int) ($summary->aktif_perempuan ?? 0),
            'punya_kelas' => (int) ($summary->punya_kelas ?? 0),
            'belum_punya_kelas' => (int) ($summary->belum_punya_kelas ?? 0),
            'jenjang_aktif' => $jenjangCounts,
        ];
    }

    /**
     * Mengambil opsi dropdown filter kelas & jenjang untuk halaman indeks siswa tanpa N+1.
     *
     * @return array{
     *     kelasOptions: Collection<int, Kelas>,
     *     jenjangOptions: list<string>
     * }
     */
    public function getIndexFilterOptions(): array
    {
        $kelasOptions = Kelas::query()
            ->select(['id', 'akademik_master_tahun_ajaran_id', 'nama_kelas', 'jenjang_kelas', 'tingkatan_kelas'])
            ->with(['tahunAjaran:id,nama_tahun_ajaran,semester,is_active'])
            ->orderByDesc('akademik_master_tahun_ajaran_id')
            ->orderBy('jenjang_kelas')
            ->orderBy('nama_kelas')
            ->get();

        $jenjangOptions = $kelasOptions
            ->pluck('jenjang_kelas')
            ->filter(static fn (mixed $value): bool => is_string($value) && trim($value) !== '')
            ->map(static fn (string $value): string => strtoupper(trim($value)))
            ->unique()
            ->sort(static function (string $a, string $b): int {
                $posA = array_search($a, self::JENJANG_ORDER, true);
                $posB = array_search($b, self::JENJANG_ORDER, true);

                if ($posA !== false && $posB !== false) {
                    return $posA <=> $posB;
                }
                if ($posA !== false) {
                    return -1;
                }
                if ($posB !== false) {
                    return 1;
                }

                return $a <=> $b;
            })
            ->values()
            ->all();

        return [
            'kelasOptions' => $kelasOptions,
            'jenjangOptions' => $jenjangOptions,
        ];
    }

    /**
     * Memuat seluruh relasi detail pada satu entitas siswa untuk mencegah lazy loading.
     */
    public function loadDetail(Siswa $siswa): Siswa
    {
        return $siswa->load(Siswa::DETAIL_RELATIONS);
    }

    /**
     * Mengambil seluruh opsi dropdown untuk form tambah dan edit siswa tanpa N+1.
     *
     * @return array{
     *     kelas: Collection<int, Kelas>,
     *     karyawan: Collection<int, Karyawan>,
     *     ekstrakurikuler: Collection<int, Ekstrakurikuler>,
     *     jemputan: Collection<int, Jemputan>,
     *     berkebutuhanKhusus: Collection<int, BerkebutuhanKhusus>,
     *     transportasi: Collection<int, Transportasi>,
     *     jenjangPendidikan: Collection<int, JenjangPendidikan>,
     *     pekerjaan: Collection<int, Pekerjaan>,
     *     penghasilan: Collection<int, Penghasilan>
     * }
     */
    public function getFormOptions(): array
    {
        return [
            'kelas' => Kelas::query()
                ->select(['id', 'akademik_master_tahun_ajaran_id', 'nama_kelas', 'jenjang_kelas'])
                ->with(['tahunAjaran:id,nama_tahun_ajaran,semester,is_active'])
                ->orderBy('jenjang_kelas')
                ->orderBy('nama_kelas')
                ->get(),
            'karyawan' => Karyawan::query()
                ->select(['nipy', 'nama_lengkap', 'gelar'])
                ->orderBy('nama_lengkap')
                ->get(),
            'ekstrakurikuler' => Ekstrakurikuler::query()
                ->select(['id', 'nama_ekstrakurikuler', 'unit'])
                ->orderBy('unit')
                ->orderBy('nama_ekstrakurikuler')
                ->get(),
            'jemputan' => Jemputan::query()
                ->select(['id', 'karyawan_nipy', 'harga_pp', 'harga_setengah'])
                ->with(['petugas:nipy,nama_lengkap,gelar'])
                ->orderBy('id')
                ->get(),
            'berkebutuhanKhusus' => BerkebutuhanKhusus::query()
                ->select(['id', 'nama_berkebutuhan_khusus'])
                ->orderBy('nama_berkebutuhan_khusus')
                ->get(),
            'transportasi' => Transportasi::query()
                ->select(['id', 'nama_transportasi'])
                ->orderBy('nama_transportasi')
                ->get(),
            'jenjangPendidikan' => JenjangPendidikan::query()
                ->select(['id', 'nama_jenjang_pendidikan'])
                ->orderBy('id')
                ->get(),
            'pekerjaan' => Pekerjaan::query()
                ->select(['id', 'nama_pekerjaan'])
                ->orderBy('nama_pekerjaan')
                ->get(),
            'penghasilan' => Penghasilan::query()
                ->select(['id', 'nama_penghasilan'])
                ->orderBy('id')
                ->get(),
        ];
    }

    /**
     * Menyimpan data siswa baru beserta unggahan avatar jika ada.
     *
     * @param  array<string, mixed>  $data
     */
    public function store(array $data, ?UploadedFile $avatarFile = null): Siswa
    {
        $payload = Arr::except($data, ['avatar', 'remove_avatar']);

        if ($avatarFile !== null) {
            $payload['avatar'] = $avatarFile->store('siswa/avatars', 'public');
        }

        return Siswa::create($payload);
    }

    /**
     * Memperbarui data siswa beserta penggantian/penghapusan avatar secara aman.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(
        Siswa $siswa,
        array $data,
        ?UploadedFile $avatarFile = null,
        bool $removeAvatar = false
    ): bool {
        $payload = Arr::except($data, ['avatar', 'remove_avatar']);
        $oldAvatar = $siswa->avatar;

        if ($avatarFile !== null) {
            $payload['avatar'] = $avatarFile->store('siswa/avatars', 'public');
        } elseif ($removeAvatar) {
            $payload['avatar'] = null;
        }

        $updated = $siswa->update($payload);

        if ($updated && ($avatarFile !== null || $removeAvatar) && $oldAvatar) {
            Storage::disk('public')->delete($oldAvatar);
        }

        return $updated;
    }

    /**
     * Menghapus data siswa beserta berkas avatar terkait.
     */
    public function delete(Siswa $siswa): bool
    {
        $oldAvatar = $siswa->avatar;
        $deleted = (bool) $siswa->delete();

        if ($deleted && $oldAvatar) {
            Storage::disk('public')->delete($oldAvatar);
        }

        return $deleted;
    }
}
