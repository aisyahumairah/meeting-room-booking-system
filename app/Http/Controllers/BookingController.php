<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Room;
use App\Services\BookingService;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService,
        protected AuditService $auditService
    ) {}

    /**
     * Show booking creation form
     */
    public function create(Request $request)
    {
        $rooms = Room::active()
            ->with('amenities')
            ->orderBy('name')
            ->get();

        // Pre-fill room if coming from room detail page
        $selectedRoom = $request->has('room_id')
            ? Room::find($request->room_id)
            : null;

        // Pre-fill date/time if coming from calendar
        $prefilledDate = $request->get('date');
        $prefilledStartTime = $request->get('start_time');
        $prefilledEndTime = $request->get('end_time');

        return view('bookings.create', compact(
            'rooms',
            'selectedRoom',
            'prefilledDate',
            'prefilledStartTime',
            'prefilledEndTime'
        ));
    }

    /**
     * Store a new booking
     */
    public function store(StoreBookingRequest $request)
    {
        try {
            $booking = $this->bookingService->createBooking(
                $request->validated(),
                auth()->user()
            );

            // Log to audit trail
            $this->auditService->log(
                'booking_created',
                'booking',
                $booking->id,
                [
                    'reference' => $booking->reference_number,
                    'room' => $booking->room->name,
                    'date' => $booking->booking_date->format('Y-m-d'),
                    'time' => $booking->time_range,
                ]
            );

            return redirect()
                ->route('dashboard')
                ->with('success', "Booking confirmed! Reference: {$booking->reference_number}");
        } catch (\Exception $e) {
            Log::error('Booking creation failed', [
                'error' => $e->getMessage(),
                'data' => $request->validated(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['room_id' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified booking.
     */
    public function show(Booking $booking)
    {
        // Users can only view their own bookings unless they are Admin/Director
        $user = auth()->user();
        if ($booking->user_id !== $user->id && !$user->canManageBookings()) {
            abort(403, 'You are not authorized to view this booking.');
        }

        $booking->load(['room', 'user', 'series']);

        return view('bookings.show', compact('booking'));
    }

    /**
     * Check availability via AJAX
     */
    public function checkAvailability(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'booking_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $available = $this->bookingService->checkAvailability(
            $request->room_id,
            $request->booking_date,
            $request->start_time,
            $request->end_time,
            $request->exclude_booking_id
        );

        if (!$available) {
            $conflict = $this->bookingService->getConflictDetails(
                $request->room_id,
                $request->booking_date,
                $request->start_time,
                $request->end_time
            );

            return response()->json([
                'available' => false,
                'conflict' => $conflict,
            ]);
        }

        return response()->json([
            'available' => true,
        ]);
    }
}
