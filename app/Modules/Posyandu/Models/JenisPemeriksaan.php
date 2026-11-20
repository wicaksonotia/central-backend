<?php

namespace App\Modules\Posyandu\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class JenisPemeriksaan extends PosyanduModel
{
    protected $connection = 'posyandu';

    protected $table = 'jenis_pemeriksaan';

    protected $fillable = [
        'pos_pemeriksaan_id',
        'kode',
        'nama',
        'satuan',
        'tipe_nilai',
        'keterangan',
        'wajib',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'wajib' => 'boolean',
            'status' => 'boolean',
        ];
    }

    public function posPemeriksaan()
    {
        return $this->belongsTo(
            PosPemeriksaan::class,
            'pos_pemeriksaan_id'
        );
    }

    public function kelompokSasaran(): BelongsToMany
    {
        return $this->belongsToMany(
            KelompokSasaran::class,
            'jenis_pemeriksaan_kelompok',
            'jenis_pemeriksaan_id',
            'kelompok_sasaran_id'
        )->withTimestamps();
    }

    public function pemeriksaan()
    {
        return $this->hasMany(
            Pemeriksaan::class,
            'jenis_pemeriksaan_id'
        );
    }
}