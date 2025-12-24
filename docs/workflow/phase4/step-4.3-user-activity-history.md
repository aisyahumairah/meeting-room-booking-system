# Step 4.3: User Activity History

**Priority:** MEDIUM | **Ref:** §7.3.2 | **Dependencies:** Step 4.1, Step 4.2  
**Status:** COMPLETE

---

## Objective

Create a dedicated user activity history page accessible from User Management. This page displays a timeline of all actions performed by a specific user, along with calculated statistics like booking counts, cancellation rate, and most frequently booked room.

---

## Design Decision

**User Activity History** is implemented as a **dedicated page** (`/admin/users/{user}/activity`) that:
- Shows user profile information at the top
- Displays calculated statistics (not just filtered logs)
- Provides a timeline of user actions from the audit_logs table
- Links to related entities (bookings, rooms)
- Supports export of user-specific activity

This differs from simply filtering the global audit trail because it includes **user-specific statistics** that require aggregation queries.

---

## Task 4.3.1: Enhance UserController Activity Method

The `activity()` method was scaffolded in Step 4.1. Here's the enhanced implementation:

**File:** `app/Http/Controllers/Admin/UserController.php`

Update the `activity()` method:

```php
/**
 * View user activity history with statistics.
 */
public function activity(Request $request, User $user)
{
    $query = \App\Models\AuditLog::where('actor_id', $user->id);

    // Date range filter
    if ($startDate = $request->input('start_date')) {
        $query->whereDate('created_at', '>=', $startDate);
    }
    if ($endDate = $request->input('end_date')) {
        $query->whereDate('created_at', '<=', $endDate);
    }

    // Date preset
    if ($preset = $request->input('preset')) {
        $this->applyDatePresetToQuery($query, $preset);
    }

    // Action type filter
    if ($eventType = $request->input('event_type')) {
        $query->where('event_type', $eventType);
    }

    $activities = $query->orderBy('created_at', 'desc')->paginate(50)->withQueryString();

    // Get event types for this user's activities
    $eventTypes = \App\Models\AuditLog::where('actor_id', $user->id)
        ->distinct()
        ->pluck('event_type')
        ->sort()
        ->values();

    // Calculate user statistics
    $stats = $this->calculateUserStats($user);

    return view('admin.users.activity', compact('user', 'activities', 'stats', 'eventTypes'));
}

/**
 * Calculate user statistics for activity page.
 */
private function calculateUserStats(User $user): array
{
    // Booking statistics
    $totalBookings = $user->bookings()->count();
    $confirmedBookings = $user->bookings()->where('status', 'confirmed')->count();
    $completedBookings = $user->bookings()->where('status', 'completed')->count();
    $cancelledBookings = $user->bookings()->where('status', 'cancelled')->count();

    // Most frequently booked room
    $mostBookedRoom = $user->bookings()
        ->selectRaw('room_id, count(*) as booking_count')
        ->groupBy('room_id')
        ->orderByDesc('booking_count')
        ->with('room:id,name')
        ->first();

    // Average booking duration
    $avgDuration = $user->bookings()
        ->selectRaw('AVG(EXTRACT(EPOCH FROM (end_time::time - start_time::time)) / 60) as avg_minutes')
        ->first()
        ->avg_minutes;

    // Recent login count (last 30 days)
    $recentLogins = \App\Models\AuditLog::where('actor_id', $user->id)
        ->where('event_type', 'login_success')
        ->where('created_at', '>=', now()->subDays(30))
        ->count();

    return [
        'total_bookings' => $totalBookings,
        'confirmed_bookings' => $confirmedBookings,
        'completed_bookings' => $completedBookings,
        'cancelled_bookings' => $cancelledBookings,
        'cancellation_rate' => $totalBookings > 0 
            ? round(($cancelledBookings / $totalBookings) * 100, 1) 
            : 0,
        'last_login' => $user->last_login_at,
        'account_age_days' => $user->created_at ? $user->created_at->diffInDays(now()) : 0,
        'most_booked_room' => $mostBookedRoom?->room?->name ?? 'None',
        'most_booked_room_count' => $mostBookedRoom?->booking_count ?? 0,
        'avg_booking_duration' => $avgDuration ? round($avgDuration) : 0,
        'recent_logins' => $recentLogins,
    ];
}

/**
 * Apply date preset to activity query.
 */
private function applyDatePresetToQuery($query, string $preset): void
{
    match ($preset) {
        'today' => $query->whereDate('created_at', now()->toDateString()),
        'last_7_days' => $query->whereDate('created_at', '>=', now()->subDays(7)->toDateString()),
        'last_30_days' => $query->whereDate('created_at', '>=', now()->subDays(30)->toDateString()),
        'this_month' => $query->whereMonth('created_at', now()->month)
                              ->whereYear('created_at', now()->year),
        default => null,
    };
}

/**
 * Export user activity history.
 */
public function exportActivity(Request $request, User $user)
{
    $format = $request->input('format', 'csv');
    $filename = "UserActivity_{$user->staff_number}_" . now()->format('YmdHis');

    // Log export
    \App\Services\AuditService::log(
        'user_activity.exported',
        'user',
        $user->id,
        ['format' => $format]
    );

    return \Maatwebsite\Excel\Facades\Excel::download(
        new \App\Exports\UserActivityExport($user, $request->all()),
        "{$filename}.{$format}",
        $format === 'xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV
    );
}
```

---

## Task 4.3.2: Create UserActivityExport Class

```bash
php artisan make:export UserActivityExport
```

**File:** `app/Exports/UserActivityExport.php`

```php
<?php

namespace App\Exports;

use App\Models\AuditLog;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class UserActivityExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithTitle
{
    protected User $user;
    protected array $filters;

    public function __construct(User $user, array $filters = [])
    {
        $this->user = $user;
        $this->filters = $filters;
    }

    public function title(): string
    {
        return "Activity - {$this->user->name}";
    }

    public function query()
    {
        $query = AuditLog::where('actor_id', $this->user->id);

        if (!empty($this->filters['start_date'])) {
            $query->whereDate('created_at', '>=', $this->filters['start_date']);
        }
        if (!empty($this->filters['end_date'])) {
            $query->whereDate('created_at', '<=', $this->filters['end_date']);
        }
        if (!empty($this->filters['event_type'])) {
            $query->where('event_type', $this->filters['event_type']);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'Timestamp',
            'Event Type',
            'Target Type',
            'Target ID',
            'Details',
            'IP Address',
        ];
    }

    public function map($log): array
    {
        return [
            $log->created_at->format('Y-m-d H:i:s'),
            $log->event_type,
            $log->target_type,
            $log->target_id,
            is_array($log->details) ? json_encode($log->details) : $log->details,
            $log->ip_address,
        ];
    }
}
```

---

## Task 4.3.3: Add Export Route

**File:** `routes/web.php`

Add to the admin users routes:

```php
Route::get('users/{user}/activity/export', [Admin\UserController::class, 'exportActivity'])
    ->name('users.activity.export');
```

---

## Task 4.3.4: Create User Activity View

**File:** `resources/views/admin/users/activity.blade.php`

```blade
@extends('layouts.app')

@section('title', 'User Activity - ' . $user->name)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('admin.users.index') }}" class="text-muted text-decoration-none">
                <i class='bx bx-arrow-back me-1'></i> Back to User List
            </a>
            <h4 class="mb-0 mt-2">Activity History</h4>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                <i class='bx bx-download me-1'></i> Export
            </button>
            <ul class="dropdown-menu">
                <li>
                    <a class="dropdown-item" href="{{ route('admin.users.activity.export', array_merge(['user' => $user->id], request()->all(), ['format' => 'csv'])) }}">
                        <i class='bx bx-file me-2'></i> Export as CSV
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ route('admin.users.activity.export', array_merge(['user' => $user->id], request()->all(), ['format' => 'xlsx'])) }}">
                        <i class='bx bx-spreadsheet me-2'></i> Export as Excel
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- User Profile Card -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-lg me-3 bg-label-primary">
                            <span class="avatar-initial rounded-circle fs-4">{{ substr($user->name, 0, 1) }}</span>
                        </div>
                        <div>
                            <h5 class="mb-1">{{ $user->name }}</h5>
                            <p class="mb-0 text-muted">{{ $user->email }}</p>
                            <div class="mt-1">
                                @switch($user->role)
                                    @case('system_admin')
                                        <span class="badge bg-danger">System Admin</span>
                                        @break
                                    @case('director')
                                        <span class="badge bg-purple">Director</span>
                                        @break
                                    @case('administrator')
                                        <span class="badge bg-warning">Administrator</span>
                                        @break
                                    @default
                                        <span class="badge bg-primary">Regular User</span>
                                @endswitch
                                @if($user->status === 'active')
                                    <span class="badge bg-success ms-1">Active</span>
                                @else
                                    <span class="badge bg-secondary ms-1">Inactive</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="row text-center text-md-start">
                        <div class="col-4">
                            <small class="text-muted d-block">Staff Number</small>
                            <span class="fw-semibold">{{ $user->staff_number }}</span>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Department</small>
                            <span class="fw-semibold">{{ $user->department ?? '-' }}</span>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Member Since</small>
                            <span class="fw-semibold">{{ $user->created_at->format('M Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0">{{ $stats['total_bookings'] }}</h3>
                            <small class="text-muted">Total Bookings</small>
                        </div>
                        <div class="avatar bg-label-primary">
                            <i class='bx bx-calendar fs-4'></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0 {{ $stats['cancellation_rate'] > 20 ? 'text-warning' : '' }}">
                                {{ $stats['cancellation_rate'] }}%
                            </h3>
                            <small class="text-muted">Cancellation Rate</small>
                        </div>
                        <div class="avatar bg-label-{{ $stats['cancellation_rate'] > 20 ? 'warning' : 'success' }}">
                            <i class='bx bx-x-circle fs-4'></i>
                        </div>
                    </div>
                    <div class="small text-muted mt-1">
                        {{ $stats['cancelled_bookings'] }} of {{ $stats['total_bookings'] }} cancelled
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0">{{ $stats['account_age_days'] }}</h3>
                            <small class="text-muted">Days Since Joined</small>
                        </div>
                        <div class="avatar bg-label-info">
                            <i class='bx bx-time-five fs-4'></i>
                        </div>
                    </div>
                    <div class="small text-muted mt-1">
                        Last login: {{ $stats['last_login'] ? $stats['last_login']->diffForHumans() : 'Never' }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 text-truncate" style="max-width: 120px;">{{ $stats['most_booked_room'] }}</h6>
                            <small class="text-muted">Most Booked Room</small>
                        </div>
                        <div class="avatar bg-label-secondary">
                            <i class='bx bx-door-open fs-4'></i>
                        </div>
                    </div>
                    @if($stats['most_booked_room_count'] > 0)
                    <div class="small text-muted mt-1">
                        {{ $stats['most_booked_room_count'] }} bookings
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Stats Row -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">Confirmed</small>
                            <h5 class="mb-0 text-success">{{ $stats['confirmed_bookings'] }}</h5>
                        </div>
                        <div>
                            <small class="text-muted">Completed</small>
                            <h5 class="mb-0 text-secondary">{{ $stats['completed_bookings'] }}</h5>
                        </div>
                        <div>
                            <small class="text-muted">Cancelled</small>
                            <h5 class="mb-0 text-danger">{{ $stats['cancelled_bookings'] }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">Avg. Duration</small>
                            <h5 class="mb-0">{{ $stats['avg_booking_duration'] }} min</h5>
                        </div>
                        <div class="avatar bg-label-info">
                            <i class='bx bx-timer fs-4'></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">Logins (Last 30 Days)</small>
                            <h5 class="mb-0">{{ $stats['recent_logins'] }}</h5>
                        </div>
                        <div class="avatar bg-label-success">
                            <i class='bx bx-log-in fs-4'></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.users.activity', $user) }}" class="row g-3">
                <!-- Quick Presets -->
                <div class="col-12">
                    <div class="btn-group flex-wrap" role="group">
                        <a href="{{ route('admin.users.activity', ['user' => $user->id, 'preset' => 'today']) }}" 
                           class="btn btn-sm {{ request('preset') == 'today' ? 'btn-primary' : 'btn-outline-primary' }}">Today</a>
                        <a href="{{ route('admin.users.activity', ['user' => $user->id, 'preset' => 'last_7_days']) }}" 
                           class="btn btn-sm {{ request('preset') == 'last_7_days' ? 'btn-primary' : 'btn-outline-primary' }}">Last 7 Days</a>
                        <a href="{{ route('admin.users.activity', ['user' => $user->id, 'preset' => 'last_30_days']) }}" 
                           class="btn btn-sm {{ request('preset') == 'last_30_days' ? 'btn-primary' : 'btn-outline-primary' }}">Last 30 Days</a>
                        <a href="{{ route('admin.users.activity', ['user' => $user->id, 'preset' => 'this_month']) }}" 
                           class="btn btn-sm {{ request('preset') == 'this_month' ? 'btn-primary' : 'btn-outline-primary' }}">This Month</a>
                        <a href="{{ route('admin.users.activity', $user) }}" 
                           class="btn btn-sm {{ !request('preset') && !request('start_date') ? 'btn-primary' : 'btn-outline-primary' }}">All Time</a>
                    </div>
                </div>

                <!-- Custom Filters -->
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Event Type</label>
                    <select name="event_type" class="form-select">
                        <option value="">All Events</option>
                        @foreach($eventTypes as $type)
                            <option value="{{ $type }}" {{ request('event_type') == $type ? 'selected' : '' }}>
                                {{ ucwords(str_replace(['_', '.'], ' ', $type)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('admin.users.activity', $user) }}" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Activity Timeline -->
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">Activity Timeline ({{ $activities->total() }} events)</h6>
        </div>
        <div class="card-body">
            @forelse($activities as $activity)
            <div class="d-flex mb-3 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                <div class="timeline-indicator me-3">
                    @php
                        $iconClass = match(true) {
                            str_contains($activity->event_type, 'login') => 'bx-log-in',
                            str_contains($activity->event_type, 'logout') => 'bx-log-out',
                            str_contains($activity->event_type, 'booking') => 'bx-calendar',
                            str_contains($activity->event_type, 'password') => 'bx-key',
                            str_contains($activity->event_type, 'profile') => 'bx-user',
                            str_contains($activity->event_type, 'room') => 'bx-door-open',
                            default => 'bx-dots-horizontal-rounded',
                        };
                        $bgClass = match(true) {
                            str_contains($activity->event_type, 'success') || str_contains($activity->event_type, 'created') => 'bg-label-success',
                            str_contains($activity->event_type, 'failed') || str_contains($activity->event_type, 'cancelled') || str_contains($activity->event_type, 'deleted') => 'bg-label-danger',
                            str_contains($activity->event_type, 'updated') => 'bg-label-warning',
                            default => 'bg-label-primary',
                        };
                    @endphp
                    <div class="avatar avatar-sm {{ $bgClass }}">
                        <i class='bx {{ $iconClass }}'></i>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="badge {{ $bgClass }} mb-1">
                                {{ ucwords(str_replace(['_', '.'], ' ', $activity->event_type)) }}
                            </span>
                            @if($activity->target_type && $activity->target_id)
                            <span class="ms-2 text-muted">
                                @if($activity->target_type === 'booking')
                                    <a href="{{ route('admin.bookings.show', $activity->target_id) }}" class="text-decoration-none">
                                        Booking #{{ $activity->target_id }}
                                    </a>
                                @elseif($activity->target_type === 'room')
                                    <a href="{{ route('rooms.show', $activity->target_id) }}" class="text-decoration-none">
                                        Room #{{ $activity->target_id }}
                                    </a>
                                @else
                                    {{ ucfirst($activity->target_type) }} #{{ $activity->target_id }}
                                @endif
                            </span>
                            @endif
                        </div>
                        <small class="text-muted">
                            {{ $activity->created_at->format('M d, Y H:i') }}
                        </small>
                    </div>
                    @if($activity->details)
                    <div class="text-muted small mt-1">
                        @if(is_array($activity->details))
                            @foreach($activity->details as $key => $value)
                                <span class="me-3">
                                    <strong>{{ ucwords(str_replace('_', ' ', $key)) }}:</strong>
                                    @if(is_array($value))
                                        {{ json_encode($value) }}
                                    @else
                                        {{ $value }}
                                    @endif
                                </span>
                            @endforeach
                        @else
                            {{ $activity->details }}
                        @endif
                    </div>
                    @endif
                    <div class="text-muted small mt-1">
                        <i class='bx bx-globe'></i> {{ $activity->ip_address ?? 'Unknown IP' }}
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-4">
                <i class='bx bx-history fs-1 text-muted'></i>
                <p class="text-muted mt-2">No activity found for the selected criteria.</p>
            </div>
            @endforelse
        </div>
        @if($activities->hasPages())
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted">Showing {{ $activities->firstItem() }}-{{ $activities->lastItem() }} of {{ $activities->total() }} events</span>
                {{ $activities->links() }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
```

---

## Testing Requirements

> **Note:** When creating test users with the factory, use `->passwordChanged()` to set `must_change_password` to `false`. Otherwise, the `MustChangePassword` middleware will redirect users causing tests to receive 302 responses instead of 200.

**File:** `tests/Feature/UserActivityHistoryTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Booking;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserActivityHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $sysAdmin;
    protected User $targetUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->sysAdmin = User::factory()->passwordChanged()->create(['role' => 'system_admin']);
        $this->targetUser = User::factory()->passwordChanged()->create(['role' => 'regular_user']);

        // Create some audit logs for target user
        AuditService::log('login_success', 'user', $this->targetUser->id);
        
        // Simulate actor being target user
        $this->actingAs($this->targetUser);
        AuditService::log('booking.created', 'booking', 1);
        AuditService::log('booking.cancelled', 'booking', 1, ['reason' => 'test']);
    }

    public function test_can_view_user_activity_page(): void
    {
        $response = $this->actingAs($this->sysAdmin)
            ->get(route('admin.users.activity', $this->targetUser));

        $response->assertStatus(200);
        $response->assertSee($this->targetUser->name);
        $response->assertSee('Activity History');
    }

    public function test_displays_user_statistics(): void
    {
        // Create some bookings for the user
        Booking::factory()->count(3)->create(['user_id' => $this->targetUser->id, 'status' => 'confirmed']);
        Booking::factory()->create(['user_id' => $this->targetUser->id, 'status' => 'cancelled']);

        $response = $this->actingAs($this->sysAdmin)
            ->get(route('admin.users.activity', $this->targetUser));

        $response->assertStatus(200);
        $response->assertSee('Total Bookings');
        $response->assertSee('Cancellation Rate');
    }

    public function test_displays_activity_timeline(): void
    {
        $response = $this->actingAs($this->sysAdmin)
            ->get(route('admin.users.activity', $this->targetUser));

        $response->assertStatus(200);
        $response->assertSee('Activity Timeline');
    }

    public function test_date_filter_works(): void
    {
        $response = $this->actingAs($this->sysAdmin)
            ->get(route('admin.users.activity', [
                'user' => $this->targetUser->id,
                'preset' => 'today',
            ]));

        $response->assertStatus(200);
    }

    public function test_event_type_filter_works(): void
    {
        $response = $this->actingAs($this->sysAdmin)
            ->get(route('admin.users.activity', [
                'user' => $this->targetUser->id,
                'event_type' => 'login_success',
            ]));

        $response->assertStatus(200);
    }

    public function test_regular_user_cannot_access(): void
    {
        $response = $this->actingAs($this->targetUser)
            ->get(route('admin.users.activity', $this->targetUser));

        $response->assertStatus(403);
    }

    public function test_export_csv_works(): void
    {
        $response = $this->actingAs($this->sysAdmin)
            ->get(route('admin.users.activity.export', [
                'user' => $this->targetUser->id,
                'format' => 'csv',
            ]));

        $response->assertStatus(200);
    }

    public function test_calculates_cancellation_rate_correctly(): void
    {
        Booking::factory()->count(4)->create([
            'user_id' => $this->targetUser->id, 
            'status' => 'confirmed'
        ]);
        Booking::factory()->create([
            'user_id' => $this->targetUser->id, 
            'status' => 'cancelled'
        ]);

        $response = $this->actingAs($this->sysAdmin)
            ->get(route('admin.users.activity', $this->targetUser));

        $response->assertStatus(200);
        // 1 cancelled out of 5 = 20%
        $response->assertSee('20');
    }
}
```

---

## Acceptance Criteria

- [x] User activity page displays user profile at top
- [x] Statistics cards show: total bookings, cancellation rate, account age, most booked room
- [x] Additional stats show: confirmed/completed/cancelled counts, avg duration, recent logins
- [x] Activity timeline displays events in reverse chronological order
- [x] Each event shows: type badge, target link, timestamp, details, IP
- [x] Date preset filters work (Today, Last 7 Days, etc.)
- [x] Custom date range filter works
- [x] Event type filter works
- [x] Pagination works (50 per page)
- [x] Export to CSV/Excel works
- [x] Links to related entities (booking, room) work
- [x] Only Director/SysAdmin can access
- [x] All tests pass: `php artisan test --filter=UserActivityHistoryTest`

---

**Next:** [Step 4.4 - System Configuration](./step-4.4-system-configuration.md)
