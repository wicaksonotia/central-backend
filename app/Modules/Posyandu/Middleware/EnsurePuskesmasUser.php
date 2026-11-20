<?php

namespace App\Modules\Posyandu\Middleware;

use App\Modules\Posyandu\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePuskesmasUser
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user('posyandu');

        if (!$user instanceof User || !$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akses pengguna tidak valid.',
            ], 403);
        }

        $hasAccess = $user->puskesmasUserAktif()
            ->whereHas('puskesmas', function ($query) {
                $query->where('status', true);
            })
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses Puskesmas.',
            ], 403);
        }

        return $next($request);
    }
}