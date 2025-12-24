@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="row">
        <!-- Welcome Card -->
        <div class="col-lg-8 mb-4 order-0">
            <div class="card">
                <div class="d-flex align-items-end row">
                    <div class="col-sm-7">
                        <div class="card-body">
                            <h5 class="card-title text-primary">Welcome back, {{ auth()->user()->name }}! 👋</h5>
                            <p class="mb-4">
                                You have <span class="fw-bold">{{ $upcomingBookings->count() }} upcoming
                                    booking{{ $upcomingBookings->count() !== 1 ? 's' : '' }}</span> this week.
                            </p>
                            <a href="{{ route('bookings.create') }}" class="btn btn-sm btn-primary">
                                <i class="bx bx-plus me-1"></i> Book a Room
                            </a>
                        </div>
                    </div>
                    <div class="col-sm-5 text-center text-sm-left">
                        <div class="card-body pb-0 px-0 px-md-4">
                            <img src="{{ asset('assets/img/illustrations/man-with-laptop.png') }}" height="140"
                                alt="View Badge User" data-app-dark-img="illustrations/man-with-laptop-dark.png"
                                data-app-light-img="illustrations/man-with-laptop-light.png" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="col-lg-4 col-md-4 order-1">
            <div class="row">
                <div class="col-lg-6 col-md-12 col-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title d-flex align-items-start justify-content-between">
                                <div class="avatar flex-shrink-0">
                                    <span class="avatar-initial rounded bg-label-info"><i class="bx bx-calendar"></i></span>
                                </div>
                            </div>
                            <span class="fw-semibold d-block mb-1">Upcoming</span>
                            <h3 class="card-title mb-2">{{ $upcomingBookings->count() }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-md-12 col-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title d-flex align-items-start justify-content-between">
                                <div class="avatar flex-shrink-0">
                                    <span class="avatar-initial rounded bg-label-warning"><i
                                            class="bx bx-time-five"></i></span>
                                </div>
                            </div>
                            <span class="fw-semibold d-block mb-1">Pending</span>
                            <h3 class="card-title mb-2">{{ $stats['pending'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Bookings List -->
        <div class="col-12 col-lg-8 order-2 order-md-3 order-lg-2 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title m-0 me-2">My Upcoming Bookings</h5>
                    <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="table-responsive text-nowrap">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Room</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            @foreach ($upcomingBookings as $booking)
                                <tr>
                                    <td>
                                        <i class="bx bx-building-house fa-lg text-primary me-3"></i>
                                        <strong>{{ $booking->room->name ?? 'N/A' }}</strong>
                                    </td>
                                    <td>
                                        {{ $booking->booking_date->format('D, M j') }} •
                                        {{ $booking->start_time }} - {{ $booking->end_time }}
                                    </td>
                                    <td>
                                        <span class="badge bg-label-{{ $booking->status_badge ?? 'secondary' }} me-1">
                                            {{ ucfirst($booking->status ?? 'pending') }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                                data-bs-toggle="dropdown">
                                                <i class="bx bx-dots-vertical-rounded"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="javascript:void(0);"><i
                                                        class="bx bx-edit-alt me-1"></i> Edit</a>
                                                <a class="dropdown-item" href="javascript:void(0);"><i
                                                        class="bx bx-trash me-1"></i> Cancel</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-md-6 col-lg-4 order-2 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title m-0 me-2">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('bookings.create') }}" class="btn btn-primary">
                            <i class="bx bx-plus me-1"></i> New Booking
                        </a>
                        <a href="{{ route('calendar') }}" class="btn btn-outline-primary">
                            <i class="bx bx-calendar me-1"></i> View Calendar
                        </a>
                        <a href="{{ route('rooms.index') }}" class="btn btn-outline-primary">
                            <i class="bx bx-building me-1"></i> Browse Rooms
                        </a>
                        <a href="{{ route('profile.password') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-lock me-1"></i> Change Password
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row">
        <div class="col-lg-3 col-md-6 col-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                            <span class="avatar-initial rounded bg-label-primary"><i
                                    class="bx bx-calendar-check"></i></span>
                        </div>
                    </div>
                    <span class="fw-semibold d-block mb-1">Total Bookings</span>
                    <h3 class="card-title mb-2">{{ $stats['total_bookings'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                            <span class="avatar-initial rounded bg-label-success"><i
                                    class="bx bx-calendar-event"></i></span>
                        </div>
                    </div>
                    <span class="fw-semibold d-block mb-1">This Month</span>
                    <h3 class="card-title mb-2">{{ $stats['this_month'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                            <span class="avatar-initial rounded bg-label-warning"><i class="bx bx-time-five"></i></span>
                        </div>
                    </div>
                    <span class="fw-semibold d-block mb-1">Pending</span>
                    <h3 class="card-title mb-2">{{ $stats['pending'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                            <span class="avatar-initial rounded bg-label-danger"><i class="bx bx-x-circle"></i></span>
                        </div>
                    </div>
                    <span class="fw-semibold d-block mb-1">Cancelled</span>
                    <h3 class="card-title mb-2">{{ $stats['cancelled'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- My Booking History Chart -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">My Booking History</h5>
                </div>
                <div class="card-body">
                    <div id="bookingsChart"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
    <script>
        // Placeholder chart - will use real data in Phase 3
        const options = {
            chart: {
                type: 'bar',
                height: 300,
                toolbar: {
                    show: false
                }
            },
            series: [{
                name: 'Bookings',
                data: [4, 6, 3, 8, 5, 7]
            }],
            colors: ['#696cff'],
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    columnWidth: '40%',
                }
            },
            dataLabels: {
                enabled: false
            },
            xaxis: {
                categories: ['Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
            },
            yaxis: {
                title: {
                    text: 'Number of Bookings'
                }
            },
            grid: {
                borderColor: '#e0e0e0',
                strokeDashArray: 3
            }
        };
        new ApexCharts(document.querySelector("#bookingsChart"), options).render();
    </script>
@endpush
