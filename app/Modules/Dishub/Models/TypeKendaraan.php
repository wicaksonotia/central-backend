<?php

namespace App\Modules\Dishub\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TypeKendaraan extends Model
{
    protected $connection = 'dishub';
    
    protected $table = 'tbl_type';

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
