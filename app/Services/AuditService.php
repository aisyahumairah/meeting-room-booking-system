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
