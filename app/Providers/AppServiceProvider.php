<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerGates();
        $this->registerViewComposers();

        if (app()->environment('local')) {
            URL::forceScheme('https');
        }

        $this->configureMail();
    }

    /**
     * Configure mail settings from database.
     */
    protected function configureMail(): void
    {
        try {
            // Use try-catch or Schema check to avoid issues during initial migrations
            // SystemSetting::isEmailEnabled() checks the settings table
            if (!\Illuminate\Support\Facades\Schema::hasTable('system_settings')) {
                return;
            }

            if (\App\Models\SystemSetting::isEmailEnabled()) {
                $host = \App\Models\SystemSetting::get('mail_host');

                // Only override if we have at least a host configured
                if ($host) {
                    $config = [
                        'transport' => \App\Models\SystemSetting::get('mail_mailer') ?? 'smtp',
                        'host' => $host,
                        'port' => \App\Models\SystemSetting::get('mail_port'),
                        'encryption' => \App\Models\SystemSetting::get('mail_encryption'),
                        'username' => \App\Models\SystemSetting::get('mail_username'),
                        'password' => \App\Models\SystemSetting::get('mail_password'),
                        'timeout' => null,
                        'local_domain' => env('MAIL_EHLO_DOMAIN'),
                    ];

                    config(['mail.mailers.smtp' => array_merge(config('mail.mailers.smtp', []), $config)]);

                    // Also override from address
                    $fromAddress = \App\Models\SystemSetting::get('mail_from_address');
                    if ($fromAddress) {
                        config(['mail.from.address' => $fromAddress]);
                        // Assuming name uses app name if not specified, or we could add a setting for it
                        // config(['mail.from.name' => ...]); 
                    }

                    // Force the default mailer to the configured one (usually smtp)
                    config(['mail.default' => \App\Models\SystemSetting::get('mail_mailer') ?? 'smtp']);
                }
            }
        } catch (\Exception $e) {
            // specific table not found or other errors ignored to not break boot
        }
    }

    /**
     * Register view composers for shared data.
     * Note: Pending approvals view composer removed as part of auto-approval refactor (Step 1.8)
     */
    protected function registerViewComposers(): void
    {
        // No view composers needed at this time
        // Future view composers can be added here as needed
    }

    /**
     * Register authorization Gates for the application.
     */
    protected function registerGates(): void
    {
        // Booking management (Admin, Director)
        Gate::define('manage-bookings', fn($user) => $user->canManageBookings());

        // Room management (Admin, Director)
        Gate::define('manage-rooms', fn($user) => $user->canManageRooms());

        // User management (Director, SysAdmin)
        Gate::define('manage-users', fn($user) => $user->canManageUsers());

        // Audit access (Director, SysAdmin)
        Gate::define('access-audit', fn($user) => $user->canAccessAudit());

        // Reports access (Admin, Director, SysAdmin)
        Gate::define('access-reports', fn($user) => $user->canAccessReports());

        // System config (SysAdmin only)
        Gate::define('configure-system', fn($user) => $user->canConfigureSystem());
    }
}
