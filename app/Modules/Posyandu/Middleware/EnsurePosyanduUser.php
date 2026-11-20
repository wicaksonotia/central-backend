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
        $user = $request->user('posyandu');

        if (!$user instanceof User || !$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akses pengguna tidak valid.',
            ], 403);
        }

        if (!$user->hasActiveAccess()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki penugasan aktif.',
            ], 403);
        }

        return $next($request);
    }
}
