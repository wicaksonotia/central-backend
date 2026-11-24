<?php

namespace App\Modules\Posyandu\Services;

use App\Modules\Posyandu\Models\PhoneOtp;
use App\Modules\Posyandu\Models\User;
use App\Modules\Shared\Models\PersonalAccessToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Login menggunakan nomor HP dan password.
     */
    public function login(string $phone, string $password): array
    {
        $phone = $this->normalizePhone($phone);

        $user = User::where('phone', $phone)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'phone' => 'Nomor HP atau password salah.',
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'phone' => 'Akun Anda sedang tidak aktif.',
            ]);
        }

        if (!$user->hasActiveAccess()) {
            throw ValidationException::withMessages([
                'phone' => 'Akun Anda belum memiliki penugasan aktif.',
            ]);
        }

        if (
            !$user->password ||
            !Hash::check($password, $user->password)
        ) {
            throw ValidationException::withMessages([
                'phone' => 'Nomor HP atau password salah.',
            ]);
        }

        $user->update([
            'last_login_at' => now(),
        ]);

        $token = $user->createToken('posyandu');

        return [
            'user' => $user->fresh(),
            'token' => $token->plainTextToken,
        ];
    }

    /**
     * Meminta OTP untuk akun Posyandu.
     */
    public function requestOtp(string $phone): array
    {
        $phone = $this->normalizePhone($phone);

        $user = User::where('phone', $phone)->first();

        if (!$user || !$user->is_active) {
            throw ValidationException::withMessages([
                'phone' => 'Nomor HP tidak terdaftar atau akun tidak aktif.',
            ]);
        }

        if (!$user->hasActiveAccess()) {
            throw ValidationException::withMessages([
                'phone' => 'Akun Anda belum memiliki penugasan aktif.',
            ]);
        }

        $key = 'posyandu-otp:' . $phone;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'phone' => 'Terlalu banyak permintaan OTP. Silakan coba lagi nanti.',
            ]);
        }

        RateLimiter::hit($key, 60);

        $otp = (string) random_int(100000, 999999);

        DB::connection('posyandu')->transaction(function () use ($phone, $otp) {
            // Hapus OTP sebelumnya yang belum diverifikasi.
            PhoneOtp::where('phone', $phone)
                ->whereNull('verified_at')
                ->delete();

            PhoneOtp::create([
                'phone' => $phone,
                'otp_hash' => Hash::make($otp),
                'expires_at' => now()->addMinutes(5),
                'attempts' => 0,
            ]);
        });

        return [
            'success' => true,
            'message' => 'OTP berhasil dibuat.',
            'otp' => app()->environment(['local', 'testing'])
                ? $otp
                : null,
        ];
    }

    /**
     * Verifikasi OTP dan menerbitkan Sanctum token.
     */
    public function verifyOtp(string $phone, string $otp): array
    {
        $phone = $this->normalizePhone($phone);

        $result = DB::connection('posyandu')->transaction(
            function () use ($phone, $otp) {

                $user = User::where('phone', $phone)->first();

                if (!$user || !$user->is_active) {
                    return ['status' => 'user_not_found'];
                }

                if (!$user->hasActiveAccess()) {
                    return ['status' => 'no_assignment'];
                }

                $otpRecord = PhoneOtp::where('phone', $phone)
                    ->whereNull('verified_at')
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                if (!$otpRecord) {
                    return ['status' => 'otp_not_found'];
                }

                if ($otpRecord->expires_at->isPast()) {
                    return ['status' => 'expired'];
                }

                if ($otpRecord->attempts >= 5) {
                    return ['status' => 'max_attempts'];
                }

                // Setiap percobaan verifikasi dihitung.
                $otpRecord->increment('attempts');

                if (!Hash::check($otp, $otpRecord->otp_hash)) {
                    return ['status' => 'invalid'];
                }

                $otpRecord->update([
                    'verified_at' => now(),
                ]);

                $user->update([
                    'phone_verified_at' => now(),
                    'last_login_at' => now(),
                ]);

                return [
                    'status' => 'success',
                    'user' => $user,
                ];
            }
        );

        if ($result['status'] !== 'success') {
            $messages = [
                'user_not_found' => 'Akun tidak ditemukan atau tidak aktif.',
                'no_assignment' => 'Akun Anda belum memiliki penugasan aktif.',
                'otp_not_found' => 'OTP tidak ditemukan. Silakan minta OTP baru.',
                'expired' => 'OTP sudah kedaluwarsa. Silakan minta OTP baru.',
                'max_attempts' => 'Batas percobaan OTP telah tercapai.',
                'invalid' => 'Kode OTP yang dimasukkan salah.',
            ];

            $message = $messages[$result['status']]
                ?? 'Verifikasi OTP gagal. Silakan coba lagi.';

            throw ValidationException::withMessages([
                'otp' => $message,
            ]);
        }

        $user = $result['user'];

        $token = $user->createToken('flutter-posyandu')->plainTextToken;

        RateLimiter::clear('posyandu-otp:' . $phone);

        return [
            'success' => true,
            'message' => 'Login berhasil.',
            'token' => $token,
            'user' => $this->me($user),
        ];
    }

    /**
     * Mengambil profil user yang sedang login.
     */
    public function me(User $user): array
    {
        $user->load([
            'puskesmasUserAktif.puskesmas.desaAktif',
            'petugasPosyanduAktif.posyandu',
        ]);

        $roles = collect();

        foreach ($user->puskesmasUserAktif as $assignment) {
            $roles->push($assignment->peran);
        }

        foreach ($user->petugasPosyanduAktif as $assignment) {
            $roles->push($assignment->peran);
        }

        $roles = $roles->unique()->values();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,

            // Role aktif yang dimiliki user.
            'roles' => $roles,

            // Diisi jika hanya ada satu jenis role aktif.
            'role' => $roles->count() === 1
                ? $roles->first()
                : null,

            'puskesmas' => $user->puskesmasUserAktif
                ->map(fn ($item) => [
                    'id' => $item->puskesmas?->id,
                    'nama' => $item->puskesmas?->nama_puskesmas,
                    'peran' => $item->peran,
                    'desa' => $item->puskesmas?->desaAktif
                        ->map(fn ($desa) => [
                            'id' => $desa->id,
                            'nama' => $desa->nama_desa,
                        ])->values(),
                ])->values(),

            'petugas_posyandu' => $user->petugasPosyanduAktif
                ->map(fn ($item) => [
                    'id' => $item->id,
                    'posyandu_id' => $item->posyandu_id,
                    'posyandu_nama' => $item->posyandu?->nama_posyandu,
                    'peran' => $item->peran,
                ])->values(),
        ];
    }

    /**
     * Logout dan menghapus token aktif.
     */
    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }

    /**
     * Normalisasi nomor HP Indonesia.
     */
    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[\s\-()]+/', '', $phone);

        if (str_starts_with($phone, '+62')) {
            $phone = '0' . substr($phone, 3);
        } elseif (str_starts_with($phone, '62')) {
            $phone = '0' . substr($phone, 2);
        }

        if (!preg_match('/^08[1-9][0-9]{7,11}$/', $phone)) {
            throw ValidationException::withMessages([
                'phone' => 'Format nomor HP tidak valid.',
            ]);
        }

        return $phone;
    }
}
