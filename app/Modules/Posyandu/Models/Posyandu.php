<?php

namespace App\Modules\Posyandu\Models;

class Posyandu extends PosyanduModel
{
    protected $connection = 'posyandu';

    protected $table = 'posyandu';

    protected $fillable = [
        'desa_id',
        'kode_posyandu',
        'nama_posyandu',
        'alamat',
        'nama_ketua',
        'no_hp',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function desa()
    {
        return $this->belongsTo(Desa::class, 'desa_id');
    }

    public function warga()
    {
        return $this->hasMany(Warga::class, 'posyandu_id');
    }

    public function kegiatan()
    {
        return $this->hasMany(KegiatanPosyandu::class, 'posyandu_id');
    }

    public function petugasPosyandu()
    {
        return $this->hasMany(PetugasPosyandu::class, 'posyandu_id');
    }

    public function posPemeriksaan()
    {
        return $this->hasMany(PosyanduPos::class, 'posyandu_id')
            ->with('posPemeriksaan')
            ->orderBy('urutan');
    }
}