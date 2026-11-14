<?php

namespace App\Modules\Posyandu\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Posyandu\Models\User;
use App\Modules\Posyandu\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->authService->login(
            $request->phone,
            $request->password
        );

        return response()->json([
            'status' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'user' => $result['user'],
                'token' => $result['token'],
                'token_type' => 'Bearer',
            ],
        ]);
    }

    /**
     * Meminta OTP.
     */
    public function requestOtp(Request $request)
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $result = $this->authService->requestOtp(
            $validated['phone']
        );

        return response()->json($result);
    }

    /**
     * Verifikasi OTP dan login.
     */
    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'otp' => ['required', 'digits:6'],
        ]);

        $result = $this->authService->verifyOtp(
            $validated['phone'],
            $validated['otp']
        );

        return response()->json($result);
    }

    /**
     * Profil user yang sedang login.
     */
    public function me(Request $request)
    {
        $user = $request->user('posyandu');

        return response()->json([
            'success' => true,
            'data' => $this->authService->me($user),
        ]);
    }

    /**
     * Logout.
     */
    public function logout(Request $request)
    {
        $user = $request->user('posyandu');

        $this->authService->logout($user);

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }
}
