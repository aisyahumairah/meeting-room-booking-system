<?php

namespace Database\Factories;

use App\Models\BookingSeries;
use App\Models\User;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingSeriesFactory extends Factory
{
    protected $model = BookingSeries::class;

    private static $counter = 0;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('now', '+7 days');
        $endDate = $this->faker->dateTimeBetween('+8 days', '+60 days');

        $startHour = $this->faker->numberBetween(8, 16);
        $duration = $this->faker->randomElement([1, 2, 3, 4]); // hours
        $endHour = min($startHour + $duration, 18);

        return [
            'reference_number' => 'BK-SERIES-' . now()->year . '-' . str_pad(++self::$counter, 5, '0', STR_PAD_LEFT),
            'user_id' => User::factory(),
            'room_id' => Room::factory(),
            'recurrence_type' => $this->faker->randomElement(['daily', 'weekly', 'monthly']),
            'recurrence_pattern' => $this->generatePattern(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'start_time' => sprintf('%02d:00', $startHour),
            'end_time' => sprintf('%02d:00', $endHour),
            'purpose' => $this->faker->sentence(10),
        ];
    }

    /**
     * Generate recurrence pattern based on type
     */
    protected function generatePattern(): array
    {
        $type = $this->faker->randomElement(['daily', 'weekly', 'monthly']);

        return match ($type) {
            'daily' => [
                'interval' => $this->faker->numberBetween(1, 7),
            ],
            'weekly' => [
                'interval' => $this->faker->numberBetween(1, 4),
                'days_of_week' => $this->faker->randomElements([1, 2, 3, 4, 5], $this->faker->numberBetween(1, 3)),
            ],
            'monthly' => [
                'interval' => $this->faker->numberBetween(1, 3),
                'day_of_month' => $this->faker->numberBetween(1, 28),
            ],
        };
    }

    /**
     * Set recurrence type to daily
     */
    public function daily(): static
    {
        return $this->state(fn(array $attributes) => [
            'recurrence_type' => 'daily',
            'recurrence_pattern' => [
                'interval' => $this->faker->numberBetween(1, 7),
            ],
        ]);
    }

    /**
     * Set recurrence type to weekly
     */
    public function weekly(): static
    {
        return $this->state(fn(array $attributes) => [
            'recurrence_type' => 'weekly',
            'recurrence_pattern' => [
                'interval' => $this->faker->numberBetween(1, 4),
                'days_of_week' => $this->faker->randomElements([1, 2, 3, 4, 5], $this->faker->numberBetween(1, 3)),
            ],
        ]);
    }

    /**
     * Set recurrence type to monthly
     */
    public function monthly(): static
    {
        return $this->state(fn(array $attributes) => [
            'recurrence_type' => 'monthly',
            'recurrence_pattern' => [
                'interval' => $this->faker->numberBetween(1, 3),
                'day_of_month' => $this->faker->numberBetween(1, 28),
            ],
        ]);
    }
}
