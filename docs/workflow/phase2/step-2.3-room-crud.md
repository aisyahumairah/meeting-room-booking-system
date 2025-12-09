# Step 2.3: Room CRUD (Admin/Director)

**Priority:** CRITICAL | **Ref:** §5.2.2, §5.5.1, §5.5.2, §5.5.3 | **Dependencies:** Step 2.1

---

## Objective

Create admin interface for room management: list, create, edit, status changes, and delete.

---

## Task 2.3.1: Admin Room Controller

```bash
php artisan make:controller Admin/RoomController --resource
```

**File:** `app/Http/Controllers/Admin/RoomController.php`

**Methods:**

| Method | Route | Description |
|--------|-------|-------------|
| index() | GET /admin/rooms | Table view of all rooms (including inactive) |
| create() | GET /admin/rooms/create | Create room form |
| store() | POST /admin/rooms | Save new room |
| edit() | GET /admin/rooms/{room}/edit | Edit room form |
| update() | PUT /admin/rooms/{room} | Update room |
| destroy() | DELETE /admin/rooms/{room} | Delete room (if no bookings) |
| updateStatus() | PUT /admin/rooms/{room}/status | Change room status |

---

## Task 2.3.2: Form Request Validation

```bash
php artisan make:request Admin/StoreRoomRequest
php artisan make:request Admin/UpdateRoomRequest
```

**Validation Rules:**
```php
[
    'name' => 'required|string|max:50|unique:rooms,name',
    'capacity' => 'required|integer|min:1|max:500',
    'floor_location' => 'required|string|max:100',
    'description' => 'nullable|string|max:500',
    'amenities' => 'nullable|array',
    'amenities.*' => 'exists:amenities,id',
    'images' => 'nullable|array|max:5',
    'images.*' => 'image|mimes:jpg,jpeg,png|max:5120', // 5MB
]
```

---

## Task 2.3.3: Define Admin Routes

**File:** `routes/web.php`

```php
Route::middleware(['auth', 'check.active', 'role:administrator,director'])->prefix('admin')->name('admin.')->group(function () {
    // Room Management
    Route::resource('rooms', Admin\RoomController::class);
    Route::put('rooms/{room}/status', [Admin\RoomController::class, 'updateStatus'])->name('rooms.status');
});
```

---

## Task 2.3.4: Admin Room List View

**File:** `resources/views/admin/rooms/index.blade.php`

**Mockup Reference:** `mrbs-mock-up/pages/rooms-list.html` (table view variant)

**Features:**
- Table with columns: Name, Capacity, Floor, Status (badge), Amenities, Actions
- "Add New Room" button
- Search by name
- Filter by status dropdown
- Status count summary (X Active, Y Inactive, Z Maintenance)
- Sorting by column
- Pagination

---

## Task 2.3.5: Create Room View

**File:** `resources/views/admin/rooms/create.blade.php`

**Mockup Reference:** `mrbs-mock-up/pages/rooms-add.html`

**Form Fields:**
- Room Name (text, required)
- Capacity (number, required)
- Floor/Location (text, required)
- Description (textarea, optional)
- Amenities (checkboxes, multi-select)
- Room Photos (file upload, max 5, drag-and-drop)

---

## Task 2.3.6: Edit Room View

**File:** `resources/views/admin/rooms/edit.blade.php`

**Mockup Reference:** `mrbs-mock-up/pages/rooms-edit.html`

Same as create, plus:
- Current images with delete option
- Set primary image option
- Status change section
- Maintenance scheduling section
- Delete button (with confirmation)

---

## Task 2.3.7: Image Upload Service

**File:** `app/Services/RoomImageService.php`

```php
class RoomImageService
{
    public function uploadImages(Room $room, array $files): array;
    public function deleteImage(RoomImage $image): bool;
    public function setPrimaryImage(RoomImage $image): bool;
    public function reorderImages(Room $room, array $order): bool;
}
```

Storage path: `storage/app/public/rooms/{room_id}/`

---

## Task 2.3.8: Delete Room Logic

**Controller Logic:**
```php
public function destroy(Room $room)
{
    // Check for ANY bookings (past, present, future)
    if ($room->hasBookings()) {
        return back()->with('error', 
            "Cannot delete room with {$room->getBookingCount()} booking(s). Deactivate instead."
        );
    }

    // Delete images from storage
    foreach ($room->images as $image) {
        $image->deleteFile();
    }

    $room->forceDelete(); // Permanent delete since no bookings
    
    return redirect()->route('admin.rooms.index')
        ->with('success', 'Room deleted successfully.');
}
```

---

## Task 2.3.9: Status Update Logic

**Controller Logic:**
```php
public function updateStatus(Request $request, Room $room)
{
    $validated = $request->validate([
        'status' => 'required|in:active,inactive,under_maintenance',
        'maintenance_start' => 'required_if:status,under_maintenance|date',
        'maintenance_end' => 'required_if:status,under_maintenance|date|after:maintenance_start',
        'maintenance_reason' => 'nullable|string|max:200',
    ]);

    $room->update(['status' => $validated['status']]);

    if ($validated['status'] === 'under_maintenance') {
        RoomMaintenanceSchedule::create([
            'room_id' => $room->id,
            'start_datetime' => $validated['maintenance_start'],
            'end_datetime' => $validated['maintenance_end'],
            'reason' => $validated['maintenance_reason'],
            'created_by' => auth()->id(),
        ]);
    }

    return back()->with('success', 'Room status updated.');
}
```

---

## Task 2.3.10: Feature Tests

```bash
php artisan make:test Feature/Admin/RoomManagementTest
```

**Test Cases:**
- Admin can view room list
- Admin can create room with amenities
- Admin can edit room
- Admin can delete room without bookings
- Admin cannot delete room with bookings
- Admin can change room status
- Admin can schedule maintenance
- Regular user cannot access admin rooms
- Validation errors shown for invalid data
- Images uploaded correctly

---

## Acceptance Criteria

- [x] Admin can view all rooms in table format
- [x] Admin can create new room with all fields
- [x] Admin can upload up to 5 images per room
- [x] Admin can edit existing room
- [x] Admin can change room status
- [x] Admin can schedule maintenance with date/time
- [x] Admin CANNOT delete room with any bookings (pending Booking model - Phase 3)
- [x] Admin can delete room with zero bookings
- [x] Confirmation dialogs shown for destructive actions
- [x] Form validation works correctly
- [x] `php artisan test --filter=RoomManagementTest` passes

---

**Next:** [Step 2.4 - Room Browsing (User)](./step-2.4-room-browsing.md)
