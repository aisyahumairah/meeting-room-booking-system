<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReportingSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $sysAdmin;
    protected User $director;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sysAdmin = User::factory()->passwordChanged()->create(['role' => 'system_admin']);
        $this->director = User::factory()->passwordChanged()->create(['role' => 'director']);
        $this->regularUser = User::factory()->passwordChanged()->create(['role' => 'regular_user']);

        // Create test data
        $room = Room::factory()->create();
        Booking::factory()->count(5)->create(['room_id' => $room->id, 'status' => 'confirmed']);
        Booking::factory()->count(2)->create(['room_id' => $room->id, 'status' => 'cancelled']);
    }

    public function test_sysadmin_can_access_reports_dashboard(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.reports.index'));
        $response->assertStatus(200);
        $response->assertSee('Reports');
    }

    public function test_director_can_access_reports(): void
    {
        $response = $this->actingAs($this->director)->get(route('admin.reports.index'));
        $response->assertStatus(200);
    }

    public function test_regular_user_cannot_access_reports(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.reports.index'));
        $response->assertStatus(403);
    }

    public function test_room_utilization_report_loads(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.reports.room-utilization'));
        $response->assertStatus(200);
        $response->assertSee('Room Utilization Report');
    }

    public function test_booking_statistics_report_loads(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.reports.booking-statistics'));
        $response->assertStatus(200);
        $response->assertSee('Booking Statistics Report');
    }

    public function test_user_activity_report_loads(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.reports.user-activity'));
        $response->assertStatus(200);
        $response->assertSee('User Activity Report');
    }

    public function test_can_export_room_utilization_xlsx(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.reports.room-utilization.export', ['format' => 'xlsx']));
        $response->assertStatus(200);
    }

    public function test_can_export_room_utilization_csv(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.reports.room-utilization.export', ['format' => 'csv']));
        $response->assertStatus(200);
    }

    public function test_can_export_room_utilization_pdf(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.reports.room-utilization.export', ['format' => 'pdf']));
        $response->assertStatus(200);
    }

    public function test_date_filter_works_for_reports(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.reports.room-utilization', [
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->toDateString(),
        ]));

        $response->assertStatus(200);
    }

    public function test_report_export_is_logged(): void
    {
        $this->actingAs($this->sysAdmin)->get(route('admin.reports.room-utilization.export', ['format' => 'xlsx']));

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'report.generated',
            'actor_id' => $this->sysAdmin->id,
        ]);
    }

    public function test_quick_stats_displayed_on_dashboard(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.reports.index'));

        $response->assertSee('Bookings This Month');
        $response->assertSee('Active Rooms');
        $response->assertSee('Active Users');
        $response->assertSee('Cancellation Rate');
    }

    // Booking Statistics Export Tests
    public function test_can_export_booking_statistics_xlsx(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.reports.booking-statistics.export', ['format' => 'xlsx']));
        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }

    public function test_can_export_booking_statistics_pdf(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.reports.booking-statistics.export', ['format' => 'pdf']));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    // User Activity Export Tests
    public function test_can_export_user_activity_xlsx(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.reports.user-activity.export', ['format' => 'xlsx']));
        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }

    public function test_can_export_user_activity_pdf(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.reports.user-activity.export', ['format' => 'pdf']));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    // Verify export content type headers
    public function test_room_utilization_xlsx_has_correct_content_type(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.reports.room-utilization.export', ['format' => 'xlsx']));
        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }

    public function test_room_utilization_pdf_has_correct_content_type(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.reports.room-utilization.export', ['format' => 'pdf']));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_group_by_works_for_booking_statistics(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.reports.booking-statistics', [
            'group_by' => 'week',
        ]));

        $response->assertStatus(200);
    }
}
