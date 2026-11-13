<?php

namespace Database\Seeders\Posyandu;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JenisPemeriksaanSeeder extends Seeder
{
    public function run(): void
    {
        $db = DB::connection('posyandu');

        $pos = $db->table('pos_pemeriksaan')
            ->pluck('id', 'kode');

        $kelompok = $db->table('kelompok_sasaran')
            ->pluck('id', 'kode');

        $data = [
            [
                'kode' => 'BB',
                'nama' => 'Berat Badan',
                'pos' => 'POS-02',
                'satuan' => 'kg',
                'kelompok' => [
                    'BAYI',
                    'BALITA',
                    'PRASEKOLAH',
                    'SEKOLAH',
                    'REMAJA',
                    'DEWASA',
                    'LANSIA',
                ],
            ],
            [
                'kode' => 'TB',
                'nama' => 'Tinggi / Panjang Badan',
                'pos' => 'POS-02',
                'satuan' => 'cm',
                'kelompok' => [
                    'BAYI',
                    'BALITA',
                    'PRASEKOLAH',
                    'SEKOLAH',
                    'REMAJA',
                    'DEWASA',
                    'LANSIA',
                ],
            ],
            [
                'kode' => 'TENSI',
                'nama' => 'Tekanan Darah',
                'pos' => 'POS-03',
                'satuan' => 'mmHg',
                'kelompok' => ['DEWASA', 'LANSIA'],
            ],
            [
                'kode' => 'LINGKAR_PINGGANG',
                'nama' => 'Lingkar Pinggang',
                'pos' => 'POS-03',
                'satuan' => 'cm',
                'kelompok' => ['DEWASA', 'LANSIA'],
            ],
            [
                'kode' => 'GULA_DARAH',
                'nama' => 'Gula Darah',
                'pos' => 'POS-05',
                'satuan' => 'mg/dL',
                'kelompok' => ['DEWASA', 'LANSIA'],
            ],
        ];

        foreach ($data as $item) {
            $posId = $pos[$item['pos']] ?? null;

            if (!$posId) {
                throw new \RuntimeException(
                    "Pos pemeriksaan {$item['pos']} belum tersedia."
                );
            }

            $db->table('jenis_pemeriksaan')->updateOrInsert(
                ['kode' => $item['kode']],
                [
                    'pos_pemeriksaan_id' => $posId,
                    'nama' => $item['nama'],
                    'satuan' => $item['satuan'],
                    'tipe_nilai' => 'numeric',
                    'keterangan' => null,
                    'wajib' => false,
                    'status' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $jenisId = $db->table('jenis_pemeriksaan')
                ->where('kode', $item['kode'])
                ->value('id');

            foreach ($item['kelompok'] as $kodeKelompok) {
                $kelompokId = $kelompok[$kodeKelompok] ?? null;

                if (!$kelompokId) {
                    throw new \RuntimeException(
                        "Kelompok sasaran {$kodeKelompok} belum tersedia."
                    );
                }

                $db->table('jenis_pemeriksaan_kelompok')->updateOrInsert(
                    [
                        'jenis_pemeriksaan_id' => $jenisId,
                        'kelompok_sasaran_id' => $kelompokId,
                    ],
                    [
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }
}
