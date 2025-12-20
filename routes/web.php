<?php

use App\Http\Controllers\Admin\RoomController as AdminRoomController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root to dashboard (will redirect to login if not authenticated)
Route::get('/', [DashboardController::class, 'index'])->middleware('auth')->name('home');

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
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/user', [DashboardController::class, 'userDashboard'])->name('dashboard.user');
    Route::get('/dashboard/admin', [DashboardController::class, 'adminDashboard'])
        ->middleware('role:administrator,director')
        ->name('dashboard.admin');

    // User profile routes
    Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('profile/password', [ProfileController::class, 'showChangePasswordForm'])->name('profile.password');
    Route::put('profile/password', [ProfileController::class, 'changePassword'])->name('profile.password.update');

    // Room Browsing (all authenticated users)
    Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
    Route::get('/rooms/{room}', [RoomController::class, 'show'])->name('rooms.show');

    // Global Calendar
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');
    Route::get('/ajax/calendar/events', [CalendarController::class, 'events'])
        ->name('ajax.calendar.events');

    // Booking creation (all authenticated users)
    Route::get('/bookings/create', [BookingController::class, 'create'])->name('bookings.create');
    Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');

    // AJAX routes for booking
    // AJAX routes for booking (Legacy)
    Route::post('/ajax/bookings/check-availability', [BookingController::class, 'checkAvailability'])
        ->name('ajax.bookings.check-availability');
    Route::post('/ajax/bookings/preview-recurrence', [BookingController::class, 'previewRecurrence'])
        ->name('ajax.bookings.preview-recurrence');

    // Enhanced Availability API
    Route::prefix('ajax')->name('ajax.')->group(function () {
        Route::post('/availability/check', [AvailabilityController::class, 'check'])
            ->name('availability.check');
        Route::get('/rooms/{room}/schedule', [AvailabilityController::class, 'roomSchedule'])
            ->name('rooms.schedule');
        Route::get('/rooms/{room}/available-slots', [AvailabilityController::class, 'availableSlots'])
            ->name('rooms.available-slots');
    });

    // Recurring booking
    Route::get('/bookings/create-recurring', [BookingController::class, 'createRecurring'])
        ->name('bookings.create-recurring');
    Route::post('/bookings/recurring', [BookingController::class, 'storeRecurring'])
        ->name('bookings.store-recurring');

    // My Bookings
    Route::get('/my-bookings', [BookingController::class, 'myBookings'])->name('my-bookings');
    Route::get('/my-bookings/{booking}', [BookingController::class, 'show'])->name('my-bookings.show');
    Route::get('/my-bookings/{booking}/edit', [BookingController::class, 'edit'])->name('my-bookings.edit');
    Route::put('/my-bookings/{booking}', [BookingController::class, 'update'])->name('my-bookings.update');
    Route::delete('/my-bookings/{booking}', [BookingController::class, 'destroy'])->name('my-bookings.destroy');

    // AJAX for calendar
    Route::get('/ajax/my-bookings/calendar', [BookingController::class, 'myBookingsCalendar'])
        ->name('ajax.my-bookings.calendar');
});

// API routes (authenticated)
Route::middleware(['auth'])->prefix('api')->group(function () {
    Route::get('/rooms/{room}/availability', [RoomController::class, 'availability'])
        ->name('api.rooms.availability');
});

// Admin/Director routes - Booking and Room Management
Route::middleware(['auth', 'active', 'must.change.password', 'role:administrator,director'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Room management
        Route::resource('rooms', AdminRoomController::class);
        Route::put('rooms/{room}/status', [AdminRoomController::class, 'updateStatus'])->name('rooms.status');
        Route::post('rooms/{room}/images/{image}/primary', [
            AdminRoomController::class,
            'setPrimaryImage'
        ])->name('rooms.images.primary');
        Route::delete('rooms/{room}/images/{image}', [AdminRoomController::class, 'deleteImage'])->name('rooms.images.destroy');

        // Booking management
        Route::middleware('can:manage-bookings')->group(function () {
            Route::resource('bookings', AdminBookingController::class)
                ->except(['create', 'store']);

            // Manual trigger for booking completion (testing purposes)
            Route::post('/bookings/complete-expired', function () {
                \Illuminate\Support\Facades\Artisan::call('bookings:complete-expired');
                return back()->with('success', 'Completed expired bookings: ' . \Illuminate\Support\Facades\Artisan::output());
            })->name('bookings.complete-expired');
        });

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
