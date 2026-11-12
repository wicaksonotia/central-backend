<?php

namespace App\Modules\Dishub\Models;

use Illuminate\Database\Eloquent\Model;

class MobileSlider extends Model
{
    protected $connection = 'dishub';
    
    protected $table = 'tbl_mobile_slider';

    public $timestamps = false;
}