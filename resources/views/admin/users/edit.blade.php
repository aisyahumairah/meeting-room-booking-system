@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Edit User: {{ $user->name }}</h5>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class='bx bx-arrow-back'></i> Back to List
                        </a>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.users.update', $user) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <!-- Read-only fields -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Staff Number</label>
                                    <input type="text" class="form-control" value="{{ $user->staff_number }}" disabled>
                                    <div class="form-text">Staff number cannot be changed.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" value="{{ $user->email }}" disabled>
                                    <div class="form-text">Email cannot be changed.</div>
                                </div>
                            </div>

                            <!-- Editable fields -->
                            <div class="mb-3">
                                <label class="form-label" for="name">Full Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                    id="name" name="name" value="{{ old('name', $user->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="department">Department</label>
                                    <input type="text" class="form-control @error('department') is-invalid @enderror"
                                        id="department" name="department"
                                        value="{{ old('department', $user->department) }}">
                                    @error('department')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="phone">Phone</label>
                                    <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                        id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="role">Role <span class="text-danger">*</span></label>
                                <select class="form-select @error('role') is-invalid @enderror" id="role"
                                    name="role" required
                                    onchange="if(this.value !== '{{ $user->role }}') { document.getElementById('roleChangeWarning').classList.remove('d-none'); } else { document.getElementById('roleChangeWarning').classList.add('d-none'); }">
                                    <option value="regular_user"
                                        {{ old('role', $user->role) == 'regular_user' ? 'selected' : '' }}>Regular User
                                    </option>
                                    <option value="administrator"
                                        {{ old('role', $user->role) == 'administrator' ? 'selected' : '' }}>Administrator
                                    </option>
                                    <option value="director"
                                        {{ old('role', $user->role) == 'director' ? 'selected' : '' }}>Director</option>
                                    <option value="system_admin"
                                        {{ old('role', $user->role) == 'system_admin' ? 'selected' : '' }}>System Admin
                                    </option>
                                </select>
                                @error('role')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div id="roleChangeWarning" class="alert alert-warning mt-2 d-none">
                                    <i class='bx bx-info-circle'></i> Changing this user's role will affect their
                                    permissions immediately.
                                </div>
                            </div>

                            <!-- Account info (read-only) -->
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Status</label>
                                    <div>
                                        @if ($user->status === 'active')
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Last Login</label>
                                    <div class="text-muted">{{ $user->last_login_at?->format('M d, Y H:i') ?? 'Never' }}
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Account Created</label>
                                    <div class="text-muted">{{ $user->created_at->format('M d, Y') }}</div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
