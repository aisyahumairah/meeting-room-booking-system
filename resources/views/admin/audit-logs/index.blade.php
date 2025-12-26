@extends('layouts.app')

@section('title', 'Audit Trail')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">
                <i class="icon-base bx bx-history icon-md me-2"></i>Audit Trail
            </h4>
            <div class="btn-group">
                <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="icon-base bx bx-download icon-sm me-1"></i> Export
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <a class="dropdown-item"
                            href="{{ route('admin.audit-logs.export', array_merge(request()->all(), ['format' => 'csv'])) }}">
                            <i class="icon-base bx bx-file icon-sm me-2"></i> Export as CSV
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item"
                            href="{{ route('admin.audit-logs.export', array_merge(request()->all(), ['format' => 'xlsx'])) }}">
                            <i class="icon-base bx bx-spreadsheet icon-sm me-2"></i> Export as Excel
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
                                    class="btn btn-sm {{ request('preset') == 'last_7_days' ? 'btn-primary' : 'btn-outline-primary' }}">Last
                                    7 Days</a>
                                <a href="{{ route('admin.audit-logs.index', ['preset' => 'last_30_days']) }}"
                                    class="btn btn-sm {{ request('preset') == 'last_30_days' ? 'btn-primary' : 'btn-outline-primary' }}">Last
                                    30 Days</a>
                                <a href="{{ route('admin.audit-logs.index', ['preset' => 'this_month']) }}"
                                    class="btn btn-sm {{ request('preset') == 'this_month' ? 'btn-primary' : 'btn-outline-primary' }}">This
                                    Month</a>
                                <a href="{{ route('admin.audit-logs.index', ['preset' => 'last_month']) }}"
                                    class="btn btn-sm {{ request('preset') == 'last_month' ? 'btn-primary' : 'btn-outline-primary' }}">Last
                                    Month</a>
                            </div>
                        </div>

                        <!-- Custom Date Range -->
                        <div class="col-md-2">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control"
                                value="{{ request('start_date') }}">
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
                                @foreach ($actors as $actor)
                                    <option value="{{ $actor->id }}"
                                        {{ request('actor_id') == $actor->id ? 'selected' : '' }}>
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
                                @foreach ($eventTypes as $type)
                                    <option value="{{ $type }}"
                                        {{ request('event_type') == $type ? 'selected' : '' }}>
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
                                @foreach ($targetTypes as $type)
                                    <option value="{{ $type }}"
                                        {{ request('target_type') == $type ? 'selected' : '' }}>
                                        {{ ucfirst($type) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Search -->
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Search actor, details..."
                                value="{{ request('search') }}">
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
                <span>Total: {{ $logs->total() }} entries</span>
                <span class="text-muted small">
                    <i class="icon-base bx bx-lock-alt me-1"></i>Logs are immutable and cannot be edited or deleted
                </span>
            </div>
            <div class="card-body">
                <table class="table table-hover w-100">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Actor</th>
                            <th>Event</th>
                            <th>Target</th>
                            <th>Summary</th>
                            <th>IP Address</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td>
                                    <span class="text-nowrap">{{ $log->created_at->format('M d, Y') }}</span><br>
                                    <small class="text-muted">{{ $log->created_at->format('H:i:s') }}</small>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm me-2">
                                            <span class="avatar-initial rounded-circle bg-label-primary">
                                                {{ substr($log->actor_name ?? 'S', 0, 1) }}
                                            </span>
                                        </div>
                                        <span>{{ $log->actor_name ?? 'System' }}</span>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $eventColor = match (true) {
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
                                    @if ($log->target_type)
                                        <span class="text-capitalize">{{ $log->target_type }}</span>
                                        @if ($log->target_id)
                                            <span class="text-muted">#{{ $log->target_id }}</span>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($log->details)
                                        <span class="text-truncate d-inline-block" style="max-width: 200px;"
                                            title="{{ json_encode($log->details) }}">
                                            {{ Str::limit(json_encode($log->details), 50) }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td><code class="small">{{ $log->ip_address ?? '-' }}</code></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-icon" data-bs-toggle="modal"
                                        data-bs-target="#logDetailModal{{ $log->id }}">
                                        <i class="icon-base bx bx-show icon-sm"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{-- @if ($logs->hasPages())
                <div class="card-footer border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Showing {{ $logs->firstItem() }}-{{ $logs->lastItem() }} of
                            {{ $logs->total() }} entries</span>
                        <div class="pagination-sm">
                            {{ $logs->links() }}
                        </div>
                    </div>
                </div>
            @endif --}}
        </div>
    </div>

    <!-- Modals -->
    @foreach ($logs as $log)
        @php
            $eventColor = match (true) {
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
        <div class="modal fade" id="logDetailModal{{ $log->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title">Audit Log Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="30%">Event ID</th>
                                    <td>#{{ $log->id }}</td>
                                </tr>
                                <tr>
                                    <th>Timestamp</th>
                                    <td>{{ $log->created_at->format('Y-m-d H:i:s') }}
                                        ({{ $log->created_at->diffForHumans() }})
                                    </td>
                                </tr>
                                <tr>
                                    <th>Actor</th>
                                    <td>{{ $log->actor_name ?? 'System' }}
                                        {{ $log->actor_id ? '(ID: ' . $log->actor_id . ')' : '' }}</td>
                                </tr>
                                <tr>
                                    <th>Event Type</th>
                                    <td><span class="badge bg-label-{{ $eventColor }}">{{ $log->event_type }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Target</th>
                                    <td>{{ $log->target_type ?? '-' }}
                                        {{ $log->target_id ? '(ID: ' . $log->target_id . ')' : '' }}</td>
                                </tr>
                                <tr>
                                    <th>IP Address</th>
                                    <td><code>{{ $log->ip_address ?? '-' }}</code></td>
                                </tr>
                                <tr>
                                    <th>User Agent</th>
                                    <td><small class="text-muted">{{ $log->user_agent ?? '-' }}</small></td>
                                </tr>
                            </table>
                        </div>
                        <div class="mt-4">
                            <h6 class="border-bottom pb-2">Detailed Payload</h6>
                            @if ($log->details)
                                <pre class="bg-label-secondary p-3 rounded mb-0" style="max-height: 400px; overflow-y: auto;"><code>{{ json_encode($log->details, JSON_PRETTY_PRINT) }}</code></pre>
                            @else
                                <div class="alert alert-secondary mb-0">No additional details recorded.</div>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endsection
