<?php

namespace Database\Seeders;

use App\Models\Amenity;
use Illuminate\Database\Seeder;

class AmenitySeeder extends Seeder
{
    public function run(): void
    {
        $amenities = [
            ['name' => 'Projector', 'icon' => 'bx-projector'],
            ['name' => 'Whiteboard', 'icon' => 'bx-chalkboard'],
            ['name' => 'Video Conferencing', 'icon' => 'bx-video'],
            ['name' => 'Teleconferencing Phone', 'icon' => 'bx-phone'],
            ['name' => 'Computer/Monitor', 'icon' => 'bx-desktop'],
            ['name' => 'Flip Chart', 'icon' => 'bx-note'],
            ['name' => 'Air Conditioning', 'icon' => 'bx-wind'],
            ['name' => 'Natural Light/Windows', 'icon' => 'bx-sun'],
        ];

        foreach ($amenities as $amenity) {
            Amenity::firstOrCreate(
                ['name' => $amenity['name']],
                ['icon' => $amenity['icon']]
            );
        }
    }
}
