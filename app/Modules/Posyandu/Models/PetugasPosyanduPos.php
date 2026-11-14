<?php

namespace App\Modules\Posyandu\Models;

class PetugasPosyanduPos extends PosyanduModel
{
    protected $connection = 'posyandu';

    protected $table = 'petugas_posyandu_pos';

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function petugasPosyandu()
    {
        return $this->belongsTo(PetugasPosyandu::class, 'petugas_posyandu_id');
    }

    public function posyanduPos()
    {
        return $this->belongsTo(
            PosyanduPos::class,
            'posyandu_pos_id'
        );
    }
}
