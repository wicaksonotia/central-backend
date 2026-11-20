<?php

namespace App\Modules\Posyandu\Models;

class PemeriksaanKeluhan extends PosyanduModel
{
    protected $connection = 'posyandu';

    protected $table = 'pemeriksaan_keluhan';

    protected $fillable = [
        'kunjungan_pos_id',
        'master_keluhan_id',
        'keluhan_lainnya',
        'catatan',
        'petugas_posyandu_id',
    ];

    public function kunjunganPos()
    {
        return $this->belongsTo(
            KunjunganPos::class,
            'kunjungan_pos_id'
        );
    }

    public function masterKeluhan()
    {
        return $this->belongsTo(
            MasterKeluhan::class,
            'master_keluhan_id'
        );
    }

    public function petugasPosyandu()
    {
        return $this->belongsTo(
            PetugasPosyandu::class,
            'petugas_posyandu_id'
        );
    }
}