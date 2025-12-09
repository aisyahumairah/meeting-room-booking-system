<?php

namespace Tests\Unit;

use App\Models\Room;
use App\Models\RoomMaintenanceSchedule;
use App\Models\User;
use App\Services\RoomMaintenanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class RoomMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    protected Room $room;
    protected User $admin;
    protected RoomMaintenanceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->administrator()->create();
        $this->room = Room::factory()->create(['status' => 'active']);
        $this->service = new RoomMaintenanceService();
    }

    // =====================
    // MODEL TESTS
    // =====================

    public function test_maintenance_schedule_can_be_created(): void
    {
        $schedule = RoomMaintenanceSchedule::create([
            'room_id' => $this->room->id,
            'start_datetime' => Carbon::now()->addDay(),
            'end_datetime' => Carbon::now()->addDay()->addHours(4),
            'reason' => 'Regular maintenance',
            'created_by' => $this->admin->id,
        ]);

        $this->assertDatabaseHas('room_maintenance_schedules', [
            'id' => $schedule->id,
            'room_id' => $this->room->id,
            'reason' => 'Regular maintenance',
        ]);
    }

    public function test_maintenance_schedule_has_room_relationship(): void
    {
        $schedule = RoomMaintenanceSchedule::create([
            'room_id' => $this->room->id,
            'start_datetime' => Carbon::now()->addDay(),
            'end_datetime' => Carbon::now()->addDay()->addHours(4),
        ]);

        $this->assertInstanceOf(Room::class, $schedule->room);
        $this->assertEquals($this->room->id, $schedule->room->id);
    }

    public function test_maintenance_schedule_has_creator_relationship(): void
    {
        $schedule = RoomMaintenanceSchedule::create([
            'room_id' => $this->room->id,
            'start_datetime' => Carbon::now()->addDay(),
            'end_datetime' => Carbon::now()->addDay()->addHours(4),
            'created_by' => $this->admin->id,
        ]);

        $this->assertInstanceOf(User::class, $schedule->creator);
        $this->assertEquals($this->admin->id, $schedule->creator->id);
    }

    // =====================
    // SCOPE TESTS
    // =====================

    public function test_active_scope_returns_current_maintenance(): void
    {
        // Create a past schedule
        RoomMaintenanceSchedule::create([
            'room_id' => $this->room->id,
            'start_datetime' => Carbon::now()->subDays(2),
            'end_datetime' => Carbon::now()->subDay(),
            'reason' => 'Past maintenance',
        ]);

        // Create a current/active schedule
        $activeSchedule = RoomMaintenanceSchedule::create([
            'room_id' => $this->room->id,
            'start_datetime' => Carbon::now()->subHour(),
            'end_datetime' => Carbon::now()->addHours(3),
            'reason' => 'Active maintenance',
        ]);

        // Create a future schedule
        RoomMaintenanceSchedule::create([
            'room_id' => $this->room->id,
            'start_datetime' => Carbon::now()->addDays(2),
            'end_datetime' => Carbon::now()->addDays(2)->addHours(4),
            'reason' => 'Future maintenance',
        ]);

        $activeSchedules = RoomMaintenanceSchedule::active()->get();

        $this->assertCount(1, $activeSchedules);
        $this->assertEquals($activeSchedule->id, $activeSchedules->first()->id);
    }

    public function test_upcoming_scope_returns_future_schedules(): void
    {
        // Create a past schedule
        RoomMaintenanceSchedule::create([
            'room_id' => $this->room->id,
            'start_datetime' => Carbon::now()->subDays(2),
            'end_datetime' => Carbon::now()->subDay(),
        ]);

        // Create future schedules
        $future1 = RoomMaintenanceSchedule::create([
            'room_id' => $this->room->id,
            'start_datetime' => Carbon::now()->addDay(),
            'end_datetime' => Carbon::now()->addDay()->addHours(4),
        ]);

        $future2 = RoomMaintenanceSchedule::create([
            'room_id' => $this->room->id,
            'start_datetime' => Carbon::now()->addDays(3),
            'end_datetime' => Carbon::now()->addDays(3)->addHours(4),
        ]);

        $upcoming = RoomMaintenanceSchedule::upcoming()->get();

        $this->assertCount(2, $upcoming);
        $this->assertTrue($upcoming->contains($future1));
        $this->assertTrue($upcoming->contains($future2));
    }

    public function test_past_scope_returns_completed_schedules(): void
    {
        // Create past schedules
        $past = RoomMaintenanceSchedule::create([
            'room_id' => $this->room->id,
            'start_datetime' => Carbon::now()->subDays(2),
            'end_datetime' => Carbon::now()->subDay(),
        ]);

        // Create future schedule
        RoomMaintenanceSchedule::create([
            'room_id' => $this->room->id,
            'start_datetime' => Carbon::now()->addDay(),
            'end_datetime' => Carbon::now()->addDay()->addHours(4),
        ]);

        $pastSchedules = RoomMaintenanceSchedule::past()->get();

        $this->assertCount(1, $pastSchedules);
        $this->assertEquals($past->id, $pastSchedules->first()->id);
    }

    // =====================
    // ACCESSOR TESTS
    // =====================

    public function test_is_active_accessor_works_correctly(): void
    {
        // Active schedule (happening now)
        $activeSchedule = RoomMaintenanceSchedule::create([
            'room_id' => $this->room->id,
            'start_datetime' => Carbon::now()->subHour(),
            'end_datetime' => Carbon::now()->addHours(3),
        ]);

        // Future schedule
        $futureSchedule = RoomMaintenanceSchedule::create([
            'room_id' => $this->room->id,
            'start_datetime' => Carbon::now()->addDay(),
            'end_datetime' => Carbon::now()->addDay()->addHours(4),
        ]);

        // Past schedule
        $pastSchedule = RoomMaintenanceSchedule::create([
            'room_id' => $this->room->id,
            'start_datetime' => Carbon::now()->subDays(2),
            'end_datetime' => Carbon::now()->subDay(),
        ]);

        $this->assertTrue($activeSchedule->is_active);
        $this->assertFalse($futureSchedule->is_active);
        $this->assertFalse($pastSchedule->is_active);
    }

    public function test_status_accessor_returns_correct_status(): void
    {
        // Active schedule
        $activeSchedule = RoomMaintenanceSchedule::create([
            'room_id' => $this->room->id,
            'start_datetime' => Carbon::now()->subHour(),
            'end_datetime' => Carbon::now()->addHours(3),
        ]);

        // Scheduled (future) schedule
        $scheduledSchedule = RoomMaintenanceSchedule::create([
            'room_id' => $this->room->id,
            'start_datetime' => Carbon::now()->addDay(),
            'end_datetime' => Carbon::now()->addDay()->addHours(4),
        ]);

        // Completed (past) schedule
        $completedSchedule = RoomMaintenanceSchedule::create([
            'room_id' => $this->room->id,
            'start_datetime' => Carbon::now()->subDays(2),
            'end_datetime' => Carbon::now()->subDay(),
        ]);

        $this->assertEquals('active', $activeSchedule->status);
        $this->assertEquals('scheduled', $scheduledSchedule->status);
        $this->assertEquals('completed', $completedSchedule->status);
    }

    public function test_date_range_accessor_formats_correctly(): void
    {
        // Same day schedule
        $sameDaySchedule = RoomMaintenanceSchedule::create([
            'room_id' => $this->room->id,
            'start_datetime' => Carbon::parse('2025-01-15 09:00:00'),
            'end_datetime' => Carbon::parse('2025-01-15 12:00:00'),
        ]);

        // Multi-day schedule
        $multiDaySchedule = RoomMaintenanceSchedule::create([
            'room_id' => $this->room->id,
            'start_datetime' => Carbon::parse('2025-01-15 09:00:00'),
            'end_datetime' => Carbon::parse('2025-01-17 17:00:00'),
        ]);

        $this->assertStringContainsString('Jan 15, 2025', $sameDaySchedule->date_range);
        $this->assertStringContainsString('9:00 AM', $sameDaySchedule->date_range);
        $this->assertStringContainsString('12:00 PM', $sameDaySchedule->date_range);

        $this->assertStringContainsString('Jan 15, 2025', $multiDaySchedule->date_range);
        $this->assertStringContainsString('Jan 17, 2025', $multiDaySchedule->date_range);
    }

    // =====================
    // METHOD TESTS
    // =====================

    public function test_conflicts_with_method_detects_overlaps(): void
    {
        // Schedule from 10:00 to 14:00 on a specific date
        $schedule = RoomMaintenanceSchedule::create([
            'room_id' => $this->room->id,
            'start_datetime' => Carbon::parse('2025-01-20 10:00:00'),
            'end_datetime' => Carbon::parse('2025-01-20 14:00:00'),
        ]);

        // Overlapping time slots
        $this->assertTrue($schedule->conflictsWith('2025-01-20', '09:00', '11:00')); // Starts before, ends during
        $this->assertTrue($schedule->conflictsWith('2025-01-20', '11:00', '13:00')); // Completely inside
        $this->assertTrue($schedule->conflictsWith('2025-01-20', '13:00', '15:00')); // Starts during, ends after
        $this->assertTrue($schedule->conflictsWith('2025-01-20', '09:00', '15:00')); // Completely contains

        // Non-overlapping time slots
        $this->assertFalse($schedule->conflictsWith('2025-01-20', '08:00', '10:00')); // Ends exactly at start
        $this->assertFalse($schedule->conflictsWith('2025-01-20', '14:00', '16:00')); // Starts exactly at end
        $this->assertFalse($schedule->conflictsWith('2025-01-20', '06:00', '08:00')); // Completely before
        $this->assertFalse($schedule->conflictsWith('2025-01-20', '16:00', '18:00')); // Completely after
        $this->assertFalse($schedule->conflictsWith('2025-01-21', '10:00', '14:00')); // Different day
    }

    // =====================
    // SERVICE TESTS
    // =====================

    public function test_service_validates_end_date_after_start_date(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('End datetime must be after start datetime.');

        $this->service->scheduleMaintenance(
            $this->room,
            Carbon::now()->addDay(),
            Carbon::now(), // End before start
            'Invalid maintenance'
        );
    }

    public function test_service_detects_schedule_conflicts(): void
    {
        // Create an existing schedule
        RoomMaintenanceSchedule::create([
            'room_id' => $this->room->id,
            'start_datetime' => Carbon::parse('2025-02-01 10:00:00'),
            'end_datetime' => Carbon::parse('2025-02-01 14:00:00'),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('conflicts with an existing schedule');

        // Try to create overlapping schedule
        $this->service->scheduleMaintenance(
            $this->room,
            Carbon::parse('2025-02-01 12:00:00'),
            Carbon::parse('2025-02-01 16:00:00'),
            'Conflicting maintenance'
        );
    }

    public function test_service_creates_schedule_successfully(): void
    {
        $schedule = $this->service->scheduleMaintenance(
            $this->room,
            Carbon::parse('2025-03-01 09:00:00'),
            Carbon::parse('2025-03-01 17:00:00'),
            'Annual maintenance',
            $this->admin
        );

        $this->assertDatabaseHas('room_maintenance_schedules', [
            'id' => $schedule->id,
            'room_id' => $this->room->id,
            'reason' => 'Annual maintenance',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_service_cancels_maintenance_and_reverts_room_status(): void
    {
        // Set room to under_maintenance
        $this->room->update(['status' => 'under_maintenance']);

        // Create an active schedule
        $schedule = RoomMaintenanceSchedule::create([
            'room_id' => $this->room->id,
            'start_datetime' => Carbon::now()->subHour(),
            'end_datetime' => Carbon::now()->addHours(3),
            'reason' => 'Active maintenance',
        ]);

        $this->service->cancelMaintenance($schedule);

        // Verify schedule is deleted
        $this->assertDatabaseMissing('room_maintenance_schedules', ['id' => $schedule->id]);

        // Verify room status reverted
        $this->room->refresh();
        $this->assertEquals('active', $this->room->status);
    }

    public function test_service_has_conflict_method_works_correctly(): void
    {
        RoomMaintenanceSchedule::create([
            'room_id' => $this->room->id,
            'start_datetime' => Carbon::parse('2025-04-01 10:00:00'),
            'end_datetime' => Carbon::parse('2025-04-01 14:00:00'),
        ]);

        // Should detect conflict
        $this->assertTrue($this->service->hasConflict(
            $this->room,
            Carbon::parse('2025-04-01 12:00:00'),
            Carbon::parse('2025-04-01 16:00:00')
        ));

        // Should not detect conflict for non-overlapping times
        $this->assertFalse($this->service->hasConflict(
            $this->room,
            Carbon::parse('2025-04-01 15:00:00'),
            Carbon::parse('2025-04-01 17:00:00')
        ));

        // Different room should not have conflict
        $otherRoom = Room::factory()->create();
        $this->assertFalse($this->service->hasConflict(
            $otherRoom,
            Carbon::parse('2025-04-01 12:00:00'),
            Carbon::parse('2025-04-01 16:00:00')
        ));
    }
}
