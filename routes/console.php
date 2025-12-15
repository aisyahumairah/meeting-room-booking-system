<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule room maintenance status updates every 5 minutes
// Schedule room maintenance status updates every 5 minutes
Schedule::command('rooms:update-maintenance-status')->everyFiveMinutes();

// Run every 15 minutes to mark completed bookings
Schedule::command('bookings:complete-expired')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/scheduler.log'));
