<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
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
            // Lock the room row to prevent race conditions
            $room = Room::lockForUpdate()->findOrFail($data['room_id']);

            // Double-check availability within transaction
            if (!$room->isAvailable($data['booking_date'], $data['start_time'], $data['end_time'])) {
                throw new \Exception('Room is no longer available for the selected time slot.');
            }

            // Determine the PIC (person in charge)
            $userId = $data['user_id'] ?? $booker->id;

            // Create the booking with confirmed status (auto-approval)
            $booking = Booking::create([
                'reference_number' => Booking::generateReferenceNumber(),
                'user_id' => $userId,
                'room_id' => $room->id,
                'booking_date' => $data['booking_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'purpose' => $data['purpose'],
                'status' => 'confirmed', // Auto-approval
            ]);

            // Log the booking creation
            Log::info('Booking created', [
                'booking_id' => $booking->id,
                'reference' => $booking->reference_number,
                'user_id' => $userId,
                'room_id' => $room->id,
                'date' => $data['booking_date'],
            ]);

            // TODO: Send email confirmation (Phase 4)
            // $this->sendConfirmationEmail($booking);

            return $booking;
        });
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
}
