<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

/**
 * Policy Yaumiyah T2Q (Tahsin & Tahfizh):
 * - Super Admin memiliki akses penuh (via BasePolicy::before()).
 * - Guru T2Q dapat mengelola input, matriks, statistik, dan hapus setoran siswa bimbingannya (sesuai NIPY).
 * - Wali Kelas hanya diizinkan melihat Matriks & Statistik kelas binaan (read-only).
 */
class YaumiyahT2qPolicy extends BasePolicy
{
    public function viewAny(User $user): Response
    {
        return $this->isGuruT2q($user)
            ? Response::allow()
            : Response::deny('Akses input Yaumiyah T2Q khusus Super Admin atau Guru T2Q.');
    }

    public function viewMatriks(User $user): Response
    {
        return ($this->isWaliKelas($user) || $this->isGuruT2q($user))
            ? Response::allow()
            : Response::deny('Akses ditolak. Fitur ini khusus Super Admin, Wali Kelas, atau Guru T2Q.');
    }

    public function viewStatistik(User $user): Response
    {
        return ($this->isWaliKelas($user) || $this->isGuruT2q($user))
            ? Response::allow()
            : Response::deny('Akses ditolak. Fitur ini khusus Super Admin, Wali Kelas, atau Guru T2Q.');
    }

    public function create(User $user): Response
    {
        return $this->isGuruT2q($user)
            ? Response::allow()
            : Response::deny('Penyimpanan setoran Yaumiyah T2Q khusus Super Admin atau Guru T2Q.');
    }

    public function delete(User $user, Model $yaumiyah): Response
    {
        if (! $this->isGuruT2q($user)) {
            return Response::deny('Penghapusan catatan Yaumiyah T2Q khusus Super Admin atau Guru T2Q.');
        }

        $nipy = trim((string) ($user->no_induk ?? ''));
        if ($nipy === '') {
            return Response::deny('Akun Guru T2Q Anda belum memiliki NIPY yang valid.');
        }

        $yaumiyah->loadMissing([
            'anggotaT2q:id,anggota_kelas_id,karyawan_nipy',
            'anggotaT2q.anggotaKelas:id,akademik_master_kelas_id',
            'anggotaT2q.anggotaKelas.kelas:id,akademik_master_tahun_ajaran_id',
            'anggotaT2q.anggotaKelas.kelas.tahunAjaran:id,is_active',
        ]);

        $anggotaT2q = $yaumiyah->anggotaT2q;
        $isActiveTa = (bool) ($anggotaT2q?->anggotaKelas?->kelas?->tahunAjaran?->is_active ?? false);

        if ($anggotaT2q === null || trim((string) $anggotaT2q->karyawan_nipy) !== $nipy || ! $isActiveTa) {
            return Response::deny('Anda hanya dapat menghapus catatan Yaumiyah T2Q siswa bimbingan Anda pada Tahun Ajaran aktif.');
        }

        return Response::allow();
    }
}
