# Step 1.7: Audit Trail Foundation

**Priority:** HIGH | **Ref:** §7.3.1 | **Dependencies:** Step 1.2

---

## Objective
Create audit logging infrastructure to track all system activities.

---

## Task 1.7.1: Create Audit Logs Migration

**Command:** `php artisan make:migration create_audit_logs_table`

**File:** `database/migrations/XXXX_create_audit_logs_table.php`

```php
Schema::create('audit_logs', function (Blueprint $table) {
    $table->id();
    
    // Event info
    $table->string('event_type', 50); // login, logout, booking_created, room_edited, etc.
    
    // Actor (who performed the action)
    $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
    $table->string('actor_name', 100); // Stored separately in case user is deleted
    
    // Target (what was affected)
    $table->string('target_type', 50)->nullable(); // user, booking, room, etc.
    $table->unsignedBigInteger('target_id')->nullable();
    
    // Details
    $table->json('details')->nullable(); // Old/new values, extra context
    
    // Request info
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    
    // Timestamp only (immutable - no updated_at)
    $table->timestamp('created_at')->useCurrent();
    
    // Indexes for filtering
    $table->index('event_type');
    $table->index('actor_id');
    $table->index(['target_type', 'target_id']);
    $table->index('created_at');
});
```

---

## Task 1.7.2: Create AuditLog Model

**Command:** `php artisan make:model AuditLog`

**File:** `app/Models/AuditLog.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false; // Only created_at, no updated_at
    
    protected $fillable = [
        'event_type',
        'actor_id',
        'actor_name',
        'target_type',
        'target_id',
        'details',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
    ];

    // Prevent updates and deletes (immutable)
    public static function boot()
    {
        parent::boot();

        static::updating(function ($model) {
            return false; // Prevent updates
        });

        static::deleting(function ($model) {
            return false; // Prevent deletes
        });
    }

    // Relationships
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    // Scopes
    public function scopeByEventType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    public function scopeByActor($query, int $userId)
    {
        return $query->where('actor_id', $userId);
    }

    public function scopeByTarget($query, string $type, int $id)
    {
        return $query->where('target_type', $type)->where('target_id', $id);
    }

    public function scopeBetweenDates($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    // Accessors
    public function getEventDisplayAttribute(): string
    {
        return match($this->event_type) {
            'login_success' => 'Login Success',
            'login_failed' => 'Login Failed',
            'logout' => 'Logout',
            'password_reset_requested' => 'Password Reset Requested',
            'password_reset_completed' => 'Password Reset Completed',
            'password_changed' => 'Password Changed',
            'profile_updated' => 'Profile Updated',
            'user_created' => 'User Created',
            'user_updated' => 'User Updated',
            'user_deactivated' => 'User Deactivated',
            'room_created' => 'Room Created',
            'room_updated' => 'Room Updated',
            'room_status_changed' => 'Room Status Changed',
            'booking_created' => 'Booking Created',
            'booking_updated' => 'Booking Updated',
            'booking_cancelled' => 'Booking Cancelled',
            'booking_approved' => 'Booking Approved',
            'booking_rejected' => 'Booking Rejected',
            default => ucwords(str_replace('_', ' ', $this->event_type)),
        };
    }
}
```

---

## Task 1.7.3: Create AuditService

**File:** `app/Services/AuditService.php`

```php
<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    public static function log(
        string $eventType,
        ?string $targetType = null,
        ?int $targetId = null,
        ?array $details = null
    ): AuditLog {
        return AuditLog::create([
            'event_type' => $eventType,
            'actor_id' => Auth::id(),
            'actor_name' => Auth::user()?->name ?? 'System',
            'target_type' => $targetType,
            'target_id' => $targetId,
            'details' => $details,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }

    // Convenience methods for common events
    public static function logLogin(bool $success, ?string $email = null): AuditLog
    {
        return self::log(
            $success ? 'login_success' : 'login_failed',
            'user',
            Auth::id(),
            $success ? null : ['email' => $email]
        );
    }

    public static function logLogout(): AuditLog
    {
        return self::log('logout', 'user', Auth::id());
    }

    public static function logPasswordResetRequested(string $email): AuditLog
    {
        return self::log('password_reset_requested', null, null, ['email' => $email]);
    }

    public static function logPasswordResetCompleted(int $userId): AuditLog
    {
        return self::log('password_reset_completed', 'user', $userId);
    }

    public static function logPasswordChanged(): AuditLog
    {
        return self::log('password_changed', 'user', Auth::id());
    }

    public static function logProfileUpdated(array $changes): AuditLog
    {
        return self::log('profile_updated', 'user', Auth::id(), $changes);
    }
}
```

---

## Task 1.7.4: Integrate Audit Logging into Auth Controllers

**File:** `app/Http/Controllers/Auth/LoginController.php`

Add logging calls:

```php
use App\Services\AuditService;

// In login() method, after successful auth:
AuditService::logLogin(true);

// In login() method, after failed auth:
AuditService::logLogin(false, $credentials['email']);

// In logout() method:
AuditService::logLogout();
```

**File:** `app/Http/Controllers/Auth/ForgotPasswordController.php`
```php
// After sending reset email:
AuditService::logPasswordResetRequested($request->email);
```

**File:** `app/Http/Controllers/Auth/ResetPasswordController.php`
```php
// After password reset:
AuditService::logPasswordResetCompleted($user->id);
```

**File:** `app/Http/Controllers/ProfileController.php`
```php
// After profile update:
AuditService::logProfileUpdated([
    'changed_fields' => array_keys($validated)
]);

// After password change:
AuditService::logPasswordChanged();
```

---

## Task 1.7.5: Event Types Reference

Phase 1 events to log:

| Event Type | Target Type | When Logged |
|------------|-------------|-------------|
| `login_success` | user | After successful login |
| `login_failed` | null | After failed login attempt |
| `logout` | user | After logout |
| `password_reset_requested` | null | When reset email sent |
| `password_reset_completed` | user | After password reset |
| `password_changed` | user | After password change (profile) |
| `profile_updated` | user | After profile update |

Future phases will add:
- `user_created`, `user_updated`, `user_deactivated`
- `room_created`, `room_updated`, `room_status_changed`
- `booking_created`, `booking_updated`, `booking_cancelled`
- `booking_approved`, `booking_rejected`

---

## Testing Requirements

**File:** `tests/Feature/AuditLogTest.php`

Test cases:
- Successful login creates audit log entry
- Failed login creates audit log entry
- Logout creates audit log entry
- Password reset request is logged
- Password change is logged
- Profile update is logged
- Audit logs cannot be updated
- Audit logs cannot be deleted

```bash
php artisan test --filter=AuditLogTest
```

---

## Acceptance Criteria
- [x] `audit_logs` table created with migration
- [x] AuditLog model is immutable (cannot update/delete)
- [x] AuditService provides logging methods
- [x] Login success/failure logged
- [x] Logout logged
- [x] Password reset request/completion logged
- [x] Profile updates logged
- [x] All tests pass

---

## Phase 1 Complete

After completing Step 1.7, Phase 1 is complete. Proceed to **Phase 2: Meeting Rooms Management**.
