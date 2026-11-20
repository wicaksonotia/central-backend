<?php

namespace Database\Seeders\Posyandu;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PuskesmasDesaSeeder extends Seeder
{
    public function run(): void
    {
        $connection = DB::connection('posyandu');

        $puskesmas = $connection->table('puskesmas')
            ->where('kode_puskesmas', 'PUSKESMAS-GAMPENGREJO')
            ->first();

        if (!$puskesmas) {
            throw new \RuntimeException(
                'Puskesmas Gampengrejo belum tersedia. Jalankan PuskesmasSeeder terlebih dahulu.'
            );
        }

        $kodeDesa = [
            'kalibelo-gampengrejo',
            'kepuhrejo-gampengrejo',
            'plosorejo-gampengrejo',
            'wanengpaten-gampengrejo',
        ];

        $desa = $connection->table('desa')
            ->whereIn('kode_desa', $kodeDesa)
            ->get();

        foreach ($desa as $item) {
            $connection->table('puskesmas_desa')->updateOrInsert(
                [
                    'puskesmas_id' => $puskesmas->id,
                    'desa_id' => $item->id,
                ],
                [
                    'status' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}