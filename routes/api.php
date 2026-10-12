
<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ComplaintController;
use App\Http\Controllers\Api\V1\Admin\InstansiController;
use App\Http\Controllers\Api\V1\Admin\UnitController;
use App\Http\Controllers\Api\V1\Admin\AdminComplaintController;
use App\Http\Controllers\Api\V1\Admin\AdminVerificationController;
use App\Http\Controllers\Api\V1\Field\FieldAssignmentController;
use App\Http\Controllers\Api\V1\Unit\UnitComplaintController;
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

        // Pengaduan milik masyarakat
        Route::apiResource('complaints', ComplaintController::class)
            ->only(['index', 'store', 'show']);

        // Admin utama
        Route::prefix('admin')
            ->middleware('main.admin')
            ->group(function () {

                // Manajemen instansi
                Route::apiResource('instansi', InstansiController::class);

                // Manajemen unit
                Route::apiResource('units', UnitController::class);

                // Pengelolaan pengaduan oleh Admin Utama
                Route::get('/complaints', [
                    AdminComplaintController::class,
                    'index',
                ]);

                Route::get('/complaints/{id}', [
                    AdminComplaintController::class,
                    'show',
                ]);

                Route::post('/complaints/{id}/delegate', [
                    AdminComplaintController::class,
                    'delegate',
                ]);

                Route::post(
                    '/complaints/{id}/reject',
                    [AdminComplaintController::class, 'reject']
                );

                // Pengelolaan verifikasi pengaduan oleh Admin Utama
                Route::get('/verifications', [
                    AdminVerificationController::class,
                    'index',
                ]);

                Route::post('/complaints/{id}/verify', [
                    AdminVerificationController::class,
                    'verify',
                ]);
            });

        // Admin Unit
        Route::prefix('unit')->group(function () {
            Route::get('/complaints', [
                UnitComplaintController::class,
                'index',
            ]);

            Route::get('/complaints/{id}', [
                UnitComplaintController::class,
                'show',
            ]);

            // Menugaskan pengaduan kepada Field Officer
            Route::post('/complaints/{id}/assign', [
                UnitComplaintController::class,
                'assignFieldOfficer',
            ]);

            Route::post('/complaints/{id}/responses', [
                UnitComplaintController::class,
                'storeResponse',
            ]);

            Route::post('/assignments/{id}/review', [
                UnitComplaintController::class,
                'reviewAssignment',
            ]);
        });

        // Field Officer
        Route::prefix('field')->group(function () {
            Route::get('/assignments', [
                FieldAssignmentController::class,
                'index',
            ]);

            Route::get('/assignments/{id}', [
                FieldAssignmentController::class,
                'show',
            ]);

            Route::post('/assignments/{id}/submit', [
                FieldAssignmentController::class,
                'submit',
            ]);
        });
    });
});
