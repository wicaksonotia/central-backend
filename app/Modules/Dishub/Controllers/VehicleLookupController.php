<?php

namespace App\Modules\Dishub\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dishub\Services\VehicleLookupService;
use Illuminate\Http\Request;

class VehicleLookupController extends Controller
{
    public function check(Request $request, VehicleLookupService $lookup)
    {
        $validated = $request->validate([
            'nouji' => ['required', 'string', 'max:30'],
            'chassis_last5' => ['required', 'regex:/^[0-9]{5}$/'],
        ]);

        try {
            $vehicle = $lookup->find($validated['nouji'], $validated['chassis_last5']);
        } catch (\RuntimeException $e) {
            report($e);
            return response()->json([
                'message' => 'Layanan pengecekan sedang belum tersedia.',
            ], 503);
        }

        if (!$vehicle) {
            return response()->json([
                'message' => 'Data kendaraan tidak ditemukan. Periksa kembali nomor uji dan lima digit terakhir nomor rangka.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'message' => 'Data kendaraan ditemukan.',
            'data' => $vehicle,
        ]);
    }
}
