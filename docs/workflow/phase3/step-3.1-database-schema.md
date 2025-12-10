# Step 3.1: Database Schema - Bookings

**Priority:** CRITICAL | **Ref:** §6.2.1, §6.2.2 | **Dependencies:** Phase 2 Complete  
**Status:** TODO

---

## Objective

Create database tables for bookings and recurring booking series. Create Booking and BookingSeries models with relationships, scopes, and helper methods.

---

## Task 3.1.1: Bookings Migration

```bash
php artisan make:migration create_bookings_table
```

**File:** `database/migrations/YYYY_MM_DD_HHMMSS_create_bookings_table.php`

**Schema:**
```php
Schema::create('bookings', function (Blueprint $table) {
    $table->id();
    $table->string('reference_number', 20)->unique(); // BK-2025-00001
    
    // Relationships
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('room_id')->constrained()->onDelete('cascade');
    $table->foreignId('series_id')->nullable()->constrained('booking_series')->onDelete('cascade');
    
    // Booking details
    $table->date('booking_date');
    $table->time('start_time');
    $table->time('end_time');
    $table->text('purpose'); // max 500 chars (validated in app)
    
    // Status: confirmed, cancelled, completed (NO pending/rejected)
    $table->enum('status', ['confirmed', 'cancelled', 'completed'])->default('confirmed');
    
    // Cancellation details
    $table->text('cancellation_reason')->nullable();
    $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamp('cancelled_at')->nullable();
    
    // Timestamps
    $table->timestamps();
    $table->softDeletes();
    
    // Indexes for common queries
    $table->index(['booking_date', 'status']);
    $table->index(['user_id', 'booking_date']);
    $table->index(['room_id', 'booking_date', 'status']);
});
```

---

## Task 3.1.2: Booking Series Migration

```bash
php artisan make:migration create_booking_series_table --create=booking_series
```

**File:** `database/migrations/YYYY_MM_DD_HHMMSS_create_booking_series_table.php`

> **Note:** This migration must run BEFORE the bookings migration (rename timestamp if needed).

**Schema:**
```php
Schema::create('booking_series', function (Blueprint $table) {
    $table->id();
    $table->string('reference_number', 25)->unique(); // BK-SERIES-2025-00001
    
    // Relationships
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('room_id')->constrained()->onDelete('cascade');
    
    // Recurrence settings
    $table->enum('recurrence_type', ['daily', 'weekly', 'monthly']);
    $table->json('recurrence_pattern'); // e.g., {"interval": 1, "days_of_week": [1, 3, 5]}
    $table->date('start_date');
    $table->date('end_date');
    
    // Common booking details
    $table->time('start_time');
    $table->time('end_time');
    $table->text('purpose');
    
    $table->timestamps();
});
```

**Recurrence Pattern Examples:**

```json
// Daily: every 2 days
{"interval": 2}

// Weekly: Monday, Wednesday, Friday
{"interval": 1, "days_of_week": [1, 3, 5]}

// Monthly: 15th of each month
{"interval": 1, "day_of_month": 15}
```

---

## Task 3.1.3: Run Migrations

```bash
php artisan migrate
```

Verify tables exist:
```bash
php artisan tinker
>>> Schema::hasTable('bookings')
=> true
>>> Schema::hasTable('booking_series')
=> true
```

---

## Task 3.1.4: Create Booking Model

```bash
php artisan make:model Booking
```

**File:** `app/Models/Booking.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference_number',
        'user_id',
        'room_id',
        'series_id',
        'booking_date',
        'start_time',
        'end_time',
        'purpose',
        'status',
        'cancellation_reason',
        'cancelled_by',
        'cancelled_at',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'cancelled_at' => 'datetime',
    ];

    // =====================
    // RELATIONSHIPS
    // =====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(BookingSeries::class, 'series_id');
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    // =====================
    // ACCESSORS
    // =====================

    /**
     * Get formatted duration (e.g., "2h 30m")
     */
    public function getDurationAttribute(): string
    {
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);
        $minutes = $start->diffInMinutes($end);
        
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        
        if ($hours > 0 && $mins > 0) {
            return "{$hours}h {$mins}m";
        } elseif ($hours > 0) {
            return "{$hours}h";
        } else {
            return "{$mins}m";
        }
    }

    /**
     * Get duration in minutes
     */
    public function getDurationMinutesAttribute(): int
    {
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);
        return $start->diffInMinutes($end);
    }

    /**
     * Get status badge HTML
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'confirmed' => '<span class="badge bg-success">Confirmed</span>',
            'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
            'completed' => '<span class="badge bg-secondary">Completed</span>',
            default => '<span class="badge bg-secondary">Unknown</span>',
        };
    }

    /**
     * Check if booking can be edited
     * Regular users: can edit confirmed bookings (changed from pending-only)
     * Admin/Director: can edit any status
     */
    public function getIsEditableAttribute(): bool
    {
        // Cannot edit completed or cancelled bookings
        if (in_array($this->status, ['completed', 'cancelled'])) {
            return false;
        }
        
        // Cannot edit past bookings
        if ($this->booking_date < now()->toDateString()) {
            return false;
        }
        
        return true;
    }

    /**
     * Check if booking can be cancelled
     */
    public function getIsCancellableAttribute(): bool
    {
        // Can only cancel confirmed bookings
        if ($this->status !== 'confirmed') {
            return false;
        }
        
        // Cannot cancel past bookings
        if ($this->booking_date < now()->toDateString()) {
            return false;
        }
        
        // Cannot cancel if booking already ended today
        if ($this->booking_date == now()->toDateString() 
            && Carbon::parse($this->end_time)->lt(now())) {
            return false;
        }
        
        return true;
    }

    /**
     * Check if this is a recurring booking
     */
    public function getIsRecurringAttribute(): bool
    {
        return $this->series_id !== null;
    }

    /**
     * Get formatted time range (e.g., "09:00 - 11:00")
     */
    public function getTimeRangeAttribute(): string
    {
        $start = Carbon::parse($this->start_time)->format('H:i');
        $end = Carbon::parse($this->end_time)->format('H:i');
        return "{$start} - {$end}";
    }

    // =====================
    // SCOPES
    // =====================

    /**
     * Filter by confirmed status
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Filter by completed status
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Filter by cancelled status
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Filter bookings for a specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Filter bookings for a specific room
     */
    public function scopeForRoom($query, int $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    /**
     * Filter upcoming bookings (date >= today)
     */
    public function scopeUpcoming($query)
    {
        return $query->where('booking_date', '>=', now()->toDateString())
                     ->whereIn('status', ['confirmed']);
    }

    /**
     * Filter past bookings (date < today or completed)
     */
    public function scopePast($query)
    {
        return $query->where(function ($q) {
            $q->where('booking_date', '<', now()->toDateString())
              ->orWhere('status', 'completed');
        });
    }

    /**
     * Filter bookings on a specific date
     */
    public function scopeOnDate($query, string $date)
    {
        return $query->where('booking_date', $date);
    }

    /**
     * Filter bookings within a date range
     */
    public function scopeBetweenDates($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('booking_date', [$startDate, $endDate]);
    }

    // =====================
    // STATIC METHODS
    // =====================

    /**
     * Generate unique reference number (BK-2025-00001)
     */
    public static function generateReferenceNumber(): string
    {
        $year = now()->year;
        $prefix = "BK-{$year}-";
        
        // Get the last booking reference for this year
        $lastBooking = self::withTrashed()
            ->where('reference_number', 'like', $prefix . '%')
            ->orderBy('reference_number', 'desc')
            ->first();
        
        if ($lastBooking) {
            // Extract the number part and increment
            $lastNumber = (int) substr($lastBooking->reference_number, -5);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        return $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }
}
```

---

## Task 3.1.5: Create BookingSeries Model

```bash
php artisan make:model BookingSeries
```

**File:** `app/Models/BookingSeries.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingSeries extends Model
{
    use HasFactory;

    protected $table = 'booking_series';

    protected $fillable = [
        'reference_number',
        'user_id',
        'room_id',
        'recurrence_type',
        'recurrence_pattern',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'purpose',
    ];

    protected $casts = [
        'recurrence_pattern' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    // =====================
    // RELATIONSHIPS
    // =====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'series_id');
    }

    // =====================
    // ACCESSORS
    // =====================

    /**
     * Get the number of occurrences in this series
     */
    public function getOccurrenceCountAttribute(): int
    {
        return $this->bookings()->count();
    }

    /**
     * Get human-readable recurrence description
     */
    public function getRecurrenceDescriptionAttribute(): string
    {
        $pattern = $this->recurrence_pattern;
        
        return match ($this->recurrence_type) {
            'daily' => "Every " . ($pattern['interval'] ?? 1) . " day(s)",
            'weekly' => $this->formatWeeklyDescription($pattern),
            'monthly' => "Monthly on day " . ($pattern['day_of_month'] ?? 1),
            default => 'Unknown pattern',
        };
    }

    private function formatWeeklyDescription(array $pattern): string
    {
        $dayNames = [
            1 => 'Mon', 2 => 'Tue', 3 => 'Wed',
            4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'
        ];
        
        $days = $pattern['days_of_week'] ?? [];
        $dayLabels = array_map(fn($d) => $dayNames[$d] ?? '', $days);
        
        return "Weekly on " . implode(', ', $dayLabels);
    }

    // =====================
    // STATIC METHODS
    // =====================

    /**
     * Generate unique series reference number (BK-SERIES-2025-00001)
     */
    public static function generateReferenceNumber(): string
    {
        $year = now()->year;
        $prefix = "BK-SERIES-{$year}-";
        
        $lastSeries = self::where('reference_number', 'like', $prefix . '%')
            ->orderBy('reference_number', 'desc')
            ->first();
        
        if ($lastSeries) {
            $lastNumber = (int) substr($lastSeries->reference_number, -5);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        return $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }
}
```

---

## Task 3.1.6: Update User Model Relationship

**File:** `app/Models/User.php`

Add the bookings relationship:

```php
// Add to existing relationships section
public function bookings(): HasMany
{
    return $this->hasMany(Booking::class);
}
```

Add import at top:
```php
use Illuminate\Database\Eloquent\Relations\HasMany;
```

---

## Task 3.1.7: Create Booking Factory

```bash
php artisan make:factory BookingFactory
```

**File:** `database/factories/BookingFactory.php`

```php
<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\User;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $startHour = $this->faker->numberBetween(8, 16);
        $duration = $this->faker->randomElement([1, 2, 3, 4]); // hours
        $endHour = min($startHour + $duration, 18);
        
        return [
            'reference_number' => Booking::generateReferenceNumber(),
            'user_id' => User::factory(),
            'room_id' => Room::factory(),
            'booking_date' => $this->faker->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'start_time' => sprintf('%02d:00', $startHour),
            'end_time' => sprintf('%02d:00', $endHour),
            'purpose' => $this->faker->sentence(10),
            'status' => 'confirmed',
        ];
    }

    /**
     * Set status to confirmed
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
        ]);
    }

    /**
     * Set status to completed
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'booking_date' => $this->faker->dateTimeBetween('-30 days', '-1 day')->format('Y-m-d'),
        ]);
    }

    /**
     * Set status to cancelled
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancellation_reason' => $this->faker->sentence(),
            'cancelled_at' => now(),
        ]);
    }
}
```

---

## Task 3.1.8: Create Booking Seeder (Development)

```bash
php artisan make:seeder BookingSeeder
```

**File:** `database/seeders/BookingSeeder.php`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Booking;
use App\Models\User;
use App\Models\Room;
use Carbon\Carbon;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $rooms = Room::active()->get();
        
        if ($users->isEmpty() || $rooms->isEmpty()) {
            $this->command->warn('No users or rooms found. Skipping booking seeder.');
            return;
        }

        $purposes = [
            'Team Weekly Standup',
            'Client Presentation',
            'Project Kickoff Meeting',
            'Sprint Planning',
            'Technical Review',
            'Design Workshop',
            'Training Session',
            'Interview',
            'Department Meeting',
            'One-on-One Discussion',
        ];

        // Create some upcoming confirmed bookings
        foreach (range(1, 15) as $i) {
            $user = $users->random();
            $room = $rooms->random();
            $date = Carbon::now()->addDays(rand(1, 30));
            $startHour = rand(8, 15);
            $duration = rand(1, 3);
            
            // Check availability before creating
            $startTime = sprintf('%02d:00', $startHour);
            $endTime = sprintf('%02d:00', min($startHour + $duration, 18));
            
            if ($room->isAvailable($date->format('Y-m-d'), $startTime, $endTime)) {
                Booking::create([
                    'reference_number' => Booking::generateReferenceNumber(),
                    'user_id' => $user->id,
                    'room_id' => $room->id,
                    'booking_date' => $date->format('Y-m-d'),
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'purpose' => $purposes[array_rand($purposes)],
                    'status' => 'confirmed',
                ]);
            }
        }

        // Create some past completed bookings
        foreach (range(1, 10) as $i) {
            $user = $users->random();
            $room = $rooms->random();
            $date = Carbon::now()->subDays(rand(1, 60));
            $startHour = rand(8, 15);
            $duration = rand(1, 3);
            
            Booking::create([
                'reference_number' => Booking::generateReferenceNumber(),
                'user_id' => $user->id,
                'room_id' => $room->id,
                'booking_date' => $date->format('Y-m-d'),
                'start_time' => sprintf('%02d:00', $startHour),
                'end_time' => sprintf('%02d:00', min($startHour + $duration, 18)),
                'purpose' => $purposes[array_rand($purposes)],
                'status' => 'completed',
            ]);
        }

        // Create a few cancelled bookings
        foreach (range(1, 5) as $i) {
            $user = $users->random();
            $room = $rooms->random();
            $date = Carbon::now()->addDays(rand(1, 14));
            
            Booking::create([
                'reference_number' => Booking::generateReferenceNumber(),
                'user_id' => $user->id,
                'room_id' => $room->id,
                'booking_date' => $date->format('Y-m-d'),
                'start_time' => sprintf('%02d:00', rand(8, 14)),
                'end_time' => sprintf('%02d:00', rand(15, 18)),
                'purpose' => $purposes[array_rand($purposes)],
                'status' => 'cancelled',
                'cancellation_reason' => 'Meeting rescheduled',
                'cancelled_by' => $user->id,
                'cancelled_at' => now(),
            ]);
        }

        $this->command->info('Created ' . Booking::count() . ' sample bookings.');
    }
}
```

---

## Task 3.1.9: Update DatabaseSeeder

**File:** `database/seeders/DatabaseSeeder.php`

Add the BookingSeeder call:

```php
public function run(): void
{
    $this->call([
        UserSeeder::class,
        AmenitySeeder::class,
        RoomSeeder::class,
        BookingSeeder::class, // Add this line
    ]);
}
```

---

## Testing Requirements

**File:** `tests/Unit/BookingTest.php`

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Booking;
use App\Models\BookingSeries;
use App\Models\User;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_belongs_to_user()
    {
        $booking = Booking::factory()->create();
        $this->assertInstanceOf(User::class, $booking->user);
    }

    public function test_booking_belongs_to_room()
    {
        $booking = Booking::factory()->create();
        $this->assertInstanceOf(Room::class, $booking->room);
    }

    public function test_reference_number_is_unique()
    {
        $ref1 = Booking::generateReferenceNumber();
        Booking::factory()->create(['reference_number' => $ref1]);
        
        $ref2 = Booking::generateReferenceNumber();
        $this->assertNotEquals($ref1, $ref2);
    }

    public function test_reference_number_format()
    {
        $ref = Booking::generateReferenceNumber();
        $year = now()->year;
        $this->assertMatchesRegularExpression("/^BK-{$year}-\d{5}$/", $ref);
    }

    public function test_duration_accessor()
    {
        $booking = Booking::factory()->create([
            'start_time' => '09:00',
            'end_time' => '11:30',
        ]);
        
        $this->assertEquals('2h 30m', $booking->duration);
    }

    public function test_status_badge_confirmed()
    {
        $booking = Booking::factory()->confirmed()->create();
        $this->assertStringContainsString('bg-success', $booking->status_badge);
    }

    public function test_confirmed_booking_is_editable()
    {
        $booking = Booking::factory()->create([
            'status' => 'confirmed',
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
        ]);
        $this->assertTrue($booking->is_editable);
    }

    public function test_completed_booking_is_not_editable()
    {
        $booking = Booking::factory()->completed()->create();
        $this->assertFalse($booking->is_editable);
    }

    public function test_cancelled_booking_is_not_cancellable()
    {
        $booking = Booking::factory()->cancelled()->create();
        $this->assertFalse($booking->is_cancellable);
    }

    public function test_upcoming_scope()
    {
        Booking::factory()->create([
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
            'status' => 'confirmed',
        ]);
        Booking::factory()->completed()->create();
        
        $this->assertEquals(1, Booking::upcoming()->count());
    }
}
```

Run tests:
```bash
php artisan test --filter=BookingTest
```

---

## Acceptance Criteria

- [ ] `bookings` table exists with all columns
- [ ] `booking_series` table exists with all columns
- [ ] Booking model has relationships: user, room, series, cancelledByUser
- [ ] Booking model has scopes: confirmed, cancelled, completed, forUser, forRoom, upcoming, past
- [ ] Booking model has accessors: duration, status_badge, is_editable, is_cancellable
- [ ] BookingSeries model has relationships: user, room, bookings
- [ ] Reference number generation works correctly (BK-YYYY-NNNNN)
- [ ] BookingFactory creates valid bookings
- [ ] BookingSeeder populates sample data
- [ ] All unit tests pass

---

**Next:** [Step 3.2 - Booking Creation](./step-3.2-booking-creation.md)
