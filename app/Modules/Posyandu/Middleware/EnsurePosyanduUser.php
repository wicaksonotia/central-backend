<?php

namespace App\Modules\Posyandu\Middleware;

use App\Modules\Posyandu\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePosyanduUser
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        if (!$request->user() instanceof User) {
            return response()->json([
                'status' => false,
                'message' => 'Akses khusus pengguna Posyandu.',
            ], 403);
        }

        return $next($request);
    }
}