@extends('layouts.app')

@section('title', 'User Management')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">User Management</h4>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                <i class='bx bx-plus'></i> Create New User
            </a>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-lg bg-label-primary me-3">
                                <i class='bx bx-user fs-3'></i>
                            </div>
                            <div>
                                <h6 class="mb-0">{{ $counts['total'] }}</h6>
                                <small class="text-muted">Total Users</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-lg bg-label-success me-3">
                                <i class='bx bx-check-circle fs-3'></i>
                            </div>
                            <div>
                                <h6 class="mb-0">{{ $counts['active'] }}</h6>
                                <small class="text-muted">Active</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-lg bg-label-secondary me-3">
                                <i class='bx bx-x-circle fs-3'></i>
                            </div>
                            <div>
                                <h6 class="mb-0">{{ $counts['inactive'] }}</h6>
                                <small class="text-muted">Inactive</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.users.index') }}" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control"
                            placeholder="Name, email, or staff number..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            <option value="">All Roles</option>
                            <option value="regular_user" {{ request('role') == 'regular_user' ? 'selected' : '' }}>Regular
                                User</option>
                            <option value="administrator" {{ request('role') == 'administrator' ? 'selected' : '' }}>
                                Administrator</option>
                            <option value="director" {{ request('role') == 'director' ? 'selected' : '' }}>Director</option>
                            <option value="system_admin" {{ request('role') == 'system_admin' ? 'selected' : '' }}>System
                                Admin</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Department</label>
                        <select name="department" class="form-select">
                            <option value="">All Departments</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept }}"
                                    {{ request('department') == $dept ? 'selected' : '' }}>{{ $dept }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Clear</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Staff #</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td>{{ $user->staff_number }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm me-2 bg-label-primary">
                                            <span
                                                class="avatar-initial rounded-circle">{{ substr($user->name, 0, 1) }}</span>
                                        </div>
                                        {{ $user->name }}
                                    </div>
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->department ?? '-' }}</td>
                                <td>
                                    @switch($user->role)
                                        @case('system_admin')
                                            <span class="badge bg-danger">System Admin</span>
                                        @break

                                        @case('director')
                                            <span class="badge bg-purple">Director</span>
                                        @break

                                        @case('administrator')
                                            <span class="badge bg-warning">Administrator</span>
                                        @break

                                        @default
                                            <span class="badge bg-primary">Regular User</span>
                                    @endswitch
                                </td>
                                <td>
                                    @if ($user->status === 'active')
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-icon" data-bs-toggle="dropdown">
                                            <i class='bx bx-dots-vertical-rounded'></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('admin.users.edit', $user) }}">
                                                    <i class='bx bx-edit-alt me-2'></i> Edit
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('admin.users.activity', $user) }}">
                                                    <i class='bx bx-history me-2'></i> View Activity
                                                </a>
                                            </li>
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                            @if ($user->status === 'active')
                                                <li>
                                                    <form action="{{ route('admin.users.deactivate', $user) }}"
                                                        method="POST"
                                                        onsubmit="return confirm('Deactivate {{ $user->name }}? They will lose system access.')">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit" class="dropdown-item text-warning">
                                                            <i class='bx bx-user-x me-2'></i> Deactivate
                                                        </button>
                                                    </form>
                                                </li>
                                            @else
                                                <li>
                                                    <form action="{{ route('admin.users.activate', $user) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit" class="dropdown-item text-success">
                                                            <i class='bx bx-user-check me-2'></i> Reactivate
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif
                                            <li>
                                                <button type="button" class="dropdown-item" data-bs-toggle="modal"
                                                    data-bs-target="#resetPasswordModal{{ $user->id }}">
                                                    <i class='bx bx-key me-2'></i> Reset Password
                                                </button>
                                            </li>
                                            @if ($user->id !== auth()->id())
                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item text-danger"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#deleteUserModal{{ $user->id }}">
                                                        <i class='bx bx-trash me-2'></i> Delete
                                                    </button>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>

                                    <!-- Reset Password Modal -->
                                    <div class="modal fade" id="resetPasswordModal{{ $user->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Reset Password for {{ $user->name }}</h5>
                                                    <button type="button" class="btn-close"
                                                        data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="{{ route('admin.users.reset-password', $user) }}"
                                                    method="POST">
                                                    @csrf
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio"
                                                                    name="method" value="generate"
                                                                    id="method_generate{{ $user->id }}" checked>
                                                                <label class="form-check-label"
                                                                    for="method_generate{{ $user->id }}">
                                                                    Generate temporary password (display on screen)
                                                                </label>
                                                            </div>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio"
                                                                    name="method" value="email"
                                                                    id="method_email{{ $user->id }}">
                                                                <label class="form-check-label"
                                                                    for="method_email{{ $user->id }}">
                                                                    Send password reset link via email
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <p class="text-muted small">
                                                            User will be required to change password on next login.
                                                        </p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline-secondary"
                                                            data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary">Reset
                                                            Password</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Delete User Modal -->
                                    <div class="modal fade" id="deleteUserModal{{ $user->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title text-danger">Delete User</h5>
                                                    <button type="button" class="btn-close"
                                                        data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <div class="modal-body">
                                                        <p>To permanently delete <strong>{{ $user->name }}</strong>, type
                                                            their full name below:</p>
                                                        <input type="text" class="form-control"
                                                            placeholder="{{ $user->name }}"
                                                            onkeyup="document.getElementById('confirmDelete{{ $user->id }}').disabled = this.value !== '{{ $user->name }}'">
                                                        <p class="text-danger small mt-2">
                                                            <i class='bx bx-error-circle'></i> This action cannot be
                                                            undone.
                                                        </p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline-secondary"
                                                            data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" id="confirmDelete{{ $user->id }}"
                                                            class="btn btn-danger" disabled>
                                                            Delete Permanently
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class='bx bx-user-x fs-1 text-muted'></i>
                                        <p class="text-muted mt-2">No users found matching your criteria.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($users->hasPages())
                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Showing {{ $users->firstItem() }}-{{ $users->lastItem() }} of
                                {{ $users->total() }} users</span>
                            {{ $users->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endsection
