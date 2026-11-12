<?php

namespace App\Modules\Dishub\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dishub\Services\MobileContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileContentController extends Controller
{
    public function __construct(
        private readonly MobileContentService $mobileContentService
    ) {}

    public function menu(): JsonResponse
    {
        return response()->json(
            $this->mobileContentService->getMenu()
        );
    }

    public function slider(): JsonResponse
    {
        return response()->json(
            $this->mobileContentService->getSlider()
        );
    }

    public function berita(): JsonResponse
    {
        return response()->json(
            $this->mobileContentService->getBerita()
        );
    }

    public function jenisUjiPersyaratan(): JsonResponse
    {
        return response()->json(
            $this->mobileContentService->getJenisUjiPersyaratan()
        );
    }

    public function detailPersyaratan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_jenis_uji' => ['sometimes', 'integer', 'min:1'],
        ]);

        return response()->json(
            $this->mobileContentService->getDetailPersyaratan(
                isset($validated['id_jenis_uji'])
                    ? (int) $validated['id_jenis_uji']
                    : null
            )
        );
    }
}
