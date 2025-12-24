@extends('layouts.app')

@section('title', 'Room Utilization Report')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="{{ route('admin.reports.index') }}" class="text-muted text-decoration-none">
                    <i class='bx bx-arrow-back me-1'></i> Back to Reports
                </a>
                <h4 class="mb-0 mt-2">Room Utilization Report</h4>
            </div>
            <div class="btn-group">
                <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class='bx bx-download me-1'></i> Export
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item"
                            href="{{ route('admin.reports.room-utilization.export', array_merge(request()->all(), ['format' => 'xlsx'])) }}">
                            <i class='bx bx-spreadsheet me-2'></i> Excel (.xlsx)
                        </a></li>
                    <li><a class="dropdown-item"
                            href="{{ route('admin.reports.room-utilization.export', array_merge(request()->all(), ['format' => 'csv'])) }}">
                            <i class='bx bx-file me-2'></i> CSV
                        </a></li>
                    <li><a class="dropdown-item"
                            href="{{ route('admin.reports.room-utilization.export', array_merge(request()->all(), ['format' => 'pdf'])) }}">
                            <i class='bx bxs-file-pdf me-2'></i> PDF
                        </a></li>
                </ul>
            </div>
        </div>

        <!-- Date Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.reports.room-utilization') }}" class="row g-3">
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

        <!-- Report Table -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Report Period: {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} -
                    {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Room</th>
                            <th>Location</th>
                            <th class="text-center">Capacity</th>
                            <th class="text-center">Total Bookings</th>
                            <th class="text-center">Hours Used</th>
                            <th class="text-center">Available Hours</th>
                            <th class="text-center">Utilization</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportData as $data)
                            <tr>
                                <td>
                                    <a href="{{ route('rooms.show', $data['room']->id) }}" class="text-decoration-none">
                                        {{ $data['room']->name }}
                                    </a>
                                </td>
                                <td>{{ $data['room']->floor_location }}</td>
                                <td class="text-center">{{ $data['room']->capacity }}</td>
                                <td class="text-center">{{ $data['total_bookings'] }}</td>
                                <td class="text-center">{{ $data['total_hours'] }}h</td>
                                <td class="text-center">{{ $data['available_hours'] }}h</td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <div class="progress me-2" style="width: 100px; height: 8px;">
                                            <div class="progress-bar bg-{{ $data['utilization_rate'] > 70 ? 'success' : ($data['utilization_rate'] > 40 ? 'warning' : 'danger') }}"
                                                style="width: {{ $data['utilization_rate'] }}%"></div>
                                        </div>
                                        <span class="fw-semibold">{{ $data['utilization_rate'] }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class='bx bx-info-circle fs-1 text-muted'></i>
                                    <p class="text-muted mt-2">No data available for the selected period.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
