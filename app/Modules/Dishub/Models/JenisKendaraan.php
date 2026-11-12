<?php

namespace App\Modules\Dishub\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisKendaraan extends Model
{
    protected $connection = 'dishub';
    
    protected $table = 'tbl_jns_kend';

    protected $primaryKey = 'id_jns_kend';

    public $timestamps = false;

    public function kendaraan(): HasMany
    {
        return $this->hasMany(
            Kendaraan::class,
            'id_jns_kend',
            'id_jns_kend'
        );
    }
}
