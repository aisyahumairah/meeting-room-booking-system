<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class AutoApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Room $room;
    protected BookingService $bookingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['status' => 'active']);
        $this->room = Room::factory()->create(['status' => 'active']);
        $this->bookingService = app(BookingService::class);
    }

    public function test_booking_is_auto_approved_immediately()
    {
        $booking = $this->bookingService->createBooking([
            'room_id' => $this->room->id,
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'purpose' => 'Test meeting',
        ], $this->user);

        $this->assertEquals('confirmed', $booking->status);
        $this->assertNotNull($booking->reference_number);
    }

    public function test_cannot_double_book_same_slot()
    {
        // First booking succeeds
        $this->bookingService->createBooking([
            'room_id' => $this->room->id,
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'purpose' => 'First meeting',
        ], $this->user);

        // Second booking for same slot should fail
        $this->expectException(\Exception::class);

        $this->bookingService->createBooking([
            'room_id' => $this->room->id,
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
            'start_time' => '09:30', // Overlaps
            'end_time' => '10:30',
            'purpose' => 'Second meeting',
        ], $this->user);
    }

    public function test_availability_check_api()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('ajax.availability.check'), [
                'room_id' => $this->room->id,
                'date' => now()->addDays(5)->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '10:00',
            ]);

        $response->assertStatus(200)
            ->assertJson(['available' => true]);
    }

    public function test_availability_check_shows_conflict()
    {
        // Create existing booking
        Booking::factory()->create([
            'room_id' => $this->room->id,
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('ajax.availability.check'), [
                'room_id' => $this->room->id,
                'date' => now()->addDays(5)->format('Y-m-d'),
                'start_time' => '09:30',
                'end_time' => '10:30',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'available' => false,
                'reason' => 'booked',
            ]);
    }

    public function test_inactive_room_not_available()
    {
        $inactiveRoom = Room::factory()->create(['status' => 'inactive']);

        $response = $this->actingAs($this->user)
            ->postJson(route('ajax.availability.check'), [
                'room_id' => $inactiveRoom->id,
                'date' => now()->addDays(5)->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '10:00',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'available' => false,
                'reason' => 'room_inactive',
            ]);
    }
}
