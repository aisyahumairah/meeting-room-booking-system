<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Room;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RoomAuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AmenitySeeder::class);

        $this->admin = User::factory()->administrator()->passwordChanged()->create();
    }

    // =====================
    // ROOM CREATION LOGGING
    // =====================

    public function test_room_creation_is_logged(): void
    {
        $this->actingAs($this->admin)->post(route('admin.rooms.store'), [
            'name' => 'Test Audit Room',
            'capacity' => 10,
            'floor_location' => '1st Floor',
            'description' => 'A test room',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => AuditService::EVENT_ROOM_CREATED,
            'target_type' => 'room',
            'actor_id' => $this->admin->id,
        ]);

        $log = AuditLog::where('event_type', AuditService::EVENT_ROOM_CREATED)->first();
        $details = $log->details;

        $this->assertEquals('Test Audit Room', $details['room_name']);
        $this->assertEquals(10, $details['capacity']);
        $this->assertEquals('1st Floor', $details['floor_location']);
    }

    public function test_room_creation_logs_amenities(): void
    {
        $amenities = \App\Models\Amenity::take(2)->pluck('id')->toArray();

        $this->actingAs($this->admin)->post(route('admin.rooms.store'), [
            'name' => 'Room With Amenities',
            'capacity' => 8,
            'floor_location' => '2nd Floor',
            'status' => 'active',
            'amenities' => $amenities,
        ]);

        $log = AuditLog::where('event_type', AuditService::EVENT_ROOM_CREATED)->first();
        $details = $log->details;

        $this->assertArrayHasKey('amenities', $details);
        $this->assertCount(2, $details['amenities']);
    }

    // =====================
    // ROOM UPDATE LOGGING
    // =====================

    public function test_room_update_logs_only_changed_fields(): void
    {
        $room = Room::factory()->create([
            'name' => 'Original Name',
            'capacity' => 10,
            'floor_location' => 'Level 1',
        ]);

        $this->actingAs($this->admin)->put(route('admin.rooms.update', $room), [
            'name' => 'Original Name', // Same
            'capacity' => 20, // Changed
            'floor_location' => 'Level 1', // Same
            'description' => 'New description', // Changed (from null)
            'status' => 'active',
        ]);

        $log = AuditLog::where('event_type', AuditService::EVENT_ROOM_UPDATED)->first();
        $this->assertNotNull($log);

        $details = $log->details;
        $this->assertArrayHasKey('changes', $details);
        $this->assertArrayHasKey('capacity', $details['changes']);
        $this->assertEquals(10, $details['changes']['capacity']['old']);
        $this->assertEquals(20, $details['changes']['capacity']['new']);
        $this->assertArrayNotHasKey('name', $details['changes']);
        $this->assertArrayNotHasKey('floor_location', $details['changes']);
    }

    public function test_room_update_logs_amenity_changes(): void
    {
        $amenity1 = \App\Models\Amenity::first();
        $amenity2 = \App\Models\Amenity::skip(1)->first();

        $room = Room::factory()->create();
        $room->amenities()->attach([$amenity1->id]);

        $this->actingAs($this->admin)->put(route('admin.rooms.update', $room), [
            'name' => $room->name,
            'capacity' => $room->capacity,
            'floor_location' => $room->floor_location,
            'status' => 'active',
            'amenities' => [$amenity2->id], // Changed amenity
        ]);

        $log = AuditLog::where('event_type', AuditService::EVENT_ROOM_UPDATED)->first();
        $this->assertNotNull($log);

        $details = $log->details;
        $this->assertArrayHasKey('amenities', $details['changes']);
    }

    public function test_room_update_does_not_log_when_nothing_changed(): void
    {
        $room = Room::factory()->create([
            'name' => 'Unchanged Room',
            'capacity' => 10,
            'floor_location' => 'Level 1',
            'description' => null,
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)->put(route('admin.rooms.update', $room), [
            'name' => 'Unchanged Room',
            'capacity' => 10,
            'floor_location' => 'Level 1',
            'description' => null,
            'status' => 'active',
        ]);

        $log = AuditLog::where('event_type', AuditService::EVENT_ROOM_UPDATED)->first();
        $this->assertNull($log);
    }

    // =====================
    // ROOM DELETION LOGGING
    // =====================

    public function test_room_deletion_is_logged(): void
    {
        $room = Room::factory()->create(['name' => 'Room To Delete']);
        $roomId = $room->id;

        $this->actingAs($this->admin)->delete(route('admin.rooms.destroy', $room));

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => AuditService::EVENT_ROOM_DELETED,
            'target_type' => 'room',
            'target_id' => $roomId,
            'actor_id' => $this->admin->id,
        ]);

        $log = AuditLog::where('event_type', AuditService::EVENT_ROOM_DELETED)->first();
        $this->assertEquals('Room To Delete', $log->details['room_name']);
    }

    // =====================
    // STATUS CHANGE LOGGING
    // =====================

    public function test_status_change_is_logged(): void
    {
        $room = Room::factory()->create(['status' => 'active']);

        $this->actingAs($this->admin)->put(route('admin.rooms.status', $room), [
            'status' => 'inactive',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => AuditService::EVENT_ROOM_STATUS_CHANGED,
            'target_type' => 'room',
            'target_id' => $room->id,
        ]);

        $log = AuditLog::where('event_type', AuditService::EVENT_ROOM_STATUS_CHANGED)->first();
        $details = $log->details;

        $this->assertEquals('active', $details['old_status']);
        $this->assertEquals('inactive', $details['new_status']);
    }

    // =====================
    // MAINTENANCE SCHEDULING
    // =====================

    public function test_maintenance_scheduling_is_logged(): void
    {
        $room = Room::factory()->create(['status' => 'active']);

        $this->actingAs($this->admin)->put(route('admin.rooms.status', $room), [
            'status' => 'under_maintenance',
            'maintenance_start' => now()->addDay()->toDateTimeString(),
            'maintenance_end' => now()->addDays(3)->toDateTimeString(),
            'maintenance_reason' => 'AC Repair',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => AuditService::EVENT_ROOM_MAINTENANCE_SCHEDULED,
            'target_type' => 'room',
            'target_id' => $room->id,
        ]);

        $log = AuditLog::where('event_type', AuditService::EVENT_ROOM_MAINTENANCE_SCHEDULED)->first();
        $details = $log->details;

        $this->assertArrayHasKey('maintenance', $details);
        $this->assertEquals('AC Repair', $details['maintenance']['reason']);
    }

    // =====================
    // IMAGE LOGGING
    // =====================

    public function test_image_upload_is_logged(): void
    {
        Storage::fake('public');

        $room = Room::factory()->create(['name' => 'Image Test Room']);

        $file = UploadedFile::fake()->image('test-room.jpg', 800, 600);

        $this->actingAs($this->admin)->post(route('admin.rooms.store'), [
            'name' => 'Room With Image',
            'capacity' => 10,
            'floor_location' => 'Level 1',
            'status' => 'active',
            'images' => [$file],
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => AuditService::EVENT_ROOM_IMAGE_UPLOADED,
            'target_type' => 'room',
        ]);

        $log = AuditLog::where('event_type', AuditService::EVENT_ROOM_IMAGE_UPLOADED)->first();
        $this->assertArrayHasKey('filename', $log->details);
    }

    public function test_image_deletion_is_logged(): void
    {
        Storage::fake('public');

        $room = Room::factory()->create(['name' => 'Room With Image']);

        // Create an image manually
        $image = \App\Models\RoomImage::create([
            'room_id' => $room->id,
            'path' => 'rooms/' . $room->id . '/test.jpg',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $this->actingAs($this->admin)->delete(route('admin.rooms.images.destroy', [
            'room' => $room,
            'image' => $image->id,
        ]));

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => AuditService::EVENT_ROOM_IMAGE_DELETED,
            'target_type' => 'room',
            'target_id' => $room->id,
        ]);

        $log = AuditLog::where('event_type', AuditService::EVENT_ROOM_IMAGE_DELETED)->first();
        $this->assertEquals($image->id, $log->details['image_id']);
    }

    // =====================
    // AUDIT LOG DETAILS
    // =====================

    public function test_audit_log_contains_actor_information(): void
    {
        $this->actingAs($this->admin)->post(route('admin.rooms.store'), [
            'name' => 'Actor Test Room',
            'capacity' => 10,
            'floor_location' => 'Level 1',
            'status' => 'active',
        ]);

        $log = AuditLog::where('event_type', AuditService::EVENT_ROOM_CREATED)->first();

        $this->assertEquals($this->admin->id, $log->actor_id);
        $this->assertEquals($this->admin->name, $log->actor_name);
        $this->assertNotNull($log->ip_address);
    }

    public function test_audit_log_is_immutable(): void
    {
        $this->actingAs($this->admin)->post(route('admin.rooms.store'), [
            'name' => 'Immutable Test',
            'capacity' => 10,
            'floor_location' => 'Level 1',
            'status' => 'active',
        ]);

        $log = AuditLog::where('event_type', AuditService::EVENT_ROOM_CREATED)->first();
        $originalEventType = $log->event_type;

        // Attempt to modify should be silently blocked
        $log->event_type = 'modified';
        $log->save();

        // Refresh from database and verify it wasn't changed
        $log->refresh();
        $this->assertEquals($originalEventType, $log->event_type);
    }
}
