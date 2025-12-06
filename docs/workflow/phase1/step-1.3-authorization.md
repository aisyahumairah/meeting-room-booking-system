# Step 1.3: Authorization & Middleware

**Priority:** CRITICAL | **Ref:** §3.5, §3.6 | **Dependencies:** Step 1.1

---

## Objective
Create middleware for role checking, active status, and maintenance mode. Define Gates for permissions.

---

## Task 1.3.1: Create CheckRole Middleware

**Command:** `php artisan make:middleware CheckRole`

**File:** `app/Http/Middleware/CheckRole.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        if (!in_array($request->user()->role, $roles)) {
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}
```

**Usage in routes:**
```php
Route::middleware('role:administrator,director')->group(function () {
    // Admin routes
});
```

---

## Task 1.3.2: Create CheckActive Middleware

**Command:** `php artisan make:middleware CheckActive`

**File:** `app/Http/Middleware/CheckActive.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckActive
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()->status === 'inactive') {
            Auth::logout();
            $request->session()->invalidate();
            
            return redirect()->route('login')
                ->withErrors(['email' => 'Your account has been deactivated.']);
        }

        return $next($request);
    }
}
```

---

## Task 1.3.3: Create MustChangePassword Middleware

**Command:** `php artisan make:middleware MustChangePassword`

**File:** `app/Http/Middleware/MustChangePassword.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class MustChangePassword
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && $request->user()->must_change_password) {
            if (!$request->routeIs('password.change') && !$request->routeIs('logout')) {
                return redirect()->route('password.change')
                    ->with('warning', 'You must change your password before continuing.');
            }
        }

        return $next($request);
    }
}
```

---

## Task 1.3.4: Register Middleware

**File:** `bootstrap/app.php` (Laravel 12)

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => \App\Http\Middleware\CheckRole::class,
        'active' => \App\Http\Middleware\CheckActive::class,
        'must.change.password' => \App\Http\Middleware\MustChangePassword::class,
    ]);
    
    // Apply to all authenticated routes
    $middleware->appendToGroup('web', [
        \App\Http\Middleware\CheckActive::class,
        \App\Http\Middleware\MustChangePassword::class,
    ]);
})
```

---

## Task 1.3.5: Define Gates in AuthServiceProvider

**File:** `app/Providers/AppServiceProvider.php` (or create AuthServiceProvider)

```php
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    // Booking management (Admin, Director)
    Gate::define('manage-bookings', fn ($user) => $user->canManageBookings());
    
    // Room management (Admin, Director)
    Gate::define('manage-rooms', fn ($user) => $user->canManageRooms());
    
    // User management (Director, SysAdmin)
    Gate::define('manage-users', fn ($user) => $user->canManageUsers());
    
    // Audit access (Director, SysAdmin)
    Gate::define('access-audit', fn ($user) => $user->canAccessAudit());
    
    // Reports access (Admin, Director, SysAdmin)
    Gate::define('access-reports', fn ($user) => $user->canAccessReports());
    
    // System config (SysAdmin only)
    Gate::define('configure-system', fn ($user) => $user->canConfigureSystem());
}
```

**Usage in controllers:**
```php
Gate::authorize('manage-bookings');
// or
$this->authorize('manage-rooms');
```

**Usage in Blade:**
```php
@can('manage-bookings')
    <a href="/admin/approvals">Approval Queue</a>
@endcan
```

---

## Task 1.3.6: Create Route Groups with Middleware

**File:** `routes/web.php`

```php
// All authenticated users
Route::middleware(['auth', 'active', 'must.change.password'])->group(function () {
    // Dashboard, profile, own bookings
});

// Admin/Director only
Route::middleware(['auth', 'role:administrator,director'])->prefix('admin')->group(function () {
    // Room management, booking approvals, reports
});

// Director/SysAdmin only
Route::middleware(['auth', 'role:director,system_admin'])->prefix('admin')->group(function () {
    // User management, audit logs
});

// SysAdmin only
Route::middleware(['auth', 'role:system_admin'])->prefix('admin')->group(function () {
    // System settings
});
```

---

## Testing Requirements

**File:** `tests/Feature/Auth/AuthorizationTest.php`

Test cases:
- Regular user cannot access admin routes
- Administrator can access booking management routes
- Administrator cannot access user management routes
- Director can access all admin routes except system config
- System admin can access system config
- Inactive user is logged out automatically

```bash
php artisan test --filter=AuthorizationTest
```

---

## Acceptance Criteria
- [ ] CheckRole middleware blocks unauthorized roles
- [ ] CheckActive middleware logs out inactive users
- [ ] MustChangePassword redirects when required
- [ ] All middleware registered in bootstrap/app.php
- [ ] Gates defined for all 6 permission types
- [ ] Route groups use correct middleware
- [ ] All tests pass

---

**Next:** [Step 1.4 - Global Layout](./step-1.4-global-layout.md)
