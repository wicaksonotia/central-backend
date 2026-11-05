<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $units = [
            [
                'instansi_id' => 1,
                'parent_id' => 1,
                'kode' => 'UPT-PKB',
                'nama' => 'UPT Pengujian Kendaraan Bermotor',
                'deskripsi' => 'Unit pelaksana pengujian kendaraan bermotor',
            ],
            [
                'instansi_id' => 1,
                'parent_id' => null,
                'kode' => 'SEKRETARIAT',
                'nama' => 'Sekretariat',
                'deskripsi' => 'Sekretariat Dinas Perhubungan Kabupaten Sidoarjo',
            ],
            [
                'instansi_id' => 1,
                'parent_id' => 1,
                'kode' => 'WASDAL',
                'nama' => 'Pengawasan dan Pengendalian Lalu Lintas dan Angkutan Jalan',
                'deskripsi' => 'Mempunyai tugas menjaga ketertiban, keamanan, dan kelancaran lalu lintas',
            ],
            [
                'instansi_id' => 1,
                'parent_id' => 1,
                'kode' => 'LALIN',
                'nama' => 'Bidang Lalu Lintas',
                'deskripsi' => 'Mempunyai tugas menyelenggarakan perumusan, pelaksanaan, pengawasan, pengendalian, dan evaluasi di bidang lalu lintas',
            ],
            [
                'instansi_id' => 1,
                'parent_id' => 1,
                'kode' => 'ANGKUTAN',
                'nama' => 'Bidang Angkutan',
                'deskripsi' => 'Mempunyai tugas untuk melaksanakan sebagian tugas dinas di bidang angkutan, yang meliputi penyusunan program, pelaksanaan, koordinasi, pengawasan, serta evaluasi urusan transportasi',
            ],
            [
                'instansi_id' => 1,
                'parent_id' => 1,
                'kode' => 'SARPRAS',
                'nama' => 'Bidang Sarana dan Prasarana Transportasi',
                'deskripsi' => 'Bertanggung jawab dalam perencanaan, pembangunan, pengembangan, serta pemeliharaan sarana dan prasarana penunjang sistem transportasi, termasuk fasilitas pendukung angkutan massal seperti halte bus, terminal, dan infrastruktur transportasi lainnya',
            ],
        ];

        DB::transaction(function () use ($units, $now) {
            foreach ($units as $unit) {
                DB::table('units')->updateOrInsert(
                    [
                        'kode' => $unit['kode'],
                        'instansi_id' => $unit['instansi_id'],
                    ],
                    [
                        ...$unit,
                        'alamat' => null,
                        'telepon' => null,
                        'aktif' => true,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        });
    }
}
