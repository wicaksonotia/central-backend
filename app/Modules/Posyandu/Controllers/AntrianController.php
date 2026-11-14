<?php

namespace App\Modules\Posyandu\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Posyandu\Services\AntrianService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AntrianController extends Controller
{
    public function __construct(
        protected AntrianService $antrianService
    ) {}

    /**
     * Daftar antrean pada suatu pos.
     */
    public function index(
        Request $request,
        int $kegiatanId,
        int $posyanduPosId
    ): JsonResponse {
        $data = $this->antrianService->daftar(
            $request->user('posyandu'),
            $kegiatanId,
            $posyanduPosId
        );

        return response()->json([
            'status' => true,
            'message' => 'Daftar antrean berhasil diambil.',
            'data' => $data,
        ]);
    }

    /**
     * Memanggil warga.
     */
    public function panggil(
        Request $request,
        int $kegiatanId,
        int $posyanduPosId,
        int $kunjunganPosId
    ): JsonResponse {
        $data = $this->antrianService->panggil(
            $request->user('posyandu'),
            $kegiatanId,
            $posyanduPosId,
            $kunjunganPosId
        );

        return response()->json([
            'status' => true,
            'message' => 'Warga berhasil dipanggil.',
            'data' => $data,
        ]);
    }

    /**
     * Melewati antrean.
     */
    public function lewati(
        Request $request,
        int $kegiatanId,
        int $posyanduPosId,
        int $kunjunganPosId
    ): JsonResponse {
        $data = $this->antrianService->lewati(
            $request->user('posyandu'),
            $kegiatanId,
            $posyanduPosId,
            $kunjunganPosId
        );

        return response()->json([
            'status' => true,
            'message' => 'Antrean berhasil dilewati.',
            'data' => $data,
        ]);
    }

    /**
     * Memasukkan kembali antrean yang dilewati.
     */
    public function panggilUlang(
        Request $request,
        int $kegiatanId,
        int $posyanduPosId,
        int $kunjunganPosId
    ): JsonResponse {
        $data = $this->antrianService->panggilUlang(
            $request->user('posyandu'),
            $kegiatanId,
            $posyanduPosId,
            $kunjunganPosId
        );

        return response()->json([
            'status' => true,
            'message' => 'Antrean berhasil dimasukkan kembali.',
            'data' => $data,
        ]);
    }

    /**
     * Menyelesaikan pemeriksaan.
     */
    public function selesai(
        Request $request,
        int $kegiatanId,
        int $posyanduPosId,
        int $kunjunganPosId
    ): JsonResponse {
        $data = $this->antrianService->selesai(
            $request->user('posyandu'),
            $kegiatanId,
            $posyanduPosId,
            $kunjunganPosId
        );

        return response()->json([
            'status' => true,
            'message' => 'Pemeriksaan berhasil diselesaikan.',
            'data' => $data,
        ]);
    }
}
