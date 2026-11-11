
<?php

use App\Http\Controllers\Admin\DataMaster\AnggotaKelasController;
use App\Http\Controllers\Admin\DataMaster\EkstrakurikulerController;
use App\Http\Controllers\Admin\DataMaster\JemputanController;
use App\Http\Controllers\Admin\DataMaster\KaryawanController;
use App\Http\Controllers\Admin\DataMaster\KelasController;
use App\Http\Controllers\Admin\DataMaster\SiswaController;
use App\Http\Controllers\Admin\DataMaster\TahunAjaranController;
use App\Http\Controllers\Admin\DataPelengkap\BerkebutuhanKhususController;
use App\Http\Controllers\Admin\DataPelengkap\JenjangPendidikanController;
use App\Http\Controllers\Admin\DataPelengkap\PekerjaanController;
use App\Http\Controllers\Admin\DataPelengkap\PenghasilanController;
use App\Http\Controllers\Admin\DataPelengkap\TransportasiController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SSOController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/sso/login', [SSOController::class, 'getLogin'])->name('sso.login');
Route::get('/callback', [SSOController::class, 'getCallback'])->name('sso.callback');
Route::get('/sso/authuser', [SSOController::class, 'connectUser'])->name('sso.authuser');

Auth::routes();

Route::middleware(['auth', 'sso.check', 'nocache'])->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::post('/data-master/tahun-ajaran/{tahun_ajaran}/toggle-active', [TahunAjaranController::class, 'toggleActive'])->name('tahun-ajaran.toggle-active');
    Route::resource('/data-master/tahun-ajaran', TahunAjaranController::class);
    Route::resource('/data-master/kelas', KelasController::class)->parameters(
        ['kelas' => 'kelas']
    );
    Route::resource('/data-master/anggota-kelas', AnggotaKelasController::class)
        ->except(['show'])
        ->parameters(['anggota-kelas' => 'anggota_kelas']);
    Route::resource('/data-master/ekstrakurikuler', EkstrakurikulerController::class)->except(['show']);
    Route::resource('/data-master/jemputan', JemputanController::class)->except(['show']);
    Route::resource('/data-master/siswa', SiswaController::class);
    Route::get('/data-master/karyawan', [KaryawanController::class, 'index'])->name('karyawan.index');
    Route::get('/data-master/karyawan/{karyawan}', [KaryawanController::class, 'show'])->name('karyawan.show');

    // Data Pelengkap
    Route::resource('/data-pelengkap/berkebutuhan-khusus', BerkebutuhanKhususController::class);
    Route::resource('/data-pelengkap/jenjang-pendidikan', JenjangPendidikanController::class);
    Route::resource('/data-pelengkap/pekerjaan', PekerjaanController::class);
    Route::resource('/data-pelengkap/penghasilan', PenghasilanController::class);
    Route::resource('/data-pelengkap/transportasi', TransportasiController::class);

});
