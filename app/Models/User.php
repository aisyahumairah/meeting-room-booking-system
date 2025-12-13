<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'staff_number',
        'name',
        'email',
        'password',
        'must_change_password',
        'last_login_at',
        'department',
        'phone',
        'role',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    // =========================================================================
    // ROLE CHECK METHODS
    // =========================================================================

    /**
     * Check if user is a Regular User.
     */
    public function isRegularUser(): bool
    {
        return $this->role === 'regular_user';
    }

    /**
     * Check if user is an Administrator.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'administrator';
    }

    /**
     * Check if user is a Director.
     */
    public function isDirector(): bool
    {
        return $this->role === 'director';
    }

    /**
     * Check if user is a System Admin.
     */
    public function isSysAdmin(): bool
    {
        return $this->role === 'system_admin';
    }

    // =========================================================================
    // PERMISSION CHECK METHODS
    // =========================================================================

    /**
     * Check if user can manage bookings (approve/reject).
     * Administrators and Directors can manage bookings.
     */
    public function canManageBookings(): bool
    {
        return in_array($this->role, ['administrator', 'director']);
    }

    /**
     * Check if user can manage meeting rooms.
     * Administrators and Directors can manage rooms.
     */
    public function canManageRooms(): bool
    {
        return in_array($this->role, ['administrator', 'director']);
    }

    /**
     * Check if user can manage other users.
     * Directors and System Admins can manage users.
     */
    public function canManageUsers(): bool
    {
        return in_array($this->role, ['director', 'system_admin']);
    }

    /**
     * Check if user can access the audit trail.
     * Directors and System Admins can view audit logs.
     */
    public function canAccessAudit(): bool
    {
        return in_array($this->role, ['director', 'system_admin']);
    }

    /**
     * Check if user can access reports.
     * Administrators, Directors, and System Admins can access reports.
     */
    public function canAccessReports(): bool
    {
        return in_array($this->role, ['administrator', 'director', 'system_admin']);
    }

    /**
     * Check if user can configure system settings.
     * Only System Admin can configure the system.
     */
    public function canConfigureSystem(): bool
    {
        return $this->role === 'system_admin';
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get all bookings for the user.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get user's notification preferences.
     */
    public function notificationPreferences(): HasOne
    {
        return $this->hasOne(UserNotificationPreference::class);
    }

    /**
     * Get notification preference value, respecting system settings.
     */
    public function wantsNotification(string $type): bool
    {
        // First check system-level setting
        $systemKey = "notify_{$type}";
        if (!SystemSetting::get($systemKey, true)) {
            return false;
        }

        // Check master email toggle
        if (!SystemSetting::isEmailEnabled()) {
            return false;
        }

        // Non-optional notifications (always sent if email enabled)
        if (in_array($type, ['welcome_email', 'password_reset'])) {
            return true;
        }

        // Check user preference
        $prefs = $this->notificationPreferences;
        if (!$prefs) {
            return true; // Default to enabled
        }

        return $prefs->{$type} ?? true;
    }

    // =========================================================================
    // QUERY SCOPES
    // =========================================================================

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include users of a specific role.
     */
    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope a query to only include users from a specific department.
     */
    public function scopeByDepartment($query, string $department)
    {
        return $query->where('department', $department);
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Get the human-readable role display name.
     */
    public function getRoleDisplayAttribute(): string
    {
        return match ($this->role) {
            'system_admin' => 'System Admin',
            'director' => 'Director',
            'administrator' => 'Administrator',
            'regular_user' => 'Regular User',
            default => ucfirst(str_replace('_', ' ', $this->role)),
        };
    }

    /**
     * Get the Bootstrap badge class for the role.
     */
    public function getRoleBadgeAttribute(): string
    {
        return match ($this->role) {
            'system_admin' => 'warning',
            'director' => 'primary',
            'administrator' => 'success',
            'regular_user' => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Get the Bootstrap badge class for the status.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'active' => 'success',
            'inactive' => 'danger',
            default => 'secondary',
        };
    }
}
