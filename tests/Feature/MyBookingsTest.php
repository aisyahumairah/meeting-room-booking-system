<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyBookingsTest extends TestCase
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

    public function test_user_can_view_my_bookings()
    {
        Booking::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->get(route('my-bookings'));

        $response->assertStatus(200);
        $response->assertViewIs('bookings.my');
        $response->assertViewHas('bookings');
    }

    public function test_cannot_view_others_booking()
    {
        $otherUser = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)
            ->get(route('my-bookings.show', $booking));

        $response->assertStatus(403);
    }

    public function test_calendar_ajax_returns_events()
    {
        $this->withoutExceptionHandling();
        Booking::factory()->create([
            'user_id' => $this->user->id,
            'booking_date' => now()->addDay(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('ajax.my-bookings.calendar', [
                'start' => now()->format('Y-m-d'),
                'end' => now()->addDays(7)->format('Y-m-d'),
            ]));

        $response->assertStatus(200);
        $response->assertJsonCount(1);
    }
}
