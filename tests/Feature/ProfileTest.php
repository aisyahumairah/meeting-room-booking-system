<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Run the user seeder
        $this->seed(\Database\Seeders\UserSeeder::class);
    }

    // =========================================================================
    // PROFILE VIEW TESTS
    // =========================================================================

    public function test_user_can_view_their_profile(): void
    {
        $user = User::where('email', 'user@mrbs.local')->first();

        $response = $this->actingAs($user)->get(route('profile.show'));

        $response->assertStatus(200);
        $response->assertViewIs('profile.show');
        $response->assertSee($user->name);
        $response->assertSee($user->staff_number);
        $response->assertSee($user->email);
    }

    public function test_guest_cannot_view_profile(): void
    {
        $response = $this->get(route('profile.show'));

        $response->assertRedirect(route('login'));
    }

    // =========================================================================
    // PROFILE EDIT TESTS
    // =========================================================================

    public function test_user_can_view_edit_profile_form(): void
    {
        $user = User::where('email', 'user@mrbs.local')->first();

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertStatus(200);
        $response->assertViewIs('profile.edit');
        $response->assertSee($user->name);
    }

    public function test_user_can_update_name_department_phone(): void
    {
        $user = User::where('email', 'user@mrbs.local')->first();

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Updated Name',
            'department' => 'Updated Department',
            'phone' => '+60 12-345 6789',
        ]);

        $response->assertRedirect(route('profile.show'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals('Updated Department', $user->department);
        $this->assertEquals('+60 12-345 6789', $user->phone);
    }

    public function test_user_cannot_update_email(): void
    {
        $user = User::where('email', 'user@mrbs.local')->first();
        $originalEmail = $user->email;

        // Attempt to update email (even if included in request, it should be ignored)
        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => 'newemail@example.com', // This should be ignored
        ]);

        $user->refresh();
        $this->assertEquals($originalEmail, $user->email);
    }

    public function test_user_cannot_update_staff_number(): void
    {
        $user = User::where('email', 'user@mrbs.local')->first();
        $originalStaffNumber = $user->staff_number;

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Updated Name',
            'staff_number' => 'NEW12345', // This should be ignored
        ]);

        $user->refresh();
        $this->assertEquals($originalStaffNumber, $user->staff_number);
    }

    public function test_user_cannot_update_role(): void
    {
        $user = User::where('email', 'user@mrbs.local')->first();
        $originalRole = $user->role;

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Updated Name',
            'role' => 'system_admin', // This should be ignored
        ]);

        $user->refresh();
        $this->assertEquals($originalRole, $user->role);
    }

    public function test_name_is_required_for_profile_update(): void
    {
        $user = User::where('email', 'user@mrbs.local')->first();

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => '',
        ]);

        $response->assertSessionHasErrors('name');
    }

    // =========================================================================
    // CHANGE PASSWORD TESTS
    // =========================================================================

    public function test_user_can_view_change_password_form(): void
    {
        $user = User::where('email', 'user@mrbs.local')->first();

        $response = $this->actingAs($user)->get(route('profile.password'));

        $response->assertStatus(200);
        $response->assertViewIs('profile.change-password');
    }

    public function test_user_can_change_password_with_correct_current_password(): void
    {
        $user = User::where('email', 'user@mrbs.local')->first();

        $response = $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'password123',
            'password' => 'NewPass@123',
            'password_confirmation' => 'NewPass@123',
        ]);

        $response->assertRedirect(route('profile.show'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('NewPass@123', $user->password));
    }

    public function test_user_cannot_change_password_with_wrong_current_password(): void
    {
        $user = User::where('email', 'user@mrbs.local')->first();

        $response = $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'wrongpassword',
            'password' => 'NewPass@123',
            'password_confirmation' => 'NewPass@123',
        ]);

        $response->assertSessionHasErrors('current_password');
    }

    public function test_new_password_must_meet_minimum_requirements(): void
    {
        $user = User::where('email', 'user@mrbs.local')->first();

        // Too short
        $response = $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'password123',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_new_password_must_contain_letters(): void
    {
        $user = User::where('email', 'user@mrbs.local')->first();

        $response = $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'password123',
            'password' => '12345678@!',
            'password_confirmation' => '12345678@!',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_new_password_must_contain_numbers(): void
    {
        $user = User::where('email', 'user@mrbs.local')->first();

        $response = $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'password123',
            'password' => 'Password@!',
            'password_confirmation' => 'Password@!',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_new_password_must_contain_symbols(): void
    {
        $user = User::where('email', 'user@mrbs.local')->first();

        $response = $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'password123',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_password_confirmation_must_match(): void
    {
        $user = User::where('email', 'user@mrbs.local')->first();

        $response = $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'password123',
            'password' => 'NewPass@123',
            'password_confirmation' => 'DifferentPass@123',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_must_change_password_flag_is_cleared_after_password_change(): void
    {
        // Create a user with must_change_password = true
        $user = User::factory()->create([
            'must_change_password' => true,
            'password' => Hash::make('password123'),
        ]);

        $this->assertTrue($user->must_change_password);

        // Use withoutMiddleware to bypass the MustChangePassword middleware redirect
        // and directly test that the controller clears the flag
        $response = $this->actingAs($user)
            ->withoutMiddleware(\App\Http\Middleware\MustChangePassword::class)
            ->put(route('profile.password.update'), [
                'current_password' => 'password123',
                'password' => 'NewPass@123',
                'password_confirmation' => 'NewPass@123',
            ]);

        $response->assertRedirect(route('profile.show'));

        $user->refresh();
        $this->assertFalse($user->must_change_password);
    }

    // =========================================================================
    // ACCESSOR TESTS
    // =========================================================================

    public function test_role_display_accessor_returns_correct_values(): void
    {
        $sysAdmin = User::where('role', 'system_admin')->first();
        $director = User::where('role', 'director')->first();
        $admin = User::where('role', 'administrator')->first();
        $user = User::where('role', 'regular_user')->first();

        $this->assertEquals('System Admin', $sysAdmin->role_display);
        $this->assertEquals('Director', $director->role_display);
        $this->assertEquals('Administrator', $admin->role_display);
        $this->assertEquals('Regular User', $user->role_display);
    }

    public function test_role_badge_accessor_returns_correct_values(): void
    {
        $sysAdmin = User::where('role', 'system_admin')->first();
        $director = User::where('role', 'director')->first();
        $admin = User::where('role', 'administrator')->first();
        $user = User::where('role', 'regular_user')->first();

        $this->assertEquals('warning', $sysAdmin->role_badge);
        $this->assertEquals('primary', $director->role_badge);
        $this->assertEquals('success', $admin->role_badge);
        $this->assertEquals('secondary', $user->role_badge);
    }

    public function test_status_badge_accessor_returns_correct_values(): void
    {
        $activeUser = User::factory()->create(['status' => 'active']);
        $inactiveUser = User::factory()->create(['status' => 'inactive']);

        $this->assertEquals('success', $activeUser->status_badge);
        $this->assertEquals('danger', $inactiveUser->status_badge);
    }
}
