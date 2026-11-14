<?php

use App\Modules\Posyandu\Controllers\AuthController;
use App\Modules\Posyandu\Controllers\AntrianController;
use App\Modules\Posyandu\Controllers\KunjunganController;
use App\Modules\Posyandu\Controllers\PemeriksaanController;
use App\Modules\Posyandu\Controllers\PosyanduPosController;
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

                // Manajemen kader
                Route::apiResource('users', UserController::class)
                    ->only(['index', 'store', 'show', 'update', 'destroy']);

                Route::post('users/{userId}/activate', [
                    UserController::class,
                    'activate'
                ]);

                Route::controller(PosyanduPosController::class)->group(function () {
                    Route::get('/pos', 'index');
                    Route::put('/pos', 'update');
                });
            });

        // =================================================
        // KUNJUNGAN
        // =================================================

        Route::controller(KunjunganController::class)->group(function () {
            Route::post('/kunjungan', 'store');
        });

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
