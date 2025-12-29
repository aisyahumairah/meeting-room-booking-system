# Step 5.4: Security Hardening

**Priority:** CRITICAL | **Ref:** §8.4 | **Dependencies:** None  
**Status:** TODO

---

## Objective

Apply comprehensive security measures to protect against common web vulnerabilities including XSS, CSRF, SQL Injection, session hijacking, brute force attacks, and IDOR.

---

## Task 5.4.1: XSS Prevention Audit

### Verify Blade Output Escaping

All user-generated content MUST use `{{ }}` syntax (auto-escapes):

```blade
{{-- ✅ SAFE - Auto-escaped --}}
<h1>{{ $room->name }}</h1>
<p>{{ $booking->purpose }}</p>
<span>{{ $user->name }}</span>

{{-- ❌ DANGEROUS - Never use with user data --}}
{!! $userInput !!}
```

### Audit Checklist

```
□ Room Management
  ├── Room name display
  ├── Room description
  ├── Floor location
  └── Amenity names

□ Booking Management
  ├── Booking purpose
  ├── Booker name
  ├── Cancellation reason
  └── Reference numbers

□ User Management
  ├── User name display
  ├── Department
  ├── Email display
  └── Activity details

□ Audit Logs
  ├── Actor names
  ├── Event details (JSON)
  └── Target descriptions

□ Reports
  └── All report data fields
```

### Input Sanitization

**File:** `app/Http/Middleware/SanitizeInput.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SanitizeInput
{
    /**
     * Fields to strip HTML from
     */
    protected array $sanitizeFields = [
        'name',
        'purpose',
        'description',
        'reason',
        'department',
    ];

    public function handle(Request $request, Closure $next)
    {
        $input = $request->all();
        
        array_walk_recursive($input, function (&$value, $key) {
            if (is_string($value) && in_array($key, $this->sanitizeFields)) {
                // Strip HTML tags
                $value = strip_tags($value);
                // Remove potential script injections
                $value = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $value);
            }
        });
        
        $request->merge($input);
        
        return $next($request);
    }
}
```

Register in `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\SanitizeInput::class,
    ]);
})
```

---

## Task 5.4.2: CSRF Protection Verification

### Verify All Forms Have CSRF Token

```
□ Authentication Forms
  ├── /login - @csrf ✓
  ├── /forgot-password - @csrf ✓
  └── /reset-password - @csrf ✓

□ User Management
  ├── Create user form - @csrf ✓
  ├── Edit user form - @csrf ✓
  ├── Deactivate/Activate - @csrf ✓
  └── Reset password - @csrf ✓

□ Room Management
  ├── Create room form - @csrf ✓
  ├── Edit room form - @csrf ✓
  ├── Status change - @csrf ✓
  └── Delete room - @csrf ✓

□ Booking Management
  ├── Create booking - @csrf ✓
  ├── Edit booking - @csrf ✓
  └── Cancel booking - @csrf ✓

□ Amenity Management
  ├── Create amenity - @csrf ✓
  ├── Edit amenity - @csrf ✓
  ├── Status toggle - @csrf ✓
  └── Delete amenity - @csrf ✓

□ Profile Management
  ├── Update profile - @csrf ✓
  └── Change password - @csrf ✓

□ Settings
  └── Update settings - @csrf ✓
```

### AJAX Requests CSRF

Ensure AJAX requests include CSRF token:

```javascript
// Global AJAX setup
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    }
});

// Or in fetch requests
fetch('/api/endpoint', {
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
    body: JSON.stringify(data)
});
```

---

## Task 5.4.3: SQL Injection Prevention Audit

### Verify Eloquent/Query Builder Usage

```
□ All queries use Eloquent or Query Builder (parameterized)
□ No raw SQL with user input concatenation
□ DB::raw() usage reviewed for safety
```

### Code Review Checklist

```php
// ✅ SAFE - Parameterized
$users = User::where('email', $email)->get();
$rooms = Room::whereIn('id', $roomIds)->get();
$bookings = Booking::where('booking_date', '>=', $startDate)->get();

// ✅ SAFE - Query Builder
DB::table('users')->where('email', $email)->first();

// ⚠️ REVIEW - Raw queries must be parameterized
DB::select('SELECT * FROM users WHERE email = ?', [$email]);

// ❌ DANGEROUS - Never do this
DB::select("SELECT * FROM users WHERE email = '$email'"); // SQL Injection!
```

### Search Function Audit

Review search implementations:

```php
// ✅ SAFE - Using LIKE with parameter binding
$query->where('name', 'ilike', '%' . $search . '%');

// For PostgreSQL full-text search (if used)
$query->whereRaw("to_tsvector('english', name) @@ plainto_tsquery('english', ?)", [$search]);
```

---

## Task 5.4.4: Session Security Configuration

### Update Session Configuration

**File:** `config/session.php`

```php
return [
    'driver' => env('SESSION_DRIVER', 'database'),
    
    'lifetime' => env('SESSION_LIFETIME', 30), // 30 minutes
    
    'expire_on_close' => false,
    
    'encrypt' => true, // Encrypt session data
    
    'cookie' => env('SESSION_COOKIE', 'mrbs_session'),
    
    'path' => '/',
    
    'domain' => env('SESSION_DOMAIN'),
    
    'secure' => env('SESSION_SECURE_COOKIE', true), // HTTPS only in production
    
    'http_only' => true, // Prevent JavaScript access
    
    'same_site' => 'lax', // CSRF protection
];
```

### Session Regeneration on Login

**File:** `app/Http/Controllers/Auth/LoginController.php`

```php
protected function authenticated(Request $request, $user)
{
    // Regenerate session ID to prevent session fixation
    $request->session()->regenerate();
    
    // Update last login timestamp
    $user->update(['last_login_at' => now()]);
    
    // Log successful login
    AuditService::log('auth.login', 'user', $user->id, [
        'ip' => $request->ip(),
        'user_agent' => $request->userAgent(),
    ]);
    
    return redirect()->intended($this->redirectTo());
}
```

### Session Invalidation on Logout

**File:** `app/Http/Controllers/Auth/LoginController.php`

```php
public function logout(Request $request)
{
    $userId = auth()->id();
    
    // Log logout before session is destroyed
    AuditService::log('auth.logout', 'user', $userId);
    
    // Invalidate session
    auth()->logout();
    
    // Invalidate AND regenerate session
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    
    return redirect('/login');
}
```

---

## Task 5.4.5: Brute Force Protection

### Login Throttling

**File:** `app/Http/Controllers/Auth/LoginController.php`

```php
use Illuminate\Foundation\Auth\ThrottlesLogins;

class LoginController extends Controller
{
    use ThrottlesLogins;
    
    /**
     * Maximum login attempts before lockout
     */
    protected int $maxAttempts = 5;
    
    /**
     * Lockout duration in minutes
     */
    protected int $decayMinutes = 15;
    
    public function login(Request $request)
    {
        // Check if too many attempts
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            
            $seconds = $this->limiter()->availableIn(
                $this->throttleKey($request)
            );
            
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
                ]);
        }
        
        // Attempt login...
        if (auth()->attempt($request->only('email', 'password'))) {
            $this->clearLoginAttempts($request);
            return $this->authenticated($request, auth()->user());
        }
        
        // Increment attempts and log failure
        $this->incrementLoginAttempts($request);
        
        AuditService::log('auth.login_failed', 'user', null, [
            'email' => $request->email,
            'ip' => $request->ip(),
            'attempts' => $this->limiter()->attempts($this->throttleKey($request)),
        ]);
        
        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'Invalid email or password.']);
    }
    
    protected function throttleKey(Request $request): string
    {
        return Str::lower($request->input('email')) . '|' . $request->ip();
    }
}
```

### Alternative: Rate Limiting Middleware

**File:** `routes/web.php`

```php
Route::middleware('throttle:5,15')->group(function () {
    Route::post('login', [LoginController::class, 'login']);
    Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLink']);
});
```

---

## Task 5.4.6: IDOR Prevention (Authorization Checks)

### Verify All Resource Access Has Authorization

**Booking Access:**
```php
// In BookingController
public function show(Booking $booking)
{
    // Regular users can only see their own bookings
    if (auth()->user()->isRegularUser() && $booking->user_id !== auth()->id()) {
        abort(403, 'You can only view your own bookings.');
    }
    
    return view('bookings.show', compact('booking'));
}

public function edit(Booking $booking)
{
    // Check ownership or admin role
    $this->authorize('update', $booking);
    
    return view('bookings.edit', compact('booking'));
}
```

**User Access:**
```php
// In Admin\UserController
public function edit(User $user)
{
    // Only Director/SysAdmin can edit users
    if (!auth()->user()->canManageUsers()) {
        abort(403);
    }
    
    // Cannot edit yourself (for certain actions)
    if ($user->id === auth()->id()) {
        return back()->with('error', 'Use profile page to edit your own account.');
    }
    
    return view('admin.users.edit', compact('user'));
}
```

### Policy-Based Authorization

**File:** `app/Policies/BookingPolicy.php`

```php
<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        return $user->id === $booking->user_id 
            || $user->canManageBookings();
    }
    
    public function update(User $user, Booking $booking): bool
    {
        // Can only update upcoming bookings
        if ($booking->booking_date < now()->toDateString()) {
            return false;
        }
        
        // Status must be confirmed
        if ($booking->status !== 'confirmed') {
            return false;
        }
        
        return $user->id === $booking->user_id 
            || $user->canManageBookings();
    }
    
    public function delete(User $user, Booking $booking): bool
    {
        // Can only cancel confirmed bookings
        if ($booking->status !== 'confirmed') {
            return false;
        }
        
        return $user->id === $booking->user_id 
            || $user->canManageBookings();
    }
}
```

Register in `AuthServiceProvider`:
```php
protected $policies = [
    Booking::class => BookingPolicy::class,
];
```

---

## Task 5.4.7: Password Security

### Verify Password Hashing

```php
// ✅ Laravel uses bcrypt by default
$user->password = Hash::make($password);

// Verify in UserFactory
public function definition(): array
{
    return [
        'password' => Hash::make('password123'),
        // or
        'password' => bcrypt('password123'),
    ];
}
```

### Password Validation Rules

```php
// In change password form
'password' => [
    'required',
    'string',
    'min:8',
    'regex:/[a-zA-Z]/',      // At least one letter
    'regex:/[0-9]/',          // At least one number
    'regex:/[@$!%*#?&]/',     // At least one special character
    'confirmed',
],
```

### Never Log Passwords

```php
// ✅ SAFE - Exclude password from logs
Log::info('User registered', $request->except(['password', 'password_confirmation']));

// ❌ DANGEROUS - Never do this
Log::info('Login attempt', $request->all()); // Logs password!
```

---

## Task 5.4.8: Security Headers

**File:** `app/Http/Middleware/SecurityHeaders.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        
        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        
        // Enable XSS filter
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        
        // Referrer policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Content Security Policy (adjust as needed)
        $response->headers->set('Content-Security-Policy', 
            "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' cdn.jsdelivr.net; " .
            "style-src 'self' 'unsafe-inline' fonts.googleapis.com; " .
            "font-src 'self' fonts.gstatic.com; " .
            "img-src 'self' data: blob:; " .
            "connect-src 'self';"
        );
        
        return $response;
    }
}
```

Register in `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\SecurityHeaders::class,
    ]);
})
```

---

## Task 5.4.9: File Upload Security

**Room Image Upload Validation:**

```php
// In StoreRoomRequest
'images.*' => [
    'image',
    'mimes:jpg,jpeg,png,webp',
    'max:5120', // 5MB
    'dimensions:min_width=100,min_height=100,max_width=4000,max_height=4000',
],
```

**Storage Configuration:**

```php
// Store in private directory
$path = $request->file('image')->store('rooms', 'private');

// Generate signed URL for access
return Storage::disk('private')->temporaryUrl($path, now()->addMinutes(30));
```

---

## Task 5.4.10: Environment Security

### Verify Production Settings

**File:** `.env` (production)

```env
APP_ENV=production
APP_DEBUG=false

# Never expose debug info
DEBUGBAR_ENABLED=false

# Secure session
SESSION_SECURE_COOKIE=true
SESSION_LIFETIME=30

# Strong app key (generate with: php artisan key:generate)
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

### Hide Sensitive Errors

**File:** `config/app.php`

```php
'debug' => env('APP_DEBUG', false), // false in production
```

---

## Security Audit Checklist

```
□ XSS Prevention
  ├── All output uses {{ }} escaping
  ├── No {!! !!} with user data
  └── Input sanitization middleware active

□ CSRF Protection
  ├── All forms have @csrf
  ├── AJAX requests include token
  └── Token meta tag in layout

□ SQL Injection
  ├── All queries use Eloquent/Query Builder
  ├── No raw SQL with user input
  └── Search functions properly escaped

□ Session Security
  ├── Database session driver
  ├── Secure cookie flags set
  ├── Session regeneration on login
  └── Session invalidation on logout

□ Brute Force Protection
  ├── Login throttling (5 attempts/15 min)
  ├── Failed attempts logged
  └── Generic error messages

□ IDOR Prevention
  ├── Authorization checks on all resources
  ├── Policies defined for models
  └── 403 returned for unauthorized access

□ Password Security
  ├── Bcrypt hashing
  ├── Strong password requirements
  └── Passwords never logged

□ Headers
  ├── X-Frame-Options
  ├── X-Content-Type-Options
  ├── X-XSS-Protection
  └── Content-Security-Policy
```

---

## Acceptance Criteria

- [ ] All user output is properly escaped
- [ ] All forms have CSRF protection
- [ ] All queries use parameterized statements
- [ ] Session security is properly configured
- [ ] Login throttling prevents brute force (5 attempts, 15 min lockout)
- [ ] All resource access has authorization checks
- [ ] Passwords are hashed with bcrypt
- [ ] Security headers are set on all responses
- [ ] File uploads are validated and stored securely
- [ ] Production environment does not expose debug info

---

**Next:** [Step 5.5 - Performance Optimization](./step-5.5-performance-optimization.md)
