<?php

namespace App\Modules\Posyandu\Models;

class PetugasPosyandu extends PosyanduModel
{
    protected $connection = 'posyandu';

    protected $table = 'petugas_posyandu';

    protected $fillable = [
        'posyandu_id',
        'user_id',
        'peran',
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
        return $this->belongsTo(Posyandu::class, 'posyandu_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function penugasanPos()
    {
        return $this->hasMany(
            PetugasPosyanduPos::class,
            'petugas_posyandu_id'
        )->with('posyanduPos.posPemeriksaan');
    }

    public function posyanduPos()
    {
        return $this->belongsToMany(
            PosyanduPos::class,
            'petugas_posyandu_pos',
            'petugas_posyandu_id',
            'posyandu_pos_id'
        )->withPivot('status')
            ->withTimestamps();
    }

    public function pemeriksaan()
    {
        return $this->hasMany(
            Pemeriksaan::class,
            'petugas_posyandu_id'
        );
    }
}
