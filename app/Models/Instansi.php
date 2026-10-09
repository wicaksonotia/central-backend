<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Instansi extends Model
{
    protected $connection = 'pengaduan';

    protected $table = 'instansi';

    protected $fillable = [
        'nama',
        'singkatan',
        'alamat',
        'email',
        'telepon',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class, 'instansi_id');
    }
}