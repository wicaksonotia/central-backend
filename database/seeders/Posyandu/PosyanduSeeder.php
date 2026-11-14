<?php

namespace Database\Seeders\Posyandu;

use Illuminate\Database\Seeder;
use App\Modules\Posyandu\Models\Desa;
use App\Modules\Posyandu\Models\Posyandu;

class PosyanduSeeder extends Seeder
{
    public function run(): void
    {
        $desa = Desa::where('kode_desa', 'kalibelo-gampengrejo')
            ->firstOrFail();

        $data = [
            [
                'kode_posyandu' => 'POS-KALIBELO',
                'nama_posyandu' => 'Posyandu Kalibelo',
            ],
            [
                'kode_posyandu' => 'POS-SUMBERTUGU',
                'nama_posyandu' => 'Posyandu Sumbertugu',
            ],
        ];

        foreach ($data as $item) {
            Posyandu::updateOrCreate(
                [
                    'desa_id' => $desa->id,
                    'kode_posyandu' => $item['kode_posyandu'],
                ],
                [
                    'nama_posyandu' => $item['nama_posyandu'],
                    'status' => true,
                ]
            );
        }
    }
}
