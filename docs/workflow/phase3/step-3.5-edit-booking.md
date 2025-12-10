# Step 3.5: Edit Booking

**Priority:** HIGH | **Ref:** §6.3.2 | **Dependencies:** Step 3.4  
**Status:** TODO

---

## Objective

Implement booking edit functionality with role-based permissions. Regular users can edit confirmed bookings (since there's no pending status), Admin/Director can edit any booking.

---

## Task 3.5.1: Create Update Booking Request

```bash
php artisan make:request UpdateBookingRequest
```

**File:** `app/Http/Requests/UpdateBookingRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Room;
use Carbon\Carbon;

class UpdateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $booking = $this->route('booking');
        $user = auth()->user();

        // Admin/Director can edit any booking
        if ($user->canManageBookings()) {
            return true;
        }

        // Regular users can only edit their own confirmed bookings
        return $booking->user_id === $user->id && $booking->is_editable;
    }

    public function rules(): array
    {
        return [
            'room_id' => ['required', 'exists:rooms,id'],
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'purpose' => ['required', 'string', 'max:500'],
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
        
        if ($startTime->lt(Carbon::parse('08:00')) || $startTime->gte(Carbon::parse('18:00'))) {
            $validator->errors()->add('start_time', 'Start time must be between 08:00 and 18:00.');
        }

        if ($endTime->lte(Carbon::parse('08:00')) || $endTime->gt(Carbon::parse('18:00'))) {
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

        if ($durationMinutes > 480) {
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
}
```

---

## Task 3.5.2: Add Edit Methods to BookingService

**File:** `app/Services/BookingService.php`

Add these methods:

```php
/**
 * Update an existing booking
 * 
 * @throws \Exception if room is not available
 */
public function updateBooking(Booking $booking, array $data, User $editor): Booking
{
    return DB::transaction(function () use ($booking, $data, $editor) {
        // Lock the room to prevent race conditions
        $room = Room::lockForUpdate()->findOrFail($data['room_id']);

        // Check availability (excluding current booking)
        if (!$this->checkAvailability(
            $data['room_id'],
            $data['booking_date'],
            $data['start_time'],
            $data['end_time'],
            $booking->id
        )) {
            throw new \Exception('Room is no longer available for the selected time slot.');
        }

        // Track changes for audit
        $changes = [];
        if ($booking->room_id != $data['room_id']) {
            $changes['room'] = [
                'from' => $booking->room->name,
                'to' => $room->name,
            ];
        }
        if ($booking->booking_date->format('Y-m-d') != $data['booking_date']) {
            $changes['date'] = [
                'from' => $booking->booking_date->format('Y-m-d'),
                'to' => $data['booking_date'],
            ];
        }
        if ($booking->start_time != $data['start_time'] || $booking->end_time != $data['end_time']) {
            $changes['time'] = [
                'from' => $booking->time_range,
                'to' => $data['start_time'] . ' - ' . $data['end_time'],
            ];
        }

        // Update booking - status remains unchanged
        $booking->update([
            'room_id' => $data['room_id'],
            'booking_date' => $data['booking_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'purpose' => $data['purpose'],
        ]);

        Log::info('Booking updated', [
            'booking_id' => $booking->id,
            'editor_id' => $editor->id,
            'changes' => $changes,
        ]);

        // TODO: Send notification email (Phase 4)
        // If admin edited someone else's booking, notify the owner

        return $booking->fresh();
    });
}

/**
 * Update an entire booking series
 */
public function updateSeries(BookingSeries $series, array $data, User $editor): BookingSeries
{
    // Recalculate all occurrence dates with new pattern
    // This is complex - for now, only allow updating common fields

    return DB::transaction(function () use ($series, $data, $editor) {
        $room = Room::lockForUpdate()->findOrFail($data['room_id']);

        // Check availability for ALL existing occurrences with new time/room
        $conflicts = [];
        foreach ($series->bookings()->where('status', 'confirmed')->get() as $booking) {
            if (!$this->checkAvailability(
                $data['room_id'],
                $booking->booking_date->format('Y-m-d'),
                $data['start_time'],
                $data['end_time'],
                $booking->id
            )) {
                $conflicts[] = $booking->booking_date;
            }
        }

        if (!empty($conflicts)) {
            throw new \Exception(
                'Cannot update series. Room is not available on: ' .
                implode(', ', array_map(fn($d) => $d->format('M d, Y'), $conflicts))
            );
        }

        // Update series
        $series->update([
            'room_id' => $data['room_id'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'purpose' => $data['purpose'],
        ]);

        // Update all confirmed bookings in series
        $series->bookings()->where('status', 'confirmed')->update([
            'room_id' => $data['room_id'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'purpose' => $data['purpose'],
        ]);

        Log::info('Booking series updated', [
            'series_id' => $series->id,
            'editor_id' => $editor->id,
            'occurrences_updated' => $series->bookings()->where('status', 'confirmed')->count(),
        ]);

        return $series->fresh();
    });
}
```

---

## Task 3.5.3: Add Edit Controller Methods

**File:** `app/Http/Controllers/BookingController.php`

Add these methods:

```php
use App\Http\Requests\UpdateBookingRequest;

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
```

---

## Task 3.5.4: Add Edit Routes

**File:** `routes/web.php`

```php
Route::middleware(['auth', 'check.active'])->group(function () {
    // ... existing routes ...

    // Edit booking
    Route::get('/my-bookings/{booking}/edit', [BookingController::class, 'edit'])
        ->name('my-bookings.edit');
    Route::put('/my-bookings/{booking}', [BookingController::class, 'update'])
        ->name('my-bookings.update');
});
```

---

## Task 3.5.5: Create Edit Booking View

**File:** `resources/views/bookings/edit.blade.php`

```blade
@extends('layouts.app')

@section('title', 'Edit Booking')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0">
            <span class="text-muted fw-light">My Bookings /</span> Edit {{ $booking->reference_number }}
        </h4>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Edit Booking</h5>
                    {!! $booking->status_badge !!}
                </div>
                <div class="card-body">
                    @if($isSeriesEdit)
                        <div class="alert alert-warning mb-4">
                            <i class="bx bx-repeat me-1"></i>
                            <strong>Recurring Booking:</strong> This is part of a series ({{ $booking->series->reference_number }}).
                            Changes will apply to the <strong>entire series</strong> ({{ $booking->series->bookings()->where('status', 'confirmed')->count() }} bookings).
                        </div>
                    @endif

                    <form action="{{ route('my-bookings.update', $booking) }}" method="POST">
                        @csrf
                        @method('PUT')

                        @if($isSeriesEdit)
                            <input type="hidden" name="update_series" value="1">
                        @endif

                        {{-- Original Values (for comparison) --}}
                        <div class="row mb-4 p-3 bg-light rounded">
                            <div class="col-12">
                                <h6 class="text-muted mb-2">Current Booking</h6>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">Room</small>
                                <p class="mb-0">{{ $booking->room->name }}</p>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">Date</small>
                                <p class="mb-0">{{ $booking->booking_date->format('M d, Y') }}</p>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">Time</small>
                                <p class="mb-0">{{ $booking->time_range }}</p>
                            </div>
                        </div>

                        <hr>
                        <h6 class="mb-3">New Values</h6>

                        {{-- Room Selection --}}
                        <div class="mb-3">
                            <label class="form-label" for="room_id">Meeting Room</label>
                            <select class="form-select @error('room_id') is-invalid @enderror" 
                                    id="room_id" name="room_id" required>
                                @foreach($rooms as $room)
                                    <option value="{{ $room->id }}" 
                                            {{ old('room_id', $booking->room_id) == $room->id ? 'selected' : '' }}>
                                        {{ $room->name }} ({{ $room->capacity }} seats, {{ $room->floor_location }})
                                    </option>
                                @endforeach
                            </select>
                            @error('room_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Date (only for single bookings) --}}
                        @if(!$isSeriesEdit)
                            <div class="mb-3">
                                <label class="form-label" for="booking_date">Date</label>
                                <input type="date" 
                                       class="form-control @error('booking_date') is-invalid @enderror" 
                                       id="booking_date" 
                                       name="booking_date" 
                                       min="{{ date('Y-m-d') }}"
                                       value="{{ old('booking_date', $booking->booking_date->format('Y-m-d')) }}"
                                       required>
                                @error('booking_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @else
                            <input type="hidden" name="booking_date" value="{{ $booking->booking_date->format('Y-m-d') }}">
                            <div class="alert alert-info mb-3">
                                <i class="bx bx-info-circle me-1"></i>
                                Series booking dates cannot be changed. To change dates, cancel and create a new series.
                            </div>
                        @endif

                        {{-- Time Selection --}}
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="start_time">Start Time</label>
                                <select class="form-select @error('start_time') is-invalid @enderror" 
                                        id="start_time" name="start_time" required>
                                    @for($hour = 8; $hour < 18; $hour++)
                                        @foreach(['00', '30'] as $minute)
                                            @php 
                                                $time = sprintf('%02d:%s', $hour, $minute);
                                                $currentStart = Carbon\Carbon::parse($booking->start_time)->format('H:i');
                                            @endphp
                                            <option value="{{ $time }}" 
                                                    {{ old('start_time', $currentStart) == $time ? 'selected' : '' }}>
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
                                    @for($hour = 8; $hour <= 18; $hour++)
                                        @foreach(['00', '30'] as $minute)
                                            @if($hour == 8 && $minute == '00') @continue @endif
                                            @php 
                                                $time = sprintf('%02d:%s', $hour, $minute);
                                                $currentEnd = Carbon\Carbon::parse($booking->end_time)->format('H:i');
                                            @endphp
                                            <option value="{{ $time }}"
                                                    {{ old('end_time', $currentEnd) == $time ? 'selected' : '' }}>
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
                                      required>{{ old('purpose', $booking->purpose) }}</textarea>
                            @error('purpose')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Read-only fields --}}
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted">Reference Number</label>
                                <p class="form-control-plaintext">{{ $booking->reference_number }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">Booked By</label>
                                <p class="form-control-plaintext">{{ $booking->user->name }}</p>
                            </div>
                        </div>

                        {{-- Submit Buttons --}}
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary me-2" id="submitBtn">
                                <i class="bx bx-save me-1"></i> Save Changes
                            </button>
                            <a href="{{ route('my-bookings.show', $booking) }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Info Sidebar --}}
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bx bx-info-circle me-1"></i> Edit Guidelines</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="bx bx-check text-success me-1"></i>
                            Status remains <strong>{{ ucfirst($booking->status) }}</strong> after edit
                        </li>
                        <li class="mb-2">
                            <i class="bx bx-check text-success me-1"></i>
                            Reference number is preserved
                        </li>
                        <li class="mb-2">
                            <i class="bx bx-check text-success me-1"></i>
                            Changes are logged in audit trail
                        </li>
                        @if($isSeriesEdit)
                            <li class="mb-2">
                                <i class="bx bx-error text-warning me-1"></i>
                                All {{ $booking->series->bookings()->where('status', 'confirmed')->count() }} occurrences will be updated
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const roomSelect = document.getElementById('room_id');
    const dateInput = document.getElementById('booking_date');
    const startTimeSelect = document.getElementById('start_time');
    const endTimeSelect = document.getElementById('end_time');
    const submitBtn = document.getElementById('submitBtn');
    
    let availabilityCheckTimeout;

    // Trigger availability check on change
    [roomSelect, dateInput, startTimeSelect, endTimeSelect].forEach(el => {
        if (el) el.addEventListener('change', checkAvailability);
    });

    function checkAvailability() {
        clearTimeout(availabilityCheckTimeout);
        
        const roomId = roomSelect.value;
        const date = dateInput ? dateInput.value : '{{ $booking->booking_date->format("Y-m-d") }}';
        const startTime = startTimeSelect.value;
        const endTime = endTimeSelect.value;

        if (!roomId || !date || !startTime || !endTime) {
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
                    exclude_booking_id: {{ $booking->id }},
                }),
            })
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('availabilityStatus');
                const message = document.getElementById('availabilityMessage');
                
                container.style.display = 'block';
                
                if (data.available) {
                    message.className = 'alert alert-success';
                    message.innerHTML = '<i class="bx bx-check-circle me-1"></i> Time slot is available!';
                    submitBtn.disabled = false;
                } else {
                    message.className = 'alert alert-danger';
                    message.innerHTML = `<i class="bx bx-x-circle me-1"></i> ${data.conflict?.message || 'Time slot is not available'}`;
                    submitBtn.disabled = true;
                }
            });
        }, 500);
    }
});
</script>
@endpush
@endsection
```

---

## Testing Requirements

**File:** `tests/Feature/EditBookingTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EditBookingTest extends TestCase
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

    public function test_user_can_edit_own_confirmed_booking()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'status' => 'confirmed',
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('my-bookings.update', $booking), [
                'room_id' => $this->room->id,
                'booking_date' => now()->addDays(7)->format('Y-m-d'),
                'start_time' => '10:00',
                'end_time' => '12:00',
                'purpose' => 'Updated purpose',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'purpose' => 'Updated purpose',
            'status' => 'confirmed', // Status unchanged
        ]);
    }

    public function test_user_cannot_edit_others_booking()
    {
        $otherUser = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('my-bookings.edit', $booking));

        $response->assertStatus(403);
    }

    public function test_user_cannot_edit_cancelled_booking()
    {
        $booking = Booking::factory()->cancelled()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('my-bookings.edit', $booking));

        $response->assertRedirect();
    }

    public function test_admin_can_edit_any_booking()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'status' => 'confirmed',
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('my-bookings.update', $booking), [
                'room_id' => $this->room->id,
                'booking_date' => now()->addDays(10)->format('Y-m-d'),
                'start_time' => '14:00',
                'end_time' => '16:00',
                'purpose' => 'Admin updated this',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'purpose' => 'Admin updated this',
        ]);
    }

    public function test_status_remains_unchanged_after_edit()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'status' => 'confirmed',
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $this->actingAs($this->user)
            ->put(route('my-bookings.update', $booking), [
                'room_id' => $this->room->id,
                'booking_date' => now()->addDays(7)->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '11:00',
                'purpose' => 'Changed purpose',
            ]);

        $booking->refresh();
        $this->assertEquals('confirmed', $booking->status);
    }

    public function test_edit_validates_availability()
    {
        // Create existing booking
        Booking::factory()->create([
            'room_id' => $this->room->id,
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
            'start_time' => '14:00',
            'end_time' => '16:00',
            'status' => 'confirmed',
        ]);

        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'booking_date' => now()->addDays(3)->format('Y-m-d'),
            'status' => 'confirmed',
        ]);

        // Try to move to conflicting slot
        $response = $this->actingAs($this->user)
            ->put(route('my-bookings.update', $booking), [
                'room_id' => $this->room->id,
                'booking_date' => now()->addDays(5)->format('Y-m-d'),
                'start_time' => '15:00', // Overlaps with 14:00-16:00
                'end_time' => '17:00',
                'purpose' => 'Conflict test',
            ]);

        $response->assertSessionHasErrors('room_id');
    }
}
```

---

## Acceptance Criteria

- [ ] Edit form displays current booking values
- [ ] User can edit their own confirmed bookings
- [ ] User cannot edit cancelled/completed bookings
- [ ] User cannot edit other users' bookings
- [ ] Admin/Director can edit any booking
- [ ] Status remains unchanged after edit (confirmed stays confirmed)
- [ ] Real-time availability check excludes current booking
- [ ] Validation errors display correctly
- [ ] Series edit updates all occurrences
- [ ] Audit log entry created for edit
- [ ] Success message displays after update
- [ ] All tests pass

---

**Next:** [Step 3.6 - Cancel Booking](./step-3.6-cancel-booking.md)
