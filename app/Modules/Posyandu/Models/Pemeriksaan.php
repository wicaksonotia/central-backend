<?php

namespace App\Modules\Posyandu\Models;

class Pemeriksaan extends PosyanduModel
{
    protected $connection = 'posyandu';

    protected $table = 'pemeriksaan';

    protected $fillable = [
        'kunjungan_pos_id',
        'jenis_pemeriksaan_id',
        'pengukuran_ke',
        'nilai_numerik',
        'nilai_teks',
        'keterangan',
        'petugas_posyandu_id',
        'waktu_pemeriksaan',
    ];

    protected function casts(): array
    {
        return [
            'pengukuran_ke' => 'integer',
            'nilai_numerik' => 'decimal:3',
            'waktu_pemeriksaan' => 'datetime',
        ];
    }

    public function kunjunganPos()
    {
        return $this->belongsTo(
            KunjunganPos::class,
            'kunjungan_pos_id'
        );
    }

    public function jenisPemeriksaan()
    {
        return $this->belongsTo(
            JenisPemeriksaan::class,
            'jenis_pemeriksaan_id'
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