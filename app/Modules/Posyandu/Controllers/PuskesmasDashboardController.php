<?php

namespace App\Modules\Posyandu\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Posyandu\Models\Desa;
use App\Modules\Posyandu\Models\Posyandu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PuskesmasDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user('posyandu');

        // Ambil seluruh Puskesmas yang menjadi penugasan aktif user.
        $puskesmasIds = $user->puskesmasUserAktif()
            ->whereHas('puskesmas', function ($query) {
                $query->where('status', true);
            })
            ->pluck('puskesmas_id');

        // Batasi Posyandu pada desa aktif di wilayah kerja Puskesmas.
        $posyanduQuery = Posyandu::query()
            ->whereHas('desa', function ($desaQuery) use ($puskesmasIds) {
                $desaQuery
                    ->where('desa.status', true)
                    ->whereHas('puskesmas', function ($puskesmasQuery) use ($puskesmasIds) {
                        $puskesmasQuery
                            ->whereIn('puskesmas.id', $puskesmasIds)
                            ->where('puskesmas.status', true)
                            ->where('puskesmas_desa.status', true);
                    });
            });

        $totalDesa = Desa::query()
            ->where('status', true)
            ->whereHas('puskesmas', function ($query) use ($puskesmasIds) {
                $query
                    ->whereIn('puskesmas.id', $puskesmasIds)
                    ->where('puskesmas.status', true)
                    ->where('puskesmas_desa.status', true);
            })
            ->count();

        $totalPosyandu = (clone $posyanduQuery)->count();

        // Ambil jumlah warga dan kegiatan dari seluruh Posyandu dalam cakupan.
        $rekapPosyandu = (clone $posyanduQuery)
            ->withCount([
                'warga',
                'kegiatan',
            ])
            ->get();

        $totalWarga = $rekapPosyandu->sum('warga_count');
        $totalKegiatan = $rekapPosyandu->sum('kegiatan_count');

        return response()->json([
            'success' => true,
            'message' => 'Statistik dashboard Puskesmas berhasil diambil.',
            'data' => [
                'total_desa' => $totalDesa,
                'total_posyandu' => $totalPosyandu,
                'total_warga' => $totalWarga,
                'total_kegiatan' => $totalKegiatan,
            ],
        ]);
    }
}
