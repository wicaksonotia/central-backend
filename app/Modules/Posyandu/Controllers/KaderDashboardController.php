<?php

namespace App\Modules\Posyandu\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Posyandu\Services\KaderDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KaderDashboardController extends Controller
{
    public function __construct(
        protected KaderDashboardService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $this->service->getDashboard(
            $request->user('posyandu')
        );

        return response()->json([
            'success' => true,
            'message' => 'Dashboard Kader berhasil dimuat.',
            'data' => $data,
        ]);
    }
}