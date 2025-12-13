<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\AuditLog;
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
            return back()->with(
                'error',
                "Cannot delete {$user->name}. User has {$bookingCount} booking(s). Use 'Deactivate' instead."
            );
        }

        // Check for audit log entries as actor
        $auditCount = AuditLog::where('actor_id', $user->id)->count();
        if ($auditCount > 0) {
            return back()->with(
                'error',
                "Cannot delete {$user->name}. User has {$auditCount} audit log entries. Use 'Deactivate' instead."
            );
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

            return back()->with(
                'success',
                "Password reset for {$user->name}. Temporary password: {$tempPassword}"
            );
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

        return back()->with(
            'success',
            "Password reset link sent to {$user->email}."
        );
    }

    /**
     * View user activity history with statistics.
     */
    public function activity(Request $request, User $user)
    {
        $query = AuditLog::where('actor_id', $user->id);

        // Date range filter
        if ($startDate = $request->input('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        // Date preset
        if ($preset = $request->input('preset')) {
            $this->applyDatePresetToQuery($query, $preset);
        }

        // Action type filter
        if ($eventType = $request->input('event_type')) {
            $query->where('event_type', $eventType);
        }

        $activities = $query->orderBy('created_at', 'desc')->paginate(50)->withQueryString();

        // Get event types for this user's activities
        $eventTypes = AuditLog::where('actor_id', $user->id)
            ->distinct()
            ->pluck('event_type')
            ->sort()
            ->values();

        // Calculate user statistics
        $stats = $this->calculateUserStats($user);

        return view('admin.users.activity', compact('user', 'activities', 'stats', 'eventTypes'));
    }

    /**
     * Calculate user statistics for activity page.
     */
    private function calculateUserStats(User $user): array
    {
        // Booking statistics
        $totalBookings = $user->bookings()->count();
        $confirmedBookings = $user->bookings()->where('status', 'confirmed')->count();
        $completedBookings = $user->bookings()->where('status', 'completed')->count();
        $cancelledBookings = $user->bookings()->where('status', 'cancelled')->count();

        // Most frequently booked room
        $mostBookedRoom = $user->bookings()
            ->selectRaw('room_id, count(*) as booking_count')
            ->groupBy('room_id')
            ->orderByDesc('booking_count')
            ->with('room:id,name')
            ->first();

        // Average booking duration
        $avgDuration = $user->bookings()
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (end_time::time - start_time::time)) / 60) as avg_minutes')
            ->first()
            ->avg_minutes;

        // Recent login count (last 30 days)
        $recentLogins = AuditLog::where('actor_id', $user->id)
            ->where('event_type', 'login_success')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        return [
            'total_bookings' => $totalBookings,
            'confirmed_bookings' => $confirmedBookings,
            'completed_bookings' => $completedBookings,
            'cancelled_bookings' => $cancelledBookings,
            'cancellation_rate' => $totalBookings > 0
                ? round(($cancelledBookings / $totalBookings) * 100, 1)
                : 0,
            'last_login' => $user->last_login_at,
            'account_age_days' => $user->created_at ? $user->created_at->diffInDays(now()) : 0,
            'most_booked_room' => $mostBookedRoom?->room?->name ?? 'None',
            'most_booked_room_count' => $mostBookedRoom?->booking_count ?? 0,
            'avg_booking_duration' => $avgDuration ? round($avgDuration) : 0,
            'recent_logins' => $recentLogins,
        ];
    }

    /**
     * Apply date preset to activity query.
     */
    private function applyDatePresetToQuery($query, string $preset): void
    {
        match ($preset) {
            'today' => $query->whereDate('created_at', now()->toDateString()),
            'last_7_days' => $query->whereDate('created_at', '>=', now()->subDays(7)->toDateString()),
            'last_30_days' => $query->whereDate('created_at', '>=', now()->subDays(30)->toDateString()),
            'this_month' => $query->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year),
            default => null,
        };
    }

    /**
     * Export user activity history.
     */
    public function exportActivity(Request $request, User $user)
    {
        $format = $request->input('format', 'csv');
        $filename = "UserActivity_{$user->staff_number}_" . now()->format('YmdHis');

        // Log export
        AuditService::log(
            'user_activity.exported',
            'user',
            $user->id,
            ['format' => $format]
        );

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\UserActivityExport($user, $request->all()),
            "{$filename}.{$format}",
            $format === 'xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV
        );
    }
}
