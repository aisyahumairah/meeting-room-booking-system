<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'capacity',
        'floor_location',
        'description',
        'status',
    ];

    protected $casts = [
        'capacity' => 'integer',
    ];

    // =====================
    // RELATIONSHIPS
    // =====================

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class)->withTimestamps();
    }

    public function images(): HasMany
    {
        return $this->hasMany(RoomImage::class)->orderBy('sort_order');
    }

    public function maintenanceSchedules(): HasMany
    {
        return $this->hasMany(RoomMaintenanceSchedule::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    // =====================
    // ACCESSORS
    // =====================

    /**
     * Get the primary image or default placeholder
     */
    public function getPrimaryImageAttribute(): string
    {
        $primary = $this->images()->where('is_primary', true)->first();

        if ($primary) {
            return asset('storage/' . $primary->path);
        }

        // Return first image if no primary set
        $first = $this->images()->first();
        if ($first) {
            return asset('storage/' . $first->path);
        }

        // Default placeholder
        return asset('assets/img/rooms/placeholder.png');
    }

    /**
     * Get status badge HTML
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'active' => '<span class="badge bg-success">Active</span>',
            'inactive' => '<span class="badge bg-secondary">Inactive</span>',
            'under_maintenance' => '<span class="badge bg-warning">Under Maintenance</span>',
            default => '<span class="badge bg-secondary">Unknown</span>',
        };
    }

    /**
     * Get human-readable status display
     */
    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            'active' => 'Active',
            'inactive' => 'Inactive',
            'under_maintenance' => 'Under Maintenance',
            default => 'Unknown',
        };
    }

    // =====================
    // SCOPES
    // =====================

    /**
     * Filter by active status only
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Filter rooms available for booking (active, not under maintenance)
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Filter by minimum capacity
     */
    public function scopeByCapacity($query, int $minCapacity)
    {
        return $query->where('capacity', '>=', $minCapacity);
    }

    /**
     * Filter by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Filter by amenities (rooms that have ALL specified amenities)
     */
    public function scopeWithAmenities($query, array $amenityIds)
    {
        if (empty($amenityIds)) {
            return $query;
        }

        return $query->whereHas('amenities', function ($q) use ($amenityIds) {
            $q->whereIn('amenities.id', $amenityIds);
        }, '=', count($amenityIds));
    }

    // =====================
    // METHODS
    // =====================

    /**
     * Check if room is available for a specific date/time range
     * (checks against confirmed bookings only)
     */
    public function isAvailable(string $date, string $startTime, string $endTime): bool
    {
        // Check if room is active
        if ($this->status !== 'active') {
            return false;
        }

        // Check for maintenance schedule conflicts
        $maintenanceConflict = $this->maintenanceSchedules()
            ->where('start_datetime', '<=', $date . ' ' . $endTime)
            ->where('end_datetime', '>=', $date . ' ' . $startTime)
            ->exists();

        if ($maintenanceConflict) {
            return false;
        }

        // Check for booking conflicts (only confirmed bookings)
        $bookingConflict = $this->bookings()
            ->where('booking_date', $date)
            ->where('status', 'confirmed')
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    // New booking starts during existing booking
                    $q->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime);
                });
            })
            ->exists();

        return !$bookingConflict;
    }

    /**
     * Check if room has any bookings (for deletion check)
     */
    public function hasBookings(): bool
    {
        // Check if Booking model exists (Phase 3)
        if (!class_exists(\App\Models\Booking::class)) {
            return false;
        }
        return $this->bookings()->exists();
    }

    /**
     * Get total booking count
     */
    public function getBookingCount(): int
    {
        // Check if Booking model exists (Phase 3)
        if (!class_exists(\App\Models\Booking::class)) {
            return 0;
        }
        return $this->bookings()->count();
    }

    /**
     * Check if room can be deleted
     */
    public function canBeDeleted(): bool
    {
        return !$this->hasBookings();
    }
}
