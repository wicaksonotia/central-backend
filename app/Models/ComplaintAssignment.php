<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComplaintAssignment extends Model
{
    protected $connection = 'pengaduan';

    protected $table = 'complaint_assignments';

    protected $fillable = [
        'complaint_id',
        'user_id',
        'assigned_by',
        'catatan',
        'ditugaskan_pada',
        'selesai_pada',
    ];

    protected function casts(): array
    {
        return [
            'ditugaskan_pada' => 'datetime',
            'selesai_pada' => 'datetime',
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

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function evidencePhotos(): HasMany
    {
        return $this->hasMany(ComplaintEvidencePhoto::class, 'assignment_id');
    }
}
