<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use App\Models\BookingSeries;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RecurringBookingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['status' => 'active']);
        $this->room = Room::factory()->create(['status' => 'active']);
    }

    public function test_can_create_weekly_recurring_booking()
    {
        $response = $this->actingAs($this->user)
            ->post(route('bookings.store-recurring'), [
                'room_id' => $this->room->id,
                'start_date' => now()->addDays(1)->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '10:00',
                'purpose' => 'Weekly team sync',
                'recurrence_type' => 'weekly',
                'recurrence_interval' => 1,
                'days_of_week' => [1, 3], // Mon, Wed
                'end_type' => 'by_occurrences',
                'occurrences' => 4,
            ]);

        $response->dumpSession();
        $response->assertRedirect(route('my-bookings'));
        $this->assertDatabaseCount('booking_series', 1);
        $this->assertEquals(4, Booking::count());
    }

    public function test_all_occurrences_are_confirmed()
    {
        $this->actingAs($this->user)
            ->post(route('bookings.store-recurring'), [
                'room_id' => $this->room->id,
                'start_date' => now()->addDays(1)->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '10:00',
                'purpose' => 'Daily standup',
                'recurrence_type' => 'daily',
                'recurrence_interval' => 1,
                'end_type' => 'by_occurrences',
                'occurrences' => 5,
            ]);

        $statuses = Booking::pluck('status')->unique();
        $this->assertCount(1, $statuses);
        $this->assertEquals('confirmed', $statuses[0]);
    }

    public function test_conflicts_block_entire_series()
    {
        // Create existing booking
        Booking::factory()->create([
            'room_id' => $this->room->id,
            'booking_date' => now()->addDays(3)->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('bookings.store-recurring'), [
                'room_id' => $this->room->id,
                'start_date' => now()->addDays(1)->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '10:00',
                'purpose' => 'Daily standup',
                'recurrence_type' => 'daily',
                'recurrence_interval' => 1,
                'end_type' => 'by_occurrences',
                'occurrences' => 5,
            ]);

        $response->assertSessionHasErrors('room_id');
        $this->assertDatabaseCount('booking_series', 0);
    }
}
