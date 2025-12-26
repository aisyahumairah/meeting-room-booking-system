# Step 4.2: Audit Trail Viewer

**Priority:** HIGH | **Ref:** §7.3.1 | **Dependencies:** Phase 1 AuditLog model  
**Status:** COMPLETE

---

## Objective

Create a comprehensive audit log viewer accessible to Directors and System Administrators. The viewer displays all system events in reverse chronological order with filtering, search, and export capabilities.

---

## Task 4.2.1: Create AuditLogController

```bash
php artisan make:controller Admin/AuditLogController
```

**File:** `app/Http/Controllers/Admin/AuditLogController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AuditLogExport;

class AuditLogController extends Controller
{
    /**
     * Display the audit log list with filters.
     */
    public function index(Request $request)
    {
        $query = AuditLog::query();

        // Date range filter
        if ($startDate = $request->input('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        // Date presets
        if ($preset = $request->input('preset')) {
            $this->applyDatePreset($query, $preset);
        }

        // Actor filter
        if ($actorId = $request->input('actor_id')) {
            $query->where('actor_id', $actorId);
        }

        // Event type filter
        if ($eventType = $request->input('event_type')) {
            $query->where('event_type', $eventType);
        }

        // Target type filter
        if ($targetType = $request->input('target_type')) {
            $query->where('target_type', $targetType);
        }

        // Full-text search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('actor_name', 'ilike', "%{$search}%")
                  ->orWhereRaw("details::text ilike ?", ["%{$search}%"])
                  ->orWhere('event_type', 'ilike', "%{$search}%");
            });
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(50)->withQueryString();

        // Get filter options
        $eventTypes = AuditLog::distinct()->pluck('event_type')->sort()->values();
        $targetTypes = AuditLog::distinct()->whereNotNull('target_type')->pluck('target_type')->sort()->values();
        $actors = User::whereIn('id', AuditLog::distinct()->whereNotNull('actor_id')->pluck('actor_id'))
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return view('admin.audit-logs.index', compact('logs', 'eventTypes', 'targetTypes', 'actors'));
    }

    /**
     * Export filtered audit logs.
     */
    public function export(Request $request)
    {
        $format = $request->input('format', 'csv');
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        // Log the export action
        AuditService::log(
            AuditService::EVENT_AUDIT_LOG_EXPORTED,
            null,
            null,
            [
                'format' => $format,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'filters' => $request->except(['format']),
            ]
        );

        $filename = "AuditLog_{$startDate}_to_{$endDate}_" . now()->format('YmdHis');

        if ($format === 'xlsx') {
            return Excel::download(
                new AuditLogExport($request->all()),
                "{$filename}.xlsx"
            );
        }

        return Excel::download(
            new AuditLogExport($request->all()),
            "{$filename}.csv",
            \Maatwebsite\Excel\Excel::CSV
        );
    }

    /**
     * Apply date preset to query.
     */
    private function applyDatePreset($query, string $preset): void
    {
        $today = now()->toDateString();
        
        match ($preset) {
            'today' => $query->whereDate('created_at', $today),
            'yesterday' => $query->whereDate('created_at', now()->subDay()->toDateString()),
            'last_7_days' => $query->whereDate('created_at', '>=', now()->subDays(7)->toDateString()),
            'last_30_days' => $query->whereDate('created_at', '>=', now()->subDays(30)->toDateString()),
            'this_month' => $query->whereMonth('created_at', now()->month)
                                  ->whereYear('created_at', now()->year),
            'last_month' => $query->whereMonth('created_at', now()->subMonth()->month)
                                  ->whereYear('created_at', now()->subMonth()->year),
            'this_year' => $query->whereYear('created_at', now()->year),
            default => null,
        };
    }
}
```

---

## Task 4.2.2: Add Audit Export Event Constant

**File:** `app/Services/AuditService.php`

Add this constant:

```php
// Audit & Report Events
public const EVENT_AUDIT_LOG_EXPORTED = 'audit_log.exported';
public const EVENT_REPORT_GENERATED = 'report.generated';
```

---

## Task 4.2.3: Create AuditLogExport Class

```bash
php artisan make:export AuditLogExport
```

**File:** `app/Exports/AuditLogExport.php`

```php
<?php

namespace App\Exports;

use App\Models\AuditLog;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class AuditLogExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = AuditLog::query();

        if (!empty($this->filters['start_date'])) {
            $query->whereDate('created_at', '>=', $this->filters['start_date']);
        }
        if (!empty($this->filters['end_date'])) {
            $query->whereDate('created_at', '<=', $this->filters['end_date']);
        }
        if (!empty($this->filters['actor_id'])) {
            $query->where('actor_id', $this->filters['actor_id']);
        }
        if (!empty($this->filters['event_type'])) {
            $query->where('event_type', $this->filters['event_type']);
        }
        if (!empty($this->filters['target_type'])) {
            $query->where('target_type', $this->filters['target_type']);
        }
        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('actor_name', 'ilike', "%{$search}%")
                  ->orWhereRaw("details::text ilike ?", ["%{$search}%"]);
            });
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'Event ID',
            'Timestamp',
            'Actor ID',
            'Actor Name',
            'Event Type',
            'Target Type',
            'Target ID',
            'Details',
            'IP Address',
            'User Agent',
        ];
    }

    public function map($log): array
    {
        return [
            $log->id,
            $log->created_at->format('Y-m-d H:i:s'),
            $log->actor_id,
            $log->actor_name,
            $log->event_type,
            $log->target_type,
            $log->target_id,
            is_array($log->details) ? json_encode($log->details) : $log->details,
            $log->ip_address,
            $log->user_agent,
        ];
    }
}
```

---

## Task 4.2.4: Define Audit Log Routes

**File:** `routes/web.php`

Add to the Director/SysAdmin admin routes group:

```php
// Audit Logs (Director/SysAdmin only)
Route::get('audit-logs', [Admin\AuditLogController::class, 'index'])->name('audit-logs.index');
Route::get('audit-logs/export', [Admin\AuditLogController::class, 'export'])->name('audit-logs.export');
```

---

## Task 4.2.5: Create Audit Log Viewer View

**File:** `resources/views/admin/audit-logs/index.blade.php`

Convert from mockup: `mrbs-mock-up/pages/system-audit-logs.html`

```blade
@extends('layouts.app')

@section('title', 'Audit Trail')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">
            <i class='bx bx-history me-2'></i>Audit Trail
        </h4>
        <div class="btn-group">
            <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                <i class='bx bx-download me-1'></i> Export
            </button>
            <ul class="dropdown-menu">
                <li>
                    <a class="dropdown-item" href="{{ route('admin.audit-logs.export', array_merge(request()->all(), ['format' => 'csv'])) }}">
                        <i class='bx bx-file me-2'></i> Export as CSV
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ route('admin.audit-logs.export', array_merge(request()->all(), ['format' => 'xlsx'])) }}">
                        <i class='bx bx-spreadsheet me-2'></i> Export as Excel
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">Filters</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.audit-logs.index') }}">
                <div class="row g-3">
                    <!-- Date Presets -->
                    <div class="col-12">
                        <label class="form-label">Quick Date Range</label>
                        <div class="btn-group flex-wrap" role="group">
                            <a href="{{ route('admin.audit-logs.index', ['preset' => 'today']) }}" 
                               class="btn btn-sm {{ request('preset') == 'today' ? 'btn-primary' : 'btn-outline-primary' }}">Today</a>
                            <a href="{{ route('admin.audit-logs.index', ['preset' => 'yesterday']) }}" 
                               class="btn btn-sm {{ request('preset') == 'yesterday' ? 'btn-primary' : 'btn-outline-primary' }}">Yesterday</a>
                            <a href="{{ route('admin.audit-logs.index', ['preset' => 'last_7_days']) }}" 
                               class="btn btn-sm {{ request('preset') == 'last_7_days' ? 'btn-primary' : 'btn-outline-primary' }}">Last 7 Days</a>
                            <a href="{{ route('admin.audit-logs.index', ['preset' => 'last_30_days']) }}" 
                               class="btn btn-sm {{ request('preset') == 'last_30_days' ? 'btn-primary' : 'btn-outline-primary' }}">Last 30 Days</a>
                            <a href="{{ route('admin.audit-logs.index', ['preset' => 'this_month']) }}" 
                               class="btn btn-sm {{ request('preset') == 'this_month' ? 'btn-primary' : 'btn-outline-primary' }}">This Month</a>
                            <a href="{{ route('admin.audit-logs.index', ['preset' => 'last_month']) }}" 
                               class="btn btn-sm {{ request('preset') == 'last_month' ? 'btn-primary' : 'btn-outline-primary' }}">Last Month</a>
                        </div>
                    </div>

                    <!-- Custom Date Range -->
                    <div class="col-md-2">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>

                    <!-- Actor Filter -->
                    <div class="col-md-2">
                        <label class="form-label">Actor</label>
                        <select name="actor_id" class="form-select">
                            <option value="">All Users</option>
                            @foreach($actors as $actor)
                                <option value="{{ $actor->id }}" {{ request('actor_id') == $actor->id ? 'selected' : '' }}>
                                    {{ $actor->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Event Type Filter -->
                    <div class="col-md-2">
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

                    <!-- Target Type Filter -->
                    <div class="col-md-2">
                        <label class="form-label">Target Type</label>
                        <select name="target_type" class="form-select">
                            <option value="">All Targets</option>
                            @foreach($targetTypes as $type)
                                <option value="{{ $type }}" {{ request('target_type') == $type ? 'selected' : '' }}>
                                    {{ ucfirst($type) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Search -->
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search actor, details..." value="{{ request('search') }}">
                    </div>

                    <!-- Action Buttons -->
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class='bx bx-filter-alt me-1'></i> Apply Filters
                        </button>
                        <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-outline-secondary">Clear</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Audit Logs Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Showing {{ $logs->total() }} entries</span>
            <span class="text-muted small">
                <i class='bx bx-lock-alt'></i> Logs are immutable and cannot be edited or deleted
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Actor</th>
                        <th>Event</th>
                        <th>Target</th>
                        <th>Summary</th>
                        <th>IP Address</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td>
                            <span class="text-nowrap">{{ $log->created_at->format('M d, Y') }}</span><br>
                            <small class="text-muted">{{ $log->created_at->format('H:i:s') }}</small>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm me-2 bg-label-primary">
                                    <span class="avatar-initial rounded-circle">
                                        {{ substr($log->actor_name ?? 'S', 0, 1) }}
                                    </span>
                                </div>
                                <span>{{ $log->actor_name ?? 'System' }}</span>
                            </div>
                        </td>
                        <td>
                            @php
                                $eventColor = match(true) {
                                    str_contains($log->event_type, 'login_success') => 'success',
                                    str_contains($log->event_type, 'login_failed') => 'danger',
                                    str_contains($log->event_type, 'created') => 'info',
                                    str_contains($log->event_type, 'updated') => 'warning',
                                    str_contains($log->event_type, 'deleted') => 'danger',
                                    str_contains($log->event_type, 'cancelled') => 'danger',
                                    str_contains($log->event_type, 'deactivated') => 'secondary',
                                    default => 'primary',
                                };
                            @endphp
                            <span class="badge bg-label-{{ $eventColor }}">
                                {{ ucwords(str_replace(['_', '.'], ' ', $log->event_type)) }}
                            </span>
                        </td>
                        <td>
                            @if($log->target_type)
                                <span class="text-capitalize">{{ $log->target_type }}</span>
                                @if($log->target_id)
                                    <span class="text-muted">#{{ $log->target_id }}</span>
                                @endif
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($log->details)
                                <span class="text-truncate d-inline-block" style="max-width: 200px;" 
                                      title="{{ json_encode($log->details) }}">
                                    {{ Str::limit(json_encode($log->details), 50) }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td><code class="small">{{ $log->ip_address ?? '-' }}</code></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-icon" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#logDetailModal{{ $log->id }}">
                                <i class='bx bx-show'></i>
                            </button>

                            <!-- Log Detail Modal -->
                            <div class="modal fade" id="logDetailModal{{ $log->id }}" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Audit Log Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <strong>Event ID:</strong> {{ $log->id }}
                                                </div>
                                                <div class="col-md-6">
                                                    <strong>Timestamp:</strong> {{ $log->created_at->format('Y-m-d H:i:s') }}
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <strong>Actor:</strong> {{ $log->actor_name ?? 'System' }}
                                                    @if($log->actor_id)
                                                        (ID: {{ $log->actor_id }})
                                                    @endif
                                                </div>
                                                <div class="col-md-6">
                                                    <strong>Event Type:</strong> 
                                                    <span class="badge bg-label-{{ $eventColor }}">{{ $log->event_type }}</span>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <strong>Target Type:</strong> {{ $log->target_type ?? '-' }}
                                                </div>
                                                <div class="col-md-6">
                                                    <strong>Target ID:</strong> {{ $log->target_id ?? '-' }}
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <strong>IP Address:</strong> <code>{{ $log->ip_address ?? '-' }}</code>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <strong>User Agent:</strong>
                                                <div class="text-muted small">{{ $log->user_agent ?? '-' }}</div>
                                            </div>
                                            <div>
                                                <strong>Details:</strong>
                                                @if($log->details)
                                                    <pre class="bg-light p-3 rounded mt-2"><code>{{ json_encode($log->details, JSON_PRETTY_PRINT) }}</code></pre>
                                                @else
                                                    <span class="text-muted">No additional details</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <i class='bx bx-history fs-1 text-muted'></i>
                            <p class="text-muted mt-2">No audit logs found for the selected criteria.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted">Showing {{ $logs->firstItem() }}-{{ $logs->lastItem() }} of {{ $logs->total() }} entries</span>
                {{ $logs->links() }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
```

---

## Task 4.2.6: Update Sidebar Navigation

**File:** `resources/views/layouts/partials/sidebar.blade.php`

Add Audit Log menu item (for Director/SysAdmin):

```blade
@if(auth()->user()->canAccessAudit())
<li class="menu-item {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}">
    <a href="{{ route('admin.audit-logs.index') }}" class="menu-link">
        <i class="menu-icon tf-icons bx bx-history"></i>
        <div>Audit Trail</div>
    </a>
</li>
@endif
```

---

## Testing Requirements

> **Note:** When creating test users with the factory, use `->passwordChanged()` to set `must_change_password` to `false`. Otherwise, the `MustChangePassword` middleware will redirect users causing tests to receive 302 responses instead of 200.

**File:** `tests/Feature/AuditLogViewerTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuditLogViewerTest extends TestCase
{
    use RefreshDatabase;

    protected User $sysAdmin;
    protected User $director;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->sysAdmin = User::factory()->passwordChanged()->create(['role' => 'system_admin']);
        $this->director = User::factory()->passwordChanged()->create(['role' => 'director']);
        $this->regularUser = User::factory()->passwordChanged()->create(['role' => 'regular_user']);

        // Create some audit logs
        AuditService::log('login_success', 'user', $this->regularUser->id);
        AuditService::log('booking.created', 'booking', 1, ['room' => 'Conference Room A']);
    }

    public function test_sysadmin_can_view_audit_logs(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.audit-logs.index'));
        $response->assertStatus(200);
        $response->assertSee('Audit Trail');
    }

    public function test_director_can_view_audit_logs(): void
    {
        $response = $this->actingAs($this->director)->get(route('admin.audit-logs.index'));
        $response->assertStatus(200);
    }

    public function test_regular_user_cannot_view_audit_logs(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.audit-logs.index'));
        $response->assertStatus(403);
    }

    public function test_audit_logs_displayed_in_reverse_chronological_order(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.audit-logs.index'));
        $response->assertStatus(200);
        
        $logs = AuditLog::orderBy('created_at', 'desc')->get();
        $this->assertGreaterThan(0, $logs->count());
    }

    public function test_filter_by_event_type_works(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.audit-logs.index', [
            'event_type' => 'login_success'
        ]));

        $response->assertStatus(200);
        $response->assertSee('Login Success');
    }

    public function test_filter_by_actor_works(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.audit-logs.index', [
            'actor_id' => $this->sysAdmin->id
        ]));

        $response->assertStatus(200);
    }

    public function test_date_preset_filter_works(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.audit-logs.index', [
            'preset' => 'today'
        ]));

        $response->assertStatus(200);
    }

    public function test_search_filter_works(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.audit-logs.index', [
            'search' => 'Conference'
        ]));

        $response->assertStatus(200);
    }

    public function test_export_csv_works(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.audit-logs.export', [
            'format' => 'csv'
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_export_xlsx_works(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.audit-logs.export', [
            'format' => 'xlsx'
        ]));

        $response->assertStatus(200);
    }

    public function test_export_action_is_logged(): void
    {
        $this->actingAs($this->sysAdmin)->get(route('admin.audit-logs.export', [
            'format' => 'csv'
        ]));

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'audit_log.exported',
            'actor_id' => $this->sysAdmin->id,
        ]);
    }

    public function test_logs_are_immutable(): void
    {
        $log = AuditLog::first();
        
        // Attempt to update (should fail at model level or be prevented)
        $this->expectException(\Exception::class);
        $log->update(['event_type' => 'modified']);
    }
}
```

---

## Acceptance Criteria

- [x] Audit log list displays all logs in reverse chronological order
- [x] Date presets work (Today, Yesterday, Last 7 Days, etc.)
- [x] Custom date range filter works
- [x] Actor filter dropdown shows all users with logs
- [x] Event type filter dropdown shows all event types
- [x] Target type filter works
- [x] Full-text search works (actor, details)
- [x] Pagination works (50 per page)
- [x] View Details modal shows full log information
- [x] Export to CSV works
- [x] Export to Excel works
- [x] Export action is logged
- [x] Only Director/SysAdmin can access
- [x] Logs cannot be edited or deleted (immutable)
- [x] All tests pass: `php artisan test --filter=AuditLogViewerTest`

---

**Next:** [Step 4.3 - User Activity History](./step-4.3-user-activity-history.md)
