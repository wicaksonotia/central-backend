<?php

namespace Database\Seeders\Posyandu;

use Illuminate\Database\Seeder;
use App\Modules\Posyandu\Models\Desa;

class DesaSeeder extends Seeder
{
    public function run(): void
    {
        Desa::updateOrCreate(
            ['kode_desa' => 'kalibelo-gampengrejo'],
            [
                'nama_desa' => 'Kalibelo',
                'kecamatan' => 'Gampengrejo',
                'kabupaten' => 'Kediri',
                'provinsi' => 'Jawa Timur',
                'status' => true,
            ]
        );
    }
}
