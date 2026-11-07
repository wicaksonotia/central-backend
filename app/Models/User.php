<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name',
    'email',
    'phone',
    'google_id',
    'avatar',
    'password',
    'profile_completed_at'
])]
#[Hidden([
    'password',
    'remember_token',
])]
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $connection = 'pengaduan';

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'google_id',
        'avatar',
        'password',
        'profile_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'profile_completed_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relasi role aktif milik user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'user_roles',
            'user_id',
            'role_id'
        )
            ->withPivot([
                'unit_id',
                'is_active',
                'deactivated_at',
            ])
            ->wherePivot('is_active', true)
            ->withTimestamps();
    }

    /**
     * Seluruh riwayat penugasan role user.
     */
    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    /**
     * Riwayat penugasan aktif.
     */
    public function activeUserRoles(): HasMany
    {
        return $this->userRoles()->active();
    }

    /**
     * Riwayat penugasan nonaktif.
     */
    public function inactiveUserRoles(): HasMany
    {
        return $this->userRoles()->inactive();
    }

    /**
     * Memeriksa apakah user memiliki role aktif tertentu.
     */
    public function hasRole(string $role): bool
    {
        return $this->roles()
            ->where('kode', $role)
            ->exists();
    }
}
