<?php

namespace App\Modules\Posyandu\Models;

class PemeriksaanKeluhan extends PosyanduModel
{
    protected $connection = 'posyandu';
    
    protected $table = 'pemeriksaan_keluhan';

    public function kunjunganPos()
    {
        return $this->belongsTo(KunjunganPos::class, 'kunjungan_pos_id');
    }

    public function masterKeluhan()
    {
        return $this->belongsTo(MasterKeluhan::class, 'master_keluhan_id');
    }

    public function petugas()
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }
}