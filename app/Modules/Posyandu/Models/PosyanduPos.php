<?php

namespace App\Modules\Posyandu\Models;

class PosyanduPos extends PosyanduModel
{
    protected $connection = 'posyandu';

    protected $table = 'posyandu_pos';

    protected $fillable = [
        'posyandu_id',
        'pos_pemeriksaan_id',
        'urutan',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'status' => 'boolean',
        ];
    }

    public function posyandu()
    {
        return $this->belongsTo(Posyandu::class, 'posyandu_id');
    }

    public function posPemeriksaan()
    {
        return $this->belongsTo(
            PosPemeriksaan::class,
            'pos_pemeriksaan_id'
        );
    }

    public function jenisPemeriksaan()
    {
        return $this->hasMany(
            JenisPemeriksaan::class,
            'pos_pemeriksaan_id',
            'pos_pemeriksaan_id'
        );
    }

    public function petugasPos()
    {
        return $this->hasMany(
            PetugasPosyanduPos::class,
            'posyandu_pos_id'
        );
    }

    public function kunjunganPos()
    {
        return $this->hasMany(
            KunjunganPos::class,
            'posyandu_pos_id'
        );
    }
}