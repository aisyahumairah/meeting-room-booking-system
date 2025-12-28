<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Amenity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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

    // =====================
    // SCOPES
    // =====================

    /**
     * Scope to only get active amenities
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get inactive amenities
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }
}
