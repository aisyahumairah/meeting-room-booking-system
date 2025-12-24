<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'booking_confirmed',
        'booking_cancelled',
        'booking_reminder',
        'room_status_changed',
    ];

    protected $casts = [
        'booking_confirmed' => 'boolean',
        'booking_cancelled' => 'boolean',
        'booking_reminder' => 'boolean',
        'room_status_changed' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get default preferences.
     */
    public static function getDefaults(): array
    {
        return [
            'booking_confirmed' => true,
            'booking_cancelled' => true,
            'booking_reminder' => true,
            'room_status_changed' => true,
        ];
    }
}
