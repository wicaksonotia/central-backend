<?php

namespace App\Modules\Dishub\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Proses extends Model
{
    protected $connection = 'dishub';
    
    protected $table = 'tbl_proses';

    protected $primaryKey = 'id_proses';

    public $timestamps = false;

    public function daftar(): BelongsTo
    {
        return $this->belongsTo(
            Daftar::class,
            'id_daftar',
            'id_daftar'
        );
    }

    public function retribusi(): BelongsTo
    {
        return $this->belongsTo(
            Retribusi::class,
            'id_retribusi',
            'id_retribusi'
        );
    }
}
