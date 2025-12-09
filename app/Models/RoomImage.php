<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class RoomImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'path',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    // =====================
    // RELATIONSHIPS
    // =====================

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    // =====================
    // ACCESSORS
    // =====================

    /**
     * Get full URL to image
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }

    // =====================
    // METHODS
    // =====================

    /**
     * Delete the image file from storage
     */
    public function deleteFile(): bool
    {
        return Storage::disk('public')->delete($this->path);
    }
}
