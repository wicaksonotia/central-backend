<?php

namespace App\Modules\Posyandu\Models;

class KunjunganPos extends PosyanduModel
{
    protected $connection = 'posyandu';

    protected $table = 'kunjungan_pos';

    public function kunjungan()
    {
        return $this->belongsTo(Kunjungan::class);
    }

    public function posyanduPos()
    {
        return $this->belongsTo(
            PosyanduPos::class,
            'posyandu_pos_id'
        );
    }

    public function petugasPosyandu()
    {
        return $this->belongsTo(
            PetugasPosyandu::class,
            'petugas_posyandu_id'
        );
    }

    public function pemeriksaan()
    {
        return $this->hasMany(Pemeriksaan::class);
    }

    public function pemeriksaanKeluhan()
    {
        return $this->hasMany(PemeriksaanKeluhan::class);
    }
}
