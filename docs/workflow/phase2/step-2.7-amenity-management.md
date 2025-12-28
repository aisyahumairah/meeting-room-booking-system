# Step 2.7: Amenity Management

**Phase:** 2 - Meeting Rooms Management  
**Step:** 2.7  
**Priority:** MEDIUM  
**Est. Time:** 3-4 hours  
**Status:** PENDING

---

## Overview

Enable administrators to manage amenities (equipment and features) independently of rooms. This includes creating, editing, deleting amenities, and managing their active/inactive status. Active amenities appear in room management forms, while inactive amenities are hidden but preserved in the database. Proper validation prevents deletion of amenities currently assigned to rooms.

---

## Dependencies

**Required:**
- ✅ Step 2.1 (Database Schema - amenities table exists)
- ✅ Phase 1 (Authentication, Authorization, Audit trail)

**Blocks:**
- None (this is an enhancement feature)

---

## Objectives

1. Add status column (active/inactive) to amenities table via migration
2. Create `Admin\AmenityController` with full CRUD operations and status toggle
3. Create validation request classes for amenity data
4. Build admin views for amenity list, create, and edit with status display/toggle
5. Implement deletion protection (prevent deleting amenities in use)
6. Add query scope to filter active amenities for room management
7. Add audit logging for amenity events including status changes
8. Add amenity management to admin sidebar navigation
9. Write feature tests for amenity CRUD and status operations

---

## Task Checklist

### 1. Add Status Column Migration

**File:** Create migration `database/migrations/2025_12_28_add_status_to_amenities_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('amenities', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('description');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('amenities', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropColumn('is_active');
        });
    }
};
```

**Run the migration:**

```bash
php artisan migrate
```

---

### 2. Update Amenity Model

**File:** `app/Models/Amenity.php`

Add the `is_active` field to the fillable array and create a query scope:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class Amenity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
        'description',
        'is_active',  // Add this
    ];

    protected $casts = [
        'is_active' => 'boolean',  // Add this
    ];

    /**
     * Rooms that have this amenity
     */
    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'amenity_room');
    }

    /**
     * Scope to only get active amenities
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get inactive amenities
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }
}
```

---

### 3. Create Request Validation Classes

**File:** `app/Http/Requests/StoreAmenityRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAmenityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(['admin', 'director']);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:amenities,name'],
            'icon' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Amenity name is required.',
            'name.unique' => 'An amenity with this name already exists.',
            'icon.required' => 'Please select an icon for this amenity.',
        ];
    }
}
```

**File:** `app/Http/Requests/UpdateAmenityRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAmenityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(['admin', 'director']);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('amenities', 'name')->ignore($this->amenity)
            ],
            'icon' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Amenity name is required.',
            'name.unique' => 'An amenity with this name already exists.',
            'icon.required' => 'Please select an icon for this amenity.',
        ];
    }
}
```

---

### 4. Create Amenity Controller

**File:** `app/Http/Controllers/Admin/AmenityController.php`

```php
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
    public function __construct(
        protected AuditService $auditService
    ) {
        $this->middleware(['auth', 'role:admin,director']);
    }

    /**
     * Display a listing of amenities
     */
    public function index(): View
    {
        $amenities = Amenity::withCount('rooms')
            ->orderBy('is_active', 'desc')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.amenities.index', compact('amenities'));
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

        $this->auditService->log(
            action: 'amenity.created',
            model: $amenity,
            description: "Created amenity: {$amenity->name}"
        );

        return redirect()
            ->route('admin.amenities.index')
            ->with('success', 'Amenity created successfully.');
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

        $this->auditService->log(
            action: 'amenity.updated',
            model: $amenity,
            description: "Updated amenity: {$originalName} → {$amenity->name}"
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

        $this->auditService->log(
            action: 'amenity.status_changed',
            model: $amenity,
            description: "Changed amenity '{$amenity->name}' status to: {$statusText}"
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
        $amenity->delete();

        $this->auditService->log(
            action: 'amenity.deleted',
            model: $amenity,
            description: "Deleted amenity: {$amenityName}"
        );

        return redirect()
            ->route('admin.amenities.index')
            ->with('success', 'Amenity deleted successfully.');
    }
}
```

---

### 5. Create Blade Views

**File:** `resources/views/admin/amenities/index.blade.php`

```blade
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Amenity Management') }}
            </h2>
            <a href="{{ route('admin.amenities.create') }}" class="btn btn-primary">
                <i class='bx bx-plus'></i> Add New Amenity
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="alert alert-success mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th>Icon</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Rooms Using</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($amenities as $amenity)
                                <tr class="{{ !$amenity->is_active ? 'opacity-60 bg-gray-50' : '' }}">
                                    <td>
                                        <i class='bx {{ $amenity->icon }} text-2xl'></i>
                                    </td>
                                    <td class="font-medium">
                                        {{ $amenity->name }}
                                        @if(!$amenity->is_active)
                                            <span class="text-xs text-gray-500">(Inactive)</span>
                                        @endif
                                    </td>
                                    <td class="text-sm text-gray-600">
                                        {{ $amenity->description ?? '—' }}
                                    </td>
                                    <td>
                                        <form action="{{ route('admin.amenities.update-status', $amenity) }}" 
                                              method="POST" 
                                              class="inline">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" 
                                                    class="badge {{ $amenity->is_active ? 'badge-success' : 'badge-secondary' }} cursor-pointer hover:opacity-80"
                                                    title="Click to toggle status">
                                                {{ $amenity->is_active ? 'Active' : 'Inactive' }}
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">
                                            {{ $amenity->rooms_count }} room(s)
                                        </span>
                                    </td>
                                    <td>
                                        <div class="flex gap-2">
                                            <a href="{{ route('admin.amenities.edit', $amenity) }}" 
                                               class="btn btn-sm btn-warning">
                                                <i class='bx bx-edit'></i> Edit
                                            </a>
                                            
                                            <form action="{{ route('admin.amenities.destroy', $amenity) }}" 
                                                  method="POST" 
                                                  onsubmit="return confirm('Are you sure you want to delete this amenity?');"
                                                  class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="btn btn-sm btn-danger"
                                                        @if($amenity->rooms_count > 0) disabled title="Cannot delete: In use by rooms" @endif>
                                                    <i class='bx bx-trash'></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-8 text-gray-500">
                                        No amenities found. <a href="{{ route('admin.amenities.create') }}" class="text-blue-600">Create one</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $amenities->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

**File:** `resources/views/admin/amenities/create.blade.php`

```blade
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Create New Amenity') }}
            </h2>
            <a href="{{ route('admin.amenities.index') }}" class="btn btn-secondary">
                <i class='bx bx-arrow-back'></i> Back to List
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('admin.amenities.store') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label for="name" class="form-label">Amenity Name *</label>
                            <input type="text" 
                                   id="name" 
                                   name="name" 
                                   class="form-input @error('name') is-invalid @enderror" 
                                   value="{{ old('name') }}" 
                                   required>
                            @error('name')
                                <span class="text-red-600 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="icon" class="form-label">Icon *</label>
                            <select id="icon" 
                                    name="icon" 
                                    class="form-input @error('icon') is-invalid @enderror" 
                                    required>
                                <option value="">Select an icon...</option>
                                @foreach($availableIcons as $iconClass => $iconName)
                                    <option value="{{ $iconClass }}" 
                                            @selected(old('icon') == $iconClass)>
                                        {{ $iconName }}
                                    </option>
                                @endforeach
                            </select>
                            @error('icon')
                                <span class="text-red-600 text-sm">{{ $message }}</span>
                            @enderror
                            
                            <div id="icon-preview" class="mt-2 text-4xl text-gray-400"></div>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" 
                                      name="description" 
                                      rows="3" 
                                      class="form-input @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                            @error('description')
                                <span class="text-red-600 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class='bx bx-save'></i> Create Amenity
                            </button>
                            <a href="{{ route('admin.amenities.index') }}" class="btn btn-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Live icon preview
        document.getElementById('icon').addEventListener('change', function() {
            const preview = document.getElementById('icon-preview');
            if (this.value) {
                preview.innerHTML = `<i class='bx ${this.value}'></i>`;
            } else {
                preview.innerHTML = '';
            }
        });

        // Show preview if old value exists
        if (document.getElementById('icon').value) {
            document.getElementById('icon').dispatchEvent(new Event('change'));
        }
    </script>
    @endpush
</x-app-layout>
```

**File:** `resources/views/admin/amenities/edit.blade.php`

```blade
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Amenity') }}: {{ $amenity->name }}
            </h2>
            <a href="{{ route('admin.amenities.index') }}" class="btn btn-secondary">
                <i class='bx bx-arrow-back'></i> Back to List
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if($amenity->rooms_count > 0)
                        <div class="alert alert-info mb-4">
                            <i class='bx bx-info-circle'></i>
                            This amenity is currently assigned to <strong>{{ $amenity->rooms_count }}</strong> room(s).
                        </div>
                    @endif

                    <form action="{{ route('admin.amenities.update', $amenity) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label for="name" class="form-label">Amenity Name *</label>
                            <input type="text" 
                                   id="name" 
                                   name="name" 
                                   class="form-input @error('name') is-invalid @enderror" 
                                   value="{{ old('name', $amenity->name) }}" 
                                   required>
                            @error('name')
                                <span class="text-red-600 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="icon" class="form-label">Icon *</label>
                            <select id="icon" 
                                    name="icon" 
                                    class="form-input @error('icon') is-invalid @enderror" 
                                    required>
                                <option value="">Select an icon...</option>
                                @foreach($availableIcons as $iconClass => $iconName)
                                    <option value="{{ $iconClass }}" 
                                            @selected(old('icon', $amenity->icon) == $iconClass)>
                                        {{ $iconName }}
                                    </option>
                                @endforeach
                            </select>
                            @error('icon')
                                <span class="text-red-600 text-sm">{{ $message }}</span>
                            @enderror
                            
                            <div id="icon-preview" class="mt-2 text-4xl text-gray-400"></div>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" 
                                      name="description" 
                                      rows="3" 
                                      class="form-input @error('description') is-invalid @enderror">{{ old('description', $amenity->description) }}</textarea>
                            @error('description')
                                <span class="text-red-600 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class='bx bx-save'></i> Update Amenity
                            </button>
                            <a href="{{ route('admin.amenities.index') }}" class="btn btn-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Live icon preview
        document.getElementById('icon').addEventListener('change', function() {
            const preview = document.getElementById('icon-preview');
            if (this.value) {
                preview.innerHTML = `<i class='bx ${this.value}'></i>`;
            } else {
                preview.innerHTML = '';
            }
        });

        // Show preview on load
        document.getElementById('icon').dispatchEvent(new Event('change'));
    </script>
    @endpush
</x-app-layout>
```

---

### 6. Add Routes

**File:** `routes/web.php`

Add to the admin routes group:

```php
// Admin Amenity Management
Route::prefix('admin/amenities')->name('admin.amenities.')->middleware(['auth', 'role:admin,director'])->group(function () {
    Route::get('/', [Admin\AmenityController::class, 'index'])->name('index');
    Route::get('/create', [Admin\AmenityController::class, 'create'])->name('create');
    Route::post('/', [Admin\AmenityController::class, 'store'])->name('store');
    Route::get('/{amenity}/edit', [Admin\AmenityController::class, 'edit'])->name('edit');
    Route::put('/{amenity}', [Admin\AmenityController::class, 'update'])->name('update');
    Route::put('/{amenity}/status', [Admin\AmenityController::class, 'updateStatus'])->name('update-status');
    Route::delete('/{amenity}', [Admin\AmenityController::class, 'destroy'])->name('destroy');
});
```

---

### 7. Update Room Management to Filter Active Amenities

**Note:** When implementing room CRUD forms (Step 2.3), ensure amenity selection only shows active amenities:

**Example in Room Controller:**

```php
public function create()
{
    // Only show active amenities in the dropdown/checkbox list
    $amenities = Amenity::active()->orderBy('name')->get();
    
    return view('admin.rooms.create', compact('amenities'));
}

public function edit(Room $room)
{
    // Only show active amenities in the  dropdown/checkbox list
    $amenities = Amenity::active()->orderBy('name')->get();
    
    return view('admin.rooms.edit', compact('room', 'amenities'));
}
```

This ensures that when admins deactivate an amenity, it won't appear in new room assignments, but existing room-amenity relationships are preserved.

---

### 8. Update Sidebar Navigation

**File:** `resources/views/layouts/navigation.blade.php`

Add amenity management link in the admin section:

```blade
@role('admin|director')
    <li class="nav-item">
        <a href="{{ route('admin.rooms.index') }}" class="nav-link {{ request()->routeIs('admin.rooms.*') ? 'active' : '' }}">
            <i class='bx bx-door-open'></i>
            <span>Manage Rooms</span>
        </a>
    </li>
    <li class="nav-item">
        <a href="{{ route('admin.amenities.index') }}" class="nav-link {{ request()->routeIs('admin.amenities.*') ? 'active' : '' }}">
            <i class='bx bx-wrench'></i>
            <span>Manage Amenities</span>
        </a>
    </li>
@endrole
```

---

### 9. Create Feature Tests

**File:** `tests/Feature/Admin/AmenityControllerTest.php`

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\Amenity;
use App\Models\AuditLog;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AmenityControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->admin()->create();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function admin_can_view_amenity_list()
    {
        Amenity::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get(route('admin.amenities.index'));

        $response->assertOk();
        $response->assertViewIs('admin.amenities.index');
        $response->assertViewHas('amenities');
    }

    /** @test */
    public function regular_user_cannot_access_amenity_management()
    {
        $response = $this->actingAs($this->user)->get(route('admin.amenities.index'));

        $response->assertForbidden();
    }

    /** @test */
    public function admin_can_create_amenity()
    {
        $data = [
            'name' => 'Smart Board',
            'icon' => 'bx-chalkboard',
            'description' => 'Interactive whiteboard',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.amenities.store'), $data);

        $response->assertRedirect(route('admin.amenities.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('amenities', [
            'name' => 'Smart Board',
            'icon' => 'bx-chalkboard',
        ]);

        // Check audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'amenity.created',
            'auditable_type' => Amenity::class,
        ]);
    }

    /** @test */
    public function amenity_name_must_be_unique()
    {
        Amenity::factory()->create(['name' => 'Projector']);

        $data = [
            'name' => 'Projector',
            'icon' => 'bx-projector',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.amenities.store'), $data);

        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function admin_can_update_amenity()
    {
        $amenity = Amenity::factory()->create(['name' => 'Old Name']);

        $data = [
            'name' => 'Updated Name',
            'icon' => 'bx-video',
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.amenities.update', $amenity), $data);

        $response->assertRedirect(route('admin.amenities.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('amenities', [
            'id' => $amenity->id,
            'name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function admin_can_delete_unused_amenity()
    {
        $amenity = Amenity::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('admin.amenities.destroy', $amenity));

        $response->assertRedirect(route('admin.amenities.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('amenities', ['id' => $amenity->id]);

        // Check audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'amenity.deleted',
        ]);
    }

    /** @test */
    public function cannot_delete_amenity_assigned_to_rooms()
    {
        $amenity = Amenity::factory()->create();
        $room = Room::factory()->create();
        $room->amenities()->attach($amenity);

        $response = $this->actingAs($this->admin)->delete(route('admin.amenities.destroy', $amenity));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('amenities', ['id' => $amenity->id]);
    }

    /** @test */
    public function amenity_list_shows_room_count()
    {
        $amenity = Amenity::factory()->create();
        $room1 = Room::factory()->create();
        $room2 = Room::factory()->create();
        
        $room1->amenities()->attach($amenity);
        $room2->amenities()->attach($amenity);

        $response = $this->actingAs($this->admin)->get(route('admin.amenities.index'));

        $response->assertOk();
        $response->assertSee('2 room(s)');
    }

    /** @test */
    public function admin_can_toggle_amenity_status()
    {
        $amenity = Amenity::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin)->put(route('admin.amenities.update-status', $amenity));

        $response->assertRedirect(route('admin.amenities.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('amenities', [
            'id' => $amenity->id,
            'is_active' => false,
        ]);

        // Check audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'amenity.status_changed',
            'auditable_type' => Amenity::class,
        ]);
    }

    /** @test */
    public function active_scope_filters_active_amenities()
    {
        Amenity::factory()->create(['name' => 'Active Amenity', 'is_active' => true]);
        Amenity::factory()->create(['name' => 'Inactive Amenity', 'is_active' => false]);

        $activeAmenities = Amenity::active()->get();

        $this->assertCount(1, $activeAmenities);
        $this->assertEquals('Active Amenity', $activeAmenities->first()->name);
    }

    /** @test */
    public function new_amenities_default_to_active()
    {
        $data = [
            'name' => 'New Amenity',
            'icon' => 'bx-wifi',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.amenities.store'), $data);

        $response->assertRedirect(route('admin.amenities.index'));

        $amenity = Amenity::where('name', 'New Amenity')->first();
        $this->assertTrue($amenity->is_active);
    }
}
```

---

## Acceptance Criteria

- ✅ Admin and Director can access amenity management pages
- ✅ Regular users are blocked from amenity management (403 Forbidden)
- ✅ Database migration adds `is_active` column with default value `true`
- ✅ Amenity model has `active()` and `inactive()` query scopes
- ✅ Amenity list displays all amenities with status, icons, and room counts
- ✅ Active amenities sorted first, then by name
- ✅ Inactive amenities visually distinguished (grayed out)
- ✅ Create form has icon picker with live preview
- ✅ New amenities default to active status
- ✅ Amenity names must be unique
- ✅ Admins can toggle amenity status with one click (active ↔ inactive)
- ✅ Status changes are logged in audit trail
- ✅ Only active amenities appear in room management forms
- ✅ Existing room-amenity relationships preserved when amenity deactivated
- ✅ Cannot delete amenities assigned to rooms (validation error shown)
- ✅ Can delete amenities not assigned to any rooms
- ✅ All CRUD operations are logged in audit trail
- ✅ Sidebar navigation includes "Manage Amenities" link
- ✅ All feature tests pass (including status toggle tests)

---

## Testing Commands

```bash
# Run amenity feature tests
php artisan test --filter=AmenityControllerTest

# Run all Phase 2 tests
php artisan test tests/Feature/Admin/

# Check code style
./vendor/bin/pint app/Http/Controllers/Admin/AmenityController.php
```

---

## Notes

- **Icon Selection:** Uses Boxicons (already in project). The icon picker shows common amenity icons with live preview.
- **Status Management:** Amenities can be active or inactive. Only active amenities appear in room management forms. This allows soft-disabling of amenities without losing historical data or breaking existing room assignments.
- **Deletion Protection:** Prevents deleting amenities that are assigned to rooms via the `amenity_room` pivot table.
- **Audit Trail:** All create, update, delete, and status change operations are logged.
- **Room Count:** The amenity list shows how many rooms use each amenity using `withCount('rooms')`.
- **Sorting:** Active amenities are listed first, then sorted alphabetically. Inactive amenities appear grayed out at the bottom.
- **Query Scopes:** Use `Amenity::active()` in room management to only show active amenities to users.
- **Responsive Design:** Uses existing theme components for consistency.

---

## Related Files

- `app/Models/Amenity.php` (created in Step 2.1, updated here for status)
- `app/Models/Room.php` (created in Step 2.1)
- Migration `create_amenities_table.php` (created in Step 2.1)
- Migration `add_status_to_amenities_table.php` (created in this step)
- Seeder `AmenitySeeder.php` (created in Step 2.1)

---

**Estimated Completion Time:** 3-4 hours
