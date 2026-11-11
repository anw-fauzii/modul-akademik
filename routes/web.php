
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SSOController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/sso/login', [SSOController::class, 'getLogin'])->name('sso.login');
Route::get('/callback', [SSOController::class, 'getCallback'])->name('sso.callback');
Route::get('/sso/authuser', [SSOController::class, 'connectUser'])->name('sso.authuser');

Auth::routes();

Route::middleware(['auth', 'sso.check', 'nocache'])->group(function () {
    Route::get('/home', [App\Http\Controllers\HomeController::class,'index'])->name('home');
    Route::post('/data-master/tahun-ajaran/{tahun_ajaran}/toggle-active', [App\Http\Controllers\Admin\DataMaster\TahunAjaranController::class, 'toggleActive'])->name('tahun-ajaran.toggle-active');
    Route::resource('/data-master/tahun-ajaran', App\Http\Controllers\Admin\DataMaster\TahunAjaranController::class);
    Route::resource('/data-master/kelas', App\Http\Controllers\Admin\DataMaster\KelasController::class)->parameters(
        ['kelas' => 'kelas']
    );
    Route::get('/data-master/karyawan', [App\Http\Controllers\Admin\DataMaster\KaryawanController::class, 'index'])->name('karyawan.index');
    Route::get('/data-master/karyawan/{karyawan}', [App\Http\Controllers\Admin\DataMaster\KaryawanController::class, 'show'])->name('karyawan.show');

    // Data Pelengkap
    Route::resource('/data-pelengkap/berkebutuhan-khusus', App\Http\Controllers\Admin\DataPelengkap\BerkebutuhanKhususController::class);
    Route::resource('/data-pelengkap/jenjang-pendidikan', App\Http\Controllers\Admin\DataPelengkap\JenjangPendidikanController::class);
    Route::resource('/data-pelengkap/pekerjaan', App\Http\Controllers\Admin\DataPelengkap\PekerjaanController::class);
    Route::resource('/data-pelengkap/penghasilan', App\Http\Controllers\Admin\DataPelengkap\PenghasilanController::class);
    Route::resource('/data-pelengkap/transportasi', App\Http\Controllers\Admin\DataPelengkap\TransportasiController::class);

});