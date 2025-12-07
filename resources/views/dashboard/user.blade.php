@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    {{-- Welcome Message --}}
    <div class="row">
        <div class="col-12 mb-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <div class="avatar avatar-lg me-3">
                            <span class="avatar-initial rounded-circle bg-primary">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </span>
                        </div>
                        <div>
                            <h4 class="mb-1">Welcome back, {{ auth()->user()->name }}! 👋</h4>
                            <p class="mb-0 text-muted">Role: <span
                                    class="badge bg-label-primary">{{ auth()->user()->role_display }}</span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Dashboard Content Placeholder --}}
    <div class="row">
        <div class="col-lg-8 col-md-12 mb-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">My Upcoming Bookings</h5>
                    <small class="text-muted">Coming in Phase 2</small>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
                        <div class="text-center text-muted">
                            <i class="bx bx-calendar bx-lg mb-2"></i>
                            <p>No upcoming bookings</p>
                            <a href="#" class="btn btn-primary">
                                <i class="bx bx-plus me-1"></i> Book a Room
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-12 mb-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="#" class="btn btn-primary">
                            <i class="bx bx-plus me-1"></i> New Booking
                        </a>
                        <a href="#" class="btn btn-outline-primary">
                            <i class="bx bx-calendar me-1"></i> View Calendar
                        </a>
                        <a href="#" class="btn btn-outline-primary">
                            <i class="bx bx-building me-1"></i> Browse Rooms
                        </a>
                        <a href="{{ route('password.change') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-lock me-1"></i> Change Password
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Activity Placeholder --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recent Booking History</h5>
                    <small class="text-muted">Last 5 bookings</small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Room</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        No booking history yet
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
