<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Complaint extends Model
{
    protected $connection = 'pengaduan';

    protected $table = 'complaints';

    protected $fillable = [
        'nomor_pengaduan',
        'user_id',
        'category_id',
        'unit_id',
        'judul',
        'isi_pengaduan',
        'status',
        'latitude',
        'longitude',
        'alamat',
        'diajukan_pada',
        'selesai_pada',
        'ditutup_pada',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'diajukan_pada' => 'datetime',
            'selesai_pada' => 'datetime',
            'ditutup_pada' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(
            ComplaintCategory::class,
            'category_id'
        );
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ComplaintPhoto::class, 'complaint_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(
            ComplaintAssignment::class,
            'complaint_id'
        );
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(
            ComplaintStatusHistory::class,
            'complaint_id'
        );
    }

    public function responses(): HasMany
    {
        return $this->hasMany(
            ComplaintResponse::class,
            'complaint_id'
        );
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(
            ComplaintVerification::class,
            'complaint_id'
        );
    }
}