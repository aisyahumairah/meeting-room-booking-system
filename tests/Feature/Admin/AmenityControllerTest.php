<?php

namespace Tests\Feature\Admin;

use App\Models\Amenity;
use App\Models\AuditLog;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AmenityControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create(['role' => 'administrator']);
        $this->user = User::factory()->create(['role' => 'user']);
    }

    /** @test */
    public function admin_can_view_amenity_list()
    {
        Amenity::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get(route('admin.amenities.index'));

        $response->assertOk();
        $response->assertViewIs('admin.amenities.index');
        $response->assertViewHas('amenities');
    }

    /** @test */
    public function regular_user_cannot_access_amenity_management()
    {
        $response = $this->actingAs($this->user)->get(route('admin.amenities.index'));

        $response->assertForbidden();
    }

    /** @test */
    public function admin_can_create_amenity()
    {
        $data = [
            'name' => 'Smart Board',
            'icon' => 'bx-chalkboard',
            'description' => 'Interactive whiteboard',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.amenities.store'), $data);

        $response->assertRedirect(route('admin.amenities.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('amenities', [
            'name' => 'Smart Board',
            'icon' => 'bx-chalkboard',
  'is_active' => true,
        ]);

        // Check audit log
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'amenity.created',
            'target_type' => 'amenity',
        ]);
    }

    /** @test */
    public function amenity_name_must_be_unique()
    {
        Amenity::factory()->create(['name' => 'Projector']);

        $data = [
            'name' => 'Projector',
            'icon' => 'bx-projector',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.amenities.store'), $data);

        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function admin_can_update_amenity()
    {
        $amenity = Amenity::factory()->create(['name' => 'Old Name']);

        $data = [
            'name' => 'Updated Name',
            'icon' => 'bx-video',
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.amenities.update', $amenity), $data);

        $response->assertRedirect(route('admin.amenities.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('amenities', [
            'id' => $amenity->id,
            'name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function admin_can_delete_unused_amenity()
    {
        $amenity = Amenity::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('admin.amenities.destroy', $amenity));

        $response->assertRedirect(route('admin.amenities.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('amenities', ['id' => $amenity->id]);

        // Check audit log
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'amenity.deleted',
        ]);
    }

    /** @test */
    public function cannot_delete_amenity_assigned_to_rooms()
    {
        $amenity = Amenity::factory()->create();
        $room = Room::factory()->create();
        $room->amenities()->attach($amenity);

        $response = $this->actingAs($this->admin)->delete(route('admin.amenities.destroy', $amenity));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('amenities', ['id' => $amenity->id]);
    }

    /** @test */
    public function amenity_list_shows_room_count()
    {
        $amenity = Amenity::factory()->create();
        $room1 = Room::factory()->create();
        $room2 = Room::factory()->create();
        
        $room1->amenities()->attach($amenity);
        $room2->amenities()->attach($amenity);

        $response = $this->actingAs($this->admin)->get(route('admin.amenities.index'));

        $response->assertOk();
        $response->assertSee('2 room(s)');
    }

    /** @test */
    public function admin_can_toggle_amenity_status()
    {
        $amenity = Amenity::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin)->put(route('admin.amenities.update-status', $amenity));

        $response->assertRedirect(route('admin.amenities.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('amenities', [
            'id' => $amenity->id,
            'is_active' => false,
        ]);

        // Check audit log
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'amenity.status_changed',
            'target_type' => 'amenity',
        ]);
    }

    /** @test */
    public function active_scope_filters_active_amenities()
    {
        Amenity::factory()->create(['name' => 'Active Amenity', 'is_active' => true]);
        Amenity::factory()->create(['name' => 'Inactive Amenity', 'is_active' => false]);

        $activeAmenities = Amenity::active()->get();

        $this->assertCount(1, $activeAmenities);
        $this->assertEquals('Active Amenity', $activeAmenities->first()->name);
    }

    /** @test */
    public function new_amenities_default_to_active()
    {
        $data = [
            'name' => 'New Amenity',
            'icon' => 'bx-wifi',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.amenities.store'), $data);

        $response->assertRedirect(route('admin.amenities.index'));

        $amenity = Amenity::where('name', 'New Amenity')->first();
        $this->assertTrue($amenity->is_active);
    }
}
