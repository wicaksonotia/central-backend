<?php

use App\Modules\Posyandu\Controllers\AuthController;
use App\Modules\Posyandu\Controllers\AntrianController;
use App\Modules\Posyandu\Controllers\KunjunganController;
use App\Modules\Posyandu\Controllers\PemeriksaanController;
use App\Modules\Posyandu\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/posyandu')->group(function () {

    // =====================================================
    // PUBLIC ROUTES
    // =====================================================

    Route::get('/ping', function () {
        return response()->json([
            'success' => true,
            'message' => 'Posyandu API is running',
            'timestamp' => now()->toDateTimeString(),
        ]);
    });

    // Authentication - OTP
    Route::prefix('auth')
        ->controller(AuthController::class)
        ->group(function () {
            Route::post('/request-otp', 'requestOtp');
            Route::post('/verify-otp', 'verifyOtp');
        });

    // =====================================================
    // AUTHENTICATED ROUTES
    // =====================================================

    Route::middleware([
        'auth:posyandu',
        'posyandu.user',
    ])->group(function () {

        // Profile & Session
        Route::controller(AuthController::class)->group(function () {
            Route::get('/me', 'me');
            Route::post('/logout', 'logout');
        });

        // =================================================
        // ADMIN POSYANDU
        // =================================================

        Route::prefix('admin')
            ->middleware('posyandu.admin')
            ->group(function () {

                // User / Kader Management
                Route::prefix('users')
                    ->controller(UserController::class)
                    ->group(function () {
                        Route::get('/', 'index');
                        Route::post('/', 'store');
                        Route::get('/{userId}', 'show');
                        Route::put('/{userId}', 'update');
                        Route::delete('/{userId}', 'destroy');
                        Route::post('/{userId}/activate', 'activate');
                    });
            });

        // =================================================
        // KUNJUNGAN
        // =================================================

        Route::post(
            '/kunjungan',
            [KunjunganController::class, 'store']
        );

        // =================================================
        // ANTRIAN
        // =================================================

        Route::prefix('kegiatan/{kegiatanId}/pos/{posyanduPosId}/antrian')
            ->controller(AntrianController::class)
            ->group(function () {
                Route::get('/', 'index');
                Route::post('/{kunjunganPosId}/panggil', 'panggil');
                Route::post('/{kunjunganPosId}/lewati', 'lewati');
                Route::post('/{kunjunganPosId}/panggil-ulang', 'panggilUlang');
                Route::post('/{kunjunganPosId}/selesai', 'selesai');
            });

        // =================================================
        // PEMERIKSAAN
        // =================================================

        Route::prefix('kunjungan-pos')
            ->controller(PemeriksaanController::class)
            ->group(function () {
                Route::get('/{kunjunganPosId}/pemeriksaan', 'show');
                Route::post('/{kunjunganPosId}/pemeriksaan', 'store');
            });
    });
});
