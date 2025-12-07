<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Run the seeder to create test users
        $this->seed(\Database\Seeders\UserSeeder::class);
    }

    public function test_successful_login_creates_audit_log_entry(): void
    {
        $user = User::where('email', 'user@mrbs.local')->first();

        $this->post(route('login'), [
            'email' => 'user@mrbs.local',
            'password' => 'password123',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'login_success',
            'actor_id' => $user->id,
            'target_type' => 'user',
        ]);
    }

    public function test_failed_login_creates_audit_log_entry(): void
    {
        $this->post(route('login'), [
            'email' => 'user@mrbs.local',
            'password' => 'wrongpassword',
        ]);

        $log = AuditLog::where('event_type', 'login_failed')->first();

        $this->assertNotNull($log);
        $this->assertNull($log->actor_id);
        $this->assertEquals(['email' => 'user@mrbs.local'], $log->details);
    }

    public function test_inactive_user_login_creates_failed_audit_log(): void
    {
        $user = User::where('email', 'user@mrbs.local')->first();
        $user->update(['status' => 'inactive']);

        $this->post(route('login'), [
            'email' => 'user@mrbs.local',
            'password' => 'password123',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'login_failed',
        ]);
    }

    public function test_logout_creates_audit_log_entry(): void
    {
        $user = User::where('email', 'user@mrbs.local')->first();

        $this->actingAs($user);

        $this->post(route('logout'));

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'logout',
            'actor_id' => $user->id,
            'target_type' => 'user',
        ]);
    }

    public function test_password_reset_request_is_logged(): void
    {
        $this->post(route('password.email'), [
            'email' => 'user@mrbs.local',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'password_reset_requested',
        ]);

        $log = AuditLog::where('event_type', 'password_reset_requested')->first();
        $this->assertEquals(['email' => 'user@mrbs.local'], $log->details);
    }

    public function test_password_change_is_logged(): void
    {
        $user = User::where('email', 'user@mrbs.local')->first();

        $this->actingAs($user);

        $this->put(route('profile.password.update'), [
            'current_password' => 'password123',
            'password' => 'NewSecure1@Pass',
            'password_confirmation' => 'NewSecure1@Pass',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'password_changed',
            'actor_id' => $user->id,
            'target_type' => 'user',
        ]);
    }

    public function test_profile_update_is_logged(): void
    {
        $user = User::where('email', 'user@mrbs.local')->first();

        $this->actingAs($user);

        $this->put(route('profile.update'), [
            'name' => 'Updated Name',
            'department' => 'IT Department',
            'phone' => '012-3456789',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'profile_updated',
            'actor_id' => $user->id,
            'target_type' => 'user',
        ]);

        $log = AuditLog::where('event_type', 'profile_updated')->first();
        $this->assertArrayHasKey('changed_fields', $log->details);
    }

    public function test_audit_logs_cannot_be_updated(): void
    {
        $user = User::where('email', 'user@mrbs.local')->first();

        $log = AuditLog::create([
            'event_type' => 'test_event',
            'actor_id' => $user->id,
            'actor_name' => $user->name,
            'target_type' => 'user',
            'target_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $originalEventType = $log->event_type;

        // Attempt to update
        $log->event_type = 'modified_event';
        $log->save();

        // Refresh from database
        $log->refresh();

        // Should still have original value
        $this->assertEquals($originalEventType, $log->event_type);
    }

    public function test_audit_logs_cannot_be_deleted(): void
    {
        $user = User::where('email', 'user@mrbs.local')->first();

        $log = AuditLog::create([
            'event_type' => 'test_event',
            'actor_id' => $user->id,
            'actor_name' => $user->name,
            'target_type' => 'user',
            'target_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $logId = $log->id;

        // Attempt to delete
        $log->delete();

        // Should still exist in database
        $this->assertDatabaseHas('audit_logs', [
            'id' => $logId,
        ]);
    }

    public function test_audit_service_logs_generic_event(): void
    {
        $user = User::where('email', 'user@mrbs.local')->first();

        $this->actingAs($user);

        AuditService::log('custom_event', 'booking', 123, ['custom_data' => 'test']);

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'custom_event',
            'actor_id' => $user->id,
            'target_type' => 'booking',
            'target_id' => 123,
        ]);
    }

    public function test_audit_log_scopes_work_correctly(): void
    {
        $user = User::where('email', 'user@mrbs.local')->first();

        AuditLog::create([
            'event_type' => 'login_success',
            'actor_id' => $user->id,
            'actor_name' => $user->name,
            'target_type' => 'user',
            'target_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        AuditLog::create([
            'event_type' => 'logout',
            'actor_id' => $user->id,
            'actor_name' => $user->name,
            'target_type' => 'user',
            'target_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        // Test scopeByEventType
        $this->assertEquals(1, AuditLog::byEventType('login_success')->count());
        $this->assertEquals(1, AuditLog::byEventType('logout')->count());

        // Test scopeByActor
        $this->assertEquals(2, AuditLog::byActor($user->id)->count());

        // Test scopeByTarget
        $this->assertEquals(2, AuditLog::byTarget('user', $user->id)->count());
    }

    public function test_event_display_accessor_returns_readable_name(): void
    {
        $log = new AuditLog(['event_type' => 'login_success']);
        $this->assertEquals('Login Success', $log->event_display);

        $log = new AuditLog(['event_type' => 'booking_created']);
        $this->assertEquals('Booking Created', $log->event_display);

        $log = new AuditLog(['event_type' => 'custom_unknown_event']);
        $this->assertEquals('Custom Unknown Event', $log->event_display);
    }
}
