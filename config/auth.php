<?php

use App\Modules\Pengaduan\Models\User as PengaduanUser;
use App\Modules\Posyandu\Models\User as PosyanduUser;

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    */

    'guards' => [

        // Default Laravel Web
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        // Modul Pengaduan
        'pengaduan' => [
            'driver' => 'sanctum',
            'provider' => 'pengaduan_users',
        ],

        // Modul Posyandu
        'posyandu' => [
            'driver' => 'sanctum',
            'provider' => 'posyandu_users',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    */

    'providers' => [

        // Default Laravel
        'users' => [
            'driver' => 'eloquent',
            'model' => PengaduanUser::class,
        ],

        // Modul Pengaduan
        'pengaduan_users' => [
            'driver' => 'eloquent',
            'model' => PengaduanUser::class,
        ],

        // Modul Posyandu
        'posyandu_users' => [
            'driver' => 'eloquent',
            'model' => PosyanduUser::class,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Password Resetting
    |--------------------------------------------------------------------------
    */

    'passwords' => [

        'users' => [
            'provider' => 'users',
            'table' => env(
                'AUTH_PASSWORD_RESET_TOKEN_TABLE',
                'password_reset_tokens'
            ),
            'expire' => 60,
            'throttle' => 60,
        ],

        'pengaduan_users' => [
            'provider' => 'pengaduan_users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],

        'posyandu_users' => [
            'provider' => 'posyandu_users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
