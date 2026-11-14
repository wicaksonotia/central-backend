<?php

namespace App\Modules\Shared\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    protected $table = 'personal_access_tokens';

    /**
     * Cari token pada database masing-masing modul.
     */
    public static function findToken($token)
    {
        $connections = [
            'pengaduan',
            'posyandu',
        ];

        // Format token lama tanpa ID|secret.
        if (! str_contains($token, '|')) {
            foreach ($connections as $connection) {
                $accessToken = static::on($connection)
                    ->where('token', $token)
                    ->first();

                if ($accessToken) {
                    return $accessToken;
                }
            }

            return null;
        }

        [$id, $plainTextToken] = explode('|', $token, 2);

        foreach ($connections as $connection) {
            $accessToken = static::on($connection)->find($id);

            if (
                $accessToken &&
                hash_equals(
                    $accessToken->token,
                    hash('sha256', $plainTextToken)
                )
            ) {
                return $accessToken;
            }
        }

        return null;
    }
}
