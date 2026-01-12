# Step 5.3: Error Handling

**Priority:** HIGH | **Ref:** §8.3.2 | **Dependencies:** None  
**Status:** COMPLETED

---

## Objective

Implement custom error pages matching the Sneat template aesthetic and ensure graceful error handling throughout the application with user-friendly messaging.

---

## Task 5.3.1: Create Custom 403 Forbidden Page

**File:** `resources/views/errors/403.blade.php`

```blade
@extends('layouts.guest')

@section('title', 'Access Denied')

@section('content')
<div class="container-xxl container-p-y">
    <div class="misc-wrapper text-center">
        <h2 class="mb-2 mx-2">Access Denied! 🔒</h2>
        <p class="mb-4 mx-2">
            You don't have permission to access this page.
            <br>
            If you believe this is a mistake, please contact your administrator.
        </p>
        
        <div class="d-flex justify-content-center gap-3 mb-4">
            <a href="{{ url()->previous() }}" class="btn btn-outline-primary">
                <i class="bx bx-arrow-back me-1"></i> Go Back
            </a>
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary">
                    <i class="bx bx-home me-1"></i> Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary">
                    <i class="bx bx-log-in me-1"></i> Login
                </a>
            @endauth
        </div>
        
        <div class="mt-3">
            <img src="{{ asset('assets/img/illustrations/page-misc-error-light.png') }}" 
                 alt="Access Denied" 
                 width="400"
                 class="img-fluid">
        </div>
        
        <div class="mt-4 text-muted">
            <small>Error Code: 403 | {{ now()->format('Y-m-d H:i:s') }}</small>
        </div>
    </div>
</div>
@endsection
```

---

## Task 5.3.2: Create Custom 404 Not Found Page

**File:** `resources/views/errors/404.blade.php`

```blade
@extends('layouts.guest')

@section('title', 'Page Not Found')

@section('content')
<div class="container-xxl container-p-y">
    <div class="misc-wrapper text-center">
        <h2 class="mb-2 mx-2">Page Not Found :(</h2>
        <p class="mb-4 mx-2">
            Oops! 😖 The page you're looking for doesn't exist or has been moved.
        </p>
        
        <div class="d-flex justify-content-center gap-3 mb-4">
            <a href="{{ url()->previous() }}" class="btn btn-outline-primary">
                <i class="bx bx-arrow-back me-1"></i> Go Back
            </a>
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary">
                    <i class="bx bx-home me-1"></i> Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary">
                    <i class="bx bx-home me-1"></i> Home
                </a>
            @endauth
        </div>
        
        <div class="mt-3">
            <img src="{{ asset('assets/img/illustrations/page-misc-error-light.png') }}" 
                 alt="Page Not Found" 
                 width="500"
                 class="img-fluid">
        </div>
        
        <div class="mt-4 text-muted">
            <small>Error Code: 404 | {{ now()->format('Y-m-d H:i:s') }}</small>
        </div>
    </div>
</div>
@endsection
```

---

## Task 5.3.3: Create Custom 500 Server Error Page

**File:** `resources/views/errors/500.blade.php`

```blade
@extends('layouts.guest')

@section('title', 'Server Error')

@section('content')
<div class="container-xxl container-p-y">
    <div class="misc-wrapper text-center">
        <h2 class="mb-2 mx-2">Something Went Wrong! 😵</h2>
        <p class="mb-4 mx-2">
            We're sorry, but something went wrong on our end.
            <br>
            Our team has been notified and is working to fix the issue.
        </p>
        
        <div class="alert alert-light d-inline-block mb-4">
            <strong>Reference ID:</strong> 
            <code>{{ session('error_id', Str::uuid()->toString()) }}</code>
            <br>
            <small class="text-muted">Please quote this ID when contacting support.</small>
        </div>
        
        <div class="d-flex justify-content-center gap-3 mb-4">
            <a href="{{ url('/') }}" class="btn btn-primary">
                <i class="bx bx-home me-1"></i> Return Home
            </a>
            <button type="button" class="btn btn-outline-primary" onclick="window.location.reload()">
                <i class="bx bx-refresh me-1"></i> Try Again
            </button>
        </div>
        
        <div class="mt-3">
            <img src="{{ asset('assets/img/illustrations/page-misc-error-light.png') }}" 
                 alt="Server Error" 
                 width="400"
                 class="img-fluid">
        </div>
        
        <div class="mt-4 text-muted">
            <small>Error Code: 500 | {{ now()->format('Y-m-d H:i:s') }}</small>
        </div>
    </div>
</div>
@endsection
```

---

## Task 5.3.4: Create Custom 503 Maintenance Page

**File:** `resources/views/errors/503.blade.php`

Also used by the maintenance mode feature from Phase 4.

```blade
@extends('layouts.guest')

@section('title', 'System Maintenance')

@section('content')
<div class="container-xxl container-p-y">
    <div class="misc-wrapper text-center">
        <h2 class="mb-2 mx-2">System Under Maintenance 🔧</h2>
        <p class="mb-4 mx-2">
            We're currently performing scheduled maintenance.
            <br>
            Please check back shortly. We apologize for any inconvenience.
        </p>
        
        <div class="alert alert-info d-inline-block mb-4">
            <i class="bx bx-info-circle me-2"></i>
            @if(isset($exception) && $exception->getMessage())
                {{ $exception->getMessage() }}
            @else
                The system will be back online soon.
            @endif
        </div>
        
        <div class="mt-3">
            <img src="{{ asset('assets/img/illustrations/page-misc-under-maintenance.png') }}" 
                 alt="Maintenance" 
                 width="400"
                 class="img-fluid">
        </div>
        
        <div class="mt-4">
            <button type="button" class="btn btn-primary" onclick="window.location.reload()">
                <i class="bx bx-refresh me-1"></i> Check Status
            </button>
        </div>
        
        <div class="mt-4 text-muted">
            <small>{{ now()->format('Y-m-d H:i:s') }}</small>
        </div>
    </div>
</div>
@endsection
```

---

## Task 5.3.5: Create Custom Maintenance Page (Alternative)

**File:** `resources/views/errors/maintenance.blade.php`

For the custom maintenance middleware (different from Laravel's 503):

```blade
@extends('layouts.guest')

@section('title', 'Maintenance Mode')

@section('content')
<div class="container-xxl container-p-y">
    <div class="misc-wrapper text-center">
        <h2 class="mb-2 mx-2">System Maintenance in Progress 🛠️</h2>
        <p class="mb-4 mx-2">
            The Meeting Room Booking System is currently undergoing maintenance.
            <br>
            Regular access will be restored shortly.
        </p>
        
        <div class="card d-inline-block mb-4">
            <div class="card-body">
                <p class="mb-2"><strong>For Administrators:</strong></p>
                <a href="{{ route('login') }}" class="btn btn-sm btn-outline-primary">
                    <i class="bx bx-log-in me-1"></i> Admin Login
                </a>
            </div>
        </div>
        
        <div class="mt-3">
            <img src="{{ asset('assets/img/illustrations/page-misc-under-maintenance.png') }}" 
                 alt="Maintenance" 
                 width="400"
                 class="img-fluid">
        </div>
        
        <div class="mt-4 text-muted">
            <small>We apologize for any inconvenience.</small>
        </div>
    </div>
</div>
@endsection
```

---

## Task 5.3.6: Create Guest Layout for Error Pages

If not already exists, create a minimal layout for error pages:

**File:** `resources/views/layouts/guest.blade.php`

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light-style">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'MRBS') - Meeting Room Booking System</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/boxicons.css') }}">
    
    <!-- Core CSS -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/theme-default.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}">
</head>

<body>
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner py-4">
                @yield('content')
            </div>
        </div>
    </div>

    <!-- Core JS -->
    <script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
</body>
</html>
```

---

## Task 5.3.7: Session Expired Modal

Create a session expired notification modal:

**File:** `resources/views/components/session-expired-modal.blade.php`

```blade
<div class="modal fade" id="sessionExpiredModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="bx bx-time-five me-2"></i>
                    Session Expired
                </h5>
            </div>
            <div class="modal-body text-center py-4">
                <i class="bx bx-lock-alt text-warning" style="font-size: 4rem;"></i>
                <h5 class="mt-3">Your session has expired</h5>
                <p class="text-muted">
                    For security reasons, your session has expired due to inactivity.
                    <br>
                    Please log in again to continue.
                </p>
            </div>
            <div class="modal-footer justify-content-center">
                <a href="{{ route('login') }}" class="btn btn-primary">
                    <i class="bx bx-log-in me-1"></i> Log In Again
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    // Show modal on 401/419 AJAX responses
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof $ !== 'undefined') {
            $(document).ajaxError(function(event, jqxhr) {
                if (jqxhr.status === 401 || jqxhr.status === 419) {
                    var modal = new bootstrap.Modal(document.getElementById('sessionExpiredModal'));
                    modal.show();
                }
            });
        }
    });
</script>
```

---

## Task 5.3.8: Toast Notification Component

Create reusable toast notifications for success/error messages:

**File:** `resources/views/components/toast.blade.php`

```blade
{{-- Success Toast --}}
@if(session('success'))
<div class="bs-toast toast toast-placement-ex m-2 fade bg-success top-0 end-0 show" 
     role="alert" 
     aria-live="assertive" 
     aria-atomic="true"
     data-bs-autohide="true"
     data-bs-delay="5000">
    <div class="toast-header">
        <i class="bx bx-check-circle me-2"></i>
        <span class="me-auto fw-semibold">Success</span>
        <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
    </div>
    <div class="toast-body">
        {{ session('success') }}
    </div>
</div>
@endif

{{-- Error Toast --}}
@if(session('error'))
<div class="bs-toast toast toast-placement-ex m-2 fade bg-danger top-0 end-0 show" 
     role="alert" 
     aria-live="assertive" 
     aria-atomic="true"
     data-bs-autohide="true"
     data-bs-delay="8000">
    <div class="toast-header">
        <i class="bx bx-error-circle me-2"></i>
        <span class="me-auto fw-semibold">Error</span>
        <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
    </div>
    <div class="toast-body">
        {{ session('error') }}
    </div>
</div>
@endif

{{-- Warning Toast --}}
@if(session('warning'))
<div class="bs-toast toast toast-placement-ex m-2 fade bg-warning top-0 end-0 show" 
     role="alert" 
     aria-live="assertive" 
     aria-atomic="true"
     data-bs-autohide="true"
     data-bs-delay="6000">
    <div class="toast-header">
        <i class="bx bx-error me-2"></i>
        <span class="me-auto fw-semibold">Warning</span>
        <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
    </div>
    <div class="toast-body">
        {{ session('warning') }}
    </div>
</div>
@endif

{{-- Info Toast --}}
@if(session('info'))
<div class="bs-toast toast toast-placement-ex m-2 fade bg-info top-0 end-0 show" 
     role="alert" 
     aria-live="assertive" 
     aria-atomic="true"
     data-bs-autohide="true"
     data-bs-delay="5000">
    <div class="toast-header">
        <i class="bx bx-info-circle me-2"></i>
        <span class="me-auto fw-semibold">Info</span>
        <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
    </div>
    <div class="toast-body">
        {{ session('info') }}
    </div>
</div>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var toastElList = document.querySelectorAll('.toast');
        toastElList.forEach(function(toastEl) {
            new bootstrap.Toast(toastEl).show();
        });
    });
</script>
```

---

## Task 5.3.9: Booking Conflict Modal

Create a modal for booking conflicts:

**File:** `resources/views/components/booking-conflict-modal.blade.php`

```blade
<div class="modal fade" id="bookingConflictModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bx bx-calendar-x me-2"></i>
                    Booking Conflict
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="bx bx-error-circle text-danger" style="font-size: 4rem;"></i>
                </div>
                <h5 class="text-center">This room is no longer available</h5>
                <p class="text-center text-muted">
                    Another booking was just confirmed for this time slot.
                </p>
                
                <div class="alert alert-light" id="conflictDetails">
                    <!-- Populated by JavaScript -->
                </div>
                
                <p class="text-center">
                    <strong>Suggestions:</strong>
                </p>
                <ul class="text-muted">
                    <li>Try a different time slot</li>
                    <li>Select a different room</li>
                    <li>Check the calendar for availability</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Close
                </button>
                <a href="{{ route('calendar') }}" class="btn btn-primary">
                    <i class="bx bx-calendar me-1"></i> View Calendar
                </a>
            </div>
        </div>
    </div>
</div>
```

---

## Task 5.3.10: Exception Handler Configuration

Update the exception handler to use custom error pages:

**File:** `app/Exceptions/Handler.php` or `bootstrap/app.php` (Laravel 11+)

```php
// In bootstrap/app.php (Laravel 11+)
->withExceptions(function (Exceptions $exceptions) {
    // Log all server errors with context
    $exceptions->report(function (Throwable $e) {
        if ($e instanceof \Illuminate\Database\QueryException || 
            $e instanceof \Exception && $e->getCode() >= 500) {
            // Log with additional context
            Log::error('Server Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth()->id(),
                'url' => request()->fullUrl(),
                'ip' => request()->ip(),
            ]);
        }
    });
    
    // Custom rendering for API/AJAX requests
    $exceptions->render(function (Throwable $e, Request $request) {
        if ($request->expectsJson()) {
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }
            
            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                return response()->json([
                    'message' => 'Session expired',
                ], 401);
            }
            
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return response()->json([
                    'message' => 'Resource not found',
                ], 404);
            }
            
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException) {
                return response()->json([
                    'message' => 'Access denied',
                ], 403);
            }
        }
        
        return null; // Let Laravel handle default rendering
    });
})
```

---

## Task 5.3.11: Error Logging Service

Create a service for consistent error logging:

**File:** `app/Services/ErrorService.php`

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ErrorService
{
    /**
     * Log an error with context and return a reference ID
     */
    public static function logError(\Throwable $e, ?array $context = []): string
    {
        $errorId = Str::uuid()->toString();
        
        Log::error('Application Error', array_merge([
            'error_id' => $errorId,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'user_id' => auth()->id(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $context));
        
        return $errorId;
    }
    
    /**
     * Log a validation error
     */
    public static function logValidationError(array $errors, ?string $formName = null): void
    {
        Log::warning('Validation Failed', [
            'form' => $formName,
            'errors' => $errors,
            'user_id' => auth()->id(),
            'url' => request()->fullUrl(),
            'input' => request()->except(['password', 'password_confirmation']),
        ]);
    }
}
```

---

## Acceptance Criteria

- [x] 403 error page created with Sneat styling (no emojis)
- [x] 404 error page created with Sneat styling (no emojis)
- [x] 500 error page created with Sneat styling and error reference (no emojis)
- [ ] 503/maintenance page created with Sneat styling (EXCLUDED per requirements)
- [x] Guest layout exists for error pages
- [x] Toast notifications work for success/error/warning/info (already existed)
- [x] Session expired modal shows on AJAX 401/419
- [ ] Booking conflict modal created (EXCLUDED - Task 5.3.9)
- [x] Error pages have navigation back to dashboard/home
- [x] Error pages are responsive (mobile-friendly)
- [x] Server errors are logged with context and reference ID
- [x] Technical details never exposed to users
- [x] Reusable components utilized across all related views
- [x] Test case document created with comprehensive test scenarios

---

## Testing Error Pages

```bash
# Test 404
curl -I http://localhost:8000/nonexistent-page

# Test 403 (access admin as regular user)
# Login as regular user, then try to access /admin/users

# Test 500 (simulate in controller)
# Temporarily add: throw new \Exception('Test error');

# Test 503
# Enable maintenance mode: php artisan down
```

---

**Next:** [Step 5.4 - Security Hardening](./step-5.4-security-hardening.md)
