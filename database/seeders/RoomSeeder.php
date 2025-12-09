<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\Amenity;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure amenities exist
        $this->call(AmenitySeeder::class);

        $amenityIds = Amenity::pluck('id')->toArray();

        // Create sample rooms
        $rooms = [
            [
                'name' => 'Boardroom A',
                'capacity' => 20,
                'floor_location' => '3rd Floor, East Wing',
                'description' => 'Executive boardroom with panoramic views. Ideal for board meetings and important presentations.',
                'status' => 'active',
            ],
            [
                'name' => 'Meeting Room 101',
                'capacity' => 8,
                'floor_location' => '1st Floor',
                'description' => 'Small meeting room suitable for team discussions.',
                'status' => 'active',
            ],
            [
                'name' => 'Meeting Room 102',
                'capacity' => 6,
                'floor_location' => '1st Floor',
                'description' => 'Compact meeting space for quick huddles.',
                'status' => 'active',
            ],
            [
                'name' => 'Conference Room B',
                'capacity' => 15,
                'floor_location' => '2nd Floor, West Wing',
                'description' => 'Mid-sized conference room with video conferencing capabilities.',
                'status' => 'active',
            ],
            [
                'name' => 'Training Room',
                'capacity' => 30,
                'floor_location' => 'Ground Floor',
                'description' => 'Large room for training sessions and workshops.',
                'status' => 'active',
            ],
            [
                'name' => 'Meeting Room 201',
                'capacity' => 10,
                'floor_location' => '2nd Floor',
                'description' => null,
                'status' => 'inactive',
            ],
        ];

        foreach ($rooms as $roomData) {
            $room = Room::create($roomData);

            // Attach random amenities (3-6 per room)
            $randomAmenities = collect($amenityIds)->random(rand(3, min(6, count($amenityIds))));
            $room->amenities()->attach($randomAmenities);
        }
    }
}
