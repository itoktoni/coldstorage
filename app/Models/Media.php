<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Media extends BaseModel
{
    use SoftDeletes;

    protected $table = 'media';

    protected $fillable = [
        'filename',
        'original_filename',
        'mime_type',
        'size',
        'disk',
        'path',
        'thumbnail_path',
        'alt',
        'title',
        'caption',
        'width',
        'height',
        'user_id',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    public static function field_name(): string
    {
        return 'filename';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the full URL to the file.
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * Get the full URL to the thumbnail.
     */
    public function getThumbnailAttribute(): ?string
    {
        if ($this->thumbnail_path && Storage::disk($this->disk)->exists($this->thumbnail_path)) {
            return Storage::disk($this->disk)->url($this->thumbnail_path);
        }

        return null;
    }

    /**
     * Check if the file is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Get human readable file size.
     */
    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Scope to filter by mime type prefix.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('mime_type', 'like', $type . '%');
    }

    /**
     * Scope to only images.
     */
    public function scopeImages($query)
    {
        return $query->where('mime_type', 'like', 'image/%');
    }
}