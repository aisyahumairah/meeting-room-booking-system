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

            // Email Server Settings
            'mail_mailer' => 'nullable|string',
            'mail_host' => 'nullable|string',
            'mail_port' => 'nullable|integer',
            'mail_username' => 'nullable|string',
            'mail_password' => 'nullable|string',
            'mail_encryption' => 'nullable|string',
            'mail_from_address' => 'nullable|email',
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
                // For email_enabled, we default to 'true' if not present in DB, so be careful?
                // Actually SystemSetting::get defaults to null, but in controller we might want default true
                // We'll stick to string 'true'/'false' storage as established.
                SystemSetting::set($key, $newValue ? 'true' : 'false', 'bool');
            }
        }

        // Process Email Server Settings
        $emailSettings = [
            'mail_mailer',
            'mail_host',
            'mail_port',
            'mail_username',
            'mail_encryption',
            'mail_from_address'
        ];

        foreach ($emailSettings as $key) {
            if ($request->has($key)) {
                $oldValue = SystemSetting::get($key);
                $newValue = $request->input($key);

                // Allow saving empty values to clear them, or maybe we want to keep them? 
                // Using input() returns null if not present, but we checked has().
                // If the user clears the input, we expect it to be null or empty string.

                if ($oldValue !== $newValue) {
                    $changes[$key] = ['from' => $oldValue, 'to' => $newValue];
                    SystemSetting::set($key, $newValue, 'string');
                }
            }
        }

        // Password defaults to empty in form if unchanged, so only update if filled
        if ($request->filled('mail_password')) {
            SystemSetting::set('mail_password', $request->input('mail_password'), 'string');
            $changes['mail_password'] = ['from' => '*****', 'to' => '*****'];
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
