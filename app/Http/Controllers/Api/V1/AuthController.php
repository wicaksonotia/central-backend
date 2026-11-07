<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PhoneOtp;
use App\Models\User;
use App\Models\UserRole;
use App\Services\UserRoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    protected UserRoleService $userRoleService;

    public function __construct(UserRoleService $userRoleService)
    {
        $this->userRoleService = $userRoleService;
    }

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

        $otp = (string) random_int(100000, 999999);

        $connection = (new PhoneOtp())->getConnection();

        $connection->transaction(function () use (
            $connection,
            $phone,
            $otp
        ) {
            // Mengunci proses penerbitan OTP untuk nomor HP ini.
            // PostgreSQL: lock otomatis dilepas saat transaksi selesai.
            $connection->select(
                'SELECT pg_advisory_xact_lock(hashtext(?))',
                [$phone]
            );

            // Batalkan seluruh OTP sebelumnya yang belum diverifikasi,
            // termasuk OTP yang sudah kedaluwarsa.
            PhoneOtp::query()
                ->where('phone', $phone)
                ->whereNull('verified_at')
                ->update([
                    'verified_at' => now(),
                ]);

            // Buat OTP baru.
            PhoneOtp::create([
                'phone' => $phone,
                'otp_hash' => Hash::make($otp),
                'expires_at' => now()->addMinutes(5),
                'attempts' => 0,
            ]);
        });

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
            'phone' => ['required', 'string', 'max:20'],
            'otp' => ['required', 'digits:6'],
        ]);

        $phone = $this->normalizePhone($validated['phone']);

        $connection = (new PhoneOtp())->getConnection();

        $result = $connection->transaction(function () use (
            $connection,
            $phone,
            $validated
        ) {
            // Sinkronisasi request OTP dan verifikasi OTP
            // berdasarkan nomor HP yang sama.
            $connection->select(
                'SELECT pg_advisory_xact_lock(hashtext(?))',
                [$phone]
            );

            // Ambil OTP terbaru yang belum diverifikasi.
            $otpRecord = PhoneOtp::query()
                ->where('phone', $phone)
                ->whereNull('verified_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (!$otpRecord) {
                return [
                    'error' => 'OTP tidak ditemukan. Silakan minta OTP baru.',
                    'code' => 422,
                ];
            }

            if ($otpRecord->expires_at->isPast()) {
                return [
                    'error' => 'OTP sudah kedaluwarsa. Silakan minta OTP baru.',
                    'code' => 422,
                ];
            }

            if ($otpRecord->attempts >= 5) {
                return [
                    'error' => 'Batas percobaan OTP sudah habis. Silakan minta OTP baru.',
                    'code' => 422,
                ];
            }

            // Tambah jumlah percobaan sebelum memeriksa OTP.
            $otpRecord->increment('attempts');

            if (!Hash::check($validated['otp'], $otpRecord->otp_hash)) {
                return [
                    'error' => 'OTP tidak valid.',
                    'code' => 422,
                ];
            }

            // Tandai OTP berhasil diverifikasi.
            $otpRecord->update([
                'verified_at' => now(),
            ]);

            // Cari user berdasarkan nomor HP.
            $user = User::query()
                ->where('phone', $phone)
                ->first();

            // Buat user jika belum terdaftar.
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

            // Pastikan user memiliki tepat satu role aktif.
            // Jika belum memiliki role aktif, berikan role masyarakat.
            $this->userRoleService->assignDefaultRole($user);

            return [
                'user' => $user,
            ];
        });

        // Jika verifikasi gagal, kembalikan pesan error.
        if (isset($result['error'])) {
            return response()->json([
                'status' => false,
                'message' => $result['error'],
            ], $result['code']);
        }

        $user = $result['user'];

        // Buat token setelah seluruh transaksi berhasil.
        $token = $user->createToken('flutter-app')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'user' => $user->fresh(),
                'token' => $token,
                'profile_completed' => $user->profile_completed_at !== null,
            ],
        ]);
    }

    /**
     * Normalisasi dan validasi nomor HP Indonesia.
     * Input	Hasil
     * 081234567890	081234567890
     * +6281234567890	081234567890
     * 6281234567890	081234567890
     * 0812-3456-7890	081234567890
     * 123456	Ditolak
     */
    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[\s\-\(\)]/', '', trim($phone));

        if (Str::startsWith($phone, '+62')) {
            $phone = '0' . substr($phone, 3);
        } elseif (Str::startsWith($phone, '62')) {
            $phone = '0' . substr($phone, 2);
        }

        if (!preg_match('/^08[1-9][0-9]{7,11}$/', $phone)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'phone' => ['Format nomor HP Indonesia tidak valid.'],
            ]);
        }

        return $phone;
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        $userRoles = UserRole::query()
            ->active()
            ->where('user_id', $user->id)
            ->with([
                'role:id,nama,kode',
                'unit:id,nama,instansi_id',
                'unit.instansi:id,nama',
            ])
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Data pengguna berhasil diambil.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $user->avatar,
                    'profile_completed_at' => $user->profile_completed_at,

                    'roles' => $userRoles->map(function ($userRole) {
                        $role = $userRole->role;
                        $unit = $userRole->unit;

                        return [
                            'id' => $role?->id,
                            'nama' => $role?->nama,
                            'kode' => $role?->kode,
                            'unit_id' => $userRole->unit_id,
                            'unit_nama' => $unit?->nama,
                            'instansi_id' => $unit?->instansi_id,
                            'instansi_nama' => $unit?->instansi?->nama,
                        ];
                    })->values(),
                ],
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

    /**
     * Logout pengguna.
     * Menghapus token perangkat yang sedang digunakan.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()
            ->currentAccessToken()
            ?->delete();

        return response()->json([
            'status' => true,
            'message' => 'Logout berhasil.',
        ]);
    }
}
