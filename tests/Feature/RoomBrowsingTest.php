<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\RoomMaintenanceSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomBrowsingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AmenitySeeder::class);

        $this->user = User::factory()->regularUser()->passwordChanged()->create();
        $this->admin = User::factory()->administrator()->passwordChanged()->create();
    }

    // =====================
    // ROOM GRID TESTS
    // =====================

    public function test_user_can_view_room_grid(): void
    {
        Room::factory()->count(3)->create(['status' => 'active']);

        $response = $this->actingAs($this->user)
            ->get(route('rooms.index'));

        $response->assertStatus(200);
        $response->assertViewIs('rooms.index');
        $response->assertViewHas('rooms');
        $response->assertViewHas('amenities');
        $this->assertEquals(3, $response->viewData('rooms')->total());
    }

    public function test_user_only_sees_active_rooms(): void
    {
        Room::factory()->create(['status' => 'active', 'name' => 'ActiveTestRoom']);
        Room::factory()->create(['status' => 'inactive', 'name' => 'InactiveTestRoom']);
        Room::factory()->create(['status' => 'under_maintenance', 'name' => 'MaintenanceTestRoom']);

        $response = $this->actingAs($this->user)
            ->get(route('rooms.index'));

        $response->assertStatus(200);
        // Active rooms show in grid
        $response->assertSee('ActiveTestRoom');
        // Total count only includes active rooms
        $this->assertEquals(1, $response->viewData('rooms')->total());
    }

    public function test_room_cards_show_required_information(): void
    {
        $room = Room::factory()->create([
            'name' => 'Test Conference Room',
            'capacity' => 20,
            'floor_location' => 'Level 5',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('rooms.index'));

        $response->assertStatus(200);
        $response->assertSee('Test Conference Room');
        $response->assertSee('20 people');
        $response->assertSee('Level 5');
        $response->assertSee('View Details');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('rooms.index'));

        $response->assertRedirect(route('login'));
    }

    // =====================
    // ROOM DETAIL TESTS
    // =====================

    public function test_user_can_view_room_detail(): void
    {
        $room = Room::factory()->create(['status' => 'active']);

        $response = $this->actingAs($this->user)
            ->get(route('rooms.show', $room));

        $response->assertStatus(200);
        $response->assertViewIs('rooms.show');
        $response->assertViewHas('room');
        $response->assertSee($room->name);
    }

    public function test_room_detail_shows_specifications(): void
    {
        $room = Room::factory()->create([
            'name' => 'Detail Test Room',
            'capacity' => 15,
            'floor_location' => 'Building A',
            'description' => 'A beautiful meeting room',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('rooms.show', $room));

        $response->assertStatus(200);
        $response->assertSee('Detail Test Room');
        $response->assertSee('15 people');
        $response->assertSee('Building A');
        $response->assertSee('A beautiful meeting room');
    }

    public function test_room_under_maintenance_shows_badge(): void
    {
        $room = Room::factory()->create(['status' => 'under_maintenance']);

        $response = $this->actingAs($this->admin)
            ->get(route('rooms.show', $room));

        $response->assertStatus(200);
        $response->assertSee('Under Maintenance');
    }

    public function test_book_button_disabled_for_maintenance_room(): void
    {
        $room = Room::factory()->create(['status' => 'under_maintenance']);

        $response = $this->actingAs($this->admin)
            ->get(route('rooms.show', $room));

        $response->assertStatus(200);
        $response->assertSee('disabled');
        $response->assertSee('Under Maintenance');
    }

    public function test_regular_user_cannot_view_inactive_room(): void
    {
        $room = Room::factory()->create(['status' => 'inactive']);

        $response = $this->actingAs($this->user)
            ->get(route('rooms.show', $room));

        $response->assertStatus(404);
    }

    public function test_admin_can_view_inactive_room(): void
    {
        $room = Room::factory()->create(['status' => 'inactive']);

        $response = $this->actingAs($this->admin)
            ->get(route('rooms.show', $room));

        $response->assertStatus(200);
    }

    // =====================
    // AVAILABILITY API TESTS
    // =====================

    public function test_availability_api_returns_correct_format(): void
    {
        $room = Room::factory()->create(['status' => 'active']);

        $response = $this->actingAs($this->user)
            ->getJson(route('api.rooms.availability', [
                'room' => $room,
                'start' => now()->startOfWeek()->toDateString(),
                'end' => now()->endOfWeek()->toDateString(),
            ]));

        $response->assertStatus(200);
        $response->assertJsonIsArray();
    }

    public function test_availability_api_includes_maintenance_periods(): void
    {
        $room = Room::factory()->create(['status' => 'active']);

        RoomMaintenanceSchedule::create([
            'room_id' => $room->id,
            'start_datetime' => now()->addDay(),
            'end_datetime' => now()->addDays(2),
            'reason' => 'Test Maintenance',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('api.rooms.availability', [
                'room' => $room,
                'start' => now()->toDateString(),
                'end' => now()->addDays(7)->toDateString(),
            ]));

        $response->assertStatus(200);
        $response->assertJsonFragment(['backgroundColor' => '#dc3545']); // Maintenance color
    }

    // =====================
    // RESPONSIVE & NAVIGATION
    // =====================

    public function test_room_grid_is_paginated(): void
    {
        Room::factory()->count(15)->create(['status' => 'active']);

        $response = $this->actingAs($this->user)
            ->get(route('rooms.index'));

        $response->assertStatus(200);
        // Only 12 rooms per page
        $rooms = $response->viewData('rooms');
        $this->assertEquals(12, $rooms->perPage());
    }
}
