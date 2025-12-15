<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingSeries;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingService
{
    /**
     * Create a new booking with auto-approval (first-come-first-served)
     * 
     * @throws \Exception if room is not available
     */
    public function createBooking(array $data, User $booker): Booking
    {
        return DB::transaction(function () use ($data, $booker) {
            // CRITICAL: Lock the room row to prevent race conditions
            // This ensures only one booking can be created for a time slot
            // even if multiple requests arrive simultaneously
            $room = Room::lockForUpdate()->findOrFail($data['room_id']);

            // Verify room is still active (could have been deactivated)
            if ($room->status !== 'active') {
                throw new \Exception('This room is no longer available for booking.');
            }

            // Double-check availability within the transaction
            // This check happens AFTER locking, so it's authoritative
            if (
                !$this->checkAvailability(
                    $room->id,
                    $data['booking_date'],
                    $data['start_time'],
                    $data['end_time']
                )
            ) {
                // Get conflict details for better error message
                $conflict = $this->getConflictDetails(
                    $room->id,
                    $data['booking_date'],
                    $data['start_time'],
                    $data['end_time']
                );

                $message = $conflict['message'] ?? 'Room is no longer available for the selected time slot.';
                throw new \Exception($message);
            }

            // Determine the PIC (person in charge)
            $userId = $data['user_id'] ?? $booker->id;

            // Create the booking with confirmed status (AUTO-APPROVAL)
            // The race is won - this booking gets the slot
            $booking = Booking::create([
                'reference_number' => Booking::generateReferenceNumber(),
                'user_id' => $userId,
                'room_id' => $room->id,
                'booking_date' => $data['booking_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'purpose' => $data['purpose'],
                'status' => 'confirmed', // Auto-approval: immediate confirmation
            ]);

            Log::info('Booking auto-approved', [
                'booking_id' => $booking->id,
                'reference' => $booking->reference_number,
                'user_id' => $userId,
                'room_id' => $room->id,
                'date' => $data['booking_date'],
                'time' => $data['start_time'] . '-' . $data['end_time'],
            ]);

            // TODO: Queue email notification (Phase 4)
            // Note: Don't send email inside transaction

            return $booking;
        }, 5); // 5 retries on deadlock
    }

    /**
     * Check if a room is available for a specific time slot
     */
    public function checkAvailability(int $roomId, string $date, string $startTime, string $endTime, ?int $excludeBookingId = null): bool
    {
        $room = Room::find($roomId);

        if (!$room) {
            return false;
        }

        // Check room status
        if ($room->status !== 'active') {
            return false;
        }

        // Check for maintenance schedule conflicts
        $maintenanceConflict = $room->maintenanceSchedules()
            ->where('start_datetime', '<=', $date . ' ' . $endTime)
            ->where('end_datetime', '>=', $date . ' ' . $startTime)
            ->exists();

        if ($maintenanceConflict) {
            return false;
        }

        // Check for booking conflicts
        $conflictQuery = Booking::where('room_id', $roomId)
            ->where('booking_date', $date)
            ->where('status', 'confirmed')
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime);
                });
            });

        // Exclude current booking when editing
        if ($excludeBookingId) {
            $conflictQuery->where('id', '!=', $excludeBookingId);
        }

        return !$conflictQuery->exists();
    }

    /**
     * Get available time slots for a room on a given date
     */
    public function getAvailableSlots(int $roomId, string $date): array
    {
        $room = Room::find($roomId);

        if (!$room || $room->status !== 'active') {
            return [];
        }

        // Get all confirmed bookings for this room on this date
        $bookings = Booking::where('room_id', $roomId)
            ->where('booking_date', $date)
            ->where('status', 'confirmed')
            ->orderBy('start_time')
            ->get(['start_time', 'end_time']);

        // Get maintenance schedules
        $maintenance = $room->maintenanceSchedules()
            ->whereDate('start_datetime', '<=', $date)
            ->whereDate('end_datetime', '>=', $date)
            ->get();

        $slots = [];
        $currentTime = '08:00';
        $endOfDay = '18:00';

        // TODO: Calculate available slots based on bookings and maintenance
        // This is a simplified version - can be enhanced

        return $slots;
    }

    /**
     * Get conflict details for a time slot
     */
    public function getConflictDetails(int $roomId, string $date, string $startTime, string $endTime): ?array
    {
        $room = Room::find($roomId);

        if (!$room) {
            return ['type' => 'room_not_found', 'message' => 'Room not found.'];
        }

        if ($room->status !== 'active') {
            return ['type' => 'room_inactive', 'message' => 'Room is not available for booking.'];
        }

        // Check maintenance
        $maintenance = $room->maintenanceSchedules()
            ->where('start_datetime', '<=', $date . ' ' . $endTime)
            ->where('end_datetime', '>=', $date . ' ' . $startTime)
            ->first();

        if ($maintenance) {
            return [
                'type' => 'maintenance',
                'message' => 'Room is under maintenance during this time.',
                'reason' => $maintenance->reason,
            ];
        }

        // Check booking conflict
        $conflict = Booking::where('room_id', $roomId)
            ->where('booking_date', $date)
            ->where('status', 'confirmed')
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            })
            ->first();

        if ($conflict) {
            return [
                'type' => 'booking',
                'message' => 'Room is already booked during this time.',
                'existing_time' => $conflict->time_range,
            ];
        }

        return null;
    }

    /**
     * Create a recurring booking series with auto-approval
     * 
     * @throws \Exception if any occurrence conflicts
     */
    public function createRecurringBooking(array $data, User $booker): BookingSeries
    {
        // Calculate all occurrence dates
        $occurrenceDates = $this->calculateOccurrences($data);

        if (empty($occurrenceDates)) {
            throw new \Exception('No valid occurrence dates found for the recurrence pattern.');
        }

        // Check availability for ALL occurrences first
        $conflicts = $this->checkAllOccurrencesAvailability(
            $data['room_id'],
            $occurrenceDates,
            $data['start_time'],
            $data['end_time']
        );

        if (!empty($conflicts)) {
            throw new \Exception(
                'Room is not available on the following dates: ' .
                implode(', ', array_map(fn($d) => $d->format('M d, Y'), $conflicts))
            );
        }

        return DB::transaction(function () use ($data, $booker, $occurrenceDates) {
            // Lock the room to prevent race conditions
            $room = Room::lockForUpdate()->findOrFail($data['room_id']);

            // Determine the PIC
            $userId = $data['user_id'] ?? $booker->id;

            // Create the series record
            $series = BookingSeries::create([
                'reference_number' => BookingSeries::generateReferenceNumber(),
                'user_id' => $userId,
                'room_id' => $room->id,
                'recurrence_type' => $data['recurrence_type'],
                'recurrence_pattern' => $this->buildRecurrencePattern($data),
                'start_date' => $occurrenceDates[0],
                'end_date' => end($occurrenceDates),
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'purpose' => $data['purpose'],
            ]);

            // Create individual bookings for each occurrence
            foreach ($occurrenceDates as $date) {
                Booking::create([
                    'reference_number' => Booking::generateReferenceNumber(),
                    'user_id' => $userId,
                    'room_id' => $room->id,
                    'series_id' => $series->id,
                    'booking_date' => $date,
                    'start_time' => $data['start_time'],
                    'end_time' => $data['end_time'],
                    'purpose' => $data['purpose'],
                    'status' => 'confirmed', // Auto-approval
                ]);
            }

            Log::info('Recurring booking created', [
                'series_id' => $series->id,
                'reference' => $series->reference_number,
                'user_id' => $userId,
                'room_id' => $room->id,
                'occurrences' => count($occurrenceDates),
            ]);

            // TODO: Send single confirmation email for series (Phase 4)

            return $series;
        });
    }

    /**
     * Calculate all occurrence dates based on recurrence pattern
     */
    public function calculateOccurrences(array $data): array
    {
        $startDate = Carbon::parse($data['start_date']);
        $dates = [];

        // Calculate end date based on end_type
        if ($data['end_type'] === 'by_date') {
            $endDate = Carbon::parse($data['end_date']);
        } else {
            // by_occurrences - will calculate during iteration
            $endDate = $startDate->copy()->addYear(); // Max limit
        }

        $maxOccurrences = $data['end_type'] === 'by_occurrences'
            ? (int) $data['occurrences']
            : 365; // Maximum safety limit

        $interval = (int) ($data['recurrence_interval'] ?? 1);

        switch ($data['recurrence_type']) {
            case 'daily':
                $current = $startDate->copy();
                while ($current->lte($endDate) && count($dates) < $maxOccurrences) {
                    $dates[] = $current->copy();
                    $current->addDays($interval);
                }
                break;

            case 'weekly':
                $daysOfWeek = $data['days_of_week'] ?? [];
                $current = $startDate->copy()->startOfWeek();

                while ($current->lte($endDate) && count($dates) < $maxOccurrences) {
                    foreach ($daysOfWeek as $dayOfWeek) {
                        $day = $current->copy()->setISOWeekday($dayOfWeek);
                        if ($day->gte($startDate) && $day->lte($endDate) && count($dates) < $maxOccurrences) {
                            $dates[] = $day->copy();
                        }
                    }
                    $current->addWeeks($interval);
                }

                // Sort dates chronologically
                usort($dates, fn($a, $b) => $a->timestamp <=> $b->timestamp);
                break;

            case 'monthly':
                $dayOfMonth = (int) ($data['day_of_month'] ?? 1);
                $current = $startDate->copy()->setDay(min($dayOfMonth, $startDate->daysInMonth));

                if ($current->lt($startDate)) {
                    $current->addMonthsNoOverflow($interval);
                }

                while ($current->lte($endDate) && count($dates) < $maxOccurrences) {
                    // Handle months with fewer days
                    $adjusted = $current->copy();
                    if ($dayOfMonth > $adjusted->daysInMonth) {
                        $adjusted->setDay($adjusted->daysInMonth);
                    } else {
                        $adjusted->setDay($dayOfMonth);
                    }

                    $dates[] = $adjusted;
                    $current->addMonthsNoOverflow($interval);
                }
                break;
        }

        return $dates;
    }

    /**
     * Check availability for all occurrences and return conflicting dates
     */
    protected function checkAllOccurrencesAvailability(
        int $roomId,
        array $dates,
        string $startTime,
        string $endTime
    ): array {
        $conflicts = [];
        $room = Room::find($roomId);

        foreach ($dates as $date) {
            $dateString = $date->format('Y-m-d');

            if (!$room->isAvailable($dateString, $startTime, $endTime)) {
                $conflicts[] = $date;
            }
        }

        return $conflicts;
    }

    /**
     * Build recurrence pattern for storage
     */
    protected function buildRecurrencePattern(array $data): array
    {
        $pattern = [
            'interval' => (int) ($data['recurrence_interval'] ?? 1),
        ];

        switch ($data['recurrence_type']) {
            case 'weekly':
                $pattern['days_of_week'] = array_map('intval', $data['days_of_week'] ?? []);
                break;
            case 'monthly':
                $pattern['day_of_month'] = (int) ($data['day_of_month'] ?? 1);
                break;
        }

        return $pattern;
    }

    /**
     * Cancel an entire booking series
     */
    /**
     * Cancel a booking
     */
    public function cancelBooking(Booking $booking, string $reason, User $cancelledBy): Booking
    {
        return DB::transaction(function () use ($booking, $reason, $cancelledBy) {
            $booking->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_by' => $cancelledBy->id,
                'cancelled_at' => now(),
            ]);

            Log::info('Booking cancelled', [
                'booking_id' => $booking->id,
                'reference' => $booking->reference_number,
                'cancelled_by' => $cancelledBy->id,
                'reason' => $reason,
            ]);

            // TODO: Send cancellation email (Phase 4)
            // If admin cancelled someone else's booking, notify the owner

            return $booking->fresh();
        });
    }

    /**
     * Cancel an entire booking series
     */
    public function cancelSeries(BookingSeries $series, string $reason, User $cancelledBy): int
    {
        return DB::transaction(function () use ($series, $reason, $cancelledBy) {
            $count = $series->bookings()
                ->where('status', 'confirmed')
                ->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => $reason,
                    'cancelled_by' => $cancelledBy->id,
                    'cancelled_at' => now(),
                ]);

            Log::info('Booking series cancelled', [
                'series_id' => $series->id,
                'reference' => $series->reference_number,
                'cancelled_by' => $cancelledBy->id,
                'reason' => $reason,
                'bookings_cancelled' => $count,
            ]);

            // TODO: Send single cancellation email for series (Phase 4)

            return $count;
        });
    }

    /**
     * Update an existing booking
     * 
     * @throws \Exception if room is not available
     */
    public function updateBooking(Booking $booking, array $data, User $editor): Booking
    {
        return DB::transaction(function () use ($booking, $data, $editor) {
            // Lock the room to prevent race conditions
            $room = Room::lockForUpdate()->findOrFail($data['room_id']);

            // Check availability (excluding current booking)
            if (
                !$this->checkAvailability(
                    $data['room_id'],
                    $data['booking_date'],
                    $data['start_time'],
                    $data['end_time'],
                    $booking->id
                )
            ) {
                throw new \Exception('Room is no longer available for the selected time slot.');
            }

            // Track changes for audit
            $changes = [];
            if ($booking->room_id != $data['room_id']) {
                $changes['room'] = [
                    'from' => $booking->room->name,
                    'to' => $room->name,
                ];
            }
            if ($booking->booking_date->format('Y-m-d') != $data['booking_date']) {
                $changes['date'] = [
                    'from' => $booking->booking_date->format('Y-m-d'),
                    'to' => $data['booking_date'],
                ];
            }
            if ($booking->start_time != $data['start_time'] || $booking->end_time != $data['end_time']) {
                $changes['time'] = [
                    'from' => $booking->time_range,
                    'to' => $data['start_time'] . ' - ' . $data['end_time'],
                ];
            }

            // Update booking - status remains unchanged
            $booking->update([
                'room_id' => $data['room_id'],
                'booking_date' => $data['booking_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'purpose' => $data['purpose'],
            ]);

            Log::info('Booking updated', [
                'booking_id' => $booking->id,
                'editor_id' => $editor->id,
                'changes' => $changes,
            ]);

            // TODO: Send notification email (Phase 4)
            // If admin edited someone else's booking, notify the owner

            return $booking->fresh();
        });
    }

    /**
     * Update an entire booking series
     */
    public function updateSeries(BookingSeries $series, array $data, User $editor): BookingSeries
    {
        // Recalculate all occurrence dates with new pattern
        // This is complex - for now, only allow updating common fields

        return DB::transaction(function () use ($series, $data, $editor) {
            $room = Room::lockForUpdate()->findOrFail($data['room_id']);

            // Check availability for ALL existing occurrences with new time/room
            $conflicts = [];
            foreach ($series->bookings()->where('status', 'confirmed')->get() as $booking) {
                if (
                    !$this->checkAvailability(
                        $data['room_id'],
                        $booking->booking_date->format('Y-m-d'),
                        $data['start_time'],
                        $data['end_time'],
                        $booking->id
                    )
                ) {
                    $conflicts[] = $booking->booking_date;
                }
            }

            if (!empty($conflicts)) {
                throw new \Exception(
                    'Cannot update series. Room is not available on: ' .
                    implode(', ', array_map(fn($d) => $d->format('M d, Y'), $conflicts))
                );
            }

            // Update series
            $series->update([
                'room_id' => $data['room_id'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'purpose' => $data['purpose'],
            ]);

            // Update all confirmed bookings in series
            $series->bookings()->where('status', 'confirmed')->update([
                'room_id' => $data['room_id'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'purpose' => $data['purpose'],
            ]);

            Log::info('Booking series updated', [
                'series_id' => $series->id,
                'editor_id' => $editor->id,
                'occurrences_updated' => $series->bookings()->where('status', 'confirmed')->count(),
            ]);

            return $series->fresh();
        });
    }
}
