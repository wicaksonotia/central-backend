<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMainAdmin
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $isMainAdmin = $user->roles()
            ->where('roles.kode', 'main_admin')
            ->exists();

        if (!$isMainAdmin) {
            return response()->json([
                'message' => 'Anda tidak memiliki hak akses admin utama.',
            ], 403);
        }

        return $next($request);
    }
}