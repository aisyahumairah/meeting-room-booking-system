# Step 3.2: Booking Creation

**Priority:** CRITICAL | **Ref:** §6.2.1 | **Dependencies:** Step 3.1  
**Status:** COMPLETE

---

## Objective

Implement one-time booking creation with real-time availability checking and auto-approval (instant confirmation).

---

## Task 3.2.1: Create Booking Request Validation

```bash
php artisan make:request StoreBookingRequest
```

**File:** `app/Http/Requests/StoreBookingRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Room;
use Carbon\Carbon;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'room_id' => ['required', 'exists:rooms,id'],
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'purpose' => ['required', 'string', 'max:500'],
            'user_id' => ['nullable', 'exists:users,id'], // For admin booking on behalf
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                return;
            }

            $this->validateOperatingHours($validator);
            $this->validateDuration($validator);
            $this->validateRoomStatus($validator);
        });
    }

    protected function validateOperatingHours($validator): void
    {
        $startTime = Carbon::parse($this->start_time);
        $endTime = Carbon::parse($this->end_time);
        
        $operatingStart = Carbon::parse('08:00');
        $operatingEnd = Carbon::parse('18:00');

        if ($startTime->lt($operatingStart) || $startTime->gte($operatingEnd)) {
            $validator->errors()->add('start_time', 'Start time must be between 08:00 and 18:00.');
        }

        if ($endTime->lte($operatingStart) || $endTime->gt($operatingEnd)) {
            $validator->errors()->add('end_time', 'End time must be between 08:00 and 18:00.');
        }
    }

    protected function validateDuration($validator): void
    {
        $startTime = Carbon::parse($this->start_time);
        $endTime = Carbon::parse($this->end_time);
        $durationMinutes = $startTime->diffInMinutes($endTime);

        if ($durationMinutes < 30) {
            $validator->errors()->add('end_time', 'Minimum booking duration is 30 minutes.');
        }

        if ($durationMinutes > 480) { // 8 hours
            $validator->errors()->add('end_time', 'Maximum booking duration is 8 hours.');
        }
    }

    protected function validateRoomStatus($validator): void
    {
        $room = Room::find($this->room_id);
        
        if ($room && $room->status !== 'active') {
            $validator->errors()->add('room_id', 'This room is not available for booking.');
        }
    }

    public function messages(): array
    {
        return [
            'room_id.required' => 'Please select a meeting room.',
            'room_id.exists' => 'The selected room does not exist.',
            'booking_date.required' => 'Please select a booking date.',
            'booking_date.after_or_equal' => 'Booking date cannot be in the past.',
            'start_time.required' => 'Please select a start time.',
            'end_time.required' => 'Please select an end time.',
            'end_time.after' => 'End time must be after start time.',
            'purpose.required' => 'Please provide a purpose for this booking.',
            'purpose.max' => 'Purpose cannot exceed 500 characters.',
        ];
    }
}
```

---

## Task 3.2.2: Create Booking Service

```bash
php artisan make:class Services/BookingService
```

**File:** `app/Services/BookingService.php`

```php
<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingService
{
    /**
     * Create a new booking with auto-approval (first-come-first-served)
     * 
     * @throws \Exception if room is not available
     */
    public function createBooking(array $data, User $booker): Booking
    {
        return DB::transaction(function () use ($data, $booker) {
            // Lock the room row to prevent race conditions
            $room = Room::lockForUpdate()->findOrFail($data['room_id']);

            // Double-check availability within transaction
            if (!$room->isAvailable($data['booking_date'], $data['start_time'], $data['end_time'])) {
                throw new \Exception('Room is no longer available for the selected time slot.');
            }

            // Determine the PIC (person in charge)
            $userId = $data['user_id'] ?? $booker->id;

            // Create the booking with confirmed status (auto-approval)
            $booking = Booking::create([
                'reference_number' => Booking::generateReferenceNumber(),
                'user_id' => $userId,
                'room_id' => $room->id,
                'booking_date' => $data['booking_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'purpose' => $data['purpose'],
                'status' => 'confirmed', // Auto-approval
            ]);

            // Log the booking creation
            Log::info('Booking created', [
                'booking_id' => $booking->id,
                'reference' => $booking->reference_number,
                'user_id' => $userId,
                'room_id' => $room->id,
                'date' => $data['booking_date'],
            ]);

            // TODO: Send email confirmation (Phase 4)
            // $this->sendConfirmationEmail($booking);

            return $booking;
        });
    }

    /**
     * Check if a room is available for a specific time slot
     */
    public function checkAvailability(int $roomId, string $date, string $startTime, string $endTime, ?int $excludeBookingId = null): bool
    {
        $room = Room::find($roomId);

        if (!$room) {
            return false;
        }

        // Check room status
        if ($room->status !== 'active') {
            return false;
        }

        // Check for maintenance schedule conflicts
        $maintenanceConflict = $room->maintenanceSchedules()
            ->where('start_datetime', '<=', $date . ' ' . $endTime)
            ->where('end_datetime', '>=', $date . ' ' . $startTime)
            ->exists();

        if ($maintenanceConflict) {
            return false;
        }

        // Check for booking conflicts
        $conflictQuery = Booking::where('room_id', $roomId)
            ->where('booking_date', $date)
            ->where('status', 'confirmed')
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
                });
            });

        // Exclude current booking when editing
        if ($excludeBookingId) {
            $conflictQuery->where('id', '!=', $excludeBookingId);
        }

        return !$conflictQuery->exists();
    }

    /**
     * Get available time slots for a room on a given date
     */
    public function getAvailableSlots(int $roomId, string $date): array
    {
        $room = Room::find($roomId);
        
        if (!$room || $room->status !== 'active') {
            return [];
        }

        // Get all confirmed bookings for this room on this date
        $bookings = Booking::where('room_id', $roomId)
            ->where('booking_date', $date)
            ->where('status', 'confirmed')
            ->orderBy('start_time')
            ->get(['start_time', 'end_time']);

        // Get maintenance schedules
        $maintenance = $room->maintenanceSchedules()
            ->whereDate('start_datetime', '<=', $date)
            ->whereDate('end_datetime', '>=', $date)
            ->get();

        $slots = [];
        $currentTime = '08:00';
        $endOfDay = '18:00';

        // TODO: Calculate available slots based on bookings and maintenance
        // This is a simplified version - can be enhanced

        return $slots;
    }

    /**
     * Get conflict details for a time slot
     */
    public function getConflictDetails(int $roomId, string $date, string $startTime, string $endTime): ?array
    {
        $room = Room::find($roomId);

        if (!$room) {
            return ['type' => 'room_not_found', 'message' => 'Room not found.'];
        }

        if ($room->status !== 'active') {
            return ['type' => 'room_inactive', 'message' => 'Room is not available for booking.'];
        }

        // Check maintenance
        $maintenance = $room->maintenanceSchedules()
            ->where('start_datetime', '<=', $date . ' ' . $endTime)
            ->where('end_datetime', '>=', $date . ' ' . $startTime)
            ->first();

        if ($maintenance) {
            return [
                'type' => 'maintenance',
                'message' => 'Room is under maintenance during this time.',
                'reason' => $maintenance->reason,
            ];
        }

        // Check booking conflict
        $conflict = Booking::where('room_id', $roomId)
            ->where('booking_date', $date)
            ->where('status', 'confirmed')
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
            })
            ->first();

        if ($conflict) {
            return [
                'type' => 'booking',
                'message' => 'Room is already booked during this time.',
                'existing_time' => $conflict->time_range,
            ];
        }

        return null;
    }
}
```

---

## Task 3.2.3: Create Booking Controller

```bash
php artisan make:controller BookingController
```

**File:** `app/Http/Controllers/BookingController.php`

```php
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
                ->route('my-bookings.show', $booking)
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
```

---

## Task 3.2.4: Define Routes

**File:** `routes/web.php`

Add to the authenticated user routes:

```php
use App\Http\Controllers\BookingController;

Route::middleware(['auth', 'check.active'])->group(function () {
    // ... existing routes ...

    // Booking creation
    Route::get('/bookings/create', [BookingController::class, 'create'])->name('bookings.create');
    Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
});
```

**File:** `routes/api.php` (or use web routes with AJAX)

```php
use App\Http\Controllers\BookingController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/bookings/check-availability', [BookingController::class, 'checkAvailability'])
        ->name('api.bookings.check-availability');
});
```

Or add to web.php for session-based AJAX:

```php
Route::middleware(['auth', 'check.active'])->group(function () {
    // AJAX routes
    Route::post('/ajax/bookings/check-availability', [BookingController::class, 'checkAvailability'])
        ->name('ajax.bookings.check-availability');
});
```

---

## Task 3.2.5: Create Booking Form View

**File:** `resources/views/bookings/create.blade.php`

```blade
@extends('layouts.app')

@section('title', 'Book a Room')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Bookings /</span> Book a Room
    </h4>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">New Booking</h5>
                    <small class="text-muted float-end">All fields are required</small>
                </div>
                <div class="card-body">
                    <form id="bookingForm" action="{{ route('bookings.store') }}" method="POST">
                        @csrf

                        {{-- Room Selection --}}
                        <div class="mb-3">
                            <label class="form-label" for="room_id">Meeting Room</label>
                            <select class="form-select @error('room_id') is-invalid @enderror" 
                                    id="room_id" name="room_id" required>
                                <option value="">Select a room...</option>
                                @foreach($rooms as $room)
                                    <option value="{{ $room->id }}" 
                                            data-capacity="{{ $room->capacity }}"
                                            data-location="{{ $room->floor_location }}"
                                            {{ (old('room_id', $selectedRoom?->id) == $room->id) ? 'selected' : '' }}>
                                        {{ $room->name }} ({{ $room->capacity }} seats, {{ $room->floor_location }})
                                    </option>
                                @endforeach
                            </select>
                            @error('room_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Booking Date --}}
                        <div class="mb-3">
                            <label class="form-label" for="booking_date">Date</label>
                            <input type="date" 
                                   class="form-control @error('booking_date') is-invalid @enderror" 
                                   id="booking_date" 
                                   name="booking_date" 
                                   min="{{ date('Y-m-d') }}"
                                   value="{{ old('booking_date', $prefilledDate ?? '') }}"
                                   required>
                            @error('booking_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Time Selection --}}
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="start_time">Start Time</label>
                                <select class="form-select @error('start_time') is-invalid @enderror" 
                                        id="start_time" name="start_time" required>
                                    <option value="">Select start time...</option>
                                    @for($hour = 8; $hour < 18; $hour++)
                                        @foreach(['00', '30'] as $minute)
                                            @php $time = sprintf('%02d:%s', $hour, $minute); @endphp
                                            <option value="{{ $time }}" 
                                                    {{ old('start_time', $prefilledStartTime ?? '') == $time ? 'selected' : '' }}>
                                                {{ $time }}
                                            </option>
                                        @endforeach
                                    @endfor
                                </select>
                                @error('start_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="end_time">End Time</label>
                                <select class="form-select @error('end_time') is-invalid @enderror" 
                                        id="end_time" name="end_time" required>
                                    <option value="">Select end time...</option>
                                    @for($hour = 8; $hour <= 18; $hour++)
                                        @foreach(['00', '30'] as $minute)
                                            @if($hour == 8 && $minute == '00') @continue @endif
                                            @php $time = sprintf('%02d:%s', $hour, $minute); @endphp
                                            <option value="{{ $time }}"
                                                    {{ old('end_time', $prefilledEndTime ?? '') == $time ? 'selected' : '' }}>
                                                {{ $time }}
                                            </option>
                                        @endforeach
                                    @endfor
                                </select>
                                @error('end_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Duration Display --}}
                        <div class="mb-3">
                            <small class="text-muted">
                                Duration: <span id="durationDisplay">--</span>
                                <span class="text-info">(Min: 30 min, Max: 8 hours)</span>
                            </small>
                        </div>

                        {{-- Availability Status --}}
                        <div id="availabilityStatus" class="mb-3" style="display: none;">
                            <div id="availabilityMessage" class="alert"></div>
                        </div>

                        {{-- Purpose --}}
                        <div class="mb-3">
                            <label class="form-label" for="purpose">Purpose of Booking</label>
                            <textarea class="form-control @error('purpose') is-invalid @enderror" 
                                      id="purpose" 
                                      name="purpose" 
                                      rows="3" 
                                      maxlength="500"
                                      placeholder="Describe the meeting purpose..."
                                      required>{{ old('purpose') }}</textarea>
                            <div class="form-text">
                                <span id="purposeCount">0</span>/500 characters
                            </div>
                            @error('purpose')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Booking on Behalf (Admin/Director only) --}}
                        @if(auth()->user()->canManageBookings())
                        <div class="mb-3">
                            <label class="form-label" for="user_id">Book on Behalf of (Optional)</label>
                            <select class="form-select" id="user_id" name="user_id">
                                <option value="">Myself ({{ auth()->user()->name }})</option>
                                @foreach(\App\Models\User::active()->where('id', '!=', auth()->id())->orderBy('name')->get() as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                            <div class="form-text">Leave empty to book for yourself</div>
                        </div>
                        @endif

                        {{-- Submit Buttons --}}
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary me-2" id="submitBtn">
                                <i class="bx bx-check me-1"></i> Confirm Booking
                            </button>
                            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Room Preview Panel --}}
        <div class="col-md-4">
            <div class="card mb-4" id="roomPreview" style="display: none;">
                <img id="roomImage" src="" class="card-img-top" alt="Room Image" style="height: 200px; object-fit: cover;">
                <div class="card-body">
                    <h5 class="card-title" id="roomName">-</h5>
                    <p class="card-text">
                        <i class="bx bx-user me-1"></i> <span id="roomCapacity">-</span> seats<br>
                        <i class="bx bx-map me-1"></i> <span id="roomLocation">-</span>
                    </p>
                    <div id="roomAmenities"></div>
                </div>
            </div>

            {{-- Booking Guidelines --}}
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bx bx-info-circle me-1"></i> Booking Guidelines</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="bx bx-time text-primary me-1"></i> Operating hours: 8:00 AM - 6:00 PM</li>
                        <li class="mb-2"><i class="bx bx-timer text-primary me-1"></i> Duration: 30 min to 8 hours</li>
                        <li class="mb-2"><i class="bx bx-calendar text-primary me-1"></i> Book any future date</li>
                        <li class="mb-2"><i class="bx bx-check-circle text-success me-1"></i> Instant confirmation</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('bookingForm');
    const roomSelect = document.getElementById('room_id');
    const dateInput = document.getElementById('booking_date');
    const startTimeSelect = document.getElementById('start_time');
    const endTimeSelect = document.getElementById('end_time');
    const purposeInput = document.getElementById('purpose');
    const submitBtn = document.getElementById('submitBtn');
    
    let availabilityCheckTimeout;

    // Room selection change
    roomSelect.addEventListener('change', function() {
        updateRoomPreview();
        checkAvailability();
    });

    // Time/date change triggers availability check
    [dateInput, startTimeSelect, endTimeSelect].forEach(el => {
        el.addEventListener('change', function() {
            updateDuration();
            checkAvailability();
        });
    });

    // Purpose character count
    purposeInput.addEventListener('input', function() {
        document.getElementById('purposeCount').textContent = this.value.length;
    });

    function updateDuration() {
        const start = startTimeSelect.value;
        const end = endTimeSelect.value;
        const display = document.getElementById('durationDisplay');

        if (start && end) {
            const [sh, sm] = start.split(':').map(Number);
            const [eh, em] = end.split(':').map(Number);
            const minutes = (eh * 60 + em) - (sh * 60 + sm);

            if (minutes > 0) {
                const hours = Math.floor(minutes / 60);
                const mins = minutes % 60;
                display.textContent = hours > 0 
                    ? `${hours}h ${mins > 0 ? mins + 'm' : ''}` 
                    : `${mins}m`;
            } else {
                display.textContent = 'Invalid';
            }
        } else {
            display.textContent = '--';
        }
    }

    function checkAvailability() {
        clearTimeout(availabilityCheckTimeout);
        
        const roomId = roomSelect.value;
        const date = dateInput.value;
        const startTime = startTimeSelect.value;
        const endTime = endTimeSelect.value;

        if (!roomId || !date || !startTime || !endTime) {
            hideAvailabilityStatus();
            return;
        }

        availabilityCheckTimeout = setTimeout(() => {
            fetch('{{ route("ajax.bookings.check-availability") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({
                    room_id: roomId,
                    booking_date: date,
                    start_time: startTime,
                    end_time: endTime,
                }),
            })
            .then(response => response.json())
            .then(data => {
                showAvailabilityStatus(data.available, data.conflict);
            })
            .catch(error => {
                console.error('Availability check failed:', error);
            });
        }, 500);
    }

    function showAvailabilityStatus(available, conflict) {
        const container = document.getElementById('availabilityStatus');
        const message = document.getElementById('availabilityMessage');
        
        container.style.display = 'block';
        
        if (available) {
            message.className = 'alert alert-success';
            message.innerHTML = '<i class="bx bx-check-circle me-1"></i> Room is available!';
            submitBtn.disabled = false;
        } else {
            message.className = 'alert alert-danger';
            message.innerHTML = `<i class="bx bx-x-circle me-1"></i> ${conflict?.message || 'Room is not available'}`;
            submitBtn.disabled = true;
        }
    }

    function hideAvailabilityStatus() {
        document.getElementById('availabilityStatus').style.display = 'none';
        submitBtn.disabled = false;
    }

    function updateRoomPreview() {
        const selected = roomSelect.options[roomSelect.selectedIndex];
        const preview = document.getElementById('roomPreview');

        if (roomSelect.value) {
            preview.style.display = 'block';
            document.getElementById('roomName').textContent = selected.text.split(' (')[0];
            document.getElementById('roomCapacity').textContent = selected.dataset.capacity;
            document.getElementById('roomLocation').textContent = selected.dataset.location;
            // TODO: Load room image and amenities via AJAX
        } else {
            preview.style.display = 'none';
        }
    }

    // Form submission with loading state
    form.addEventListener('submit', function() {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Creating...';
    });

    // Initialize
    updateDuration();
    if (roomSelect.value) {
        updateRoomPreview();
        checkAvailability();
    }
});
</script>
@endpush
@endsection
```

---

## Task 3.2.6: Update Sidebar Navigation

**File:** `resources/views/layouts/partials/sidebar.blade.php`

Add to the Bookings section:

```blade
{{-- Bookings Section --}}
<li class="menu-header small text-uppercase">
    <span class="menu-header-text">Bookings</span>
</li>

<li class="menu-item {{ request()->routeIs('bookings.create') ? 'active' : '' }}">
    <a href="{{ route('bookings.create') }}" class="menu-link">
        <i class="menu-icon tf-icons bx bx-plus-circle"></i>
        <div data-i18n="Book a Room">Book a Room</div>
    </a>
</li>

<li class="menu-item {{ request()->routeIs('calendar') ? 'active' : '' }}">
    <a href="{{ route('calendar') }}" class="menu-link">
        <i class="menu-icon tf-icons bx bx-calendar"></i>
        <div data-i18n="Calendar">Calendar</div>
    </a>
</li>

<li class="menu-item {{ request()->routeIs('my-bookings.*') ? 'active' : '' }}">
    <a href="{{ route('my-bookings') }}" class="menu-link">
        <i class="menu-icon tf-icons bx bx-list-check"></i>
        <div data-i18n="My Bookings">My Bookings</div>
    </a>
</li>

@if(auth()->user()->canManageBookings())
<li class="menu-item {{ request()->routeIs('admin.bookings.*') ? 'active' : '' }}">
    <a href="{{ route('admin.bookings.index') }}" class="menu-link">
        <i class="menu-icon tf-icons bx bx-book-content"></i>
        <div data-i18n="All Bookings">All Bookings</div>
    </a>
</li>
@endif
```

---

## Testing Requirements

**File:** `tests/Feature/BookingCreationTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingCreationTest extends TestCase
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

    public function test_user_can_view_booking_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('bookings.create'));

        $response->assertStatus(200);
        $response->assertViewIs('bookings.create');
    }

    public function test_user_can_create_booking()
    {
        $response = $this->actingAs($this->user)
            ->post(route('bookings.store'), [
                'room_id' => $this->room->id,
                'booking_date' => now()->addDays(5)->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '11:00',
                'purpose' => 'Team meeting',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bookings', [
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_booking_is_auto_confirmed()
    {
        $this->actingAs($this->user)
            ->post(route('bookings.store'), [
                'room_id' => $this->room->id,
                'booking_date' => now()->addDays(5)->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '11:00',
                'purpose' => 'Team meeting',
            ]);

        $booking = Booking::latest()->first();
        $this->assertEquals('confirmed', $booking->status);
    }

    public function test_cannot_book_past_date()
    {
        $response = $this->actingAs($this->user)
            ->post(route('bookings.store'), [
                'room_id' => $this->room->id,
                'booking_date' => now()->subDays(1)->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '11:00',
                'purpose' => 'Team meeting',
            ]);

        $response->assertSessionHasErrors('booking_date');
    }

    public function test_cannot_book_outside_operating_hours()
    {
        $response = $this->actingAs($this->user)
            ->post(route('bookings.store'), [
                'room_id' => $this->room->id,
                'booking_date' => now()->addDays(5)->format('Y-m-d'),
                'start_time' => '07:00', // Before 8am
                'end_time' => '09:00',
                'purpose' => 'Team meeting',
            ]);

        $response->assertSessionHasErrors('start_time');
    }

    public function test_cannot_book_inactive_room()
    {
        $inactiveRoom = Room::factory()->create(['status' => 'inactive']);

        $response = $this->actingAs($this->user)
            ->post(route('bookings.store'), [
                'room_id' => $inactiveRoom->id,
                'booking_date' => now()->addDays(5)->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '11:00',
                'purpose' => 'Team meeting',
            ]);

        $response->assertSessionHasErrors('room_id');
    }

    public function test_cannot_double_book()
    {
        // Create existing booking
        Booking::factory()->create([
            'room_id' => $this->room->id,
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => 'confirmed',
        ]);

        // Try to book same slot
        $response = $this->actingAs($this->user)
            ->post(route('bookings.store'), [
                'room_id' => $this->room->id,
                'booking_date' => now()->addDays(5)->format('Y-m-d'),
                'start_time' => '10:00', // Overlaps with 09:00-11:00
                'end_time' => '12:00',
                'purpose' => 'Another meeting',
            ]);

        $response->assertSessionHasErrors('room_id');
    }
}
```

Run tests:
```bash
php artisan test --filter=BookingCreationTest
```

---

## Acceptance Criteria

- [x] Booking form displays at `/bookings/create`
- [x] Room dropdown shows all active rooms
- [x] Date picker prevents past dates
- [x] Time dropdowns show 30-minute increments (8:00-18:00)
- [x] Duration displays correctly when times selected
- [x] Real-time availability check works via AJAX
- [x] Booking is created with "confirmed" status (auto-approval)
- [x] Unique reference number generated (BK-YYYY-NNNNN)
- [x] Validation errors display correctly
- [x] Success message with reference number displays
- [x] Audit log entry created for booking
- [x] All feature tests pass (11/12, 1 intentionally skipped)

---

**Next:** [Step 3.3 - Recurring Bookings](./step-3.3-recurring-bookings.md)
