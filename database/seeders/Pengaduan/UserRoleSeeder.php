<?php

namespace Database\Seeders\Pengaduan;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $userRoles = [
            // ADMIN UNIT (2)
            [4, 2, 3],
            [5, 2, 4],
            [6, 3, 5],
            [7, 2, 6],
            [8, 2, 1],
            [9, 3, 2],
            // FIELD OFFICER (3)
            [10, 3, 3],
            [11, 3, 4],
            [12, 3, 5],
            [13, 3, 6],
            [14, 3, 1],
            [15, 3, 2],
        ];

        foreach ($userRoles as [$userId, $roleId, $unitId]) {
            DB::table('user_roles')->updateOrInsert(
                [
                    'user_id' => $userId,
                    'role_id' => $roleId,
                    'unit_id' => $unitId,
                ],
                [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
