<?php

namespace App\Modules\Posyandu\Services;

use App\Modules\Posyandu\Models\Warga;
use App\Modules\Posyandu\Models\KegiatanPosyandu;
use App\Modules\Posyandu\Models\Kunjungan;
use App\Modules\Posyandu\Models\KunjunganPos;
use App\Modules\Posyandu\Models\User as ModelsUser;
use Carbon\Carbon;

class KaderDashboardService
{
    public function getDashboard(ModelsUser $user): array
    {
        // Ambil seluruh penugasan Kader yang masih aktif.
        $assignments = $user->petugasPosyanduAktif()
            ->where('peran', 'kader')
            ->whereHas('posyandu', function ($query) {
                $query->where('status', true);
            })
            ->with('posyandu')
            ->get();

        if ($assignments->isEmpty()) {
            abort(403, 'Anda tidak memiliki penugasan Kader yang aktif.');
        }

        $posyanduIds = $assignments
            ->pluck('posyandu_id')
            ->unique()
            ->values()
            ->all();

        $today = Carbon::today();
        $startOfMonth = $today->copy()->startOfMonth();
        $endOfMonth = $today->copy()->endOfMonth();

        // Total warga aktif.
        $totalWarga = Warga::query()
            ->whereIn('posyandu_id', $posyanduIds)
            ->where('status', true)
            ->count();

        // Kegiatan bulan ini, tidak termasuk draft dan dibatalkan.
        $totalKegiatan = KegiatanPosyandu::query()
            ->whereIn('posyandu_id', $posyanduIds)
            ->whereBetween('tanggal_kegiatan', [
                $startOfMonth,
                $endOfMonth,
            ])
            ->whereIn('status', ['berlangsung', 'selesai'])
            ->count();

        // Jumlah pendaftaran kunjungan hari ini.
        $kunjunganHariIni = Kunjungan::query()
            ->whereHas('kegiatan', function ($query) use ($posyanduIds) {
                $query->whereIn('posyandu_id', $posyanduIds);
            })
            ->whereDate('waktu_daftar', $today)
            ->count();

        // Jumlah antrean yang sedang menunggu pemeriksaan.
        $antreanMenunggu = KunjunganPos::query()
            ->whereHas('kunjungan.kegiatan', function ($query) use ($posyanduIds) {
                $query->whereIn('posyandu_id', $posyanduIds);
            })
            ->where('status', 'waiting')
            ->count();

        return [
            'posyandu' => $assignments->map(function ($assignment) {
                return [
                    'id' => $assignment->posyandu->id,
                    'nama' => $assignment->posyandu->nama_posyandu,
                ];
            })->values(),

            'statistics' => [
                'total_warga' => $totalWarga,
                'total_kegiatan' => $totalKegiatan,
                'kunjungan_hari_ini' => $kunjunganHariIni,
                'antrean_menunggu' => $antreanMenunggu,
            ],
        ];
    }
}