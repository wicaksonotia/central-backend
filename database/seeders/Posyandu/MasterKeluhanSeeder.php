<?php

namespace Database\Seeders\Posyandu;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterKeluhanSeeder extends Seeder
{
    public function run(): void
    {
        $db = DB::connection('posyandu');

        $data = [
            'Demam',
            'Batuk',
            'Pilek',
            'Sakit kepala',
            'Pusing',
            'Mual',
            'Muntah',
            'Diare',
            'Sembelit',
            'Nyeri perut',
            'Nyeri sendi',
            'Sesak napas',
            'Lemas',
            'Tidak nafsu makan',
            'Keluhan lainnya',
        ];

        foreach ($data as $nama) {
            $db->table('master_keluhan')->updateOrInsert(
                ['nama' => $nama],
                [
                    'status' => true,
                    'updated_at' => now(),
                ]
            );
        }
    }
}