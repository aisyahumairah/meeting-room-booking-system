<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Booking;
use App\Models\User;
use App\Models\Room;
use Carbon\Carbon;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $rooms = Room::active()->get();

        if ($users->isEmpty() || $rooms->isEmpty()) {
            $this->command->warn('No users or rooms found. Skipping booking seeder.');
            return;
        }

        $purposes = [
            'Team Weekly Standup',
            'Client Presentation',
            'Project Kickoff Meeting',
            'Sprint Planning',
            'Technical Review',
            'Design Workshop',
            'Training Session',
            'Interview',
            'Department Meeting',
            'One-on-One Discussion',
        ];

        // Create some upcoming confirmed bookings
        foreach (range(1, 15) as $i) {
            $user = $users->random();
            $room = $rooms->random();
            $date = Carbon::now()->addDays(rand(1, 30));
            $startHour = rand(8, 15);
            $duration = rand(1, 3);

            // Check availability before creating
            $startTime = sprintf('%02d:00', $startHour);
            $endTime = sprintf('%02d:00', min($startHour + $duration, 18));

            if ($room->isAvailable($date->format('Y-m-d'), $startTime, $endTime)) {
                Booking::create([
                    'reference_number' => Booking::generateReferenceNumber(),
                    'user_id' => $user->id,
                    'room_id' => $room->id,
                    'booking_date' => $date->format('Y-m-d'),
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'purpose' => $purposes[array_rand($purposes)],
                    'status' => 'confirmed',
                ]);
            }
        }

        // Create some past completed bookings
        foreach (range(1, 10) as $i) {
            $user = $users->random();
            $room = $rooms->random();
            $date = Carbon::now()->subDays(rand(1, 60));
            $startHour = rand(8, 15);
            $duration = rand(1, 3);

            Booking::create([
                'reference_number' => Booking::generateReferenceNumber(),
                'user_id' => $user->id,
                'room_id' => $room->id,
                'booking_date' => $date->format('Y-m-d'),
                'start_time' => sprintf('%02d:00', $startHour),
                'end_time' => sprintf('%02d:00', min($startHour + $duration, 18)),
                'purpose' => $purposes[array_rand($purposes)],
                'status' => 'completed',
            ]);
        }

        // Create a few cancelled bookings
        foreach (range(1, 5) as $i) {
            $user = $users->random();
            $room = $rooms->random();
            $date = Carbon::now()->addDays(rand(1, 14));

            Booking::create([
                'reference_number' => Booking::generateReferenceNumber(),
                'user_id' => $user->id,
                'room_id' => $room->id,
                'booking_date' => $date->format('Y-m-d'),
                'start_time' => sprintf('%02d:00', rand(8, 14)),
                'end_time' => sprintf('%02d:00', rand(15, 18)),
                'purpose' => $purposes[array_rand($purposes)],
                'status' => 'cancelled',
                'cancellation_reason' => 'Meeting rescheduled',
                'cancelled_by' => $user->id,
                'cancelled_at' => now(),
            ]);
        }

        $this->command->info('Created ' . Booking::count() . ' sample bookings.');
    }
}
