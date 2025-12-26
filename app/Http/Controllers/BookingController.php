<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\StoreRecurringBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Http\Requests\CancelBookingRequest;
use App\Models\Booking;
use App\Models\BookingSeries;
use App\Models\Room;
use App\Services\BookingService;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService,
        protected AuditService $auditService,
        protected \App\Services\BookingStatusService $statusService
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
                    'room_id' => $booking->room_id,
                    'user' => $booking->user->name,
                    'user_id' => $booking->user_id,
                    'date' => $booking->booking_date->format('Y-m-d'),
                    'time' => $booking->time_range,
                    'purpose' => \Illuminate\Support\Str::limit($booking->purpose, 100),
                    'created_by' => auth()->user()->name,
                    'is_on_behalf' => $booking->user_id !== auth()->id(),
                ]
            );

            return redirect()
                ->route('dashboard')
                ->with('success', "Booking confirmed! Reference: {$booking->reference_number}");
        } catch (\Exception $e) {
            Log::warning('Booking creation failed (possible race condition)', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'data' => $request->validated(),
            ]);

            // Check if it's an availability issue
            if (str_contains($e->getMessage(), 'available') || str_contains($e->getMessage(), 'taken')) {
                return back()
                    ->withInput()
                    ->with('error', 'Sorry, this time slot was just taken by another user. Please select a different time.')
                    ->withErrors([
                        'room_id' => 'The selected time slot is no longer available. Someone else just booked it.',
                    ]);
            }

            // Generic error
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

    /**
     * Show recurring booking creation form
     */
    public function createRecurring(Request $request)
    {
        $rooms = Room::active()
            ->with('amenities')
            ->orderBy('name')
            ->get();

        $selectedRoom = $request->has('room_id')
            ? Room::find($request->room_id)
            : null;

        return view('bookings.create-recurring', compact('rooms', 'selectedRoom'));
    }

    /**
     * Store a recurring booking series
     */
    public function storeRecurring(StoreRecurringBookingRequest $request)
    {
        try {
            $series = $this->bookingService->createRecurringBooking(
                $request->validated(),
                auth()->user()
            );

            // Log to audit trail
            $this->auditService->log(
                'booking_series_created',
                'booking_series',
                $series->id,
                [
                    'reference' => $series->reference_number,
                    'room' => $series->room->name,
                    'user' => $series->user->name,
                    'recurrence' => $series->recurrence_description,
                    'start_date' => $series->start_date->format('Y-m-d'),
                    'end_date' => $series->end_date->format('Y-m-d'),
                    'occurrences' => $series->bookings()->count(),
                    'created_by' => auth()->user()->name,
                ]
            );

            return redirect()
                ->route('my-bookings')
                ->with('success', "Recurring booking confirmed! {$series->bookings()->count()} bookings created. Reference: {$series->reference_number}");
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['room_id' => $e->getMessage()]);
        }
    }

    /**
     * Preview recurrence dates via AJAX
     */
    public function previewRecurrence(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'recurrence_type' => 'required|in:daily,weekly,monthly',
            'recurrence_interval' => 'required|integer|min:1',
            'days_of_week' => 'array',
            'day_of_month' => 'integer|between:1,31',
            'end_type' => 'required|in:by_date,by_occurrences',
            'end_date' => 'date',
            'occurrences' => 'integer|min:2|max:52',
        ]);

        $dates = $this->bookingService->calculateOccurrences($request->all());

        return response()->json([
            'dates' => array_map(fn($d) => [
                'date' => $d->format('Y-m-d'),
                'formatted' => $d->format('D, M d, Y'),
                'day_name' => $d->format('l'),
            ], $dates),
            'count' => count($dates),
        ]);
    }

    /**
     * Display user's bookings list
     */
    public function myBookings(Request $request)
    {
        // Auto-complete expired bookings
        $this->statusService->completeAllExpired();

        $query = Booking::with(['room', 'series'])
            ->forUser(auth()->id())
            ->orderBy('booking_date', 'desc')
            ->orderBy('start_time', 'desc');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        if ($request->filled('date_from')) {
            $query->where('booking_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('booking_date', '<=', $request->date_to);
        }

        if ($request->filled('filter')) {
            switch ($request->filter) {
                case 'upcoming':
                    $query->where('booking_date', '>=', now()->toDateString())
                        ->where('status', 'confirmed');
                    break;
                case 'past':
                    $query->where(function ($q) {
                        $q->where('booking_date', '<', now()->toDateString())
                            ->orWhere('status', 'completed');
                    });
                    break;
            }
        }

        $bookings = $query->paginate(20)->withQueryString();

        // Get rooms for filter dropdown
        $rooms = Room::orderBy('name')->get();

        // Get stats
        $stats = [
            'total' => Booking::forUser(auth()->id())->count(),
            'confirmed' => Booking::forUser(auth()->id())->confirmed()->count(),
            'completed' => Booking::forUser(auth()->id())->completed()->count(),
            'cancelled' => Booking::forUser(auth()->id())->cancelled()->count(),
        ];

        return view('bookings.my', compact('bookings', 'rooms', 'stats'));
    }


    /**
     * Get user's bookings for calendar (AJAX)
     */
    public function myBookingsCalendar(Request $request)
    {
        // Auto-complete expired bookings
        $this->statusService->completeAllExpired();

        $request->validate([
            'start' => 'required|string',
            'end' => 'required|string',
        ]);

        // FullCalendar sends ISO8601 dates (potentially with time), but we filter by date only
        $startDate = Carbon::parse($request->start)->format('Y-m-d');
        $endDate = Carbon::parse($request->end)->format('Y-m-d');

        $bookings = Booking::with('room')
            ->forUser(auth()->id())
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->whereIn('status', ['confirmed', 'completed'])
            ->get();

        $events = $bookings->map(function ($booking) {
            return [
                'id' => $booking->id,
                'title' => $booking->room->name,
                // FullCalendar expects 'start' and 'end' in ISO8601
                'start' => $booking->booking_date->format('Y-m-d') . 'T' . $booking->start_time,
                'end' => $booking->booking_date->format('Y-m-d') . 'T' . $booking->end_time,
                'url' => route('my-bookings.show', $booking),
                'backgroundColor' => $this->getStatusColor($booking->status),
                'borderColor' => $this->getStatusColor($booking->status),
                'extendedProps' => [
                    'reference' => $booking->reference_number,
                    'purpose' => $booking->purpose,
                    'status' => $booking->status,
                    'room' => $booking->room->name,
                    'is_recurring' => $booking->is_recurring,
                ],
            ];
        });

        return response()->json($events);
    }

    /**
     * Show edit form for a booking
     */
    public function edit(Booking $booking)
    {
        // Authorization check
        $user = auth()->user();

        if ($booking->user_id !== $user->id && !$user->canManageBookings()) {
            abort(403, 'You can only edit your own bookings.');
        }

        if (!$booking->is_editable && !$user->canManageBookings()) {
            return redirect()
                ->route('my-bookings.show', $booking)
                ->with('error', 'This booking cannot be edited.');
        }

        $rooms = Room::active()->orderBy('name')->get();
        $isSeriesEdit = $booking->is_recurring;

        return view('bookings.edit', compact('booking', 'rooms', 'isSeriesEdit'));
    }

    /**
     * Update a booking
     */
    public function update(UpdateBookingRequest $request, Booking $booking)
    {
        try {
            $user = auth()->user();
            $isAdminEdit = $booking->user_id !== $user->id;

            // Check if this is a series and handle accordingly
            if ($booking->is_recurring && $request->has('update_series')) {
                $series = $booking->series;
                $this->bookingService->updateSeries($series, $request->validated(), $user);

                $this->auditService->log(
                    'booking_series_updated',
                    'booking_series',
                    $series->id,
                    [
                        'reference' => $series->reference_number,
                        'updated_by' => $user->name,
                        'occurrences' => $series->bookings()->where('status', 'confirmed')->count(),
                    ]
                );

                $message = "Series updated! {$series->bookings()->where('status', 'confirmed')->count()} bookings affected.";
            } else {
                $booking = $this->bookingService->updateBooking($booking, $request->validated(), $user);

                $this->auditService->log(
                    'booking_updated',
                    'booking',
                    $booking->id,
                    [
                        'reference' => $booking->reference_number,
                        'updated_by' => $user->name,
                        'is_admin_edit' => $isAdminEdit,
                        'changes' => [
                            'room' => $booking->wasChanged('room_id') ? [
                                'from' => Room::find($booking->getOriginal('room_id'))?->name,
                                'to' => $booking->room->name,
                            ] : null,
                            'date' => $booking->wasChanged('booking_date') ? [
                                'from' => $booking->getOriginal('booking_date')->format('Y-m-d'),
                                'to' => $booking->booking_date->format('Y-m-d'),
                            ] : null,
                            'time' => $booking->wasChanged('start_time') || $booking->wasChanged('end_time') ? [
                                'from' => $booking->getOriginal('start_time') . '-' . $booking->getOriginal('end_time'),
                                'to' => $booking->time_range,
                            ] : null,
                        ],
                    ]
                );

                $message = 'Booking updated successfully!';
            }

            // Redirect based on who edited
            if ($isAdminEdit) {
                return redirect()
                    ->route('admin.bookings.index')
                    ->with('success', $message);
            }

            return redirect()
                ->route('my-bookings.show', $booking)
                ->with('success', $message);
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['room_id' => $e->getMessage()]);
        }
    }

    /**
     * Cancel a booking
     */
    public function destroy(CancelBookingRequest $request, Booking $booking)
    {
        $user = auth()->user();
        $isAdminCancel = $booking->user_id !== $user->id;

        try {
            // Check if this is a series - cancel entire series
            if ($booking->is_recurring) {
                $series = $booking->series;
                $count = $this->bookingService->cancelSeries(
                    $series,
                    $request->cancellation_reason,
                    $user
                );

                $this->auditService->log(
                    'booking_series_cancelled',
                    'booking_series',
                    $series->id,
                    [
                        'reference' => $series->reference_number,
                        'cancelled_by' => $user->name,
                        'reason' => $request->cancellation_reason,
                        'bookings_cancelled' => $count,
                        'room' => $series->room->name,
                        'series_owner' => $series->user->name,
                    ]
                );

                $message = "Series cancelled. {$count} bookings have been cancelled.";
            } else {
                $this->bookingService->cancelBooking(
                    $booking,
                    $request->cancellation_reason,
                    $user
                );

                $this->auditService->log(
                    'booking_cancelled',
                    'booking',
                    $booking->id,
                    [
                        'reference' => $booking->reference_number,
                        'cancelled_by' => $user->name,
                        'reason' => $request->cancellation_reason,
                        'is_admin_cancel' => $isAdminCancel,
                        'original_date' => $booking->booking_date->format('Y-m-d'),
                        'original_time' => $booking->time_range,
                        'room' => $booking->room->name,
                        'booking_owner' => $booking->user->name,
                    ]
                );

                $message = 'Booking cancelled successfully.';
            }

            // Redirect based on who cancelled
            if ($isAdminCancel) {
                return redirect()
                    ->route('admin.bookings.index')
                    ->with('success', $message);
            }

            return redirect()
                ->route('my-bookings')
                ->with('success', $message);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to cancel booking: ' . $e->getMessage()]);
        }
    }

    protected function getStatusColor(string $status): string
    {
        return match ($status) {
            'confirmed' => '#28a745', // Green
            'cancelled' => '#dc3545', // Red
            'completed' => '#6c757d', // Gray
            default => '#17a2b8',
        };
    }
}
