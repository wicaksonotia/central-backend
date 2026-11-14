<?php

namespace App\Modules\Posyandu\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Posyandu\Services\PosyanduPosService;
use Illuminate\Http\Request;

class PosyanduPosController extends Controller
{
    public function __construct(
        protected PosyanduPosService $posyanduPosService
    ) {}

    /**
     * Daftar konfigurasi pos.
     */
    public function index(Request $request)
    {
        $data = $this->posyanduPosService->daftar(
            $request->user('posyandu')
        );

        return response()->json([
            'success' => true,
            'message' => 'Konfigurasi pos berhasil diambil.',
            'data' => $data,
        ]);
    }

    /**
     * Simpan urutan dan status pos.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'pos' => ['required', 'array', 'min:1'],
            'pos.*.posyandu_pos_id' => [
                'required',
                'integer',
                'distinct',
            ],
            'pos.*.urutan' => [
                'required',
                'integer',
                'min:1',
            ],
            'pos.*.status' => [
                'required',
                'boolean',
            ],
        ]);

        $data = $this->posyanduPosService->simpan(
            $request->user('posyandu'),
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'Konfigurasi pos berhasil diperbarui.',
            'data' => $data,
        ]);
    }
}
