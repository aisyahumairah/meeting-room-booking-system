@extends('layouts.app')

@section('title', 'User Activity - ' . $user->name)

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="{{ route('admin.users.index') }}" class="text-muted text-decoration-none">
                    <i class="icon-base bx bx-arrow-back icon-sm me-1"></i> Back to User List
                </a>
                <h4 class="mb-0 mt-2">Activity History</h4>
            </div>
            <div class="btn-group">
                <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="icon-base bx bx-download icon-sm me-1"></i> Export
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <a class="dropdown-item"
                            href="{{ route('admin.users.activity.export', array_merge(['user' => $user->id], request()->all(), ['format' => 'csv'])) }}">
                            <i class="icon-base bx bx-file icon-sm me-2"></i> Export as CSV
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item"
                            href="{{ route('admin.users.activity.export', array_merge(['user' => $user->id], request()->all(), ['format' => 'xlsx'])) }}">
                            <i class="icon-base bx bx-spreadsheet icon-sm me-2"></i> Export as Excel
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
                                    @if ($user->status === 'active')
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
                            <div class="avatar">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="icon-base bx bx-calendar icon-md"></i>
                                </span>
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
                            <div class="avatar">
                                <span
                                    class="avatar-initial rounded bg-label-{{ $stats['cancellation_rate'] > 20 ? 'warning' : 'success' }}">
                                    <i class="icon-base bx bx-x-circle icon-md"></i>
                                </span>
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
                            <div class="avatar">
                                <span class="avatar-initial rounded bg-label-info">
                                    <i class="icon-base bx bx-time-five icon-md"></i>
                                </span>
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
                                <h6 class="mb-0 text-truncate" style="max-width: 120px;">{{ $stats['most_booked_room'] }}
                                </h6>
                                <small class="text-muted">Most Booked Room</small>
                            </div>
                            <div class="avatar">
                                <span class="avatar-initial rounded bg-label-secondary">
                                    <i class="icon-base bx bx-door-open icon-md"></i>
                                </span>
                            </div>
                        </div>
                        @if ($stats['most_booked_room_count'] > 0)
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
                            <div class="avatar">
                                <span class="avatar-initial rounded bg-label-info">
                                    <i class="icon-base bx bx-timer icon-md"></i>
                                </span>
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
                            <div class="avatar">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class="icon-base bx bx-log-in icon-md"></i>
                                </span>
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
                                class="btn btn-sm {{ request('preset') == 'last_7_days' ? 'btn-primary' : 'btn-outline-primary' }}">Last
                                7 Days</a>
                            <a href="{{ route('admin.users.activity', ['user' => $user->id, 'preset' => 'last_30_days']) }}"
                                class="btn btn-sm {{ request('preset') == 'last_30_days' ? 'btn-primary' : 'btn-outline-primary' }}">Last
                                30 Days</a>
                            <a href="{{ route('admin.users.activity', ['user' => $user->id, 'preset' => 'this_month']) }}"
                                class="btn btn-sm {{ request('preset') == 'this_month' ? 'btn-primary' : 'btn-outline-primary' }}">This
                                Month</a>
                            <a href="{{ route('admin.users.activity', $user) }}"
                                class="btn btn-sm {{ !request('preset') && !request('start_date') ? 'btn-primary' : 'btn-outline-primary' }}">All
                                Time</a>
                        </div>
                    </div>

                    <!-- Custom Filters -->
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control"
                            value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Event Type</label>
                        <select name="event_type" class="form-select">
                            <option value="">All Events</option>
                            @foreach ($eventTypes as $type)
                                <option value="{{ $type }}"
                                    {{ request('event_type') == $type ? 'selected' : '' }}>
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
                                $iconClass = match (true) {
                                    str_contains($activity->event_type, 'login') => 'bx-log-in',
                                    str_contains($activity->event_type, 'logout') => 'bx-log-out',
                                    str_contains($activity->event_type, 'booking') => 'bx-calendar',
                                    str_contains($activity->event_type, 'password') => 'bx-key',
                                    str_contains($activity->event_type, 'profile') => 'bx-user',
                                    str_contains($activity->event_type, 'room') => 'bx-door-open',
                                    default => 'bx-dots-horizontal-rounded',
                                };
                                $bgClass = match (true) {
                                    str_contains($activity->event_type, 'success') ||
                                        str_contains($activity->event_type, 'created')
                                        => 'bg-label-success',
                                    str_contains($activity->event_type, 'failed') ||
                                        str_contains($activity->event_type, 'cancelled') ||
                                        str_contains($activity->event_type, 'deleted')
                                        => 'bg-label-danger',
                                    str_contains($activity->event_type, 'updated') => 'bg-label-warning',
                                    default => 'bg-label-primary',
                                };
                            @endphp
                            <div class="avatar avatar-sm">
                                <span class="avatar-initial rounded-circle {{ $bgClass }}">
                                    <i class="icon-base bx {{ $iconClass }} icon-xs"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="badge {{ $bgClass }} mb-1">
                                        {{ ucwords(str_replace(['_', '.'], ' ', $activity->event_type)) }}
                                    </span>
                                    @if ($activity->target_type && $activity->target_id)
                                        <span class="ms-2 text-muted">
                                            @if ($activity->target_type === 'booking')
                                                <a href="{{ route('bookings.create') }}" class="text-decoration-none">
                                                    Booking #{{ $activity->target_id }}
                                                </a>
                                            @elseif($activity->target_type === 'room')
                                                <a href="{{ route('rooms.show', $activity->target_id) }}"
                                                    class="text-decoration-none">
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
                            @if ($activity->details)
                                <div class="text-muted small mt-1">
                                    @if (is_array($activity->details))
                                        @foreach ($activity->details as $key => $value)
                                            <span class="me-3">
                                                <strong>{{ ucwords(str_replace('_', ' ', $key)) }}:</strong>
                                                @if (is_array($value))
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
                                <i class="icon-base bx bx-globe icon-xs me-1"></i>
                                {{ $activity->ip_address ?? 'Unknown IP' }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4">
                        <i class="icon-base bx bx-history icon-xl text-muted"></i>
                        <p class="text-muted mt-2">No activity found for the selected criteria.</p>
                    </div>
                @endforelse
            </div>
            @if ($activities->hasPages())
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Showing {{ $activities->firstItem() }}-{{ $activities->lastItem() }} of
                            {{ $activities->total() }} events</span>
                        {{ $activities->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
