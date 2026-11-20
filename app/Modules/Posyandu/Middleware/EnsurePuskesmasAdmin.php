<?php

namespace App\Modules\Posyandu\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePuskesmasAdmin
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user('posyandu');

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $isAdmin = $user->puskesmasUserAktif()
            ->where('peran', 'admin_puskesmas')
            ->whereHas('puskesmas', function ($query) {
                $query->where('status', true);
            })
            ->exists();

        if (!$isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses sebagai Admin Puskesmas.',
            ], 403);
        }

        return $next($request);
    }
}