@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
    <div class="row">
        <!-- Welcome Card -->
        <div class="col-lg-8 mb-4 order-0">
            <div class="card">
                <div class="d-flex align-items-end row">
                    <div class="col-sm-7">
                        <div class="card-body">
                            <h5 class="card-title text-primary">Welcome back, {{ auth()->user()->name }}! 🎉</h5>
                            <p class="mb-4">
                                You have <span class="fw-bold">{{ $stats['today_count'] }}
                                    booking{{ $stats['today_count'] !== 1 ? 's' : '' }}</span> scheduled for today.
                                Check the calendar for more details.
                            </p>
                            <a href="{{ route('calendar') }}" class="btn btn-sm btn-outline-primary">View Calendar</a>
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

        <!-- Statistics Cards -->
        <div class="col-lg-4 col-md-4 order-1">
            <div class="row">
                <div class="col-lg-6 col-md-12 col-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title d-flex align-items-start justify-content-between">
                                <div class="avatar flex-shrink-0">
                                    <span class="avatar-initial rounded bg-label-success"><i
                                            class="bx bx-calendar-check"></i></span>
                                </div>
                            </div>
                            <span class="fw-semibold d-block mb-1">Today</span>
                            <h3 class="card-title mb-2">{{ $stats['today_count'] }}</h3>
                            <small class="text-{{ $stats['today_count'] > 0 ? 'success' : 'muted' }} fw-semibold">
                                <i class="bx bx-{{ $stats['today_count'] > 0 ? 'up-arrow-alt' : 'minus' }}"></i>
                                Active today
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-md-12 col-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title d-flex align-items-start justify-content-between">
                                <div class="avatar flex-shrink-0">
                                    <span class="avatar-initial rounded bg-label-info"><i
                                            class="bx bx-calendar-week"></i></span>
                                </div>
                            </div>
                            <span class="fw-semibold d-block mb-1">This Week</span>
                            <h3 class="card-title mb-2">{{ $stats['week_total'] }}</h3>
                            <small class="text-{{ $stats['week_change_value'] >= 0 ? 'success' : 'danger' }} fw-semibold">
                                <i
                                    class="bx bx-{{ $stats['week_change_value'] >= 0 ? 'up-arrow-alt' : 'down-arrow-alt' }}"></i>
                                {{ $stats['week_change'] }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Room Utilization Chart -->
        <div class="col-12 col-lg-8 order-2 order-md-3 order-lg-2 mb-4">
            <div class="card">
                <div class="row row-bordered g-0">
                    <div class="col-md-8">
                        <h5 class="card-header m-0 me-2 pb-3">Room Utilization (Weekly)</h5>
                        <div id="utilizationChart" class="px-2"></div>
                    </div>
                    <div class="col-md-4">
                        <div class="card-body">
                            <div class="text-center">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button"
                                        id="utilizationPeriod" data-bs-toggle="dropdown">
                                        {{ $year }}
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        @for ($y = date('Y'); $y >= date('Y') - 2; $y--)
                                            <a class="dropdown-item {{ $year == $y ? 'active' : '' }}"
                                                href="{{ route('dashboard.admin', ['year' => $y]) }}">{{ $y }}</a>
                                        @endfor
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="growthChart"></div>
                        <div class="text-center fw-semibold pt-3 mb-2">{{ $avgUtilization }}% Average Utilization</div>
                        <div class="d-flex px-xxl-4 px-lg-2 p-4 gap-xxl-3 gap-lg-1 gap-3 justify-content-between">
                            <div class="d-flex">
                                <div class="me-2">
                                    <span class="badge bg-label-primary p-2"><i
                                            class="bx bx-calendar text-primary"></i></span>
                                </div>
                                <div class="d-flex flex-column">
                                    <small>This Week</small>
                                    <h6 class="mb-0">{{ $stats['week_total'] }}</h6>
                                </div>
                            </div>
                            <div class="d-flex">
                                <div class="me-2">
                                    <span class="badge bg-label-info p-2"><i
                                            class="bx bx-calendar-event text-info"></i></span>
                                </div>
                                <div class="d-flex flex-column">
                                    <small>This Month</small>
                                    <h6 class="mb-0">{{ $stats['month_total'] }}</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity / Today's Bookings -->
        <div class="col-md-6 col-lg-4 order-2 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title m-0 me-2">Today's Bookings</h5>
                    <span class="badge bg-primary">{{ $stats['today_count'] }}</span>
                </div>
                <div class="card-body">
                    @forelse($todaysBookings as $booking)
                        <div class="d-flex mb-3 pb-1">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-calendar"></i></span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <h6 class="mb-0">{{ $booking->room->name ?? 'Room' }}</h6>
                                    <small class="text-muted">{{ $booking->start_time }} -
                                        {{ $booking->user->name ?? 'User' }}</small>
                                </div>
                                <div class="user-progress">
                                    <span
                                        class="badge bg-label-{{ $booking->status === 'confirmed' ? 'success' : 'info' }}">
                                        {{ ucfirst($booking->status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="d-flex justify-content-center align-items-center" style="min-height: 150px;">
                            <div class="text-center text-muted">
                                <i class="bx bx-calendar-x bx-lg mb-2"></i>
                                <p class="mb-0">No bookings today</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Panel -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4 col-sm-6">
                            <a href="{{ route('admin.bookings.index') }}" class="btn btn-primary w-100">
                                <i class="bx bx-list-ul me-2"></i> View All Bookings
                            </a>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-primary w-100">
                                <i class="bx bx-chart me-2"></i> Generate Report
                            </a>
                        </div>
                        @can('manage-rooms')
                            <div class="col-md-4 col-sm-6">
                                <a href="{{ route('admin.rooms.index') }}" class="btn btn-outline-primary w-100">
                                    <i class="bx bx-plus me-2"></i> Manage Rooms
                                </a>
                            </div>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
    <script>
        // Room Utilization Chart
        const utilizationOptions = {
            chart: {
                type: 'bar',
                height: 250,
                stacked: true,
                toolbar: {
                    show: false
                }
            },
            series: @json($chartData),
            colors: ['#696cff', '#03c3ec'],
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '40%',
                    borderRadius: 4
                }
            },
            dataLabels: {
                enabled: false
            },
            xaxis: {
                categories: @json($days)
            },
            yaxis: {
                title: {
                    text: 'Hours'
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'left'
            },
            grid: {
                borderColor: '#e0e0e0',
                strokeDashArray: 3
            }
        };
        new ApexCharts(document.querySelector("#utilizationChart"), utilizationOptions).render();

        // Growth/Radial Chart
        const growthOptions = {
            chart: {
                height: 150,
                type: 'radialBar'
            },
            series: [{{ $avgUtilization }}],
            colors: ['#696cff'],
            plotOptions: {
                radialBar: {
                    hollow: {
                        size: '60%'
                    },
                    track: {
                        background: '#e7e7e7'
                    },
                    dataLabels: {
                        name: {
                            show: false
                        },
                        value: {
                            fontSize: '24px',
                            fontWeight: 600,
                            color: '#697a8d'
                        }
                    }
                }
            }
        };
        new ApexCharts(document.querySelector("#growthChart"), growthOptions).render();
    </script>
@endpush
