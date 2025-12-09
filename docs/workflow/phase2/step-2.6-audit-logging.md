# Step 2.6: Audit Logging for Rooms

**Priority:** MEDIUM | **Ref:** §7.3.1 | **Dependencies:** Step 2.3, Phase 1 AuditService

---

## Objective

Implement audit logging for all room management events using the AuditService created in Phase 1.

---

## Task 2.6.1: Review Existing AuditService

**File:** `app/Services/AuditService.php` (from Phase 1)

Ensure the service has the `log()` method:
```php
public static function log(
    string $eventType,
    ?string $targetType = null,
    ?int $targetId = null,
    array $details = []
): AuditLog
```

---

## Task 2.6.2: Define Room Event Types

Add to `app/Services/AuditService.php` or create constants:

```php
// Room Management Events
const EVENT_ROOM_CREATED = 'room.created';
const EVENT_ROOM_UPDATED = 'room.updated';
const EVENT_ROOM_DELETED = 'room.deleted';
const EVENT_ROOM_STATUS_CHANGED = 'room.status_changed';
const EVENT_ROOM_MAINTENANCE_SCHEDULED = 'room.maintenance_scheduled';
const EVENT_ROOM_MAINTENANCE_CANCELLED = 'room.maintenance_cancelled';
const EVENT_ROOM_IMAGE_UPLOADED = 'room.image_uploaded';
const EVENT_ROOM_IMAGE_DELETED = 'room.image_deleted';
```

---

## Task 2.6.3: Update Admin RoomController

**File:** `app/Http/Controllers/Admin/RoomController.php`

Add audit logging to each action:

### store() - Room Created
```php
public function store(StoreRoomRequest $request)
{
    $room = Room::create($validated);
    $room->amenities()->sync($request->amenities ?? []);
    
    // Handle image uploads...
    
    AuditService::log(
        'room.created',
        'room',
        $room->id,
        [
            'room_name' => $room->name,
            'capacity' => $room->capacity,
            'floor_location' => $room->floor_location,
            'amenities' => $room->amenities->pluck('name')->toArray(),
        ]
    );

    return redirect()->route('admin.rooms.index')
        ->with('success', 'Room created successfully.');
}
```

### update() - Room Updated
```php
public function update(UpdateRoomRequest $request, Room $room)
{
    $oldValues = $room->only(['name', 'capacity', 'floor_location', 'description', 'status']);
    $oldAmenities = $room->amenities->pluck('name')->toArray();
    
    $room->update($validated);
    $room->amenities()->sync($request->amenities ?? []);
    
    $newValues = $room->fresh()->only(['name', 'capacity', 'floor_location', 'description', 'status']);
    $newAmenities = $room->amenities->pluck('name')->toArray();
    
    // Only log if something changed
    $changes = [];
    foreach ($oldValues as $key => $oldValue) {
        if ($oldValue !== $newValues[$key]) {
            $changes[$key] = ['old' => $oldValue, 'new' => $newValues[$key]];
        }
    }
    if ($oldAmenities !== $newAmenities) {
        $changes['amenities'] = ['old' => $oldAmenities, 'new' => $newAmenities];
    }
    
    if (!empty($changes)) {
        AuditService::log(
            'room.updated',
            'room',
            $room->id,
            [
                'room_name' => $room->name,
                'changes' => $changes,
            ]
        );
    }

    return redirect()->route('admin.rooms.index')
        ->with('success', 'Room updated successfully.');
}
```

### destroy() - Room Deleted
```php
public function destroy(Room $room)
{
    if ($room->hasBookings()) {
        return back()->with('error', 'Cannot delete room with existing bookings.');
    }

    $roomName = $room->name;
    $roomId = $room->id;
    
    // Delete images from storage
    foreach ($room->images as $image) {
        $image->deleteFile();
    }
    
    $room->forceDelete();
    
    AuditService::log(
        'room.deleted',
        'room',
        $roomId,
        ['room_name' => $roomName]
    );

    return redirect()->route('admin.rooms.index')
        ->with('success', 'Room deleted successfully.');
}
```

### updateStatus() - Status Changed
```php
public function updateStatus(Request $request, Room $room)
{
    $oldStatus = $room->status;
    $newStatus = $request->status;
    
    $room->update(['status' => $newStatus]);
    
    $details = [
        'room_name' => $room->name,
        'old_status' => $oldStatus,
        'new_status' => $newStatus,
    ];
    
    // If scheduling maintenance
    if ($newStatus === 'under_maintenance' && $request->filled('maintenance_start')) {
        $schedule = RoomMaintenanceSchedule::create([
            'room_id' => $room->id,
            'start_datetime' => $request->maintenance_start,
            'end_datetime' => $request->maintenance_end,
            'reason' => $request->maintenance_reason,
            'created_by' => auth()->id(),
        ]);
        
        $details['maintenance'] = [
            'start' => $request->maintenance_start,
            'end' => $request->maintenance_end,
            'reason' => $request->maintenance_reason,
        ];
        
        AuditService::log(
            'room.maintenance_scheduled',
            'room',
            $room->id,
            $details
        );
    } else {
        AuditService::log(
            'room.status_changed',
            'room',
            $room->id,
            $details
        );
    }

    return back()->with('success', 'Room status updated.');
}
```

---

## Task 2.6.4: Log Image Upload/Delete

**File:** `app/Services/RoomImageService.php`

```php
public function uploadImages(Room $room, array $files): array
{
    $uploaded = [];
    
    foreach ($files as $file) {
        $path = $file->store("rooms/{$room->id}", 'public');
        
        $image = RoomImage::create([
            'room_id' => $room->id,
            'path' => $path,
            'is_primary' => $room->images()->count() === 0,
            'sort_order' => $room->images()->count(),
        ]);
        
        $uploaded[] = $image;
        
        AuditService::log(
            'room.image_uploaded',
            'room',
            $room->id,
            [
                'room_name' => $room->name,
                'image_id' => $image->id,
                'filename' => $file->getClientOriginalName(),
            ]
        );
    }
    
    return $uploaded;
}

public function deleteImage(RoomImage $image): bool
{
    $room = $image->room;
    $imageId = $image->id;
    
    $image->deleteFile();
    $image->delete();
    
    AuditService::log(
        'room.image_deleted',
        'room',
        $room->id,
        [
            'room_name' => $room->name,
            'image_id' => $imageId,
        ]
    );
    
    return true;
}
```

---

## Task 2.6.5: Update Audit Log Viewer (Optional)

**File:** `resources/views/admin/audit-logs/index.blade.php`

Add room event types to the filter dropdown:
```blade
<option value="room.created">Room Created</option>
<option value="room.updated">Room Updated</option>
<option value="room.deleted">Room Deleted</option>
<option value="room.status_changed">Room Status Changed</option>
<option value="room.maintenance_scheduled">Maintenance Scheduled</option>
```

---

## Task 2.6.6: Feature Tests

```bash
php artisan make:test Feature/RoomAuditLoggingTest
```

**Test Cases:**
- Room creation is logged
- Room update logs changes only
- Room deletion is logged
- Status change is logged
- Maintenance scheduling is logged
- Image upload is logged
- Image deletion is logged
- Audit log contains correct details

```php
public function test_room_creation_is_logged(): void
{
    $admin = User::factory()->create(['role' => 'administrator']);
    
    $this->actingAs($admin)->post(route('admin.rooms.store'), [
        'name' => 'Test Room',
        'capacity' => 10,
        'floor_location' => '1st Floor',
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'event_type' => 'room.created',
        'target_type' => 'room',
        'actor_id' => $admin->id,
    ]);
}

public function test_room_update_logs_only_changes(): void
{
    $admin = User::factory()->create(['role' => 'administrator']);
    $room = Room::factory()->create(['name' => 'Original Name']);
    
    $this->actingAs($admin)->put(route('admin.rooms.update', $room), [
        'name' => 'Original Name', // Same
        'capacity' => 20, // Changed
        'floor_location' => $room->floor_location,
    ]);

    $log = \App\Models\AuditLog::where('event_type', 'room.updated')->first();
    $details = json_decode($log->details, true);
    
    $this->assertArrayHasKey('capacity', $details['changes']);
    $this->assertArrayNotHasKey('name', $details['changes']);
}
```

---

## Acceptance Criteria

- [x] Room creation logged with name, capacity, floor, amenities
- [x] Room update logged with only changed fields
- [x] Room deletion logged with room name
- [x] Status change logged with old/new status
- [x] Maintenance scheduling logged with dates and reason
- [x] Image upload/delete logged
- [x] Audit logs visible in admin viewer with room events (event types defined, viewer optional)
- [x] `php artisan test --filter=RoomAuditLoggingTest` passes

---

## Phase 2 Complete!

After completing all steps, verify:
1. All tests pass: `php artisan test`
2. All acceptance criteria checked
3. Manual testing of room management flow

**Next Phase:** [Phase 3 - Booking Management](../phase3/README.md)
