<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule room maintenance status updates every 5 minutes
Schedule::command('rooms:update-maintenance-status')->everyFiveMinutes();

// Schedule booking reminders to be sent daily at 9:00 AM
Schedule::command('bookings:send-reminders')->dailyAt('09:00');
