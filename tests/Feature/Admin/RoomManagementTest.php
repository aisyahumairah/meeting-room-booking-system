<?php

namespace Tests\Feature\Admin;

use App\Models\Amenity;
use App\Models\Room;
use App\Models\RoomImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RoomManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AmenitySeeder::class);

        $this->admin = User::factory()->administrator()->passwordChanged()->create();
        $this->regularUser = User::factory()->regularUser()->passwordChanged()->create();
    }

    // =====================
    // INDEX TESTS
    // =====================

    public function test_admin_can_view_room_list(): void
    {
        Room::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.rooms.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.rooms.index');
        $response->assertViewHas('rooms');
        $response->assertViewHas('statusCounts');
    }

    public function test_regular_user_cannot_access_admin_rooms(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('admin.rooms.index'));

        $response->assertStatus(403);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.rooms.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_room_list_can_be_searched(): void
    {
        Room::factory()->create(['name' => 'Conference Room Alpha']);
        Room::factory()->create(['name' => 'Meeting Room Beta']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.rooms.index', ['search' => 'Alpha']));

        $response->assertStatus(200);
        $response->assertSee('Conference Room Alpha');
        $response->assertDontSee('Meeting Room Beta');
    }

    public function test_room_list_can_be_filtered_by_status(): void
    {
        Room::factory()->create(['status' => 'active', 'name' => 'AlphaTestRoom123']);
        Room::factory()->create(['status' => 'inactive', 'name' => 'BetaTestRoom456']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.rooms.index', ['status' => 'active']));

        $response->assertStatus(200);
        $response->assertSee('AlphaTestRoom123');
        $response->assertDontSee('BetaTestRoom456');
    }

    // =====================
    // CREATE TESTS
    // =====================

    public function test_admin_can_view_create_room_form(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.rooms.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.rooms.create');
        $response->assertViewHas('amenities');
    }

    public function test_admin_can_create_room_with_amenities(): void
    {
        $amenities = Amenity::take(2)->pluck('id')->toArray();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.rooms.store'), [
                'name' => 'New Conference Room',
                'capacity' => 20,
                'floor_location' => 'Level 5',
                'description' => 'A modern conference room',
                'amenities' => $amenities,
            ]);

        $response->assertRedirect(route('admin.rooms.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('rooms', [
            'name' => 'New Conference Room',
            'capacity' => 20,
        ]);

        $room = Room::where('name', 'New Conference Room')->first();
        $this->assertCount(2, $room->amenities);
    }

    public function test_admin_can_create_room_with_images(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin)
            ->post(route('admin.rooms.store'), [
                'name' => 'Room With Images',
                'capacity' => 10,
                'floor_location' => 'Level 2',
                'images' => [
                    UploadedFile::fake()->image('room1.jpg'),
                    UploadedFile::fake()->image('room2.jpg'),
                ],
            ]);

        $response->assertRedirect(route('admin.rooms.index'));

        $room = Room::where('name', 'Room With Images')->first();
        $this->assertCount(2, $room->images);

        // First image should be primary
        $this->assertTrue($room->images->first()->is_primary);
    }

    public function test_validation_errors_shown_for_invalid_data(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.rooms.store'), [
                'name' => '', // Required
                'capacity' => 0, // Min 1
                'floor_location' => '', // Required
            ]);

        $response->assertSessionHasErrors(['name', 'capacity', 'floor_location']);
    }

    public function test_room_name_must_be_unique(): void
    {
        Room::factory()->create(['name' => 'Existing Room']);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.rooms.store'), [
                'name' => 'Existing Room',
                'capacity' => 10,
                'floor_location' => 'Level 1',
            ]);

        $response->assertSessionHasErrors(['name']);
    }

    // =====================
    // EDIT TESTS
    // =====================

    public function test_admin_can_view_edit_room_form(): void
    {
        $room = Room::factory()->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.rooms.edit', $room));

        $response->assertStatus(200);
        $response->assertViewIs('admin.rooms.edit');
        $response->assertViewHas('room');
        $response->assertViewHas('amenities');
    }

    public function test_admin_can_edit_room(): void
    {
        $room = Room::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($this->admin)
            ->put(route('admin.rooms.update', $room), [
                'name' => 'New Name',
                'capacity' => 15,
                'floor_location' => 'Level 3',
            ]);

        $response->assertRedirect(route('admin.rooms.edit', $room));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'name' => 'New Name',
        ]);
    }

    // =====================
    // DELETE TESTS
    // =====================

    public function test_admin_can_delete_room_without_bookings(): void
    {
        $room = Room::factory()->create();

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.rooms.destroy', $room));

        $response->assertRedirect(route('admin.rooms.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }

    public function test_admin_cannot_delete_room_with_bookings(): void
    {
        $room = Room::factory()->create();

        // Create a booking for this room
        \App\Models\Booking::factory()->create([
            'room_id' => $room->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->from(route('admin.rooms.index'))
            ->delete(route('admin.rooms.destroy', $room));

        $response->assertRedirect(route('admin.rooms.index'));
        $response->assertSessionHas('error');

        // Room should still exist
        $this->assertDatabaseHas('rooms', ['id' => $room->id]);
    }

    // =====================
    // STATUS TESTS
    // =====================

    public function test_admin_can_change_room_status(): void
    {
        $room = Room::factory()->create(['status' => 'active']);

        $response = $this->actingAs($this->admin)
            ->put(route('admin.rooms.status', $room), [
                'status' => 'inactive',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $room->refresh();
        $this->assertEquals('inactive', $room->status);
    }

    public function test_admin_can_schedule_maintenance(): void
    {
        $room = Room::factory()->create(['status' => 'active']);

        $response = $this->actingAs($this->admin)
            ->put(route('admin.rooms.status', $room), [
                'status' => 'under_maintenance',
                'maintenance_start' => now()->addDay()->format('Y-m-d H:i:s'),
                'maintenance_end' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'maintenance_reason' => 'Annual maintenance',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $room->refresh();
        $this->assertEquals('under_maintenance', $room->status);

        $this->assertDatabaseHas('room_maintenance_schedules', [
            'room_id' => $room->id,
            'reason' => 'Annual maintenance',
        ]);
    }

    // =====================
    // IMAGE TESTS
    // =====================

    public function test_images_uploaded_correctly(): void
    {
        Storage::fake('public');

        $room = Room::factory()->create();

        $response = $this->actingAs($this->admin)
            ->put(route('admin.rooms.update', $room), [
                'name' => $room->name,
                'capacity' => $room->capacity,
                'floor_location' => $room->floor_location,
                'images' => [
                    UploadedFile::fake()->image('new-image.jpg'),
                ],
            ]);

        $response->assertRedirect();

        $room->refresh();
        $this->assertCount(1, $room->images);
    }

    public function test_max_five_images_limit(): void
    {
        Storage::fake('public');

        // Create room with 5 images already
        $room = Room::factory()->create();
        for ($i = 0; $i < 5; $i++) {
            RoomImage::create([
                'room_id' => $room->id,
                'path' => "rooms/{$room->id}/image{$i}.jpg",
                'is_primary' => $i === 0,
                'sort_order' => $i,
            ]);
        }

        // Try to add more images - should not add
        $response = $this->actingAs($this->admin)
            ->put(route('admin.rooms.update', $room), [
                'name' => $room->name,
                'capacity' => $room->capacity,
                'floor_location' => $room->floor_location,
                'images' => [
                    UploadedFile::fake()->image('extra.jpg'),
                ],
            ]);

        $response->assertRedirect();

        $room->refresh();
        $this->assertCount(5, $room->images); // Still only 5
    }
}
