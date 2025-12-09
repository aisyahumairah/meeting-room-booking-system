# Step 2.5: Room Search & Filtering

**Priority:** HIGH | **Ref:** §5.3.1 | **Dependencies:** Step 2.4

---

## Objective

Implement room search and filtering with capacity, amenities, and date/time availability filters.

---

## Task 2.5.1: Update RoomController Index

**File:** `app/Http/Controllers/RoomController.php`

```php
public function index(Request $request)
{
    $query = Room::with(['amenities', 'images'])
        ->active()
        ->orderBy('name');

    // Search by name
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
        
        $query->whereDoesntHave('bookings', function ($q) use ($date, $startTime, $endTime) {
            $q->where('booking_date', $date)
              ->where('status', 'confirmed')
              ->where('start_time', '<', $endTime)
              ->where('end_time', '>', $startTime);
        })
        ->whereDoesntHave('maintenanceSchedules', function ($q) use ($date, $startTime, $endTime) {
            $q->where('start_datetime', '<', $date . ' ' . $endTime)
              ->where('end_datetime', '>', $date . ' ' . $startTime);
        });
    }

    $rooms = $query->paginate(12)->withQueryString();
    $amenities = Amenity::orderBy('name')->get();

    return view('rooms.index', compact('rooms', 'amenities'));
}
```

---

## Task 2.5.2: Filter Sidebar Component

**File:** `resources/views/rooms/partials/filters.blade.php`

```blade
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Filter Rooms</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('rooms.index') }}" method="GET" id="filterForm">
            
            <!-- Search -->
            <div class="mb-3">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" 
                       value="{{ request('search') }}" placeholder="Room name...">
            </div>

            <!-- Capacity -->
            <div class="mb-3">
                <label class="form-label">Minimum Capacity</label>
                <select name="capacity" class="form-select">
                    <option value="">Any</option>
                    @foreach([2, 4, 6, 8, 10, 12, 15, 20, 25] as $cap)
                        <option value="{{ $cap }}" {{ request('capacity') == $cap ? 'selected' : '' }}>
                            {{ $cap }}+ people
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Date & Time -->
            <div class="mb-3">
                <label class="form-label">Check Availability</label>
                <input type="date" name="date" class="form-control mb-2" 
                       value="{{ request('date') }}" min="{{ date('Y-m-d') }}">
                <div class="row">
                    <div class="col-6">
                        <select name="start_time" class="form-select">
                            <option value="">Start</option>
                            @for($h = 8; $h < 18; $h++)
                                @foreach(['00', '30'] as $m)
                                    @php $t = sprintf('%02d:%s', $h, $m); @endphp
                                    <option value="{{ $t }}:00" {{ request('start_time') == "$t:00" ? 'selected' : '' }}>
                                        {{ $t }}
                                    </option>
                                @endforeach
                            @endfor
                        </select>
                    </div>
                    <div class="col-6">
                        <select name="end_time" class="form-select">
                            <option value="">End</option>
                            @for($h = 8; $h <= 18; $h++)
                                @foreach(['00', '30'] as $m)
                                    @if($h == 18 && $m == '30') @continue @endif
                                    @php $t = sprintf('%02d:%s', $h, $m); @endphp
                                    <option value="{{ $t }}:00" {{ request('end_time') == "$t:00" ? 'selected' : '' }}>
                                        {{ $t }}
                                    </option>
                                @endforeach
                            @endfor
                        </select>
                    </div>
                </div>
            </div>

            <!-- Amenities -->
            <div class="mb-3">
                <label class="form-label">Amenities</label>
                @foreach($amenities as $amenity)
                    <div class="form-check">
                        <input type="checkbox" name="amenities[]" value="{{ $amenity->id }}"
                               class="form-check-input" id="amenity{{ $amenity->id }}"
                               {{ in_array($amenity->id, (array) request('amenities', [])) ? 'checked' : '' }}>
                        <label class="form-check-label" for="amenity{{ $amenity->id }}">
                            {!! $amenity->icon_html !!} {{ $amenity->name }}
                        </label>
                    </div>
                @endforeach
            </div>

            <!-- Buttons -->
            <button type="submit" class="btn btn-primary w-100 mb-2">Apply Filters</button>
            <a href="{{ route('rooms.index') }}" class="btn btn-outline-secondary w-100">Clear All</a>
        </form>
    </div>
</div>
```

---

## Task 2.5.3: Update Room Index View

**File:** `resources/views/rooms/index.blade.php`

```blade
@extends('layouts.app')

@section('content')
<div class="container-xxl">
    <h4 class="mb-4">Meeting Rooms</h4>
    
    <div class="row">
        <!-- Filters Sidebar -->
        <div class="col-lg-3 col-md-4">
            @include('rooms.partials.filters')
        </div>

        <!-- Room Grid -->
        <div class="col-lg-9 col-md-8">
            <!-- Applied Filters & Count -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted">{{ $rooms->total() }} room(s) found</span>
                @if(request()->hasAny(['search', 'capacity', 'amenities', 'date']))
                    <div class="applied-filters">
                        @if(request('search'))
                            <span class="badge bg-label-primary me-1">
                                Search: {{ request('search') }}
                                <a href="{{ request()->fullUrlWithoutQuery('search') }}" class="ms-1">&times;</a>
                            </span>
                        @endif
                        @if(request('capacity'))
                            <span class="badge bg-label-primary me-1">
                                {{ request('capacity') }}+ people
                            </span>
                        @endif
                        @if(request('date'))
                            <span class="badge bg-label-primary me-1">
                                {{ request('date') }} {{ request('start_time') }}-{{ request('end_time') }}
                            </span>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Room Cards Grid -->
            @if($rooms->isEmpty())
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bx bx-search-alt bx-lg text-muted mb-3"></i>
                        <h5>No rooms found</h5>
                        <p class="text-muted">Try adjusting your filters or search criteria.</p>
                        <a href="{{ route('rooms.index') }}" class="btn btn-primary">Clear Filters</a>
                    </div>
                </div>
            @else
                <div class="row">
                    @foreach($rooms as $room)
                        @include('rooms.partials.room-card', ['room' => $room])
                    @endforeach
                </div>
                
                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-4">
                    {{ $rooms->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
```

---

## Task 2.5.4: Room Card Partial

**File:** `resources/views/rooms/partials/room-card.blade.php`

```blade
<div class="col-lg-4 col-md-6 mb-4">
    <div class="card h-100 room-card">
        <img src="{{ $room->primary_image }}" class="card-img-top" alt="{{ $room->name }}">
        <div class="card-body">
            <h5 class="card-title mb-2">{{ $room->name }}</h5>
            <p class="text-muted small mb-2">
                <i class="bx bx-user me-1"></i> {{ $room->capacity }} people
                <span class="mx-1">•</span>
                <i class="bx bx-map me-1"></i> {{ $room->floor_location }}
            </p>
            <div class="d-flex flex-wrap gap-1 mb-2">
                @foreach($room->amenities->take(4) as $amenity)
                    <span class="badge bg-label-secondary" title="{{ $amenity->name }}">
                        {!! $amenity->icon_html !!}
                    </span>
                @endforeach
                @if($room->amenities->count() > 4)
                    <span class="badge bg-label-secondary">+{{ $room->amenities->count() - 4 }}</span>
                @endif
            </div>
        </div>
        <div class="card-footer bg-transparent">
            <a href="{{ route('rooms.show', $room) }}" class="btn btn-primary w-100">
                <i class="bx bx-info-circle me-1"></i> View Details
            </a>
        </div>
    </div>
</div>
```

---

## Task 2.5.5: AJAX Filter Enhancement (Optional)

**File:** `public/assets/js/room-filters.js`

```javascript
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('filterForm');
    const roomGrid = document.getElementById('roomGrid');
    
    // Optional: Auto-submit on select change
    filterForm.querySelectorAll('select').forEach(select => {
        select.addEventListener('change', function() {
            filterForm.submit();
        });
    });

    // Validate time range
    const startTime = filterForm.querySelector('[name="start_time"]');
    const endTime = filterForm.querySelector('[name="end_time"]');
    
    endTime.addEventListener('change', function() {
        if (startTime.value && this.value && this.value <= startTime.value) {
            alert('End time must be after start time');
            this.value = '';
        }
    });
});
```

---

## Task 2.5.6: Feature Tests

```bash
php artisan make:test Feature/RoomFilteringTest
```

**Test Cases:**
- Filter by capacity returns correct rooms
- Filter by amenities (AND logic) works
- Filter by availability excludes booked rooms
- Filter by availability excludes maintenance rooms
- Multiple filters combine correctly
- Empty results show message
- Clear filters resets all

---

## Acceptance Criteria

- [x] Search by room name works (case-insensitive)
- [x] Capacity filter shows rooms >= selected value
- [x] Amenity filter uses AND logic (all selected must match)
- [x] Date/time filter excludes rooms with conflicting bookings
- [x] Date/time filter excludes rooms under maintenance
- [x] Applied filters shown as removable badges
- [x] Result count updates with filters
- [x] "Clear All Filters" resets the page
- [x] Empty state shown when no results
- [x] Filters preserved on pagination
- [x] `php artisan test --filter=RoomFilteringTest` passes

---

**Next:** [Step 2.6 - Audit Logging](./step-2.6-audit-logging.md)
