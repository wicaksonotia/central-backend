<?php

namespace App\Modules\Posyandu\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Posyandu\Services\PemeriksaanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PemeriksaanController extends Controller
{
    public function __construct(
        protected PemeriksaanService $pemeriksaanService
    ) {}

    /**
     * Menyimpan hasil pemeriksaan.
     */
    public function store(
        Request $request,
        int $kunjunganPosId
    ): JsonResponse {
        $validated = $request->validate([
            'pemeriksaan' => ['required', 'array', 'min:1'],

            'pemeriksaan.*.jenis_pemeriksaan_id' => [
                'required',
                'integer',
            ],

            'pemeriksaan.*.pengukuran_ke' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'pemeriksaan.*.nilai_numerik' => [
                'nullable',
                'numeric',
            ],

            'pemeriksaan.*.nilai_teks' => [
                'nullable',
                'string',
            ],

            'pemeriksaan.*.keterangan' => [
                'nullable',
                'string',
            ],
        ]);

        $user = $request->user('posyandu');

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $data = $this->pemeriksaanService->simpan(
            $kunjunganPosId,
            $validated['pemeriksaan'],
            $user->id
        );

        return response()->json([
            'status' => true,
            'message' => 'Hasil pemeriksaan berhasil disimpan.',
            'data' => $data,
        ]);
    }

    /**
     * Menampilkan hasil pemeriksaan untuk kunjungan tertentu.
     */
    public function show(
        Request $request,
        int $kunjunganPosId
    ): JsonResponse {
        $user = $request->user('posyandu');

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $data = $this->pemeriksaanService->detail(
            $kunjunganPosId,
            $user->id
        );

        return response()->json([
            'status' => true,
            'message' => 'Detail pemeriksaan berhasil diambil.',
            'data' => $data,
        ]);
    }
}
