<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'kode' => 'main_admin',
                'nama' => 'Admin Utama',
                'deskripsi' => 'Mengelola seluruh aplikasi pengaduan.',
            ],
            [
                'kode' => 'unit_admin',
                'nama' => 'Admin Unit',
                'deskripsi' => 'Mengelola pengaduan dalam unitnya.',
            ],
            [
                'kode' => 'field_officer',
                'nama' => 'Petugas Lapangan',
                'deskripsi' => 'Menangani pengaduan di lapangan.',
            ],
            [
                'kode' => 'masyarakat',
                'nama' => 'Masyarakat',
                'deskripsi' => 'Mengirim dan memantau pengaduan.',
            ],
        ];

        $db = DB::connection('pengaduan');

        foreach ($roles as $role) {
            $db->table('roles')->updateOrInsert(
                ['kode' => $role['kode']],
                array_merge($role, [
                    'updated_at' => now(),
                    'created_at' => now(),
                ])
            );
        }
    }
}
