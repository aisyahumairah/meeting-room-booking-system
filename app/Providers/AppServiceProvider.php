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
