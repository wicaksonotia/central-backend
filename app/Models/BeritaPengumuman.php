<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class BeritaPengumuman extends Model
{
    use SoftDeletes;

    protected $connection = 'pengaduan';

    protected $table = 'berita_pengumuman';

    protected $fillable = [
        'instansi_id',
        'jenis',
        'judul',
        'slug',
        'ringkasan',
        'konten',
        'gambar',
        'status',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    protected $appends = [
        'gambar_url',
    ];

    public function instansi(): BelongsTo
    {
        return $this->belongsTo(
            Instansi::class,
            'instansi_id'
        );
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function pengubah(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }

    public function getGambarUrlAttribute(): ?string
    {
        if (!$this->gambar) {
            return null;
        }

        return Storage::url($this->gambar);
    }

    public function scopePublished($query)
    {
        return $query
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeBerita($query)
    {
        return $query->where('jenis', 'berita');
    }

    public function scopePengumuman($query)
    {
        return $query->where('jenis', 'pengumuman');
    }
}
