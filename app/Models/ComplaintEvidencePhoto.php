<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintEvidencePhoto extends Model
{
    protected $connection = 'pengaduan';

    protected $table = 'complaint_evidence_photos';

    protected $fillable = [
        'complaint_id',
        'assignment_id',
        'user_id',
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

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(
            ComplaintAssignment::class,
            'assignment_id'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
