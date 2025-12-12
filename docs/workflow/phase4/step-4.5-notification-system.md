# Step 4.5: Notification System

**Priority:** HIGH | **Ref:** §7.4 | **Dependencies:** Step 4.4 (System Settings)  
**Status:** TODO

---

## Objective

Implement a comprehensive email notification system that sends emails for key system events (account creation, password reset, booking confirmation, booking cancellation, booking reminders, room status changes). The system respects system-wide settings and user preferences, and logs all sent notifications.

---

## Task 4.5.1: Create Notification Log Migration

```bash
php artisan make:migration create_notification_logs_table
```

**File:** `database/migrations/YYYY_MM_DD_HHMMSS_create_notification_logs_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 50); // welcome, password_reset, booking_confirmed, etc.
            $table->string('channel', 20)->default('email'); // email, sms (future)
            $table->string('recipient_email');
            $table->string('subject');
            $table->text('content')->nullable(); // Store rendered content or summary
            $table->string('status', 20)->default('sent'); // sent, failed, pending
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable(); // Related IDs, additional context
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
```

---

## Task 4.5.2: Create User Notification Preferences Migration

```bash
php artisan make:migration create_user_notification_preferences_table
```

**File:** `database/migrations/YYYY_MM_DD_HHMMSS_create_user_notification_preferences_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('booking_confirmed')->default(true);
            $table->boolean('booking_cancelled')->default(true);
            $table->boolean('booking_reminder')->default(true);
            $table->boolean('room_status_changed')->default(true);
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notification_preferences');
    }
};
```

---

## Task 4.5.3: Create NotificationLog Model

```bash
php artisan make:model NotificationLog
```

**File:** `app/Models/NotificationLog.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'channel',
        'recipient_email',
        'subject',
        'content',
        'status',
        'error_message',
        'metadata',
        'sent_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
    ];

    // Notification Types
    public const TYPE_WELCOME = 'welcome';
    public const TYPE_PASSWORD_RESET = 'password_reset';
    public const TYPE_BOOKING_CONFIRMED = 'booking_confirmed';
    public const TYPE_BOOKING_CANCELLED = 'booking_cancelled';
    public const TYPE_BOOKING_REMINDER = 'booking_reminder';
    public const TYPE_ROOM_STATUS_CHANGED = 'room_status_changed';

    // Statuses
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_PENDING = 'pending';

    /**
     * Get the user this notification was sent to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get human-readable type name.
     */
    public function getTypeNameAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_WELCOME => 'Welcome Email',
            self::TYPE_PASSWORD_RESET => 'Password Reset',
            self::TYPE_BOOKING_CONFIRMED => 'Booking Confirmed',
            self::TYPE_BOOKING_CANCELLED => 'Booking Cancelled',
            self::TYPE_BOOKING_REMINDER => 'Booking Reminder',
            self::TYPE_ROOM_STATUS_CHANGED => 'Room Status Changed',
            default => ucwords(str_replace('_', ' ', $this->type)),
        };
    }

    /**
     * Scope for notifications by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for failed notifications.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }
}
```

---

## Task 4.5.4: Create UserNotificationPreference Model

```bash
php artisan make:model UserNotificationPreference
```

**File:** `app/Models/UserNotificationPreference.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'booking_confirmed',
        'booking_cancelled',
        'booking_reminder',
        'room_status_changed',
    ];

    protected $casts = [
        'booking_confirmed' => 'boolean',
        'booking_cancelled' => 'boolean',
        'booking_reminder' => 'boolean',
        'room_status_changed' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get default preferences.
     */
    public static function getDefaults(): array
    {
        return [
            'booking_confirmed' => true,
            'booking_cancelled' => true,
            'booking_reminder' => true,
            'room_status_changed' => true,
        ];
    }
}
```

---

## Task 4.5.5: Update User Model Relationship

**File:** `app/Models/User.php`

Add relationship:

```php
use App\Models\UserNotificationPreference;

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
```

---

## Task 4.5.6: Create NotificationService

**File:** `app/Services/NotificationService.php`

```php
<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\NotificationLog;
use App\Models\Room;
use App\Models\SystemSetting;
use App\Models\User;
use App\Mail\WelcomeEmail;
use App\Mail\BookingConfirmedEmail;
use App\Mail\BookingCancelledEmail;
use App\Mail\BookingReminderEmail;
use App\Mail\RoomStatusChangedEmail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Send welcome email to new user.
     */
    public static function sendWelcomeEmail(User $user, string $tempPassword): void
    {
        if (!SystemSetting::isEmailEnabled() || !SystemSetting::get('notify_welcome_email', true)) {
            return;
        }

        try {
            Mail::to($user->email)->queue(new WelcomeEmail($user, $tempPassword));

            self::logNotification($user, NotificationLog::TYPE_WELCOME, $user->email, 
                'Welcome to MRBS', ['temp_password_included' => true]);
        } catch (\Exception $e) {
            self::logNotification($user, NotificationLog::TYPE_WELCOME, $user->email,
                'Welcome to MRBS', [], NotificationLog::STATUS_FAILED, $e->getMessage());
            Log::error('Failed to send welcome email', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Send booking confirmation email.
     */
    public static function sendBookingConfirmedEmail(Booking $booking): void
    {
        $user = $booking->user;

        if (!$user->wantsNotification('booking_confirmed')) {
            return;
        }

        try {
            Mail::to($user->email)->queue(new BookingConfirmedEmail($booking));

            self::logNotification($user, NotificationLog::TYPE_BOOKING_CONFIRMED, $user->email,
                "Booking Confirmed: {$booking->reference_number}",
                ['booking_id' => $booking->id, 'reference' => $booking->reference_number]);
        } catch (\Exception $e) {
            self::logNotification($user, NotificationLog::TYPE_BOOKING_CONFIRMED, $user->email,
                "Booking Confirmed: {$booking->reference_number}",
                ['booking_id' => $booking->id], NotificationLog::STATUS_FAILED, $e->getMessage());
            Log::error('Failed to send booking confirmation', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Send booking cancellation email.
     */
    public static function sendBookingCancelledEmail(Booking $booking, ?string $reason = null): void
    {
        $user = $booking->user;

        if (!$user->wantsNotification('booking_cancelled')) {
            return;
        }

        try {
            Mail::to($user->email)->queue(new BookingCancelledEmail($booking, $reason));

            self::logNotification($user, NotificationLog::TYPE_BOOKING_CANCELLED, $user->email,
                "Booking Cancelled: {$booking->reference_number}",
                ['booking_id' => $booking->id, 'reference' => $booking->reference_number, 'reason' => $reason]);
        } catch (\Exception $e) {
            self::logNotification($user, NotificationLog::TYPE_BOOKING_CANCELLED, $user->email,
                "Booking Cancelled: {$booking->reference_number}",
                ['booking_id' => $booking->id], NotificationLog::STATUS_FAILED, $e->getMessage());
            Log::error('Failed to send cancellation email', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Send booking reminder email (for scheduled job).
     */
    public static function sendBookingReminderEmail(Booking $booking): void
    {
        $user = $booking->user;

        if (!$user->wantsNotification('booking_reminder')) {
            return;
        }

        try {
            Mail::to($user->email)->queue(new BookingReminderEmail($booking));

            self::logNotification($user, NotificationLog::TYPE_BOOKING_REMINDER, $user->email,
                "Reminder: Upcoming Booking - {$booking->reference_number}",
                ['booking_id' => $booking->id, 'reference' => $booking->reference_number]);
        } catch (\Exception $e) {
            self::logNotification($user, NotificationLog::TYPE_BOOKING_REMINDER, $user->email,
                "Reminder: Upcoming Booking",
                ['booking_id' => $booking->id], NotificationLog::STATUS_FAILED, $e->getMessage());
            Log::error('Failed to send reminder email', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Send room status change notification to affected users.
     */
    public static function sendRoomStatusChangedEmail(Room $room, string $oldStatus, string $newStatus, array $affectedBookings = []): void
    {
        if (!SystemSetting::isEmailEnabled() || !SystemSetting::get('notify_room_status_changed', true)) {
            return;
        }

        // Get unique users from affected bookings
        $userIds = collect($affectedBookings)->pluck('user_id')->unique();
        $users = User::whereIn('id', $userIds)->get();

        foreach ($users as $user) {
            if (!$user->wantsNotification('room_status_changed')) {
                continue;
            }

            $userBookings = collect($affectedBookings)->where('user_id', $user->id)->values();

            try {
                Mail::to($user->email)->queue(new RoomStatusChangedEmail($room, $oldStatus, $newStatus, $userBookings->toArray()));

                self::logNotification($user, NotificationLog::TYPE_ROOM_STATUS_CHANGED, $user->email,
                    "Room Status Changed: {$room->name}",
                    ['room_id' => $room->id, 'old_status' => $oldStatus, 'new_status' => $newStatus, 'affected_bookings' => $userBookings->count()]);
            } catch (\Exception $e) {
                self::logNotification($user, NotificationLog::TYPE_ROOM_STATUS_CHANGED, $user->email,
                    "Room Status Changed: {$room->name}",
                    ['room_id' => $room->id], NotificationLog::STATUS_FAILED, $e->getMessage());
                Log::error('Failed to send room status email', ['room_id' => $room->id, 'user_id' => $user->id, 'error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Log notification to database.
     */
    private static function logNotification(
        ?User $user,
        string $type,
        string $recipientEmail,
        string $subject,
        array $metadata = [],
        string $status = NotificationLog::STATUS_SENT,
        ?string $errorMessage = null
    ): NotificationLog {
        return NotificationLog::create([
            'user_id' => $user?->id,
            'type' => $type,
            'channel' => 'email',
            'recipient_email' => $recipientEmail,
            'subject' => $subject,
            'status' => $status,
            'error_message' => $errorMessage,
            'metadata' => $metadata,
            'sent_at' => $status === NotificationLog::STATUS_SENT ? now() : null,
        ]);
    }
}
```

---

## Task 4.5.7: Create Email Mailable Classes

```bash
php artisan make:mail WelcomeEmail --markdown=emails.welcome
php artisan make:mail BookingConfirmedEmail --markdown=emails.booking-confirmed
php artisan make:mail BookingCancelledEmail --markdown=emails.booking-cancelled
php artisan make:mail BookingReminderEmail --markdown=emails.booking-reminder
php artisan make:mail RoomStatusChangedEmail --markdown=emails.room-status-changed
```

**File:** `app/Mail/WelcomeEmail.php`

```php
<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $tempPassword
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to MRBS - Your Account Has Been Created',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.welcome',
            with: [
                'user' => $this->user,
                'tempPassword' => $this->tempPassword,
                'loginUrl' => route('login'),
            ],
        );
    }
}
```

**File:** `resources/views/emails/welcome.blade.php`

```blade
<x-mail::message>
# Welcome to MRBS, {{ $user->name }}!

Your account has been created for the Meeting Room Booking System.

**Your Login Credentials:**
- **Email:** {{ $user->email }}
- **Temporary Password:** {{ $tempPassword }}

<x-mail::button :url="$loginUrl">
Login Now
</x-mail::button>

**Important:** For security, you will be required to change your password upon first login.

If you have any questions, please contact your system administrator.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
```

**File:** `app/Mail/BookingConfirmedEmail.php`

```php
<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmedEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Booking Confirmed: {$this->booking->reference_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.booking-confirmed',
            with: [
                'booking' => $this->booking->load(['room', 'user']),
                'viewUrl' => route('bookings.show', $this->booking),
            ],
        );
    }
}
```

**File:** `resources/views/emails/booking-confirmed.blade.php`

```blade
<x-mail::message>
# Booking Confirmed

Your meeting room booking has been confirmed.

**Booking Details:**
- **Reference:** {{ $booking->reference_number }}
- **Room:** {{ $booking->room->name }}
- **Date:** {{ $booking->booking_date->format('l, F j, Y') }}
- **Time:** {{ $booking->start_time->format('g:i A') }} - {{ $booking->end_time->format('g:i A') }}
- **Purpose:** {{ $booking->purpose }}

@if($booking->attendees)
**Attendees:** {{ $booking->attendees }}
@endif

<x-mail::button :url="$viewUrl">
View Booking Details
</x-mail::button>

If you need to cancel this booking, please do so at least 24 hours in advance.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
```

**File:** `app/Mail/BookingCancelledEmail.php`

```php
<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingCancelledEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public ?string $reason = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Booking Cancelled: {$this->booking->reference_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.booking-cancelled',
            with: [
                'booking' => $this->booking->load(['room', 'user']),
                'reason' => $this->reason,
            ],
        );
    }
}
```

**File:** `resources/views/emails/booking-cancelled.blade.php`

```blade
<x-mail::message>
# Booking Cancelled

Your meeting room booking has been cancelled.

**Cancelled Booking Details:**
- **Reference:** {{ $booking->reference_number }}
- **Room:** {{ $booking->room->name }}
- **Date:** {{ $booking->booking_date->format('l, F j, Y') }}
- **Time:** {{ $booking->start_time->format('g:i A') }} - {{ $booking->end_time->format('g:i A') }}
- **Purpose:** {{ $booking->purpose }}

@if($reason)
**Cancellation Reason:** {{ $reason }}
@endif

If you need to book a room again, please visit the booking system.

<x-mail::button :url="route('rooms.index')">
Browse Rooms
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
```

**File:** `app/Mail/BookingReminderEmail.php`

```php
<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingReminderEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Reminder: Upcoming Meeting - {$this->booking->reference_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.booking-reminder',
            with: [
                'booking' => $this->booking->load(['room']),
                'viewUrl' => route('bookings.show', $this->booking),
            ],
        );
    }
}
```

**File:** `resources/views/emails/booking-reminder.blade.php`

```blade
<x-mail::message>
# Upcoming Meeting Reminder

This is a reminder that you have a meeting room booking tomorrow.

**Booking Details:**
- **Reference:** {{ $booking->reference_number }}
- **Room:** {{ $booking->room->name }}
- **Location:** {{ $booking->room->location }}
- **Date:** {{ $booking->booking_date->format('l, F j, Y') }}
- **Time:** {{ $booking->start_time->format('g:i A') }} - {{ $booking->end_time->format('g:i A') }}
- **Purpose:** {{ $booking->purpose }}

@if($booking->room->amenities->isNotEmpty())
**Room Amenities:** {{ $booking->room->amenities->pluck('name')->join(', ') }}
@endif

<x-mail::button :url="$viewUrl">
View Booking Details
</x-mail::button>

If you need to cancel this booking, please do so before the meeting time.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
```

**File:** `app/Mail/RoomStatusChangedEmail.php`

```php
<?php

namespace App\Mail;

use App\Models\Room;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RoomStatusChangedEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Room $room,
        public string $oldStatus,
        public string $newStatus,
        public array $affectedBookings = []
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Room Status Changed: {$this->room->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.room-status-changed',
            with: [
                'room' => $this->room,
                'oldStatus' => $this->oldStatus,
                'newStatus' => $this->newStatus,
                'affectedBookings' => $this->affectedBookings,
            ],
        );
    }
}
```

**File:** `resources/views/emails/room-status-changed.blade.php`

```blade
<x-mail::message>
# Room Status Changed

The status of **{{ $room->name }}** has been updated.

**Status Change:**
- **Previous Status:** {{ ucfirst($oldStatus) }}
- **New Status:** {{ ucfirst($newStatus) }}

@if(count($affectedBookings) > 0)
**Your Affected Bookings:**

<x-mail::table>
| Date | Time | Reference |
|:-----|:-----|:----------|
@foreach($affectedBookings as $booking)
| {{ \Carbon\Carbon::parse($booking['booking_date'])->format('M d, Y') }} | {{ \Carbon\Carbon::parse($booking['start_time'])->format('g:i A') }} | {{ $booking['reference_number'] }} |
@endforeach
</x-mail::table>

@if($newStatus === 'maintenance')
Your bookings in this room may be affected. Please check your booking status or contact an administrator for assistance.
@endif
@endif

<x-mail::button :url="route('rooms.show', $room)">
View Room Details
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
```

---

## Task 4.5.8: Create Booking Reminder Scheduler

```bash
php artisan make:command SendBookingReminders
```

**File:** `app/Console/Commands/SendBookingReminders.php`

```php
<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendBookingReminders extends Command
{
    protected $signature = 'bookings:send-reminders';
    protected $description = 'Send reminder emails for bookings happening tomorrow';

    public function handle(): int
    {
        $tomorrow = now()->addDay()->toDateString();

        $bookings = Booking::with(['user', 'room'])
            ->whereDate('booking_date', $tomorrow)
            ->where('status', 'confirmed')
            ->whereDoesntHave('reminders') // Avoid duplicate reminders
            ->get();

        $this->info("Found {$bookings->count()} bookings for tomorrow.");

        $sent = 0;
        foreach ($bookings as $booking) {
            NotificationService::sendBookingReminderEmail($booking);
            $sent++;
        }

        $this->info("Sent {$sent} reminder emails.");

        return Command::SUCCESS;
    }
}
```

**Register in** `routes/console.php` (or `app/Console/Kernel.php` for older Laravel):

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('bookings:send-reminders')->dailyAt('09:00');
```

---

## Task 4.5.9: Add Notification Preferences to Profile

**File:** `app/Http/Controllers/ProfileController.php`

Add methods:

```php
use App\Models\UserNotificationPreference;

/**
 * Show notification preferences.
 */
public function notifications()
{
    $user = auth()->user();
    $preferences = $user->notificationPreferences ?? new UserNotificationPreference(
        UserNotificationPreference::getDefaults()
    );

    return view('profile.notifications', compact('preferences'));
}

/**
 * Update notification preferences.
 */
public function updateNotifications(Request $request)
{
    $validated = $request->validate([
        'booking_confirmed' => 'boolean',
        'booking_cancelled' => 'boolean',
        'booking_reminder' => 'boolean',
        'room_status_changed' => 'boolean',
    ]);

    $user = auth()->user();

    UserNotificationPreference::updateOrCreate(
        ['user_id' => $user->id],
        [
            'booking_confirmed' => $request->boolean('booking_confirmed'),
            'booking_cancelled' => $request->boolean('booking_cancelled'),
            'booking_reminder' => $request->boolean('booking_reminder'),
            'room_status_changed' => $request->boolean('room_status_changed'),
        ]
    );

    return back()->with('success', 'Notification preferences updated.');
}
```

**File:** `resources/views/profile/notifications.blade.php`

```blade
@extends('layouts.app')

@section('title', 'Notification Preferences')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-md-6 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class='bx bx-bell me-2'></i>Notification Preferences
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">
                        Choose which email notifications you want to receive. 
                        Note: Some critical notifications cannot be disabled.
                    </p>

                    <form action="{{ route('profile.notifications.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" 
                                   id="booking_confirmed" name="booking_confirmed" value="1"
                                   {{ $preferences->booking_confirmed ? 'checked' : '' }}>
                            <label class="form-check-label" for="booking_confirmed">
                                Booking Confirmed
                            </label>
                            <div class="form-text">Receive email when your booking is confirmed</div>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" 
                                   id="booking_cancelled" name="booking_cancelled" value="1"
                                   {{ $preferences->booking_cancelled ? 'checked' : '' }}>
                            <label class="form-check-label" for="booking_cancelled">
                                Booking Cancelled
                            </label>
                            <div class="form-text">Receive email when your booking is cancelled</div>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" 
                                   id="booking_reminder" name="booking_reminder" value="1"
                                   {{ $preferences->booking_reminder ? 'checked' : '' }}>
                            <label class="form-check-label" for="booking_reminder">
                                Booking Reminders
                            </label>
                            <div class="form-text">Receive reminder 24 hours before your booking</div>
                        </div>

                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" 
                                   id="room_status_changed" name="room_status_changed" value="1"
                                   {{ $preferences->room_status_changed ? 'checked' : '' }}>
                            <label class="form-check-label" for="room_status_changed">
                                Room Status Changes
                            </label>
                            <div class="form-text">Receive email when a room with your booking changes status</div>
                        </div>

                        <div class="alert alert-info mb-4">
                            <i class='bx bx-info-circle me-1'></i>
                            <strong>Non-optional notifications:</strong> Welcome emails and password reset 
                            emails will always be sent for security purposes.
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                Save Preferences
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

---

## Task 4.5.10: Define Notification Routes

**File:** `routes/web.php`

Add to profile routes:

```php
Route::middleware(['auth', 'check.active'])->group(function () {
    // ... existing profile routes
    Route::get('profile/notifications', [ProfileController::class, 'notifications'])->name('profile.notifications');
    Route::put('profile/notifications', [ProfileController::class, 'updateNotifications'])->name('profile.notifications.update');
});
```

---

## Testing Requirements

**File:** `tests/Feature/NotificationSystemTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Booking;
use App\Models\Room;
use App\Models\SystemSetting;
use App\Models\NotificationLog;
use App\Models\UserNotificationPreference;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Room $room;
    protected Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        
        Mail::fake();
        
        $this->seed(\Database\Seeders\SystemSettingSeeder::class);
        
        $this->user = User::factory()->create();
        $this->room = Room::factory()->create();
        $this->booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
        ]);
    }

    public function test_welcome_email_is_sent_and_logged(): void
    {
        NotificationService::sendWelcomeEmail($this->user, 'temp123');

        Mail::assertQueued(\App\Mail\WelcomeEmail::class);
        
        $this->assertDatabaseHas('notification_logs', [
            'user_id' => $this->user->id,
            'type' => NotificationLog::TYPE_WELCOME,
            'status' => NotificationLog::STATUS_SENT,
        ]);
    }

    public function test_booking_confirmed_email_respects_user_preference(): void
    {
        // Disable user preference
        UserNotificationPreference::create([
            'user_id' => $this->user->id,
            'booking_confirmed' => false,
            'booking_cancelled' => true,
            'booking_reminder' => true,
            'room_status_changed' => true,
        ]);

        NotificationService::sendBookingConfirmedEmail($this->booking);

        Mail::assertNotQueued(\App\Mail\BookingConfirmedEmail::class);
    }

    public function test_notifications_respect_master_toggle(): void
    {
        SystemSetting::set('email_enabled', 'false', 'bool');

        NotificationService::sendBookingConfirmedEmail($this->booking);

        Mail::assertNotQueued(\App\Mail\BookingConfirmedEmail::class);
    }

    public function test_notifications_respect_individual_system_toggle(): void
    {
        SystemSetting::set('notify_booking_confirmed', 'false', 'bool');

        NotificationService::sendBookingConfirmedEmail($this->booking);

        Mail::assertNotQueued(\App\Mail\BookingConfirmedEmail::class);
    }

    public function test_user_can_update_notification_preferences(): void
    {
        $response = $this->actingAs($this->user)->put(route('profile.notifications.update'), [
            'booking_confirmed' => false,
            'booking_cancelled' => true,
            'booking_reminder' => false,
            'room_status_changed' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('user_notification_preferences', [
            'user_id' => $this->user->id,
            'booking_confirmed' => false,
            'booking_reminder' => false,
        ]);
    }

    public function test_notification_log_created_on_failure(): void
    {
        Mail::shouldReceive('to->queue')->andThrow(new \Exception('SMTP error'));

        NotificationService::sendWelcomeEmail($this->user, 'temp123');

        $this->assertDatabaseHas('notification_logs', [
            'user_id' => $this->user->id,
            'type' => NotificationLog::TYPE_WELCOME,
            'status' => NotificationLog::STATUS_FAILED,
        ]);
    }

    public function test_room_status_notification_sent_to_affected_users(): void
    {
        $bookings = [
            [
                'user_id' => $this->user->id,
                'reference_number' => 'BK001',
                'booking_date' => now()->addDay()->toDateString(),
                'start_time' => '09:00',
            ],
        ];

        NotificationService::sendRoomStatusChangedEmail($this->room, 'available', 'maintenance', $bookings);

        Mail::assertQueued(\App\Mail\RoomStatusChangedEmail::class);
    }
}
```

---

## Acceptance Criteria

- [ ] `notification_logs` table stores all sent notifications
- [ ] `user_notification_preferences` table stores user preferences
- [ ] Welcome email sent on user creation (with temp password)
- [ ] Booking confirmation email sent on booking creation
- [ ] Booking cancellation email sent on cancellation
- [ ] Booking reminder email sent 24h before (via scheduler)
- [ ] Room status change email sent to affected users
- [ ] System-level master email toggle respected
- [ ] System-level individual toggles respected
- [ ] User preferences respected for optional notifications
- [ ] Failed notifications logged with error message
- [ ] User can view/update preferences via profile
- [ ] Scheduler command registered for reminders
- [ ] All emails have proper markdown templates
- [ ] All tests pass: `php artisan test --filter=NotificationSystemTest`

---

**Next:** [Step 4.6 - Reporting System](./step-4.6-reporting-system.md)
