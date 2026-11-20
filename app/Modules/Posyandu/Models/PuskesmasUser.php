<?php

namespace App\Modules\Posyandu\Models;

class PuskesmasUser extends PosyanduModel
{
    protected $connection = 'posyandu';

    protected $table = 'puskesmas_user';

    protected $fillable = [
        'puskesmas_id',
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

    public function puskesmas()
    {
        return $this->belongsTo(Puskesmas::class, 'puskesmas_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}