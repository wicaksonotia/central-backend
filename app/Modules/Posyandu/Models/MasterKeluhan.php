<?php

namespace App\Modules\Posyandu\Models;

class MasterKeluhan extends PosyanduModel
{
    protected $connection = 'posyandu';

    protected $table = 'master_keluhan';

    protected $fillable = [
        'nama',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function pemeriksaanKeluhan()
    {
        return $this->hasMany(
            PemeriksaanKeluhan::class,
            'master_keluhan_id'
        );
    }
}