<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Room;
use App\Models\Amenity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_date_cannot_be_in_the_past()
    {
        $user = User::factory()->create();
        $room = Room::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->post(route('bookings.store'), [
            'room_id' => $room->id,
            'booking_date' => now()->subDays(1)->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'purpose' => 'Test meeting',
        ]);

        $response->assertSessionHasErrors('booking_date');
    }

    public function test_booking_end_time_must_be_after_start_time()
    {
        $user = User::factory()->create();
        $room = Room::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->post(route('bookings.store'), [
            'room_id' => $room->id,
            'booking_date' => now()->addDays(1)->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '09:00',
            'purpose' => 'Test meeting',
        ]);

        $response->assertSessionHasErrors('end_time');
    }

    public function test_room_name_must_be_unique()
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        Room::factory()->create(['name' => 'Conference Room A']);

        $response = $this->actingAs($admin)->post(route('admin.rooms.store'), [
            'name' => 'Conference Room A',
            'capacity' => 10,
            'floor_location' => 'Floor 1',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_user_email_must_be_unique()
    {
        $admin = User::factory()->create(['role' => 'director']);
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'staff_number' => 'EMP001',
            'name' => 'New User',
            'email' => 'existing@example.com',
            'role' => 'regular_user',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_amenity_name_must_be_unique()
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        Amenity::factory()->create(['name' => 'Projector']);

        $response = $this->actingAs($admin)->post(route('admin.amenities.store'), [
            'name' => 'Projector',
            'icon' => 'bx-projector',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_room_capacity_must_be_positive()
    {
        $admin = User::factory()->create(['role' => 'administrator']);

        $response = $this->actingAs($admin)->post(route('admin.rooms.store'), [
            'name' => 'New Room',
            'capacity' => 0,
            'floor_location' => 'Floor 1',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('capacity');
    }
}
