<?php

namespace App\Modules\Dishub\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Uji extends Model
{
    protected $connection = 'dishub';
    
    protected $table = 'tbl_uji';

    protected $primaryKey = 'id_uji';

    public $timestamps = false;

    public function retribusi(): HasMany
    {
        return $this->hasMany(
            Retribusi::class,
            'id_uji',
            'id_uji'
        );
    }
}