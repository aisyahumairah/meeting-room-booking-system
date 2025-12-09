<?php

namespace Tests\Unit;

use App\Models\Room;
use App\Models\Amenity;
use App\Models\RoomImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AmenitySeeder::class);
    }

    public function test_room_can_be_created(): void
    {
        $room = Room::create([
            'name' => 'Test Room',
            'capacity' => 10,
            'floor_location' => '1st Floor',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('rooms', ['name' => 'Test Room']);
    }

    public function test_room_has_amenities_relationship(): void
    {
        $room = Room::factory()->create();
        $amenity = Amenity::first();

        $room->amenities()->attach($amenity->id);

        $this->assertTrue($room->amenities->contains($amenity));
    }

    public function test_room_active_scope(): void
    {
        Room::factory()->create(['status' => 'active']);
        Room::factory()->create(['status' => 'inactive']);

        $activeRooms = Room::active()->get();

        $this->assertCount(1, $activeRooms);
    }

    public function test_room_by_capacity_scope(): void
    {
        Room::factory()->create(['capacity' => 5]);
        Room::factory()->create(['capacity' => 15]);

        $largeRooms = Room::byCapacity(10)->get();

        $this->assertCount(1, $largeRooms);
    }

    public function test_room_status_badge_accessor(): void
    {
        $activeRoom = Room::factory()->create(['status' => 'active']);
        $inactiveRoom = Room::factory()->create(['status' => 'inactive']);

        $this->assertStringContainsString('bg-success', $activeRoom->status_badge);
        $this->assertStringContainsString('bg-secondary', $inactiveRoom->status_badge);
    }

    public function test_room_can_be_soft_deleted(): void
    {
        $room = Room::factory()->create();
        $roomId = $room->id;

        $room->delete();

        $this->assertSoftDeleted('rooms', ['id' => $roomId]);
    }

    public function test_room_name_must_be_unique(): void
    {
        Room::factory()->create(['name' => 'Unique Room']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Room::factory()->create(['name' => 'Unique Room']);
    }

    public function test_room_can_check_if_has_bookings(): void
    {
        // Skip until Booking model is created in Phase 3
        $this->markTestSkipped('Booking model not yet created - will be implemented in Phase 3.');
    }

    public function test_room_can_be_deleted_check(): void
    {
        // Skip until Booking model is created in Phase 3
        $this->markTestSkipped('Booking model not yet created - will be implemented in Phase 3.');
    }

    public function test_room_status_display_accessor(): void
    {
        $activeRoom = Room::factory()->create(['status' => 'active']);
        $inactiveRoom = Room::factory()->create(['status' => 'inactive']);
        $maintenanceRoom = Room::factory()->create(['status' => 'under_maintenance']);

        $this->assertEquals('Active', $activeRoom->status_display);
        $this->assertEquals('Inactive', $inactiveRoom->status_display);
        $this->assertEquals('Under Maintenance', $maintenanceRoom->status_display);
    }

    public function test_room_primary_image_returns_placeholder_when_no_images(): void
    {
        $room = Room::factory()->create();

        $this->assertStringContainsString('placeholder.png', $room->primary_image);
    }

    public function test_room_by_status_scope(): void
    {
        Room::factory()->create(['status' => 'active']);
        Room::factory()->create(['status' => 'inactive']);
        Room::factory()->create(['status' => 'under_maintenance']);

        $this->assertCount(1, Room::byStatus('active')->get());
        $this->assertCount(1, Room::byStatus('inactive')->get());
        $this->assertCount(1, Room::byStatus('under_maintenance')->get());
    }

    public function test_room_available_scope(): void
    {
        Room::factory()->create(['status' => 'active']);
        Room::factory()->create(['status' => 'inactive']);
        Room::factory()->create(['status' => 'under_maintenance']);

        $availableRooms = Room::available()->get();

        $this->assertCount(1, $availableRooms);
        $this->assertEquals('active', $availableRooms->first()->status);
    }

    public function test_amenity_has_rooms_relationship(): void
    {
        $room = Room::factory()->create();
        $amenity = Amenity::first();

        $room->amenities()->attach($amenity->id);

        $this->assertTrue($amenity->rooms->contains($room));
    }

    public function test_amenity_icon_html_accessor(): void
    {
        $amenity = Amenity::first();

        $this->assertStringContainsString('<i class="bx', $amenity->icon_html);
    }

    public function test_room_image_belongs_to_room(): void
    {
        $room = Room::factory()->create();
        $image = RoomImage::create([
            'room_id' => $room->id,
            'path' => 'rooms/test.jpg',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $this->assertEquals($room->id, $image->room->id);
    }

    public function test_room_image_url_accessor(): void
    {
        $room = Room::factory()->create();
        $image = RoomImage::create([
            'room_id' => $room->id,
            'path' => 'rooms/test.jpg',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $this->assertStringContainsString('storage/rooms/test.jpg', $image->url);
    }

    public function test_room_with_amenities_scope(): void
    {
        $room1 = Room::factory()->create();
        $room2 = Room::factory()->create();

        $amenity1 = Amenity::first();
        $amenity2 = Amenity::skip(1)->first();

        // Room 1 has both amenities
        $room1->amenities()->attach([$amenity1->id, $amenity2->id]);

        // Room 2 has only amenity 1
        $room2->amenities()->attach([$amenity1->id]);

        // Filter by both amenities - should only return room1
        $roomsWithBothAmenities = Room::withAmenities([$amenity1->id, $amenity2->id])->get();

        $this->assertCount(1, $roomsWithBothAmenities);
        $this->assertEquals($room1->id, $roomsWithBothAmenities->first()->id);
    }
}
