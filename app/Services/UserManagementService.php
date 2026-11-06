<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class UserManagementService
{
    /**
     * Membuat beberapa user sekaligus beserta role dan unit-nya.
     */
    public function createBulk(
        string $roleCode,
        int $unitId,
        array $users
    ): Collection {
        return DB::connection('pengaduan')->transaction(function () use (
            $roleCode,
            $unitId,
            $users
        ) {
            $role = Role::where('kode', $roleCode)->firstOrFail();

            $createdUsers = collect();

            foreach ($users as $item) {
                $user = User::create([
                    'name' => trim($item['name']),
                    'phone' => trim($item['phone']),
                ]);

                UserRole::create([
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                    'unit_id' => $unitId,
                ]);

                $createdUsers->push([
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'role' => $role->kode,
                    'unit_id' => $unitId,
                ]);
            }

            return $createdUsers;
        });
    }
}