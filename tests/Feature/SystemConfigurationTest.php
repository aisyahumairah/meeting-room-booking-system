<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SystemConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected User $sysAdmin;
    protected User $director;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\SystemSettingSeeder::class);

        $this->sysAdmin = User::factory()->passwordChanged()->create(['role' => 'system_admin']);
        $this->director = User::factory()->passwordChanged()->create(['role' => 'director']);
        $this->regularUser = User::factory()->passwordChanged()->create(['role' => 'regular_user']);
    }

    public function test_sysadmin_can_access_settings(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.settings.index'));
        $response->assertStatus(200);
        $response->assertSee('System Configuration');
    }

    public function test_director_cannot_access_settings(): void
    {
        $response = $this->actingAs($this->director)->get(route('admin.settings.index'));
        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_access_settings(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.settings.index'));
        $response->assertStatus(403);
    }

    public function test_can_update_session_timeout(): void
    {
        $response = $this->actingAs($this->sysAdmin)->put(route('admin.settings.update'), [
            'session_timeout' => 60,
            'password_reset_expiry' => 30,
            'login_attempt_limit' => 5,
            'lockout_duration' => 15,
        ]);

        $response->assertRedirect(route('admin.settings.index'));
        $this->assertEquals(60, SystemSetting::get('session_timeout'));
    }

    public function test_validates_session_timeout_range(): void
    {
        $response = $this->actingAs($this->sysAdmin)->put(route('admin.settings.update'), [
            'session_timeout' => 200, // Above max of 120
            'password_reset_expiry' => 30,
            'login_attempt_limit' => 5,
            'lockout_duration' => 15,
        ]);

        $response->assertSessionHasErrors('session_timeout');
    }

    public function test_can_toggle_email_notifications(): void
    {
        $response = $this->actingAs($this->sysAdmin)->put(route('admin.settings.update'), [
            'session_timeout' => 30,
            'password_reset_expiry' => 30,
            'login_attempt_limit' => 5,
            'lockout_duration' => 15,
            'email_enabled' => false,
        ]);

        $response->assertRedirect();
        $this->assertFalse(SystemSetting::isEmailEnabled());
    }

    public function test_can_enable_maintenance_mode(): void
    {
        $response = $this->actingAs($this->sysAdmin)->put(route('admin.settings.update'), [
            'session_timeout' => 30,
            'password_reset_expiry' => 30,
            'login_attempt_limit' => 5,
            'lockout_duration' => 15,
            'maintenance_mode' => true,
        ]);

        $response->assertRedirect();
        $this->assertTrue(SystemSetting::isMaintenanceMode());
    }

    public function test_maintenance_mode_blocks_regular_users(): void
    {
        SystemSetting::set('maintenance_mode', 'true', 'bool');

        $response = $this->actingAs($this->regularUser)->get(route('dashboard'));
        $response->assertStatus(503);
    }

    public function test_maintenance_mode_allows_admins(): void
    {
        SystemSetting::set('maintenance_mode', 'true', 'bool');

        $response = $this->actingAs($this->sysAdmin)->get(route('admin.settings.index'));
        $response->assertStatus(200);
    }

    public function test_can_reset_settings_to_defaults(): void
    {
        SystemSetting::set('session_timeout', 120, 'int');

        $response = $this->actingAs($this->sysAdmin)->post(route('admin.settings.reset'), [
            'group' => 'security',
        ]);

        $response->assertRedirect();
        $this->assertEquals(30, SystemSetting::get('session_timeout'));
    }

    public function test_settings_changes_are_logged(): void
    {
        $this->actingAs($this->sysAdmin)->put(route('admin.settings.update'), [
            'session_timeout' => 45,
            'password_reset_expiry' => 30,
            'login_attempt_limit' => 5,
            'lockout_duration' => 15,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'settings.updated',
            'actor_id' => $this->sysAdmin->id,
        ]);
    }

    public function test_system_setting_model_get_and_set(): void
    {
        SystemSetting::set('test_key', 'test_value', 'string');
        $this->assertEquals('test_value', SystemSetting::get('test_key'));

        SystemSetting::set('test_int', 42, 'int');
        $this->assertSame(42, SystemSetting::get('test_int'));

        SystemSetting::set('test_bool', 'true', 'bool');
        $this->assertTrue(SystemSetting::get('test_bool'));
    }

    public function test_settings_page_displays_all_sections(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.settings.index'));

        $response->assertStatus(200);
        $response->assertSee('Session');
        $response->assertSee('Security');
        $response->assertSee('Password Policy');
        $response->assertSee('Booking Rules');
        $response->assertSee('Notification Settings');
        $response->assertSee('System Maintenance');
    }

    public function test_unauthenticated_user_cannot_access_settings(): void
    {
        $response = $this->get(route('admin.settings.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_maintenance_mode_returns_json_for_ajax_requests(): void
    {
        SystemSetting::set('maintenance_mode', 'true', 'bool');

        $response = $this->actingAs($this->regularUser)
            ->withHeader('Accept', 'application/json')
            ->get(route('dashboard'));

        $response->assertStatus(503);
        $response->assertJson(['message' => 'System is currently under maintenance. Please try again later.']);
    }

    public function test_can_reset_notification_settings(): void
    {
        SystemSetting::set('email_enabled', 'false', 'bool');

        $response = $this->actingAs($this->sysAdmin)->post(route('admin.settings.reset'), [
            'group' => 'notifications',
        ]);

        $response->assertRedirect();
        $this->assertTrue(SystemSetting::isEmailEnabled());
    }
}
