<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\NotificationLog;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendBookingReminders extends Command
{
    protected $signature = 'bookings:send-reminders';
    protected $description = 'Send reminder emails for bookings happening tomorrow';

    public function handle(): int
    {
        $tomorrow = now()->addDay()->toDateString();

        // Get bookings for tomorrow that haven't received reminders yet
        $bookings = Booking::with(['user', 'room'])
            ->whereDate('booking_date', $tomorrow)
            ->where('status', 'confirmed')
            ->whereNotIn('id', function ($query) {
                $query->select('metadata->booking_id')
                    ->from('notification_logs')
                    ->where('type', NotificationLog::TYPE_BOOKING_REMINDER);
            })
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
