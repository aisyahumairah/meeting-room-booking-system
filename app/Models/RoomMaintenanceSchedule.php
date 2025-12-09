<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomMaintenanceSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'start_datetime',
        'end_datetime',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
    ];

    // =====================
    // RELATIONSHIPS
    // =====================

    /**
     * Get the room this maintenance schedule belongs to
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the user who created this maintenance schedule
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // =====================
    // SCOPES
    // =====================

    /**
     * Get currently active maintenance schedules (happening now)
     */
    public function scopeActive($query)
    {
        $now = Carbon::now();
        return $query->where('start_datetime', '<=', $now)
            ->where('end_datetime', '>=', $now);
    }

    /**
     * Get upcoming maintenance schedules (not yet started)
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_datetime', '>', Carbon::now());
    }

    /**
     * Get past maintenance schedules (already ended)
     */
    public function scopePast($query)
    {
        return $query->where('end_datetime', '<', Carbon::now());
    }

    /**
     * Get schedules that should start now (start time has passed but room not yet marked)
     * Used by the auto-status update command
     */
    public function scopeShouldStartNow($query)
    {
        $now = Carbon::now();
        return $query->where('start_datetime', '<=', $now)
            ->where('end_datetime', '>', $now)
            ->whereHas('room', function ($q) {
                $q->where('status', '!=', 'under_maintenance');
            });
    }

    /**
     * Get schedules that should end now (end time has passed but room still marked as maintenance)
     * Used by the auto-status update command
     */
    public function scopeShouldEndNow($query)
    {
        $now = Carbon::now();
        return $query->where('end_datetime', '<=', $now)
            ->whereHas('room', function ($q) {
                $q->where('status', 'under_maintenance');
            });
    }

    // =====================
    // ACCESSORS
    // =====================

    /**
     * Check if maintenance is currently active
     */
    public function getIsActiveAttribute(): bool
    {
        $now = Carbon::now();
        return $this->start_datetime <= $now && $this->end_datetime >= $now;
    }

    /**
     * Get formatted date range for display
     */
    public function getDateRangeAttribute(): string
    {
        $start = $this->start_datetime;
        $end = $this->end_datetime;

        if ($start->isSameDay($end)) {
            return $start->format('M d, Y') . ' (' . $start->format('g:i A') . ' - ' . $end->format('g:i A') . ')';
        }

        return $start->format('M d, Y g:i A') . ' - ' . $end->format('M d, Y g:i A');
    }

    /**
     * Get the status of this maintenance schedule
     */
    public function getStatusAttribute(): string
    {
        $now = Carbon::now();

        if ($this->end_datetime < $now) {
            return 'completed';
        }

        if ($this->start_datetime <= $now && $this->end_datetime >= $now) {
            return 'active';
        }

        return 'scheduled';
    }

    /**
     * Get status badge HTML for display
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'completed' => '<span class="badge bg-secondary">Completed</span>',
            'active' => '<span class="badge bg-warning">In Progress</span>',
            'scheduled' => '<span class="badge bg-info">Scheduled</span>',
            default => '<span class="badge bg-secondary">Unknown</span>',
        };
    }

    // =====================
    // METHODS
    // =====================

    /**
     * Check if this maintenance schedule conflicts with a given date/time range
     *
     * @param string $date Date in Y-m-d format
     * @param string $startTime Start time in H:i format
     * @param string $endTime End time in H:i format
     * @return bool
     */
    public function conflictsWith(string $date, string $startTime, string $endTime): bool
    {
        $checkStart = Carbon::parse($date . ' ' . $startTime);
        $checkEnd = Carbon::parse($date . ' ' . $endTime);

        // Check for overlap: maintenance starts before check ends AND maintenance ends after check starts
        return $this->start_datetime < $checkEnd && $this->end_datetime > $checkStart;
    }

    /**
     * Check if this maintenance schedule conflicts with given datetime range
     *
     * @param Carbon $startDatetime
     * @param Carbon $endDatetime
     * @return bool
     */
    public function conflictsWithDatetimes(Carbon $startDatetime, Carbon $endDatetime): bool
    {
        return $this->start_datetime < $endDatetime && $this->end_datetime > $startDatetime;
    }
}
