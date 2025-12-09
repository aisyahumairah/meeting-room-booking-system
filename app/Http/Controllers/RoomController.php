<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    /**
     * Display a grid view of active rooms.
     */
    public function index()
    {
        $rooms = Room::with(['amenities', 'images'])
            ->active()
            ->orderBy('name')
            ->paginate(12);

        $totalRooms = Room::active()->count();

        return view('rooms.index', compact('rooms', 'totalRooms'));
    }

    /**
     * Display the specified room's detail page.
     */
    public function show(Room $room)
    {
        // Only show active rooms (or allow admin to see all)
        if ($room->status !== 'active' && !auth()->user()->canManageRooms()) {
            abort(404);
        }

        $room->load(['amenities', 'images']);

        return view('rooms.show', compact('room'));
    }

    /**
     * Return room availability for FullCalendar.
     */
    public function availability(Room $room, Request $request)
    {
        $start = $request->get('start');
        $end = $request->get('end');

        $events = collect();

        // Add bookings if Booking model exists (Phase 3)
        if (class_exists(\App\Models\Booking::class)) {
            $bookings = $room->bookings()
                ->where('status', 'confirmed')
                ->whereBetween('booking_date', [$start, $end])
                ->get();

            foreach ($bookings as $booking) {
                $isOwn = $booking->user_id === auth()->id();

                $events->push([
                    'id' => $booking->id,
                    'title' => $isOwn ? $booking->purpose : 'Booked',
                    'start' => $booking->booking_date . 'T' . $booking->start_time,
                    'end' => $booking->booking_date . 'T' . $booking->end_time,
                    'color' => $isOwn ? '#28a745' : '#696cff',
                    'extendedProps' => [
                        'reference' => $booking->reference_number,
                        'booker' => auth()->user()->canManageBookings() ? $booking->user->name : null,
                    ]
                ]);
            }
        }

        // Add maintenance periods
        $maintenance = $room->maintenanceSchedules()
            ->where('start_datetime', '<=', $end)
            ->where('end_datetime', '>=', $start)
            ->get();

        foreach ($maintenance as $m) {
            $events->push([
                'title' => 'Maintenance: ' . ($m->reason ?? 'Scheduled'),
                'start' => $m->start_datetime->toIso8601String(),
                'end' => $m->end_datetime->toIso8601String(),
                'color' => '#dc3545',
                'display' => 'background',
            ]);
        }

        return response()->json($events);
    }
}
