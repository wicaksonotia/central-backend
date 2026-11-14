<?php

namespace Database\Seeders\Posyandu;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Modules\Posyandu\Models\User;
use App\Modules\Posyandu\Models\PetugasPosyandu;
use App\Modules\Posyandu\Models\PetugasPosyanduPos;
use App\Modules\Posyandu\Models\Posyandu;

class PetugasKaderSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection('posyandu')->transaction(function () {

            $daftarKader = [
                [
                    'kode_posyandu' => 'POS-KALIBELO',
                    'kader' => [
                        [
                            'name' => 'Kader Posyandu 1 A',
                            'phone' => '081300000001',
                        ],
                        [
                            'name' => 'Kader Posyandu 1 B',
                            'phone' => '081300000002',
                        ],
                    ],
                ],
                [
                    'kode_posyandu' => 'POS-SUMBERTUGU',
                    'kader' => [
                        [
                            'name' => 'Kader Posyandu 2 A',
                            'phone' => '081300000003',
                        ],
                        [
                            'name' => 'Kader Posyandu 2 B',
                            'phone' => '081300000004',
                        ],
                    ],
                ],
            ];

            foreach ($daftarKader as $item) {

                $posyandu = Posyandu::where(
                    'kode_posyandu',
                    $item['kode_posyandu']
                )->firstOrFail();

                foreach ($item['kader'] as $data) {

                    $user = User::updateOrCreate(
                        [
                            'phone' => $data['phone'],
                        ],
                        [
                            'name' => $data['name'],
                            'is_active' => true,
                        ]
                    );

                    $petugas = PetugasPosyandu::updateOrCreate(
                        [
                            'posyandu_id' => $posyandu->id,
                            'user_id' => $user->id,
                        ],
                        [
                            'peran' => 'kader',
                            'status' => true,
                        ]
                    );

                    // Contoh penugasan pos pemeriksaan.
                    $posPemeriksaan = \App\Modules\Posyandu\Models\PosPemeriksaan::where(
                        'status',
                        true
                    )->orderBy('urutan')->get();

                    foreach ($posPemeriksaan as $pos) {
                        PetugasPosyanduPos::updateOrCreate(
                            [
                                'petugas_posyandu_id' => $petugas->id,
                                'pos_pemeriksaan_id' => $pos->id,
                            ],
                            [
                                'status' => true,
                            ]
                        );
                    }
                }
            }
        });
    }
}
