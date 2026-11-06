
<?php

use App\Http\Controllers\Api\V1\Admin\AdminComplaintCategoryController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ComplaintController;
use App\Http\Controllers\Api\V1\Admin\InstansiController;
use App\Http\Controllers\Api\V1\Admin\UnitController;
use App\Http\Controllers\Api\V1\Admin\AdminComplaintController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\Admin\AdminVerificationController;
use App\Http\Controllers\Api\V1\BeritaPengumumanController;
use App\Http\Controllers\Api\V1\Field\FieldAssignmentController;
use App\Http\Controllers\Api\V1\Unit\UnitComplaintController;
use App\Http\Controllers\Api\V1\Unit\UnitFieldOfficerController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::get('/ping', function () {
        return response()->json([
            'success' => true,
            'message' => 'Central Backend API is running',
            'timestamp' => now()->toDateTimeString(),
        ]);
    });
    // Autentikasi
    Route::prefix('auth')->group(function () {
        Route::post('/phone/request-otp', [AuthController::class, 'requestOtp']);
        Route::post('/phone/verify-otp', [AuthController::class, 'verifyOtp']);
    });

    // =====================================================
    // BERITA & PENGUMUMAN - PUBLIC
    // =====================================================

    Route::get('/berita-pengumuman', [
        BeritaPengumumanController::class,
        'index',
    ]);

    Route::get('/berita-pengumuman/{id}', [
        BeritaPengumumanController::class,
        'show',
    ])->whereNumber('id');

    // Pengguna yang sudah login
    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/me', [AuthController::class, 'updateProfile']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);


        // Pengaduan milik masyarakat
        Route::apiResource('complaints', ComplaintController::class)
            ->only(['index', 'store', 'show']);
        Route::get('/masyarakat/dashboard', [
            ComplaintController::class,
            'dashboard',
        ]);

        // Admin utama
        Route::prefix('admin')
            ->middleware('main.admin')
            ->group(function () {

                // Manajemen instansi
                Route::apiResource('instansi', InstansiController::class);

                // Manajemen unit
                Route::apiResource('units', UnitController::class);

                Route::get('/dashboard', [
                    AdminComplaintController::class,
                    'dashboard',
                ]);

                // Pengelolaan pengaduan oleh Admin Utama
                Route::get('/complaints', [
                    AdminComplaintController::class,
                    'index',
                ]);

                Route::get('/complaints/{id}', [
                    AdminComplaintController::class,
                    'show',
                ]);

                Route::patch(
                    '/complaints/{id}/category',
                    [AdminComplaintController::class, 'setCategory']
                )->whereNumber('id');

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

                // Category management
                Route::get(
                    '/complaint-categories',
                    [AdminComplaintCategoryController::class, 'index']
                );

                Route::post(
                    '/complaint-categories',
                    [AdminComplaintCategoryController::class, 'store']
                );

                Route::get(
                    '/complaint-categories/{id}',
                    [AdminComplaintCategoryController::class, 'show']
                )->whereNumber('id');

                Route::put(
                    '/complaint-categories/{id}',
                    [AdminComplaintCategoryController::class, 'update']
                )->whereNumber('id');

                Route::delete(
                    '/complaint-categories/{id}',
                    [AdminComplaintCategoryController::class, 'destroy']
                )->whereNumber('id');

                // management of Admin Unit and Field Officer users
                Route::post('/users/unit-admin/bulk', [
                    AdminUserController::class,
                    'storeUnitAdmins',
                ]);

                Route::post('/users/field-officer/bulk', [
                    AdminUserController::class,
                    'storeFieldOfficers',
                ]);
            });


        // Admin Unit
        Route::prefix('unit')->group(function () {
            Route::get('/dashboard', [
                UnitComplaintController::class,
                'dashboard',
            ]);

            Route::get('/complaints', [
                UnitComplaintController::class,
                'index',
            ]);

            Route::get('/complaints/{id}', [
                UnitComplaintController::class,
                'show',
            ]);

            // Mengembalikan pengaduan ke Admin Utama
            Route::post('/complaints/{id}/return', [
                UnitComplaintController::class,
                'returnComplaint',
            ])->whereNumber('id');

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

            Route::get('/field-officers', [
                UnitComplaintController::class,
                'fieldOfficers',
            ]);

            // Manajemen Field Officer oleh Admin Unit
            Route::post('/field-officers/bulk', [
                UnitFieldOfficerController::class,
                'store',
            ]);
        });

        // =====================================================
        // BERITA & PENGUMUMAN - ADMIN UTAMA & ADMIN UNIT
        // =====================================================

        Route::prefix('admin/berita-pengumuman')
            ->controller(BeritaPengumumanController::class)
            ->group(function () {

                Route::get('/', 'adminIndex');

                Route::post('/', 'store');

                Route::post('/{id}', 'update')
                    ->whereNumber('id');

                Route::delete('/{id}', 'destroy')
                    ->whereNumber('id');
            });


        // Field Officer
        Route::prefix('field')->group(function () {

            // Dashboard Field Officer
            Route::get('/dashboard', [
                FieldAssignmentController::class,
                'dashboard',
            ]);

            // Daftar penugasan
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
