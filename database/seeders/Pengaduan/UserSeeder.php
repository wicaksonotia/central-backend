<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $defaultPassword = 'password123';
        $users = [
            // =========================
            // Masyarakat
            // =========================
            [
                'id' => 3,
                'name' => 'Masyarakat 1',
                'email' => 'masyarakat1@example.com',
                'phone' => '081234567890',
                'password' => null,
            ],
            [
                'id' => 10,
                'name' => 'Masyarakat 2',
                'email' => 'masyarakat2@example.com',
                'phone' => '081234567891',
                'password' => null,
            ],
            // =========================
            // Admin Unit
            // =========================
            [
                'name' => 'Admin Unit Wasdal',
                'email' => 'admin.wasdal@example.com',
                'phone' => '08113600490',
                'password' => $defaultPassword,
            ],
            [
                'name' => 'Admin Unit Lalin',
                'email' => 'admin.lalin@example.com',
                'phone' => '08113600491',
                'password' => $defaultPassword,
            ],
            [
                'name' => 'Admin Unit Angkutan',
                'email' => 'admin.angkutan@example.com',
                'phone' => '08113600492',
                'password' => $defaultPassword,
            ],
            [
                'name' => 'Admin Unit Sarpras',
                'email' => 'admin.sarpras@example.com',
                'phone' => '08113600493',
                'password' => $defaultPassword,
            ],
            [
                'id' => 5,
                'name' => 'Admin Unit PKB',
                'email' => 'admin.pkb@example.com',
                'phone' => '08113600494',
                'password' => $defaultPassword,
            ],
            [
                'id' => 8,
                'name' => 'Admin Unit Sekretariat',
                'email' => 'admin.sekretariat@example.com',
                'phone' => '08113600495',
                'password' => $defaultPassword,
            ],
            // =========================
            // Petugas Lapangan
            // =========================
            [
                'name' => 'Petugas Lapangan Wasdal',
                'email' => 'petugas.wasdal@example.com',
                'phone' => '085755124530',
                'password' => $defaultPassword,
            ],
            [
                'name' => 'Petugas Lapangan Lalin',
                'email' => 'petugas.lalin@example.com',
                'phone' => '085755124531',
                'password' => $defaultPassword,
            ],
            [
                'name' => 'Petugas Lapangan Angkutan',
                'email' => 'petugas.angkutan@example.com',
                'phone' => '085755124532',
                'password' => $defaultPassword,
            ],
            [
                'id' => 11,
                'name' => 'Petugas Lapangan Sarpras',
                'email' => 'petugas.sarpras@example.com',
                'phone' => '085755124533',
                'password' => $defaultPassword,
            ],
            [
                'id' => 6,
                'name' => 'Petugas Lapangan PKB',
                'email' => 'petugas.pkb@example.com',
                'phone' => '085646774334',
                'password' => $defaultPassword,
            ],
            [
                'id' => 9,
                'name' => 'Petugas Lapangan Sekretariat',
                'email' => 'petugas.sekretariat@example.com',
                'phone' => '085646774335',
                'password' => $defaultPassword,
            ],
        ];

        DB::transaction(function () use ($users) {
            foreach ($users as $user) {
                if (!empty($user['password'])) {
                    $user['password'] = Hash::make($user['password']);
                }

                // Gunakan ID jika tersedia, jika tidak gunakan email.
                $identity = isset($user['id'])
                    ? ['id' => $user['id']]
                    : ['email' => $user['email']];

                DB::table('users')->updateOrInsert(
                    $identity,
                    [
                        ...$user,
                        'email_verified_at' => null,
                        'remember_token' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'google_id' => null,
                        'avatar' => null,
                        'phone_verified_at' => null,
                        'profile_completed_at' => null,
                    ]
                );
            }
        });

        // Sinkronisasi sequence ID PostgreSQL
        DB::statement("
            SELECT setval(
                pg_get_serial_sequence('users', 'id'),
                COALESCE((SELECT MAX(id) FROM users), 1),
                EXISTS (SELECT 1 FROM users)
            )
        ");
    }
}
