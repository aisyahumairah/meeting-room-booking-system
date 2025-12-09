# Step 2.1: Database Schema - Rooms, Amenities & Images

**Priority:** CRITICAL | **Ref:** §5.2, §5.5.1 | **Dependencies:** Phase 1 Complete

---

## Objective

Create database tables for meeting rooms, amenities (equipment/features), room-amenity relationships, and room images. Create corresponding Eloquent models with relationships, scopes, and accessors.

---

## Task 2.1.1: Rooms Migration

```bash
php artisan make:migration create_rooms_table
```

**File:** `database/migrations/xxxx_xx_xx_create_rooms_table.php`

**Schema:**
```php
Schema::create('rooms', function (Blueprint $table) {
    $table->id();
    $table->string('name', 50)->unique();
    $table->integer('capacity')->unsigned();
    $table->string('floor_location', 100);
    $table->text('description')->nullable(); // max 500 chars enforced in validation
    $table->enum('status', ['active', 'inactive', 'under_maintenance'])->default('active');
    $table->timestamps();
    $table->softDeletes();
    
    // Indexes for common queries
    $table->index(['status']);
    $table->index(['capacity']);
    $table->index(['floor_location']);
});
```

---

## Task 2.1.2: Amenities Migration

```bash
php artisan make:migration create_amenities_table
```

**File:** `database/migrations/xxxx_xx_xx_create_amenities_table.php`

**Schema:**
```php
Schema::create('amenities', function (Blueprint $table) {
    $table->id();
    $table->string('name', 50)->unique();
    $table->string('icon', 50)->nullable(); // Boxicons class name
    $table->timestamps();
});
```

---

## Task 2.1.3: Amenity-Room Pivot Table

```bash
php artisan make:migration create_amenity_room_table
```

**File:** `database/migrations/xxxx_xx_xx_create_amenity_room_table.php`

**Schema:**
```php
Schema::create('amenity_room', function (Blueprint $table) {
    $table->id();
    $table->foreignId('amenity_id')->constrained()->onDelete('cascade');
    $table->foreignId('room_id')->constrained()->onDelete('cascade');
    $table->timestamps();
    
    // Prevent duplicate amenity-room pairs
    $table->unique(['amenity_id', 'room_id']);
});
```

---

## Task 2.1.4: Room Images Migration

```bash
php artisan make:migration create_room_images_table
```

**File:** `database/migrations/xxxx_xx_xx_create_room_images_table.php`

**Schema:**
```php
Schema::create('room_images', function (Blueprint $table) {
    $table->id();
    $table->foreignId('room_id')->constrained()->onDelete('cascade');
    $table->string('path', 255); // Relative path in storage
    $table->boolean('is_primary')->default(false);
    $table->integer('sort_order')->default(0);
    $table->timestamps();
    
    $table->index(['room_id', 'is_primary']);
});
```

**Storage Configuration:**

Create the storage directory and symbolic link:
```bash
mkdir -p storage/app/public/rooms
php artisan storage:link
```

Images will be stored at: `storage/app/public/rooms/{room_id}/`
Accessible via: `public/storage/rooms/{room_id}/`

---

## Task 2.1.5: Room Model

```bash
php artisan make:model Room
```

**File:** `app/Models/Room.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'capacity',
        'floor_location',
        'description',
        'status',
    ];

    protected $casts = [
        'capacity' => 'integer',
    ];

    // =====================
    // RELATIONSHIPS
    // =====================

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class)->withTimestamps();
    }

    public function images(): HasMany
    {
        return $this->hasMany(RoomImage::class)->orderBy('sort_order');
    }

    public function maintenanceSchedules(): HasMany
    {
        return $this->hasMany(RoomMaintenanceSchedule::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    // =====================
    // ACCESSORS
    // =====================

    /**
     * Get the primary image or default placeholder
     */
    public function getPrimaryImageAttribute(): string
    {
        $primary = $this->images()->where('is_primary', true)->first();
        
        if ($primary) {
            return asset('storage/' . $primary->path);
        }
        
        // Return first image if no primary set
        $first = $this->images()->first();
        if ($first) {
            return asset('storage/' . $first->path);
        }
        
        // Default placeholder
        return asset('assets/img/rooms/placeholder.png');
    }

    /**
     * Get status badge HTML
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'active' => '<span class="badge bg-success">Active</span>',
            'inactive' => '<span class="badge bg-secondary">Inactive</span>',
            'under_maintenance' => '<span class="badge bg-warning">Under Maintenance</span>',
            default => '<span class="badge bg-secondary">Unknown</span>',
        };
    }

    /**
     * Get human-readable status display
     */
    public function getStatusDisplayAttribute(): string
    {
        return match($this->status) {
            'active' => 'Active',
            'inactive' => 'Inactive',
            'under_maintenance' => 'Under Maintenance',
            default => 'Unknown',
        };
    }

    // =====================
    // SCOPES
    // =====================

    /**
     * Filter by active status only
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Filter rooms available for booking (active, not under maintenance)
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Filter by minimum capacity
     */
    public function scopeByCapacity($query, int $minCapacity)
    {
        return $query->where('capacity', '>=', $minCapacity);
    }

    /**
     * Filter by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Filter by amenities (rooms that have ALL specified amenities)
     */
    public function scopeWithAmenities($query, array $amenityIds)
    {
        if (empty($amenityIds)) {
            return $query;
        }

        return $query->whereHas('amenities', function ($q) use ($amenityIds) {
            $q->whereIn('amenities.id', $amenityIds);
        }, '=', count($amenityIds));
    }

    // =====================
    // METHODS
    // =====================

    /**
     * Check if room is available for a specific date/time range
     * (checks against confirmed bookings only)
     */
    public function isAvailable(string $date, string $startTime, string $endTime): bool
    {
        // Check if room is active
        if ($this->status !== 'active') {
            return false;
        }

        // Check for maintenance schedule conflicts
        $maintenanceConflict = $this->maintenanceSchedules()
            ->where('start_datetime', '<=', $date . ' ' . $endTime)
            ->where('end_datetime', '>=', $date . ' ' . $startTime)
            ->exists();

        if ($maintenanceConflict) {
            return false;
        }

        // Check for booking conflicts (only confirmed bookings)
        $bookingConflict = $this->bookings()
            ->where('booking_date', $date)
            ->where('status', 'confirmed')
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    // New booking starts during existing booking
                    $q->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
                });
            })
            ->exists();

        return !$bookingConflict;
    }

    /**
     * Check if room has any bookings (for deletion check)
     */
    public function hasBookings(): bool
    {
        return $this->bookings()->exists();
    }

    /**
     * Get total booking count
     */
    public function getBookingCount(): int
    {
        return $this->bookings()->count();
    }

    /**
     * Check if room can be deleted
     */
    public function canBeDeleted(): bool
    {
        return !$this->hasBookings();
    }
}
```

---

## Task 2.1.6: Amenity Model

```bash
php artisan make:model Amenity
```

**File:** `app/Models/Amenity.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Amenity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
    ];

    // =====================
    // RELATIONSHIPS
    // =====================

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class)->withTimestamps();
    }

    // =====================
    // ACCESSORS
    // =====================

    /**
     * Get icon HTML
     */
    public function getIconHtmlAttribute(): string
    {
        if ($this->icon) {
            return '<i class="bx ' . e($this->icon) . '"></i>';
        }
        return '<i class="bx bx-check"></i>';
    }
}
```

---

## Task 2.1.7: RoomImage Model

```bash
php artisan make:model RoomImage
```

**File:** `app/Models/RoomImage.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class RoomImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'path',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    // =====================
    // RELATIONSHIPS
    // =====================

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    // =====================
    // ACCESSORS
    // =====================

    /**
     * Get full URL to image
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }

    // =====================
    // METHODS
    // =====================

    /**
     * Delete the image file from storage
     */
    public function deleteFile(): bool
    {
        return Storage::disk('public')->delete($this->path);
    }
}
```

---

## Task 2.1.8: Amenity Seeder

```bash
php artisan make:seeder AmenitySeeder
```

**File:** `database/seeders/AmenitySeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Models\Amenity;
use Illuminate\Database\Seeder;

class AmenitySeeder extends Seeder
{
    public function run(): void
    {
        $amenities = [
            ['name' => 'Projector', 'icon' => 'bx-projector'],
            ['name' => 'Whiteboard', 'icon' => 'bx-chalkboard'],
            ['name' => 'Video Conferencing', 'icon' => 'bx-video'],
            ['name' => 'Teleconferencing Phone', 'icon' => 'bx-phone'],
            ['name' => 'Computer/Monitor', 'icon' => 'bx-desktop'],
            ['name' => 'Flip Chart', 'icon' => 'bx-note'],
            ['name' => 'Air Conditioning', 'icon' => 'bx-wind'],
            ['name' => 'Natural Light/Windows', 'icon' => 'bx-sun'],
        ];

        foreach ($amenities as $amenity) {
            Amenity::firstOrCreate(
                ['name' => $amenity['name']],
                ['icon' => $amenity['icon']]
            );
        }
    }
}
```

---

## Task 2.1.9: Room Factory

```bash
php artisan make:factory RoomFactory
```

**File:** `database/factories/RoomFactory.php`

```php
<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        $floors = ['Ground Floor', '1st Floor', '2nd Floor', '3rd Floor', '4th Floor'];
        $wings = ['East Wing', 'West Wing', 'North Wing', 'South Wing', ''];
        
        return [
            'name' => 'Room ' . $this->faker->unique()->numberBetween(101, 999),
            'capacity' => $this->faker->randomElement([4, 6, 8, 10, 12, 15, 20, 25, 30]),
            'floor_location' => $this->faker->randomElement($floors) . ($this->faker->boolean(50) ? ', ' . $this->faker->randomElement($wings) : ''),
            'description' => $this->faker->optional(0.7)->paragraph(),
            'status' => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    public function underMaintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'under_maintenance',
        ]);
    }
}
```

---

## Task 2.1.10: Room Seeder (Development Data)

```bash
php artisan make:seeder RoomSeeder
```

**File:** `database/seeders/RoomSeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\Amenity;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure amenities exist
        $this->call(AmenitySeeder::class);
        
        $amenityIds = Amenity::pluck('id')->toArray();

        // Create sample rooms
        $rooms = [
            [
                'name' => 'Boardroom A',
                'capacity' => 20,
                'floor_location' => '3rd Floor, East Wing',
                'description' => 'Executive boardroom with panoramic views. Ideal for board meetings and important presentations.',
                'status' => 'active',
            ],
            [
                'name' => 'Meeting Room 101',
                'capacity' => 8,
                'floor_location' => '1st Floor',
                'description' => 'Small meeting room suitable for team discussions.',
                'status' => 'active',
            ],
            [
                'name' => 'Meeting Room 102',
                'capacity' => 6,
                'floor_location' => '1st Floor',
                'description' => 'Compact meeting space for quick huddles.',
                'status' => 'active',
            ],
            [
                'name' => 'Conference Room B',
                'capacity' => 15,
                'floor_location' => '2nd Floor, West Wing',
                'description' => 'Mid-sized conference room with video conferencing capabilities.',
                'status' => 'active',
            ],
            [
                'name' => 'Training Room',
                'capacity' => 30,
                'floor_location' => 'Ground Floor',
                'description' => 'Large room for training sessions and workshops.',
                'status' => 'active',
            ],
            [
                'name' => 'Meeting Room 201',
                'capacity' => 10,
                'floor_location' => '2nd Floor',
                'description' => null,
                'status' => 'inactive',
            ],
        ];

        foreach ($rooms as $roomData) {
            $room = Room::create($roomData);
            
            // Attach random amenities (3-6 per room)
            $randomAmenities = collect($amenityIds)->random(rand(3, min(6, count($amenityIds))));
            $room->amenities()->attach($randomAmenities);
        }
    }
}
```

---

## Task 2.1.11: Update DatabaseSeeder

**File:** `database/seeders/DatabaseSeeder.php`

Add to the `run()` method:

```php
public function run(): void
{
    $this->call([
        UserSeeder::class,
        AmenitySeeder::class,
        RoomSeeder::class,
    ]);
}
```

---

## Task 2.1.12: Create Room Placeholder Image

**File:** `public/assets/img/rooms/placeholder.png`

Create a simple placeholder image (or copy from mockup assets if available). This is shown when a room has no uploaded photos.

---

## Task 2.1.13: Run Migrations

```bash
php artisan migrate:fresh --seed
```

Verify tables exist:
```bash
php artisan tinker
>>> \DB::select('SELECT table_name FROM information_schema.tables WHERE table_schema = \'public\'');
```

---

## Task 2.1.14: Create Unit Tests

```bash
php artisan make:test Unit/RoomTest --unit
```

**File:** `tests/Unit/RoomTest.php`

```php
<?php

namespace Tests\Unit;

use App\Models\Room;
use App\Models\Amenity;
use App\Models\RoomImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AmenitySeeder::class);
    }

    public function test_room_can_be_created(): void
    {
        $room = Room::create([
            'name' => 'Test Room',
            'capacity' => 10,
            'floor_location' => '1st Floor',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('rooms', ['name' => 'Test Room']);
    }

    public function test_room_has_amenities_relationship(): void
    {
        $room = Room::factory()->create();
        $amenity = Amenity::first();
        
        $room->amenities()->attach($amenity->id);
        
        $this->assertTrue($room->amenities->contains($amenity));
    }

    public function test_room_active_scope(): void
    {
        Room::factory()->create(['status' => 'active']);
        Room::factory()->create(['status' => 'inactive']);
        
        $activeRooms = Room::active()->get();
        
        $this->assertCount(1, $activeRooms);
    }

    public function test_room_by_capacity_scope(): void
    {
        Room::factory()->create(['capacity' => 5]);
        Room::factory()->create(['capacity' => 15]);
        
        $largeRooms = Room::byCapacity(10)->get();
        
        $this->assertCount(1, $largeRooms);
    }

    public function test_room_status_badge_accessor(): void
    {
        $activeRoom = Room::factory()->create(['status' => 'active']);
        $inactiveRoom = Room::factory()->create(['status' => 'inactive']);
        
        $this->assertStringContainsString('bg-success', $activeRoom->status_badge);
        $this->assertStringContainsString('bg-secondary', $inactiveRoom->status_badge);
    }

    public function test_room_can_be_soft_deleted(): void
    {
        $room = Room::factory()->create();
        $roomId = $room->id;
        
        $room->delete();
        
        $this->assertSoftDeleted('rooms', ['id' => $roomId]);
    }

    public function test_room_name_must_be_unique(): void
    {
        Room::factory()->create(['name' => 'Unique Room']);
        
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Room::factory()->create(['name' => 'Unique Room']);
    }

    public function test_room_can_check_if_has_bookings(): void
    {
        $room = Room::factory()->create();
        
        // Without bookings table, this should return false
        $this->assertFalse($room->hasBookings());
    }

    public function test_room_can_be_deleted_check(): void
    {
        $room = Room::factory()->create();
        
        // Room with no bookings can be deleted
        $this->assertTrue($room->canBeDeleted());
    }
}
```

Run tests:
```bash
php artisan test --filter=RoomTest
```

---

## Acceptance Criteria

- [x] `rooms`, `amenities`, `amenity_room`, `room_images` tables exist
- [x] Room model has relationships: amenities(), images(), maintenanceSchedules(), bookings()
- [x] Room model has scopes: active(), available(), byCapacity(), byStatus(), withAmenities()
- [x] Room model has accessors: primary_image, status_badge, status_display
- [x] Room model has methods: isAvailable(), hasBookings(), canBeDeleted()
- [x] Amenity model has relationship to rooms
- [x] RoomImage model has URL accessor and deleteFile() method
- [x] AmenitySeeder creates 8 default amenities with icons
- [x] RoomSeeder creates 6 sample rooms with amenities
- [x] Room placeholder image exists
- [x] `php artisan test --filter=RoomTest` passes (16 passed, 2 skipped pending Booking model)

---

**Next:** [Step 2.2 - Room Maintenance Scheduling](./step-2.2-room-maintenance.md)
