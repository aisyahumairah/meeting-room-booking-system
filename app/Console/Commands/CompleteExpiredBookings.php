<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BookingStatusService;

class CompleteExpiredBookings extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'bookings:complete-expired 
                            {--dry-run : Preview what would be updated without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Update confirmed bookings to completed status after their end time has passed';

    /**
     * Execute the console command.
     */
    public function handle(BookingStatusService $statusService): int
    {
        $this->info('Checking for expired bookings...');

        if ($this->option('dry-run')) {
            $expired = $statusService->getExpiredBookings();

            if ($expired->isEmpty()) {
                $this->info('No expired bookings found.');
                return Command::SUCCESS;
            }

            $this->warn("DRY RUN - {$expired->count()} booking(s) would be updated:");
            $this->table(
                ['Reference', 'Date', 'Time', 'Room'],
                $expired->map(fn($b) => [
                    $b->reference_number,
                    $b->booking_date->format('Y-m-d'),
                    $b->time_range,
                    $b->room->name ?? 'N/A',
                ])
            );
            return Command::SUCCESS;
        }

        $count = $statusService->completeAllExpired();

        if ($count === 0) {
            $this->info('No expired bookings to complete.');
        } else {
            $this->info("Successfully completed {$count} booking(s).");
        }

        return Command::SUCCESS;
    }
}
