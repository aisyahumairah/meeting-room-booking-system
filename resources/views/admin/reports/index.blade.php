@extends('layouts.app')

@section('title', 'Reports')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">
                <i class="icon-base bx bx-bar-chart-alt-2 icon-md me-2"></i>Reports & Analytics
            </h4>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-0">{{ $stats['total_bookings_this_month'] }}</h3>
                                <small class="text-muted">Bookings This Month</small>
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
                                <h3 class="mb-0">{{ $stats['total_rooms'] }}</h3>
                                <small class="text-muted">Active Rooms</small>
                            </div>
                            <div class="avatar">
                                <span class="avatar-initial rounded bg-label-info">
                                    <i class="icon-base bx bx-door-open icon-md"></i>
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
                                <h3 class="mb-0">{{ $stats['total_users'] }}</h3>
                                <small class="text-muted">Active Users</small>
                            </div>
                            <div class="avatar">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class="icon-base bx bx-user icon-md"></i>
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
                                <h3 class="mb-0 {{ $stats['cancellation_rate'] > 15 ? 'text-warning' : '' }}">
                                    {{ $stats['cancellation_rate'] }}%
                                </h3>
                                <small class="text-muted">Cancellation Rate</small>
                            </div>
                            <div class="avatar">
                                <span
                                    class="avatar-initial rounded bg-label-{{ $stats['cancellation_rate'] > 15 ? 'warning' : 'secondary' }}">
                                    <i class="icon-base bx bx-x-circle icon-md"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Available Reports -->
        <div class="row">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <div class="avatar avatar-lg mx-auto mb-3">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="icon-base bx bx-building-house icon-lg"></i>
                            </span>
                        </div>
                        <h5>Room Utilization</h5>
                        <p class="text-muted">
                            Analyze how each room is being used. See booking counts, hours used, and utilization rates.
                        </p>
                        <a href="{{ route('admin.reports.room-utilization') }}" class="btn btn-primary">
                            View Report
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <div class="avatar avatar-lg mx-auto mb-3">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="icon-base bx bx-line-chart icon-lg"></i>
                            </span>
                        </div>
                        <h5>Booking Statistics</h5>
                        <p class="text-muted">
                            View booking trends, status distribution, peak hours, and top rooms.
                        </p>
                        <a href="{{ route('admin.reports.booking-statistics') }}" class="btn btn-info">
                            View Report
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <div class="avatar avatar-lg mx-auto mb-3">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="icon-base bx bx-user-check icon-lg"></i>
                            </span>
                        </div>
                        <h5>User Activity</h5>
                        <p class="text-muted">
                            See user booking activity, cancellation rates, and department breakdown.
                        </p>
                        <a href="{{ route('admin.reports.user-activity') }}" class="btn btn-success">
                            View Report
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
