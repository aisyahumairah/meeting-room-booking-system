<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoomRequest;
use App\Http\Requests\Admin\UpdateRoomRequest;
use App\Models\Amenity;
use App\Models\Room;
use App\Models\RoomMaintenanceSchedule;
use App\Services\AuditService;
use App\Services\RoomImageService;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    protected RoomImageService $imageService;

    public function __construct(RoomImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Display a listing of rooms for admin.
     */
    public function index(Request $request)
    {
        $query = Room::withTrashed()->with(['amenities', 'images']);

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'ilike', '%' . $request->search . '%');
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Sorting
        $sortField = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');
        $allowedSorts = ['name', 'capacity', 'floor_location', 'status', 'created_at'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection === 'desc' ? 'desc' : 'asc');
        }

        $rooms = $query->paginate(15)->withQueryString();

        // Get status counts
        $statusCounts = [
            'active' => Room::where('status', 'active')->count(),
            'inactive' => Room::where('status', 'inactive')->count(),
            'under_maintenance' => Room::where('status', 'under_maintenance')->count(),
        ];

        return view('admin.rooms.index', compact('rooms', 'statusCounts'));
    }

    /**
     * Show the form for creating a new room.
     */
    public function create()
    {
        $amenities = Amenity::orderBy('name')->get();
        return view('admin.rooms.create', compact('amenities'));
    }

    /**
     * Store a newly created room in storage.
     */
    public function store(StoreRoomRequest $request)
    {
        $room = Room::create($request->validated());

        // Attach amenities
        if ($request->has('amenities')) {
            $room->amenities()->attach($request->amenities);
        }

        // Upload images
        if ($request->hasFile('images')) {
            $this->imageService->uploadImages($room, $request->file('images'));
        }

        // Audit log: Room created
        AuditService::log(
            AuditService::EVENT_ROOM_CREATED,
            'room',
            $room->id,
            [
                'room_name' => $room->name,
                'capacity' => $room->capacity,
                'floor_location' => $room->floor_location,
                'amenities' => $room->amenities->pluck('name')->toArray(),
            ]
        );

        return redirect()
            ->route('admin.rooms.index')
            ->with('success', "Room '{$room->name}' created successfully.");
    }

    /**
     * Show the form for editing the specified room.
     */
    public function edit(Room $room)
    {
        $room->load(['amenities', 'images', 'maintenanceSchedules' => function ($query) {
            $query->upcoming()->orderBy('start_datetime');
        }]);

        $amenities = Amenity::orderBy('name')->get();

        return view('admin.rooms.edit', compact('room', 'amenities'));
    }

    /**
     * Update the specified room in storage.
     */
    public function update(UpdateRoomRequest $request, Room $room)
    {
        // Capture old values for audit
        $oldValues = $room->only(['name', 'capacity', 'floor_location', 'description', 'status']);
        $oldAmenities = $room->amenities->pluck('name')->toArray();

        $room->update($request->validated());

        // Sync amenities
        $room->amenities()->sync($request->amenities ?? []);

        // Get new values after update
        $room->refresh();
        $newValues = $room->only(['name', 'capacity', 'floor_location', 'description', 'status']);
        $newAmenities = $room->amenities->pluck('name')->toArray();

        // Calculate changes
        $changes = [];
        foreach ($oldValues as $key => $oldValue) {
            if ($oldValue !== $newValues[$key]) {
                $changes[$key] = ['old' => $oldValue, 'new' => $newValues[$key]];
            }
        }
        if ($oldAmenities !== $newAmenities) {
            $changes['amenities'] = ['old' => $oldAmenities, 'new' => $newAmenities];
        }

        // Only log if something changed
        if (!empty($changes)) {
            AuditService::log(
                AuditService::EVENT_ROOM_UPDATED,
                'room',
                $room->id,
                [
                    'room_name' => $room->name,
                    'changes' => $changes,
                ]
            );
        }

        // Upload new images
        if ($request->hasFile('images')) {
            $this->imageService->uploadImages($room, $request->file('images'));
        }

        return redirect()
            ->route('admin.rooms.edit', $room)
            ->with('success', "Room '{$room->name}' updated successfully.");
    }

    /**
     * Remove the specified room from storage.
     */
    public function destroy(Room $room)
    {
        // Check for ANY bookings (past, present, future)
        if ($room->hasBookings()) {
            return back()->with(
                'error',
                "Cannot delete room with {$room->getBookingCount()} booking(s). Deactivate instead."
            );
        }

        $roomName = $room->name;
        $roomId = $room->id;

        // Delete images from storage
        foreach ($room->images as $image) {
            $this->imageService->deleteImage($image);
        }

        $room->forceDelete(); // Permanent delete since no bookings

        // Audit log: Room deleted
        AuditService::log(
            AuditService::EVENT_ROOM_DELETED,
            'room',
            $roomId,
            ['room_name' => $roomName]
        );

        return redirect()
            ->route('admin.rooms.index')
            ->with('success', "Room '{$roomName}' deleted successfully.");
    }

    /**
     * Update the room status.
     */
    public function updateStatus(Request $request, Room $room)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,inactive,under_maintenance',
            'maintenance_start' => 'required_if:status,under_maintenance|nullable|date',
            'maintenance_end' => 'required_if:status,under_maintenance|nullable|date|after:maintenance_start',
            'maintenance_reason' => 'nullable|string|max:200',
        ]);

        $oldStatus = $room->status;
        $newStatus = $validated['status'];

        $room->update(['status' => $newStatus]);

        $details = [
            'room_name' => $room->name,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ];

        // If scheduling maintenance
        if ($newStatus === 'under_maintenance' && $validated['maintenance_start'] && $validated['maintenance_end']) {
            RoomMaintenanceSchedule::create([
                'room_id' => $room->id,
                'start_datetime' => $validated['maintenance_start'],
                'end_datetime' => $validated['maintenance_end'],
                'reason' => $validated['maintenance_reason'],
                'created_by' => auth()->id(),
            ]);

            $details['maintenance'] = [
                'start' => $validated['maintenance_start'],
                'end' => $validated['maintenance_end'],
                'reason' => $validated['maintenance_reason'],
            ];

            AuditService::log(
                AuditService::EVENT_ROOM_MAINTENANCE_SCHEDULED,
                'room',
                $room->id,
                $details
            );
        } else {
            AuditService::log(
                AuditService::EVENT_ROOM_STATUS_CHANGED,
                'room',
                $room->id,
                $details
            );
        }

        return back()->with('success', 'Room status updated successfully.');
    }

    /**
     * Set image as primary.
     */
    public function setPrimaryImage(Room $room, int $imageId)
    {
        $image = $room->images()->findOrFail($imageId);
        $this->imageService->setPrimaryImage($image);

        return back()->with('success', 'Primary image updated.');
    }

    /**
     * Delete a room image.
     */
    public function deleteImage(Room $room, int $imageId)
    {
        $image = $room->images()->findOrFail($imageId);
        $this->imageService->deleteImage($image);

        return back()->with('success', 'Image deleted.');
    }
}
