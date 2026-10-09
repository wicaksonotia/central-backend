
<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Admin\InstansiController;
use App\Http\Controllers\Api\V1\Admin\UnitController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Autentikasi
    Route::prefix('auth')->group(function () {
        Route::post('/phone/request-otp', [AuthController::class, 'requestOtp']);
        Route::post('/phone/verify-otp', [AuthController::class, 'verifyOtp']);
    });

    // Pengguna yang sudah login
    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/me', [AuthController::class, 'updateProfile']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Admin utama
        Route::prefix('admin')
            ->middleware('main.admin')
            ->group(function () {
                Route::apiResource('instansi', InstansiController::class);
                Route::apiResource('units', UnitController::class);
            });
    });
});