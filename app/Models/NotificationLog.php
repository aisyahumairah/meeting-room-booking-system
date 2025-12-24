<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'channel',
        'recipient_email',
        'subject',
        'content',
        'status',
        'error_message',
        'metadata',
        'sent_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
    ];

    // Notification Types
    public const TYPE_WELCOME = 'welcome';
    public const TYPE_PASSWORD_RESET = 'password_reset';
    public const TYPE_BOOKING_CONFIRMED = 'booking_confirmed';
    public const TYPE_BOOKING_CANCELLED = 'booking_cancelled';
    public const TYPE_BOOKING_REMINDER = 'booking_reminder';
    public const TYPE_ROOM_STATUS_CHANGED = 'room_status_changed';

    // Statuses
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_PENDING = 'pending';

    /**
     * Get the user this notification was sent to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get human-readable type name.
     */
    public function getTypeNameAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_WELCOME => 'Welcome Email',
            self::TYPE_PASSWORD_RESET => 'Password Reset',
            self::TYPE_BOOKING_CONFIRMED => 'Booking Confirmed',
            self::TYPE_BOOKING_CANCELLED => 'Booking Cancelled',
            self::TYPE_BOOKING_REMINDER => 'Booking Reminder',
            self::TYPE_ROOM_STATUS_CHANGED => 'Room Status Changed',
            default => ucwords(str_replace('_', ' ', $this->type)),
        };
    }

    /**
     * Scope for notifications by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for failed notifications.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }
}
