<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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

    protected $appends = ['url'];

    protected function casts(): array
    {
        return [
            'ukuran' => 'integer',
        ];
    }

    public function getUrlAttribute(): ?string
    {
        if (empty($this->path)) {
            return null;
        }

        return Storage::url($this->path);
    }

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class, 'complaint_id');
    }
}