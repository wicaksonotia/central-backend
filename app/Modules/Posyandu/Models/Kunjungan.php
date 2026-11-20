<?php

namespace App\Modules\Posyandu\Models;

class Kunjungan extends PosyanduModel
{
    protected $connection = 'posyandu';

    protected $table = 'kunjungan';

    protected $fillable = [
        'kegiatan_id',
        'warga_id',
        'nomor_antrian',
        'waktu_daftar',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'nomor_antrian' => 'integer',
            'waktu_daftar' => 'datetime',
        ];
    }

    public function kegiatan()
    {
        return $this->belongsTo(KegiatanPosyandu::class, 'kegiatan_id');
    }

    public function warga()
    {
        return $this->belongsTo(Warga::class, 'warga_id');
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function kunjunganPos()
    {
        return $this->hasMany(KunjunganPos::class, 'kunjungan_id');
    }
}