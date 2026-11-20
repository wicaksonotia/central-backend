<?php

namespace App\Modules\Posyandu\Models;

class Desa extends PosyanduModel
{
    protected $connection = 'posyandu';

    protected $table = 'desa';

    protected $fillable = [
        'kode_desa',
        'nama_desa',
        'kecamatan',
        'kabupaten',
        'provinsi',
        'status',
    ];

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

    public function puskesmas()
    {
        return $this->belongsToMany(
            Puskesmas::class,
            'puskesmas_desa',
            'desa_id',
            'puskesmas_id'
        )->withPivot(['id', 'status'])
            ->withTimestamps();
    }
}