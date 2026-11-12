<?php

namespace App\Modules\Dishub\Services;

use App\Modules\Dishub\Models\Uji;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class TotalUjiHariIniService
{

    public function getTotal(): array
    {
        $tanggalHariIni = Carbon::today()->toDateString();

        $mapping = [
            1 => 'uji_berkala',
            8 => 'uji_pertama',
            2 => 'numpang_masuk',
            3 => 'numpang_keluar',
            4 => 'mutasi_masuk',
            5 => 'mutasi_keluar',
        ];

        $hasil = Uji::query()
            ->whereIn('id_uji', array_keys($mapping))
            ->withCount([
                'retribusi as total_hari_ini' => function (
                    Builder $query
                ) use ($tanggalHariIni) {
                    $query
                        ->where('validasi', true)
                        ->whereDate('tgl_retribusi', $tanggalHariIni);
                },
            ])
            ->get(['id_uji']);

        $total = array_fill_keys(array_values($mapping), 0);

        foreach ($hasil as $uji) {
            $key = $mapping[(int) $uji->id_uji] ?? null;

            if ($key !== null) {
                $total[$key] = (int) $uji->total_hari_ini;
            }
        }

        return [
            'status' => 'ok',
            'status_message' => '-',
            'data' => $total,
        ];
    }
}
