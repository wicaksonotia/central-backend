<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $connection = 'pengaduan';

    protected $fillable = [
        'kode',
        'nama',
        'deskripsi',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_roles',
            'role_id',
            'user_id'
        )
        ->withPivot('unit_id')
        ->withTimestamps();
    }
}