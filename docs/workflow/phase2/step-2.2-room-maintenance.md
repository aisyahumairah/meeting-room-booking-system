# Step 2.2: Room Maintenance Scheduling

**Priority:** MEDIUM | **Ref:** §5.5.2 | **Dependencies:** Step 2.1

---

## Objective

Create room maintenance scheduling system with automatic status updates.

---

## Task 2.2.1: Maintenance Schedules Migration

```bash
php artisan make:migration create_room_maintenance_schedules_table
```

**Schema:**
```php
Schema::create('room_maintenance_schedules', function (Blueprint $table) {
    $table->id();
    $table->foreignId('room_id')->constrained()->onDelete('cascade');
    $table->datetime('start_datetime');
    $table->datetime('end_datetime');
    $table->string('reason', 200)->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->index(['room_id', 'start_datetime', 'end_datetime']);
});
```

---

## Task 2.2.2: RoomMaintenanceSchedule Model

```bash
php artisan make:model RoomMaintenanceSchedule
```

**File:** `app/Models/RoomMaintenanceSchedule.php`

- **Relationships:** room(), creator()
- **Scopes:** active(), upcoming(), past(), shouldStartNow(), shouldEndNow()
- **Accessors:** is_active, date_range, status, status_badge
- **Methods:** conflictsWith($date, $startTime, $endTime)

---

## Task 2.2.3: Auto-Status Update Command

```bash
php artisan make:command UpdateRoomMaintenanceStatus
```

**File:** `app/Console/Commands/UpdateRoomMaintenanceStatus.php`

Command logic:
1. Find schedules that should start now → set room status to `under_maintenance`
2. Find schedules that should end now → revert room status to `active`

---

## Task 2.2.4: Register Scheduled Command

**File:** `routes/console.php`

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('rooms:update-maintenance-status')->everyFiveMinutes();
```

---

## Task 2.2.5: RoomMaintenanceService (Optional)

**File:** `app/Services/RoomMaintenanceService.php`

Methods:
- `scheduleMaintenance(Room, Carbon, Carbon, ?reason, ?createdBy)`
- `cancelMaintenance(RoomMaintenanceSchedule)`
- `updateMaintenance(RoomMaintenanceSchedule, Carbon, Carbon, ?reason)`
- `hasConflict(Room, Carbon, Carbon, ?excludeId)`

---

## Task 2.2.6: Unit Tests

```bash
php artisan make:test Unit/RoomMaintenanceTest --unit
```

Test cases:
- Maintenance schedule can be created
- Active scope returns current maintenance
- is_active accessor works correctly
- conflictsWith method detects overlaps
- Service validates end date after start date

---

## Acceptance Criteria

- [x] `room_maintenance_schedules` table exists
- [x] RoomMaintenanceSchedule model has relationships and scopes
- [x] UpdateRoomMaintenanceStatus command runs correctly
- [x] Scheduled command registered (every 5 minutes)
- [x] `php artisan test --filter=RoomMaintenanceTest` passes

---

**Next:** [Step 2.3 - Room CRUD (Admin)](./step-2.3-room-crud.md)
