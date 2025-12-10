# Step 3.4: My Bookings

**Priority:** HIGH | **Ref:** §6.3.1 | **Dependencies:** Step 3.1  
**Status:** TODO

---

## Objective

Implement the personal booking list page where users can view, filter, and navigate to their bookings with both list and calendar views.

---

## Task 3.4.1: Add My Bookings Controller Methods

**File:** `app/Http/Controllers/BookingController.php`

Add these methods:

```php
/**
 * Display user's bookings list
 */
public function myBookings(Request $request)
{
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
 * Display a single booking
 */
public function show(Booking $booking)
{
    // Authorization: user can only view their own bookings
    if ($booking->user_id !== auth()->id() && !auth()->user()->canManageBookings()) {
        abort(403, 'You can only view your own bookings.');
    }

    $booking->load(['room', 'user', 'series.bookings', 'cancelledByUser']);

    return view('bookings.show', compact('booking'));
}

/**
 * Get user's bookings for calendar (AJAX)
 */
public function myBookingsCalendar(Request $request)
{
    $request->validate([
        'start' => 'required|date',
        'end' => 'required|date',
    ]);

    $bookings = Booking::with('room')
        ->forUser(auth()->id())
        ->whereBetween('booking_date', [$request->start, $request->end])
        ->get();

    $events = $bookings->map(function ($booking) {
        return [
            'id' => $booking->id,
            'title' => $booking->room->name,
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

protected function getStatusColor(string $status): string
{
    return match ($status) {
        'confirmed' => '#28a745', // Green
        'cancelled' => '#dc3545', // Red
        'completed' => '#6c757d', // Gray
        default => '#17a2b8',
    };
}
```

---

## Task 3.4.2: Add Routes

**File:** `routes/web.php`

```php
Route::middleware(['auth', 'check.active'])->group(function () {
    // ... existing routes ...

    // My Bookings
    Route::get('/my-bookings', [BookingController::class, 'myBookings'])->name('my-bookings');
    Route::get('/my-bookings/{booking}', [BookingController::class, 'show'])->name('my-bookings.show');
    
    // AJAX for calendar
    Route::get('/ajax/my-bookings/calendar', [BookingController::class, 'myBookingsCalendar'])
        ->name('ajax.my-bookings.calendar');
});
```

---

## Task 3.4.3: Create My Bookings List View

**File:** `resources/views/bookings/my.blade.php`

**Mockup Reference:** `mrbs-mock-up/pages/bookings-my.html`

```blade
@extends('layouts.app')

@section('title', 'My Bookings')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0">
            <span class="text-muted fw-light">Bookings /</span> My Bookings
        </h4>
        <div>
            <a href="{{ route('bookings.create') }}" class="btn btn-primary">
                <i class="bx bx-plus me-1"></i> New Booking
            </a>
        </div>
    </div>

    {{-- Quick Stats --}}
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
                            <h5 class="mb-0">{{ $stats['total'] }}</h5>
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
                                <i class="bx bx-check-circle"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Confirmed</small>
                            <h5 class="mb-0">{{ $stats['confirmed'] }}</h5>
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
                            <span class="avatar-initial rounded bg-label-secondary">
                                <i class="bx bx-check-double"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Completed</small>
                            <h5 class="mb-0">{{ $stats['completed'] }}</h5>
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
                                <i class="bx bx-x-circle"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Cancelled</small>
                            <h5 class="mb-0">{{ $stats['cancelled'] }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- View Toggle & Filters --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            {{-- View Toggle --}}
            <ul class="nav nav-pills" id="viewTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="list-tab" data-bs-toggle="tab" 
                            data-bs-target="#listView" type="button" role="tab">
                        <i class="bx bx-list-ul me-1"></i> List
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="calendar-tab" data-bs-toggle="tab" 
                            data-bs-target="#calendarView" type="button" role="tab">
                        <i class="bx bx-calendar me-1"></i> Calendar
                    </button>
                </li>
            </ul>

            {{-- Quick Filters --}}
            <div class="d-flex gap-2">
                <a href="{{ route('my-bookings', ['filter' => 'upcoming']) }}" 
                   class="btn btn-outline-primary btn-sm {{ request('filter') === 'upcoming' ? 'active' : '' }}">
                    Upcoming
                </a>
                <a href="{{ route('my-bookings', ['filter' => 'past']) }}" 
                   class="btn btn-outline-secondary btn-sm {{ request('filter') === 'past' ? 'active' : '' }}">
                    Past
                </a>
                <a href="{{ route('my-bookings') }}" 
                   class="btn btn-outline-dark btn-sm {{ !request('filter') ? 'active' : '' }}">
                    All
                </a>
            </div>
        </div>

        {{-- Advanced Filters --}}
        <div class="card-body border-bottom">
            <form action="{{ route('my-bookings') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select form-select-sm" name="status">
                        <option value="">All Statuses</option>
                        <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>
                <div class="col-md-3">
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
                    <label class="form-label">From Date</label>
                    <input type="date" class="form-control form-control-sm" name="date_from" 
                           value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" class="form-control form-control-sm" name="date_to" 
                           value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="{{ route('my-bookings') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                </div>
            </form>
        </div>

        {{-- Tab Content --}}
        <div class="tab-content">
            {{-- List View --}}
            <div class="tab-pane fade show active" id="listView" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Date & Time</th>
                                <th>Room</th>
                                <th>Purpose</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bookings as $booking)
                                <tr>
                                    <td>
                                        <a href="{{ route('my-bookings.show', $booking) }}">
                                            {{ $booking->reference_number }}
                                        </a>
                                        @if($booking->is_recurring)
                                            <i class="bx bx-repeat text-info" title="Recurring"></i>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $booking->booking_date->format('D, M d, Y') }}</div>
                                        <small class="text-muted">{{ $booking->time_range }}</small>
                                    </td>
                                    <td>
                                        <div>{{ $booking->room->name }}</div>
                                        <small class="text-muted">{{ $booking->room->floor_location }}</small>
                                    </td>
                                    <td>
                                        <span title="{{ $booking->purpose }}">
                                            {{ Str::limit($booking->purpose, 40) }}
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
                                                <a class="dropdown-item" href="{{ route('my-bookings.show', $booking) }}">
                                                    <i class="bx bx-show me-1"></i> View Details
                                                </a>
                                                @if($booking->is_editable)
                                                    <a class="dropdown-item" href="{{ route('my-bookings.edit', $booking) }}">
                                                        <i class="bx bx-edit me-1"></i> Edit
                                                    </a>
                                                @endif
                                                @if($booking->is_cancellable)
                                                    <a class="dropdown-item text-danger" href="#" 
                                                       onclick="confirmCancel({{ $booking->id }}, '{{ $booking->reference_number }}')">
                                                        <i class="bx bx-x me-1"></i> Cancel
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="bx bx-calendar-x bx-lg mb-2"></i>
                                            <p class="mb-2">No bookings found</p>
                                            <a href="{{ route('bookings.create') }}" class="btn btn-primary btn-sm">
                                                Create Your First Booking
                                            </a>
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
                        {{ $bookings->links() }}
                    </div>
                @endif
            </div>

            {{-- Calendar View --}}
            <div class="tab-pane fade" id="calendarView" role="tabpanel">
                <div class="card-body">
                    <div id="myBookingsCalendar" style="min-height: 600px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Cancel Confirmation Modal --}}
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="cancelForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to cancel booking <strong id="cancelRef"></strong>?</p>
                    <div class="mb-3">
                        <label class="form-label">Cancellation Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="cancellation_reason" rows="3" 
                                  required maxlength="500" placeholder="Please provide a reason..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Go Back</button>
                    <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let calendar;

    // Initialize calendar when tab is shown
    document.getElementById('calendar-tab').addEventListener('shown.bs.tab', function() {
        if (!calendar) {
            initCalendar();
        } else {
            calendar.updateSize();
        }
    });

    function initCalendar() {
        const calendarEl = document.getElementById('myBookingsCalendar');
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            slotMinTime: '08:00:00',
            slotMaxTime: '18:00:00',
            weekends: false,
            height: 'auto',
            events: function(info, successCallback, failureCallback) {
                fetch(`{{ route('ajax.my-bookings.calendar') }}?start=${info.startStr}&end=${info.endStr}`)
                    .then(response => response.json())
                    .then(data => successCallback(data))
                    .catch(error => failureCallback(error));
            },
            eventClick: function(info) {
                window.location.href = info.event.url;
                info.jsEvent.preventDefault();
            },
            eventDidMount: function(info) {
                // Add tooltip
                info.el.title = `${info.event.extendedProps.room}\n${info.event.extendedProps.purpose}`;
            }
        });
        calendar.render();
    }
});

function confirmCancel(bookingId, reference) {
    document.getElementById('cancelRef').textContent = reference;
    document.getElementById('cancelForm').action = `/my-bookings/${bookingId}`;
    new bootstrap.Modal(document.getElementById('cancelModal')).show();
}
</script>
@endpush
@endsection
```

---

## Task 3.4.4: Create Booking Detail View

**File:** `resources/views/bookings/show.blade.php`

```blade
@extends('layouts.app')

@section('title', 'Booking Details')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0">
            <span class="text-muted fw-light">My Bookings /</span> {{ $booking->reference_number }}
        </h4>
        <a href="{{ route('my-bookings') }}" class="btn btn-outline-secondary">
            <i class="bx bx-arrow-back me-1"></i> Back to My Bookings
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
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted">Reference Number</label>
                            <p class="fs-5 fw-semibold">{{ $booking->reference_number }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Status</label>
                            <p>{!! $booking->status_badge !!}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
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

                    <div class="mb-3">
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

                    @if($booking->is_recurring)
                        <div class="alert alert-info">
                            <i class="bx bx-repeat me-1"></i>
                            <strong>Recurring Booking</strong> - Part of series {{ $booking->series->reference_number }}
                            ({{ $booking->series->occurrence_count }} occurrences)
                        </div>
                    @endif
                </div>

                {{-- Actions --}}
                <div class="card-footer">
                    @if($booking->is_editable)
                        <a href="{{ route('my-bookings.edit', $booking) }}" class="btn btn-primary me-2">
                            <i class="bx bx-edit me-1"></i> Edit Booking
                        </a>
                    @endif
                    @if($booking->is_cancellable)
                        <button type="button" class="btn btn-outline-danger" 
                                onclick="confirmCancel({{ $booking->id }}, '{{ $booking->reference_number }}')">
                            <i class="bx bx-x me-1"></i> Cancel Booking
                        </button>
                    @endif
                </div>
            </div>

            {{-- Series Occurrences (if recurring) --}}
            @if($booking->is_recurring && $booking->series)
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Series Occurrences</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($booking->series->bookings()->orderBy('booking_date')->get() as $occurrence)
                                    <tr class="{{ $occurrence->id === $booking->id ? 'table-primary' : '' }}">
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

        {{-- Room Info Sidebar --}}
        <div class="col-md-4">
            <div class="card mb-4">
                <img src="{{ $booking->room->primary_image }}" class="card-img-top" 
                     alt="{{ $booking->room->name }}" style="height: 150px; object-fit: cover;">
                <div class="card-body">
                    <h5 class="card-title">{{ $booking->room->name }}</h5>
                    <p class="card-text">
                        <i class="bx bx-user me-1"></i> {{ $booking->room->capacity }} seats<br>
                        <i class="bx bx-map me-1"></i> {{ $booking->room->floor_location }}
                    </p>
                    <a href="{{ route('rooms.show', $booking->room) }}" class="btn btn-outline-primary btn-sm">
                        View Room Details
                    </a>
                </div>
            </div>

            {{-- Booking Info --}}
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Booking Information</h6>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Booked by</span>
                        <span>{{ $booking->user->name }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Created</span>
                        <span>{{ $booking->created_at->format('M d, Y H:i') }}</span>
                    </li>
                    @if($booking->updated_at != $booking->created_at)
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Last Updated</span>
                            <span>{{ $booking->updated_at->format('M d, Y H:i') }}</span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- Cancel Modal (same as in my.blade.php) --}}
@include('bookings.partials.cancel-modal')

@push('scripts')
<script>
function confirmCancel(bookingId, reference) {
    document.getElementById('cancelRef').textContent = reference;
    document.getElementById('cancelForm').action = `/my-bookings/${bookingId}`;
    new bootstrap.Modal(document.getElementById('cancelModal')).show();
}
</script>
@endpush
@endsection
```

---

## Task 3.4.5: Create Cancel Modal Partial

**File:** `resources/views/bookings/partials/cancel-modal.blade.php`

```blade
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="cancelForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to cancel booking <strong id="cancelRef"></strong>?</p>
                    <div class="alert alert-warning">
                        <i class="bx bx-info-circle me-1"></i>
                        This action cannot be undone. The room will become available for others to book.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cancellation Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="cancellation_reason" rows="3" 
                                  required maxlength="500" placeholder="Please provide a reason for cancellation..."></textarea>
                        <div class="form-text">Maximum 500 characters</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Go Back</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bx bx-x me-1"></i> Confirm Cancellation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
```

---

## Testing Requirements

**File:** `tests/Feature/MyBookingsTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyBookingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['status' => 'active']);
        $this->room = Room::factory()->create(['status' => 'active']);
    }

    public function test_user_can_view_my_bookings()
    {
        Booking::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->get(route('my-bookings'));

        $response->assertStatus(200);
        $response->assertViewIs('bookings.my');
        $response->assertViewHas('bookings');
    }

    public function test_user_only_sees_own_bookings()
    {
        $ownBooking = Booking::factory()->create(['user_id' => $this->user->id]);
        $otherBooking = Booking::factory()->create(); // Different user

        $response = $this->actingAs($this->user)
            ->get(route('my-bookings'));

        $response->assertSee($ownBooking->reference_number);
        $response->assertDontSee($otherBooking->reference_number);
    }

    public function test_user_can_view_booking_details()
    {
        $booking = Booking::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->get(route('my-bookings.show', $booking));

        $response->assertStatus(200);
        $response->assertViewIs('bookings.show');
        $response->assertSee($booking->reference_number);
    }

    public function test_user_cannot_view_others_booking()
    {
        $otherBooking = Booking::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('my-bookings.show', $otherBooking));

        $response->assertStatus(403);
    }

    public function test_filter_by_status_works()
    {
        Booking::factory()->create(['user_id' => $this->user->id, 'status' => 'confirmed']);
        Booking::factory()->cancelled()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->get(route('my-bookings', ['status' => 'confirmed']));

        $response->assertViewHas('bookings', function ($bookings) {
            return $bookings->count() === 1 && $bookings->first()->status === 'confirmed';
        });
    }

    public function test_stats_are_calculated_correctly()
    {
        Booking::factory()->count(2)->create(['user_id' => $this->user->id, 'status' => 'confirmed']);
        Booking::factory()->cancelled()->create(['user_id' => $this->user->id]);
        Booking::factory()->completed()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->get(route('my-bookings'));

        $response->assertViewHas('stats', function ($stats) {
            return $stats['total'] === 4 
                && $stats['confirmed'] === 2 
                && $stats['cancelled'] === 1
                && $stats['completed'] === 1;
        });
    }
}
```

---

## Acceptance Criteria

- [ ] My Bookings page displays at `/my-bookings`
- [ ] List shows only the logged-in user's bookings
- [ ] Stats cards show correct counts
- [ ] Filters by status, room, and date range work
- [ ] Quick filters (Upcoming, Past, All) work
- [ ] Calendar view shows bookings with correct colors
- [ ] Booking detail page shows all information
- [ ] Recurring bookings show series information
- [ ] Actions (Edit, Cancel) show based on status
- [ ] Users cannot view other users' bookings
- [ ] All tests pass

---

**Next:** [Step 3.5 - Edit Booking](./step-3.5-edit-booking.md)
