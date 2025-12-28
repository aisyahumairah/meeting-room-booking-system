<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAmenityRequest;
use App\Http\Requests\UpdateAmenityRequest;
use App\Models\Amenity;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AmenityController extends Controller
{
    /**
     * Display a listing of amenities
     */
    public function index(\Illuminate\Http\Request $request): View
    {
        $query = Amenity::withCount('rooms');

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'ilike', '%' . $request->search . '%');
        }

        // Filter by status
        if ($request->filled('status')) {
            $status = $request->status === 'active' ? true : false;
            $query->where('is_active', $status);
        }

        $amenities = $query->orderBy('is_active', 'desc')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        // Get status counts
        $statusCounts = [
            'active' => Amenity::active()->count(),
            'inactive' => Amenity::inactive()->count(),
        ];

        return view('admin.amenities.index', compact('amenities', 'statusCounts'));
    }

    /**
     * Show the form for creating a new amenity
     */
    public function create(): View
    {
        // Common Boxicons for amenities
        $availableIcons = [
            'bx-projector' => 'Projector',
            'bx-chalkboard' => 'Whiteboard',
            'bx-video' => 'Video Conferencing',
            'bx-phone' => 'Phone',
            'bx-desktop' => 'Computer/Monitor',
            'bx-note' => 'Flip Chart',
            'bx-wind' => 'Air Conditioning',
            'bx-sun' => 'Windows/Natural Light',
            'bx-wifi' => 'WiFi',
            'bx-speaker' => 'Sound System',
            'bx-tv' => 'TV/Display',
            'bx-microphone' => 'Microphone',
            'bx-coffee' => 'Refreshments',
            'bx-printer' => 'Printer',
        ];

        return view('admin.amenities.create', compact('availableIcons'));
    }

    /**
     * Store a newly created amenity
     */
    public function store(StoreAmenityRequest $request): RedirectResponse
    {
        $amenity = Amenity::create($request->validated());

        AuditService::log(
            'amenity.created',
            'amenity',
            $amenity->id,
            ['amenity_name' => $amenity->name]
        );

        return redirect()
            ->route('admin.amenities.index')
            ->with('success', 'Amenity created successfully.');
    }

    /**
     * Display the specified amenity.
     */
    public function show(Amenity $amenity): View
    {
        $amenity->load(['rooms' => function ($query) {
            $query->orderBy('name');
        }]);

        return view('admin.amenities.show', compact('amenity'));
    }

    /**
     * Show the form for editing an amenity
     */
    public function edit(Amenity $amenity): View
    {
        $availableIcons = [
            'bx-projector' => 'Projector',
            'bx-chalkboard' => 'Whiteboard',
            'bx-video' => 'Video Conferencing',
            'bx-phone' => 'Phone',
            'bx-desktop' => 'Computer/Monitor',
            'bx-note' => 'Flip Chart',
            'bx-wind' => 'Air Conditioning',
            'bx-sun' => 'Windows/Natural Light',
            'bx-wifi' => 'WiFi',
            'bx-speaker' => 'Sound System',
            'bx-tv' => 'TV/Display',
            'bx-microphone' => 'Microphone',
            'bx-coffee' => 'Refreshments',
            'bx-printer' => 'Printer',
        ];

        $amenity->loadCount('rooms');

        return view('admin.amenities.edit', compact('amenity', 'availableIcons'));
    }

    /**
     * Update the specified amenity
     */
    public function update(UpdateAmenityRequest $request, Amenity $amenity): RedirectResponse
    {
        $originalName = $amenity->name;
        $amenity->update($request->validated());

        AuditService::log(
            'amenity.updated',
            'amenity',
            $amenity->id,
            [
                'old_name' => $originalName,
                'new_name' => $amenity->name
            ]
        );

        return redirect()
            ->route('admin.amenities.index')
            ->with('success', 'Amenity updated successfully.');
    }

    /**
     * Toggle amenity status (active/inactive)
     */
    public function updateStatus(Amenity $amenity): RedirectResponse
    {
        $newStatus = !$amenity->is_active;
        $amenity->update(['is_active' => $newStatus]);

        $statusText = $newStatus ? 'activated' : 'deactivated';

        AuditService::log(
            'amenity.status_changed',
            'amenity',
            $amenity->id,
            [
                'amenity_name' => $amenity->name,
                'new_status' => $statusText
            ]
        );

        return redirect()
            ->route('admin.amenities.index')
            ->with('success', "Amenity {$statusText} successfully.");
    }

    /**
     * Remove the specified amenity
     */
    public function destroy(Amenity $amenity): RedirectResponse
    {
        // Prevent deletion if amenity is assigned to any rooms
        $amenity->loadCount('rooms');
        
        if ($amenity->rooms_count > 0) {
            return back()->with('error', "Cannot delete amenity '{$amenity->name}' as it is assigned to {$amenity->rooms_count} room(s). Please remove it from all rooms first.");
        }

        $amenityName = $amenity->name;
        $amenityId = $amenity->id;
        $amenity->delete();

        AuditService::log(
            'amenity.deleted',
            'amenity',
            $amenityId,
            ['amenity_name' => $amenityName]
        );

        return redirect()
            ->route('admin.amenities.index')
            ->with('success', 'Amenity deleted successfully.');
    }
}
