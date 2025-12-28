<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Amenity>
 */
class AmenityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $icons = [
            'bx-projector',
            'bx-chalkboard',
            'bx-video',
            'bx-phone',
            'bx-desktop',
            'bx-note',
            'bx-wind',
            'bx-sun',
            'bx-wifi',
            'bx-speaker',
            'bx-tv',
            'bx-microphone',
        ];

        return [
            'name' => fake()->unique()->randomElement([
                'Projector',
                'Whiteboard',
                'Video Conferencing',
                'Phone System',
                'Computer',
                'Flip Chart',
                'Air Conditioning',
                'Natural Light',
                'WiFi',
                'Sound System',
                'TV Display',
                'Microphone',
            ]),
            'icon' => fake()->randomElement($icons),
            'is_active' => true,
        ];
    }
}
