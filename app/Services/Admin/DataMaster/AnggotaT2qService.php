<?php

namespace App\Services\Admin\DataMaster;

use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\AnggotaT2q;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Services\Admin\DataMaster\Concerns\ManagesTahunAjaranMembership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AnggotaT2qService
{
    use ManagesTahunAjaranMembership;

    private const string NESTED_SISWA_MEMBERSHIP_RELATION = 'anggotaT2q.anggotaKelas.kelas';

    private const string ENTITY_LABEL = 'anggota T2Q';

    /**
     * Membangun query daftar anggota T2Q beserta relasi kelas, siswa, tahun ajaran, dan guru T2Q tanpa N+1.
     *
     * @return Builder<AnggotaT2q>
     */
    public function buildIndexQuery(
        ?int $tahunAjaranId = null,
        ?string $karyawanNipy = null,
        ?string $tingkat = null,
        ?int $kelasId = null
    ): Builder {
        $normalizedNipy = trim((string) $karyawanNipy);
        $normalizedTingkat = AnggotaT2q::normalizeTingkat($tingkat);

        return AnggotaT2q::query()
            ->select([
                'id',
                'anggota_kelas_id',
                'karyawan_nipy',
                'tingkat',
                'pendaftaran',
                'keterangan',
            ])
            ->withMasterRelations()
            ->when($tahunAjaranId !== null, fn (Builder $query) => $query->forTahunAjaran($tahunAjaranId))
            ->when($normalizedNipy !== '', fn (Builder $query) => $query->forKaryawan($normalizedNipy))
            ->when($normalizedTingkat !== '', fn (Builder $query) => $query->forTingkat($normalizedTingkat))
            ->when($kelasId !== null, fn (Builder $query) => $query->forKelas($kelasId))
            ->orderBy('tingkat')
            ->orderBy('karyawan_nipy')
            ->orderBy('anggota_kelas_id')
            ->orderBy('id');
    }

    /**
     * Mengambil semua data anggota T2Q dengan filter opsional tanpa N+1.
     *
     * @return Collection<int, AnggotaT2q>
     */
    public function getAll(
        ?int $tahunAjaranId = null,
        ?string $karyawanNipy = null,
        ?string $tingkat = null,
        ?int $kelasId = null
    ): Collection {
        return $this->buildIndexQuery($tahunAjaranId, $karyawanNipy, $tingkat, $kelasId)->get();
    }

    /**
     * Mengambil daftar opsi filter untuk halaman indeks anggota T2Q.
     *
     * @return array{
     *     tahunAjaran: Collection<int, TahunAjaran>,
     *     kelas: Collection<int, Kelas>,
     *     karyawan: Collection<int, Karyawan>,
     *     tingkatOptions: list<string>
     * }
     */
    public function getFilterOptions(): array
    {
        return [
            ...$this->buildSharedMembershipFilterOptions(),
            'karyawan' => $this->getKaryawanOptions(),
            'tingkatOptions' => $this->getTingkatOptions(),
        ];
    }

    /**
     * Mengambil daftar opsi dropdown untuk form edit anggota T2Q tanpa N+1.
     *
     * @return array{
     *     karyawan: Collection<int, Karyawan>,
     *     tingkatOptions: list<string>,
     *     anggotaKelas: Collection<int, AnggotaKelas>
     * }
     */
    public function getFormOptions(?AnggotaT2q $anggotaT2q = null): array
    {
        $tahunAjaranId = $anggotaT2q?->anggotaKelas?->kelas?->akademik_master_tahun_ajaran_id;

        $anggotaKelas = $this->baseAnggotaKelasFormQuery()
            ->when(
                $tahunAjaranId !== null,
                fn (Builder $query) => $query->whereHas(
                    'kelas',
                    fn (Builder $kelasQuery) => $kelasQuery->where('akademik_master_tahun_ajaran_id', (int) $tahunAjaranId)
                )
            )
            ->get();

        return [
            'karyawan' => $this->getKaryawanOptions(),
            'tingkatOptions' => AnggotaT2q::buildTingkatOptions(array_merge(
                $this->getTingkatOptions(),
                [$anggotaT2q?->tingkat]
            )),
            'anggotaKelas' => $anggotaKelas,
        ];
    }

    /**
     * Mengambil opsi form tambah anggota T2Q berdasarkan Tahun Ajaran terpilih atau aktif.
     *
     * @return array{
     *     tahunAjaran: Collection<int, TahunAjaran>,
     *     selectedTahunAjaranId: ?int,
     *     karyawan: Collection<int, Karyawan>,
     *     tingkatOptions: list<string>,
     *     anggotaKelas: Collection<int, AnggotaKelas>,
     *     excludedTahunAjaranIds: list<int>,
     *     hiddenRegisteredCount: int,
     *     hiddenInactiveCount: int,
     *     hiddenWithoutKelasCount: int
     * }
     */
    public function getCreateFormOptions(?int $targetTahunAjaranId = null): array
    {
        $tahunAjaran = TahunAjaran::query()
            ->select(['id', 'nama_tahun_ajaran', 'semester', 'is_active'])
            ->orderByDesc('is_active')
            ->orderByDesc('nama_tahun_ajaran')
            ->orderBy('semester')
            ->orderByDesc('id')
            ->get();

        $selectedTahunAjaran = $targetTahunAjaranId !== null
            ? $tahunAjaran->firstWhere('id', $targetTahunAjaranId)
            : null;

        if ($selectedTahunAjaran === null) {
            $selectedTahunAjaran = $tahunAjaran->firstWhere('is_active', true) ?? $tahunAjaran->first();
        }

        $selectedTahunAjaranId = $selectedTahunAjaran?->id !== null ? (int) $selectedTahunAjaran->id : null;
        $targetTahunAjaranIds = $selectedTahunAjaranId !== null ? [$selectedTahunAjaranId] : [];

        return [
            'tahunAjaran' => $tahunAjaran,
            'selectedTahunAjaranId' => $selectedTahunAjaranId,
            'karyawan' => $this->getKaryawanOptions(),
            'tingkatOptions' => $this->getTingkatOptions(),
            ...$this->buildCandidateAnggotaKelasPayloadFor(
                $targetTahunAjaranIds,
                $this->buildCreateAnggotaKelasQuery($targetTahunAjaranIds),
                self::NESTED_SISWA_MEMBERSHIP_RELATION
            ),
        ];
    }

    /**
     * Membangun query kandidat Anggota Kelas pada form tambah anggota T2Q.
     *
     * @param  list<int>  $targetTahunAjaranIds
     * @return Builder<AnggotaKelas>
     */
    public function buildCreateAnggotaKelasQuery(array $targetTahunAjaranIds = []): Builder
    {
        return $this->applyCandidateAnggotaKelasFilters(
            $this->baseAnggotaKelasFormQuery(),
            $targetTahunAjaranIds,
            self::NESTED_SISWA_MEMBERSHIP_RELATION
        );
    }

    /**
     * Mencari ID anggota kelas yang tahun ajaran kelasnya tidak sama dengan tahun ajaran tujuan.
     *
     * @param  list<int>  $anggotaKelasIds
     * @return list<int>
     */
    public function findMismatchedTahunAjaranAnggotaKelas(array $anggotaKelasIds, int $tahunAjaranId): array
    {
        return $this->findMismatchedTahunAjaranAnggotaKelasIds($anggotaKelasIds, $tahunAjaranId);
    }

    /**
     * Mencari daftar NIS siswa dari anggota kelas terpilih yang sudah terdaftar di T2Q
     * pada tahun ajaran/semester yang sama.
     *
     * @param  list<int>  $anggotaKelasIds
     * @return list<string>
     */
    public function findConflictingAnggotaKelasInTahunAjaran(
        array $anggotaKelasIds,
        int $tahunAjaranId,
        ?int $ignoreAnggotaT2qId = null,
        bool $lockForUpdate = false
    ): array {
        return $this->findConflictingMemberNisInTahunAjaran(
            AnggotaT2q::class,
            $anggotaKelasIds,
            $tahunAjaranId,
            $ignoreAnggotaT2qId,
            $lockForUpdate
        );
    }

    /**
     * Menyimpan satu atau beberapa anggota T2Q secara atomik dalam satu transaksi.
     *
     * @param  array<string, mixed>  $data
     * @return Collection<int, AnggotaT2q>
     */
    public function store(array $data): Collection
    {
        $anggotaKelasIds = array_values(array_unique(array_map(
            'intval',
            $this->normalizeBatchIdentifiers($data['anggota_kelas_id'])
        )));
        $karyawanNipy = trim((string) $data['karyawan_nipy']);
        $rawTingkat = AnggotaT2q::normalizeTingkat($data['tingkat']);
        $pendaftaran = (string) $data['pendaftaran'];
        $keterangan = $this->normalizeNullableText($data['keterangan'] ?? null);
        $requestedTahunAjaranId = isset($data['tahun_ajaran_id']) && is_numeric($data['tahun_ajaran_id'])
            ? (int) $data['tahun_ajaran_id']
            : null;

        return DB::transaction(function () use (
            $anggotaKelasIds,
            $karyawanNipy,
            $rawTingkat,
            $pendaftaran,
            $keterangan,
            $requestedTahunAjaranId
        ): Collection {
            $anggotaKelasRows = AnggotaKelas::query()
                ->select(['id', 'siswa_nis', 'akademik_master_kelas_id'])
                ->with(['kelas:id,akademik_master_tahun_ajaran_id,tingkatan_kelas'])
                ->whereIn('id', $anggotaKelasIds)
                ->get()
                ->keyBy('id');

            if ($requestedTahunAjaranId !== null) {
                $this->assertValidAnggotaKelasTahunAjaranAndNoConflict(
                    AnggotaT2q::class,
                    self::ENTITY_LABEL,
                    $anggotaKelasIds,
                    $anggotaKelasRows,
                    $requestedTahunAjaranId
                );
            } else {
                $groupedByYear = $anggotaKelasRows->groupBy(
                    static fn (AnggotaKelas $row): int => (int) ($row->kelas?->akademik_master_tahun_ajaran_id ?? 0)
                );

                if ($anggotaKelasRows->count() !== count($anggotaKelasIds) || $groupedByYear->has(0)) {
                    throw ValidationException::withMessages([
                        'anggota_kelas_id' => 'Data kelas anggota yang dipilih tidak valid atau belum terhubung ke tahun ajaran.',
                    ]);
                }

                foreach ($groupedByYear as $tahunAjaranId => $rows) {
                    $idsInYear = $rows->pluck('id')->map(static fn (mixed $id): int => (int) $id)->values()->all();
                    $this->assertValidAnggotaKelasTahunAjaranAndNoConflict(
                        AnggotaT2q::class,
                        self::ENTITY_LABEL,
                        $idsInYear,
                        $rows->keyBy('id'),
                        (int) $tahunAjaranId
                    );
                }
            }

            $seenStudentYears = [];
            $created = new Collection;

            foreach ($anggotaKelasIds as $anggotaKelasId) {
                /** @var AnggotaKelas $anggotaKelasRow */
                $anggotaKelasRow = $anggotaKelasRows->get($anggotaKelasId);
                $studentYearKey = $anggotaKelasRow->siswa_nis.':'.(int) ($anggotaKelasRow->kelas?->akademik_master_tahun_ajaran_id ?? 0);

                if (isset($seenStudentYears[$studentYearKey])) {
                    throw ValidationException::withMessages([
                        'anggota_kelas_id' => "Siswa dengan NIS {$anggotaKelasRow->siswa_nis} dipilih lebih dari sekali dalam tahun ajaran yang sama.",
                    ]);
                }

                $seenStudentYears[$studentYearKey] = true;

                $created->push(AnggotaT2q::create([
                    'anggota_kelas_id' => $anggotaKelasRow->id,
                    'karyawan_nipy' => $karyawanNipy,
                    'tingkat' => $this->resolveEffectiveTingkat($rawTingkat, $anggotaKelasRow),
                    'pendaftaran' => $pendaftaran,
                    'keterangan' => $keterangan,
                ]));
            }

            return $created;
        });
    }

    /**
     * Memperbarui data anggota T2Q secara atomik dalam satu transaksi.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(AnggotaT2q $anggotaT2q, array $data): bool
    {
        $newAnggotaKelasId = (int) $data['anggota_kelas_id'];
        $karyawanNipy = trim((string) $data['karyawan_nipy']);
        $rawTingkat = AnggotaT2q::normalizeTingkat($data['tingkat']);
        $keterangan = $this->normalizeNullableText($data['keterangan'] ?? null);

        return DB::transaction(function () use (
            $anggotaT2q,
            $data,
            $newAnggotaKelasId,
            $karyawanNipy,
            $rawTingkat,
            $keterangan
        ): bool {
            $targetAnggotaKelas = AnggotaKelas::query()
                ->select(['id', 'siswa_nis', 'akademik_master_kelas_id'])
                ->with(['kelas:id,akademik_master_tahun_ajaran_id,tingkatan_kelas'])
                ->findOrFail($newAnggotaKelasId);

            $targetTahunAjaranId = (int) ($targetAnggotaKelas->kelas?->akademik_master_tahun_ajaran_id ?? 0);

            $this->assertValidAnggotaKelasTahunAjaranAndNoConflict(
                AnggotaT2q::class,
                self::ENTITY_LABEL,
                [$newAnggotaKelasId],
                new Collection([$targetAnggotaKelas->id => $targetAnggotaKelas]),
                $targetTahunAjaranId,
                (int) $anggotaT2q->id
            );

            return $anggotaT2q->update([
                'anggota_kelas_id' => $targetAnggotaKelas->id,
                'karyawan_nipy' => $karyawanNipy,
                'tingkat' => $this->resolveEffectiveTingkat($rawTingkat, $targetAnggotaKelas),
                'pendaftaran' => $data['pendaftaran'],
                'keterangan' => $keterangan,
            ]);
        });
    }

    /**
     * Menghapus data anggota T2Q dari database.
     */
    public function delete(AnggotaT2q $anggotaT2q): bool
    {
        return (bool) $anggotaT2q->delete();
    }

    /**
     * Menentukan nilai tingkat aktual (mendukung mode otomatis mengikuti tingkatan kelas siswa).
     *
     * @throws ValidationException
     */
    private function resolveEffectiveTingkat(string $rawTingkat, AnggotaKelas $anggotaKelas): string
    {
        if ($rawTingkat !== AnggotaT2q::TINGKAT_AUTO) {
            return $rawTingkat;
        }

        $kelasTingkat = AnggotaT2q::normalizeTingkat($anggotaKelas->kelas?->tingkatan_kelas);

        if ($kelasTingkat === '') {
            throw ValidationException::withMessages([
                'tingkat' => 'Tingkatan kelas untuk salah satu siswa belum diisi sehingga tingkat T2Q harus dipilih manual.',
            ]);
        }

        return $kelasTingkat;
    }

    /**
     * Query dasar daftar Anggota Kelas beserta relasi siswa, kelas, dan riwayat T2Q.
     *
     * @return Builder<AnggotaKelas>
     */
    private function baseAnggotaKelasFormQuery(): Builder
    {
        return AnggotaKelas::query()
            ->select([
                'id',
                'siswa_nis',
                'akademik_master_kelas_id',
                'pendaftaran',
            ])
            ->with([
                'siswa:nis,nama_lengkap,jenis_kelamin,nisn,status',
                'siswa.anggotaT2q:akademik_master_anggota_t2q.id,akademik_master_anggota_t2q.anggota_kelas_id,akademik_master_anggota_t2q.karyawan_nipy,akademik_master_anggota_t2q.tingkat,akademik_master_anggota_t2q.pendaftaran',
                'siswa.anggotaT2q.guruT2q:nipy,nama_lengkap,gelar',
                'siswa.anggotaT2q.anggotaKelas:id,siswa_nis,akademik_master_kelas_id',
                'siswa.anggotaT2q.anggotaKelas.kelas:id,akademik_master_tahun_ajaran_id,nama_kelas,jenjang_kelas,tingkatan_kelas',
                'siswa.anggotaT2q.anggotaKelas.kelas.tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
                'kelas:id,akademik_master_tahun_ajaran_id,nama_kelas,jenjang_kelas,tingkatan_kelas',
                'kelas.tahunAjaran:id,nama_tahun_ajaran,semester,is_active',
            ])
            ->orderBy('akademik_master_kelas_id')
            ->orderBy('siswa_nis')
            ->orderBy('id');
    }

    /**
     * Mengambil daftar guru / karyawan untuk pilihan pembimbing T2Q.
     *
     * @return Collection<int, Karyawan>
     */
    private function getKaryawanOptions(): Collection
    {
        return Karyawan::query()
            ->select(['nipy', 'nama_lengkap', 'gelar'])
            ->orderBy('nama_lengkap')
            ->orderBy('nipy')
            ->get();
    }

    /**
     * Mengambil daftar pilihan tingkat standar digabungkan dengan tingkat yang tercatat di Kelas dan Anggota T2Q.
     *
     * @return list<string>
     */
    private function getTingkatOptions(): array
    {
        $kelasTingkat = Kelas::query()
            ->select(['tingkatan_kelas'])
            ->whereNotNull('tingkatan_kelas')
            ->distinct()
            ->pluck('tingkatan_kelas');

        $t2qTingkat = AnggotaT2q::query()
            ->select(['tingkat'])
            ->whereNotNull('tingkat')
            ->distinct()
            ->pluck('tingkat');

        return AnggotaT2q::buildTingkatOptions($kelasTingkat->merge($t2qTingkat));
    }
}
