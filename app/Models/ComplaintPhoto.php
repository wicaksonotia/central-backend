<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintPhoto extends Model
{
    protected $connection = 'pengaduan';

    protected $table = 'complaint_photos';

    protected $fillable = [
        'complaint_id',
        'path',
        'nama_file',
        'mime_type',
        'ukuran',
    ];

    protected function casts(): array
    {
        return [
            'ukuran' => 'integer',
        ];
    }

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class, 'complaint_id');
    }
}