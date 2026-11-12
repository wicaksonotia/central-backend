<?php

namespace App\Modules\Dishub\Models;

use Illuminate\Database\Eloquent\Model;

class MobileJenisUji extends Model
{
    protected $connection = 'dishub';
    
    protected $table = 'tbl_mobile_jenis_uji';

    protected $primaryKey = 'id';

    public $timestamps = false;
}