<?php

namespace App\Modules\Posyandu\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $connection = 'posyandu';

    protected $table = 'users';

    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function petugasPosyandu()
    {
        return $this->hasMany(PetugasPosyandu::class, 'user_id');
    }

    public function kegiatanDibuat()
    {
        return $this->hasMany(KegiatanPosyandu::class, 'created_by');
    }

    public function kunjunganDibuat()
    {
        return $this->hasMany(Kunjungan::class, 'created_by');
    }
}