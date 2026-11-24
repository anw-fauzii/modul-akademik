<?php

namespace App\Providers;

use App\Models\Admin\DataMaster\AnggotaEkstrakurikuler;
use App\Models\Admin\DataMaster\AnggotaJemputan;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\AnggotaT2q;
use App\Models\Admin\DataMaster\DaftarJilid;
use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Models\Admin\DataMaster\Jemputan;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\KomentarT2q;
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
use App\Models\Admin\Penilaian\PenilaianFinalisasi;
use App\Models\Admin\Penilaian\RekapMapel;
use App\Models\Admin\Penilaian\SumatifBab;
use App\Models\Admin\Presensi\PresensiSiswa;
use App\Models\Admin\T2q\RaporT2q;
use App\Models\Admin\T2q\YaumiyahTahfizh;
use App\Models\Admin\T2q\YaumiyahTahsin;
use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use App\Policies\AdminOnlyPolicy;
use App\Policies\AnggotaKelasPolicy;
use App\Policies\AspekTpPolicy;
use App\Policies\EkstrakurikulerPolicy;
use App\Policies\JadwalPelajaranPolicy;
use App\Policies\KelasPolicy;
use App\Policies\PenilaianFinalisasiPolicy;
use App\Policies\PresensiSiswaPolicy;
use App\Policies\RaporT2qPolicy;
use App\Policies\RekapMapelPolicy;
use App\Policies\SumatifBabPolicy;
use App\Policies\TujuanPembelajaranPolicy;
use App\Policies\YaumiyahT2qPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Peta registrasi eksplisit Policy seluruh entitas aplikasi.
     *
     * @var array<class-string, class-string>
     */
    private const POLICIES = [
        AnggotaEkstrakurikuler::class => AdminOnlyPolicy::class,
        AnggotaJemputan::class => AdminOnlyPolicy::class,
        AnggotaKelas::class => AnggotaKelasPolicy::class,
        AnggotaT2q::class => AdminOnlyPolicy::class,
        DaftarJilid::class => AdminOnlyPolicy::class,
        Ekstrakurikuler::class => EkstrakurikulerPolicy::class,
        Jemputan::class => AdminOnlyPolicy::class,
        Karyawan::class => AdminOnlyPolicy::class,
        Kelas::class => KelasPolicy::class,
        KomentarT2q::class => AdminOnlyPolicy::class,
        Sekolah::class => AdminOnlyPolicy::class,
        Siswa::class => AdminOnlyPolicy::class,
        TahunAjaran::class => AdminOnlyPolicy::class,
        TargetCapaian::class => AdminOnlyPolicy::class,
        Titimangsa::class => AdminOnlyPolicy::class,
        BerkebutuhanKhusus::class => AdminOnlyPolicy::class,
        JenjangPendidikan::class => AdminOnlyPolicy::class,
        Pekerjaan::class => AdminOnlyPolicy::class,
        Penghasilan::class => AdminOnlyPolicy::class,
        Transportasi::class => AdminOnlyPolicy::class,
        AgendaSekolah::class => AdminOnlyPolicy::class,
        Pengumuman::class => AdminOnlyPolicy::class,
        JadwalPelajaran::class => JadwalPelajaranPolicy::class,
        JamPelajaran::class => AdminOnlyPolicy::class,
        KelompokPelajaran::class => AdminOnlyPolicy::class,
        MataPelajaran::class => AdminOnlyPolicy::class,
        Kkm::class => AdminOnlyPolicy::class,
        AspekTp::class => AspekTpPolicy::class,
        SumatifBab::class => SumatifBabPolicy::class,
        TujuanPembelajaran::class => TujuanPembelajaranPolicy::class,
        PenilaianFinalisasi::class => PenilaianFinalisasiPolicy::class,
        RekapMapel::class => RekapMapelPolicy::class,
        PresensiSiswa::class => PresensiSiswaPolicy::class,
        RaporT2q::class => RaporT2qPolicy::class,
        YaumiyahTahfizh::class => YaumiyahT2qPolicy::class,
        YaumiyahTahsin::class => YaumiyahT2qPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach (self::POLICIES as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }
}
