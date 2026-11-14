<?php

namespace App\Modules\Posyandu\Models;

class PhoneOtp extends PosyanduModel
{
    protected $connection = 'posyandu';
    
    protected $table = 'phone_otp';

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }
}
