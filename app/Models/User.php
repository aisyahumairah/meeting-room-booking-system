<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
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
}
