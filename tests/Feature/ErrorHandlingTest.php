<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_404_for_nonexistent_page()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/nonexistent-page');

        $response->assertStatus(404);
        $response->assertSee('Page Not Found');
    }

    public function test_it_returns_403_for_unauthorized_access()
    {
        $user = User::factory()->create(['role' => 'regular_user']);

        $response = $this->actingAs($user)->get(route('admin.users.index'));

        $response->assertStatus(403);
    }

    public function test_it_returns_404_for_nonexistent_booking()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('bookings.show', 99999));

        $response->assertStatus(404);
    }

    public function test_it_returns_404_for_nonexistent_room()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('rooms.show', 99999));

        $response->assertStatus(404);
    }

    public function test_unauthenticated_user_is_redirected_to_login()
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_it_returns_404_for_nonexistent_amenity()
    {
        $admin = User::factory()->create(['role' => 'administrator']);

        $response = $this->actingAs($admin)->get(route('admin.amenities.show', 99999));

        $response->assertStatus(404);
    }
}
