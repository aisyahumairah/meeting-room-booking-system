# Step 2.4: Room Browsing (User View)

**Priority:** HIGH | **Ref:** §5.2.1, §5.4.1 | **Dependencies:** Step 2.1, Step 2.3

---

## Objective

Create user-facing room browsing with grid view and detailed room pages with availability calendar.

---

## Task 2.4.1: Room Controller (Public/User)

```bash
php artisan make:controller RoomController
```

**File:** `app/Http/Controllers/RoomController.php`

**Methods:**

| Method | Route | Description |
|--------|-------|-------------|
| index() | GET /rooms | Grid view of active rooms |
| show() | GET /rooms/{room} | Room detail page |
| availability() | GET /api/rooms/{room}/availability | AJAX endpoint for calendar |

---

## Task 2.4.2: Define Public Routes

**File:** `routes/web.php`

```php
Route::middleware(['auth', 'check.active'])->group(function () {
    // Room Browsing (all authenticated users)
    Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
    Route::get('/rooms/{room}', [RoomController::class, 'show'])->name('rooms.show');
});

// API route for calendar (authenticated)
Route::middleware(['auth'])->prefix('api')->group(function () {
    Route::get('/rooms/{room}/availability', [RoomController::class, 'availability'])
        ->name('api.rooms.availability');
});
```

---

## Task 2.4.3: Room Grid View

**File:** `resources/views/rooms/index.blade.php`

**Mockup Reference:** `mrbs-mock-up/pages/rooms-list.html` (grid/card view)

**Features:**
- Grid of room cards (responsive: 4 cols desktop, 2 tablet, 1 mobile)
- Each card shows:
  - Room image (primary or placeholder)
  - Room name
  - Capacity icon + number
  - Floor/location
  - Status badge (Available/Booked/Maintenance)
  - Amenity icons (first 4-5)
  - "View Details" button
- Only shows **Active** rooms (hide inactive)
- Result count: "X rooms found"
- Filter sidebar (handled in Step 2.5)

**Card Template:**
```blade
<div class="col-md-6 col-lg-4 col-xl-3 mb-4">
    <div class="card h-100 room-card">
        <img src="{{ $room->primary_image }}" class="card-img-top" alt="{{ $room->name }}">
        <div class="card-body">
            <h5 class="card-title">{{ $room->name }}</h5>
            <p class="text-muted mb-2">
                <i class="bx bx-user"></i> {{ $room->capacity }} people
                <span class="mx-2">|</span>
                <i class="bx bx-map"></i> {{ $room->floor_location }}
            </p>
            <div class="amenities mb-2">
                @foreach($room->amenities->take(4) as $amenity)
                    <span title="{{ $amenity->name }}">{!! $amenity->icon_html !!}</span>
                @endforeach
            </div>
            {!! $room->status_badge !!}
        </div>
        <div class="card-footer">
            <a href="{{ route('rooms.show', $room) }}" class="btn btn-primary w-100">
                View Details
            </a>
        </div>
    </div>
</div>
```

---

## Task 2.4.4: Room Detail View

**File:** `resources/views/rooms/show.blade.php`

**Mockup Reference:** `mrbs-mock-up/pages/rooms-detail.html`

**Sections:**

1. **Header Section**
   - Room name (large)
   - Status badge
   - Capacity with icon
   - Floor/location with icon

2. **Photo Gallery**
   - Primary image (large)
   - Thumbnail gallery
   - Lightbox on click

3. **Room Specifications**
   - Description text
   - Capacity
   - Floor/location

4. **Amenities List**
   - All amenities with icons and names

5. **Availability Calendar**
   - FullCalendar (week view)
   - Shows bookings for this room
   - Color-coded slots

6. **Action Button**
   - "Book This Room" button
   - Disabled if under maintenance

---

## Task 2.4.5: FullCalendar Integration

**Include in layout/page:**
```html
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
```

**Calendar Container:**
```blade
<div id="availability-calendar"></div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('availability-calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'timeGridDay,timeGridWeek'
        },
        slotMinTime: '08:00:00',
        slotMaxTime: '18:00:00',
        weekends: false,
        allDaySlot: false,
        slotDuration: '00:30:00',
        events: '{{ route("api.rooms.availability", $room) }}',
        eventColor: '#696cff',
        nowIndicator: true,
        selectable: false,
        eventClick: function(info) {
            // Show booking details tooltip/modal
        }
    });
    calendar.render();
});
</script>
@endpush
```

---

## Task 2.4.6: Availability API Endpoint

**Controller Method:**
```php
public function availability(Room $room, Request $request)
{
    $start = $request->get('start');
    $end = $request->get('end');
    
    $bookings = $room->bookings()
        ->where('status', 'confirmed')
        ->whereBetween('booking_date', [$start, $end])
        ->get();
    
    $events = $bookings->map(function ($booking) {
        $isOwn = $booking->user_id === auth()->id();
        
        return [
            'id' => $booking->id,
            'title' => $isOwn ? $booking->purpose : 'Booked',
            'start' => $booking->booking_date . 'T' . $booking->start_time,
            'end' => $booking->booking_date . 'T' . $booking->end_time,
            'color' => $isOwn ? '#28a745' : '#696cff',
            'extendedProps' => [
                'reference' => $booking->reference_number,
                'booker' => auth()->user()->canManageBookings() ? $booking->user->name : null,
            ]
        ];
    });

    // Add maintenance periods
    $maintenance = $room->maintenanceSchedules()
        ->where('start_datetime', '<=', $end)
        ->where('end_datetime', '>=', $start)
        ->get();

    foreach ($maintenance as $m) {
        $events->push([
            'title' => 'Maintenance',
            'start' => $m->start_datetime->toIso8601String(),
            'end' => $m->end_datetime->toIso8601String(),
            'color' => '#dc3545',
            'display' => 'background',
        ]);
    }

    return response()->json($events);
}
```

---

## Task 2.4.7: CSS Enhancements

**File:** `public/assets/css/rooms.css`

```css
.room-card {
    transition: transform 0.2s, box-shadow 0.2s;
}
.room-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}
.room-card .card-img-top {
    height: 180px;
    object-fit: cover;
}
.room-card .amenities i {
    font-size: 1.2rem;
    margin-right: 0.5rem;
    color: #697a8d;
}
.room-gallery img {
    cursor: pointer;
    transition: opacity 0.2s;
}
.room-gallery img:hover {
    opacity: 0.8;
}
```

---

## Task 2.4.8: Update Sidebar Navigation

**File:** `resources/views/layouts/partials/sidebar.blade.php`

Add Meeting Rooms menu item:
```blade
<li class="menu-item {{ request()->routeIs('rooms.*') ? 'active' : '' }}">
    <a href="{{ route('rooms.index') }}" class="menu-link">
        <i class="menu-icon bx bx-building"></i>
        <div>Meeting Rooms</div>
    </a>
</li>
```

---

## Task 2.4.9: Feature Tests

```bash
php artisan make:test Feature/RoomBrowsingTest
```

**Test Cases:**
- User can view room grid
- User only sees active rooms
- User can view room detail
- Availability API returns correct format
- Room under maintenance shows badge
- Book button disabled for maintenance room
- Admin sees more booking details than regular user

---

## Acceptance Criteria

- [x] Room grid displays all active rooms
- [x] Room cards show image, name, capacity, floor, amenities, status
- [x] Room detail page shows full information
- [x] Photo gallery with lightbox works
- [x] FullCalendar displays room bookings
- [x] Calendar shows 8AM-6PM, Mon-Fri
- [x] Maintenance periods shown in red background
- [x] "Book This Room" button links to booking form
- [x] Button disabled if room under maintenance
- [x] Responsive layout works on mobile
- [x] `php artisan test --filter=RoomBrowsingTest` passes

---

**Next:** [Step 2.5 - Room Search & Filtering](./step-2.5-room-search-filtering.md)
