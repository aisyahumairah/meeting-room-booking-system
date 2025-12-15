<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AvailabilityController extends Controller
{
    /**
     * Check if a specific time slot is available
     */
    public function check(Request $request): JsonResponse
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'exclude_booking_id' => 'nullable|integer',
        ]);

        $room = Room::find($request->room_id);

        // Check room status
        if ($room->status !== 'active') {
            return response()->json([
                'available' => false,
                'reason' => 'room_inactive',
                'message' => 'This room is currently not available for booking.',
            ]);
        }

        // Check for maintenance
        $maintenance = $room->maintenanceSchedules()
            ->where('start_datetime', '<=', $request->date . ' ' . $request->end_time)
            ->where('end_datetime', '>=', $request->date . ' ' . $request->start_time)
            ->first();

        if ($maintenance) {
            return response()->json([
                'available' => false,
                'reason' => 'maintenance',
                'message' => 'Room is scheduled for maintenance during this time.',
                'maintenance_reason' => $maintenance->reason,
            ]);
        }

        // Check for booking conflicts
        $conflictQuery = Booking::where('room_id', $request->room_id)
            ->where('booking_date', $request->date)
            ->where('status', 'confirmed')
            ->where(function ($query) use ($request) {
                $query->where('start_time', '<', $request->end_time)
                    ->where('end_time', '>', $request->start_time);
            });

        // Exclude current booking when editing
        if ($request->exclude_booking_id) {
            $conflictQuery->where('id', '!=', $request->exclude_booking_id);
        }

        $conflict = $conflictQuery->first();

        if ($conflict) {
            return response()->json([
                'available' => false,
                'reason' => 'booked',
                'message' => 'This time slot is already booked.',
                'conflict' => [
                    'time' => $conflict->time_range,
                    'reference' => $conflict->reference_number,
                ],
            ]);
        }

        return response()->json([
            'available' => true,
            'message' => 'Time slot is available.',
        ]);
    }

    /**
     * Get all bookings for a room on a specific date (for calendar display)
     */
    public function roomSchedule(Request $request, Room $room): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $bookings = Booking::where('room_id', $room->id)
            ->where('booking_date', $request->date)
            ->where('status', 'confirmed')
            ->orderBy('start_time')
            ->get(['id', 'reference_number', 'start_time', 'end_time', 'purpose', 'user_id'])
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'reference' => $booking->reference_number,
                    'start_time' => $booking->start_time,
                    'end_time' => $booking->end_time,
                    'purpose' => $booking->purpose,
                    'is_mine' => $booking->user_id === auth()->id(),
                ];
            });

        // Also get maintenance schedules for the date
        $maintenance = $room->maintenanceSchedules()
            ->whereDate('start_datetime', '<=', $request->date)
            ->whereDate('end_datetime', '>=', $request->date)
            ->get()
            ->map(function ($m) use ($request) {
                return [
                    'type' => 'maintenance',
                    'reason' => $m->reason,
                    'start_time' => $m->start_datetime->format('H:i'),
                    'end_time' => $m->end_datetime->format('H:i'),
                ];
            });

        return response()->json([
            'room' => [
                'id' => $room->id,
                'name' => $room->name,
                'status' => $room->status,
            ],
            'date' => $request->date,
            'bookings' => $bookings,
            'maintenance' => $maintenance,
            'operating_hours' => [
                'start' => '08:00',
                'end' => '18:00',
            ],
        ]);
    }

    /**
     * Get available time slots for a room on a specific date
     */
    public function availableSlots(Request $request, Room $room): JsonResponse
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'duration' => 'nullable|integer|min:30|max:480', // Duration in minutes
        ]);

        $date = $request->date;
        $duration = $request->get('duration', 60); // Default 1 hour

        // Get all blocked time ranges
        $blockedSlots = collect();

        // Get confirmed bookings
        $bookings = Booking::where('room_id', $room->id)
            ->where('booking_date', $date)
            ->where('status', 'confirmed')
            ->orderBy('start_time')
            ->get();

        foreach ($bookings as $booking) {
            $blockedSlots->push([
                'start' => $booking->start_time,
                'end' => $booking->end_time,
                'type' => 'booking',
            ]);
        }

        // Get maintenance schedules
        $maintenance = $room->maintenanceSchedules()
            ->whereDate('start_datetime', '<=', $date)
            ->whereDate('end_datetime', '>=', $date)
            ->get();

        foreach ($maintenance as $m) {
            $blockedSlots->push([
                'start' => $m->start_datetime->format('H:i'),
                'end' => $m->end_datetime->format('H:i'),
                'type' => 'maintenance',
            ]);
        }

        // Calculate available slots
        $operatingStart = '08:00';
        $operatingEnd = '18:00';
        $slotIncrement = 30; // 30-minute increments

        $available = [];
        $current = strtotime($operatingStart);
        $end = strtotime($operatingEnd);

        while ($current < $end) {
            $slotStart = date('H:i', $current);
            $slotEnd = date('H:i', $current + ($duration * 60));

            // Check if slot end exceeds operating hours
            if (strtotime($slotEnd) > $end) {
                break;
            }

            // Check if slot overlaps with any blocked time
            $isBlocked = false;
            foreach ($blockedSlots as $blocked) {
                $blockedStart = strtotime($blocked['start']);
                $blockedEnd = strtotime($blocked['end']);
                $currentEnd = $current + ($duration * 60);

                if ($current < $blockedEnd && $currentEnd > $blockedStart) {
                    $isBlocked = true;
                    break;
                }
            }

            if (!$isBlocked) {
                $available[] = [
                    'start' => $slotStart,
                    'end' => $slotEnd,
                    'duration' => $duration,
                ];
            }

            $current += ($slotIncrement * 60);
        }

        return response()->json([
            'room' => $room->name,
            'date' => $date,
            'requested_duration' => $duration,
            'available_slots' => $available,
            'count' => count($available),
        ]);
    }
}
