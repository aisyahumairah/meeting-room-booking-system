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
