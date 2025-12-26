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
