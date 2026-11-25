
<?php

use App\Http\Controllers\Admin\DataMaster\AnggotaEkstrakurikulerController;
use App\Http\Controllers\Admin\DataMaster\AnggotaJemputanController;
use App\Http\Controllers\Admin\DataMaster\AnggotaKelasController;
use App\Http\Controllers\Admin\DataMaster\AnggotaT2qController;
use App\Http\Controllers\Admin\DataMaster\DaftarJilidController;
use App\Http\Controllers\Admin\DataMaster\EkstrakurikulerController;
use App\Http\Controllers\Admin\DataMaster\JemputanController;
use App\Http\Controllers\Admin\DataMaster\KaryawanController;
use App\Http\Controllers\Admin\DataMaster\KelasController;
use App\Http\Controllers\Admin\DataMaster\SekolahController;
use App\Http\Controllers\Admin\DataMaster\SiswaController;
use App\Http\Controllers\Admin\DataMaster\SiswaMutasiController;
use App\Http\Controllers\Admin\DataMaster\TahunAjaranController;
use App\Http\Controllers\Admin\DataMaster\TargetCapaianController;
use App\Http\Controllers\Admin\DataMaster\TitimangsaController;
use App\Http\Controllers\Admin\DataPelengkap\BerkebutuhanKhususController;
use App\Http\Controllers\Admin\DataPelengkap\JenjangPendidikanController;
use App\Http\Controllers\Admin\DataPelengkap\PekerjaanController;
use App\Http\Controllers\Admin\DataPelengkap\PenghasilanController;
use App\Http\Controllers\Admin\DataPelengkap\TransportasiController;
use App\Http\Controllers\Admin\Informasi\AgendaSekolahController;
use App\Http\Controllers\Admin\Informasi\PengumumanController;
use App\Http\Controllers\Admin\Pelajaran\JadwalPelajaranController;
use App\Http\Controllers\Admin\Pelajaran\JamPelajaranController;
use App\Http\Controllers\Admin\Pelajaran\KelompokPelajaranController;
use App\Http\Controllers\Admin\Pelajaran\MataPelajaranController;
use App\Http\Controllers\Admin\Penilaian\FinalisasiController;
use App\Http\Controllers\Admin\Penilaian\KkmController;
use App\Http\Controllers\Admin\Penilaian\PenilaianTpController;
use App\Http\Controllers\Admin\Penilaian\RaporController;
use App\Http\Controllers\Admin\Presensi\PresensiSiswaController;
use App\Http\Controllers\Admin\Prestasi\PrestasiSiswaController;
use App\Http\Controllers\Admin\T2q\YaumiyahTahfizhController;
use App\Http\Controllers\Admin\T2q\YaumiyahTahsinController;
use App\Http\Controllers\Admin\TujuanPembelajaran\TujuanPembelajaranController;
use App\Http\Controllers\GuruMapel\DashboardController as GuruMapelDashboardController;
use App\Http\Controllers\GuruMapel\JadwalPelajaranController as GuruMapelJadwalPelajaranController;
use App\Http\Controllers\GuruMapel\PenilaianEkstrakurikulerController as GuruMapelPenilaianEkstrakurikulerController;
use App\Http\Controllers\GuruMapel\PenilaianTpController as GuruMapelPenilaianTpController;
use App\Http\Controllers\GuruMapel\PerkembanganSiswaController as GuruMapelPerkembanganSiswaController;
use App\Http\Controllers\GuruT2q\DashboardController as GuruT2qDashboardController;
use App\Http\Controllers\GuruT2q\FinalisasiT2qController as GuruT2qFinalisasiController;
use App\Http\Controllers\GuruT2q\PenilaianEkstrakurikulerController as GuruT2qPenilaianEkstrakurikulerController;
use App\Http\Controllers\GuruT2q\RaporT2qController as GuruT2qRaporController;
use App\Http\Controllers\GuruT2q\YaumiyahTahfizhController as GuruT2qYaumiyahTahfizhController;
use App\Http\Controllers\GuruT2q\YaumiyahTahsinController as GuruT2qYaumiyahTahsinController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SSOController;
use App\Http\Controllers\WaliKelas\AnggotaKelasController as WaliKelasAnggotaKelasController;
use App\Http\Controllers\WaliKelas\DashboardController as WaliKelasDashboardController;
use App\Http\Controllers\WaliKelas\JadwalPelajaranController as WaliKelasJadwalPelajaranController;
use App\Http\Controllers\WaliKelas\PenilaianEkstrakurikulerController as WaliKelasPenilaianEkstrakurikulerController;
use App\Http\Controllers\WaliKelas\PenilaianTpController as WaliKelasPenilaianTpController;
use App\Http\Controllers\WaliKelas\PerkembanganSiswaController as WaliKelasPerkembanganSiswaController;
use App\Http\Controllers\WaliKelas\PresensiSiswaController as WaliKelasPresensiSiswaController;
use App\Http\Controllers\WaliKelas\RaporController as WaliKelasRaporController;
use App\Http\Controllers\WaliKelas\YaumiyahT2qController as WaliKelasYaumiyahT2qController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/sso/login', [SSOController::class, 'getLogin'])->name('sso.login');
Route::get('/callback', [SSOController::class, 'getCallback'])->name('sso.callback');
Route::get('/sso/authuser', [SSOController::class, 'connectUser'])->name('sso.authuser');

Auth::routes();

Route::middleware(['auth', 'sso.check', 'nocache', 'sqid.decode'])->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/wali-kelas/dashboard', [WaliKelasDashboardController::class, 'index'])
        ->name('wali-kelas.dashboard');
    Route::get('/wali-kelas/anggota-kelas', [WaliKelasAnggotaKelasController::class, 'index'])
        ->name('wali-kelas.anggota-kelas.index');
    Route::get('/wali-kelas/anggota-kelas/{anggota_kelas}', [WaliKelasAnggotaKelasController::class, 'show'])
        ->name('wali-kelas.anggota-kelas.show');
    Route::get('/wali-kelas/jadwal-pelajaran', [WaliKelasJadwalPelajaranController::class, 'index'])
        ->name('wali-kelas.jadwal-pelajaran.index');
    Route::get('/wali-kelas/perkembangan-siswa', [WaliKelasPerkembanganSiswaController::class, 'index'])
        ->name('wali-kelas.perkembangan-siswa.index');
    Route::get('/wali-kelas/perkembangan-siswa/{anggota_kelas}', [WaliKelasPerkembanganSiswaController::class, 'show'])
        ->name('wali-kelas.perkembangan-siswa.show');
    Route::get('/wali-kelas/presensi-siswa/tahunan', [WaliKelasPresensiSiswaController::class, 'tahunan'])
        ->name('wali-kelas.presensi-siswa.tahunan');
    Route::get('/wali-kelas/presensi-siswa/rekap', [WaliKelasPresensiSiswaController::class, 'rekap'])
        ->name('wali-kelas.presensi-siswa.rekap');
    Route::get('/wali-kelas/presensi-siswa/bulk', [WaliKelasPresensiSiswaController::class, 'bulk'])
        ->name('wali-kelas.presensi-siswa.bulk');
    Route::post('/wali-kelas/presensi-siswa/bulk', [WaliKelasPresensiSiswaController::class, 'bulkStore'])
        ->name('wali-kelas.presensi-siswa.bulk.store');
    Route::delete('/wali-kelas/presensi-siswa/bulk', [WaliKelasPresensiSiswaController::class, 'bulkDestroy'])
        ->name('wali-kelas.presensi-siswa.bulk.destroy');
    Route::get('/wali-kelas/presensi-siswa', [WaliKelasPresensiSiswaController::class, 'index'])
        ->name('wali-kelas.presensi-siswa.index');
    Route::get('/wali-kelas/presensi-siswa/{presensi_siswa}/edit', [WaliKelasPresensiSiswaController::class, 'edit'])
        ->name('wali-kelas.presensi-siswa.edit');
    Route::match(['put', 'patch'], '/wali-kelas/presensi-siswa/{presensi_siswa}', [WaliKelasPresensiSiswaController::class, 'update'])
        ->name('wali-kelas.presensi-siswa.update');
    Route::delete('/wali-kelas/presensi-siswa/{presensi_siswa}', [WaliKelasPresensiSiswaController::class, 'destroy'])
        ->name('wali-kelas.presensi-siswa.destroy');
    Route::get('/wali-kelas/penilaian/nilai-tp', [WaliKelasPenilaianTpController::class, 'index'])
        ->name('wali-kelas.penilaian-tp.index');
    Route::post('/wali-kelas/penilaian/nilai-tp', [WaliKelasPenilaianTpController::class, 'store'])
        ->name('wali-kelas.penilaian-tp.store');
    Route::post('/wali-kelas/penilaian/nilai-tp/bobot', [WaliKelasPenilaianTpController::class, 'updateBobot'])
        ->name('wali-kelas.penilaian-tp.bobot.update');
    Route::post('/wali-kelas/penilaian/nilai-tp/aspek', [WaliKelasPenilaianTpController::class, 'storeAspek'])
        ->name('wali-kelas.penilaian-tp.aspek.store');
    Route::put('/wali-kelas/penilaian/nilai-tp/aspek/{aspek_tp}', [WaliKelasPenilaianTpController::class, 'updateAspek'])
        ->name('wali-kelas.penilaian-tp.aspek.update');
    Route::delete('/wali-kelas/penilaian/nilai-tp/aspek/{aspek_tp}', [WaliKelasPenilaianTpController::class, 'destroyAspek'])
        ->name('wali-kelas.penilaian-tp.aspek.destroy');
    Route::post('/wali-kelas/penilaian/nilai-tp/sumatif-bab', [WaliKelasPenilaianTpController::class, 'storeSumatifBab'])
        ->name('wali-kelas.penilaian-tp.sumatif-bab.store');
    Route::put('/wali-kelas/penilaian/nilai-tp/sumatif-bab/{sumatif_bab}', [WaliKelasPenilaianTpController::class, 'updateSumatifBab'])
        ->name('wali-kelas.penilaian-tp.sumatif-bab.update');
    Route::delete('/wali-kelas/penilaian/nilai-tp/sumatif-bab/{sumatif_bab}', [WaliKelasPenilaianTpController::class, 'destroySumatifBab'])
        ->name('wali-kelas.penilaian-tp.sumatif-bab.destroy');
    Route::get('/wali-kelas/penilaian/nilai-tp/export', [WaliKelasPenilaianTpController::class, 'export'])
        ->name('wali-kelas.penilaian-tp.export');
    Route::post('/wali-kelas/penilaian/nilai-tp/import', [WaliKelasPenilaianTpController::class, 'import'])
        ->name('wali-kelas.penilaian-tp.import');
    Route::get('/wali-kelas/penilaian/rekap', [WaliKelasPenilaianTpController::class, 'rekap'])
        ->name('wali-kelas.penilaian-rekap.index');
    Route::put('/wali-kelas/penilaian/rekap/{rekap_mapel}/deskripsi', [WaliKelasPenilaianTpController::class, 'updateDeskripsi'])
        ->name('wali-kelas.penilaian-rekap.deskripsi.update');
    Route::post('/wali-kelas/penilaian/rekap/regenerate-deskripsi', [WaliKelasPenilaianTpController::class, 'regenerateDeskripsi'])
        ->name('wali-kelas.penilaian-rekap.deskripsi.regenerate');
    Route::post('/wali-kelas/penilaian/finalisasi', [FinalisasiController::class, 'finalize'])
        ->name('wali-kelas.penilaian-finalisasi.finalize');
    Route::post('/wali-kelas/penilaian/finalisasi/unlock', [FinalisasiController::class, 'unlock'])
        ->name('wali-kelas.penilaian-finalisasi.unlock');
    Route::get('/wali-kelas/rapor/identitas/kelas/{kelas}', [WaliKelasRaporController::class, 'identitasKelas'])
        ->name('wali-kelas.rapor.identitas.kelas');
    Route::get('/wali-kelas/rapor/identitas/siswa/{anggota_kelas}', [WaliKelasRaporController::class, 'identitasSiswa'])
        ->name('wali-kelas.rapor.identitas.siswa');
    Route::get('/wali-kelas/rapor/nilai/kelas/{kelas}', [WaliKelasRaporController::class, 'nilaiKelas'])
        ->name('wali-kelas.rapor.nilai.kelas');
    Route::get('/wali-kelas/rapor/nilai/siswa/{anggota_kelas}', [WaliKelasRaporController::class, 'nilaiSiswa'])
        ->name('wali-kelas.rapor.nilai.siswa');
    Route::get('/wali-kelas/t2q/yaumiyah-tahsin/matriks', [WaliKelasYaumiyahT2qController::class, 'matriksTahsin'])
        ->name('wali-kelas.t2q.yaumiyah-tahsin.matriks');
    Route::get('/wali-kelas/t2q/yaumiyah-tahfizh/matriks', [WaliKelasYaumiyahT2qController::class, 'matriksTahfizh'])
        ->name('wali-kelas.t2q.yaumiyah-tahfizh.matriks');
    Route::get('/wali-kelas/t2q/yaumiyah-tahsin/statistik', [WaliKelasYaumiyahT2qController::class, 'statistikTahsin'])
        ->name('wali-kelas.t2q.yaumiyah-tahsin.statistik');
    Route::get('/wali-kelas/t2q/yaumiyah-tahfizh/statistik', [WaliKelasYaumiyahT2qController::class, 'statistikTahfizh'])
        ->name('wali-kelas.t2q.yaumiyah-tahfizh.statistik');
    Route::get('/wali-kelas/ekstrakurikuler', [WaliKelasPenilaianEkstrakurikulerController::class, 'index'])
        ->name('wali-kelas.ekstrakurikuler.index');
    Route::post('/wali-kelas/ekstrakurikuler/wajib/{kelas}/penilaian', [WaliKelasPenilaianEkstrakurikulerController::class, 'storeWajib'])
        ->name('wali-kelas.ekstrakurikuler.wajib.store');
    Route::post('/wali-kelas/ekstrakurikuler/{ekstrakurikuler}/penilaian', [WaliKelasPenilaianEkstrakurikulerController::class, 'store'])
        ->name('wali-kelas.ekstrakurikuler.penilaian.store');
    Route::get('/guru-mapel/dashboard', [GuruMapelDashboardController::class, 'index'])
        ->name('guru-mapel.dashboard');
    Route::get('/guru-mapel/jadwal-pelajaran', [GuruMapelJadwalPelajaranController::class, 'index'])
        ->name('guru-mapel.jadwal-pelajaran.index');
    Route::get('/guru-mapel/perkembangan-siswa', [GuruMapelPerkembanganSiswaController::class, 'index'])
        ->name('guru-mapel.perkembangan-siswa.index');
    Route::get('/guru-mapel/perkembangan-siswa/{anggota_kelas}', [GuruMapelPerkembanganSiswaController::class, 'show'])
        ->name('guru-mapel.perkembangan-siswa.show');
    Route::get('/guru-mapel/penilaian/nilai-tp', [GuruMapelPenilaianTpController::class, 'index'])
        ->name('guru-mapel.penilaian-tp.index');
    Route::post('/guru-mapel/penilaian/nilai-tp', [GuruMapelPenilaianTpController::class, 'store'])
        ->name('guru-mapel.penilaian-tp.store');
    Route::post('/guru-mapel/penilaian/nilai-tp/bobot', [GuruMapelPenilaianTpController::class, 'updateBobot'])
        ->name('guru-mapel.penilaian-tp.bobot.update');
    Route::post('/guru-mapel/penilaian/nilai-tp/aspek', [GuruMapelPenilaianTpController::class, 'storeAspek'])
        ->name('guru-mapel.penilaian-tp.aspek.store');
    Route::put('/guru-mapel/penilaian/nilai-tp/aspek/{aspek_tp}', [GuruMapelPenilaianTpController::class, 'updateAspek'])
        ->name('guru-mapel.penilaian-tp.aspek.update');
    Route::delete('/guru-mapel/penilaian/nilai-tp/aspek/{aspek_tp}', [GuruMapelPenilaianTpController::class, 'destroyAspek'])
        ->name('guru-mapel.penilaian-tp.aspek.destroy');
    Route::post('/guru-mapel/penilaian/nilai-tp/sumatif-bab', [GuruMapelPenilaianTpController::class, 'storeSumatifBab'])
        ->name('guru-mapel.penilaian-tp.sumatif-bab.store');
    Route::put('/guru-mapel/penilaian/nilai-tp/sumatif-bab/{sumatif_bab}', [GuruMapelPenilaianTpController::class, 'updateSumatifBab'])
        ->name('guru-mapel.penilaian-tp.sumatif-bab.update');
    Route::delete('/guru-mapel/penilaian/nilai-tp/sumatif-bab/{sumatif_bab}', [GuruMapelPenilaianTpController::class, 'destroySumatifBab'])
        ->name('guru-mapel.penilaian-tp.sumatif-bab.destroy');
    Route::get('/guru-mapel/penilaian/nilai-tp/export', [GuruMapelPenilaianTpController::class, 'export'])
        ->name('guru-mapel.penilaian-tp.export');
    Route::post('/guru-mapel/penilaian/nilai-tp/import', [GuruMapelPenilaianTpController::class, 'import'])
        ->name('guru-mapel.penilaian-tp.import');
    Route::get('/guru-mapel/penilaian/rekap', [GuruMapelPenilaianTpController::class, 'rekap'])
        ->name('guru-mapel.penilaian-rekap.index');
    Route::put('/guru-mapel/penilaian/rekap/{rekap_mapel}/deskripsi', [GuruMapelPenilaianTpController::class, 'updateDeskripsi'])
        ->name('guru-mapel.penilaian-rekap.deskripsi.update');
    Route::post('/guru-mapel/penilaian/rekap/regenerate-deskripsi', [GuruMapelPenilaianTpController::class, 'regenerateDeskripsi'])
        ->name('guru-mapel.penilaian-rekap.deskripsi.regenerate');
    Route::post('/guru-mapel/penilaian/finalisasi', [FinalisasiController::class, 'finalize'])
        ->name('guru-mapel.penilaian-finalisasi.finalize');
    Route::post('/guru-mapel/penilaian/finalisasi/unlock', [FinalisasiController::class, 'unlock'])
        ->name('guru-mapel.penilaian-finalisasi.unlock');
    Route::get('/guru-mapel/ekstrakurikuler', [GuruMapelPenilaianEkstrakurikulerController::class, 'index'])
        ->name('guru-mapel.ekstrakurikuler.index');
    Route::post('/guru-mapel/ekstrakurikuler/{ekstrakurikuler}/penilaian', [GuruMapelPenilaianEkstrakurikulerController::class, 'store'])
        ->name('guru-mapel.ekstrakurikuler.penilaian.store');
    Route::get('/guru-t2q/dashboard', [GuruT2qDashboardController::class, 'index'])
        ->name('guru-t2q.dashboard');
    Route::get('/guru-t2q/yaumiyah-tahsin', [GuruT2qYaumiyahTahsinController::class, 'index'])
        ->name('guru-t2q.yaumiyah-tahsin.index');
    Route::get('/guru-t2q/yaumiyah-tahsin/matriks', [GuruT2qYaumiyahTahsinController::class, 'matriks'])
        ->name('guru-t2q.yaumiyah-tahsin.matriks');
    Route::get('/guru-t2q/yaumiyah-tahsin/statistik', [GuruT2qYaumiyahTahsinController::class, 'statistik'])
        ->name('guru-t2q.yaumiyah-tahsin.statistik');
    Route::post('/guru-t2q/yaumiyah-tahsin', [GuruT2qYaumiyahTahsinController::class, 'store'])
        ->name('guru-t2q.yaumiyah-tahsin.store');
    Route::delete('/guru-t2q/yaumiyah-tahsin/{yaumiyah_tahsin}', [GuruT2qYaumiyahTahsinController::class, 'destroy'])
        ->name('guru-t2q.yaumiyah-tahsin.destroy');
    Route::get('/guru-t2q/yaumiyah-tahfizh', [GuruT2qYaumiyahTahfizhController::class, 'index'])
        ->name('guru-t2q.yaumiyah-tahfizh.index');
    Route::get('/guru-t2q/yaumiyah-tahfizh/matriks', [GuruT2qYaumiyahTahfizhController::class, 'matriks'])
        ->name('guru-t2q.yaumiyah-tahfizh.matriks');
    Route::get('/guru-t2q/yaumiyah-tahfizh/statistik', [GuruT2qYaumiyahTahfizhController::class, 'statistik'])
        ->name('guru-t2q.yaumiyah-tahfizh.statistik');
    Route::post('/guru-t2q/yaumiyah-tahfizh', [GuruT2qYaumiyahTahfizhController::class, 'store'])
        ->name('guru-t2q.yaumiyah-tahfizh.store');
    Route::delete('/guru-t2q/yaumiyah-tahfizh/{yaumiyah_tahfizh}', [GuruT2qYaumiyahTahfizhController::class, 'destroy'])
        ->name('guru-t2q.yaumiyah-tahfizh.destroy');
    Route::get('/guru-t2q/rapor', [GuruT2qRaporController::class, 'index'])
        ->name('guru-t2q.rapor.index');
    Route::post('/guru-t2q/rapor', [GuruT2qRaporController::class, 'store'])
        ->name('guru-t2q.rapor.store');
    Route::post('/guru-t2q/rapor/generate', [GuruT2qRaporController::class, 'generate'])
        ->name('guru-t2q.rapor.generate');
    Route::post('/guru-t2q/finalisasi', [GuruT2qFinalisasiController::class, 'finalize'])
        ->name('guru-t2q.finalisasi.finalize');
    Route::post('/guru-t2q/finalisasi/unlock', [GuruT2qFinalisasiController::class, 'unlock'])
        ->name('guru-t2q.finalisasi.unlock');
    Route::post('/guru-t2q/penilaian/finalisasi', [FinalisasiController::class, 'finalize'])
        ->name('guru-t2q.penilaian-finalisasi.finalize');
    Route::post('/guru-t2q/penilaian/finalisasi/unlock', [FinalisasiController::class, 'unlock'])
        ->name('guru-t2q.penilaian-finalisasi.unlock');
    Route::get('/guru-t2q/ekstrakurikuler', [GuruT2qPenilaianEkstrakurikulerController::class, 'index'])
        ->name('guru-t2q.ekstrakurikuler.index');
    Route::post('/guru-t2q/ekstrakurikuler/{ekstrakurikuler}/penilaian', [GuruT2qPenilaianEkstrakurikulerController::class, 'store'])
        ->name('guru-t2q.ekstrakurikuler.penilaian.store');
    Route::post('/data-master/tahun-ajaran/{tahun_ajaran}/toggle-active', [TahunAjaranController::class, 'toggleActive'])->name('tahun-ajaran.toggle-active');
    Route::resource('/data-master/tahun-ajaran', TahunAjaranController::class);
    Route::resource('/data-master/kelas', KelasController::class)->parameters(
        ['kelas' => 'kelas']
    );
    Route::resource('/data-master/anggota-kelas', AnggotaKelasController::class)
        ->except(['show'])
        ->parameters(['anggota-kelas' => 'anggota_kelas']);
    Route::resource('/data-master/anggota-ekstrakurikuler', AnggotaEkstrakurikulerController::class)
        ->except(['show'])
        ->parameters(['anggota-ekstrakurikuler' => 'anggota_ekstrakurikuler']);
    Route::resource('/data-master/ekstrakurikuler', EkstrakurikulerController::class);
    Route::resource('/data-master/jemputan', JemputanController::class);
    Route::resource('/data-master/anggota-jemputan', AnggotaJemputanController::class)
        ->except(['show'])
        ->parameters(['anggota-jemputan' => 'anggota_jemputan']);
    Route::resource('/data-master/anggota-t2q', AnggotaT2qController::class)
        ->except(['show'])
        ->parameters(['anggota-t2q' => 'anggota_t2q']);
    Route::get('/data-master/daftar-jilid', [DaftarJilidController::class, 'index'])
        ->name('daftar-jilid.index');
    Route::resource('/data-master/target-capaian', TargetCapaianController::class)
        ->except(['show'])
        ->parameters(['target-capaian' => 'target_capaian']);
    Route::resource('/data-master/siswa', SiswaController::class);
    Route::middleware('role:super admin')
        ->prefix('/data-master/siswa-mutasi')
        ->name('siswa-mutasi.')
        ->group(function (): void {
            Route::get('/', [SiswaMutasiController::class, 'index'])->name('index');
            Route::get('/kelulusan', [SiswaMutasiController::class, 'kelulusanForm'])->name('kelulusan.create');
            Route::post('/kelulusan', [SiswaMutasiController::class, 'kelulusanStore'])->name('kelulusan.store');
            Route::get('/mutasi-keluar', [SiswaMutasiController::class, 'mutasiKeluarForm'])->name('keluar.create');
            Route::post('/mutasi-keluar', [SiswaMutasiController::class, 'mutasiKeluarStore'])->name('keluar.store');
            Route::get('/mutasi-masuk', [SiswaMutasiController::class, 'mutasiMasukForm'])->name('masuk.create');
            Route::post('/mutasi-masuk', [SiswaMutasiController::class, 'mutasiMasukStore'])->name('masuk.store');
            Route::post('/{siswa_mutasi}/batalkan', [SiswaMutasiController::class, 'batalkan'])->name('batalkan');
        });
    Route::get('/data-master/karyawan', [KaryawanController::class, 'index'])->name('karyawan.index');
    Route::get('/data-master/karyawan/{karyawan}', [KaryawanController::class, 'show'])->name('karyawan.show');
    Route::get('/data-master/karyawan/{karyawan}/foto', [KaryawanController::class, 'foto'])->name('karyawan.foto');
    Route::post('/data-master/titimangsa/{titimangsa}/toggle-status', [TitimangsaController::class, 'toggleStatus'])
        ->name('titimangsa.toggle-status');
    Route::resource('/data-master/titimangsa', TitimangsaController::class)
        ->except(['show'])
        ->parameters(['titimangsa' => 'titimangsa']);
    Route::post('/data-master/sekolah/{sekolah}/toggle-status', [SekolahController::class, 'toggleStatus'])
        ->name('sekolah.toggle-status');
    Route::resource('/data-master/sekolah', SekolahController::class)
        ->except(['show'])
        ->parameters(['sekolah' => 'sekolah']);

    // Data Pelengkap
    Route::resource('/data-pelengkap/berkebutuhan-khusus', BerkebutuhanKhususController::class)
        ->parameters(['berkebutuhan-khusus' => 'berkebutuhan_khusus']);
    Route::resource('/data-pelengkap/jenjang-pendidikan', JenjangPendidikanController::class);
    Route::resource('/data-pelengkap/pekerjaan', PekerjaanController::class);
    Route::resource('/data-pelengkap/penghasilan', PenghasilanController::class);
    Route::resource('/data-pelengkap/transportasi', TransportasiController::class);

    // Informasi
    Route::resource('/informasi/agenda-sekolah', AgendaSekolahController::class)
        ->except(['show'])
        ->parameters(['agenda-sekolah' => 'agenda_sekolah']);
    Route::resource('/informasi/pengumuman', PengumumanController::class)
        ->except(['show'])
        ->parameters(['pengumuman' => 'pengumuman']);

    // Pelajaran
    Route::resource('/pelajaran/kelompok-pelajaran', KelompokPelajaranController::class)
        ->except(['show'])
        ->parameters(['kelompok-pelajaran' => 'kelompok_pelajaran']);
    Route::post('/pelajaran/mata-pelajaran/{mata_pelajaran}/toggle-status', [MataPelajaranController::class, 'toggleStatus'])
        ->name('mata-pelajaran.toggle-status');
    Route::resource('/pelajaran/mata-pelajaran', MataPelajaranController::class)
        ->except(['show'])
        ->parameters(['mata-pelajaran' => 'mata_pelajaran']);
    Route::resource('/pelajaran/jam-pelajaran', JamPelajaranController::class)
        ->except(['show'])
        ->parameters(['jam-pelajaran' => 'jam_pelajaran']);
    Route::post('/pelajaran/jadwal-pelajaran/{jadwal_pelajaran}/toggle-status', [JadwalPelajaranController::class, 'toggleStatus'])
        ->name('jadwal-pelajaran.toggle-status');
    Route::resource('/pelajaran/jadwal-pelajaran', JadwalPelajaranController::class)
        ->except(['show'])
        ->parameters(['jadwal-pelajaran' => 'jadwal_pelajaran']);

    // Tujuan Pembelajaran
    Route::get('/tujuan-pembelajaran/tp/export', [TujuanPembelajaranController::class, 'export'])
        ->name('tujuan-pembelajaran.export');
    Route::get('/tujuan-pembelajaran/tp/import/template', [TujuanPembelajaranController::class, 'template'])
        ->name('tujuan-pembelajaran.template');
    Route::post('/tujuan-pembelajaran/tp/import', [TujuanPembelajaranController::class, 'import'])
        ->name('tujuan-pembelajaran.import');
    Route::post('/tujuan-pembelajaran/tp/{tujuan_pembelajaran}/toggle-status', [TujuanPembelajaranController::class, 'toggleStatus'])
        ->name('tujuan-pembelajaran.toggle-status');
    Route::resource('/tujuan-pembelajaran/tp', TujuanPembelajaranController::class)
        ->except(['show'])
        ->names('tujuan-pembelajaran')
        ->parameters(['tp' => 'tujuan_pembelajaran']);

    // Penilaian TP (SD)
    Route::resource('/penilaian/kkm', KkmController::class)
        ->except(['show'])
        ->names('penilaian-kkm')
        ->parameters(['kkm' => 'kkm']);
    Route::get('/penilaian/nilai-tp', [PenilaianTpController::class, 'index'])
        ->name('penilaian-tp.index');
    Route::post('/penilaian/nilai-tp', [PenilaianTpController::class, 'store'])
        ->name('penilaian-tp.store');
    Route::post('/penilaian/nilai-tp/bobot', [PenilaianTpController::class, 'updateBobot'])
        ->name('penilaian-tp.bobot.update');
    Route::post('/penilaian/nilai-tp/aspek', [PenilaianTpController::class, 'storeAspek'])
        ->name('penilaian-tp.aspek.store');
    Route::put('/penilaian/nilai-tp/aspek/{aspek_tp}', [PenilaianTpController::class, 'updateAspek'])
        ->name('penilaian-tp.aspek.update');
    Route::delete('/penilaian/nilai-tp/aspek/{aspek_tp}', [PenilaianTpController::class, 'destroyAspek'])
        ->name('penilaian-tp.aspek.destroy');
    Route::post('/penilaian/nilai-tp/sumatif-bab', [PenilaianTpController::class, 'storeSumatifBab'])
        ->name('penilaian-tp.sumatif-bab.store');
    Route::put('/penilaian/nilai-tp/sumatif-bab/{sumatif_bab}', [PenilaianTpController::class, 'updateSumatifBab'])
        ->name('penilaian-tp.sumatif-bab.update');
    Route::delete('/penilaian/nilai-tp/sumatif-bab/{sumatif_bab}', [PenilaianTpController::class, 'destroySumatifBab'])
        ->name('penilaian-tp.sumatif-bab.destroy');
    Route::get('/penilaian/nilai-tp/export', [PenilaianTpController::class, 'export'])
        ->name('penilaian-tp.export');
    Route::post('/penilaian/nilai-tp/import', [PenilaianTpController::class, 'import'])
        ->name('penilaian-tp.import');
    Route::get('/penilaian/rekap', [PenilaianTpController::class, 'rekap'])
        ->name('penilaian-rekap.index');
    Route::put('/penilaian/rekap/{rekap_mapel}/deskripsi', [PenilaianTpController::class, 'updateDeskripsi'])
        ->name('penilaian-rekap.deskripsi.update');
    Route::post('/penilaian/rekap/regenerate-deskripsi', [PenilaianTpController::class, 'regenerateDeskripsi'])
        ->name('penilaian-rekap.deskripsi.regenerate');
    Route::post('/penilaian/finalisasi', [FinalisasiController::class, 'finalize'])
        ->name('penilaian-finalisasi.finalize');
    Route::post('/penilaian/finalisasi/unlock', [FinalisasiController::class, 'unlock'])
        ->name('penilaian-finalisasi.unlock');
    Route::get('/penilaian/rapor', [RaporController::class, 'index'])
        ->name('penilaian-rapor.index');
    Route::get('/penilaian/rapor/identitas/kelas/{kelas}', [RaporController::class, 'identitasKelas'])
        ->name('penilaian-rapor.identitas.kelas');
    Route::get('/penilaian/rapor/identitas/siswa/{anggota_kelas}', [RaporController::class, 'identitasSiswa'])
        ->name('penilaian-rapor.identitas.siswa');
    Route::get('/penilaian/rapor/nilai/kelas/{kelas}', [RaporController::class, 'nilaiKelas'])
        ->name('penilaian-rapor.nilai.kelas');
    Route::get('/penilaian/rapor/nilai/siswa/{anggota_kelas}', [RaporController::class, 'nilaiSiswa'])
        ->name('penilaian-rapor.nilai.siswa');

    // Presensi
    Route::get('/presensi/presensi-siswa/tahunan', [PresensiSiswaController::class, 'tahunan'])
        ->name('presensi-siswa.tahunan');
    Route::get('/presensi/presensi-siswa/rekap', [PresensiSiswaController::class, 'rekap'])
        ->name('presensi-siswa.rekap');
    Route::get('/presensi/presensi-siswa/bulk', [PresensiSiswaController::class, 'bulk'])
        ->name('presensi-siswa.bulk');
    Route::post('/presensi/presensi-siswa/bulk', [PresensiSiswaController::class, 'bulkStore'])
        ->name('presensi-siswa.bulk.store');
    Route::delete('/presensi/presensi-siswa/bulk', [PresensiSiswaController::class, 'bulkDestroy'])
        ->name('presensi-siswa.bulk.destroy');
    Route::post('/presensi/presensi-siswa/sync-fingerspot', [PresensiSiswaController::class, 'syncFingerspot'])
        ->name('presensi-siswa.sync-fingerspot');
    Route::resource('/presensi/presensi-siswa', PresensiSiswaController::class)
        ->except(['show', 'create', 'store'])
        ->parameters(['presensi-siswa' => 'presensi_siswa']);

    // T2Q (Yaumiyah Tahsin & Tahfizh)
    Route::get('/t2q/yaumiyah-tahsin', [YaumiyahTahsinController::class, 'index'])
        ->name('t2q.yaumiyah-tahsin.index');
    Route::get('/t2q/yaumiyah-tahsin/matriks', [YaumiyahTahsinController::class, 'matriks'])
        ->name('t2q.yaumiyah-tahsin.matriks');
    Route::get('/t2q/yaumiyah-tahsin/statistik', [YaumiyahTahsinController::class, 'statistik'])
        ->name('t2q.yaumiyah-tahsin.statistik');
    Route::post('/t2q/yaumiyah-tahsin', [YaumiyahTahsinController::class, 'store'])
        ->name('t2q.yaumiyah-tahsin.store');
    Route::delete('/t2q/yaumiyah-tahsin/{yaumiyah_tahsin}', [YaumiyahTahsinController::class, 'destroy'])
        ->name('t2q.yaumiyah-tahsin.destroy');

    Route::get('/t2q/yaumiyah-tahfizh', [YaumiyahTahfizhController::class, 'index'])
        ->name('t2q.yaumiyah-tahfizh.index');
    Route::get('/t2q/yaumiyah-tahfizh/matriks', [YaumiyahTahfizhController::class, 'matriks'])
        ->name('t2q.yaumiyah-tahfizh.matriks');
    Route::get('/t2q/yaumiyah-tahfizh/statistik', [YaumiyahTahfizhController::class, 'statistik'])
        ->name('t2q.yaumiyah-tahfizh.statistik');
    Route::post('/t2q/yaumiyah-tahfizh', [YaumiyahTahfizhController::class, 'store'])
        ->name('t2q.yaumiyah-tahfizh.store');
    Route::delete('/t2q/yaumiyah-tahfizh/{yaumiyah_tahfizh}', [YaumiyahTahfizhController::class, 'destroy'])
        ->name('t2q.yaumiyah-tahfizh.destroy');

    // Prestasi Siswa
    Route::resource('/prestasi/prestasi-siswa', PrestasiSiswaController::class)
        ->parameters(['prestasi-siswa' => 'prestasi_siswa']);

});
