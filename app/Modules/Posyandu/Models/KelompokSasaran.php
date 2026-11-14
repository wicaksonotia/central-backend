<?php

namespace App\Modules\Posyandu\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class KelompokSasaran extends PosyanduModel
{
    protected $connection = 'posyandu';
    
    protected $table = 'kelompok_sasaran';

    protected function casts(): array
    {
        return [
            'usia_min_bulan' => 'integer',
            'usia_max_bulan' => 'integer',
            'status' => 'boolean',
        ];
    }

    public function jenisPemeriksaan(): BelongsToMany
    {
        return $this->belongsToMany(
            JenisPemeriksaan::class,
            'jenis_pemeriksaan_kelompok',
            'kelompok_sasaran_id',
            'jenis_pemeriksaan_id'
        )->withTimestamps();
    }
}