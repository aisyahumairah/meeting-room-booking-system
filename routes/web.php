<?php

use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});

// Guest routes (unauthenticated users only)
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);

    Route::get('forgot-password', [ForgotPasswordController::class, 'showForm'])->name('password.request');
    Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('password.email');

    Route::get('reset-password/{token}', [ResetPasswordController::class, 'showForm'])->name('password.reset');
    Route::post('reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
});

// Authenticated routes - All logged in users
Route::middleware(['auth', 'active', 'must.change.password'])->group(function () {
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('password/change', [ChangePasswordController::class, 'showForm'])->name('password.change');
    Route::post('password/change', [ChangePasswordController::class, 'change']);

    // Dashboard routes
    Route::get('/dashboard/user', function () {
        return view('dashboard.user');
    })->name('dashboard.user');

    Route::get('/dashboard/admin', function () {
        return view('dashboard.admin');
    })->name('dashboard.admin');

    // User profile routes (to be implemented in Step 1.5)
    // Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    // Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // User's own bookings (to be implemented in Phase 2)
    // Route::get('/my-bookings', [BookingController::class, 'myBookings'])->name('bookings.my');
});

// Admin/Director routes - Booking and Room Management
Route::middleware(['auth', 'active', 'must.change.password', 'role:administrator,director'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Room management placeholder (to be implemented in Phase 2)
        Route::get('/rooms', function () {
            return response()->json(['message' => 'Room management placeholder']);
        })->name('rooms.index');

        // Booking approvals (to be implemented in Phase 2)
        // Route::get('/bookings/approvals', [BookingApprovalController::class, 'index'])->name('bookings.approvals');
        // Route::post('/bookings/{booking}/approve', [BookingApprovalController::class, 'approve'])->name('bookings.approve');
        // Route::post('/bookings/{booking}/reject', [BookingApprovalController::class, 'reject'])->name('bookings.reject');

        // Reports (to be implemented in Phase 4)
        // Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    });

// Director/SysAdmin routes - User Management and Audit
Route::middleware(['auth', 'active', 'must.change.password', 'role:director,system_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // User management placeholder (to be implemented in Phase 3)
        Route::get('/users', function () {
            return response()->json(['message' => 'User management placeholder']);
        })->name('users.index');

        // Audit logs (to be implemented in Step 1.7)
        // Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit.index');
    });

// SysAdmin only routes - System Configuration
Route::middleware(['auth', 'active', 'must.change.password', 'role:system_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // System settings (to be implemented in Phase 4)
        // Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        // Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    });
