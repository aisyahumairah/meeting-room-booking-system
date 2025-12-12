<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingSeries extends Model
{
    use HasFactory;

    protected $table = 'booking_series';

    protected $fillable = [
        'reference_number',
        'user_id',
        'room_id',
        'recurrence_type',
        'recurrence_pattern',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'purpose',
    ];

    protected $casts = [
        'recurrence_pattern' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
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

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'series_id');
    }

    // =====================
    // ACCESSORS
    // =====================

    /**
     * Get the number of occurrences in this series
     */
    public function getOccurrenceCountAttribute(): int
    {
        return $this->bookings()->count();
    }

    /**
     * Get human-readable recurrence description
     */
    public function getRecurrenceDescriptionAttribute(): string
    {
        $pattern = $this->recurrence_pattern;

        return match ($this->recurrence_type) {
            'daily' => "Every " . ($pattern['interval'] ?? 1) . " day(s)",
            'weekly' => $this->formatWeeklyDescription($pattern),
            'monthly' => "Monthly on day " . ($pattern['day_of_month'] ?? 1),
            default => 'Unknown pattern',
        };
    }

    private function formatWeeklyDescription(array $pattern): string
    {
        $dayNames = [
            1 => 'Mon',
            2 => 'Tue',
            3 => 'Wed',
            4 => 'Thu',
            5 => 'Fri',
            6 => 'Sat',
            7 => 'Sun'
        ];

        $days = $pattern['days_of_week'] ?? [];
        $dayLabels = array_map(fn($d) => $dayNames[$d] ?? '', $days);

        return "Weekly on " . implode(', ', $dayLabels);
    }

    // =====================
    // STATIC METHODS
    // =====================

    /**
     * Generate unique series reference number (BK-SERIES-2025-00001)
     */
    public static function generateReferenceNumber(): string
    {
        $year = now()->year;
        $prefix = "BK-SERIES-{$year}-";

        $lastSeries = self::where('reference_number', 'like', $prefix . '%')
            ->orderBy('reference_number', 'desc')
            ->first();

        if ($lastSeries) {
            $lastNumber = (int) substr($lastSeries->reference_number, -5);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }
}
