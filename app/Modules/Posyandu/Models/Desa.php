<?php

namespace App\Modules\Posyandu\Models;

class Desa extends PosyanduModel
{
    protected $connection = 'posyandu';
    
    protected $table = 'desa';

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function posyandu()
    {
        return $this->hasMany(Posyandu::class, 'desa_id');
    }
}