<?php

namespace App\Modules\Dishub\Models;

use Illuminate\Database\Eloquent\Model;

class MobileDetailPersyaratan extends Model
{
    protected $connection = 'dishub';
    
    protected $table = 'tbl_mobile_detail_persyaratan';

    protected $primaryKey = 'id';

    public $timestamps = false;
}