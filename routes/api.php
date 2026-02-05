<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AbsenController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AktivitasController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LayananController;
use App\Http\Controllers\DokumenPribadiController;
use App\Http\Controllers\SinkronisasiController;
use App\Http\Controllers\GpsController;
use App\Http\Controllers\LaporanApiController;


Route::post('/sign-in', [LoginController::class, 'signIn']);
Route::post('/row-insert-user', [SinkronisasiController::class, 'insert_user']);
Route::post('/push-master-aktivitas', [SinkronisasiController::class, 'push_master_aktivitas']);
Route::post('/push-tpp-jabatan', [SinkronisasiController::class, 'push_nilai_tpp_ke_jabatan']);


Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::middleware('my-throttle')->group(function () {
        Route::get('/current-user', [LoginController::class, 'current_user']);
        Route::get('/waktu-server', [HomeController::class, 'waktu_server']);
        Route::post('/check-gps', [GpsController::class, 'checkGps']);
        Route::prefix('absen')->group(function () {
            Route::get('/check-absen', [AbsenController::class, 'checkAbsen']);
            Route::get('/check-absen-nakes', [AbsenController::class, 'checkAbsenNakes']);
            Route::get('/hapus-absen', [AbsenController::class, 'hapusAbsen']);
            Route::post('/presensi', [AbsenController::class, 'presensi']);
        });

        Route::prefix('profile')->group(function () {
            Route::post('/update', [ProfileController::class, 'updateProfil']);
            Route::get('/get-image-profil', [ProfileController::class, 'getImageProfil']);
            Route::post('/ubah-password', [ProfileController::class, 'ubahPassword']);
            Route::post('/verifikasi-wajah', [ProfileController::class, 'verifikasiWajah']);
            Route::post('/hapus-wajah', [ProfileController::class, 'hapus_wajah']);
        });

        Route::prefix('option')->group(function () {
            Route::get('/master-aktivitas', [AktivitasController::class, 'option_master_aktivitas']);
            Route::get('/status-kawin', [ProfileController::class, 'option_status_kawin']);
            Route::get('/golongan', [ProfileController::class, 'option_golongan']);
            Route::get('/pendidikan', [ProfileController::class, 'option_pendidikan']);
            Route::get('/sasaran-kinerja', [ProfileController::class, 'option_skp']);
        });

        Route::prefix('home')->group(function () {
            Route::get('/pegawai', [HomeController::class, 'pegawai']);
            Route::get('/atasan', [HomeController::class, 'atasan']);
            Route::get('/kinerja', [HomeController::class, 'kinerja']);
            Route::get('/kehadiran', [HomeController::class, 'kehadiran']);
            Route::get('/tpp', [HomeController::class, 'tpp']);
            Route::get('/sasaran-kinerja', [HomeController::class, 'sasaran_kinerja']);
        });

        Route::prefix('aktivitas')->group(function () {
            Route::get('/list', [AktivitasController::class, 'list']);
            Route::post('/store', [AktivitasController::class, 'store']);
            Route::get('/show/{params}', [AktivitasController::class, 'show']);
            Route::post('/update/{params}', [AktivitasController::class, 'update']);
            Route::delete('/delete/{params}', [AktivitasController::class, 'delete']);
            Route::get('/check-menit-kinerja/{params}', [AktivitasController::class, 'checkMenitKinerja']);
        });

        Route::prefix('pengumuman')->group(function () {
            Route::get('/list', [HomeController::class, 'pengumuman']);
        });

        Route::prefix('layanan')->group(function () {
            Route::get('/list', [LayananController::class, 'list']);
            Route::get('/get-icon', [LayananController::class, 'getIcon']);
            Route::get('/list-cuti', [LayananController::class, 'cuti_list']);
            Route::get('/dokumen-by-cuti/{params}', [LayananController::class, 'dokumenByCuti']);
            Route::get('/dokumen-cuti-by-cuti/{params}', [LayananController::class, 'dokumenCutiByCuti']);

            Route::get('/layanan-general', [LayananController::class, 'layananGeneral']);

            Route::post('/store', [LayananController::class, 'store']);
            Route::get('/detail-cuti/{params}', [LayananController::class, 'cuti_detail']);
            Route::post('/update/{params}', [LayananController::class, 'update']);
            Route::get('/option', [LayananController::class, 'option']);
        });

        Route::prefix('dokumen-pribadi')->group(function () {
            Route::get('/', [DokumenPribadiController::class, 'dokumen_pribadi']);
            Route::get('/file-dokumen-pribadi', [DokumenPribadiController::class, 'file_dokumen_pribadi']);
        });

        Route::post('/logout', [LoginController::class, 'revoke']);
    });

    Route::prefix('set-laporan')->group(function () {
        Route::get('/laporan-kehadiran-pegawai', [LaporanApiController::class, 'export_pegawai_bulan'])->name('setlaporan.laporan.kehadiran.export');
        Route::get('/laporan-kinerja-pegawai', [LaporanApiController::class, 'export_to_kinerja_pegawai'])->name('setlaporan.laporan.kinerja.export');
        Route::get('/laporan-tpp-pegawai', [LaporanApiController::class, 'export_to_tpp_pegawai'])->name('setlaporan.laporan.tpp.export');
    });
});
