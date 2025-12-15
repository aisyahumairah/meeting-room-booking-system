<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingCalendarTest extends TestCase
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

    public function test_user_can_view_calendar()
    {
        $response = $this->actingAs($this->user)
            ->get(route('calendar'));

        $response->assertStatus(200);
        $response->assertViewIs('bookings.calendar');
    }

    public function test_calendar_events_returns_own_bookings_only_for_regular_user()
    {
        $ownBooking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'status' => 'confirmed',
            'booking_date' => now()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);

        $otherBooking = Booking::factory()->create([
            'room_id' => $this->room->id,
            'status' => 'confirmed',
            'booking_date' => now()->format('Y-m-d'),
            'start_time' => '11:00',
            'end_time' => '12:00',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('ajax.calendar.events', [
                'start' => now()->startOfMonth()->format('Y-m-d'),
                'end' => now()->endOfMonth()->format('Y-m-d'),
            ]));

        $response->assertStatus(200);
        $events = $response->json();

        // Regular user should see only their own booking as a clickable event
        // But wait, the calendar requirements often say regular users should see availability
        // The implementation in CalendarController.php says:
        // if (!$canViewAll) { $query->where('user_id', $user->id); }
        // So regular users ONLY see THEIR OWN bookings on the calendar?
        // That means they can't see "Someone booked this" slots?
        // Let's check the objective: "Users see only their bookings, while Admin/Director see all bookings."
        // That seems to be the requirement.

        $this->assertCount(1, $events);
        $this->assertEquals($ownBooking->id, $events[0]['id']);
    }

    public function test_admin_sees_all_bookings()
    {
        Booking::factory()->count(3)->create([
            'room_id' => $this->room->id,
            'status' => 'confirmed',
            'booking_date' => now()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('ajax.calendar.events', [
                'start' => now()->startOfMonth()->format('Y-m-d'),
                'end' => now()->endOfMonth()->format('Y-m-d'),
            ]));

        $events = $response->json();
        $this->assertCount(3, $events);
    }

    public function test_cancelled_bookings_not_shown()
    {
        Booking::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'status' => 'confirmed',
            'booking_date' => now()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);

        Booking::factory()->cancelled()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'booking_date' => now()->format('Y-m-d'),
            'start_time' => '11:00',
            'end_time' => '12:00',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('ajax.calendar.events', [
                'start' => now()->startOfMonth()->format('Y-m-d'),
                'end' => now()->endOfMonth()->format('Y-m-d'),
            ]));

        $events = $response->json();
        $this->assertCount(1, $events);
    }

    public function test_room_filter_works()
    {
        $room1 = Room::factory()->create();
        $room2 = Room::factory()->create();

        Booking::factory()->create([
            'user_id' => $this->admin->id,
            'room_id' => $room1->id,
            'status' => 'confirmed',
            'booking_date' => now()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);

        Booking::factory()->create([
            'user_id' => $this->admin->id,
            'room_id' => $room2->id,
            'status' => 'confirmed',
            'booking_date' => now()->format('Y-m-d'),
            'start_time' => '11:00',
            'end_time' => '12:00',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('ajax.calendar.events', [
                'start' => now()->startOfMonth()->format('Y-m-d'),
                'end' => now()->endOfMonth()->format('Y-m-d'),
                'room_id' => $room1->id,
            ]));

        $events = $response->json();
        $this->assertCount(1, $events);
    }

    public function test_events_have_correct_urls()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'confirmed',
            'booking_date' => now()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('ajax.calendar.events', [
                'start' => now()->startOfMonth()->format('Y-m-d'),
                'end' => now()->endOfMonth()->format('Y-m-d'),
            ]));

        $events = $response->json();
        $this->assertStringContainsString('my-bookings', $events[0]['url']);
    }
}
