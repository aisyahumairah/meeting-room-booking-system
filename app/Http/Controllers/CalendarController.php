<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CalendarController extends Controller
{
    /**
     * Display the global booking calendar
     */
    public function index()
    {
        $rooms = Room::active()->orderBy('name')->get();
        $canViewAllBookings = auth()->user()->canManageBookings();

        return view('bookings.calendar', compact('rooms', 'canViewAllBookings'));
    }

    /**
     * Get calendar events (AJAX)
     */
    public function events(Request $request): JsonResponse
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date',
            'room_id' => 'nullable|exists:rooms,id',
        ]);

        $user = auth()->user();
        $canViewAll = $user->canManageBookings();

        $query = Booking::with(['room', 'user'])
            ->whereBetween('booking_date', [$request->start, $request->end]);

        // Filter by room if specified
        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        // Regular users only see their own bookings
        if (!$canViewAll) {
            $query->where('user_id', $user->id);
        }

        // Only show confirmed and completed bookings (not cancelled)
        $query->whereIn('status', ['confirmed', 'completed']);

        $bookings = $query->get();

        $events = $bookings->map(function ($booking) use ($user, $canViewAll) {
            $isOwn = $booking->user_id === $user->id;

            return [
                'id' => $booking->id,
                'title' => $this->getEventTitle($booking, $canViewAll),
                'start' => $booking->booking_date->format('Y-m-d') . 'T' . $booking->start_time,
                'end' => $booking->booking_date->format('Y-m-d') . 'T' . $booking->end_time,
                'backgroundColor' => $this->getEventColor($booking, $isOwn),
                'borderColor' => $this->getEventColor($booking, $isOwn),
                'textColor' => '#ffffff',
                'url' => $isOwn
                    ? route('my-bookings.show', $booking)
                    : ($canViewAll ? route('admin.bookings.show', $booking) : null),
                'extendedProps' => [
                    'reference' => $booking->reference_number,
                    'room' => $booking->room->name,
                    'roomId' => $booking->room_id,
                    'purpose' => $booking->purpose,
                    'status' => $booking->status,
                    'user' => $booking->user->name,
                    'userEmail' => $booking->user->email,
                    'isOwn' => $isOwn,
                    'isRecurring' => $booking->is_recurring,
                    'duration' => $booking->duration,
                ],
            ];
        });

        return response()->json($events);
    }

    /**
     * Get the event title based on user role
     */
    protected function getEventTitle(Booking $booking, bool $canViewAll): string
    {
        if ($canViewAll) {
            return "{$booking->room->name} - {$booking->user->name}";
        }
        return $booking->room->name;
    }

    /**
     * Get event color based on status and ownership
     */
    protected function getEventColor(Booking $booking, bool $isOwn): string
    {
        if ($booking->status === 'completed') {
            return '#6c757d'; // Gray for completed
        }

        if ($isOwn) {
            return '#28a745'; // Green for own bookings
        }

        // For admin viewing others' bookings
        return '#17a2b8'; // Blue for others' bookings
    }
}
