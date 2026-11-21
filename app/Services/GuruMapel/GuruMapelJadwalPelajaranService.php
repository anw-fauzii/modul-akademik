<?php

namespace App\Services\GuruMapel;

use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\Pelajaran\JadwalPelajaran;
use App\Models\Admin\Pelajaran\JamPelajaran;
use App\Models\Admin\Penilaian\Kkm;
use App\Models\User;
use App\Services\Admin\Pelajaran\JadwalPelajaranService;
use App\Services\WaliKelas\WaliKelasScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class GuruMapelJadwalPelajaranService
{
    public function __construct(
        protected ?WaliKelasScopeService $scopeService = null
    ) {
        $this->scopeService ??= new WaliKelasScopeService;
    }

    /**
     * Memuat jadwal mengajar pribadi Guru Mapel SD (khusus slot dengan karyawan_nipy === user->no_induk)
     * pada Tahun Ajaran aktif tanpa N+1.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function getIndexData(User $user, array $input = []): array
    {
        $taughtScope = $this->scopeService->getTaughtSdScopeForUser($user, null, true);
        $activeTahunAjaran = $taughtScope['activeTahunAjaran'];
        $nipy = (string) ($taughtScope['nipy'] ?? '');
        $karyawanProfile = $taughtScope['karyawanProfile'] ?? null;

        $rawKelasId = trim((string) ($input['kelas_id'] ?? ''));
        $requestedKelasId = ($rawKelasId !== '' && ctype_digit($rawKelasId) && (int) $rawKelasId > 0)
            ? (int) $rawKelasId
            : null;
        $hari = trim((string) ($input['hari'] ?? ''));
        $search = trim((string) ($input['search'] ?? ''));

        if ($activeTahunAjaran === null || $nipy === '' || $taughtScope['taughtClasses']->isEmpty()) {
            return [
                'isEligible' => false,
                'ineligibleReason' => $taughtScope['ineligibleReason'] ?? 'not_assigned_active_year',
                'nipy' => $nipy,
                'karyawanProfile' => $karyawanProfile,
                'activeTahunAjaran' => $activeTahunAjaran,
                'taughtClasses' => new EloquentCollection,
                'jadwalPelajaran' => new EloquentCollection,
                'weeklySlots' => [],
                'jamSlots' => new EloquentCollection,
                'hariOptions' => JadwalPelajaran::HARI_OPTIONS,
                'summary' => [
                    'total' => 0,
                    'classes' => 0,
                    'per_hari' => array_fill_keys(array_keys(JadwalPelajaran::HARI_OPTIONS), 0),
                ],
                'filters' => [
                    'kelas_id' => '',
                    'hari' => $hari,
                    'search' => $search,
                ],
            ];
        }

        /** @var EloquentCollection<int, Kelas> $taughtClasses */
        $taughtClasses = $taughtScope['taughtClasses'];
        if ($requestedKelasId !== null && ! $taughtClasses->contains('id', $requestedKelasId)) {
            abort(403, 'Anda hanya dapat memfilter kelas yang Anda ajar pada Jadwal Pelajaran aktif.');
        }

        $baseQuery = JadwalPelajaran::query()
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
            ])
            ->withMasterRelations()
            ->where('akademik_master_tahun_ajaran_id', $activeTahunAjaran->id)
            ->where('karyawan_nipy', $nipy)
            ->where('status', true)
            ->whereHas('kelas', static fn (Builder $q) => $q->whereRaw('UPPER(jenjang_kelas) = ?', [Kkm::TARGET_UNIT]));

        $allMySlots = (clone $baseQuery)->ordered()->get();

        $perHari = array_fill_keys(array_keys(JadwalPelajaran::HARI_OPTIONS), 0);
        foreach ($allMySlots as $slot) {
            $h = (int) $slot->hari;
            if (isset($perHari[$h])) {
                $perHari[$h]++;
            }
        }

        $filteredSlots = $allMySlots->filter(static function (JadwalPelajaran $slot) use ($requestedKelasId, $hari, $search): bool {
            if ($requestedKelasId !== null && (int) $slot->akademik_master_kelas_id !== $requestedKelasId) {
                return false;
            }
            if ($hari !== '' && $hari !== JadwalPelajaranService::FILTER_ALL && (int) $slot->hari !== (int) $hari) {
                return false;
            }
            if ($search !== '') {
                $needle = mb_strtolower($search);
                $hay = mb_strtolower(implode(' ', array_filter([
                    $slot->kelas?->nama_kelas,
                    $slot->mataPelajaran?->nama_mata_pelajaran,
                    $slot->mataPelajaran?->singkatan,
                    $slot->ruang,
                    $slot->keterangan,
                ])));
                if (! str_contains($hay, $needle)) {
                    return false;
                }
            }

            return true;
        })->values();

        // Susun jadwal mingguan pribadi: jam_ke x hari (Senin-Jumat)
        $jamIds = $allMySlots->pluck('akademik_pelajaran_jam_pelajaran_id')->filter()->unique()->values()->all();
        $jamSlots = $jamIds !== []
            ? JamPelajaran::query()
                ->select(['id', 'jam_ke', 'unit', 'jam_mulai', 'jam_selesai', 'keterangan'])
                ->whereIn('id', $jamIds)
                ->orderBy('jam_ke')
                ->get()
            : new EloquentCollection;

        $weeklySlots = [];
        foreach ($filteredSlots as $slot) {
            $jamId = (int) ($slot->akademik_pelajaran_jam_pelajaran_id ?? 0);
            $dayNum = (int) $slot->hari;
            $weeklySlots[$jamId][$dayNum][] = $slot;
        }

        return [
            'isEligible' => true,
            'ineligibleReason' => null,
            'nipy' => $nipy,
            'karyawanProfile' => $karyawanProfile,
            'activeTahunAjaran' => $activeTahunAjaran,
            'taughtClasses' => $taughtClasses,
            'jadwalPelajaran' => new EloquentCollection($filteredSlots->all()),
            'weeklySlots' => $weeklySlots,
            'jamSlots' => $jamSlots,
            'hariOptions' => JadwalPelajaran::HARI_OPTIONS,
            'summary' => [
                'total' => $allMySlots->count(),
                'classes' => $taughtClasses->count(),
                'per_hari' => $perHari,
            ],
            'filters' => [
                'kelas_id' => $requestedKelasId !== null ? (string) $requestedKelasId : '',
                'hari' => $hari,
                'search' => $search,
            ],
        ];
    }
}
