<?php

namespace Database\Seeders\Posyandu;

use Illuminate\Database\Seeder;
use App\Modules\Posyandu\Models\User;
use App\Modules\Posyandu\Models\Desa;
use App\Modules\Posyandu\Models\Posyandu;
use App\Modules\Posyandu\Models\PetugasPosyandu;

class AdminPosyanduSeeder extends Seeder
{
    public function run(): void
    {
        $desa = Desa::where('kode_desa', 'kalibelo-gampengrejo')
            ->firstOrFail();

        $admins = [
            [
                'kode_posyandu' => 'POS-KALIBELO',
                'name' => 'Admin Posyandu Kalibelo',
                'phone' => '081234567890',
            ],
            [
                'kode_posyandu' => 'POS-SUMBERTUGU',
                'name' => 'Admin Posyandu Sumbertugu',
                'phone' => '081234567891',
            ],
        ];

        foreach ($admins as $admin) {
            $posyandu = Posyandu::where('desa_id', $desa->id)
                ->where('kode_posyandu', $admin['kode_posyandu'])
                ->firstOrFail();

            $user = User::updateOrCreate(
                ['phone' => $admin['phone']],
                [
                    'name' => $admin['name'],
                    'is_active' => true,
                ]
            );

            PetugasPosyandu::updateOrCreate(
                [
                    'posyandu_id' => $posyandu->id,
                    'user_id' => $user->id,
                ],
                [
                    'peran' => 'admin',
                    'status' => true,
                ]
            );
        }

        $this->command->info('Dua Admin Posyandu berhasil disiapkan.');
    }
}
