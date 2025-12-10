# Step 3.11: Audit Logging for Bookings

**Priority:** MEDIUM | **Ref:** §6.5 | **Dependencies:** Phase 1 AuditService  
**Status:** TODO

---

## Objective

Ensure all booking-related events are properly logged in the audit trail for compliance and tracking purposes.

---

## Overview

The AuditService from Phase 1 handles the actual logging. This step focuses on:

1. Ensuring all booking events are logged consistently
2. Adding booking-specific event types
3. Creating a view for booking audit history

---

## Task 3.11.1: Define Booking Audit Event Types

Create constants for booking-related audit events:

**File:** `app/Services/AuditService.php`

Add these event type constants (if not using an enum):

```php
// Booking-related audit events
const BOOKING_CREATED = 'booking_created';
const BOOKING_UPDATED = 'booking_updated';
const BOOKING_CANCELLED = 'booking_cancelled';
const BOOKING_AUTO_COMPLETED = 'booking_auto_completed';
const BOOKING_SERIES_CREATED = 'booking_series_created';
const BOOKING_SERIES_UPDATED = 'booking_series_updated';
const BOOKING_SERIES_CANCELLED = 'booking_series_cancelled';
```

---

## Task 3.11.2: Verify Booking Controller Logs Events

Ensure all booking controller actions log to audit trail. Review and update:

**File:** `app/Http/Controllers/BookingController.php`

For booking creation:

```php
// In store() method, after successful creation
$this->auditService->log(
    'booking_created',
    'booking',
    $booking->id,
    [
        'reference' => $booking->reference_number,
        'room' => $booking->room->name,
        'room_id' => $booking->room_id,
        'user' => $booking->user->name,
        'user_id' => $booking->user_id,
        'date' => $booking->booking_date->format('Y-m-d'),
        'time' => $booking->time_range,
        'purpose' => Str::limit($booking->purpose, 100),
        'created_by' => auth()->user()->name,
        'is_on_behalf' => $booking->user_id !== auth()->id(),
    ]
);
```

For booking update:

```php
// In update() method
$this->auditService->log(
    'booking_updated',
    'booking',
    $booking->id,
    [
        'reference' => $booking->reference_number,
        'updated_by' => auth()->user()->name,
        'changes' => [
            'room' => $booking->wasChanged('room_id') ? [
                'from' => Room::find($booking->getOriginal('room_id'))?->name,
                'to' => $booking->room->name,
            ] : null,
            'date' => $booking->wasChanged('booking_date') ? [
                'from' => $booking->getOriginal('booking_date'),
                'to' => $booking->booking_date->format('Y-m-d'),
            ] : null,
            'time' => $booking->wasChanged('start_time') || $booking->wasChanged('end_time') ? [
                'from' => $booking->getOriginal('start_time') . '-' . $booking->getOriginal('end_time'),
                'to' => $booking->time_range,
            ] : null,
        ],
    ]
);
```

For booking cancellation:

```php
// In destroy() method
$this->auditService->log(
    'booking_cancelled',
    'booking',
    $booking->id,
    [
        'reference' => $booking->reference_number,
        'cancelled_by' => auth()->user()->name,
        'reason' => $request->cancellation_reason,
        'is_admin_cancel' => $booking->user_id !== auth()->id(),
        'original_date' => $booking->booking_date->format('Y-m-d'),
        'original_time' => $booking->time_range,
        'room' => $booking->room->name,
        'booking_owner' => $booking->user->name,
    ]
);
```

---

## Task 3.11.3: Log Series Events

**File:** `app/Http/Controllers/BookingController.php`

For recurring booking creation:

```php
// In storeRecurring() method
$this->auditService->log(
    'booking_series_created',
    'booking_series',
    $series->id,
    [
        'reference' => $series->reference_number,
        'room' => $series->room->name,
        'user' => $series->user->name,
        'recurrence' => $series->recurrence_description,
        'start_date' => $series->start_date->format('Y-m-d'),
        'end_date' => $series->end_date->format('Y-m-d'),
        'occurrences' => $series->bookings()->count(),
        'created_by' => auth()->user()->name,
    ]
);
```

For series cancellation:

```php
$this->auditService->log(
    'booking_series_cancelled',
    'booking_series',
    $series->id,
    [
        'reference' => $series->reference_number,
        'cancelled_by' => auth()->user()->name,
        'reason' => $request->cancellation_reason,
        'bookings_cancelled' => $count,
        'room' => $series->room->name,
        'series_owner' => $series->user->name,
    ]
);
```

---

## Task 3.11.4: Create Booking Audit History View

Add a section in the booking detail page to show audit history:

**File:** `resources/views/bookings/show.blade.php`

Add below the main content:

```blade
{{-- Audit History (Admin only) --}}
@if(auth()->user()->canManageBookings())
<div class="card mt-4">
    <div class="card-header">
        <h6 class="mb-0"><i class="bx bx-history me-1"></i> Audit History</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Date/Time</th>
                    <th>Action</th>
                    <th>User</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                @forelse($booking->auditLogs()->orderBy('created_at', 'desc')->limit(20)->get() as $log)
                    <tr>
                        <td class="small">{{ $log->created_at->format('M d, Y H:i') }}</td>
                        <td>
                            <span class="badge bg-{{ $log->action === 'booking_cancelled' ? 'danger' : 'primary' }}">
                                {{ str_replace('_', ' ', ucfirst($log->action)) }}
                            </span>
                        </td>
                        <td class="small">{{ $log->user?->name ?? 'System' }}</td>
                        <td class="small">
                            @if(is_array($log->metadata))
                                @foreach(array_slice($log->metadata, 0, 3) as $key => $value)
                                    @if(!is_array($value))
                                        <span class="text-muted">{{ $key }}:</span> {{ $value }}<br>
                                    @endif
                                @endforeach
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-3">No audit history available</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif
```

---

## Task 3.11.5: Add Audit Log Relationship to Booking Model

**File:** `app/Models/Booking.php`

Add the relationship:

```php
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Get audit logs for this booking
 */
public function auditLogs(): MorphMany
{
    return $this->morphMany(AuditLog::class, 'auditable', 'entity_type', 'entity_id');
}
```

Note: The relationship depends on how AuditLog was implemented in Phase 1. Adjust field names as needed.

If AuditLog doesn't use polymorphic relationships, use a regular query:

```php
/**
 * Get audit logs for this booking
 */
public function getAuditLogsAttribute()
{
    return AuditLog::where('entity_type', 'booking')
                   ->where('entity_id', $this->id)
                   ->orderBy('created_at', 'desc')
                   ->get();
}
```

---

## Task 3.11.6: Create Audit Log Filter for Bookings

Allow admins to filter audit logs by booking-related events:

**File:** `app/Http/Controllers/Admin/AuditLogController.php`

Add booking filter option:

```php
public function index(Request $request)
{
    $query = AuditLog::with('user')->orderBy('created_at', 'desc');

    // Filter by entity type
    if ($request->filled('entity_type')) {
        $entityType = $request->entity_type;
        
        if ($entityType === 'bookings') {
            $query->whereIn('entity_type', ['booking', 'booking_series']);
        } else {
            $query->where('entity_type', $entityType);
        }
    }

    // Filter by action
    if ($request->filled('action')) {
        $query->where('action', 'like', '%' . $request->action . '%');
    }

    // Filter by date range
    if ($request->filled('date_from')) {
        $query->whereDate('created_at', '>=', $request->date_from);
    }

    if ($request->filled('date_to')) {
        $query->whereDate('created_at', '<=', $request->date_to);
    }

    $logs = $query->paginate(50);

    return view('admin.audit-logs.index', compact('logs'));
}
```

---

## Task 3.11.7: Audit Event Summary

Ensure these events are being logged throughout the booking lifecycle:

| Event | When | Logged By |
|-------|------|-----------|
| `booking_created` | New one-time booking | BookingController@store |
| `booking_updated` | Booking edited | BookingController@update |
| `booking_cancelled` | Booking cancelled | BookingController@destroy |
| `booking_auto_completed` | Status set to completed | CompleteExpiredBookings command |
| `booking_series_created` | New recurring series | BookingController@storeRecurring |
| `booking_series_updated` | Series edited | BookingController@update |
| `booking_series_cancelled` | Series cancelled | BookingController@destroy |

---

## Testing Requirements

**File:** `tests/Feature/BookingAuditTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use App\Models\AuditLog;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingAuditTest extends TestCase
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

    public function test_booking_creation_is_logged()
    {
        $this->actingAs($this->user)
            ->post(route('bookings.store'), [
                'room_id' => $this->room->id,
                'booking_date' => now()->addDays(5)->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '10:00',
                'purpose' => 'Test meeting',
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'booking_created',
            'entity_type' => 'booking',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_booking_cancellation_is_logged()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'confirmed',
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $this->actingAs($this->user)
            ->delete(route('my-bookings.destroy', $booking), [
                'cancellation_reason' => 'Meeting cancelled',
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'booking_cancelled',
            'entity_type' => 'booking',
            'entity_id' => $booking->id,
        ]);
    }

    public function test_audit_log_contains_required_metadata()
    {
        $this->actingAs($this->user)
            ->post(route('bookings.store'), [
                'room_id' => $this->room->id,
                'booking_date' => now()->addDays(5)->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '10:00',
                'purpose' => 'Test meeting',
            ]);

        $log = AuditLog::where('action', 'booking_created')->first();
        
        $this->assertNotNull($log);
        $this->assertIsArray($log->metadata);
        $this->assertArrayHasKey('reference', $log->metadata);
        $this->assertArrayHasKey('room', $log->metadata);
        $this->assertArrayHasKey('date', $log->metadata);
    }

    public function test_admin_can_view_booking_audit_history()
    {
        $admin = User::factory()->create(['role' => 'administrator', 'status' => 'active']);
        $booking = Booking::factory()->create();

        // Create some audit logs
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'booking_created',
            'entity_type' => 'booking',
            'entity_id' => $booking->id,
            'metadata' => ['reference' => $booking->reference_number],
        ]);

        $response = $this->actingAs($admin)
            ->get(route('my-bookings.show', $booking));

        $response->assertStatus(200);
        $response->assertSee('Audit History');
    }
}
```

---

## Audit Log Data Structure

Each audit log entry should contain:

```json
{
    "id": 123,
    "user_id": 1,
    "action": "booking_cancelled",
    "entity_type": "booking",
    "entity_id": 45,
    "ip_address": "192.168.1.100",
    "user_agent": "Mozilla/5.0 ...",
    "metadata": {
        "reference": "BK-2025-00045",
        "cancelled_by": "John Admin",
        "reason": "Room needed for emergency meeting",
        "is_admin_cancel": true,
        "original_date": "2025-12-15",
        "original_time": "09:00 - 11:00",
        "room": "Conference Room A",
        "booking_owner": "Jane User"
    },
    "created_at": "2025-12-10 14:30:00"
}
```

---

## Acceptance Criteria

- [ ] All booking creations are logged
- [ ] All booking updates are logged with change details
- [ ] All booking cancellations are logged with reasons
- [ ] All series creations are logged
- [ ] All series cancellations are logged
- [ ] Auto-completed bookings are logged by system
- [ ] Admins can view audit history on booking detail page
- [ ] Audit logs contain meaningful metadata
- [ ] Logs include who performed the action
- [ ] Logs include IP address and user agent
- [ ] All tests pass

---

## Phase 3 Completion

With this step complete, Phase 3 (Booking Management) is finished! 

**Summary of Phase 3 deliverables:**
- ✅ Database schema (bookings, booking_series)
- ✅ One-time booking creation with auto-approval
- ✅ Recurring booking support
- ✅ My Bookings page (list & calendar views)
- ✅ Edit booking functionality
- ✅ Cancel booking functionality
- ✅ All Bookings admin view
- ✅ Auto-approval system with race condition prevention
- ✅ Global booking calendar
- ✅ Automatic status updates (confirmed → completed)
- ✅ Comprehensive audit logging

**Next Phase:** [Phase 4 - Notifications & Reporting](../phase4/README.md)
