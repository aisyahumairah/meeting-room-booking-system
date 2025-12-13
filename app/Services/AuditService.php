<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    // Authentication Events
    public const EVENT_LOGIN_SUCCESS = 'login_success';
    public const EVENT_LOGIN_FAILED = 'login_failed';
    public const EVENT_LOGOUT = 'logout';
    public const EVENT_PASSWORD_RESET_REQUESTED = 'password_reset_requested';
    public const EVENT_PASSWORD_RESET_COMPLETED = 'password_reset_completed';
    public const EVENT_PASSWORD_CHANGED = 'password_changed';
    public const EVENT_PROFILE_UPDATED = 'profile_updated';

    // Room Management Events
    public const EVENT_ROOM_CREATED = 'room.created';
    public const EVENT_ROOM_UPDATED = 'room.updated';
    public const EVENT_ROOM_DELETED = 'room.deleted';
    public const EVENT_ROOM_STATUS_CHANGED = 'room.status_changed';
    public const EVENT_ROOM_MAINTENANCE_SCHEDULED = 'room.maintenance_scheduled';
    public const EVENT_ROOM_MAINTENANCE_CANCELLED = 'room.maintenance_cancelled';
    public const EVENT_ROOM_IMAGE_UPLOADED = 'room.image_uploaded';
    public const EVENT_ROOM_IMAGE_DELETED = 'room.image_deleted';

    // User Management Events
    public const EVENT_USER_CREATED = 'user.created';
    public const EVENT_USER_UPDATED = 'user.updated';
    public const EVENT_USER_ROLE_CHANGED = 'user.role_changed';
    public const EVENT_USER_DEACTIVATED = 'user.deactivated';
    public const EVENT_USER_REACTIVATED = 'user.reactivated';
    public const EVENT_USER_DELETED = 'user.deleted';
    public const EVENT_USER_PASSWORD_RESET_BY_ADMIN = 'user.password_reset_by_admin';

    // Audit & Report Events
    public const EVENT_AUDIT_LOG_EXPORTED = 'audit_log.exported';
    public const EVENT_REPORT_GENERATED = 'report.generated';

    // Settings Events
    public const EVENT_SETTINGS_UPDATED = 'settings.updated';
    public const EVENT_MAINTENANCE_MODE_TOGGLED = 'settings.maintenance_mode_toggled';

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
