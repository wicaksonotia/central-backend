<?php

namespace Database\Seeders\Posyandu;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PuskesmasSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection('posyandu')
            ->table('puskesmas')
            ->updateOrInsert(
                ['kode_puskesmas' => 'PUSKESMAS-GAMPENGREJO'],
                [
                    'nama_puskesmas' => 'Puskesmas Gampengrejo',
                    'alamat' => null,
                    'no_hp' => null,
                    'email' => null,
                    'status' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
    }
}