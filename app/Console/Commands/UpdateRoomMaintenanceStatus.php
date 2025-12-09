<?php

namespace App\Console\Commands;

use App\Models\RoomMaintenanceSchedule;
use Illuminate\Console\Command;

class UpdateRoomMaintenanceStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rooms:update-maintenance-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update room statuses based on maintenance schedules';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking room maintenance schedules...');

        // Find schedules that should start now → set room status to `under_maintenance`
        $schedulesToStart = RoomMaintenanceSchedule::shouldStartNow()->with('room')->get();

        foreach ($schedulesToStart as $schedule) {
            $room = $schedule->room;
            $room->update(['status' => 'under_maintenance']);
            $this->line("  → Room '{$room->name}' set to under_maintenance (Reason: {$schedule->reason})");
        }

        if ($schedulesToStart->count() > 0) {
            $this->info("Started maintenance for {$schedulesToStart->count()} room(s).");
        }

        // Find schedules that should end now → revert room status to `active`
        $schedulesToEnd = RoomMaintenanceSchedule::shouldEndNow()->with('room')->get();

        foreach ($schedulesToEnd as $schedule) {
            $room = $schedule->room;

            // Only revert if no other active maintenance schedules exist for this room
            $otherActiveSchedules = RoomMaintenanceSchedule::where('room_id', $room->id)
                ->where('id', '!=', $schedule->id)
                ->active()
                ->exists();

            if (!$otherActiveSchedules) {
                $room->update(['status' => 'active']);
                $this->line("  → Room '{$room->name}' reverted to active");
            }
        }

        if ($schedulesToEnd->count() > 0) {
            $this->info("Ended maintenance for {$schedulesToEnd->count()} room(s).");
        }

        if ($schedulesToStart->count() === 0 && $schedulesToEnd->count() === 0) {
            $this->info('No maintenance status changes needed.');
        }

        return Command::SUCCESS;
    }
}
