<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Amenity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
    ];

    // =====================
    // RELATIONSHIPS
    // =====================

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class)->withTimestamps();
    }

    // =====================
    // ACCESSORS
    // =====================

    /**
     * Get icon HTML
     */
    public function getIconHtmlAttribute(): string
    {
        if ($this->icon) {
            return '<i class="bx ' . e($this->icon) . '"></i>';
        }
        return '<i class="bx bx-check"></i>';
    }
}
