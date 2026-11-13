<?php

namespace Database\Seeders\Posyandu;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PosPemeriksaanSeeder extends Seeder
{
    public function run(): void
    {
        $db = DB::connection('posyandu');

        $data = [
            [
                'kode' => 'POS-01',
                'nama' => 'Pendaftaran',
                'keterangan' => 'Pendaftaran dan verifikasi data warga.',
                'urutan' => 1,
            ],
            [
                'kode' => 'POS-02',
                'nama' => 'Berat Badan dan Tinggi Badan',
                'keterangan' => 'Pengukuran berat badan dan tinggi atau panjang badan.',
                'urutan' => 2,
            ],
            [
                'kode' => 'POS-03',
                'nama' => 'Tensi dan Lingkar Pinggang',
                'keterangan' => 'Pengukuran tekanan darah dan lingkar pinggang.',
                'urutan' => 3,
            ],
            [
                'kode' => 'POS-04',
                'nama' => 'Keluhan',
                'keterangan' => 'Pencatatan keluhan warga.',
                'urutan' => 4,
            ],
            [
                'kode' => 'POS-05',
                'nama' => 'Gula Darah',
                'keterangan' => 'Pemeriksaan kadar gula darah.',
                'urutan' => 5,
            ],
        ];

        foreach ($data as $item) {
            $db->table('pos_pemeriksaan')->updateOrInsert(
                ['kode' => $item['kode']],
                [
                    'nama' => $item['nama'],
                    'keterangan' => $item['keterangan'],
                    'urutan' => $item['urutan'],
                    'status' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}