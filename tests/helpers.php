<?php

use App\Models\Admin\DataMaster\AnggotaEkstrakurikuler;
use App\Models\Admin\DataMaster\AnggotaJemputan;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\AnggotaT2q;
use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Models\Admin\DataMaster\Jemputan;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Sekolah;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\DataMaster\TargetCapaian;
use App\Models\Admin\DataMaster\Titimangsa;
use App\Models\Admin\DataPelengkap\BerkebutuhanKhusus;
use App\Models\Admin\DataPelengkap\JenjangPendidikan;
use App\Models\Admin\DataPelengkap\Pekerjaan;
use App\Models\Admin\DataPelengkap\Penghasilan;
use App\Models\Admin\DataPelengkap\Transportasi;
use App\Models\Admin\Informasi\AgendaSekolah;
use App\Models\Admin\Informasi\Pengumuman;
use App\Models\Admin\Pelajaran\JadwalPelajaran;
use App\Models\Admin\Pelajaran\JamPelajaran;
use App\Models\Admin\Pelajaran\KelompokPelajaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Admin\Penilaian\AspekTp;
use App\Models\Admin\Penilaian\Kkm;
use App\Models\Admin\Penilaian\RekapMapel;
use App\Models\Admin\Penilaian\SumatifBab;
use App\Models\Admin\Presensi\PresensiSiswa;
use App\Models\Admin\T2q\YaumiyahTahfizh;
use App\Models\Admin\T2q\YaumiyahTahsin;
use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use App\Support\Sqid;

if (! function_exists('sqidFor')) {
    /**
     * Meng-encode ID menjadi Sqid sesuai nama route (dipakai untuk assertion URL pada test).
     */
    function sqidFor(string $routeName, int|string $id): string
    {
        $map = [
            'anggota-ekstrakurikuler' => AnggotaEkstrakurikuler::class,
            'anggota-jemputan' => AnggotaJemputan::class,
            'anggota-kelas' => AnggotaKelas::class,
            'anggota-t2q' => AnggotaT2q::class,
            'agenda-sekolah' => AgendaSekolah::class,
            'rapor.identitas.kelas' => Kelas::class,
            'rapor.nilai.kelas' => Kelas::class,
            'rapor.identitas.siswa' => AnggotaKelas::class,
            'rapor.nilai.siswa' => AnggotaKelas::class,
            'perkembangan-siswa' => AnggotaKelas::class,
            'presensi-siswa' => PresensiSiswa::class,
            'aspek' => AspekTp::class,
            'sumatif-bab' => SumatifBab::class,
            'toggle-masuk-rapor' => TujuanPembelajaran::class,
            'tujuan-pembelajaran' => TujuanPembelajaran::class,
            'deskripsi' => RekapMapel::class,
            'penilaian-kkm' => Kkm::class,
            'jadwal-pelajaran' => JadwalPelajaran::class,
            'jam-pelajaran' => JamPelajaran::class,
            'kelompok-pelajaran' => KelompokPelajaran::class,
            'mata-pelajaran' => MataPelajaran::class,
            'berkebutuhan-khusus' => BerkebutuhanKhusus::class,
            'jenjang-pendidikan' => JenjangPendidikan::class,
            'pekerjaan' => Pekerjaan::class,
            'penghasilan' => Penghasilan::class,
            'transportasi' => Transportasi::class,
            'pengumuman' => Pengumuman::class,
            'yaumiyah-tahsin' => YaumiyahTahsin::class,
            'yaumiyah-tahfizh' => YaumiyahTahfizh::class,
            'tahun-ajaran' => TahunAjaran::class,
            'target-capaian' => TargetCapaian::class,
            'titimangsa' => Titimangsa::class,
            'ekstrakurikuler' => Ekstrakurikuler::class,
            'jemputan' => Jemputan::class,
            'karyawan' => Karyawan::class,
            'sekolah' => Sekolah::class,
            'siswa' => Siswa::class,
            'kelas' => Kelas::class,
        ];

        foreach ($map as $needle => $model) {
            if (str_contains($routeName, $needle)) {
                return (string) Sqid::encodeFor($model, $id);
            }
        }

        throw new InvalidArgumentException("Route [{$routeName}] belum dipetakan di sqidFor().");
    }
}

if (! function_exists('sqidParam')) {
    /**
     * Meng-encode nilai parameter query (kelas_id, tahun_ajaran_id, dst.) menjadi Sqid.
     */
    function sqidParam(string $param, int|string $id): string
    {
        return (string) Sqid::encodeQueryValue($param, $id);
    }
}
