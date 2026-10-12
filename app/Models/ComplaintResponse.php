<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintResponse extends Model
{
    protected $connection = 'pengaduan';

    protected $table = 'complaint_responses';

    protected $fillable = [
        'complaint_id',
        'user_id',
        'isi',
        'untuk_publik',
    ];

    protected function casts(): array
    {
        return [
            'untuk_publik' => 'boolean',
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