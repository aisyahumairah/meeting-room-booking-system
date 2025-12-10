# Step 3.9: Booking Calendar

**Priority:** HIGH | **Ref:** §6.2.3 | **Dependencies:** Step 3.1, 3.2  
**Status:** TODO

---

## Objective

Implement a global booking calendar with day/week/month views using FullCalendar. Users see only their bookings, while Admin/Director see all bookings. Clicking empty slots navigates to booking creation.

---

## Task 3.9.1: Create Calendar Controller

```bash
php artisan make:controller CalendarController
```

**File:** `app/Http/Controllers/CalendarController.php`

```php
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
```

---

## Task 3.9.2: Add Calendar Routes

**File:** `routes/web.php`

```php
use App\Http\Controllers\CalendarController;

Route::middleware(['auth', 'check.active'])->group(function () {
    // ... existing routes ...

    // Global Calendar
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');
    Route::get('/ajax/calendar/events', [CalendarController::class, 'events'])
        ->name('ajax.calendar.events');
});
```

---

## Task 3.9.3: Create Global Calendar View

**File:** `resources/views/bookings/calendar.blade.php`

**Mockup Reference:** `mrbs-mock-up/pages/bookings-calendar.html`

```blade
@extends('layouts.app')

@section('title', 'Booking Calendar')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
<style>
    /* Calendar Styling */
    #bookingCalendar {
        min-height: 700px;
    }
    
    .fc-event {
        cursor: pointer;
        font-size: 0.8rem;
        border-radius: 4px;
    }
    
    .fc-daygrid-event {
        padding: 2px 4px;
    }
    
    .fc-timegrid-event {
        border-radius: 4px;
    }
    
    .fc-event:hover {
        opacity: 0.9;
    }
    
    /* Legend */
    .calendar-legend {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }
    
    .legend-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.85rem;
    }
    
    .legend-color {
        width: 16px;
        height: 16px;
        border-radius: 3px;
    }

    /* Event tooltip */
    .event-tooltip {
        position: absolute;
        z-index: 1050;
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        max-width: 280px;
        font-size: 13px;
    }
</style>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0">
            <span class="text-muted fw-light">Bookings /</span> Calendar
        </h4>
        <div class="d-flex gap-2">
            <a href="{{ route('bookings.create') }}" class="btn btn-primary">
                <i class="bx bx-plus me-1"></i> New Booking
            </a>
        </div>
    </div>

    <div class="row">
        {{-- Filters Sidebar --}}
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bx bx-filter-alt me-1"></i> Filters</h6>
                </div>
                <div class="card-body">
                    {{-- Room Filter --}}
                    <div class="mb-3">
                        <label class="form-label">Room</label>
                        <select class="form-select form-select-sm" id="roomFilter">
                            <option value="">All Rooms</option>
                            @foreach($rooms as $room)
                                <option value="{{ $room->id }}">{{ $room->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Date Jump --}}
                    <div class="mb-3">
                        <label class="form-label">Jump to Date</label>
                        <input type="date" class="form-control form-control-sm" id="dateJump" 
                               value="{{ date('Y-m-d') }}">
                    </div>

                    <button class="btn btn-outline-primary btn-sm w-100" id="todayBtn">
                        <i class="bx bx-calendar me-1"></i> Today
                    </button>
                </div>
            </div>

            {{-- Legend --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bx bx-palette me-1"></i> Legend</h6>
                </div>
                <div class="card-body">
                    <div class="calendar-legend flex-column">
                        <div class="legend-item">
                            <span class="legend-color" style="background-color: #28a745;"></span>
                            <span>Your Bookings</span>
                        </div>
                        @if($canViewAllBookings)
                            <div class="legend-item">
                                <span class="legend-color" style="background-color: #17a2b8;"></span>
                                <span>Others' Bookings</span>
                            </div>
                        @endif
                        <div class="legend-item">
                            <span class="legend-color" style="background-color: #6c757d;"></span>
                            <span>Completed</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Create --}}
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bx bx-info-circle me-1"></i> Tips</h6>
                </div>
                <div class="card-body small">
                    <ul class="mb-0">
                        <li>Click an empty slot to create a booking</li>
                        <li>Click an event to view details</li>
                        <li>Use room filter to focus on one room</li>
                        <li>Switch views using the buttons above the calendar</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Calendar --}}
        <div class="col-md-9">
            <div class="card">
                <div class="card-body">
                    <div id="bookingCalendar"></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Event Detail Tooltip (hidden by default) --}}
<div id="eventTooltip" class="event-tooltip" style="display: none;">
    <div class="tooltip-content"></div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('bookingCalendar');
    const roomFilter = document.getElementById('roomFilter');
    const dateJump = document.getElementById('dateJump');
    const todayBtn = document.getElementById('todayBtn');
    const tooltip = document.getElementById('eventTooltip');
    
    // Initialize FullCalendar
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        initialDate: new Date(),
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        slotMinTime: '08:00:00',
        slotMaxTime: '18:00:00',
        weekends: true,
        height: 'auto',
        navLinks: true,
        selectable: true,
        selectMirror: true,
        nowIndicator: true,
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        },
        slotLabelFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        },
        
        // Fetch events
        events: function(info, successCallback, failureCallback) {
            const roomId = roomFilter.value;
            let url = `{{ route('ajax.calendar.events') }}?start=${info.startStr}&end=${info.endStr}`;
            
            if (roomId) {
                url += `&room_id=${roomId}`;
            }

            fetch(url)
                .then(response => response.json())
                .then(data => successCallback(data))
                .catch(error => {
                    console.error('Failed to load events:', error);
                    failureCallback(error);
                });
        },

        // Click on event - navigate to detail
        eventClick: function(info) {
            if (info.event.url) {
                window.location.href = info.event.url;
                info.jsEvent.preventDefault();
            } else {
                // Show tooltip for events without URL (non-owned bookings for regular users)
                showEventTooltip(info.event, info.jsEvent);
            }
        },

        // Hover on event - show tooltip
        eventMouseEnter: function(info) {
            showEventTooltip(info.event, info.jsEvent);
        },

        eventMouseLeave: function(info) {
            hideEventTooltip();
        },

        // Click on empty slot - go to create booking
        select: function(info) {
            const roomId = roomFilter.value || '';
            const date = info.startStr.split('T')[0];
            const startTime = info.startStr.includes('T') 
                ? info.startStr.split('T')[1].substring(0, 5) 
                : '09:00';
            const endTime = info.endStr.includes('T') 
                ? info.endStr.split('T')[1].substring(0, 5) 
                : '10:00';

            // Navigate to create booking with pre-filled values
            window.location.href = `{{ route('bookings.create') }}?room_id=${roomId}&date=${date}&start_time=${startTime}&end_time=${endTime}`;
        },

        // Date click (in month view)
        dateClick: function(info) {
            // Switch to day view when clicking a date in month view
            if (calendar.view.type === 'dayGridMonth') {
                calendar.changeView('timeGridDay', info.date);
            }
        },

        // View change
        viewDidMount: function(info) {
            hideEventTooltip();
        },
    });

    calendar.render();

    // Room filter change
    roomFilter.addEventListener('change', function() {
        calendar.refetchEvents();
    });

    // Date jump
    dateJump.addEventListener('change', function() {
        calendar.gotoDate(this.value);
    });

    // Today button
    todayBtn.addEventListener('click', function() {
        calendar.today();
        dateJump.value = new Date().toISOString().split('T')[0];
    });

    // Event tooltip functions
    function showEventTooltip(event, jsEvent) {
        const props = event.extendedProps;
        const content = `
            <div class="mb-2">
                <strong class="text-primary">${props.room}</strong>
                ${props.isRecurring ? '<i class="bx bx-repeat text-info ms-1" title="Recurring"></i>' : ''}
            </div>
            <div class="small">
                <p class="mb-1"><i class="bx bx-time me-1"></i> ${event.start.toLocaleTimeString('en-GB', {hour: '2-digit', minute:'2-digit'})} - ${event.end.toLocaleTimeString('en-GB', {hour: '2-digit', minute:'2-digit'})} (${props.duration})</p>
                <p class="mb-1"><i class="bx bx-user me-1"></i> ${props.user}</p>
                <p class="mb-1"><i class="bx bx-info-circle me-1"></i> ${props.purpose}</p>
                <p class="mb-0"><span class="badge bg-${props.status === 'confirmed' ? 'success' : 'secondary'}">${props.status}</span></p>
            </div>
            ${event.url ? '<div class="mt-2 small text-muted">Click to view details</div>' : ''}
        `;

        tooltip.querySelector('.tooltip-content').innerHTML = content;
        tooltip.style.display = 'block';
        tooltip.style.left = (jsEvent.pageX + 10) + 'px';
        tooltip.style.top = (jsEvent.pageY + 10) + 'px';
    }

    function hideEventTooltip() {
        tooltip.style.display = 'none';
    }

    // Hide tooltip on scroll
    document.addEventListener('scroll', hideEventTooltip, true);
});
</script>
@endpush
@endsection
```

---

## Task 3.9.4: Update User Model for Permission Check

Ensure the `canManageBookings` method exists:

**File:** `app/Models/User.php`

```php
/**
 * Check if user can manage all bookings (Admin/Director)
 */
public function canManageBookings(): bool
{
    return in_array($this->role, ['administrator', 'director']);
}
```

---

## Task 3.9.5: Add Gate for Manage Bookings

**File:** `app/Providers/AppServiceProvider.php` or `AuthServiceProvider`

```php
use Illuminate\Support\Facades\Gate;

// In boot() method
Gate::define('manage-bookings', function ($user) {
    return $user->canManageBookings();
});
```

---

## Testing Requirements

**File:** `tests/Feature/BookingCalendarTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;
    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['status' => 'active', 'role' => 'regular_user']);
        $this->admin = User::factory()->create(['status' => 'active', 'role' => 'administrator']);
        $this->room = Room::factory()->create(['status' => 'active']);
    }

    public function test_user_can_view_calendar()
    {
        $response = $this->actingAs($this->user)
            ->get(route('calendar'));

        $response->assertStatus(200);
        $response->assertViewIs('bookings.calendar');
    }

    public function test_calendar_events_returns_own_bookings_only_for_regular_user()
    {
        $ownBooking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'status' => 'confirmed',
            'booking_date' => now()->format('Y-m-d'),
        ]);

        $otherBooking = Booking::factory()->create([
            'room_id' => $this->room->id,
            'status' => 'confirmed',
            'booking_date' => now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('ajax.calendar.events', [
                'start' => now()->startOfMonth()->format('Y-m-d'),
                'end' => now()->endOfMonth()->format('Y-m-d'),
            ]));

        $response->assertStatus(200);
        $events = $response->json();

        $this->assertCount(1, $events);
        $this->assertEquals($ownBooking->id, $events[0]['id']);
    }

    public function test_admin_sees_all_bookings()
    {
        Booking::factory()->count(3)->create([
            'room_id' => $this->room->id,
            'status' => 'confirmed',
            'booking_date' => now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('ajax.calendar.events', [
                'start' => now()->startOfMonth()->format('Y-m-d'),
                'end' => now()->endOfMonth()->format('Y-m-d'),
            ]));

        $events = $response->json();
        $this->assertCount(3, $events);
    }

    public function test_cancelled_bookings_not_shown()
    {
        Booking::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'status' => 'confirmed',
            'booking_date' => now()->format('Y-m-d'),
        ]);

        Booking::factory()->cancelled()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'booking_date' => now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('ajax.calendar.events', [
                'start' => now()->startOfMonth()->format('Y-m-d'),
                'end' => now()->endOfMonth()->format('Y-m-d'),
            ]));

        $events = $response->json();
        $this->assertCount(1, $events);
    }

    public function test_room_filter_works()
    {
        $room1 = Room::factory()->create();
        $room2 = Room::factory()->create();

        Booking::factory()->create([
            'user_id' => $this->admin->id,
            'room_id' => $room1->id,
            'status' => 'confirmed',
            'booking_date' => now()->format('Y-m-d'),
        ]);

        Booking::factory()->create([
            'user_id' => $this->admin->id,
            'room_id' => $room2->id,
            'status' => 'confirmed',
            'booking_date' => now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('ajax.calendar.events', [
                'start' => now()->startOfMonth()->format('Y-m-d'),
                'end' => now()->endOfMonth()->format('Y-m-d'),
                'room_id' => $room1->id,
            ]));

        $events = $response->json();
        $this->assertCount(1, $events);
    }

    public function test_events_have_correct_urls()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'confirmed',
            'booking_date' => now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('ajax.calendar.events', [
                'start' => now()->startOfMonth()->format('Y-m-d'),
                'end' => now()->endOfMonth()->format('Y-m-d'),
            ]));

        $events = $response->json();
        $this->assertStringContainsString('my-bookings', $events[0]['url']);
    }
}
```

---

## Acceptance Criteria

- [ ] Calendar page displays at `/calendar`
- [ ] Day, Week, and Month views work correctly
- [ ] Regular users see only their own bookings
- [ ] Admin/Director see all bookings
- [ ] Bookings are color-coded (green for own, blue for others, gray for completed)
- [ ] Room filter filters events correctly
- [ ] Date jump navigates to selected date
- [ ] Today button returns to current date
- [ ] Clicking an event navigates to its detail page
- [ ] Hovering shows event tooltip with details
- [ ] Clicking empty slot navigates to booking form with pre-filled values
- [ ] Cancelled bookings are not displayed
- [ ] Calendar respects operating hours (8:00-18:00)
- [ ] All tests pass

---

**Next:** [Step 3.10 - Status Auto-Update](./step-3.10-status-auto-update.md)
