<?php

namespace App\Modules\Posyandu\Models;

class Warga extends PosyanduModel
{
    protected $connection = 'posyandu';
    
    protected $table = 'warga';

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'status' => 'boolean',
        ];
    }

    public function posyandu()
    {
        return $this->belongsTo(Posyandu::class, 'posyandu_id');
    }

    public function kunjungan()
    {
        return $this->hasMany(Kunjungan::class, 'warga_id');
    }
}