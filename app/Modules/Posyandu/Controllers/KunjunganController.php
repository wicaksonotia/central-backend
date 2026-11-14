<?php

namespace App\Modules\Posyandu\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Posyandu\Services\KunjunganService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KunjunganController extends Controller
{
    public function __construct(
        protected KunjunganService $kunjunganService
    ) {}

    /**
     * Mendaftarkan warga ke kegiatan Posyandu.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kegiatan_id' => ['required', 'integer', 'exists:posyandu.kegiatan_posyandu,id'],
            'warga_id' => ['required', 'integer', 'exists:posyandu.warga,id'],
        ]);

        $kunjungan = $this->kunjunganService->daftarWarga(
            kegiatanId: $validated['kegiatan_id'],
            wargaId: $validated['warga_id'],
            userId: $request->user()?->id
        );

        return response()->json([
            'status' => true,
            'message' => 'Warga berhasil didaftarkan ke kegiatan Posyandu.',
            'data' => $kunjungan,
        ], 201);
    }
}
