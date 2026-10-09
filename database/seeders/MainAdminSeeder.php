<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class MainAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'wicaksono.tia@gmail.com';
        $phone = '085755124535';

        DB::connection('pengaduan')->transaction(function () use ($email, $phone) {
            // Cari role main_admin yang sudah dibuat RoleSeeder.
            $role = Role::where('kode', 'main_admin')->first();

            if (!$role) {
                throw new RuntimeException(
                    'Role main_admin belum tersedia. Jalankan RoleSeeder terlebih dahulu.'
                );
            }

            // Cari akun berdasarkan email.
            $user = User::where('email', $email)->first();

            if (!$user) {
                // Password acak; login tetap menggunakan Google/OTP.
                $user = User::create([
                    'name' => 'Admin Utama',
                    'email' => $email,
                    'phone' => $phone,
                    'password' => Hash::make('h3l10s'),
                ]);
            } else {
                // Lengkapi nomor HP tanpa mengganti password akun lama.
                $user->phone = $phone;

                if (empty($user->name)) {
                    $user->name = 'Admin Utama';
                }

                $user->save();
            }

            // Pastikan akun memiliki role main_admin.
            $exists = DB::connection('pengaduan')
                ->table('user_roles')
                ->where('user_id', $user->id)
                ->where('role_id', $role->id)
                ->whereNull('unit_id')
                ->exists();

            if (!$exists) {
                DB::connection('pengaduan')
                    ->table('user_roles')
                    ->insert([
                        'user_id' => $user->id,
                        'role_id' => $role->id,
                        'unit_id' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            }
        });

        $this->command?->info(
            'Akun admin utama berhasil disiapkan.'
        );
    }
}