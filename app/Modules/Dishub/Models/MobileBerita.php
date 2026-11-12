<?php

namespace App\Modules\Dishub\Models;

use Illuminate\Database\Eloquent\Model;

class MobileBerita extends Model
{
    protected $connection = 'dishub';
    
    protected $table = 'tbl_mobile_berita';

    protected $primaryKey = 'id';

    public $timestamps = false;
}