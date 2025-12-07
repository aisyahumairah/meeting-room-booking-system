<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test that regular user sees user dashboard when accessing main dashboard.
     */
    public function test_regular_user_sees_user_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'regular_user',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.user');
    }

    /**
     * Test that system admin sees user dashboard (not admin).
     */
    public function test_system_admin_sees_user_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'system_admin',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.user');
    }

    /**
     * Test that administrator sees admin dashboard.
     */
    public function test_administrator_sees_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'administrator',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.admin');
    }

    /**
     * Test that director sees admin dashboard.
     */
    public function test_director_sees_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'director',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.admin');
    }

    /**
     * Test that regular user cannot access admin dashboard directly.
     */
    public function test_regular_user_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'regular_user',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($user)->get('/dashboard/admin');

        $response->assertStatus(403);
    }

    /**
     * Test that regular user can access user dashboard directly.
     */
    public function test_regular_user_can_access_user_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'regular_user',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($user)->get('/dashboard/user');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.user');
    }

    /**
     * Test that administrator can access admin dashboard directly.
     */
    public function test_administrator_can_access_admin_dashboard_directly(): void
    {
        $user = User::factory()->create([
            'role' => 'administrator',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($user)->get('/dashboard/admin');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.admin');
    }

    /**
     * Test that director can access admin dashboard directly.
     */
    public function test_director_can_access_admin_dashboard_directly(): void
    {
        $user = User::factory()->create([
            'role' => 'director',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($user)->get('/dashboard/admin');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.admin');
    }

    /**
     * Test user dashboard displays correct widgets.
     */
    public function test_user_dashboard_displays_widgets(): void
    {
        $user = User::factory()->create([
            'role' => 'regular_user',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($user)->get('/dashboard/user');

        $response->assertStatus(200);
        $response->assertSee('Welcome back');
        $response->assertSee('My Upcoming Bookings');
        $response->assertSee('Quick Actions');
        $response->assertSee('My Booking History');
    }

    /**
     * Test admin dashboard displays correct widgets.
     */
    public function test_admin_dashboard_displays_widgets(): void
    {
        $user = User::factory()->create([
            'role' => 'administrator',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($user)->get('/dashboard/admin');

        $response->assertStatus(200);
        $response->assertSee('Welcome back');
        $response->assertSee('Pending Approvals');
        $response->assertSee('Today');
        $response->assertSee('Quick Actions');
        $response->assertSee('Room Utilization');
    }

    /**
     * Test that home route redirects to dashboard for authenticated users.
     */
    public function test_home_route_redirects_to_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'regular_user',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.user');
    }

    /**
     * Test that unauthenticated user is redirected to login.
     */
    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    /**
     * Test inactive user cannot access dashboard.
     */
    public function test_inactive_user_cannot_access_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'regular_user',
            'status' => 'inactive',
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        // Inactive users are redirected with an error message
        $response->assertStatus(302);
    }

    /**
     * Test must change password user is redirected.
     */
    public function test_must_change_password_user_is_redirected(): void
    {
        $user = User::factory()->create([
            'role' => 'regular_user',
            'status' => 'active',
            'must_change_password' => true,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('password.change'));
    }
}
