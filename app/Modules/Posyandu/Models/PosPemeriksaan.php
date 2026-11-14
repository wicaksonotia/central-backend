<?php

namespace App\Modules\Posyandu\Models;

class PosPemeriksaan extends PosyanduModel
{
    protected $connection = 'posyandu';

    protected $table = 'pos_pemeriksaan';

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'status' => 'boolean',
        ];
    }

    public function jenisPemeriksaan()
    {
        return $this->hasMany(
            JenisPemeriksaan::class,
            'pos_pemeriksaan_id'
        );
    }

    public function konfigurasiPosyandu()
    {
        return $this->hasMany(
            PosyanduPos::class,
            'pos_pemeriksaan_id'
        );
    }
}
