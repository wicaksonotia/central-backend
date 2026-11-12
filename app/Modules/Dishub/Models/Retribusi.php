<?php

namespace App\Modules\Dishub\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Retribusi extends Model
{
    protected $connection = 'dishub';
    
    protected $table = 'tbl_retribusi';

    protected $primaryKey = 'id_retribusi';

    public $timestamps = false;

    public function uji(): BelongsTo
    {
        return $this->belongsTo(
            Uji::class,
            'id_uji',
            'id_uji'
        );
    }
}
