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

            self::logNotification(
                $user,
                NotificationLog::TYPE_WELCOME,
                $user->email,
                'Welcome to MRBS',
                ['temp_password_included' => true]
            );
        } catch (\Exception $e) {
            self::logNotification(
                $user,
                NotificationLog::TYPE_WELCOME,
                $user->email,
                'Welcome to MRBS',
                [],
                NotificationLog::STATUS_FAILED,
                $e->getMessage()
            );
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

            self::logNotification(
                $user,
                NotificationLog::TYPE_BOOKING_CONFIRMED,
                $user->email,
                "Booking Confirmed: {$booking->reference_number}",
                ['booking_id' => $booking->id, 'reference' => $booking->reference_number]
            );
        } catch (\Exception $e) {
            self::logNotification(
                $user,
                NotificationLog::TYPE_BOOKING_CONFIRMED,
                $user->email,
                "Booking Confirmed: {$booking->reference_number}",
                ['booking_id' => $booking->id],
                NotificationLog::STATUS_FAILED,
                $e->getMessage()
            );
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

            self::logNotification(
                $user,
                NotificationLog::TYPE_BOOKING_CANCELLED,
                $user->email,
                "Booking Cancelled: {$booking->reference_number}",
                ['booking_id' => $booking->id, 'reference' => $booking->reference_number, 'reason' => $reason]
            );
        } catch (\Exception $e) {
            self::logNotification(
                $user,
                NotificationLog::TYPE_BOOKING_CANCELLED,
                $user->email,
                "Booking Cancelled: {$booking->reference_number}",
                ['booking_id' => $booking->id],
                NotificationLog::STATUS_FAILED,
                $e->getMessage()
            );
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

            self::logNotification(
                $user,
                NotificationLog::TYPE_BOOKING_REMINDER,
                $user->email,
                "Reminder: Upcoming Booking - {$booking->reference_number}",
                ['booking_id' => $booking->id, 'reference' => $booking->reference_number]
            );
        } catch (\Exception $e) {
            self::logNotification(
                $user,
                NotificationLog::TYPE_BOOKING_REMINDER,
                $user->email,
                "Reminder: Upcoming Booking",
                ['booking_id' => $booking->id],
                NotificationLog::STATUS_FAILED,
                $e->getMessage()
            );
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

                self::logNotification(
                    $user,
                    NotificationLog::TYPE_ROOM_STATUS_CHANGED,
                    $user->email,
                    "Room Status Changed: {$room->name}",
                    ['room_id' => $room->id, 'old_status' => $oldStatus, 'new_status' => $newStatus, 'affected_bookings' => $userBookings->count()]
                );
            } catch (\Exception $e) {
                self::logNotification(
                    $user,
                    NotificationLog::TYPE_ROOM_STATUS_CHANGED,
                    $user->email,
                    "Room Status Changed: {$room->name}",
                    ['room_id' => $room->id],
                    NotificationLog::STATUS_FAILED,
                    $e->getMessage()
                );
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
