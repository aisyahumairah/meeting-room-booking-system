# Step 4.1: User Management

**Priority:** HIGH | **Ref:** §7.2.1 | **Dependencies:** Phase 1-3 Complete  
**Status:** TODO

---

## Objective

Implement full user management functionality accessible to Directors and System Administrators, including CRUD operations, role assignment, status management, and password reset.

---

## Task 4.1.1: Create Admin UserController

```bash
php artisan make:controller Admin/UserController
```

**File:** `app/Http/Controllers/Admin/UserController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Display user list with search and filters.
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Search by name, email, or staff number
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%")
                  ->orWhere('staff_number', 'ilike', "%{$search}%");
            });
        }

        // Filter by role
        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        // Filter by department
        if ($department = $request->input('department')) {
            $query->where('department', $department);
        }

        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $users = $query->orderBy('name')->paginate(25)->withQueryString();

        // Get unique departments for filter dropdown
        $departments = User::whereNotNull('department')
            ->distinct()
            ->pluck('department')
            ->sort()
            ->values();

        // Count summaries
        $counts = [
            'total' => User::count(),
            'active' => User::where('status', 'active')->count(),
            'inactive' => User::where('status', 'inactive')->count(),
        ];

        return view('admin.users.index', compact('users', 'departments', 'counts'));
    }

    /**
     * Show create user form.
     */
    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Store new user.
     */
    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();

        // Generate temporary password if not provided
        $tempPassword = $validated['password'] ?? Str::random(12);

        $user = User::create([
            'staff_number' => $validated['staff_number'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($tempPassword),
            'department' => $validated['department'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'] ?? 'regular_user',
            'status' => 'active',
            'must_change_password' => true,
        ]);

        // Log audit event
        AuditService::log(
            AuditService::EVENT_USER_CREATED,
            'user',
            $user->id,
            [
                'staff_number' => $user->staff_number,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]
        );

        // TODO: Send welcome email with credentials (Step 4.5)
        // Mail::to($user->email)->queue(new WelcomeEmail($user, $tempPassword));

        return redirect()
            ->route('admin.users.index')
            ->with('success', "User {$user->name} created successfully.");
    }

    /**
     * Show edit user form.
     */
    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update user.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();
        $oldRole = $user->role;

        $changes = [];
        foreach (['name', 'department', 'phone', 'role'] as $field) {
            if (isset($validated[$field]) && $user->$field !== $validated[$field]) {
                $changes[$field] = [
                    'from' => $user->$field,
                    'to' => $validated[$field],
                ];
            }
        }

        $user->update($validated);

        // Log role change specifically if changed
        if ($oldRole !== $user->role) {
            AuditService::log(
                AuditService::EVENT_USER_ROLE_CHANGED,
                'user',
                $user->id,
                [
                    'old_role' => $oldRole,
                    'new_role' => $user->role,
                ]
            );
        } elseif (!empty($changes)) {
            AuditService::log(
                AuditService::EVENT_USER_UPDATED,
                'user',
                $user->id,
                $changes
            );
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', "User {$user->name} updated successfully.");
    }

    /**
     * Deactivate user.
     */
    public function deactivate(User $user)
    {
        // Prevent self-deactivation
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['status' => 'inactive']);

        AuditService::log(
            AuditService::EVENT_USER_DEACTIVATED,
            'user',
            $user->id,
            ['user_name' => $user->name]
        );

        return back()->with('success', "User {$user->name} has been deactivated.");
    }

    /**
     * Reactivate user.
     */
    public function activate(User $user)
    {
        $user->update(['status' => 'active']);

        AuditService::log(
            AuditService::EVENT_USER_REACTIVATED,
            'user',
            $user->id,
            ['user_name' => $user->name]
        );

        return back()->with('success', "User {$user->name} has been reactivated.");
    }

    /**
     * Delete user (only if no bookings/activity).
     */
    public function destroy(User $user)
    {
        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        // Check for existing bookings
        $bookingCount = $user->bookings()->count();
        if ($bookingCount > 0) {
            return back()->with('error', 
                "Cannot delete {$user->name}. User has {$bookingCount} booking(s). Use 'Deactivate' instead.");
        }

        // Check for audit log entries as actor
        $auditCount = \App\Models\AuditLog::where('actor_id', $user->id)->count();
        if ($auditCount > 0) {
            return back()->with('error', 
                "Cannot delete {$user->name}. User has {$auditCount} audit log entries. Use 'Deactivate' instead.");
        }

        $userName = $user->name;

        AuditService::log(
            AuditService::EVENT_USER_DELETED,
            'user',
            $user->id,
            ['user_name' => $userName, 'email' => $user->email]
        );

        $user->forceDelete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', "User {$userName} has been permanently deleted.");
    }

    /**
     * Reset user password.
     */
    public function resetPassword(Request $request, User $user)
    {
        $method = $request->input('method', 'generate'); // 'generate' or 'email'

        if ($method === 'generate') {
            $tempPassword = Str::random(12);
            $user->update([
                'password' => Hash::make($tempPassword),
                'must_change_password' => true,
            ]);

            AuditService::log(
                AuditService::EVENT_USER_PASSWORD_RESET_BY_ADMIN,
                'user',
                $user->id,
                ['method' => 'temp_password_generated']
            );

            return back()->with('success', 
                "Password reset for {$user->name}. Temporary password: {$tempPassword}");
        }

        // Send reset link via email
        // TODO: Implement in Step 4.5
        // Password::sendResetLink(['email' => $user->email]);

        AuditService::log(
            AuditService::EVENT_USER_PASSWORD_RESET_BY_ADMIN,
            'user',
            $user->id,
            ['method' => 'reset_link_sent']
        );

        return back()->with('success', 
            "Password reset link sent to {$user->email}.");
    }

    /**
     * View user activity history.
     */
    public function activity(Request $request, User $user)
    {
        // This links to Step 4.3 - User Activity History
        $query = \App\Models\AuditLog::where('actor_id', $user->id);

        // Date range filter
        if ($startDate = $request->input('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        // Action type filter
        if ($eventType = $request->input('event_type')) {
            $query->where('event_type', $eventType);
        }

        $activities = $query->orderBy('created_at', 'desc')->paginate(50);

        // Calculate user statistics
        $stats = $this->calculateUserStats($user);

        return view('admin.users.activity', compact('user', 'activities', 'stats'));
    }

    /**
     * Calculate user statistics for activity page.
     */
    private function calculateUserStats(User $user): array
    {
        $bookings = $user->bookings();

        $totalBookings = $bookings->count();
        $cancelledBookings = $user->bookings()->where('status', 'cancelled')->count();

        // Most frequently booked room
        $mostBookedRoom = $user->bookings()
            ->selectRaw('room_id, count(*) as booking_count')
            ->groupBy('room_id')
            ->orderByDesc('booking_count')
            ->with('room:id,name')
            ->first();

        return [
            'total_bookings' => $totalBookings,
            'cancelled_bookings' => $cancelledBookings,
            'cancellation_rate' => $totalBookings > 0 
                ? round(($cancelledBookings / $totalBookings) * 100, 1) 
                : 0,
            'last_login' => $user->last_login_at,
            'account_age_days' => $user->created_at->diffInDays(now()),
            'most_booked_room' => $mostBookedRoom?->room?->name ?? 'None',
        ];
    }
}
```

---

## Task 4.1.2: Create Form Request Validators

```bash
php artisan make:request Admin/StoreUserRequest
php artisan make:request Admin/UpdateUserRequest
```

**File:** `app/Http/Requests/Admin/StoreUserRequest.php`

```php
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->canManageUsers();
    }

    public function rules(): array
    {
        return [
            'staff_number' => ['required', 'string', 'max:20', 'unique:users,staff_number'],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'department' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in(['regular_user', 'administrator', 'director', 'system_admin'])],
            'password' => ['nullable', 'string', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'staff_number.unique' => 'This staff number is already registered.',
            'email.unique' => 'This email address is already registered.',
        ];
    }
}
```

**File:** `app/Http/Requests/Admin/UpdateUserRequest.php`

```php
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->canManageUsers();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in(['regular_user', 'administrator', 'director', 'system_admin'])],
        ];
    }
}
```

---

## Task 4.1.3: Add User Management Audit Events

**File:** `app/Services/AuditService.php`

Add these constants to the existing AuditService class:

```php
// User Management Events (add after existing constants)
public const EVENT_USER_CREATED = 'user.created';
public const EVENT_USER_UPDATED = 'user.updated';
public const EVENT_USER_ROLE_CHANGED = 'user.role_changed';
public const EVENT_USER_DEACTIVATED = 'user.deactivated';
public const EVENT_USER_REACTIVATED = 'user.reactivated';
public const EVENT_USER_DELETED = 'user.deleted';
public const EVENT_USER_PASSWORD_RESET_BY_ADMIN = 'user.password_reset_by_admin';
```

---

## Task 4.1.4: Define User Management Routes

**File:** `routes/web.php`

Add to the admin routes group (with Director/SysAdmin middleware):

```php
// User Management (Director/SysAdmin only)
Route::middleware(['auth', 'check.active', 'check.role:director,system_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', Admin\UserController::class)->except(['show']);
    Route::put('users/{user}/deactivate', [Admin\UserController::class, 'deactivate'])->name('users.deactivate');
    Route::put('users/{user}/activate', [Admin\UserController::class, 'activate'])->name('users.activate');
    Route::post('users/{user}/reset-password', [Admin\UserController::class, 'resetPassword'])->name('users.reset-password');
    Route::get('users/{user}/activity', [Admin\UserController::class, 'activity'])->name('users.activity');
});
```

---

## Task 4.1.5: Create User List View

**File:** `resources/views/admin/users/index.blade.php`

Convert from mockup: `mrbs-mock-up/pages/users-list.html`

```blade
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
                           placeholder="Name, email, or staff number..." 
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select">
                        <option value="">All Roles</option>
                        <option value="regular_user" {{ request('role') == 'regular_user' ? 'selected' : '' }}>Regular User</option>
                        <option value="administrator" {{ request('role') == 'administrator' ? 'selected' : '' }}>Administrator</option>
                        <option value="director" {{ request('role') == 'director' ? 'selected' : '' }}>Director</option>
                        <option value="system_admin" {{ request('role') == 'system_admin' ? 'selected' : '' }}>System Admin</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Department</label>
                    <select name="department" class="form-select">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept }}" {{ request('department') == $dept ? 'selected' : '' }}>{{ $dept }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
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
                                    <span class="avatar-initial rounded-circle">{{ substr($user->name, 0, 1) }}</span>
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
                            @if($user->status === 'active')
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
                                    <li><hr class="dropdown-divider"></li>
                                    @if($user->status === 'active')
                                    <li>
                                        <form action="{{ route('admin.users.deactivate', $user) }}" method="POST" 
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
                                        <form action="{{ route('admin.users.activate', $user) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="dropdown-item text-success">
                                                <i class='bx bx-user-check me-2'></i> Reactivate
                                            </button>
                                        </form>
                                    </li>
                                    @endif
                                    <li>
                                        <button type="button" class="dropdown-item" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#resetPasswordModal{{ $user->id }}">
                                            <i class='bx bx-key me-2'></i> Reset Password
                                        </button>
                                    </li>
                                    @if($user->id !== auth()->id())
                                    <li><hr class="dropdown-divider"></li>
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
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="{{ route('admin.users.reset-password', $user) }}" method="POST">
                                            @csrf
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" 
                                                               name="method" value="generate" id="method_generate{{ $user->id }}" checked>
                                                        <label class="form-check-label" for="method_generate{{ $user->id }}">
                                                            Generate temporary password (display on screen)
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" 
                                                               name="method" value="email" id="method_email{{ $user->id }}">
                                                        <label class="form-check-label" for="method_email{{ $user->id }}">
                                                            Send password reset link via email
                                                        </label>
                                                    </div>
                                                </div>
                                                <p class="text-muted small">
                                                    User will be required to change password on next login.
                                                </p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Reset Password</button>
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
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <div class="modal-body">
                                                <p>To permanently delete <strong>{{ $user->name }}</strong>, type their full name below:</p>
                                                <input type="text" class="form-control" 
                                                       placeholder="{{ $user->name }}"
                                                       onkeyup="document.getElementById('confirmDelete{{ $user->id }}').disabled = this.value !== '{{ $user->name }}'">
                                                <p class="text-danger small mt-2">
                                                    <i class='bx bx-error-circle'></i> This action cannot be undone.
                                                </p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" id="confirmDelete{{ $user->id }}" class="btn btn-danger" disabled>
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
        @if($users->hasPages())
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted">Showing {{ $users->firstItem() }}-{{ $users->lastItem() }} of {{ $users->total() }} users</span>
                {{ $users->links() }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
```

---

## Task 4.1.6: Create User Create/Edit Views

**File:** `resources/views/admin/users/create.blade.php`

```blade
@extends('layouts.app')

@section('title', 'Create New User')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Create New User</h5>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class='bx bx-arrow-back'></i> Back to List
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.users.store') }}" method="POST">
                        @csrf
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="staff_number">Staff Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('staff_number') is-invalid @enderror" 
                                       id="staff_number" name="staff_number" value="{{ old('staff_number') }}" required>
                                @error('staff_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                       id="email" name="email" value="{{ old('email') }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="name">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="department">Department</label>
                                <input type="text" class="form-control @error('department') is-invalid @enderror" 
                                       id="department" name="department" value="{{ old('department') }}">
                                @error('department')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="phone">Phone</label>
                                <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                       id="phone" name="phone" value="{{ old('phone') }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="role">Role <span class="text-danger">*</span></label>
                            <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                                <option value="regular_user" {{ old('role') == 'regular_user' ? 'selected' : '' }}>Regular User</option>
                                <option value="administrator" {{ old('role') == 'administrator' ? 'selected' : '' }}>Administrator</option>
                                <option value="director" {{ old('role') == 'director' ? 'selected' : '' }}>Director</option>
                                <option value="system_admin" {{ old('role') == 'system_admin' ? 'selected' : '' }}>System Admin</option>
                            </select>
                            @error('role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="password">Initial Password</label>
                            <input type="text" class="form-control @error('password') is-invalid @enderror" 
                                   id="password" name="password" placeholder="Leave blank to auto-generate">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">User will be required to change password on first login.</div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Create User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

**File:** `resources/views/admin/users/edit.blade.php`

Convert from mockup: `mrbs-mock-up/pages/users-edit.html`

```blade
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
                            <label class="form-label" for="name">Full Name <span class="text-danger">*</span></label>
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
                                       id="department" name="department" value="{{ old('department', $user->department) }}">
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
                            <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required
                                    onchange="if(this.value !== '{{ $user->role }}') { document.getElementById('roleChangeWarning').classList.remove('d-none'); } else { document.getElementById('roleChangeWarning').classList.add('d-none'); }">
                                <option value="regular_user" {{ old('role', $user->role) == 'regular_user' ? 'selected' : '' }}>Regular User</option>
                                <option value="administrator" {{ old('role', $user->role) == 'administrator' ? 'selected' : '' }}>Administrator</option>
                                <option value="director" {{ old('role', $user->role) == 'director' ? 'selected' : '' }}>Director</option>
                                <option value="system_admin" {{ old('role', $user->role) == 'system_admin' ? 'selected' : '' }}>System Admin</option>
                            </select>
                            @error('role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div id="roleChangeWarning" class="alert alert-warning mt-2 d-none">
                                <i class='bx bx-info-circle'></i> Changing this user's role will affect their permissions immediately.
                            </div>
                        </div>

                        <!-- Account info (read-only) -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <div>
                                    @if($user->status === 'active')
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Last Login</label>
                                <div class="text-muted">{{ $user->last_login_at?->format('M d, Y H:i') ?? 'Never' }}</div>
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
```

---

## Task 4.1.7: Update Sidebar Navigation

**File:** `resources/views/layouts/partials/sidebar.blade.php`

Add User Management menu item (for Director/SysAdmin):

```blade
@if(auth()->user()->canManageUsers())
<li class="menu-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
    <a href="{{ route('admin.users.index') }}" class="menu-link">
        <i class="menu-icon tf-icons bx bx-user-circle"></i>
        <div>User Management</div>
    </a>
</li>
@endif
```

---

## Testing Requirements

**File:** `tests/Feature/UserManagementTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $sysAdmin;
    protected User $director;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->sysAdmin = User::factory()->create(['role' => 'system_admin']);
        $this->director = User::factory()->create(['role' => 'director']);
        $this->regularUser = User::factory()->create(['role' => 'regular_user']);
    }

    public function test_sysadmin_can_access_user_list(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.users.index'));
        $response->assertStatus(200);
        $response->assertSee('User Management');
    }

    public function test_director_can_access_user_list(): void
    {
        $response = $this->actingAs($this->director)->get(route('admin.users.index'));
        $response->assertStatus(200);
    }

    public function test_regular_user_cannot_access_user_list(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.users.index'));
        $response->assertStatus(403);
    }

    public function test_sysadmin_can_create_user(): void
    {
        $response = $this->actingAs($this->sysAdmin)->post(route('admin.users.store'), [
            'staff_number' => 'NEW001',
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'role' => 'regular_user',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
    }

    public function test_cannot_create_user_with_duplicate_staff_number(): void
    {
        $response = $this->actingAs($this->sysAdmin)->post(route('admin.users.store'), [
            'staff_number' => $this->regularUser->staff_number,
            'name' => 'Duplicate',
            'email' => 'duplicate@example.com',
            'role' => 'regular_user',
        ]);

        $response->assertSessionHasErrors('staff_number');
    }

    public function test_sysadmin_can_update_user(): void
    {
        $response = $this->actingAs($this->sysAdmin)->put(route('admin.users.update', $this->regularUser), [
            'name' => 'Updated Name',
            'department' => 'IT',
            'phone' => '123456',
            'role' => 'administrator',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $this->regularUser->id,
            'name' => 'Updated Name',
            'role' => 'administrator',
        ]);
    }

    public function test_sysadmin_can_deactivate_user(): void
    {
        $response = $this->actingAs($this->sysAdmin)->put(route('admin.users.deactivate', $this->regularUser));

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $this->regularUser->id,
            'status' => 'inactive',
        ]);
    }

    public function test_cannot_deactivate_own_account(): void
    {
        $response = $this->actingAs($this->sysAdmin)->put(route('admin.users.deactivate', $this->sysAdmin));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', [
            'id' => $this->sysAdmin->id,
            'status' => 'active',
        ]);
    }

    public function test_cannot_delete_user_with_bookings(): void
    {
        // Create a booking for the user
        \App\Models\Booking::factory()->create(['user_id' => $this->regularUser->id]);

        $response = $this->actingAs($this->sysAdmin)->delete(route('admin.users.destroy', $this->regularUser));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->regularUser->id]);
    }

    public function test_can_delete_user_without_activity(): void
    {
        $newUser = User::factory()->create();

        $response = $this->actingAs($this->sysAdmin)->delete(route('admin.users.destroy', $newUser));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $newUser->id]);
    }

    public function test_sysadmin_can_reset_user_password(): void
    {
        $response = $this->actingAs($this->sysAdmin)->post(route('admin.users.reset-password', $this->regularUser), [
            'method' => 'generate',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'id' => $this->regularUser->id,
            'must_change_password' => true,
        ]);
    }

    public function test_user_search_filter_works(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.users.index', ['search' => $this->regularUser->name]));

        $response->assertStatus(200);
        $response->assertSee($this->regularUser->name);
    }

    public function test_user_role_filter_works(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.users.index', ['role' => 'director']));

        $response->assertStatus(200);
        $response->assertSee($this->director->name);
        $response->assertDontSee($this->regularUser->name);
    }
}
```

---

## Acceptance Criteria

- [ ] User list page displays all users with search, filters, and pagination
- [ ] Stats cards show total, active, and inactive user counts
- [ ] Create user form works with validation
- [ ] Edit user form works (staff_number and email are read-only)
- [ ] Role change shows warning and logs to audit trail
- [ ] Deactivate/reactivate toggle works correctly
- [ ] Cannot deactivate own account
- [ ] Cannot delete user with bookings or audit activity
- [ ] Delete requires typing user name to confirm
- [ ] Password reset (generate/email) works
- [ ] User activity link navigates to activity history
- [ ] All actions logged to audit trail
- [ ] All tests pass: `php artisan test --filter=UserManagementTest`

---

**Next:** [Step 4.2 - Audit Trail Viewer](./step-4.2-audit-trail-viewer.md)
