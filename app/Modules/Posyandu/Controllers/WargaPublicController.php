<?php

namespace App\Modules\Posyandu\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Posyandu\Services\WargaPublicService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WargaPublicController extends Controller
{
    public function __construct(
        protected WargaPublicService $wargaPublicService
    ) {}

    /**
     * Pencarian warga berdasarkan nama atau NIK.
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:100'],
        ]);

        $data = $this->wargaPublicService->search(
            trim($validated['q'])
        );

        return response()->json([
            'status' => true,
            'message' => $data['count'] > 0
                ? 'Data warga ditemukan.'
                : 'Data warga tidak ditemukan.',
            'data' => $data,
        ]);
    }
}
