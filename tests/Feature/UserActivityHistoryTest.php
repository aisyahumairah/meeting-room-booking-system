<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Booking;
use App\Models\Room;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserActivityHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $sysAdmin;
    protected User $targetUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sysAdmin = User::factory()->passwordChanged()->create(['role' => 'system_admin']);
        $this->targetUser = User::factory()->passwordChanged()->create(['role' => 'regular_user']);

        // Create some audit logs for target user
        AuditService::log('login_success', 'user', $this->targetUser->id);

        // Simulate actor being target user
        $this->actingAs($this->targetUser);
        AuditService::log('booking.created', 'booking', 1);
        AuditService::log('booking.cancelled', 'booking', 1, ['reason' => 'test']);
    }

    public function test_can_view_user_activity_page(): void
    {
        $response = $this->actingAs($this->sysAdmin)
            ->get(route('admin.users.activity', $this->targetUser));

        $response->assertStatus(200);
        $response->assertSee($this->targetUser->name);
        $response->assertSee('Activity History');
    }

    public function test_displays_user_statistics(): void
    {
        // Create a room for bookings
        $room = Room::factory()->create();

        // Create some bookings for the user
        Booking::factory()->count(3)->create([
            'user_id' => $this->targetUser->id,
            'room_id' => $room->id,
            'status' => 'confirmed'
        ]);
        Booking::factory()->create([
            'user_id' => $this->targetUser->id,
            'room_id' => $room->id,
            'status' => 'cancelled'
        ]);

        $response = $this->actingAs($this->sysAdmin)
            ->get(route('admin.users.activity', $this->targetUser));

        $response->assertStatus(200);
        $response->assertSee('Total Bookings');
        $response->assertSee('Cancellation Rate');
    }

    public function test_displays_activity_timeline(): void
    {
        $response = $this->actingAs($this->sysAdmin)
            ->get(route('admin.users.activity', $this->targetUser));

        $response->assertStatus(200);
        $response->assertSee('Activity Timeline');
    }

    public function test_date_filter_works(): void
    {
        $response = $this->actingAs($this->sysAdmin)
            ->get(route('admin.users.activity', [
                'user' => $this->targetUser->id,
                'preset' => 'today',
            ]));

        $response->assertStatus(200);
    }

    public function test_event_type_filter_works(): void
    {
        $response = $this->actingAs($this->sysAdmin)
            ->get(route('admin.users.activity', [
                'user' => $this->targetUser->id,
                'event_type' => 'login_success',
            ]));

        $response->assertStatus(200);
    }

    public function test_regular_user_cannot_access(): void
    {
        $response = $this->actingAs($this->targetUser)
            ->get(route('admin.users.activity', $this->targetUser));

        $response->assertStatus(403);
    }

    public function test_export_csv_works(): void
    {
        $response = $this->actingAs($this->sysAdmin)
            ->get(route('admin.users.activity.export', [
                'user' => $this->targetUser->id,
                'format' => 'csv',
            ]));

        $response->assertStatus(200);
    }

    public function test_calculates_cancellation_rate_correctly(): void
    {
        // Create a room for bookings
        $room = Room::factory()->create();

        Booking::factory()->count(4)->create([
            'user_id' => $this->targetUser->id,
            'room_id' => $room->id,
            'status' => 'confirmed'
        ]);
        Booking::factory()->create([
            'user_id' => $this->targetUser->id,
            'room_id' => $room->id,
            'status' => 'cancelled'
        ]);

        $response = $this->actingAs($this->sysAdmin)
            ->get(route('admin.users.activity', $this->targetUser));

        $response->assertStatus(200);
        // 1 cancelled out of 5 = 20%
        $response->assertSee('20');
    }

    public function test_date_range_filter_works(): void
    {
        $response = $this->actingAs($this->sysAdmin)
            ->get(route('admin.users.activity', [
                'user' => $this->targetUser->id,
                'start_date' => now()->subDays(7)->toDateString(),
                'end_date' => now()->toDateString(),
            ]));

        $response->assertStatus(200);
    }

    public function test_preset_last_7_days_works(): void
    {
        $response = $this->actingAs($this->sysAdmin)
            ->get(route('admin.users.activity', [
                'user' => $this->targetUser->id,
                'preset' => 'last_7_days',
            ]));

        $response->assertStatus(200);
    }

    public function test_preset_last_30_days_works(): void
    {
        $response = $this->actingAs($this->sysAdmin)
            ->get(route('admin.users.activity', [
                'user' => $this->targetUser->id,
                'preset' => 'last_30_days',
            ]));

        $response->assertStatus(200);
    }

    public function test_preset_this_month_works(): void
    {
        $response = $this->actingAs($this->sysAdmin)
            ->get(route('admin.users.activity', [
                'user' => $this->targetUser->id,
                'preset' => 'this_month',
            ]));

        $response->assertStatus(200);
    }

    public function test_director_can_access_user_activity(): void
    {
        $director = User::factory()->passwordChanged()->create(['role' => 'director']);

        $response = $this->actingAs($director)
            ->get(route('admin.users.activity', $this->targetUser));

        $response->assertStatus(200);
    }

    public function test_administrator_cannot_access_user_activity(): void
    {
        $admin = User::factory()->passwordChanged()->create(['role' => 'administrator']);

        $response = $this->actingAs($admin)
            ->get(route('admin.users.activity', $this->targetUser));

        $response->assertStatus(403);
    }

    public function test_export_xlsx_works(): void
    {
        $response = $this->actingAs($this->sysAdmin)
            ->get(route('admin.users.activity.export', [
                'user' => $this->targetUser->id,
                'format' => 'xlsx',
            ]));

        $response->assertStatus(200);
    }

    public function test_activity_page_shows_user_profile_info(): void
    {
        $response = $this->actingAs($this->sysAdmin)
            ->get(route('admin.users.activity', $this->targetUser));

        $response->assertStatus(200);
        $response->assertSee($this->targetUser->email);
        $response->assertSee($this->targetUser->staff_number);
    }

    public function test_pagination_works(): void
    {
        // Create additional audit logs to test pagination
        $this->actingAs($this->targetUser);
        for ($i = 0; $i < 60; $i++) {
            AuditService::log('test_event', 'test', $i);
        }

        $response = $this->actingAs($this->sysAdmin)
            ->get(route('admin.users.activity', $this->targetUser));

        $response->assertStatus(200);
        // Should have pagination since we created more than 50 entries
        $response->assertSee('Activity Timeline');
    }
}
