<?php

namespace App\Modules\Posyandu\Models;

use Illuminate\Database\Eloquent\Model;

abstract class PosyanduModel extends Model
{
    protected $connection = 'posyandu';

    protected $guarded = ['id'];
}