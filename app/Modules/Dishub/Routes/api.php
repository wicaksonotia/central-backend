<?php

use App\Modules\Dishub\Controllers\KendaraanController;
use App\Modules\Dishub\Controllers\MobileContentController;
use App\Modules\Dishub\Controllers\TotalUjiHariIniController;
use App\Modules\Dishub\Controllers\VehicleLookupController;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/dishub')->group(function () {
    Route::post('/kendaraan/cek', [VehicleLookupController::class, 'check'])
        ->middleware(ThrottleRequests::class . ':10,1');

    Route::get(
        '/kendaraan/detail',
        [KendaraanController::class, 'detail']
    );

    Route::get(
        '/kendaraan/hasil-uji',
        [KendaraanController::class, 'hasilUji']
    );

    Route::get('/kendaraan/keterangan-tidak-lulus', [
        KendaraanController::class,
        'keteranganTidakLulus',
    ]);

    Route::get('/kendaraan/riwayat', [
        KendaraanController::class,
        'riwayat',
    ]);

    Route::get('/kendaraan/riwayat/detail', [
        KendaraanController::class,
        'detailRiwayat',
    ]);

    Route::get(
        '/uji/total-hari-ini',
        [TotalUjiHariIniController::class, 'index']
    );

    Route::prefix('')->controller(MobileContentController::class)->group(function () {
        Route::get('/menu', 'menu');
        Route::get('/slider', 'slider');
        Route::get('/berita', 'berita');
        Route::get('/jenisujipersyaratan', 'jenisUjiPersyaratan');
        Route::get('/detailpersyaratan', 'detailPersyaratan');
    });
});
