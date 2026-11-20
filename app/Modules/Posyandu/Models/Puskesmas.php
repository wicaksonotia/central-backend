<?php

namespace App\Modules\Posyandu\Models;

class Puskesmas extends PosyanduModel
{
    protected $connection = 'posyandu';

    protected $table = 'puskesmas';

    protected $fillable = [
        'kode_puskesmas',
        'nama_puskesmas',
        'alamat',
        'no_hp',
        'email',
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
        return $this->belongsToMany(
            Desa::class,
            'puskesmas_desa',
            'puskesmas_id',
            'desa_id'
        )->withPivot(['id', 'status'])
            ->withTimestamps();
    }

    public function desaAktif()
    {
        return $this->desa()
            ->wherePivot('status', true)
            ->where('desa.status', true);
    }

    public function penugasanUser()
    {
        return $this->hasMany(PuskesmasUser::class, 'puskesmas_id');
    }

    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'puskesmas_user',
            'puskesmas_id',
            'user_id'
        )->withPivot(['id', 'peran', 'status'])
            ->withTimestamps();
    }

    public function usersAktif()
    {
        return $this->users()
            ->wherePivot('status', true)
            ->where('users.is_active', true);
    }
}