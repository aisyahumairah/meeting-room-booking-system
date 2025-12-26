@extends('layouts.app')

@section('title', 'Booking Statistics Report')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="{{ route('admin.reports.index') }}" class="text-muted text-decoration-none">
                    <i class='bx bx-arrow-back me-1'></i> Back to Reports
                </a>
                <h4 class="mb-0 mt-2">Booking Statistics Report</h4>
            </div>
            <div class="btn-group">
                <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class='bx bx-download me-1'></i> Export
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item"
                            href="{{ route('admin.reports.booking-statistics.export', array_merge(request()->all(), ['format' => 'xlsx'])) }}">
                            <i class='bx bx-spreadsheet me-2'></i> Excel
                        </a></li>
                    <li><a class="dropdown-item"
                            href="{{ route('admin.reports.booking-statistics.export', array_merge(request()->all(), ['format' => 'pdf'])) }}">
                            <i class='bx bxs-file-pdf me-2'></i> PDF
                        </a></li>
                </ul>
            </div>
        </div>

        <!-- Date Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.reports.booking-statistics') }}" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Group By</label>
                        <select name="group_by" class="form-select">
                            <option value="day" {{ $groupBy == 'day' ? 'selected' : '' }}>Day</option>
                            <option value="week" {{ $groupBy == 'week' ? 'selected' : '' }}>Week</option>
                            <option value="month" {{ $groupBy == 'month' ? 'selected' : '' }}>Month</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">
                            <i class='bx bx-refresh me-1'></i> Update
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <!-- Status Distribution -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h6 class="mb-0">Status Distribution</h6>
                    </div>
                    <div class="card-body">
                        @foreach ($statusDistribution as $status => $count)
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span
                                    class="badge bg-{{ $status == 'confirmed' ? 'success' : ($status == 'cancelled' ? 'danger' : 'secondary') }}">
                                    {{ ucfirst($status) }}
                                </span>
                                <span class="fw-semibold">{{ $count }}</span>
                            </div>
                        @endforeach
                        @if (empty($statusDistribution))
                            <p class="text-muted text-center">No data available</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Top Rooms -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h6 class="mb-0">Top 5 Most Booked Rooms</h6>
                    </div>
                    <div class="card-body">
                        @foreach ($topRooms as $item)
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>{{ $item->room->name ?? 'Unknown' }}</span>
                                <span class="badge bg-primary">{{ $item->booking_count }} bookings</span>
                            </div>
                        @endforeach
                        @if ($topRooms->isEmpty())
                            <p class="text-muted text-center">No data available</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Peak Hours -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h6 class="mb-0">Peak Booking Hours</h6>
                    </div>
                    <div class="card-body">
                        @foreach ($peakHours as $hour)
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>{{ sprintf('%02d:00', $hour->hour) }} -
                                    {{ sprintf('%02d:00', $hour->hour + 1) }}</span>
                                <span class="badge bg-info">{{ $hour->count }} bookings</span>
                            </div>
                        @endforeach
                        @if ($peakHours->isEmpty())
                            <p class="text-muted text-center">No data available</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Booking Trends -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h6 class="mb-0">Booking Trend</h6>
                    </div>
                    <div class="card-body">
                        @if (count($bookingTrends) > 0)
                            <div style="max-height: 200px; overflow-y: auto;">
                                @foreach ($bookingTrends as $period => $count)
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <small>{{ $period }}</small>
                                        <span class="badge bg-secondary">{{ $count }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted text-center">No data available</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
