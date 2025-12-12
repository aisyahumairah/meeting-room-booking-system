<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Booking;
use App\Models\BookingSeries;
use App\Models\User;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_belongs_to_user()
    {
        $booking = Booking::factory()->create();
        $this->assertInstanceOf(User::class, $booking->user);
    }

    public function test_booking_belongs_to_room()
    {
        $booking = Booking::factory()->create();
        $this->assertInstanceOf(Room::class, $booking->room);
    }

    public function test_reference_number_is_unique()
    {
        $ref1 = Booking::generateReferenceNumber();
        Booking::factory()->create(['reference_number' => $ref1]);

        $ref2 = Booking::generateReferenceNumber();
        $this->assertNotEquals($ref1, $ref2);
    }

    public function test_reference_number_format()
    {
        $ref = Booking::generateReferenceNumber();
        $year = now()->year;
        $this->assertMatchesRegularExpression("/^BK-{$year}-\d{5}$/", $ref);
    }

    public function test_duration_accessor()
    {
        $booking = Booking::factory()->create([
            'start_time' => '09:00',
            'end_time' => '11:30',
        ]);

        $this->assertEquals('2h 30m', $booking->duration);
    }

    public function test_status_badge_confirmed()
    {
        $booking = Booking::factory()->confirmed()->create();
        $this->assertStringContainsString('bg-success', $booking->status_badge);
    }

    public function test_confirmed_booking_is_editable()
    {
        $booking = Booking::factory()->create([
            'status' => 'confirmed',
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
        ]);
        $this->assertTrue($booking->is_editable);
    }

    public function test_completed_booking_is_not_editable()
    {
        $booking = Booking::factory()->completed()->create();
        $this->assertFalse($booking->is_editable);
    }

    public function test_cancelled_booking_is_not_cancellable()
    {
        $booking = Booking::factory()->cancelled()->create();
        $this->assertFalse($booking->is_cancellable);
    }

    public function test_upcoming_scope()
    {
        Booking::factory()->create([
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
            'status' => 'confirmed',
        ]);
        Booking::factory()->completed()->create();

        $this->assertEquals(1, Booking::upcoming()->count());
    }

    public function test_time_range_accessor()
    {
        $booking = Booking::factory()->create([
            'start_time' => '09:00',
            'end_time' => '11:00',
        ]);

        $this->assertEquals('09:00 - 11:00', $booking->time_range);
    }

    public function test_is_recurring_accessor()
    {
        $booking = Booking::factory()->create(['series_id' => null]);
        $this->assertFalse($booking->is_recurring);
    }

    public function test_for_user_scope()
    {
        $user = User::factory()->create();
        Booking::factory()->create(['user_id' => $user->id]);
        Booking::factory()->create(); // Different user

        $this->assertEquals(1, Booking::forUser($user->id)->count());
    }

    public function test_for_room_scope()
    {
        $room = Room::factory()->create();
        Booking::factory()->create(['room_id' => $room->id]);
        Booking::factory()->create(); // Different room

        $this->assertEquals(1, Booking::forRoom($room->id)->count());
    }

    public function test_on_date_scope()
    {
        $date = now()->addDays(3)->format('Y-m-d');
        Booking::factory()->create(['booking_date' => $date]);
        Booking::factory()->create(['booking_date' => now()->addDays(5)->format('Y-m-d')]);

        $this->assertEquals(1, Booking::onDate($date)->count());
    }

    public function test_duration_minutes_accessor()
    {
        $booking = Booking::factory()->create([
            'start_time' => '09:00',
            'end_time' => '11:30',
        ]);

        $this->assertEquals(150, $booking->duration_minutes);
    }
}
