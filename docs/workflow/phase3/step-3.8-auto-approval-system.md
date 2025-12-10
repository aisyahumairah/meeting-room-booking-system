# Step 3.8: Auto-Approval System

**Priority:** CRITICAL | **Ref:** §6.4.2 | **Dependencies:** Step 3.2  
**Status:** TODO

---

## Objective

Implement the auto-approval (first-come-first-served) system with database locking to prevent race conditions and provide real-time conflict feedback.

---

## Overview

Since the system uses **auto-approval**, bookings are confirmed immediately when availability is verified. This step focuses on:

1. **Race condition prevention** - Database-level locking ensures only one booking wins a slot
2. **Real-time availability** - Users see instant feedback on slot availability
3. **Conflict handling** - Clear error messages when slots are taken

---

## Task 3.8.1: Update BookingService with Proper Locking

The BookingService from Step 3.2 already has the foundation. This step ensures proper locking and error handling:

**File:** `app/Services/BookingService.php`

Ensure createBooking method uses proper locking:

```php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Create a new booking with auto-approval (first-come-first-served)
 * Uses database locking to prevent race conditions
 * 
 * @throws \Exception if room is not available
 */
public function createBooking(array $data, User $booker): Booking
{
    return DB::transaction(function () use ($data, $booker) {
        // CRITICAL: Lock the room row to prevent race conditions
        // This ensures only one booking can be created for a time slot
        // even if multiple requests arrive simultaneously
        $room = Room::lockForUpdate()->findOrFail($data['room_id']);

        // Verify room is still active (could have been deactivated)
        if ($room->status !== 'active') {
            throw new \Exception('This room is no longer available for booking.');
        }

        // Double-check availability within the transaction
        // This check happens AFTER locking, so it's authoritative
        if (!$this->isTimeSlotAvailable(
            $room->id,
            $data['booking_date'],
            $data['start_time'],
            $data['end_time']
        )) {
            // Get conflict details for better error message
            $conflict = $this->getConflictDetails(
                $room->id,
                $data['booking_date'],
                $data['start_time'],
                $data['end_time']
            );

            $message = $conflict['message'] ?? 'Room is no longer available for the selected time slot.';
            throw new \Exception($message);
        }

        // Determine the PIC (person in charge)
        $userId = $data['user_id'] ?? $booker->id;

        // Create the booking with confirmed status (AUTO-APPROVAL)
        // The race is won - this booking gets the slot
        $booking = Booking::create([
            'reference_number' => Booking::generateReferenceNumber(),
            'user_id' => $userId,
            'room_id' => $room->id,
            'booking_date' => $data['booking_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'purpose' => $data['purpose'],
            'status' => 'confirmed', // Auto-approval: immediate confirmation
        ]);

        Log::info('Booking auto-approved', [
            'booking_id' => $booking->id,
            'reference' => $booking->reference_number,
            'user_id' => $userId,
            'room_id' => $room->id,
            'date' => $data['booking_date'],
            'time' => $data['start_time'] . '-' . $data['end_time'],
        ]);

        // TODO: Queue email notification (Phase 4)
        // Note: Don't send email inside transaction

        return $booking;
    }, 5); // 5 retries on deadlock
}

/**
 * Check if a time slot is available (internal use within transaction)
 * This does NOT lock - it's meant to be called after lockForUpdate on room
 */
protected function isTimeSlotAvailable(int $roomId, string $date, string $startTime, string $endTime): bool
{
    // Check for existing confirmed bookings that overlap
    $conflictExists = Booking::where('room_id', $roomId)
        ->where('booking_date', $date)
        ->where('status', 'confirmed')
        ->where(function ($query) use ($startTime, $endTime) {
            // Overlap condition: existing start < new end AND existing end > new start
            $query->where('start_time', '<', $endTime)
                  ->where('end_time', '>', $startTime);
        })
        ->exists();

    if ($conflictExists) {
        return false;
    }

    // Check for maintenance schedules
    $maintenanceConflict = DB::table('room_maintenance_schedules')
        ->where('room_id', $roomId)
        ->where('start_datetime', '<=', $date . ' ' . $endTime)
        ->where('end_datetime', '>=', $date . ' ' . $startTime)
        ->exists();

    return !$maintenanceConflict;
}
```

---

## Task 3.8.2: Create a Dedicated Availability Check API

For real-time availability checking without the overhead of creating a booking:

**File:** `app/Http/Controllers/Api/AvailabilityController.php`

```bash
php artisan make:controller Api/AvailabilityController
```

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AvailabilityController extends Controller
{
    /**
     * Check if a specific time slot is available
     */
    public function check(Request $request): JsonResponse
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'exclude_booking_id' => 'nullable|integer',
        ]);

        $room = Room::find($request->room_id);

        // Check room status
        if ($room->status !== 'active') {
            return response()->json([
                'available' => false,
                'reason' => 'room_inactive',
                'message' => 'This room is currently not available for booking.',
            ]);
        }

        // Check for maintenance
        $maintenance = $room->maintenanceSchedules()
            ->where('start_datetime', '<=', $request->date . ' ' . $request->end_time)
            ->where('end_datetime', '>=', $request->date . ' ' . $request->start_time)
            ->first();

        if ($maintenance) {
            return response()->json([
                'available' => false,
                'reason' => 'maintenance',
                'message' => 'Room is scheduled for maintenance during this time.',
                'maintenance_reason' => $maintenance->reason,
            ]);
        }

        // Check for booking conflicts
        $conflictQuery = Booking::where('room_id', $request->room_id)
            ->where('booking_date', $request->date)
            ->where('status', 'confirmed')
            ->where(function ($query) use ($request) {
                $query->where('start_time', '<', $request->end_time)
                      ->where('end_time', '>', $request->start_time);
            });

        // Exclude current booking when editing
        if ($request->exclude_booking_id) {
            $conflictQuery->where('id', '!=', $request->exclude_booking_id);
        }

        $conflict = $conflictQuery->first();

        if ($conflict) {
            return response()->json([
                'available' => false,
                'reason' => 'booked',
                'message' => 'This time slot is already booked.',
                'conflict' => [
                    'time' => $conflict->time_range,
                    'reference' => $conflict->reference_number,
                ],
            ]);
        }

        return response()->json([
            'available' => true,
            'message' => 'Time slot is available.',
        ]);
    }

    /**
     * Get all bookings for a room on a specific date (for calendar display)
     */
    public function roomSchedule(Request $request, Room $room): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $bookings = Booking::where('room_id', $room->id)
            ->where('booking_date', $request->date)
            ->where('status', 'confirmed')
            ->orderBy('start_time')
            ->get(['id', 'reference_number', 'start_time', 'end_time', 'purpose', 'user_id'])
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'reference' => $booking->reference_number,
                    'start_time' => $booking->start_time,
                    'end_time' => $booking->end_time,
                    'purpose' => $booking->purpose,
                    'is_mine' => $booking->user_id === auth()->id(),
                ];
            });

        // Also get maintenance schedules for the date
        $maintenance = $room->maintenanceSchedules()
            ->whereDate('start_datetime', '<=', $request->date)
            ->whereDate('end_datetime', '>=', $request->date)
            ->get()
            ->map(function ($m) use ($request) {
                return [
                    'type' => 'maintenance',
                    'reason' => $m->reason,
                    'start_time' => $m->start_datetime->format('H:i'),
                    'end_time' => $m->end_datetime->format('H:i'),
                ];
            });

        return response()->json([
            'room' => [
                'id' => $room->id,
                'name' => $room->name,
                'status' => $room->status,
            ],
            'date' => $request->date,
            'bookings' => $bookings,
            'maintenance' => $maintenance,
            'operating_hours' => [
                'start' => '08:00',
                'end' => '18:00',
            ],
        ]);
    }

    /**
     * Get available time slots for a room on a specific date
     */
    public function availableSlots(Request $request, Room $room): JsonResponse
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'duration' => 'nullable|integer|min:30|max:480', // Duration in minutes
        ]);

        $date = $request->date;
        $duration = $request->get('duration', 60); // Default 1 hour

        // Get all blocked time ranges
        $blockedSlots = collect();

        // Get confirmed bookings
        $bookings = Booking::where('room_id', $room->id)
            ->where('booking_date', $date)
            ->where('status', 'confirmed')
            ->orderBy('start_time')
            ->get();

        foreach ($bookings as $booking) {
            $blockedSlots->push([
                'start' => $booking->start_time,
                'end' => $booking->end_time,
                'type' => 'booking',
            ]);
        }

        // Get maintenance schedules
        $maintenance = $room->maintenanceSchedules()
            ->whereDate('start_datetime', '<=', $date)
            ->whereDate('end_datetime', '>=', $date)
            ->get();

        foreach ($maintenance as $m) {
            $blockedSlots->push([
                'start' => $m->start_datetime->format('H:i'),
                'end' => $m->end_datetime->format('H:i'),
                'type' => 'maintenance',
            ]);
        }

        // Calculate available slots
        $operatingStart = '08:00';
        $operatingEnd = '18:00';
        $slotIncrement = 30; // 30-minute increments

        $available = [];
        $current = strtotime($operatingStart);
        $end = strtotime($operatingEnd);

        while ($current < $end) {
            $slotStart = date('H:i', $current);
            $slotEnd = date('H:i', $current + ($duration * 60));

            // Check if slot end exceeds operating hours
            if (strtotime($slotEnd) > $end) {
                break;
            }

            // Check if slot overlaps with any blocked time
            $isBlocked = false;
            foreach ($blockedSlots as $blocked) {
                $blockedStart = strtotime($blocked['start']);
                $blockedEnd = strtotime($blocked['end']);
                $currentEnd = $current + ($duration * 60);

                if ($current < $blockedEnd && $currentEnd > $blockedStart) {
                    $isBlocked = true;
                    break;
                }
            }

            if (!$isBlocked) {
                $available[] = [
                    'start' => $slotStart,
                    'end' => $slotEnd,
                    'duration' => $duration,
                ];
            }

            $current += ($slotIncrement * 60);
        }

        return response()->json([
            'room' => $room->name,
            'date' => $date,
            'requested_duration' => $duration,
            'available_slots' => $available,
            'count' => count($available),
        ]);
    }
}
```

---

## Task 3.8.3: Add Availability API Routes

**File:** `routes/web.php`

Add the availability check routes for AJAX (session-based):

```php
use App\Http\Controllers\Api\AvailabilityController;

Route::middleware(['auth', 'check.active'])->group(function () {
    // ... existing routes ...

    // Availability API (AJAX routes)
    Route::prefix('ajax')->name('ajax.')->group(function () {
        Route::post('/availability/check', [AvailabilityController::class, 'check'])
            ->name('availability.check');
        Route::get('/rooms/{room}/schedule', [AvailabilityController::class, 'roomSchedule'])
            ->name('rooms.schedule');
        Route::get('/rooms/{room}/available-slots', [AvailabilityController::class, 'availableSlots'])
            ->name('rooms.available-slots');
    });
});
```

---

## Task 3.8.4: Enhance Real-Time Availability in Booking Form

**File:** `resources/views/bookings/create.blade.php`

Update the JavaScript section for better real-time feedback:

```javascript
// Add this to the existing script section

// Enhanced availability checking with loading state
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

    // Show loading state
    const container = document.getElementById('availabilityStatus');
    const message = document.getElementById('availabilityMessage');
    container.style.display = 'block';
    message.className = 'alert alert-secondary';
    message.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Checking availability...';
    submitBtn.disabled = true;

    availabilityCheckTimeout = setTimeout(() => {
        fetch('{{ route("ajax.availability.check") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                room_id: roomId,
                date: date,
                start_time: startTime,
                end_time: endTime,
            }),
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network error');
            }
            return response.json();
        })
        .then(data => {
            if (data.available) {
                message.className = 'alert alert-success';
                message.innerHTML = '<i class="bx bx-check-circle me-1"></i> <strong>Available!</strong> This time slot is free.';
                submitBtn.disabled = false;
            } else {
                message.className = 'alert alert-danger';
                let errorHtml = `<i class="bx bx-x-circle me-1"></i> <strong>Not Available</strong><br>${data.message}`;
                
                // Show conflict details if available
                if (data.conflict) {
                    errorHtml += `<br><small class="text-muted">Existing booking: ${data.conflict.time} (${data.conflict.reference})</small>`;
                }
                
                message.innerHTML = errorHtml;
                submitBtn.disabled = true;
            }
        })
        .catch(error => {
            console.error('Availability check failed:', error);
            message.className = 'alert alert-warning';
            message.innerHTML = '<i class="bx bx-error me-1"></i> Could not verify availability. Please try again.';
            submitBtn.disabled = false; // Allow submission, server will validate
        });
    }, 300); // Debounce 300ms
}
```

---

## Task 3.8.5: Handle Concurrent Submission Errors

When the server rejects a booking due to race condition (slot taken between check and submit):

**File:** `app/Http/Controllers/BookingController.php`

The store method should display user-friendly conflict message:

```php
public function store(StoreBookingRequest $request)
{
    try {
        $booking = $this->bookingService->createBooking(
            $request->validated(),
            auth()->user()
        );

        // ... audit logging ...

        return redirect()
            ->route('my-bookings.show', $booking)
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
```

---

## Task 3.8.6: Add Conflict Notification to Form

Show a prominent alert when returning from failed submission:

**File:** `resources/views/bookings/create.blade.php`

Add at the top of the form:

```blade
{{-- Race condition warning --}}
@if(session('error'))
    <div class="alert alert-danger alert-dismissible mb-4">
        <i class="bx bx-error-circle me-2"></i>
        <strong>Booking Failed!</strong>
        <p class="mb-0 mt-2">{{ session('error') }}</p>
        <p class="mb-0 mt-2 small">
            <i class="bx bx-info-circle me-1"></i>
            Tip: The selection below has been preserved. Please choose a different time and try again.
        </p>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
```

---

## Technical Notes

### Why Database Locking?

Without proper locking, two users submitting simultaneously for the same slot could both pass the availability check:

```
User A: Check availability → Available ✓
User B: Check availability → Available ✓
User A: Create booking → Success ✓
User B: Create booking → ALSO Success ✗ (Double booking!)
```

With `lockForUpdate()`:

```
User A: Lock room row, check availability → Available ✓
User B: Try to lock → BLOCKED (waiting for A)
User A: Create booking → Success ✓, Release lock
User B: Lock acquired, check availability → NOT Available
User B: Exception thrown → Friendly error message
```

### PostgreSQL Considerations

The `lockForUpdate()` in Laravel uses `FOR UPDATE` which is supported by PostgreSQL. The transaction with retry (5 retries) handles potential deadlocks gracefully.

---

## Testing Requirements

**File:** `tests/Feature/AutoApprovalTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class AutoApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Room $room;
    protected BookingService $bookingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['status' => 'active']);
        $this->room = Room::factory()->create(['status' => 'active']);
        $this->bookingService = app(BookingService::class);
    }

    public function test_booking_is_auto_approved_immediately()
    {
        $booking = $this->bookingService->createBooking([
            'room_id' => $this->room->id,
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'purpose' => 'Test meeting',
        ], $this->user);

        $this->assertEquals('confirmed', $booking->status);
        $this->assertNotNull($booking->reference_number);
    }

    public function test_cannot_double_book_same_slot()
    {
        // First booking succeeds
        $this->bookingService->createBooking([
            'room_id' => $this->room->id,
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'purpose' => 'First meeting',
        ], $this->user);

        // Second booking for same slot should fail
        $this->expectException(\Exception::class);
        
        $this->bookingService->createBooking([
            'room_id' => $this->room->id,
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
            'start_time' => '09:30', // Overlaps
            'end_time' => '10:30',
            'purpose' => 'Second meeting',
        ], $this->user);
    }

    public function test_availability_check_api()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('ajax.availability.check'), [
                'room_id' => $this->room->id,
                'date' => now()->addDays(5)->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '10:00',
            ]);

        $response->assertStatus(200)
                 ->assertJson(['available' => true]);
    }

    public function test_availability_check_shows_conflict()
    {
        // Create existing booking
        Booking::factory()->create([
            'room_id' => $this->room->id,
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('ajax.availability.check'), [
                'room_id' => $this->room->id,
                'date' => now()->addDays(5)->format('Y-m-d'),
                'start_time' => '09:30',
                'end_time' => '10:30',
            ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'available' => false,
                     'reason' => 'booked',
                 ]);
    }

    public function test_inactive_room_not_available()
    {
        $inactiveRoom = Room::factory()->create(['status' => 'inactive']);

        $response = $this->actingAs($this->user)
            ->postJson(route('ajax.availability.check'), [
                'room_id' => $inactiveRoom->id,
                'date' => now()->addDays(5)->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '10:00',
            ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'available' => false,
                     'reason' => 'room_inactive',
                 ]);
    }
}
```

---

## Acceptance Criteria

- [ ] Bookings are immediately confirmed (no pending status)
- [ ] First valid booking wins the slot (race condition handled)
- [ ] Conflicting bookings are rejected with clear message
- [ ] Real-time availability check works via AJAX
- [ ] Availability API returns detailed conflict info
- [ ] Room schedule API shows all bookings for a date
- [ ] Available slots API calculates open time slots
- [ ] User sees friendly error if slot taken between check and submit
- [ ] Database locking prevents double bookings
- [ ] All tests pass

---

**Next:** [Step 3.9 - Booking Calendar](./step-3.9-booking-calendar.md)
