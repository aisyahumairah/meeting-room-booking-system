<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Booking;
use App\Models\Room;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $sysAdmin;
    protected User $director;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sysAdmin = User::factory()->create([
            'role' => 'system_admin',
            'must_change_password' => false,
        ]);
        $this->director = User::factory()->create([
            'role' => 'director',
            'must_change_password' => false,
        ]);
        $this->regularUser = User::factory()->create([
            'role' => 'regular_user',
            'must_change_password' => false,
        ]);
    }

    public function test_sysadmin_can_access_user_list(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.users.index'));
        $response->assertStatus(200);
        $response->assertSee('User Management');
    }

    public function test_director_can_access_user_list(): void
    {
        $response = $this->actingAs($this->director)->get(route('admin.users.index'));
        $response->assertStatus(200);
    }

    public function test_regular_user_cannot_access_user_list(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.users.index'));
        $response->assertStatus(403);
    }

    public function test_sysadmin_can_view_create_user_form(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.users.create'));
        $response->assertStatus(200);
        $response->assertSee('Create New User');
    }

    public function test_sysadmin_can_create_user(): void
    {
        $response = $this->actingAs($this->sysAdmin)->post(route('admin.users.store'), [
            'staff_number' => 'NEW001',
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'role' => 'regular_user',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);

        // Verify audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'user.created',
            'actor_id' => $this->sysAdmin->id,
        ]);
    }

    public function test_cannot_create_user_with_duplicate_staff_number(): void
    {
        $response = $this->actingAs($this->sysAdmin)->post(route('admin.users.store'), [
            'staff_number' => $this->regularUser->staff_number,
            'name' => 'Duplicate',
            'email' => 'duplicate@example.com',
            'role' => 'regular_user',
        ]);

        $response->assertSessionHasErrors('staff_number');
    }

    public function test_cannot_create_user_with_duplicate_email(): void
    {
        $response = $this->actingAs($this->sysAdmin)->post(route('admin.users.store'), [
            'staff_number' => 'UNIQUE001',
            'name' => 'Duplicate Email',
            'email' => $this->regularUser->email,
            'role' => 'regular_user',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_sysadmin_can_view_edit_user_form(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.users.edit', $this->regularUser));
        $response->assertStatus(200);
        $response->assertSee('Edit User');
        $response->assertSee($this->regularUser->name);
    }

    public function test_sysadmin_can_update_user(): void
    {
        $response = $this->actingAs($this->sysAdmin)->put(route('admin.users.update', $this->regularUser), [
            'name' => 'Updated Name',
            'department' => 'IT',
            'phone' => '123456',
            'role' => 'administrator',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $this->regularUser->id,
            'name' => 'Updated Name',
            'role' => 'administrator',
        ]);

        // Verify role change audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'user.role_changed',
            'target_id' => $this->regularUser->id,
        ]);
    }

    public function test_sysadmin_can_deactivate_user(): void
    {
        $response = $this->actingAs($this->sysAdmin)->put(route('admin.users.deactivate', $this->regularUser));

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $this->regularUser->id,
            'status' => 'inactive',
        ]);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'user.deactivated',
            'target_id' => $this->regularUser->id,
        ]);
    }

    public function test_cannot_deactivate_own_account(): void
    {
        $response = $this->actingAs($this->sysAdmin)->put(route('admin.users.deactivate', $this->sysAdmin));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', [
            'id' => $this->sysAdmin->id,
            'status' => 'active',
        ]);
    }

    public function test_sysadmin_can_activate_user(): void
    {
        $inactiveUser = User::factory()->create(['status' => 'inactive', 'role' => 'regular_user', 'must_change_password' => false]);

        $response = $this->actingAs($this->sysAdmin)->put(route('admin.users.activate', $inactiveUser));

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $inactiveUser->id,
            'status' => 'active',
        ]);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'user.reactivated',
            'target_id' => $inactiveUser->id,
        ]);
    }

    public function test_cannot_delete_user_with_bookings(): void
    {
        // Create a room first
        $room = Room::factory()->create();

        // Create a booking for the user
        Booking::factory()->create([
            'user_id' => $this->regularUser->id,
            'room_id' => $room->id,
        ]);

        $response = $this->actingAs($this->sysAdmin)->delete(route('admin.users.destroy', $this->regularUser));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->regularUser->id]);
    }

    public function test_cannot_delete_user_with_audit_activity(): void
    {
        // Create an audit log entry for the user as actor
        AuditLog::create([
            'event_type' => 'test_event',
            'actor_id' => $this->regularUser->id,
            'actor_name' => $this->regularUser->name,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->sysAdmin)->delete(route('admin.users.destroy', $this->regularUser));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->regularUser->id]);
    }

    public function test_can_delete_user_without_activity(): void
    {
        $newUser = User::factory()->create(['must_change_password' => false]);

        $response = $this->actingAs($this->sysAdmin)->delete(route('admin.users.destroy', $newUser));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $newUser->id]);
    }

    public function test_cannot_delete_own_account(): void
    {
        $response = $this->actingAs($this->sysAdmin)->delete(route('admin.users.destroy', $this->sysAdmin));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->sysAdmin->id]);
    }

    public function test_sysadmin_can_reset_user_password(): void
    {
        $response = $this->actingAs($this->sysAdmin)->post(route('admin.users.reset-password', $this->regularUser), [
            'method' => 'generate',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'id' => $this->regularUser->id,
            'must_change_password' => true,
        ]);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'user.password_reset_by_admin',
            'target_id' => $this->regularUser->id,
        ]);
    }

    public function test_user_search_filter_works(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.users.index', ['search' => $this->regularUser->name]));

        $response->assertStatus(200);
        $response->assertSee($this->regularUser->name);
    }

    public function test_user_role_filter_works(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.users.index', ['role' => 'director']));

        $response->assertStatus(200);
        $response->assertSee($this->director->name);
        // The regular user should not be visible when filtering by director role
        $response->assertDontSee($this->regularUser->email);
    }

    public function test_user_status_filter_works(): void
    {
        $inactiveUser = User::factory()->create(['status' => 'inactive', 'name' => 'Inactive Test User', 'must_change_password' => false]);

        $response = $this->actingAs($this->sysAdmin)->get(route('admin.users.index', ['status' => 'inactive']));

        $response->assertStatus(200);
        $response->assertSee('Inactive Test User');
    }

    public function test_sysadmin_can_view_user_activity(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.users.activity', $this->regularUser));

        $response->assertStatus(200);
        $response->assertSee('User Activity');
        $response->assertSee($this->regularUser->name);
    }

    public function test_administrator_cannot_access_user_management(): void
    {
        $admin = User::factory()->create(['role' => 'administrator', 'must_change_password' => false]);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));
        $response->assertStatus(403);
    }
}
