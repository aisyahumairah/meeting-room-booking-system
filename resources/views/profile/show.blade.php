@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Account /</span> My Profile
    </h4>

    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <h5 class="card-header">Profile Details</h5>
                <div class="card-body">
                    <div class="d-flex align-items-start align-items-sm-center gap-4 mb-4">
                        <img src="{{ asset('assets/img/avatars/1.png') }}" alt="user-avatar" class="d-block rounded"
                            height="100" width="100">
                        <div>
                            <h5 class="mb-1">{{ $user->name }}</h5>
                            <span class="badge bg-{{ $user->role_badge }}">{{ $user->role_display }}</span>
                            <span class="badge bg-{{ $user->status_badge }} ms-1">{{ ucfirst($user->status) }}</span>
                        </div>
                    </div>
                    <hr class="my-0">

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label fw-medium">Staff Number</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control-plaintext" value="{{ $user->staff_number }}"
                                        readonly>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label fw-medium">Full Name</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control-plaintext" value="{{ $user->name }}"
                                        readonly>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label fw-medium">Email Address</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control-plaintext" value="{{ $user->email }}"
                                        readonly>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label fw-medium">Department</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control-plaintext"
                                        value="{{ $user->department ?? '-' }}" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label fw-medium">Phone</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control-plaintext" value="{{ $user->phone ?? '-' }}"
                                        readonly>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label fw-medium">Role</label>
                                <div class="col-sm-8">
                                    <span class="badge bg-{{ $user->role_badge }}">{{ $user->role_display }}</span>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label fw-medium">Account Created</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control-plaintext"
                                        value="{{ $user->created_at->format('M d, Y') }}" readonly>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label fw-medium">Last Login</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control-plaintext"
                                        value="{{ $user->last_login_at ? $user->last_login_at->format('M d, Y H:i') : 'Never' }}"
                                        readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('profile.edit') }}" class="btn btn-primary">
                        <i class="bx bx-edit me-1"></i> Edit Profile
                    </a>
                    <a href="{{ route('profile.password') }}" class="btn btn-outline-secondary">
                        <i class="bx bx-lock-alt me-1"></i> Change Password
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
