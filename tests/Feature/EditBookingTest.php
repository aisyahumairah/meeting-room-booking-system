<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EditBookingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;
    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['status' => 'active', 'role' => 'regular_user']);
        $this->admin = User::factory()->create(['status' => 'active', 'role' => 'administrator']);
        $this->room = Room::factory()->create(['status' => 'active']);
    }

    public function test_user_can_edit_own_confirmed_booking()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'status' => 'confirmed',
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('my-bookings.update', $booking), [
                'room_id' => $this->room->id,
                'booking_date' => now()->addDays(7)->format('Y-m-d'),
                'start_time' => '10:00',
                'end_time' => '12:00',
                'purpose' => 'Updated purpose',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'purpose' => 'Updated purpose',
            'status' => 'confirmed', // Status unchanged
        ]);
    }

    public function test_user_cannot_edit_others_booking()
    {
        $otherUser = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('my-bookings.edit', $booking));

        $response->assertStatus(403);
    }

    public function test_user_cannot_edit_cancelled_booking()
    {
        $booking = Booking::factory()->cancelled()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('my-bookings.edit', $booking));

        $response->assertRedirect();
    }

    public function test_admin_can_edit_any_booking()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'status' => 'confirmed',
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('my-bookings.update', $booking), [
                'room_id' => $this->room->id,
                'booking_date' => now()->addDays(10)->format('Y-m-d'),
                'start_time' => '14:00',
                'end_time' => '16:00',
                'purpose' => 'Admin updated this',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'purpose' => 'Admin updated this',
        ]);
    }

    public function test_status_remains_unchanged_after_edit()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'status' => 'confirmed',
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $this->actingAs($this->user)
            ->put(route('my-bookings.update', $booking), [
                'room_id' => $this->room->id,
                'booking_date' => now()->addDays(7)->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '11:00',
                'purpose' => 'Changed purpose',
            ]);

        $booking->refresh();
        $this->assertEquals('confirmed', $booking->status);
    }

    public function test_edit_validates_availability()
    {
        // Create existing booking
        Booking::factory()->create([
            'room_id' => $this->room->id,
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
            'start_time' => '14:00',
            'end_time' => '16:00',
            'status' => 'confirmed',
        ]);

        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'booking_date' => now()->addDays(6)->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'status' => 'confirmed',
        ]);

        // Try to move my booking to the taken slot
        $response = $this->actingAs($this->user)
            ->put(route('my-bookings.update', $booking), [
                'room_id' => $this->room->id,
                'booking_date' => now()->addDays(5)->format('Y-m-d'),
                'start_time' => '14:30', // Overlap
                'end_time' => '15:30',
                'purpose' => 'Conflict purpose',
            ]);

        $response->assertSessionHasErrors(['room_id']); // Service throws generic exception which controller catches and puts in room_id error
    }
}
