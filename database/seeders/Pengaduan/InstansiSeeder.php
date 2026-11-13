<?php

namespace Database\Seeders\Pengaduan;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InstansiSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection('pengaduan')
            ->table('instansi')
            ->updateOrInsert(
                ['id' => 1],
                [
                    'nama'       => 'Dinas Perhubungan Kabupaten Sidoarjo',
                    'singkatan'  => 'Dishub Sidoarjo',
                    'alamat'     => 'Kabupaten Sidoarjo, Jawa Timur',
                    'email'      => null,
                    'telepon'    => null,
                    'aktif'      => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
    }
}
