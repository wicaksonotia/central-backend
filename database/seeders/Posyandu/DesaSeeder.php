<?php

namespace Database\Seeders\Posyandu;

use Illuminate\Database\Seeder;
use App\Modules\Posyandu\Models\Desa;

class DesaSeeder extends Seeder
{
    public function run(): void
    {
        $desa = [
            [
                'kode_desa' => 'kalibelo-gampengrejo',
                'nama_desa' => 'Kalibelo',
            ],
            [
                'kode_desa' => 'kepuhrejo-gampengrejo',
                'nama_desa' => 'Kepuhrejo',
            ],
            [
                'kode_desa' => 'plosorejo-gampengrejo',
                'nama_desa' => 'Plosorejo',
            ],
            [
                'kode_desa' => 'wanengpaten-gampengrejo',
                'nama_desa' => 'Wanengpaten',
            ],
        ];

        foreach ($desa as $item) {
            Desa::updateOrCreate(
                ['kode_desa' => $item['kode_desa']],
                [
                    'nama_desa' => $item['nama_desa'],
                    'kecamatan' => 'Gampengrejo',
                    'kabupaten' => 'Kediri',
                    'provinsi' => 'Jawa Timur',
                    'status' => true,
                ]
            );
        }
    }
}