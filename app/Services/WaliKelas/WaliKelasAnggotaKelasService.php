<?php

namespace App\Services\WaliKelas;

use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\User;
use App\Services\Admin\DataMaster\AnggotaKelasService;
use App\Services\Admin\DataMaster\SiswaService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class WaliKelasAnggotaKelasService
{
    /**
     * Kolom biodata ringkas siswa yang dimuat pada daftar anggota kelas Wali Kelas (anti-N+1).
     *
     * @var list<string>
     */
    private const ROSTER_SISWA_COLUMNS = [
        'nis',
        'nama_lengkap',
        'nisn',
        'jenis_kelamin',
        'status',
        'avatar',
        'tempat_lahir',
        'tanggal_lahir',
        'nomor_hp',
        'whatsapp',
        'email',
        'nama_ayah',
        'nama_ibu',
    ];

    public function __construct(
        protected ?WaliKelasScopeService $scopeService = null,
        protected ?AnggotaKelasService $anggotaKelasService = null,
        protected ?SiswaService $siswaService = null
    ) {
        $this->scopeService ??= new WaliKelasScopeService;
        $this->anggotaKelasService ??= new AnggotaKelasService;
        $this->siswaService ??= new SiswaService;
    }

    /**
     * Mengambil data daftar anggota kelas yang dibina user pada tahun ajaran aktif tanpa N+1.
     *
     * @return array<string, mixed>
     */
    public function getIndexData(User $user, ?int $requestedKelasId = null): array
    {
        $scope = $this->scopeService->resolveScopeContext($user, $requestedKelasId);

        if (! $scope['isEligible'] || $scope['selectedKelas'] === null || $scope['activeTahunAjaran'] === null) {
            return [
                ...$scope,
                'students' => new EloquentCollection,
                'studentStats' => $this->scopeService->summarizeStudents(new EloquentCollection),
            ];
        }

        $students = $this->scopeService->sortAnggotaByStudentName(
            $this->anggotaKelasService
                ->buildIndexQuery($scope['activeTahunAjaran']->id, $scope['selectedKelas']->id)
                ->with(['siswa:'.implode(',', self::ROSTER_SISWA_COLUMNS)])
                ->get()
        );

        return [
            ...$scope,
            'students' => $students,
            'studentStats' => $this->scopeService->summarizeStudents($students),
        ];
    }

    /**
     * Memuat biodata lengkap anggota kelas milik Wali Kelas / Pendamping dengan proteksi kepemilikan & anti-N+1.
     *
     * @return array<string, mixed>
     */
    public function getStudentDetail(User $user, AnggotaKelas $anggotaKelas): array
    {
        $scope = $this->scopeService->authorizeAnggotaKelasForUser($user, $anggotaKelas);
        $anggotaKelas->setRelation('kelas', $scope['selectedKelas']);

        $siswa = $anggotaKelas->relationLoaded('siswa')
            ? $anggotaKelas->getRelation('siswa')
            : Siswa::query()->where('nis', $anggotaKelas->siswa_nis)->first();

        if (! $siswa instanceof Siswa) {
            abort(404, 'Data siswa tidak ditemukan.');
        }

        $siswa = $this->siswaService->loadDetail($siswa);
        $anggotaKelas->setRelation('siswa', $siswa);

        return [
            ...$scope,
            'anggotaKelas' => $anggotaKelas,
            'siswa' => $siswa,
        ];
    }
}
