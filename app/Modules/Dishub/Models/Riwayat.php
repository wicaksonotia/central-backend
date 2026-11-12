<?php

namespace App\Modules\Dishub\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Riwayat extends Model
{
    protected $connection = 'dishub';
    
    protected $table = 'tbl_riwayat';

    protected $primaryKey = 'id_riwayat';

    public $timestamps = false;

    public function kendaraan(): BelongsTo
    {
        return $this->belongsTo(
            Kendaraan::class,
            'id_kendaraan',
            'id_kendaraan'
        );
    }
}
