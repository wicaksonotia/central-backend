<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use RuntimeException;

class UserRoleService
{
    /**
     * Memberikan role masyarakat kepada pengguna baru.
     * Tidak menambahkan role jika user sudah memiliki role aktif.
     */
    public function assignDefaultRole(User $user): void
    {
        $user->getConnection()->transaction(function () use ($user) {

            // Mengunci data user agar tidak terjadi pemberian role
            // aktif secara bersamaan.
            User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // Periksa apakah user sudah memiliki role aktif.
            $alreadyAssigned = UserRole::active()
                ->where('user_id', $user->getKey())
                ->exists();

            if ($alreadyAssigned) {
                return;
            }

            $role = Role::query()
                ->where('kode', 'masyarakat')
                ->first();

            if (!$role) {
                throw new RuntimeException(
                    'Role masyarakat belum tersedia. Jalankan RoleSeeder.'
                );
            }

            UserRole::create([
                'user_id' => $user->getKey(),
                'role_id' => $role->id,
                'unit_id' => null,
                'is_active' => true,
                'deactivated_at' => null,
            ]);
        });
    }
}