<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateBookingRequest;
use App\Http\Requests\CancelBookingRequest;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Services\BookingService;
use App\Services\AuditService;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService,
        protected AuditService $auditService,
        protected \App\Services\BookingStatusService $statusService
    ) {}

    /**
     * Display all bookings
     */
    public function index(Request $request)
    {
        // Auto-complete expired bookings on page load
        $this->statusService->completeAllExpired();

        $query = Booking::with(['user', 'room', 'series', 'cancelledByUser'])
            ->orderBy('booking_date', 'desc')
            ->orderBy('start_time', 'desc');

        // Search by reference number or user
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by room
        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('booking_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('booking_date', '<=', $request->date_to);
        }

        // Filter by booking type
        if ($request->filled('booking_type')) {
            if ($request->booking_type === 'recurring') {
                $query->whereNotNull('series_id');
            } else {
                $query->whereNull('series_id');
            }
        }

        // Quick filters
        if ($request->filled('filter')) {
            switch ($request->filter) {
                case 'today':
                    $query->where('booking_date', now()->toDateString());
                    break;
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

        $bookings = $query->paginate(25)->withQueryString();

        // Get data for filters
        $rooms = Room::orderBy('name')->get();
        $users = User::active()->orderBy('name')->get();

        // Get summary stats
        $stats = [
            'total' => Booking::count(),
            'today' => Booking::whereDate('booking_date', now())->confirmed()->count(),
            'upcoming' => Booking::where('booking_date', '>', now()->toDateString())->confirmed()->count(),
            'cancelled_this_month' => Booking::whereMonth('cancelled_at', now()->month)
                ->cancelled()->count(),
        ];

        return view('admin.bookings.index', compact('bookings', 'rooms', 'users', 'stats'));
    }

    /**
     * Display specific booking
     */
    public function show(Booking $booking)
    {
        $booking->load(['user', 'room', 'series.bookings', 'cancelledByUser']);

        return view('admin.bookings.show', compact('booking'));
    }

    /**
     * Show edit form for any booking
     */
    public function edit(Booking $booking)
    {
        $rooms = Room::active()->orderBy('name')->get();
        $isSeriesEdit = $booking->is_recurring;

        return view('admin.bookings.edit', compact('booking', 'rooms', 'isSeriesEdit'));
    }

    /**
     * Update any booking
     */
    public function update(UpdateBookingRequest $request, Booking $booking)
    {
        try {
            if ($booking->is_recurring && $request->has('update_series')) {
                $series = $booking->series;
                $this->bookingService->updateSeries($series, $request->validated(), auth()->user());

                $this->auditService->log(
                    'booking_series_updated',
                    'booking_series',
                    $series->id,
                    [
                        'reference' => $series->reference_number,
                        'updated_by' => auth()->user()->name,
                        'is_admin_action' => true,
                    ]
                );

                $message = "Series updated successfully!";
            } else {
                $booking = $this->bookingService->updateBooking(
                    $booking,
                    $request->validated(),
                    auth()->user()
                );

                $this->auditService->log(
                    'booking_updated',
                    'booking',
                    $booking->id,
                    [
                        'reference' => $booking->reference_number,
                        'updated_by' => auth()->user()->name,
                        'is_admin_action' => true,
                    ]
                );

                $message = 'Booking updated successfully!';
            }

            return redirect()
                ->route('admin.bookings.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['room_id' => $e->getMessage()]);
        }
    }

    /**
     * Cancel any booking
     */
    public function destroy(CancelBookingRequest $request, Booking $booking)
    {
        try {
            if ($booking->is_recurring) {
                $series = $booking->series;
                $count = $this->bookingService->cancelSeries(
                    $series,
                    $request->cancellation_reason,
                    auth()->user()
                );

                $this->auditService->log(
                    'booking_series_cancelled',
                    'booking_series',
                    $series->id,
                    [
                        'reference' => $series->reference_number,
                        'cancelled_by' => auth()->user()->name,
                        'is_admin_action' => true,
                        'bookings_cancelled' => $count,
                        'reason' => $request->cancellation_reason,
                    ]
                );

                $message = "Series cancelled. {$count} bookings affected.";
            } else {
                $this->bookingService->cancelBooking(
                    $booking,
                    $request->cancellation_reason,
                    auth()->user()
                );

                $this->auditService->log(
                    'booking_cancelled',
                    'booking',
                    $booking->id,
                    [
                        'reference' => $booking->reference_number,
                        'cancelled_by' => auth()->user()->name,
                        'is_admin_action' => true,
                        'reason' => $request->cancellation_reason,
                    ]
                );

                $message = 'Booking cancelled successfully.';
            }

            return redirect()
                ->route('admin.bookings.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to cancel: ' . $e->getMessage()]);
        }
    }
}
