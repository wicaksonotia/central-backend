<?php

namespace App\Modules\Posyandu\Models;

class KegiatanPosyandu extends PosyanduModel
{
    protected $connection = 'posyandu';
    
    protected $table = 'kegiatan_posyandu';

    protected function casts(): array
    {
        return [
            'tanggal_kegiatan' => 'date',
        ];
    }

    public function posyandu()
    {
        return $this->belongsTo(Posyandu::class, 'posyandu_id');
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function kunjungan()
    {
        return $this->hasMany(Kunjungan::class, 'kegiatan_id');
    }
}