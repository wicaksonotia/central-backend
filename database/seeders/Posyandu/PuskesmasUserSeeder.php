<?php

namespace Database\Seeders\Posyandu;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PuskesmasUserSeeder extends Seeder
{
    public function run(): void
    {
        $connection = DB::connection('posyandu');

        $puskesmas = $connection->table('puskesmas')
            ->where('kode_puskesmas', 'PUSKESMAS-GAMPENGREJO')
            ->first();

        if (!$puskesmas) {
            throw new \RuntimeException(
                'Puskesmas Gampengrejo belum tersedia.'
            );
        }

        $userId = $connection->table('users')
            ->where('phone', '085755124535')
            ->value('id');

        if (!$userId) {
            $userId = $connection->table('users')->insertGetId([
                'name' => 'Admin Puskesmas Gampengrejo',
                'phone' => '085755124535',
                'email' => 'admin.puskesmas@gampengrejo.test',
                'password' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $connection->table('puskesmas_user')->updateOrInsert(
            [
                'puskesmas_id' => $puskesmas->id,
                'user_id' => $userId,
            ],
            [
                'peran' => 'admin_puskesmas',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}