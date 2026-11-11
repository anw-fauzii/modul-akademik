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
    /**
     * Membangun query daftar siswa beserta relasi utama tanpa N+1.
     *
     * @return Builder<Siswa>
     */
    public function buildIndexQuery(): Builder
    {
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
