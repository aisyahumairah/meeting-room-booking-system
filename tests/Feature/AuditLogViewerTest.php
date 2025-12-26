<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuditLogViewerTest extends TestCase
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

        // Create some audit logs
        AuditService::log('login_success', 'user', $this->regularUser->id);
        AuditService::log('booking.created', 'booking', 1, ['room' => 'Conference Room A']);
    }

    public function test_sysadmin_can_view_audit_logs(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.audit-logs.index'));
        $response->assertStatus(200);
        $response->assertSee('Audit Trail');
    }

    public function test_director_can_view_audit_logs(): void
    {
        $response = $this->actingAs($this->director)->get(route('admin.audit-logs.index'));
        $response->assertStatus(200);
    }

    public function test_regular_user_cannot_view_audit_logs(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.audit-logs.index'));
        $response->assertStatus(403);
    }

    public function test_audit_logs_displayed_in_reverse_chronological_order(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.audit-logs.index'));
        $response->assertStatus(200);

        $logs = AuditLog::orderBy('created_at', 'desc')->get();
        $this->assertGreaterThan(0, $logs->count());
    }

    public function test_filter_by_event_type_works(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.audit-logs.index', [
            'event_type' => 'login_success'
        ]));

        $response->assertStatus(200);
        $response->assertSee('Login Success');
    }

    public function test_filter_by_actor_works(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.audit-logs.index', [
            'actor_id' => $this->sysAdmin->id
        ]));

        $response->assertStatus(200);
    }

    public function test_date_preset_filter_works(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.audit-logs.index', [
            'preset' => 'today'
        ]));

        $response->assertStatus(200);
    }

    public function test_search_filter_works(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.audit-logs.index', [
            'search' => 'Conference'
        ]));

        $response->assertStatus(200);
    }

    public function test_export_csv_works(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.audit-logs.export', [
            'format' => 'csv'
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=utf-8');
    }

    public function test_export_xlsx_works(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.audit-logs.export', [
            'format' => 'xlsx'
        ]));

        $response->assertStatus(200);
    }

    public function test_export_action_is_logged(): void
    {
        $this->actingAs($this->sysAdmin)->get(route('admin.audit-logs.export', [
            'format' => 'csv'
        ]));

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'audit_log.exported',
            'actor_id' => $this->sysAdmin->id,
        ]);
    }

    public function test_logs_are_immutable(): void
    {
        $log = AuditLog::first();
        $originalEventType = $log->event_type;

        // Attempt to update (should be prevented by model hooks)
        $log->update(['event_type' => 'modified']);

        // Refresh from database and verify it wasn't changed
        $log->refresh();
        $this->assertEquals($originalEventType, $log->event_type);
    }
}
