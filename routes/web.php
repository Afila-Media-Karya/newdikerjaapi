<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LaporanApiController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('set-laporan')->group(function () {
    Route::get('/laporan-kehadiran-pegawai', [LaporanApiController::class, 'export_pegawai_bulan'])->name('setlaporan.laporan.kehadiran.export');
    Route::get('/laporan-kinerja-pegawai', [LaporanApiController::class, 'export_to_kinerja_pegawai'])->name('setlaporan.laporan.kinerja.export');
    Route::get('/laporan-tpp-pegawai', [LaporanApiController::class, 'export_to_tpp_pegawai'])->name('setlaporan.laporan.tpp.export');
});
