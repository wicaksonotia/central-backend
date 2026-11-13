<?php

namespace Database\Seeders\Posyandu;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KelompokSasaranSeeder extends Seeder
{
    public function run(): void
    {
        $db = DB::connection('posyandu');

        $data = [
            [
                'kode' => 'BAYI',
                'nama' => 'Bayi',
                'usia_min_bulan' => 0,
                'usia_max_bulan' => 11,
            ],
            [
                'kode' => 'BALITA',
                'nama' => 'Balita',
                'usia_min_bulan' => 12,
                'usia_max_bulan' => 59,
            ],
            [
                'kode' => 'PRASEKOLAH',
                'nama' => 'Anak Prasekolah',
                'usia_min_bulan' => 60,
                'usia_max_bulan' => 71,
            ],
            [
                'kode' => 'SEKOLAH',
                'nama' => 'Anak Usia Sekolah',
                'usia_min_bulan' => 72,
                'usia_max_bulan' => 119,
            ],
            [
                'kode' => 'REMAJA',
                'nama' => 'Remaja',
                'usia_min_bulan' => 120,
                'usia_max_bulan' => 215,
            ],
            [
                'kode' => 'DEWASA',
                'nama' => 'Dewasa',
                'usia_min_bulan' => 216,
                'usia_max_bulan' => 719,
            ],
            [
                'kode' => 'LANSIA',
                'nama' => 'Lansia',
                'usia_min_bulan' => 720,
                'usia_max_bulan' => null,
            ],
        ];

        foreach ($data as $item) {
            $db->table('kelompok_sasaran')->updateOrInsert(
                ['kode' => $item['kode']],
                [
                    'nama' => $item['nama'],
                    'usia_min_bulan' => $item['usia_min_bulan'],
                    'usia_max_bulan' => $item['usia_max_bulan'],
                    'status' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}