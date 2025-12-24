# Step 4.4: System Configuration

**Priority:** HIGH | **Ref:** §7.3.3 | **Dependencies:** None  
**Status:** COMPLETE

---

## Objective

Create a system configuration management interface accessible only to System Administrators. The configuration uses a key-value `system_settings` table to store configurable settings like session timeout, notification toggles, and maintenance mode.

---

## Task 4.4.1: Create System Settings Migration

```bash
php artisan make:migration create_system_settings_table
```

**File:** `database/migrations/YYYY_MM_DD_HHMMSS_create_system_settings_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string'); // string, int, bool, json
            $table->string('group', 50)->default('general'); // For organizing settings
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
```

---

## Task 4.4.2: Create SystemSetting Model

```bash
php artisan make:model SystemSetting
```

**File:** `app/Models/SystemSetting.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("setting.{$key}", 3600, function () use ($key) {
            return self::where('key', $key)->first();
        });

        if (!$setting) {
            return $default;
        }

        return self::castValue($setting->value, $setting->type);
    }

    /**
     * Set a setting value.
     */
    public static function set(string $key, mixed $value, string $type = 'string'): void
    {
        $storedValue = is_array($value) ? json_encode($value) : (string) $value;

        self::updateOrCreate(
            ['key' => $key],
            ['value' => $storedValue, 'type' => $type]
        );

        Cache::forget("setting.{$key}");
    }

    /**
     * Get all settings grouped.
     */
    public static function getAllGrouped(): array
    {
        return self::all()->groupBy('group')->toArray();
    }

    /**
     * Cast value based on type.
     */
    private static function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'int', 'integer' => (int) $value,
            'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json', 'array' => json_decode($value, true) ?? [],
            default => $value,
        };
    }

    /**
     * Check if maintenance mode is enabled.
     */
    public static function isMaintenanceMode(): bool
    {
        return self::get('maintenance_mode', false);
    }

    /**
     * Check if emails are enabled.
     */
    public static function isEmailEnabled(): bool
    {
        return self::get('email_enabled', true);
    }

    /**
     * Get session timeout in minutes.
     */
    public static function getSessionTimeout(): int
    {
        return self::get('session_timeout', 30);
    }
}
```

---

## Task 4.4.3: Create Settings Seeder

```bash
php artisan make:seeder SystemSettingSeeder
```

**File:** `database/seeders/SystemSettingSeeder.php`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemSetting;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Session & Security
            [
                'key' => 'session_timeout',
                'value' => '30',
                'type' => 'int',
                'group' => 'security',
                'description' => 'Auto-logout after this period of inactivity (minutes)',
            ],
            [
                'key' => 'password_reset_expiry',
                'value' => '30',
                'type' => 'int',
                'group' => 'security',
                'description' => 'Password reset link validity (minutes)',
            ],
            [
                'key' => 'login_attempt_limit',
                'value' => '5',
                'type' => 'int',
                'group' => 'security',
                'description' => 'Maximum failed login attempts before lockout',
            ],
            [
                'key' => 'lockout_duration',
                'value' => '15',
                'type' => 'int',
                'group' => 'security',
                'description' => 'Account lockout duration (minutes)',
            ],

            // Notifications
            [
                'key' => 'email_enabled',
                'value' => 'true',
                'type' => 'bool',
                'group' => 'notifications',
                'description' => 'Master toggle for all email notifications',
            ],
            [
                'key' => 'notify_welcome_email',
                'value' => 'true',
                'type' => 'bool',
                'group' => 'notifications',
                'description' => 'Send welcome email to new users',
            ],
            [
                'key' => 'notify_password_reset',
                'value' => 'true',
                'type' => 'bool',
                'group' => 'notifications',
                'description' => 'Send password reset emails',
            ],
            [
                'key' => 'notify_booking_confirmed',
                'value' => 'true',
                'type' => 'bool',
                'group' => 'notifications',
                'description' => 'Send booking confirmation emails',
            ],
            [
                'key' => 'notify_booking_cancelled',
                'value' => 'true',
                'type' => 'bool',
                'group' => 'notifications',
                'description' => 'Send booking cancellation emails',
            ],
            [
                'key' => 'notify_booking_reminder',
                'value' => 'true',
                'type' => 'bool',
                'group' => 'notifications',
                'description' => 'Send booking reminders (24h before)',
            ],
            [
                'key' => 'notify_room_status_changed',
                'value' => 'true',
                'type' => 'bool',
                'group' => 'notifications',
                'description' => 'Notify users when room status changes',
            ],

            // Maintenance
            [
                'key' => 'maintenance_mode',
                'value' => 'false',
                'type' => 'bool',
                'group' => 'maintenance',
                'description' => 'Enable system maintenance mode',
            ],
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('System settings seeded.');
    }
}
```

Update `DatabaseSeeder.php`:

```php
$this->call([
    // ... existing seeders
    SystemSettingSeeder::class,
]);
```

---

## Task 4.4.4: Create SettingsController

```bash
php artisan make:controller Admin/SettingsController
```

**File:** `app/Http/Controllers/Admin/SettingsController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\AuditService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Display settings page.
     */
    public function index()
    {
        $settings = SystemSetting::all()->keyBy('key');

        // Password policy (read-only, hardcoded)
        $passwordPolicy = [
            'min_length' => 8,
            'require_letters' => true,
            'require_numbers' => true,
            'require_symbols' => true,
        ];

        // Booking rules (read-only, hardcoded)
        $bookingRules = [
            'operating_hours_start' => '08:00',
            'operating_hours_end' => '18:00',
            'time_increments' => 30,
            'min_duration' => 30,
            'max_duration' => 480, // 8 hours in minutes
            'max_advance_days' => 'Unlimited',
            'max_recurring_period' => '1 year',
        ];

        return view('admin.settings.index', compact('settings', 'passwordPolicy', 'bookingRules'));
    }

    /**
     * Update settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            // Security settings
            'session_timeout' => 'required|integer|min:15|max:120',
            'password_reset_expiry' => 'required|integer|min:5|max:60',
            'login_attempt_limit' => 'required|integer|min:3|max:10',
            'lockout_duration' => 'required|integer|min:5|max:60',

            // Notification toggles
            'email_enabled' => 'boolean',
            'notify_welcome_email' => 'boolean',
            'notify_password_reset' => 'boolean',
            'notify_booking_confirmed' => 'boolean',
            'notify_booking_cancelled' => 'boolean',
            'notify_booking_reminder' => 'boolean',
            'notify_room_status_changed' => 'boolean',

            // Maintenance
            'maintenance_mode' => 'boolean',
        ]);

        $changes = [];

        // Process integer settings
        foreach (['session_timeout', 'password_reset_expiry', 'login_attempt_limit', 'lockout_duration'] as $key) {
            $oldValue = SystemSetting::get($key);
            $newValue = (int) $validated[$key];
            
            if ($oldValue !== $newValue) {
                $changes[$key] = ['from' => $oldValue, 'to' => $newValue];
                SystemSetting::set($key, $newValue, 'int');
            }
        }

        // Process boolean settings
        $boolSettings = [
            'email_enabled',
            'notify_welcome_email',
            'notify_password_reset',
            'notify_booking_confirmed',
            'notify_booking_cancelled',
            'notify_booking_reminder',
            'notify_room_status_changed',
            'maintenance_mode',
        ];

        foreach ($boolSettings as $key) {
            $oldValue = SystemSetting::get($key, false);
            $newValue = $request->boolean($key);
            
            if ($oldValue !== $newValue) {
                $changes[$key] = ['from' => $oldValue, 'to' => $newValue];
                SystemSetting::set($key, $newValue ? 'true' : 'false', 'bool');
            }
        }

        // Log changes
        if (!empty($changes)) {
            // Special logging for maintenance mode
            if (isset($changes['maintenance_mode'])) {
                AuditService::log(
                    AuditService::EVENT_MAINTENANCE_MODE_TOGGLED,
                    null,
                    null,
                    ['enabled' => $request->boolean('maintenance_mode')]
                );
            }

            AuditService::log(
                AuditService::EVENT_SETTINGS_UPDATED,
                null,
                null,
                $changes
            );
        }

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Settings updated successfully.');
    }

    /**
     * Reset settings to defaults.
     */
    public function reset(Request $request)
    {
        $group = $request->input('group', 'all');

        // Re-run seeder for the group
        if ($group === 'all' || $group === 'security') {
            SystemSetting::set('session_timeout', 30, 'int');
            SystemSetting::set('password_reset_expiry', 30, 'int');
            SystemSetting::set('login_attempt_limit', 5, 'int');
            SystemSetting::set('lockout_duration', 15, 'int');
        }

        if ($group === 'all' || $group === 'notifications') {
            SystemSetting::set('email_enabled', 'true', 'bool');
            SystemSetting::set('notify_welcome_email', 'true', 'bool');
            SystemSetting::set('notify_password_reset', 'true', 'bool');
            SystemSetting::set('notify_booking_confirmed', 'true', 'bool');
            SystemSetting::set('notify_booking_cancelled', 'true', 'bool');
            SystemSetting::set('notify_booking_reminder', 'true', 'bool');
            SystemSetting::set('notify_room_status_changed', 'true', 'bool');
        }

        if ($group === 'all' || $group === 'maintenance') {
            SystemSetting::set('maintenance_mode', 'false', 'bool');
        }

        AuditService::log(
            AuditService::EVENT_SETTINGS_UPDATED,
            null,
            null,
            ['action' => 'reset_to_defaults', 'group' => $group]
        );

        return redirect()
            ->route('admin.settings.index')
            ->with('success', "Settings reset to defaults ({$group}).");
    }
}
```

---

## Task 4.4.5: Add Settings Audit Events

**File:** `app/Services/AuditService.php`

Add these constants:

```php
// Settings Events
public const EVENT_SETTINGS_UPDATED = 'settings.updated';
public const EVENT_MAINTENANCE_MODE_TOGGLED = 'settings.maintenance_mode_toggled';
```

---

## Task 4.4.6: Create Maintenance Mode Middleware

```bash
php artisan make:middleware CheckMaintenanceMode
```

**File:** `app/Http/Middleware/CheckMaintenanceMode.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\SystemSetting;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check if maintenance mode is enabled
        if (SystemSetting::isMaintenanceMode()) {
            // Allow admins and sysadmins through
            if (auth()->check() && 
                in_array(auth()->user()->role, ['administrator', 'director', 'system_admin'])) {
                return $next($request);
            }

            // Block regular users
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'System is currently under maintenance. Please try again later.'
                ], 503);
            }

            return response()->view('errors.maintenance', [], 503);
        }

        return $next($request);
    }
}
```

**Register in** `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        // ... existing middleware
        'maintenance.custom' => \App\Http\Middleware\CheckMaintenanceMode::class,
    ]);
})
```

---

## Task 4.4.7: Create Maintenance Error Page

**File:** `resources/views/errors/maintenance.blade.php`

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Maintenance - MRBS</title>
    <link href="{{ asset('assets/vendor/css/core.css') }}" rel="stylesheet">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .maintenance-card {
            background: white;
            border-radius: 16px;
            padding: 3rem;
            text-align: center;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .maintenance-icon {
            font-size: 5rem;
            color: #667eea;
            margin-bottom: 1.5rem;
        }
        h1 {
            color: #333;
            margin-bottom: 1rem;
        }
        p {
            color: #666;
            margin-bottom: 2rem;
        }
        .btn-retry {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 32px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-retry:hover {
            opacity: 0.9;
            color: white;
        }
    </style>
</head>
<body>
    <div class="maintenance-card">
        <div class="maintenance-icon">
            <i class='bx bx-wrench'></i>
        </div>
        <h1>System Under Maintenance</h1>
        <p>
            We're currently performing scheduled maintenance to improve your experience. 
            Please check back shortly.
        </p>
        <a href="{{ url('/') }}" class="btn-retry">
            <i class='bx bx-refresh'></i> Try Again
        </a>
    </div>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</body>
</html>
```

---

## Task 4.4.8: Define Settings Routes

**File:** `routes/web.php`

Add to SysAdmin-only routes:

```php
// System Settings (SysAdmin only)
Route::middleware(['auth', 'check.active', 'check.role:system_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('settings', [Admin\SettingsController::class, 'index'])->name('settings.index');
    Route::put('settings', [Admin\SettingsController::class, 'update'])->name('settings.update');
    Route::post('settings/reset', [Admin\SettingsController::class, 'reset'])->name('settings.reset');
});
```

Apply maintenance middleware to authenticated routes:

```php
Route::middleware(['auth', 'check.active', 'maintenance.custom'])->group(function () {
    // ... existing authenticated routes
});
```

---

## Task 4.4.9: Create Settings View

**File:** `resources/views/admin/settings/index.blade.php`

Convert from mockup: `mrbs-mock-up/pages/settings-system.html`

```blade
@extends('layouts.app')

@section('title', 'System Configuration')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">
            <i class='bx bx-cog me-2'></i>System Configuration
        </h4>
    </div>

    <form action="{{ route('admin.settings.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row">
            <!-- Left Column -->
            <div class="col-md-6">
                <!-- Session & Security Settings -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class='bx bx-shield-quarter me-2'></i>Session & Security
                        </h6>
                        <button type="button" class="btn btn-sm btn-outline-secondary" 
                                onclick="if(confirm('Reset security settings to defaults?')) document.getElementById('resetSecurityForm').submit()">
                            Reset to Defaults
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Session Timeout (minutes)</label>
                            <input type="number" name="session_timeout" class="form-control" 
                                   value="{{ $settings['session_timeout']->value ?? 30 }}"
                                   min="15" max="120" required>
                            <div class="form-text">Auto-logout after this period of inactivity (15-120 min)</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password Reset Token Expiry (minutes)</label>
                            <input type="number" name="password_reset_expiry" class="form-control" 
                                   value="{{ $settings['password_reset_expiry']->value ?? 30 }}"
                                   min="5" max="60" required>
                            <div class="form-text">How long password reset links remain valid (5-60 min)</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Login Attempt Limit</label>
                            <input type="number" name="login_attempt_limit" class="form-control" 
                                   value="{{ $settings['login_attempt_limit']->value ?? 5 }}"
                                   min="3" max="10" required>
                            <div class="form-text">Maximum failed attempts before temporary lockout (3-10)</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Lockout Duration (minutes)</label>
                            <input type="number" name="lockout_duration" class="form-control" 
                                   value="{{ $settings['lockout_duration']->value ?? 15 }}"
                                   min="5" max="60" required>
                            <div class="form-text">Account lockout duration after exceeding attempts (5-60 min)</div>
                        </div>
                    </div>
                </div>

                <!-- Password Policy (Read-Only) -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class='bx bx-key me-2'></i>Password Policy
                            <span class="badge bg-secondary ms-2">Read-Only</span>
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-3">
                            <i class='bx bx-info-circle me-1'></i>
                            These requirements are set by organizational policy and cannot be changed.
                        </div>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class='bx bx-check text-success me-2'></i>
                                Minimum {{ $passwordPolicy['min_length'] }} characters
                            </li>
                            <li class="mb-2">
                                <i class='bx bx-check text-success me-2'></i>
                                Must contain letters
                            </li>
                            <li class="mb-2">
                                <i class='bx bx-check text-success me-2'></i>
                                Must contain numbers
                            </li>
                            <li>
                                <i class='bx bx-check text-success me-2'></i>
                                Must contain symbols
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Booking Rules (Read-Only) -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class='bx bx-calendar-check me-2'></i>Booking Rules
                            <span class="badge bg-secondary ms-2">Read-Only</span>
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-3">
                            <i class='bx bx-info-circle me-1'></i>
                            These rules are set by organizational policy and cannot be changed.
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <small class="text-muted d-block">Operating Hours</small>
                                <strong>{{ $bookingRules['operating_hours_start'] }} - {{ $bookingRules['operating_hours_end'] }}</strong>
                            </div>
                            <div class="col-6 mb-3">
                                <small class="text-muted d-block">Time Increments</small>
                                <strong>{{ $bookingRules['time_increments'] }} minutes</strong>
                            </div>
                            <div class="col-6 mb-3">
                                <small class="text-muted d-block">Minimum Duration</small>
                                <strong>{{ $bookingRules['min_duration'] }} minutes</strong>
                            </div>
                            <div class="col-6 mb-3">
                                <small class="text-muted d-block">Maximum Duration</small>
                                <strong>{{ $bookingRules['max_duration'] / 60 }} hours</strong>
                            </div>
                            <div class="col-6 mb-3">
                                <small class="text-muted d-block">Advance Booking</small>
                                <strong>{{ $bookingRules['max_advance_days'] }}</strong>
                            </div>
                            <div class="col-6 mb-3">
                                <small class="text-muted d-block">Max Recurring Period</small>
                                <strong>{{ $bookingRules['max_recurring_period'] }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-md-6">
                <!-- Notification Settings -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class='bx bx-bell me-2'></i>Notification Settings
                        </h6>
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                onclick="if(confirm('Reset notification settings to defaults?')) { document.getElementById('resetGroup').value='notifications'; document.getElementById('resetForm').submit(); }">
                            Reset to Defaults
                        </button>
                    </div>
                    <div class="card-body">
                        <!-- Master Toggle -->
                        <div class="mb-4 pb-3 border-bottom">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" 
                                       id="email_enabled" name="email_enabled" value="1"
                                       {{ ($settings['email_enabled']->value ?? 'true') === 'true' ? 'checked' : '' }}
                                       onchange="toggleNotificationSettings(this.checked)">
                                <label class="form-check-label fw-bold" for="email_enabled">
                                    Enable Email Notifications
                                </label>
                            </div>
                            <div class="form-text">Master toggle for all email notifications</div>
                        </div>

                        <!-- Individual Toggles -->
                        <div id="notificationToggles">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input notification-toggle" type="checkbox" 
                                       id="notify_welcome_email" name="notify_welcome_email" value="1"
                                       {{ ($settings['notify_welcome_email']->value ?? 'true') === 'true' ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify_welcome_email">
                                    Account Creation (Welcome Email)
                                </label>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input notification-toggle" type="checkbox" 
                                       id="notify_password_reset" name="notify_password_reset" value="1"
                                       {{ ($settings['notify_password_reset']->value ?? 'true') === 'true' ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify_password_reset">
                                    Password Reset
                                </label>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input notification-toggle" type="checkbox" 
                                       id="notify_booking_confirmed" name="notify_booking_confirmed" value="1"
                                       {{ ($settings['notify_booking_confirmed']->value ?? 'true') === 'true' ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify_booking_confirmed">
                                    Booking Confirmed
                                </label>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input notification-toggle" type="checkbox" 
                                       id="notify_booking_cancelled" name="notify_booking_cancelled" value="1"
                                       {{ ($settings['notify_booking_cancelled']->value ?? 'true') === 'true' ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify_booking_cancelled">
                                    Booking Cancelled
                                </label>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input notification-toggle" type="checkbox" 
                                       id="notify_booking_reminder" name="notify_booking_reminder" value="1"
                                       {{ ($settings['notify_booking_reminder']->value ?? 'true') === 'true' ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify_booking_reminder">
                                    Booking Reminder (24h before)
                                </label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input notification-toggle" type="checkbox" 
                                       id="notify_room_status_changed" name="notify_room_status_changed" value="1"
                                       {{ ($settings['notify_room_status_changed']->value ?? 'true') === 'true' ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify_room_status_changed">
                                    Room Status Changed
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Maintenance Mode -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class='bx bx-wrench me-2'></i>System Maintenance
                        </h6>
                    </div>
                    <div class="card-body">
                        @php
                            $isMaintenanceOn = ($settings['maintenance_mode']->value ?? 'false') === 'true';
                        @endphp
                        
                        @if($isMaintenanceOn)
                        <div class="alert alert-warning mb-3">
                            <i class='bx bx-error-circle me-1'></i>
                            <strong>Maintenance mode is currently ACTIVE.</strong> 
                            Regular users cannot access the system.
                        </div>
                        @endif

                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" 
                                   id="maintenance_mode" name="maintenance_mode" value="1"
                                   {{ $isMaintenanceOn ? 'checked' : '' }}
                                   onchange="confirmMaintenanceToggle(this)">
                            <label class="form-check-label fw-bold" for="maintenance_mode">
                                Enable Maintenance Mode
                            </label>
                        </div>
                        <div class="form-text mt-2">
                            When enabled:
                            <ul class="mb-0 mt-1">
                                <li>Regular users cannot log in</li>
                                <li>A "System Under Maintenance" page is shown</li>
                                <li>Administrators and System Admins can still access</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="card">
            <div class="card-body d-flex justify-content-between align-items-center">
                <span class="text-muted">
                    <i class='bx bx-info-circle me-1'></i>
                    Changes are applied immediately after saving.
                </span>
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class='bx bx-save me-1'></i> Save Configuration
                </button>
            </div>
        </div>
    </form>

    <!-- Hidden Reset Forms -->
    <form id="resetSecurityForm" action="{{ route('admin.settings.reset') }}" method="POST" class="d-none">
        @csrf
        <input type="hidden" name="group" value="security">
    </form>
    <form id="resetForm" action="{{ route('admin.settings.reset') }}" method="POST" class="d-none">
        @csrf
        <input type="hidden" id="resetGroup" name="group" value="notifications">
    </form>
</div>

@push('scripts')
<script>
function toggleNotificationSettings(enabled) {
    const toggles = document.querySelectorAll('.notification-toggle');
    toggles.forEach(toggle => {
        toggle.disabled = !enabled;
        if (!enabled) {
            toggle.parentElement.classList.add('text-muted');
        } else {
            toggle.parentElement.classList.remove('text-muted');
        }
    });
}

function confirmMaintenanceToggle(checkbox) {
    if (checkbox.checked) {
        if (!confirm('Enable maintenance mode? Regular users will be blocked from accessing the system.')) {
            checkbox.checked = false;
        }
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const masterToggle = document.getElementById('email_enabled');
    toggleNotificationSettings(masterToggle.checked);
});
</script>
@endpush
@endsection
```

---

## Task 4.4.10: Update Sidebar Navigation

**File:** `resources/views/layouts/partials/sidebar.blade.php`

Add System Settings menu item (for SysAdmin only):

```blade
@if(auth()->user()->canConfigureSystem())
<li class="menu-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
    <a href="{{ route('admin.settings.index') }}" class="menu-link">
        <i class="menu-icon tf-icons bx bx-cog"></i>
        <div>System Settings</div>
    </a>
</li>
@endif
```

---

## Testing Requirements

> **Note:** When creating test users with the factory, use `->passwordChanged()` to set `must_change_password` to `false`. Otherwise, the `MustChangePassword` middleware will redirect users causing tests to receive 302 responses instead of 200.

**File:** `tests/Feature/SystemConfigurationTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SystemConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected User $sysAdmin;
    protected User $director;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\SystemSettingSeeder::class);
        
        $this->sysAdmin = User::factory()->passwordChanged()->create(['role' => 'system_admin']);
        $this->director = User::factory()->passwordChanged()->create(['role' => 'director']);
        $this->regularUser = User::factory()->passwordChanged()->create(['role' => 'regular_user']);
    }

    public function test_sysadmin_can_access_settings(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.settings.index'));
        $response->assertStatus(200);
        $response->assertSee('System Configuration');
    }

    public function test_director_cannot_access_settings(): void
    {
        $response = $this->actingAs($this->director)->get(route('admin.settings.index'));
        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_access_settings(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.settings.index'));
        $response->assertStatus(403);
    }

    public function test_can_update_session_timeout(): void
    {
        $response = $this->actingAs($this->sysAdmin)->put(route('admin.settings.update'), [
            'session_timeout' => 60,
            'password_reset_expiry' => 30,
            'login_attempt_limit' => 5,
            'lockout_duration' => 15,
        ]);

        $response->assertRedirect(route('admin.settings.index'));
        $this->assertEquals(60, SystemSetting::get('session_timeout'));
    }

    public function test_validates_session_timeout_range(): void
    {
        $response = $this->actingAs($this->sysAdmin)->put(route('admin.settings.update'), [
            'session_timeout' => 200, // Above max of 120
            'password_reset_expiry' => 30,
            'login_attempt_limit' => 5,
            'lockout_duration' => 15,
        ]);

        $response->assertSessionHasErrors('session_timeout');
    }

    public function test_can_toggle_email_notifications(): void
    {
        $response = $this->actingAs($this->sysAdmin)->put(route('admin.settings.update'), [
            'session_timeout' => 30,
            'password_reset_expiry' => 30,
            'login_attempt_limit' => 5,
            'lockout_duration' => 15,
            'email_enabled' => false,
        ]);

        $response->assertRedirect();
        $this->assertFalse(SystemSetting::isEmailEnabled());
    }

    public function test_can_enable_maintenance_mode(): void
    {
        $response = $this->actingAs($this->sysAdmin)->put(route('admin.settings.update'), [
            'session_timeout' => 30,
            'password_reset_expiry' => 30,
            'login_attempt_limit' => 5,
            'lockout_duration' => 15,
            'maintenance_mode' => true,
        ]);

        $response->assertRedirect();
        $this->assertTrue(SystemSetting::isMaintenanceMode());
    }

    public function test_maintenance_mode_blocks_regular_users(): void
    {
        SystemSetting::set('maintenance_mode', 'true', 'bool');

        $response = $this->actingAs($this->regularUser)->get(route('dashboard'));
        $response->assertStatus(503);
    }

    public function test_maintenance_mode_allows_admins(): void
    {
        SystemSetting::set('maintenance_mode', 'true', 'bool');

        $response = $this->actingAs($this->sysAdmin)->get(route('admin.settings.index'));
        $response->assertStatus(200);
    }

    public function test_can_reset_settings_to_defaults(): void
    {
        SystemSetting::set('session_timeout', 120, 'int');

        $response = $this->actingAs($this->sysAdmin)->post(route('admin.settings.reset'), [
            'group' => 'security',
        ]);

        $response->assertRedirect();
        $this->assertEquals(30, SystemSetting::get('session_timeout'));
    }

    public function test_settings_changes_are_logged(): void
    {
        $this->actingAs($this->sysAdmin)->put(route('admin.settings.update'), [
            'session_timeout' => 45,
            'password_reset_expiry' => 30,
            'login_attempt_limit' => 5,
            'lockout_duration' => 15,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'settings.updated',
            'actor_id' => $this->sysAdmin->id,
        ]);
    }

    public function test_system_setting_model_get_and_set(): void
    {
        SystemSetting::set('test_key', 'test_value', 'string');
        $this->assertEquals('test_value', SystemSetting::get('test_key'));

        SystemSetting::set('test_int', 42, 'int');
        $this->assertSame(42, SystemSetting::get('test_int'));

        SystemSetting::set('test_bool', 'true', 'bool');
        $this->assertTrue(SystemSetting::get('test_bool'));
    }
}
```

---

## Acceptance Criteria

- [x] Settings page displays all configurable settings
- [x] Session & Security section: session timeout, password reset expiry, login limit, lockout duration
- [x] Password Policy section is read-only (displays hardcoded values)
- [x] Booking Rules section is read-only (displays hardcoded values)
- [x] Notification toggles work (master toggle + individual)
- [x] Master notification toggle disables individual toggles when off
- [x] Maintenance mode toggle requires confirmation
- [x] Maintenance mode blocks regular users (shows error page)
- [x] Maintenance mode allows admins through
- [x] Reset to Defaults button works per section
- [x] Settings changes are logged to audit trail
- [x] SystemSetting model caches values
- [x] Only SysAdmin can access settings
- [x] All tests pass: `php artisan test --filter=SystemConfigurationTest`

---

**Next:** [Step 4.5 - Notification System](./step-4.5-notification-system.md)
