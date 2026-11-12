<?php

namespace App\Modules\Dishub\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Daftar extends Model
{
    protected $connection = 'dishub';
    
    protected $table = 'tbl_daftar';

    protected $primaryKey = 'id_daftar';

    public $timestamps = false;

    public function retribusi(): BelongsTo
    {
        return $this->belongsTo(
            Retribusi::class,
            'id_retribusi',
            'id_retribusi'
        );
    }

    public function proses(): HasMany
    {
        return $this->hasMany(
            Proses::class,
            'id_daftar',
            'id_daftar'
        );
    }

    public function prosesTerakhir(): HasOne
    {
        return $this->hasOne(
            Proses::class,
            'id_daftar',
            'id_daftar'
        )->ofMany([
            'tgl_uji' => 'max',
            'id_proses' => 'max',
        ]);
    }
}