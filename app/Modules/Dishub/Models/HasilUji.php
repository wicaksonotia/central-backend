<?php

namespace App\Modules\Dishub\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HasilUji extends Model
{
    protected $connection = 'dishub';
    
    protected $table = 'tbl_hasil_uji';

    protected $primaryKey = 'id_hasil_uji';

    public $timestamps = false;

    public function kendaraan(): BelongsTo
    {
        return $this->belongsTo(
            Kendaraan::class,
            'id_kendaraan',
            'id_kendaraan'
        );
    }

    public function daftarKelulusan(): HasMany
    {
        return $this->hasMany(
            ListKelulusan::class,
            'id_hasil_uji',
            'id_hasil_uji'
        );
    }

    public function daftar(): BelongsTo
    {
        return $this->belongsTo(
            Daftar::class,
            'id_daftar',
            'id_daftar'
        );
    }

    public function uji(): BelongsTo
    {
        return $this->belongsTo(
            Uji::class,
            'id_uji',
            'id_uji'
        );
    }
}
