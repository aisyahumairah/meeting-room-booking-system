# Step 3.6: Cancel Booking

**Priority:** HIGH | **Ref:** §6.3.3 | **Dependencies:** Step 3.4  
**Status:** TODO

---

## Objective

Implement booking cancellation with required reason, support for series cancellation, and proper authorization. Cancelling a recurring booking cancels the entire series.

---

## Task 3.6.1: Create Cancel Booking Request

```bash
php artisan make:request CancelBookingRequest
```

**File:** `app/Http/Requests/CancelBookingRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $booking = $this->route('booking');
        $user = auth()->user();

        // Admin/Director can cancel any booking
        if ($user->canManageBookings()) {
            return true;
        }

        // Regular users can only cancel their own confirmed bookings
        return $booking->user_id === $user->id && $booking->is_cancellable;
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'cancellation_reason.required' => 'Please provide a reason for cancellation.',
            'cancellation_reason.max' => 'Cancellation reason cannot exceed 500 characters.',
        ];
    }
}
```

---

## Task 3.6.2: Add Cancel Methods to BookingService

**File:** `app/Services/BookingService.php`

Add these methods:

```php
/**
 * Cancel a booking
 */
public function cancelBooking(Booking $booking, string $reason, User $cancelledBy): Booking
{
    return DB::transaction(function () use ($booking, $reason, $cancelledBy) {
        $booking->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_by' => $cancelledBy->id,
            'cancelled_at' => now(),
        ]);

        Log::info('Booking cancelled', [
            'booking_id' => $booking->id,
            'reference' => $booking->reference_number,
            'cancelled_by' => $cancelledBy->id,
            'reason' => $reason,
        ]);

        // TODO: Send cancellation email (Phase 4)
        // If admin cancelled someone else's booking, notify the owner

        return $booking->fresh();
    });
}

/**
 * Cancel an entire booking series
 */
public function cancelSeries(BookingSeries $series, string $reason, User $cancelledBy): int
{
    return DB::transaction(function () use ($series, $reason, $cancelledBy) {
        $count = $series->bookings()
            ->where('status', 'confirmed')
            ->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_by' => $cancelledBy->id,
                'cancelled_at' => now(),
            ]);

        Log::info('Booking series cancelled', [
            'series_id' => $series->id,
            'reference' => $series->reference_number,
            'cancelled_by' => $cancelledBy->id,
            'reason' => $reason,
            'bookings_cancelled' => $count,
        ]);

        // TODO: Send single cancellation email for series (Phase 4)

        return $count;
    });
}
```

---

## Task 3.6.3: Add Cancel Controller Method

**File:** `app/Http/Controllers/BookingController.php`

Add this method:

```php
use App\Http\Requests\CancelBookingRequest;

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
                    'is_admin_cancel' => $isAdminCancel,
                    'bookings_cancelled' => $count,
                    'reason' => $request->cancellation_reason,
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
                    'is_admin_cancel' => $isAdminCancel,
                    'reason' => $request->cancellation_reason,
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
```

---

## Task 3.6.4: Add Cancel Route

**File:** `routes/web.php`

```php
Route::middleware(['auth', 'check.active'])->group(function () {
    // ... existing routes ...

    // Cancel booking (DELETE method)
    Route::delete('/my-bookings/{booking}', [BookingController::class, 'destroy'])
        ->name('my-bookings.destroy');
});
```

---

## Task 3.6.5: Update Cancel Modal (Series Warning)

**File:** `resources/views/bookings/partials/cancel-modal.blade.php`

Update to handle series cancellation warning:

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
                    
                    {{-- Series warning (shown dynamically) --}}
                    <div id="seriesWarning" class="alert alert-warning" style="display: none;">
                        <i class="bx bx-repeat me-1"></i>
                        <strong>Recurring Booking:</strong> This will cancel the <strong>entire series</strong> 
                        (<span id="seriesCount">0</span> bookings).
                    </div>

                    <div class="alert alert-danger">
                        <i class="bx bx-error-circle me-1"></i>
                        <strong>This action cannot be undone.</strong> The room will become available for others to book.
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Cancellation Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="cancellation_reason" rows="3" 
                                  required maxlength="500" 
                                  placeholder="Please provide a reason for cancellation..."></textarea>
                        <div class="form-text">Maximum 500 characters. This will be logged for records.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bx bx-arrow-back me-1"></i> Go Back
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bx bx-x me-1"></i> Confirm Cancellation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmCancel(bookingId, reference, isRecurring = false, seriesCount = 0) {
    document.getElementById('cancelRef').textContent = reference;
    document.getElementById('cancelForm').action = `/my-bookings/${bookingId}`;
    
    // Show/hide series warning
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
```

---

## Task 3.6.6: Update Booking List to Pass Series Info

**File:** `resources/views/bookings/my.blade.php`

Update the cancel link in the table:

```blade
@if($booking->is_cancellable)
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
```

---

## Testing Requirements

**File:** `tests/Feature/CancelBookingTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use App\Models\BookingSeries;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CancelBookingTest extends TestCase
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

    public function test_user_can_cancel_own_confirmed_booking()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'confirmed',
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('my-bookings.destroy', $booking), [
                'cancellation_reason' => 'Meeting rescheduled',
            ]);

        $response->assertRedirect(route('my-bookings'));
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Meeting rescheduled',
            'cancelled_by' => $this->user->id,
        ]);
    }

    public function test_cancellation_reason_is_required()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'confirmed',
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('my-bookings.destroy', $booking), [
                'cancellation_reason' => '',
            ]);

        $response->assertSessionHasErrors('cancellation_reason');
    }

    public function test_user_cannot_cancel_others_booking()
    {
        $otherUser = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('my-bookings.destroy', $booking), [
                'cancellation_reason' => 'Test',
            ]);

        $response->assertStatus(403);
    }

    public function test_user_cannot_cancel_already_cancelled_booking()
    {
        $booking = Booking::factory()->cancelled()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('my-bookings.destroy', $booking), [
                'cancellation_reason' => 'Test',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_cancel_any_booking()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'confirmed',
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('my-bookings.destroy', $booking), [
                'cancellation_reason' => 'Admin cancelled - room needed',
            ]);

        $response->assertRedirect(route('admin.bookings.index'));
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
            'cancelled_by' => $this->admin->id,
        ]);
    }

    public function test_cancelling_recurring_booking_cancels_entire_series()
    {
        // Create a series with multiple bookings
        $series = BookingSeries::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
        ]);

        $bookings = [];
        for ($i = 0; $i < 5; $i++) {
            $bookings[] = Booking::factory()->create([
                'user_id' => $this->user->id,
                'room_id' => $this->room->id,
                'series_id' => $series->id,
                'status' => 'confirmed',
                'booking_date' => now()->addDays($i + 1)->format('Y-m-d'),
            ]);
        }

        // Cancel using first booking
        $response = $this->actingAs($this->user)
            ->delete(route('my-bookings.destroy', $bookings[0]), [
                'cancellation_reason' => 'Series no longer needed',
            ]);

        $response->assertRedirect(route('my-bookings'));

        // All bookings in series should be cancelled
        foreach ($bookings as $booking) {
            $this->assertDatabaseHas('bookings', [
                'id' => $booking->id,
                'status' => 'cancelled',
            ]);
        }
    }

    public function test_cancelled_at_timestamp_is_recorded()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'confirmed',
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $this->actingAs($this->user)
            ->delete(route('my-bookings.destroy', $booking), [
                'cancellation_reason' => 'Test',
            ]);

        $booking->refresh();
        $this->assertNotNull($booking->cancelled_at);
        $this->assertTrue($booking->cancelled_at->isToday());
    }

    public function test_room_becomes_available_after_cancellation()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'status' => 'confirmed',
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '11:00',
        ]);

        // Room should not be available before cancellation
        $this->assertFalse($this->room->isAvailable(
            now()->addDays(5)->format('Y-m-d'),
            '09:00',
            '11:00'
        ));

        // Cancel booking
        $this->actingAs($this->user)
            ->delete(route('my-bookings.destroy', $booking), [
                'cancellation_reason' => 'Test',
            ]);

        // Room should be available after cancellation
        $this->assertTrue($this->room->isAvailable(
            now()->addDays(5)->format('Y-m-d'),
            '09:00',
            '11:00'
        ));
    }
}
```

---

## Acceptance Criteria

- [ ] Cancel button shows for confirmed bookings only
- [ ] Cancellation reason is required (validation)
- [ ] Reason is stored in database (max 500 chars)
- [ ] Cancelled_by and cancelled_at are recorded
- [ ] User can cancel their own confirmed bookings
- [ ] User cannot cancel already cancelled/completed bookings
- [ ] User cannot cancel other users' bookings
- [ ] Admin/Director can cancel any booking
- [ ] Cancelling recurring booking cancels entire series
- [ ] Series cancellation shows warning with count
- [ ] Room becomes available immediately after cancellation
- [ ] Audit log entry created for cancellation
- [ ] Success message displays after cancellation
- [ ] All tests pass

---

**Next:** [Step 3.7 - All Bookings](./step-3.7-all-bookings.md)
