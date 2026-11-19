<?php

namespace App\Services\Admin\DataMaster;

use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\DataMaster\Titimangsa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class TitimangsaService
{
    private const array INDEX_COLUMNS = [
        'id',
        'akademik_master_tahun_ajaran_id',
        'jenjang_kelas',
        'tingkatan_kelas',
        'periode',
        'tempat',
        'tanggal_titimangsa',
        'kepala_sekolah_nipy',
        'nama_kepala_sekolah_snapshot',
        'jabatan_kepala_sekolah',
        'status',
    ];

    /**
     * Membangun query daftar titimangsa dengan eager loading tanpa N+1.
     *
     * @param  array{
     *     tahun_ajaran_id?: string|int|null,
     *     jenjang_kelas?: string|null,
     *     periode?: string|null
     * }  $filters
     * @return Builder<Titimangsa>
     */
    public function buildIndexQuery(array $filters = []): Builder
    {
        $tahunAjaranId = isset($filters['tahun_ajaran_id']) && ctype_digit((string) $filters['tahun_ajaran_id'])
            ? (int) $filters['tahun_ajaran_id']
            : null;
        $jenjang = strtoupper(trim((string) ($filters['jenjang_kelas'] ?? '')));
        $periode = strtolower(trim((string) ($filters['periode'] ?? '')));

        return Titimangsa::query()
            ->select(self::INDEX_COLUMNS)
            ->withMasterRelations()
            ->when(
                $tahunAjaranId !== null && $tahunAjaranId > 0,
                static fn (Builder $query) => $query->where('akademik_master_tahun_ajaran_id', $tahunAjaranId)
            )
            ->when(
                $jenjang !== '',
                static fn (Builder $query) => $query->where('jenjang_kelas', $jenjang)
            )
            ->when(
                $periode !== '' && array_key_exists($periode, Titimangsa::PERIODE_OPTIONS),
                static fn (Builder $query) => $query->where('periode', $periode)
            )
            ->orderByDesc('akademik_master_tahun_ajaran_id')
            ->orderBy('jenjang_kelas')
            ->orderByRaw("CASE WHEN tingkatan_kelas = '*' THEN 0 ELSE 1 END")
            ->orderBy('tingkatan_kelas')
            ->orderBy('periode')
            ->orderBy('id');
    }

    /**
     * Mengambil semua data titimangsa sesuai filter tanpa N+1.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Titimangsa>
     */
    public function getAll(array $filters = []): Collection
    {
        return $this->buildIndexQuery($filters)->get();
    }

    /**
     * Mengambil data lengkap halaman indeks titimangsa beserta opsi filter.
     *
     * @param  array<string, mixed>  $filters
     * @return array{
     *     titimangsa: Collection<int, Titimangsa>,
     *     filters: array{tahun_ajaran_id: string, jenjang_kelas: string, periode: string},
     *     tahunAjaranOptions: Collection<int, TahunAjaran>,
     *     jenjangOptions: list<string>,
     *     periodeOptions: array<string, string>
     * }
     */
    public function getIndexData(array $filters = []): array
    {
        $normalizedFilters = [
            'tahun_ajaran_id' => trim((string) ($filters['tahun_ajaran_id'] ?? '')),
            'jenjang_kelas' => strtoupper(trim((string) ($filters['jenjang_kelas'] ?? ''))),
            'periode' => strtolower(trim((string) ($filters['periode'] ?? ''))),
        ];

        return [
            'titimangsa' => $this->getAll($normalizedFilters),
            'filters' => $normalizedFilters,
            'tahunAjaranOptions' => TahunAjaran::query()
                ->select(['id', 'nama_tahun_ajaran', 'semester', 'is_active'])
                ->orderByDesc('is_active')
                ->orderByDesc('nama_tahun_ajaran')
                ->orderBy('semester')
                ->get(),
            'jenjangOptions' => Titimangsa::JENJANG_OPTIONS,
            'periodeOptions' => Titimangsa::PERIODE_OPTIONS,
        ];
    }

    /**
     * Mengambil daftar opsi dropdown untuk form tambah dan edit titimangsa.
     *
     * @return array{
     *     tahunAjaran: Collection<int, TahunAjaran>,
     *     karyawan: Collection<int, Karyawan>,
     *     jenjangOptions: list<string>,
     *     periodeOptions: array<string, string>
     * }
     */
    public function getFormOptions(): array
    {
        return [
            'tahunAjaran' => TahunAjaran::query()
                ->select(['id', 'nama_tahun_ajaran', 'semester', 'is_active'])
                ->orderByDesc('is_active')
                ->orderByDesc('nama_tahun_ajaran')
                ->orderBy('semester')
                ->get(),
            'karyawan' => Karyawan::query()
                ->select(['nipy', 'nama_lengkap', 'gelar', 'jabatan', 'unit'])
                ->orderBy('nama_lengkap')
                ->get(),
            'jenjangOptions' => Titimangsa::JENJANG_OPTIONS,
            'periodeOptions' => Titimangsa::PERIODE_OPTIONS,
        ];
    }

    /**
     * Menemukan titimangsa aktif paling spesifik untuk kelas & periode dalam 1 query (siap dipakai PDF Rapor).
     */
    public function resolveForKelas(Kelas $kelas, string $periode): ?Titimangsa
    {
        $taId = (int) $kelas->akademik_master_tahun_ajaran_id;
        $jenjang = strtoupper(trim((string) $kelas->jenjang_kelas));
        $tingkatan = trim((string) $kelas->tingkatan_kelas);
        $normPeriode = strtolower(trim($periode));

        if ($taId <= 0 || $jenjang === '' || ! array_key_exists($normPeriode, Titimangsa::PERIODE_OPTIONS)) {
            return null;
        }

        $tingkatKeys = $tingkatan !== '' && $tingkatan !== Titimangsa::TINGKATAN_SEMUA
            ? [$tingkatan, Titimangsa::TINGKATAN_SEMUA]
            : [Titimangsa::TINGKATAN_SEMUA];

        $candidates = Titimangsa::query()
            ->select(self::INDEX_COLUMNS)
            ->withMasterRelations()
            ->where('akademik_master_tahun_ajaran_id', $taId)
            ->where('jenjang_kelas', $jenjang)
            ->where('periode', $normPeriode)
            ->whereIn('tingkatan_kelas', $tingkatKeys)
            ->where('status', true)
            ->get();

        return Titimangsa::resolveFromCollection($candidates, $taId, $jenjang, $tingkatan, $normPeriode);
    }

    /**
     * Menyimpan pengaturan titimangsa baru.
     *
     * @param  array<string, mixed>  $data
     */
    public function store(array $data): Titimangsa
    {
        return Titimangsa::create($this->prepareAttributes($data));
    }

    /**
     * Memperbarui pengaturan titimangsa.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Titimangsa $titimangsa, array $data): bool
    {
        return $titimangsa->update($this->prepareAttributes($data));
    }

    /**
     * Menghapus pengaturan titimangsa.
     */
    public function delete(Titimangsa $titimangsa): bool
    {
        return (bool) $titimangsa->delete();
    }

    /**
     * Mengubah status aktif/nonaktif titimangsa secara cepat.
     */
    public function toggleStatus(Titimangsa $titimangsa): bool
    {
        return $titimangsa->update([
            'status' => ! (bool) $titimangsa->status,
        ]);
    }

    /**
     * Menormalisasi atribut serta mengisi snapshot nama Kepala Sekolah otomatis saat memilih NIPY karyawan.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareAttributes(array $data): array
    {
        $nipy = isset($data['kepala_sekolah_nipy']) ? trim((string) $data['kepala_sekolah_nipy']) : '';
        $snapshot = isset($data['nama_kepala_sekolah_snapshot'])
            ? trim((string) $data['nama_kepala_sekolah_snapshot'])
            : '';

        if ($nipy !== '' && $snapshot === '') {
            $kepsek = Karyawan::query()
                ->select(['nipy', 'nama_lengkap', 'gelar'])
                ->where('nipy', $nipy)
                ->first();

            if ($kepsek instanceof Karyawan) {
                $snapshot = $kepsek->nama_gelar;
            }
        }

        $tingkatan = trim((string) ($data['tingkatan_kelas'] ?? ''));

        return [
            'akademik_master_tahun_ajaran_id' => (int) $data['akademik_master_tahun_ajaran_id'],
            'jenjang_kelas' => strtoupper(trim((string) $data['jenjang_kelas'])),
            'tingkatan_kelas' => $tingkatan !== '' ? $tingkatan : Titimangsa::TINGKATAN_SEMUA,
            'periode' => strtolower(trim((string) $data['periode'])),
            'tempat' => trim((string) $data['tempat']),
            'tanggal_titimangsa' => $data['tanggal_titimangsa'],
            'kepala_sekolah_nipy' => $nipy !== '' ? $nipy : null,
            'nama_kepala_sekolah_snapshot' => $snapshot !== '' ? $snapshot : null,
            'jabatan_kepala_sekolah' => trim((string) ($data['jabatan_kepala_sekolah'] ?? '')) ?: Titimangsa::DEFAULT_JABATAN,
            'status' => (bool) ($data['status'] ?? true),
        ];
    }
}
