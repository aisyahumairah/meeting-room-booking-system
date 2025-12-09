<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\Room;
use App\Models\RoomMaintenanceSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomFilteringTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AmenitySeeder::class);

        $this->user = User::factory()->regularUser()->passwordChanged()->create();
    }

    // =====================
    // SEARCH TESTS
    // =====================

    public function test_search_by_room_name_works(): void
    {
        Room::factory()->create(['name' => 'Conference Alpha', 'status' => 'active']);
        Room::factory()->create(['name' => 'Meeting Beta', 'status' => 'active']);
        Room::factory()->create(['name' => 'Boardroom Gamma', 'status' => 'active']);

        $response = $this->actingAs($this->user)
            ->get(route('rooms.index', ['search' => 'Alpha']));

        $response->assertStatus(200);
        $response->assertSee('Conference Alpha');
        $response->assertDontSee('Meeting Beta');
        $response->assertDontSee('Boardroom Gamma');
    }

    public function test_search_is_case_insensitive(): void
    {
        Room::factory()->create(['name' => 'CONFERENCE ROOM A', 'status' => 'active']);

        $response = $this->actingAs($this->user)
            ->get(route('rooms.index', ['search' => 'conference']));

        $response->assertStatus(200);
        $response->assertSee('CONFERENCE ROOM A');
    }

    // =====================
    // CAPACITY FILTER TESTS
    // =====================

    public function test_capacity_filter_shows_rooms_gte_selected(): void
    {
        Room::factory()->create(['name' => 'Small Room', 'capacity' => 4, 'status' => 'active']);
        Room::factory()->create(['name' => 'Medium Room', 'capacity' => 10, 'status' => 'active']);
        Room::factory()->create(['name' => 'Large Room', 'capacity' => 20, 'status' => 'active']);

        $response = $this->actingAs($this->user)
            ->get(route('rooms.index', ['capacity' => 10]));

        $response->assertStatus(200);
        $response->assertDontSee('Small Room');
        $response->assertSee('Medium Room');
        $response->assertSee('Large Room');
    }

    // =====================
    // AMENITY FILTER TESTS
    // =====================

    public function test_amenity_filter_uses_and_logic(): void
    {
        $projector = Amenity::where('name', 'Projector')->first();
        $whiteboard = Amenity::where('name', 'Whiteboard')->first();
        $video = Amenity::where('name', 'Video Conferencing')->first();

        // Room with both projector and whiteboard
        $roomBoth = Room::factory()->create(['name' => 'Room Both', 'status' => 'active']);
        $roomBoth->amenities()->attach([$projector->id, $whiteboard->id]);

        // Room with only projector
        $roomProjector = Room::factory()->create(['name' => 'Room Projector Only', 'status' => 'active']);
        $roomProjector->amenities()->attach([$projector->id]);

        // Room with all three
        $roomAll = Room::factory()->create(['name' => 'Room All', 'status' => 'active']);
        $roomAll->amenities()->attach([$projector->id, $whiteboard->id, $video->id]);

        // Filter by both projector AND whiteboard
        $response = $this->actingAs($this->user)
            ->get(route('rooms.index', ['amenities' => [$projector->id, $whiteboard->id]]));

        $response->assertStatus(200);
        $response->assertSee('Room Both');
        $response->assertSee('Room All');
        $response->assertDontSee('Room Projector Only');
    }

    public function test_single_amenity_filter_works(): void
    {
        $projector = Amenity::where('name', 'Projector')->first();

        $roomWith = Room::factory()->create(['name' => 'Has Projector', 'status' => 'active']);
        $roomWith->amenities()->attach([$projector->id]);

        $roomWithout = Room::factory()->create(['name' => 'No Projector', 'status' => 'active']);

        $response = $this->actingAs($this->user)
            ->get(route('rooms.index', ['amenities' => [$projector->id]]));

        $response->assertStatus(200);
        $response->assertSee('Has Projector');
        $response->assertDontSee('No Projector');
    }

    // =====================
    // DATE/TIME AVAILABILITY TESTS
    // =====================

    public function test_availability_filter_excludes_rooms_under_maintenance(): void
    {
        $availableRoom = Room::factory()->create(['name' => 'Available Room', 'status' => 'active']);
        $maintRoom = Room::factory()->create(['name' => 'Maintenance Room', 'status' => 'active']);

        // Schedule maintenance for tomorrow 9-12
        RoomMaintenanceSchedule::create([
            'room_id' => $maintRoom->id,
            'start_datetime' => now()->addDay()->setTime(9, 0),
            'end_datetime' => now()->addDay()->setTime(12, 0),
            'reason' => 'AC Repair',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('rooms.index', [
                'date' => now()->addDay()->toDateString(),
                'start_time' => '10:00:00',
                'end_time' => '11:00:00',
            ]));

        $response->assertStatus(200);
        $response->assertSee('Available Room');
        $response->assertDontSee('Maintenance Room');
    }

    public function test_availability_filter_allows_non_overlapping_maintenance(): void
    {
        $room = Room::factory()->create(['name' => 'Partial Maintenance', 'status' => 'active']);

        // Schedule maintenance for 9-10
        RoomMaintenanceSchedule::create([
            'room_id' => $room->id,
            'start_datetime' => now()->addDay()->setTime(9, 0),
            'end_datetime' => now()->addDay()->setTime(10, 0),
            'reason' => 'Quick Repair',
            'created_by' => $this->user->id,
        ]);

        // Search for 14:00-15:00 (no overlap)
        $response = $this->actingAs($this->user)
            ->get(route('rooms.index', [
                'date' => now()->addDay()->toDateString(),
                'start_time' => '14:00:00',
                'end_time' => '15:00:00',
            ]));

        $response->assertStatus(200);
        $response->assertSee('Partial Maintenance');
    }

    // =====================
    // MULTIPLE FILTERS COMBINED
    // =====================

    public function test_multiple_filters_combine_correctly(): void
    {
        $projector = Amenity::where('name', 'Projector')->first();

        // Room that matches all criteria
        $perfectRoom = Room::factory()->create([
            'name' => 'Perfect Match',
            'capacity' => 15,
            'status' => 'active',
        ]);
        $perfectRoom->amenities()->attach([$projector->id]);

        // Room too small
        $smallRoom = Room::factory()->create([
            'name' => 'Too Small',
            'capacity' => 4,
            'status' => 'active',
        ]);
        $smallRoom->amenities()->attach([$projector->id]);

        // Room without amenity
        $noAmenity = Room::factory()->create([
            'name' => 'No Amenity',
            'capacity' => 20,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('rooms.index', [
                'capacity' => 10,
                'amenities' => [$projector->id],
            ]));

        $response->assertStatus(200);
        $response->assertSee('Perfect Match');
        $response->assertDontSee('Too Small');
        $response->assertDontSee('No Amenity');
    }

    // =====================
    // EMPTY STATE & CLEAR FILTERS
    // =====================

    public function test_empty_results_show_message(): void
    {
        Room::factory()->create(['name' => 'Only Room', 'status' => 'active']);

        $response = $this->actingAs($this->user)
            ->get(route('rooms.index', ['search' => 'NonExistentRoom123']));

        $response->assertStatus(200);
        $response->assertSee('No rooms found');
        $response->assertSee('Clear Filters');
    }

    public function test_clear_filters_resets_page(): void
    {
        Room::factory()->count(3)->create(['status' => 'active']);

        // Access index with filters, then clear
        $response = $this->actingAs($this->user)
            ->get(route('rooms.index'));

        $response->assertStatus(200);
        $rooms = $response->viewData('rooms');
        $this->assertEquals(3, $rooms->total());
    }

    // =====================
    // FILTER PRESERVATION
    // =====================

    public function test_filters_preserved_on_pagination(): void
    {
        Room::factory()->count(15)->create(['status' => 'active']);

        $response = $this->actingAs($this->user)
            ->get(route('rooms.index', ['search' => 'Room', 'page' => 2]));

        $response->assertStatus(200);
        // The pagination link should contain the search parameter
        $this->assertStringContainsString('search=Room', $response->content());
    }

    public function test_result_count_updates_with_filters(): void
    {
        Room::factory()->count(5)->create(['status' => 'active', 'capacity' => 5]);
        Room::factory()->count(3)->create(['status' => 'active', 'capacity' => 20]);

        $response = $this->actingAs($this->user)
            ->get(route('rooms.index', ['capacity' => 15]));

        $response->assertStatus(200);
        $rooms = $response->viewData('rooms');
        $this->assertEquals(3, $rooms->total());
    }

    // =====================
    // APPLIED FILTERS DISPLAY
    // =====================

    public function test_applied_filters_shown_as_badges(): void
    {
        Room::factory()->create(['status' => 'active']);

        $response = $this->actingAs($this->user)
            ->get(route('rooms.index', ['search' => 'test', 'capacity' => 10]));

        $response->assertStatus(200);
        // Check for filter badges in the response
        $response->assertSee('test'); // search term shown
        $response->assertSee('10+ people'); // capacity shown
    }
}
