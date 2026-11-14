<?php

namespace App\Modules\Posyandu\Services;

use App\Modules\Posyandu\Models\User;
use App\Modules\Posyandu\Models\PosyanduPos;
use App\Modules\Posyandu\Models\PetugasPosyandu;
use App\Modules\Posyandu\Models\PetugasPosyanduPos;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(
        protected PetugasContextService $petugasContext
    ) {}

    /**
     * Mengambil konfigurasi pos aktif milik Posyandu.
     */
    private function getPosyanduPos(
        int $posyanduId,
        array $posIds
    ): Collection {
        $posIds = array_values(array_unique(
            array_map('intval', $posIds)
        ));

        $posList = PosyanduPos::query()
            ->where('posyandu_id', $posyanduId)
            ->where('status', true)
            ->whereIn('id', $posIds)
            ->get();

        if ($posList->count() !== count($posIds)) {
            throw ValidationException::withMessages([
                'posyandu_pos_ids' =>
                    'Ada pos pemeriksaan yang tidak valid, tidak aktif, atau bukan milik Posyandu Anda.',
            ]);
        }

        return $posList;
    }

    /**
     * Menyimpan penugasan pos kader.
     */
    private function simpanPenugasanPos(
        PetugasPosyandu $petugas,
        Collection $posList
    ): void {
        $posIds = $posList->pluck('id')->all();

        // Nonaktifkan penugasan yang tidak dipilih.
        $query = PetugasPosyanduPos::query()
            ->where('petugas_posyandu_id', $petugas->id);

        if (empty($posIds)) {
            $query->update(['status' => false]);
        } else {
            $query->whereNotIn('posyandu_pos_id', $posIds)
                ->update(['status' => false]);
        }

        // Aktifkan kembali atau buat penugasan.
        foreach ($posList as $pos) {
            PetugasPosyanduPos::updateOrCreate(
                [
                    'petugas_posyandu_id' => $petugas->id,
                    'posyandu_pos_id' => $pos->id,
                ],
                [
                    'status' => true,
                ]
            );
        }
    }

    /**
     * Admin membuat akun kader.
     */
    public function createKader(
        User $admin,
        array $data
    ): User {
        $posyanduId = $this->petugasContext
            ->adminPosyanduId($admin);

        $phone = $this->normalizePhone($data['phone']);

        return DB::connection('posyandu')->transaction(
            function () use ($data, $phone, $posyanduId) {
                if (User::where('phone', $phone)->exists()) {
                    throw ValidationException::withMessages([
                        'phone' => 'Nomor HP sudah terdaftar.',
                    ]);
                }

                if (!empty($data['email'])) {
                    if (User::where('email', $data['email'])->exists()) {
                        throw ValidationException::withMessages([
                            'email' => 'Email sudah terdaftar.',
                        ]);
                    }
                }

                $posList = $this->getPosyanduPos(
                    $posyanduId,
                    $data['posyandu_pos_ids'] ?? []
                );

                $user = User::create([
                    'name' => $data['name'],
                    'phone' => $phone,
                    'email' => $data['email'] ?? null,
                    'password' => null,
                    'is_active' => true,
                ]);

                $petugas = PetugasPosyandu::create([
                    'posyandu_id' => $posyanduId,
                    'user_id' => $user->id,
                    'peran' => 'kader',
                    'status' => true,
                ]);

                $this->simpanPenugasanPos($petugas, $posList);

                return $user->load([
                    'petugasPosyandu.posyandu',
                    'petugasPosyandu.penugasanPos.posyanduPos.posPemeriksaan',
                ]);
            }
        );
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

    /**
     * Memperbarui data kader.
     */
    public function updateKader(
        User $admin,
        int $userId,
        array $data
    ): User {
        $posyanduId = $this->petugasContext
            ->adminPosyanduId($admin);

        return DB::connection('posyandu')->transaction(
            function () use ($userId, $data, $posyanduId) {
                $petugas = PetugasPosyandu::query()
                    ->where('posyandu_id', $posyanduId)
                    ->where('peran', 'kader')
                    ->where('status', true)
                    ->where('user_id', $userId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $user = User::query()
                    ->whereKey($userId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $phone = isset($data['phone'])
                    ? $this->normalizePhone($data['phone'])
                    : $user->phone;

                $phoneExists = User::query()
                    ->where('phone', $phone)
                    ->where('id', '!=', $user->id)
                    ->exists();

                if ($phoneExists) {
                    throw ValidationException::withMessages([
                        'phone' => 'Nomor HP sudah digunakan.',
                    ]);
                }

                if (array_key_exists('email', $data) && $data['email']) {
                    $emailExists = User::query()
                        ->where('email', $data['email'])
                        ->where('id', '!=', $user->id)
                        ->exists();

                    if ($emailExists) {
                        throw ValidationException::withMessages([
                            'email' => 'Email sudah digunakan.',
                        ]);
                    }
                }

                $user->update([
                    'name' => $data['name'] ?? $user->name,
                    'phone' => $phone,
                    'email' => array_key_exists('email', $data)
                        ? $data['email']
                        : $user->email,
                ]);

                if (array_key_exists('posyandu_pos_ids', $data)) {
                    $posList = $this->getPosyanduPos(
                        $posyanduId,
                        $data['posyandu_pos_ids'] ?? []
                    );

                    $this->simpanPenugasanPos($petugas, $posList);
                }

                return $user->load(
                    'petugasPosyandu.penugasanPos.posyanduPos.posPemeriksaan'
                );
            }
        );
    }

    /**
     * Menonaktifkan kader pada Posyandu Admin.
     */
    public function deactivateKader(
        User $admin,
        int $userId
    ): void {
        $posyanduId = $this->petugasContext
            ->adminPosyanduId($admin);

        DB::connection('posyandu')->transaction(
            function () use ($userId, $posyanduId) {
                $petugas = PetugasPosyandu::query()
                    ->where('posyandu_id', $posyanduId)
                    ->where('user_id', $userId)
                    ->where('peran', 'kader')
                    ->where('status', true)
                    ->lockForUpdate()
                    ->firstOrFail();

                $petugas->update([
                    'status' => false,
                ]);

                PetugasPosyanduPos::query()
                    ->where('petugas_posyandu_id', $petugas->id)
                    ->update([
                        'status' => false,
                    ]);

                $masihAktif = PetugasPosyandu::query()
                    ->where('user_id', $userId)
                    ->where('status', true)
                    ->exists();

                if (!$masihAktif) {
                    $user = User::query()
                        ->whereKey($userId)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $user->update([
                        'is_active' => false,
                    ]);

                    $user->tokens()->delete();
                }
            }
        );
    }

    /**
     * Mengaktifkan kembali kader pada Posyandu Admin.
     */
    public function activateKader(
        User $admin,
        int $userId,
        array $data = []
    ): User {
        $posyanduId = $this->petugasContext
            ->adminPosyanduId($admin);

        return DB::connection('posyandu')->transaction(
            function () use ($userId, $posyanduId, $data) {
                $user = User::query()
                    ->whereKey($userId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $petugas = PetugasPosyandu::query()
                    ->where('posyandu_id', $posyanduId)
                    ->where('user_id', $userId)
                    ->where('peran', 'kader')
                    ->where('status', false)
                    ->lockForUpdate()
                    ->firstOrFail();

                $user->update([
                    'is_active' => true,
                ]);

                $petugas->update([
                    'status' => true,
                ]);

                if (array_key_exists('posyandu_pos_ids', $data)) {
                    $posList = $this->getPosyanduPos(
                        $posyanduId,
                        $data['posyandu_pos_ids'] ?? []
                    );

                    $this->simpanPenugasanPos($petugas, $posList);
                }

                return $user->load(
                    'petugasPosyandu.penugasanPos.posyanduPos.posPemeriksaan'
                );
            }
        );
    }
}
