<?php

namespace Database\Seeders\Posyandu;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PosPemeriksaanSeeder::class,
            KelompokSasaranSeeder::class,
            JenisPemeriksaanSeeder::class,
            MasterKeluhanSeeder::class,
        ]);
    }
}