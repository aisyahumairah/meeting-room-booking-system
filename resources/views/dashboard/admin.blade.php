@extends('layouts.app')

@section('title', 'Admin Dashboard')

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
                            <h4 class="mb-1">Welcome back, {{ auth()->user()->name }}! 🎛️</h4>
                            <p class="mb-0 text-muted">Role: <span
                                    class="badge bg-label-primary">{{ auth()->user()->role_display }}</span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Cards Row --}}
    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-6 mb-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text mb-2">Total Rooms</p>
                            <div class="d-flex align-items-end mb-1 flex-wrap">
                                <h4 class="card-title mb-0 me-2">0</h4>
                            </div>
                            <small class="text-muted">Active meeting rooms</small>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-primary rounded-3 p-2">
                                <i class="bx bx-building bx-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 mb-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text mb-2">Today's Bookings</p>
                            <div class="d-flex align-items-end mb-1 flex-wrap">
                                <h4 class="card-title mb-0 me-2">0</h4>
                            </div>
                            <small class="text-muted">Scheduled for today</small>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-success rounded-3 p-2">
                                <i class="bx bx-calendar-check bx-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 mb-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text mb-2">Pending Approvals</p>
                            <div class="d-flex align-items-end mb-1 flex-wrap">
                                <h4 class="card-title mb-0 me-2">0</h4>
                            </div>
                            <small class="text-muted">Awaiting review</small>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-warning rounded-3 p-2">
                                <i class="bx bx-time bx-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 mb-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text mb-2">Active Users</p>
                            <div class="d-flex align-items-end mb-1 flex-wrap">
                                <h4 class="card-title mb-0 me-2">0</h4>
                            </div>
                            <small class="text-muted">Registered staff</small>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-info rounded-3 p-2">
                                <i class="bx bx-user bx-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content Row --}}
    <div class="row">
        {{-- Recent Bookings --}}
        <div class="col-lg-8 mb-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recent Bookings</h5>
                    <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Room</th>
                                    <th>Date/Time</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="bx bx-calendar-x bx-lg mb-2 d-block"></i>
                                        No bookings yet - Coming in Phase 2
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Pending Approvals --}}
        <div class="col-lg-4 mb-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Pending Approvals</h5>
                    <span class="badge bg-warning">0</span>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
                        <div class="text-center text-muted">
                            <i class="bx bx-check-circle bx-lg mb-2"></i>
                            <p>No pending approvals</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3 col-sm-6">
                            <a href="#" class="btn btn-primary w-100">
                                <i class="bx bx-plus me-1"></i> New Booking
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <a href="#" class="btn btn-outline-primary w-100">
                                <i class="bx bx-building me-1"></i> Manage Rooms
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <a href="#" class="btn btn-outline-primary w-100">
                                <i class="bx bx-bar-chart-alt-2 me-1"></i> View Reports
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <a href="#" class="btn btn-outline-secondary w-100">
                                <i class="bx bx-user me-1"></i> Manage Users
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
