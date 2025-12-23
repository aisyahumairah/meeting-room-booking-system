<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\User;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    private static $counter = 0;

    public function definition(): array
    {
        $startHour = $this->faker->numberBetween(8, 16);
        $duration = $this->faker->randomElement([1, 2, 3, 4]); // hours
        $endHour = min($startHour + $duration, 18);

        return [
            'reference_number' => 'BK-' . now()->year . '-' . str_pad(++self::$counter, 5, '0', STR_PAD_LEFT),
            'user_id' => User::factory(),
            'room_id' => Room::factory(),
            'booking_date' => $this->faker->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'start_time' => sprintf('%02d:00', $startHour),
            'end_time' => sprintf('%02d:00', $endHour),
            'purpose' => $this->faker->sentence(10),
            'status' => 'confirmed',
        ];
    }

    /**
     * Set status to confirmed
     */
    public function confirmed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'confirmed',
        ]);
    }

    /**
     * Set status to completed
     */
    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'completed',
            'booking_date' => $this->faker->dateTimeBetween('-30 days', '-1 day')->format('Y-m-d'),
        ]);
    }

    /**
     * Set status to cancelled
     */
    public function cancelled(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'cancelled',
            'cancellation_reason' => $this->faker->sentence(),
            'cancelled_at' => now(),
        ]);
    }
}
