<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    /**
     * Display a grid view of active rooms with search and filtering.
     */
    public function index(Request $request)
    {
        $query = Room::with(['amenities', 'images'])
            ->active()
            ->orderBy('name');

        // Search by name (case-insensitive)
        if ($request->filled('search')) {
            $query->where('name', 'ilike', '%' . $request->search . '%');
        }

        // Filter by minimum capacity
        if ($request->filled('capacity')) {
            $query->byCapacity((int) $request->capacity);
        }

        // Filter by amenities (AND logic - must have ALL selected)
        if ($request->filled('amenities')) {
            $amenityIds = is_array($request->amenities)
                ? $request->amenities
                : explode(',', $request->amenities);
            $query->withAmenities($amenityIds);
        }

        // Filter by date/time availability
        if ($request->filled(['date', 'start_time', 'end_time'])) {
            $date = $request->date;
            $startTime = $request->start_time;
            $endTime = $request->end_time;

            // Exclude rooms with conflicting confirmed bookings (if Booking model exists)
            if (class_exists(\App\Models\Booking::class)) {
                $query->whereDoesntHave('bookings', function ($q) use ($date, $startTime, $endTime) {
                    $q->where('booking_date', $date)
                        ->where('status', 'confirmed')
                        ->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime);
                });
            }

            // Exclude rooms with conflicting maintenance schedules
            $query->whereDoesntHave('maintenanceSchedules', function ($q) use ($date, $startTime, $endTime) {
                $q->where('start_datetime', '<', $date . ' ' . $endTime)
                    ->where('end_datetime', '>', $date . ' ' . $startTime);
            });
        }

        $rooms = $query->paginate(12)->withQueryString();
        $amenities = Amenity::orderBy('name')->get();

        return view('rooms.index', compact('rooms', 'amenities'));
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
