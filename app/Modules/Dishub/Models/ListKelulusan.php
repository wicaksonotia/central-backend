<?php

namespace App\Modules\Dishub\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListKelulusan extends Model
{
    protected $connection = 'dishub';
    
    protected $table = 'tbl_list_kelulusan';

    public $timestamps = false;

    public function hasilUji(): BelongsTo
    {
        return $this->belongsTo(
            HasilUji::class,
            'id_hasil_uji',
            'id_hasil_uji'
        );
    }
}
