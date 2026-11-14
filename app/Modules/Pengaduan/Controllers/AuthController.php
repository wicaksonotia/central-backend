<?php

namespace App\Modules\Pengaduan\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pengaduan\Models\User;
use App\Modules\Pengaduan\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Request OTP.
     */
    public function requestOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $result = $this->authService->requestOtp(
            $validated['phone']
        );

        if (!$result['success']) {
            return response()->json([
                'status' => false,
                'message' => $result['message'],
            ], $result['code']);
        }

        return response()->json([
            'status' => true,
            'message' => 'OTP berhasil dibuat.',
            'data' => [
                'phone' => $result['phone'],
                'expires_in' => $result['expires_in'],
                'otp' => $result['otp'],
            ],
        ]);
    }

    /**
     * Verifikasi OTP.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'otp' => ['required', 'digits:6'],
        ]);

        $result = $this->authService->verifyOtp(
            $validated['phone'],
            $validated['otp']
        );

        if (!$result['success']) {
            return response()->json([
                'status' => false,
                'message' => $result['message'],
            ], $result['code']);
        }

        return response()->json([
            'status' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'user' => $result['user'],
                'token' => $result['token'],
                'profile_completed' => $result['profile_completed'],
            ],
        ]);
    }

    /**
     * Profil pengguna.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'status' => true,
            'message' => 'Data pengguna berhasil diambil.',
            'data' => $this->authService->me($user),
        ]);
    }

    /**
     * Update profil.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                'unique:users,email,' . $user->id,
            ],
        ]);

        $updatedUser = $this->authService->updateProfile(
            $user,
            $validated
        );

        return response()->json([
            'status' => true,
            'message' => 'Profile berhasil diperbarui.',
            'data' => [
                'user' => $updatedUser,
            ],
        ]);
    }

    /**
     * Logout.
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->authService->logout($user);

        return response()->json([
            'status' => true,
            'message' => 'Logout berhasil.',
        ]);
    }
}
