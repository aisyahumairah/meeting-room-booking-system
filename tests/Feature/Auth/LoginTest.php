<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_login_page(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Welcome to MRBS');
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
            'status' => 'active',
            'must_change_password' => false,
            'role' => 'regular_user',
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard.user'));
    }

    public function test_user_cannot_login_with_incorrect_password(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => 'password123',
            'status' => 'inactive',
        ]);

        $response = $this->post('/login', [
            'email' => 'inactive@example.com',
            'password' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_admin_is_redirected_to_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'administrator',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard.admin'));
    }

    public function test_director_is_redirected_to_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'director',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard.admin'));
    }

    public function test_user_with_must_change_password_is_redirected(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'must_change_password' => true,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('password.change'));
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user);

        $response = $this->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    public function test_last_login_is_updated_on_login(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'must_change_password' => false,
            'last_login_at' => null,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
    }
}
