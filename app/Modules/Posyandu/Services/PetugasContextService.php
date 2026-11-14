<?php

namespace App\Modules\Posyandu\Services;

use App\Modules\Posyandu\Models\User;
use App\Modules\Posyandu\Models\PetugasPosyandu;
use App\Modules\Posyandu\Models\PosyanduPos;
use Illuminate\Auth\Access\AuthorizationException;

class PetugasContextService
{
    /**
     * Mengambil penugasan Admin yang sedang aktif.
     */
    public function adminAssignment(User $user): PetugasPosyandu
    {
        $assignment = $user->petugasPosyandu()
            ->with('posyandu')
            ->where('status', true)
            ->where('peran', 'admin')
            ->first();

        if (!$assignment) {
            throw new AuthorizationException(
                'Anda tidak memiliki penugasan sebagai Admin Posyandu.'
            );
        }

        return $assignment;
    }

    /**
     * Mengambil ID Posyandu milik Admin.
     */
    public function adminPosyanduId(User $user): int
    {
        return (int) $this->adminAssignment($user)->posyandu_id;
    }

    /**
     * Memastikan petugas aktif pada Posyandu tertentu.
     */
    public function petugasAssignment(
        User $user,
        int $posyanduId
    ): PetugasPosyandu {
        $assignment = $user->petugasPosyandu()
            ->where('posyandu_id', $posyanduId)
            ->where('status', true)
            ->first();

        if (!$assignment) {
            throw new AuthorizationException(
                'Anda tidak memiliki penugasan pada Posyandu ini.'
            );
        }

        return $assignment;
    }

    /**
     * Memastikan petugas berhak mengakses pos tertentu.
     *
     * Admin dapat mengakses semua pos aktif di Posyandunya.
     * Kader hanya dapat mengakses pos yang ditugaskan kepadanya.
     */
    public function authorizePosPemeriksaan(
        User $user,
        int $posyanduId,
        int $posyanduPosId
    ): PetugasPosyandu {
        $assignment = $this->petugasAssignment(
            $user,
            $posyanduId
        );

        // Pastikan konfigurasi pos benar-benar milik Posyandu ini.
        $pos = PosyanduPos::whereKey($posyanduPosId)
            ->where('posyandu_id', $posyanduId)
            ->where('status', true)
            ->first();

        if (!$pos) {
            throw new AuthorizationException(
                'Pos pemeriksaan tidak ditemukan atau tidak aktif.'
            );
        }

        // Admin dapat mengakses seluruh pos aktif.
        if ($assignment->peran === 'admin') {
            return $assignment;
        }

        // Kader hanya dapat mengakses pos yang ditugaskan.
        $hasAccess = $assignment->penugasanPos()
            ->where('posyandu_pos_id', $posyanduPosId)
            ->where('status', true)
            ->exists();

        if (!$hasAccess) {
            throw new AuthorizationException(
                'Anda tidak memiliki penugasan pada pos pemeriksaan ini.'
            );
        }

        return $assignment;
    }
}
