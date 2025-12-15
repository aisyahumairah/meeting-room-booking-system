<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use App\Models\BookingSeries;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CancelBookingTest extends TestCase
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

    public function test_user_can_cancel_own_confirmed_booking()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'confirmed',
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('my-bookings.destroy', $booking), [
                'cancellation_reason' => 'Meeting rescheduled',
            ]);

        $response->assertRedirect(route('my-bookings'));
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Meeting rescheduled',
            'cancelled_by' => $this->user->id,
        ]);
    }

    public function test_cancellation_reason_is_required()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'confirmed',
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('my-bookings.destroy', $booking), [
                'cancellation_reason' => '',
            ]);

        $response->assertSessionHasErrors('cancellation_reason');
    }

    public function test_user_cannot_cancel_others_booking()
    {
        $otherUser = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('my-bookings.destroy', $booking), [
                'cancellation_reason' => 'Test',
            ]);

        $response->assertStatus(403);
    }

    public function test_user_cannot_cancel_already_cancelled_booking()
    {
        $booking = Booking::factory()->cancelled()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('my-bookings.destroy', $booking), [
                'cancellation_reason' => 'Test',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_cancel_any_booking()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'confirmed',
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('my-bookings.destroy', $booking), [
                'cancellation_reason' => 'Admin cancelled - room needed',
            ]);

        $response->assertRedirect(route('admin.bookings.index'));
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
            'cancelled_by' => $this->admin->id,
        ]);
    }

    public function test_cancelling_recurring_booking_cancels_entire_series()
    {
        // Create a series with multiple bookings
        $series = BookingSeries::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
        ]);

        $bookings = [];
        for ($i = 0; $i < 5; $i++) {
            $bookings[] = Booking::factory()->create([
                'user_id' => $this->user->id,
                'room_id' => $this->room->id,
                'series_id' => $series->id,
                'status' => 'confirmed',
                'booking_date' => now()->addDays($i + 1)->format('Y-m-d'),
            ]);
        }

        // Cancel using first booking
        $response = $this->actingAs($this->user)
            ->delete(route('my-bookings.destroy', $bookings[0]), [
                'cancellation_reason' => 'Series no longer needed',
            ]);

        $response->assertRedirect(route('my-bookings'));

        // All bookings in series should be cancelled
        foreach ($bookings as $booking) {
            $this->assertDatabaseHas('bookings', [
                'id' => $booking->id,
                'status' => 'cancelled',
            ]);
        }
    }

    public function test_cancelled_at_timestamp_is_recorded()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'confirmed',
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $this->actingAs($this->user)
            ->delete(route('my-bookings.destroy', $booking), [
                'cancellation_reason' => 'Test',
            ]);

        $booking->refresh();
        $this->assertNotNull($booking->cancelled_at);
        $this->assertTrue($booking->cancelled_at->isToday());
    }

    public function test_room_becomes_available_after_cancellation()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'status' => 'confirmed',
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '11:00',
        ]);

        // Room should not be available before cancellation
        $this->assertFalse($this->room->isAvailable(
            now()->addDays(5)->format('Y-m-d'),
            '09:00',
            '11:00'
        ));

        // Cancel booking
        $this->actingAs($this->user)
            ->delete(route('my-bookings.destroy', $booking), [
                'cancellation_reason' => 'Test',
            ]);

        // Room should be available after cancellation
        $this->assertTrue($this->room->isAvailable(
            now()->addDays(5)->format('Y-m-d'),
            '09:00',
            '11:00'
        ));
    }
}
