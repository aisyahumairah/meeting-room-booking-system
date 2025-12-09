<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        $floors = ['Ground Floor', '1st Floor', '2nd Floor', '3rd Floor', '4th Floor'];
        $wings = ['East Wing', 'West Wing', 'North Wing', 'South Wing', ''];

        return [
            'name' => 'Room ' . $this->faker->unique()->numberBetween(101, 999),
            'capacity' => $this->faker->randomElement([4, 6, 8, 10, 12, 15, 20, 25, 30]),
            'floor_location' => $this->faker->randomElement($floors) . ($this->faker->boolean(50) ? ', ' . $this->faker->randomElement($wings) : ''),
            'description' => $this->faker->optional(0.7)->paragraph(),
            'status' => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    public function underMaintenance(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'under_maintenance',
        ]);
    }
}
