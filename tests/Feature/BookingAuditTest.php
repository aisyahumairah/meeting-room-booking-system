<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use App\Models\AuditLog;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['status' => 'active']);
        $this->room = Room::factory()->create(['status' => 'active']);
    }

    public function test_booking_creation_is_logged()
    {
        $this->actingAs($this->user)
            ->post(route('bookings.store'), [
                'room_id' => $this->room->id,
                'booking_date' => now()->addDays(5)->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '10:00',
                'purpose' => 'Test meeting',
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'booking_created',
            'target_type' => 'booking',
            'actor_id' => $this->user->id,
        ]);
    }

    public function test_booking_cancellation_is_logged()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'confirmed',
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $this->actingAs($this->user)
            ->delete(route('my-bookings.destroy', $booking), [
                'cancellation_reason' => 'Meeting cancelled',
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'booking_cancelled',
            'target_type' => 'booking',
            'target_id' => $booking->id,
        ]);
    }

    public function test_audit_log_contains_required_details()
    {
        $this->actingAs($this->user)
            ->post(route('bookings.store'), [
                'room_id' => $this->room->id,
                'booking_date' => now()->addDays(5)->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '10:00',
                'purpose' => 'Test meeting',
            ]);

        $log = AuditLog::where('event_type', 'booking_created')->first();

        $this->assertNotNull($log);
        $this->assertIsArray($log->details);
        $this->assertArrayHasKey('reference', $log->details);
        $this->assertArrayHasKey('room', $log->details);
        $this->assertArrayHasKey('date', $log->details);
    }

    public function test_admin_can_view_booking_audit_history()
    {
        $admin = User::factory()->create(['role' => 'administrator', 'status' => 'active']);
        $booking = Booking::factory()->create();

        // Create some audit logs
        AuditLog::create([
            'actor_id' => $admin->id,
            'event_type' => 'booking_created',
            'target_type' => 'booking',
            'target_id' => $booking->id,
            'actor_name' => $admin->name,
            'details' => ['reference' => $booking->reference_number],
        ]);

        $response = $this->actingAs($admin)
            ->get(route('my-bookings.show', $booking));

        $response->assertStatus(200);
        $response->assertSee('Audit History');
    }
}
