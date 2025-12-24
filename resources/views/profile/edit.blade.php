@extends('layouts.app')

@section('title', 'Edit Profile')

@section('content')
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Account / <a href="{{ route('profile.show') }}">My Profile</a> /</span> Edit
    </h4>

    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <h5 class="card-header">Edit Profile</h5>
                <div class="card-body">
                    <div class="d-flex align-items-start align-items-sm-center gap-4 mb-4">
                        <img src="{{ asset('assets/img/avatars/kosong.jpeg') }}" alt="user-avatar" class="d-block rounded"
                            height="100" width="100">
                        <div>
                            <h5 class="mb-1">{{ $user->name }}</h5>
                            <span class="badge bg-{{ $user->role_badge }}">{{ $user->role_display }}</span>
                        </div>
                    </div>
                    <hr class="my-0">

                    <form action="{{ route('profile.update') }}" method="POST" class="mt-4">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <!-- Staff Number (Read Only) -->
                                <div class="row mb-3">
                                    <label class="col-sm-4 col-form-label fw-medium">Staff Number</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control-plaintext"
                                            value="{{ $user->staff_number }}" readonly>
                                        <small class="text-muted">Cannot be changed</small>
                                    </div>
                                </div>

                                <!-- Full Name (Editable) -->
                                <div class="row mb-3">
                                    <label for="name" class="col-sm-4 col-form-label fw-medium">Full Name <span
                                            class="text-danger">*</span></label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                                            id="name" name="name" value="{{ old('name', $user->name) }}" required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Email (Read Only) -->
                                <div class="row mb-3">
                                    <label class="col-sm-4 col-form-label fw-medium">Email Address</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control-plaintext" value="{{ $user->email }}"
                                            readonly>
                                        <small class="text-muted">Cannot be changed</small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <!-- Department (Editable) -->
                                <div class="row mb-3">
                                    <label for="department" class="col-sm-4 col-form-label fw-medium">Department</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control @error('department') is-invalid @enderror"
                                            id="department" name="department"
                                            value="{{ old('department', $user->department) }}"
                                            placeholder="e.g., IT Department">
                                        @error('department')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Phone (Editable) -->
                                <div class="row mb-3">
                                    <label for="phone" class="col-sm-4 col-form-label fw-medium">Phone Number</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                            id="phone" name="phone" value="{{ old('phone', $user->phone) }}"
                                            placeholder="e.g., +60 12-345 6789">
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Role (Read Only) -->
                                <div class="row mb-3">
                                    <label class="col-sm-4 col-form-label fw-medium">Role</label>
                                    <div class="col-sm-8">
                                        <span class="badge bg-{{ $user->role_badge }}">{{ $user->role_display }}</span>
                                        <br><small class="text-muted">Contact administrator to change</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="bx bx-save me-1"></i> Save Changes
                                </button>
                                <a href="{{ route('profile.show') }}" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
