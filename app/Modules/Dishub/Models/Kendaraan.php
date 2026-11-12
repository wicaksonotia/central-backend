<?php

namespace App\Modules\Dishub\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Kendaraan extends Model
{
    protected $connection = 'dishub';
    
    protected $table = 'tbl_kendaraan';

    protected $primaryKey = 'id_kendaraan';

    public $timestamps = false;

    public function type(): HasOne
    {
        return $this->hasOne(
            TypeKendaraan::class,
            'id_kendaraan',
            'id_kendaraan'
        );
    }

    public function sertifikasi(): HasOne
    {
        return $this->hasOne(
            Sertifikasi::class,
            'id_kendaraan',
            'id_kendaraan'
        );
    }

    public function jenisKendaraan(): HasOne
    {
        return $this->hasOne(
            JenisKendaraan::class,
            'id_jns_kend',
            'id_jns_kend'
        );
    }

    public function hasilUji(): HasMany
    {
        return $this->hasMany(
            HasilUji::class,
            'id_kendaraan',
            'id_kendaraan'
        );
    }

    public function riwayat(): HasMany
    {
        return $this->hasMany(
            Riwayat::class,
            'id_kendaraan',
            'id_kendaraan'
        );
    }
}
