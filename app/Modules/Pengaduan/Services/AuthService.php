<?php

namespace App\Modules\Pengaduan\Services;

use App\Modules\Pengaduan\Models\PhoneOtp;
use App\Modules\Pengaduan\Models\User;
use App\Modules\Pengaduan\Models\UserRole;
use App\Modules\Shared\Models\PersonalAccessToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        protected UserRoleService $userRoleService
    ) {}

    /**
     * Request OTP untuk login/register.
     */
    public function requestOtp(string $inputPhone): array
    {
        $phone = $this->normalizePhone($inputPhone);

        $rateLimitKey = 'otp-request:' . $phone;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return [
                'success' => false,
                'code' => 429,
                'message' => "Terlalu banyak permintaan OTP. Coba lagi dalam {$seconds} detik.",
            ];
        }

        RateLimiter::hit($rateLimitKey, 60);

        $otp = (string) random_int(100000, 999999);

        $connection = (new PhoneOtp())->getConnection();

        $connection->transaction(function () use (
            $connection,
            $phone,
            $otp
        ) {
            $connection->select(
                'SELECT pg_advisory_xact_lock(hashtext(?))',
                [$phone]
            );

            PhoneOtp::query()
                ->where('phone', $phone)
                ->whereNull('verified_at')
                ->update([
                    'verified_at' => now(),
                ]);

            PhoneOtp::create([
                'phone' => $phone,
                'otp_hash' => Hash::make($otp),
                'expires_at' => now()->addMinutes(5),
                'attempts' => 0,
            ]);
        });

        return [
            'success' => true,
            'phone' => $phone,
            'expires_in' => 300,
            'otp' => app()->environment('local') ? $otp : null,
        ];
    }

    /**
     * Verifikasi OTP dan login/register pengguna.
     */
    public function verifyOtp(string $inputPhone, string $otp): array
    {
        $phone = $this->normalizePhone($inputPhone);

        $connection = (new PhoneOtp())->getConnection();

        $result = $connection->transaction(function () use (
            $connection,
            $phone,
            $otp
        ) {
            $connection->select(
                'SELECT pg_advisory_xact_lock(hashtext(?))',
                [$phone]
            );

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

            $otpRecord->increment('attempts');

            if (!Hash::check($otp, $otpRecord->otp_hash)) {
                return [
                    'error' => 'OTP tidak valid.',
                    'code' => 422,
                ];
            }

            $otpRecord->update([
                'verified_at' => now(),
            ]);

            $user = User::query()
                ->where('phone', $phone)
                ->first();

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

            $this->userRoleService->assignDefaultRole($user);

            return [
                'user' => $user,
            ];
        });

        if (isset($result['error'])) {
            return [
                'success' => false,
                'code' => $result['code'],
                'message' => $result['error'],
            ];
        }

        $user = $result['user'];

        // Token dibuat setelah transaksi berhasil.
        $token = $user->createToken('flutter-app')->plainTextToken;

        return [
            'success' => true,
            'user' => $user->fresh(),
            'token' => $token,
            'profile_completed' => $user->profile_completed_at !== null,
        ];
    }

    /**
     * Mengambil profil dan role aktif pengguna.
     */
    public function me(User $user): array
    {
        $userRoles = UserRole::query()
            ->active()
            ->where('user_id', $user->id)
            ->with([
                'role:id,nama,kode',
                'unit:id,nama,instansi_id',
                'unit.instansi:id,nama',
            ])
            ->get();

        return [
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
        ];
    }

    /**
     * Memperbarui profil pengguna.
     */
    public function updateProfile(User $user, array $data): User
    {
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'profile_completed_at' => now(),
        ]);

        return $user->fresh();
    }

    /**
     * Logout hanya token perangkat yang sedang digunakan.
     */
    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }

    /**
     * Normalisasi dan validasi nomor HP Indonesia.
     */
    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace(
            '/[\s\-\(\)]/',
            '',
            trim($phone)
        );

        if (Str::startsWith($phone, '+62')) {
            $phone = '0' . substr($phone, 3);
        } elseif (Str::startsWith($phone, '62')) {
            $phone = '0' . substr($phone, 2);
        }

        if (!preg_match('/^08[1-9][0-9]{7,11}$/', $phone)) {
            throw ValidationException::withMessages([
                'phone' => ['Format nomor HP Indonesia tidak valid.'],
            ]);
        }

        return $phone;
    }
}
