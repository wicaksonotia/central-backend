<?php

namespace App\Modules\Dishub\Models;

use Illuminate\Database\Eloquent\Model;

class MobileMenu extends Model
{
    protected $connection = 'dishub';
    
    protected $table = 'tbl_mobile_menu';

    public $timestamps = false;
}