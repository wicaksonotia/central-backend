<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

use App\Modules\Pengaduan\Middleware\EnsureMainAdmin;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'main.admin' => EnsureMainAdmin::class,
        ]);

        // $middleware->redirectGuests(
        //     fn(Request $request) => $request->is('api/*')
        //         ? null
        //         : route('login')
        // );
        $middleware->redirectGuestsTo(
            fn(Request $request) => null
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*')
                || $request->expectsJson(),
        );
    })
    ->create();
