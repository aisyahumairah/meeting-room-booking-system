<?php

namespace Tests\Unit\Models;

use App\Models\Amenity;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AmenityTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_has_rooms_relationship()
    {
        $amenity = Amenity::factory()->create();
        $room = Room::factory()->create();

        $room->amenities()->attach($amenity);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $amenity->rooms);
        $this->assertTrue($amenity->rooms->contains($room));
    }

    public function test_it_has_active_scope()
    {
        Amenity::factory()->create(['is_active' => true]);
        Amenity::factory()->create(['is_active' => false]);

        $activeAmenities = Amenity::active()->get();

        $this->assertCount(1, $activeAmenities);
        $this->assertTrue($activeAmenities->first()->is_active);
    }

    public function test_it_has_inactive_scope()
    {
        Amenity::factory()->create(['is_active' => true]);
        Amenity::factory()->create(['is_active' => false]);

        $inactiveAmenities = Amenity::inactive()->get();

        $this->assertCount(1, $inactiveAmenities);
        $this->assertFalse($inactiveAmenities->first()->is_active);
    }

    public function test_it_casts_is_active_to_boolean()
    {
        $amenity = Amenity::factory()->create(['is_active' => 1]);

        $this->assertIsBool($amenity->is_active);
        $this->assertTrue($amenity->is_active);
    }

    public function test_name_is_required()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Amenity::create([
            'icon' => 'bx-test',
        ]);
    }

    public function test_it_defaults_to_active_when_created()
    {
        $amenity = Amenity::create([
            'name' => 'Test Amenity',
            'icon' => 'bx-test',
        ]);

        $this->assertTrue($amenity->fresh()->is_active);
    }
}
