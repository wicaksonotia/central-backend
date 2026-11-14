<?php

namespace App\Modules\Posyandu\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePosyanduAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('posyandu');

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $isAdmin = $user->petugasPosyandu()
            ->where('status', true)
            ->where('peran', 'admin')
            ->exists();

        if (!$isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses sebagai Admin Posyandu.',
            ], 403);
        }

        return $next($request);
    }
}
