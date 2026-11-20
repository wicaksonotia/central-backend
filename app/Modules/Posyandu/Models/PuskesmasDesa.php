<?php

namespace App\Modules\Posyandu\Models;

class PuskesmasDesa extends PosyanduModel
{
    protected $connection = 'posyandu';

    protected $table = 'puskesmas_desa';

    protected $fillable = [
        'puskesmas_id',
        'desa_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function puskesmas()
    {
        return $this->belongsTo(Puskesmas::class, 'puskesmas_id');
    }

    public function desa()
    {
        return $this->belongsTo(Desa::class, 'desa_id');
    }
}