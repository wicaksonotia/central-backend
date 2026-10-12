<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintStatusHistory extends Model
{
    protected $connection = 'pengaduan';

    protected $table = 'complaint_status_histories';

    // Tabel ini tidak memiliki kolom updated_at.
    public const UPDATED_AT = null;

    protected $fillable = [
        'complaint_id',
        'user_id',
        'status_sebelumnya',
        'status_baru',
        'catatan',
        'berubah_pada',
    ];

    protected function casts(): array
    {
        return [
            'berubah_pada' => 'datetime',
        ];
    }

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class, 'complaint_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}