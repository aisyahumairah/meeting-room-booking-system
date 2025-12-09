<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false; // Only created_at, no updated_at

    protected $fillable = [
        'event_type',
        'actor_id',
        'actor_name',
        'target_type',
        'target_id',
        'details',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
    ];

    // Prevent updates and deletes (immutable)
    public static function boot()
    {
        parent::boot();

        static::updating(function ($model) {
            return false; // Prevent updates
        });

        static::deleting(function ($model) {
            return false; // Prevent deletes
        });
    }

    // Relationships
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    // Scopes
    public function scopeByEventType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    public function scopeByActor($query, int $userId)
    {
        return $query->where('actor_id', $userId);
    }

    public function scopeByTarget($query, string $type, int $id)
    {
        return $query->where('target_type', $type)->where('target_id', $id);
    }

    public function scopeBetweenDates($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    // Accessors
    public function getEventDisplayAttribute(): string
    {
        return match ($this->event_type) {
            'login_success' => 'Login Success',
            'login_failed' => 'Login Failed',
            'logout' => 'Logout',
            'password_reset_requested' => 'Password Reset Requested',
            'password_reset_completed' => 'Password Reset Completed',
            'password_changed' => 'Password Changed',
            'profile_updated' => 'Profile Updated',
            'user_created' => 'User Created',
            'user_updated' => 'User Updated',
            'user_deactivated' => 'User Deactivated',
            'room.created' => 'Room Created',
            'room.updated' => 'Room Updated',
            'room.deleted' => 'Room Deleted',
            'room.status_changed' => 'Room Status Changed',
            'room.maintenance_scheduled' => 'Room Maintenance Scheduled',
            'room.maintenance_cancelled' => 'Room Maintenance Cancelled',
            'room.image_uploaded' => 'Room Image Uploaded',
            'room.image_deleted' => 'Room Image Deleted',
            'booking_created' => 'Booking Created',
            'booking_updated' => 'Booking Updated',
            'booking_cancelled' => 'Booking Cancelled',
            'booking_approved' => 'Booking Approved',
            'booking_rejected' => 'Booking Rejected',
            default => ucwords(str_replace(['_', '.'], ' ', $this->event_type)),
        };
    }
}
