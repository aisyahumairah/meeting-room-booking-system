# Step 1.6: Dashboards

**Priority:** HIGH | **Ref:** §4.3.1, §4.3.2 | **Dependencies:** Step 1.3, Step 1.4

---

## Objective
Create role-specific dashboards: User Dashboard and Admin Dashboard.

---

## Task 1.6.1: Create Dashboard Controller

**Command:** `php artisan make:controller DashboardController`

**File:** `app/Http/Controllers/DashboardController.php`

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Redirect based on role
        if ($user->canManageBookings()) {
            return $this->adminDashboard();
        }
        
        return $this->userDashboard();
    }

    public function userDashboard()
    {
        $user = Auth::user();
        
        // Placeholder data - will be populated in Phase 3
        $upcomingBookings = collect(); // Booking::where('user_id', $user->id)->upcoming()->take(5)->get();
        $pastBookings = collect(); // Booking::where('user_id', $user->id)->past()->take(5)->get();
        
        $stats = [
            'total_bookings' => 0,
            'this_month' => 0,
            'pending' => 0,
            'cancelled' => 0,
        ];
        
        return view('dashboard.user', compact('upcomingBookings', 'pastBookings', 'stats'));
    }

    public function adminDashboard()
    {
        // Placeholder data - will be populated in Phase 3
        $pendingApprovals = collect();
        $todaysBookings = collect();
        
        $stats = [
            'pending_count' => 0,
            'today_count' => 0,
            'week_total' => 0,
            'month_total' => 0,
            'week_change' => '+0%',
        ];
        
        $topRooms = collect(); // For utilization chart
        
        return view('dashboard.admin', compact('pendingApprovals', 'todaysBookings', 'stats', 'topRooms'));
    }
}
```

---

## Task 1.6.2: Create Dashboard Routes

**File:** `routes/web.php`

```php
Route::middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/dashboard/user', [DashboardController::class, 'userDashboard'])->name('dashboard.user');
    Route::get('/dashboard/admin', [DashboardController::class, 'adminDashboard'])
        ->middleware('role:administrator,director')
        ->name('dashboard.admin');
});
```

---

## Task 1.6.3: Create User Dashboard View

**File:** `resources/views/dashboard/user.blade.php`

**Mockup Reference:** `mrbs-mock-up/pages/dashboard-user.html`

Widgets:

**1. Quick Book Button (prominent)**
```php
<div class="col-12 mb-4">
    <a href="{{ route('bookings.create') }}" class="btn btn-primary btn-lg">
        <i class="bx bx-plus me-2"></i> Book a Room
    </a>
</div>
```

**2. Upcoming Bookings Widget**
```php
<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h5 class="mb-0">Upcoming Bookings</h5>
        <a href="{{ route('bookings.my') }}" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="card-body">
        @forelse($upcomingBookings as $booking)
            <div class="d-flex mb-3">
                <div class="flex-grow-1">
                    <h6 class="mb-1">{{ $booking->room->name }}</h6>
                    <small class="text-muted">
                        {{ $booking->booking_date->format('D, M j') }} • 
                        {{ $booking->start_time }} - {{ $booking->end_time }}
                    </small>
                </div>
                <span class="badge bg-{{ $booking->status_badge }}">{{ $booking->status }}</span>
            </div>
        @empty
            <p class="text-muted mb-0">No upcoming bookings</p>
        @endforelse
    </div>
</div>
```

**3. Past Bookings Widget** (similar structure)

**4. Quick Stats Cards**
```php
<div class="row">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <span class="fw-semibold d-block mb-1">Total Bookings</span>
                <h3 class="card-title mb-0">{{ $stats['total_bookings'] }}</h3>
            </div>
        </div>
    </div>
    <!-- Repeat for this_month, pending, cancelled -->
</div>
```

**5. My Bookings Chart (last 6 months)** - using ApexCharts
```php
<div class="card">
    <div class="card-header">My Booking History</div>
    <div class="card-body">
        <div id="bookingsChart"></div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
<script>
    // Placeholder chart - will use real data in Phase 3
    const options = {
        chart: { type: 'bar', height: 300 },
        series: [{ name: 'Bookings', data: [4, 6, 3, 8, 5, 7] }],
        xaxis: { categories: ['Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'] }
    };
    new ApexCharts(document.querySelector("#bookingsChart"), options).render();
</script>
@endpush
```

---

## Task 1.6.4: Create Admin Dashboard View

**File:** `resources/views/dashboard/admin.blade.php`

**Mockup Reference:** `mrbs-mock-up/pages/dashboard-admin.html`

Widgets:

**1. Pending Approvals Widget**
```php
<div class="card bg-warning text-white">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <div>
                <h3 class="mb-0">{{ $stats['pending_count'] }}</h3>
                <span>Pending Approvals</span>
            </div>
            <div class="avatar">
                <span class="avatar-initial rounded bg-white text-warning">
                    <i class="bx bx-time-five"></i>
                </span>
            </div>
        </div>
        <a href="{{ route('admin.approvals') }}" class="text-white stretched-link">
            <small>View All <i class="bx bx-chevron-right"></i></small>
        </a>
    </div>
</div>
```

**2. Today's Bookings Widget**
```php
<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h5>Today's Bookings</h5>
        <span class="badge bg-primary">{{ $stats['today_count'] }}</span>
    </div>
    <div class="card-body">
        @forelse($todaysBookings as $booking)
            <div class="d-flex mb-2">
                <div class="flex-grow-1">
                    <strong>{{ $booking->start_time }}</strong> - {{ $booking->room->name }}
                    <br><small class="text-muted">{{ $booking->user->name }}</small>
                </div>
            </div>
        @empty
            <p class="text-muted">No bookings today</p>
        @endforelse
    </div>
</div>
```

**3. Booking Statistics Cards**
- This Week total (with % change vs last week)
- This Month total (with % change vs last month)

**4. Room Utilization Chart (Top 5 rooms)**
```php
<div class="card">
    <div class="card-header">Room Utilization</div>
    <div class="card-body">
        <div id="utilizationChart"></div>
    </div>
</div>
```

**5. Quick Actions Panel**
```php
<div class="card">
    <div class="card-header">Quick Actions</div>
    <div class="card-body">
        <a href="{{ route('admin.approvals') }}" class="btn btn-primary mb-2 w-100">
            <i class="bx bx-check-circle me-2"></i> Approve Bookings
        </a>
        <a href="{{ route('admin.bookings') }}" class="btn btn-outline-primary mb-2 w-100">
            <i class="bx bx-list-ul me-2"></i> View All Bookings
        </a>
        <a href="{{ route('admin.reports') }}" class="btn btn-outline-primary mb-2 w-100">
            <i class="bx bx-chart me-2"></i> Generate Report
        </a>
        @can('manage-rooms')
        <a href="{{ route('admin.rooms.create') }}" class="btn btn-outline-primary w-100">
            <i class="bx bx-plus me-2"></i> Add New Room
        </a>
        @endcan
    </div>
</div>
```

---

## Testing Requirements

**File:** `tests/Feature/DashboardTest.php`

Test cases:
- Regular user sees user dashboard
- Regular user cannot access admin dashboard
- Administrator sees admin dashboard
- Director sees admin dashboard
- System admin sees user dashboard (not admin)
- Dashboard displays correct widgets

```bash
php artisan test --filter=DashboardTest
```

---

## Acceptance Criteria
- [ ] User dashboard displays for regular users and system admins
- [ ] Admin dashboard displays for administrators and directors
- [ ] All widgets render without errors (with placeholder data)
- [ ] Quick Book button links to booking create
- [ ] Quick actions panel shows role-appropriate buttons
- [ ] Charts render (with placeholder data)
- [ ] All tests pass

---

**Next:** [Step 1.7 - Audit Trail](./step-1.7-audit-trail.md)
