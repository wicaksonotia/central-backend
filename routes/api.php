<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('/phone/request-otp', [AuthController::class, 'requestOtp']);
        Route::post('/phone/verify-otp', [AuthController::class, 'verifyOtp']);
    });

    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/me', [AuthController::class, 'me']);

        Route::put('/me', [AuthController::class, 'updateProfile']);

        Route::post('/auth/logout', [AuthController::class, 'logout']);
    });
});