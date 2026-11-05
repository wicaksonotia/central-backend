<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $userRoles = [
            [4, 1, null],
            [5, 2, 2],
            [6, 3, 2],
            [8, 2, 1],
            [9, 3, 1],
            [11, 3, 6],
            [19, 2, 3],
            [20, 2, 4],
            [21, 2, 5],
            [22, 2, 6],
            [23, 3, 3],
            [24, 3, 4],
            [25, 3, 5],
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
