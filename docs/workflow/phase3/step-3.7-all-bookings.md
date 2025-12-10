# Step 3.7: All Bookings (Admin)

**Priority:** HIGH | **Ref:** §6.4.1 | **Dependencies:** Step 3.1  
**Status:** TODO

---

## Objective

Implement the administrative view of all bookings in the system with comprehensive filters, search, and management capabilities. Export functionality will be implemented in Phase 4.

---

## Task 3.7.1: Create Admin Booking Controller

```bash
php artisan make:controller Admin/BookingController
```

**File:** `app/Http/Controllers/Admin/BookingController.php`

```php
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
        protected AuditService $auditService
    ) {
        $this->middleware('can:manage-bookings');
    }

    /**
     * Display all bookings
     */
    public function index(Request $request)
    {
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
```

---

## Task 3.7.2: Add Admin Booking Routes

**File:** `routes/web.php`

```php
use App\Http\Controllers\Admin\BookingController as AdminBookingController;

Route::middleware(['auth', 'check.active'])->group(function () {
    // ... existing routes ...

    // Admin Booking Management
    Route::middleware('can:manage-bookings')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('bookings', AdminBookingController::class)
            ->except(['create', 'store']);
    });
});
```

---

## Task 3.7.3: Create All Bookings View

**File:** `resources/views/admin/bookings/index.blade.php`

**Mockup Reference:** `mrbs-mock-up/pages/bookings-all.html`

```blade
@extends('layouts.app')

@section('title', 'All Bookings')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0">
            <span class="text-muted fw-light">Admin /</span> All Bookings
        </h4>
        <div>
            {{-- Export buttons will be added in Phase 4 --}}
            <span class="text-muted small">Export: Coming in Phase 4</span>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row mb-4">
        <div class="col-sm-6 col-lg-3 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="bx bx-calendar"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Total Bookings</small>
                            <h5 class="mb-0">{{ number_format($stats['total']) }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="bx bx-calendar-check"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Today's Bookings</small>
                            <h5 class="mb-0">{{ $stats['today'] }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="bx bx-calendar-plus"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Upcoming</small>
                            <h5 class="mb-0">{{ $stats['upcoming'] }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="bx bx-calendar-x"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Cancelled (This Month)</small>
                            <h5 class="mb-0">{{ $stats['cancelled_this_month'] }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bookings Table --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            {{-- Quick Filters --}}
            <div class="d-flex gap-2">
                <a href="{{ route('admin.bookings.index', ['filter' => 'today']) }}" 
                   class="btn btn-outline-primary btn-sm {{ request('filter') === 'today' ? 'active' : '' }}">
                    Today
                </a>
                <a href="{{ route('admin.bookings.index', ['filter' => 'upcoming']) }}" 
                   class="btn btn-outline-success btn-sm {{ request('filter') === 'upcoming' ? 'active' : '' }}">
                    Upcoming
                </a>
                <a href="{{ route('admin.bookings.index', ['filter' => 'past']) }}" 
                   class="btn btn-outline-secondary btn-sm {{ request('filter') === 'past' ? 'active' : '' }}">
                    Past
                </a>
                <a href="{{ route('admin.bookings.index') }}" 
                   class="btn btn-outline-dark btn-sm {{ !request('filter') ? 'active' : '' }}">
                    All
                </a>
            </div>

            {{-- Search --}}
            <form action="{{ route('admin.bookings.index') }}" method="GET" class="d-flex gap-2">
                <input type="text" class="form-control form-control-sm" name="search" 
                       placeholder="Search reference or user..." value="{{ request('search') }}" style="width: 200px;">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bx bx-search"></i>
                </button>
            </form>
        </div>

        {{-- Advanced Filters --}}
        <div class="card-body border-bottom">
            <form action="{{ route('admin.bookings.index') }}" method="GET" class="row g-3" id="filterForm">
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select form-select-sm" name="status">
                        <option value="">All</option>
                        <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Room</label>
                    <select class="form-select form-select-sm" name="room_id">
                        <option value="">All Rooms</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}" {{ request('room_id') == $room->id ? 'selected' : '' }}>
                                {{ $room->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">User</label>
                    <select class="form-select form-select-sm" name="user_id">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From</label>
                    <input type="date" class="form-control form-control-sm" name="date_from" 
                           value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To</label>
                    <input type="date" class="form-control form-control-sm" name="date_to" 
                           value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>User</th>
                        <th>Room</th>
                        <th>Date & Time</th>
                        <th>Purpose</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                        <tr>
                            <td>
                                <a href="{{ route('admin.bookings.show', $booking) }}">
                                    {{ $booking->reference_number }}
                                </a>
                                @if($booking->is_recurring)
                                    <i class="bx bx-repeat text-info" title="Recurring"></i>
                                @endif
                            </td>
                            <td>
                                <div>{{ $booking->user->name }}</div>
                                <small class="text-muted">{{ $booking->user->email }}</small>
                            </td>
                            <td>
                                <div>{{ $booking->room->name }}</div>
                                <small class="text-muted">{{ $booking->room->floor_location }}</small>
                            </td>
                            <td>
                                <div>{{ $booking->booking_date->format('D, M d, Y') }}</div>
                                <small class="text-muted">{{ $booking->time_range }} ({{ $booking->duration }})</small>
                            </td>
                            <td>
                                <span title="{{ $booking->purpose }}">
                                    {{ Str::limit($booking->purpose, 30) }}
                                </span>
                            </td>
                            <td>{!! $booking->status_badge !!}</td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn btn-sm btn-icon btn-outline-secondary dropdown-toggle hide-arrow" 
                                            data-bs-toggle="dropdown">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a class="dropdown-item" href="{{ route('admin.bookings.show', $booking) }}">
                                            <i class="bx bx-show me-1"></i> View Details
                                        </a>
                                        @if($booking->status !== 'completed')
                                            <a class="dropdown-item" href="{{ route('admin.bookings.edit', $booking) }}">
                                                <i class="bx bx-edit me-1"></i> Edit
                                            </a>
                                        @endif
                                        @if($booking->status === 'confirmed')
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item text-danger" href="#" 
                                               onclick="confirmCancel(
                                                   {{ $booking->id }}, 
                                                   '{{ $booking->reference_number }}',
                                                   {{ $booking->is_recurring ? 'true' : 'false' }},
                                                   {{ $booking->is_recurring ? $booking->series->bookings()->where('status', 'confirmed')->count() : 0 }}
                                               )">
                                                <i class="bx bx-x me-1"></i> Cancel
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="bx bx-calendar-x bx-lg mb-2"></i>
                                    <p class="mb-0">No bookings found matching your criteria</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($bookings->hasPages())
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        Showing {{ $bookings->firstItem() }} to {{ $bookings->lastItem() }} of {{ $bookings->total() }} bookings
                    </small>
                    {{ $bookings->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

{{-- Cancel Modal --}}
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="cancelForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Booking (Admin)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>You are about to cancel booking <strong id="cancelRef"></strong>.</p>
                    
                    <div id="seriesWarning" class="alert alert-warning" style="display: none;">
                        <i class="bx bx-repeat me-1"></i>
                        This will cancel the <strong>entire series</strong> (<span id="seriesCount">0</span> bookings).
                    </div>

                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-1"></i>
                        The booking owner will be notified of this cancellation.
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Admin Cancellation Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="cancellation_reason" rows="3" 
                                  required maxlength="500" 
                                  placeholder="Reason for administrative cancellation..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Go Back</button>
                    <button type="submit" class="btn btn-danger">Cancel Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function confirmCancel(bookingId, reference, isRecurring, seriesCount) {
    document.getElementById('cancelRef').textContent = reference;
    document.getElementById('cancelForm').action = `{{ url('admin/bookings') }}/${bookingId}`;
    
    const seriesWarning = document.getElementById('seriesWarning');
    if (isRecurring && seriesCount > 0) {
        document.getElementById('seriesCount').textContent = seriesCount;
        seriesWarning.style.display = 'block';
    } else {
        seriesWarning.style.display = 'none';
    }
    
    new bootstrap.Modal(document.getElementById('cancelModal')).show();
}
</script>
@endpush
@endsection
```

---

## Task 3.7.4: Create Admin Booking Detail View

**File:** `resources/views/admin/bookings/show.blade.php`

```blade
@extends('layouts.app')

@section('title', 'Booking Details - Admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0">
            <span class="text-muted fw-light">Admin / Bookings /</span> {{ $booking->reference_number }}
        </h4>
        <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary">
            <i class="bx bx-arrow-back me-1"></i> Back to All Bookings
        </a>
    </div>

    <div class="row">
        {{-- Main Details --}}
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Booking Details</h5>
                    {!! $booking->status_badge !!}
                </div>
                <div class="card-body">
                    {{-- Booking Info --}}
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-muted">Reference Number</label>
                            <p class="fs-5 fw-semibold">{{ $booking->reference_number }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Booking Type</label>
                            <p>
                                @if($booking->is_recurring)
                                    <span class="badge bg-info">Recurring</span>
                                    {{ $booking->series->reference_number }}
                                @else
                                    <span class="badge bg-secondary">One-time</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-muted">Date</label>
                            <p class="fs-5">
                                <i class="bx bx-calendar me-1 text-primary"></i>
                                {{ $booking->booking_date->format('l, F d, Y') }}
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Time</label>
                            <p class="fs-5">
                                <i class="bx bx-time me-1 text-primary"></i>
                                {{ $booking->time_range }} ({{ $booking->duration }})
                            </p>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-muted">Purpose</label>
                        <p>{{ $booking->purpose }}</p>
                    </div>

                    @if($booking->status === 'cancelled')
                        <div class="alert alert-danger">
                            <h6 class="alert-heading mb-2">
                                <i class="bx bx-x-circle me-1"></i> Booking Cancelled
                            </h6>
                            <p class="mb-1"><strong>Reason:</strong> {{ $booking->cancellation_reason }}</p>
                            <p class="mb-0 small">
                                Cancelled by {{ $booking->cancelledByUser?->name ?? 'Unknown' }} 
                                on {{ $booking->cancelled_at?->format('M d, Y \a\t H:i') }}
                            </p>
                        </div>
                    @endif
                </div>

                {{-- Actions --}}
                <div class="card-footer">
                    @if($booking->status !== 'completed')
                        <a href="{{ route('admin.bookings.edit', $booking) }}" class="btn btn-primary me-2">
                            <i class="bx bx-edit me-1"></i> Edit Booking
                        </a>
                    @endif
                    @if($booking->status === 'confirmed')
                        <button type="button" class="btn btn-outline-danger" 
                                onclick="confirmCancel(
                                    {{ $booking->id }}, 
                                    '{{ $booking->reference_number }}',
                                    {{ $booking->is_recurring ? 'true' : 'false' }},
                                    {{ $booking->is_recurring ? $booking->series->bookings()->where('status', 'confirmed')->count() : 0 }}
                                )">
                            <i class="bx bx-x me-1"></i> Cancel Booking
                        </button>
                    @endif
                </div>
            </div>

            {{-- Series Occurrences --}}
            @if($booking->is_recurring && $booking->series)
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Series Occurrences ({{ $booking->series->bookings->count() }} total)</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($booking->series->bookings()->orderBy('booking_date')->get() as $occurrence)
                                    <tr class="{{ $occurrence->id === $booking->id ? 'table-primary' : '' }}">
                                        <td>{{ $occurrence->reference_number }}</td>
                                        <td>{{ $occurrence->booking_date->format('D, M d, Y') }}</td>
                                        <td>{{ $occurrence->time_range }}</td>
                                        <td>{!! $occurrence->status_badge !!}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="col-md-4">
            {{-- Booked By --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Booked By</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar me-3">
                            <span class="avatar-initial rounded-circle bg-label-primary">
                                {{ strtoupper(substr($booking->user->name, 0, 1)) }}
                            </span>
                        </div>
                        <div>
                            <h6 class="mb-0">{{ $booking->user->name }}</h6>
                            <small class="text-muted">{{ $booking->user->email }}</small>
                        </div>
                    </div>
                    <hr>
                    <div class="small">
                        <p class="mb-1"><strong>Role:</strong> {{ ucwords(str_replace('_', ' ', $booking->user->role)) }}</p>
                        <p class="mb-0"><strong>Department:</strong> {{ $booking->user->department ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            {{-- Room Info --}}
            <div class="card mb-4">
                <img src="{{ $booking->room->primary_image }}" class="card-img-top" 
                     alt="{{ $booking->room->name }}" style="height: 150px; object-fit: cover;">
                <div class="card-body">
                    <h5 class="card-title">{{ $booking->room->name }}</h5>
                    <p class="card-text">
                        <i class="bx bx-user me-1"></i> {{ $booking->room->capacity }} seats<br>
                        <i class="bx bx-map me-1"></i> {{ $booking->room->floor_location }}
                    </p>
                </div>
            </div>

            {{-- Timeline --}}
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Activity Timeline</h6>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Created</span>
                        <span>{{ $booking->created_at->format('M d, Y H:i') }}</span>
                    </li>
                    @if($booking->updated_at != $booking->created_at && $booking->status !== 'cancelled')
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Last Updated</span>
                            <span>{{ $booking->updated_at->format('M d, Y H:i') }}</span>
                        </li>
                    @endif
                    @if($booking->cancelled_at)
                        <li class="list-group-item d-flex justify-content-between text-danger">
                            <span>Cancelled</span>
                            <span>{{ $booking->cancelled_at->format('M d, Y H:i') }}</span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- Cancel Modal --}}
@include('admin.bookings.partials.cancel-modal')

@push('scripts')
<script>
function confirmCancel(bookingId, reference, isRecurring, seriesCount) {
    document.getElementById('cancelRef').textContent = reference;
    document.getElementById('cancelForm').action = `{{ url('admin/bookings') }}/${bookingId}`;
    
    const seriesWarning = document.getElementById('seriesWarning');
    if (isRecurring && seriesCount > 0) {
        document.getElementById('seriesCount').textContent = seriesCount;
        seriesWarning.style.display = 'block';
    } else {
        seriesWarning.style.display = 'none';
    }
    
    new bootstrap.Modal(document.getElementById('cancelModal')).show();
}
</script>
@endpush
@endsection
```

---

## Task 3.7.5: Create Admin Cancel Modal Partial

**File:** `resources/views/admin/bookings/partials/cancel-modal.blade.php`

```blade
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="cancelForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Booking (Admin)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>You are about to cancel booking <strong id="cancelRef"></strong>.</p>
                    
                    <div id="seriesWarning" class="alert alert-warning" style="display: none;">
                        <i class="bx bx-repeat me-1"></i>
                        This will cancel the <strong>entire series</strong> (<span id="seriesCount">0</span> bookings).
                    </div>

                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-1"></i>
                        The booking owner will be notified of this cancellation (Phase 4).
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Admin Cancellation Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="cancellation_reason" rows="3" 
                                  required maxlength="500" 
                                  placeholder="Reason for administrative cancellation..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Go Back</button>
                    <button type="submit" class="btn btn-danger">Cancel Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>
```

---

## Testing Requirements

**File:** `tests/Feature/AdminBookingTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminBookingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;
    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['status' => 'active', 'role' => 'administrator']);
        $this->regularUser = User::factory()->create(['status' => 'active', 'role' => 'regular_user']);
        $this->room = Room::factory()->create(['status' => 'active']);
    }

    public function test_admin_can_view_all_bookings()
    {
        Booking::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.bookings.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.bookings.index');
        $response->assertViewHas('bookings');
    }

    public function test_regular_user_cannot_access_admin_bookings()
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('admin.bookings.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_filter_by_status()
    {
        Booking::factory()->count(3)->create(['status' => 'confirmed']);
        Booking::factory()->cancelled()->count(2)->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.bookings.index', ['status' => 'cancelled']));

        $response->assertViewHas('bookings', function ($bookings) {
            return $bookings->count() === 2;
        });
    }

    public function test_admin_can_search_by_reference()
    {
        $booking = Booking::factory()->create(['reference_number' => 'BK-2025-00123']);
        Booking::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.bookings.index', ['search' => 'BK-2025-00123']));

        $response->assertViewHas('bookings', function ($bookings) use ($booking) {
            return $bookings->count() === 1 && $bookings->first()->id === $booking->id;
        });
    }

    public function test_admin_can_view_any_booking_detail()
    {
        $booking = Booking::factory()->create(['user_id' => $this->regularUser->id]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.bookings.show', $booking));

        $response->assertStatus(200);
        $response->assertSee($booking->reference_number);
    }

    public function test_admin_can_cancel_any_booking()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->regularUser->id,
            'status' => 'confirmed',
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.bookings.destroy', $booking), [
                'cancellation_reason' => 'Room needed for priority event',
            ]);

        $response->assertRedirect(route('admin.bookings.index'));
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
            'cancelled_by' => $this->admin->id,
        ]);
    }

    public function test_stats_are_displayed()
    {
        Booking::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.bookings.index'));

        $response->assertViewHas('stats');
    }
}
```

---

## Acceptance Criteria

- [ ] Admin can view all bookings at `/admin/bookings`
- [ ] Stats cards show correct totals
- [ ] Quick filters (Today, Upcoming, Past, All) work
- [ ] Search by reference number works
- [ ] Search by user name/email works
- [ ] Filter by status, room, user, and date range works
- [ ] Booking details page shows all information
- [ ] Admin can edit any booking
- [ ] Admin can cancel any confirmed booking
- [ ] Series info shows for recurring bookings
- [ ] Regular users cannot access admin booking pages
- [ ] Pagination works correctly
- [ ] All tests pass

---

**Next:** [Step 3.8 - Auto-Approval System](./step-3.8-auto-approval-system.md)
