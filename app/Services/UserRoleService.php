<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UserRoleService
{
    /**
     * Memberikan role masyarakat kepada pengguna baru.
     * Tidak mengubah role pengguna yang sudah ada.
     */
    public function assignDefaultRole(User $user): void
    {
        $db = DB::connection('pengaduan');

        $role = $db->table('roles')
            ->where('kode', 'masyarakat')
            ->first();

        if (! $role) {
            throw new RuntimeException(
                'Role masyarakat belum tersedia. Jalankan RoleSeeder.'
            );
        }

        $alreadyAssigned = $db->table('user_roles')
            ->where('user_id', $user->getKey())
            ->where('role_id', $role->id)
            ->whereNull('unit_id')
            ->exists();

        if (! $alreadyAssigned) {
            $db->table('user_roles')->insert([
                'user_id' => $user->getKey(),
                'role_id' => $role->id,
                'unit_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}