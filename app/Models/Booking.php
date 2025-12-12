<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference_number',
        'user_id',
        'room_id',
        'series_id',
        'booking_date',
        'start_time',
        'end_time',
        'purpose',
        'status',
        'cancellation_reason',
        'cancelled_by',
        'cancelled_at',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'cancelled_at' => 'datetime',
    ];

    // =====================
    // RELATIONSHIPS
    // =====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(BookingSeries::class, 'series_id');
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    // =====================
    // ACCESSORS
    // =====================

    /**
     * Get formatted duration (e.g., "2h 30m")
     */
    public function getDurationAttribute(): string
    {
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);
        $minutes = $start->diffInMinutes($end);

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        if ($hours > 0 && $mins > 0) {
            return "{$hours}h {$mins}m";
        } elseif ($hours > 0) {
            return "{$hours}h";
        } else {
            return "{$mins}m";
        }
    }

    /**
     * Get duration in minutes
     */
    public function getDurationMinutesAttribute(): int
    {
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);
        return $start->diffInMinutes($end);
    }

    /**
     * Get status badge HTML
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'confirmed' => '<span class="badge bg-success">Confirmed</span>',
            'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
            'completed' => '<span class="badge bg-secondary">Completed</span>',
            default => '<span class="badge bg-secondary">Unknown</span>',
        };
    }

    /**
     * Check if booking can be edited
     * Regular users: can edit confirmed bookings (changed from pending-only)
     * Admin/Director: can edit any status
     */
    public function getIsEditableAttribute(): bool
    {
        // Cannot edit completed or cancelled bookings
        if (in_array($this->status, ['completed', 'cancelled'])) {
            return false;
        }

        // Cannot edit past bookings
        if ($this->booking_date < now()->toDateString()) {
            return false;
        }

        return true;
    }

    /**
     * Check if booking can be cancelled
     */
    public function getIsCancellableAttribute(): bool
    {
        // Can only cancel confirmed bookings
        if ($this->status !== 'confirmed') {
            return false;
        }

        // Cannot cancel past bookings
        if ($this->booking_date < now()->toDateString()) {
            return false;
        }

        // Cannot cancel if booking already ended today
        if (
            $this->booking_date == now()->toDateString()
            && Carbon::parse($this->end_time)->lt(now())
        ) {
            return false;
        }

        return true;
    }

    /**
     * Check if this is a recurring booking
     */
    public function getIsRecurringAttribute(): bool
    {
        return $this->series_id !== null;
    }

    /**
     * Get formatted time range (e.g., "09:00 - 11:00")
     */
    public function getTimeRangeAttribute(): string
    {
        $start = Carbon::parse($this->start_time)->format('H:i');
        $end = Carbon::parse($this->end_time)->format('H:i');
        return "{$start} - {$end}";
    }

    // =====================
    // SCOPES
    // =====================

    /**
     * Filter by confirmed status
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Filter by completed status
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Filter by cancelled status
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Filter bookings for a specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Filter bookings for a specific room
     */
    public function scopeForRoom($query, int $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    /**
     * Filter upcoming bookings (date >= today)
     */
    public function scopeUpcoming($query)
    {
        return $query->where('booking_date', '>=', now()->toDateString())
            ->whereIn('status', ['confirmed']);
    }

    /**
     * Filter past bookings (date < today or completed)
     */
    public function scopePast($query)
    {
        return $query->where(function ($q) {
            $q->where('booking_date', '<', now()->toDateString())
                ->orWhere('status', 'completed');
        });
    }

    /**
     * Filter bookings on a specific date
     */
    public function scopeOnDate($query, string $date)
    {
        return $query->where('booking_date', $date);
    }

    /**
     * Filter bookings within a date range
     */
    public function scopeBetweenDates($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('booking_date', [$startDate, $endDate]);
    }

    // =====================
    // STATIC METHODS
    // =====================

    /**
     * Generate unique reference number (BK-2025-00001)
     */
    public static function generateReferenceNumber(): string
    {
        $year = now()->year;
        $prefix = "BK-{$year}-";

        // Get the last booking reference for this year
        $lastBooking = self::withTrashed()
            ->where('reference_number', 'like', $prefix . '%')
            ->orderBy('reference_number', 'desc')
            ->first();

        if ($lastBooking) {
            // Extract the number part and increment
            $lastNumber = (int) substr($lastBooking->reference_number, -5);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }
}
