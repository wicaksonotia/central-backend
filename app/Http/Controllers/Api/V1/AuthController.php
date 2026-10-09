<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PhoneOtp;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Request OTP untuk login/register menggunakan nomor HP.
     */
    public function requestOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => [
                'required',
                'string',
                'max:20',
            ],
        ]);

        $phone = $this->normalizePhone($validated['phone']);

        $rateLimitKey = 'otp-request:' . $phone;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return response()->json([
                'status' => false,
                'message' => "Terlalu banyak permintaan OTP. Coba lagi dalam {$seconds} detik.",
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, 60);

        // Batalkan OTP aktif sebelumnya.
        PhoneOtp::where('phone', $phone)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->update([
                'verified_at' => now(),
            ]);

        $otp = (string) random_int(100000, 999999);

        PhoneOtp::create([
            'phone' => $phone,
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0,
        ]);

        /*
         * DEVELOPMENT ONLY
         *
         * Nanti bagian ini diganti dengan SMS/WhatsApp provider.
         */
        return response()->json([
            'status' => true,
            'message' => 'OTP berhasil dibuat.',
            'data' => [
                'phone' => $phone,
                'expires_in' => 300,
                'otp' => app()->environment('local')
                    ? $otp
                    : null,
            ],
        ]);
    }

    /**
     * Verifikasi OTP.
     */
    public function verifyOtp(Request $request): JsonResponse
{
    $validated = $request->validate([
        'phone' => [
            'required',
            'string',
            'max:20',
        ],
        'otp' => [
            'required',
            'digits:6',
        ],
    ]);

    $phone = $this->normalizePhone($validated['phone']);

    $otpRecord = PhoneOtp::where('phone', $phone)
        ->whereNull('verified_at')
        ->latest('id')
        ->first();

    if (!$otpRecord) {
        return response()->json([
            'status' => false,
            'message' => 'OTP tidak ditemukan. Silakan minta OTP baru.',
        ], 422);
    }

    if ($otpRecord->expires_at->isPast()) {
        return response()->json([
            'status' => false,
            'message' => 'OTP sudah kedaluwarsa. Silakan minta OTP baru.',
        ], 422);
    }

    if ($otpRecord->attempts >= 5) {
        return response()->json([
            'status' => false,
            'message' => 'Batas percobaan OTP sudah habis. Silakan minta OTP baru.',
        ], 422);
    }

    $otpRecord->increment('attempts');

    if (!Hash::check($validated['otp'], $otpRecord->otp_hash)) {
        return response()->json([
            'status' => false,
            'message' => 'OTP tidak valid.',
        ], 422);
    }

    $otpRecord->update([
        'verified_at' => now(),
    ]);

    $user = User::where('phone', $phone)->first();

    if (!$user) {
        $user = User::create([
            'name' => 'Pengguna',
            'phone' => $phone,
            'phone_verified_at' => now(),
        ]);
    } else {
        $user->update([
            'phone_verified_at' => now(),
        ]);
    }

    $token = $user->createToken('flutter-app')->plainTextToken;

    return response()->json([
        'status' => true,
        'message' => 'Login berhasil.',
        'data' => [
            'user' => $user,
            'token' => $token,
            'profile_completed' => $user->profile_completed_at !== null,
        ],
    ]);
}

    /**
     * Normalisasi nomor HP Indonesia.
     */
    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[\s\-\(\)]/', '', trim($phone));

        if (Str::startsWith($phone, '+62')) {
            return '0' . substr($phone, 3);
        }

        if (Str::startsWith($phone, '62')) {
            return '0' . substr($phone, 2);
        }

        return $phone;
    }

    public function me(Request $request)
{
    return response()->json([
        'status' => true,
        'message' => 'Data pengguna berhasil diambil.',
        'data' => [
            'user' => $request->user(),
        ],
    ]);
}

public function updateProfile(Request $request)
{
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

    $user->update([
        'name' => $validated['name'],
        'email' => $validated['email'] ?? null,
        'profile_completed_at' => now(),
    ]);

    return response()->json([
        'status' => true,
        'message' => 'Profile berhasil diperbarui.',
        'data' => [
            'user' => $user->fresh(),
        ],
    ]);
}

public function logout(Request $request)
{
    $request->user()->currentAccessToken()?->delete();

    return response()->json([
        'status' => true,
        'message' => 'Logout berhasil.',
    ]);
}
}