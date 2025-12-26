@extends('layouts.app')

@section('title', 'User Activity Report')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="{{ route('admin.reports.index') }}" class="text-muted text-decoration-none">
                    <i class='bx bx-arrow-back me-1'></i> Back to Reports
                </a>
                <h4 class="mb-0 mt-2">User Activity Report</h4>
            </div>
            <div class="btn-group">
                <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class='bx bx-download me-1'></i> Export
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item"
                            href="{{ route('admin.reports.user-activity.export', array_merge(request()->all(), ['format' => 'xlsx'])) }}">
                            <i class='bx bx-spreadsheet me-2'></i> Excel
                        </a></li>
                    <li><a class="dropdown-item"
                            href="{{ route('admin.reports.user-activity.export', array_merge(request()->all(), ['format' => 'pdf'])) }}">
                            <i class='bx bxs-file-pdf me-2'></i> PDF
                        </a></li>
                </ul>
            </div>
        </div>

        <!-- Date Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.reports.user-activity') }}" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">
                            <i class='bx bx-refresh me-1'></i> Update Report
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <!-- Department Stats -->
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h6 class="mb-0">Users by Department</h6>
                    </div>
                    <div class="card-body">
                        @foreach ($departmentStats as $dept)
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>{{ $dept->department }}</span>
                                <span class="badge bg-primary">{{ $dept->user_count }}</span>
                            </div>
                        @endforeach
                        @if ($departmentStats->isEmpty())
                            <p class="text-muted text-center">No department data</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- User Table -->
            <div class="col-md-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Top Users by Booking Activity</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-hover w-100">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Department</th>
                                    <th class="text-center">Bookings</th>
                                    <th class="text-center">Cancelled</th>
                                    <th class="text-center">Cancel Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reportData as $data)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm me-2 bg-label-primary">
                                                    <span
                                                        class="avatar-initial rounded-circle">{{ substr($data['user']->name, 0, 1) }}</span>
                                                </div>
                                                <div>
                                                    <span>{{ $data['user']->name }}</span>
                                                    <small class="d-block text-muted">{{ $data['user']->email }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $data['user']->department ?? '-' }}</td>
                                        <td class="text-center">{{ $data['total_bookings'] }}</td>
                                        <td class="text-center">{{ $data['cancelled_bookings'] }}</td>
                                        <td class="text-center">
                                            <span
                                                class="{{ $data['cancellation_rate'] > 20 ? 'text-warning fw-semibold' : '' }}">
                                                {{ $data['cancellation_rate'] }}%
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <p class="text-muted">No activity data for the selected period.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
