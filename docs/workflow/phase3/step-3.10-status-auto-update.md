# Step 3.10: Status Auto-Update (Scheduled Job)

**Priority:** MEDIUM | **Ref:** §6.4.3 | **Dependencies:** Step 3.1  
**Status:** TODO

---

## Objective

Implement a scheduled job that automatically updates booking status from "Confirmed" to "Completed" after the booking end time has passed.

---

## Task 3.10.1: Create the Booking Completion Command

```bash
php artisan make:command CompleteExpiredBookings
```

**File:** `app/Console/Commands/CompleteExpiredBookings.php`

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CompleteExpiredBookings extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'bookings:complete-expired 
                            {--dry-run : Preview what would be updated without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Update confirmed bookings to completed status after their end time has passed';

    /**
     * Execute the console command.
     */
    public function handle(AuditService $auditService): int
    {
        $this->info('Checking for expired bookings...');

        $now = Carbon::now();
        $today = $now->toDateString();
        $currentTime = $now->format('H:i:s');

        // Find confirmed bookings that have ended
        $query = Booking::where('status', 'confirmed')
            ->where(function ($q) use ($today, $currentTime) {
                // Past dates - definitely completed
                $q->where('booking_date', '<', $today)
                  // Or today but end time has passed
                  ->orWhere(function ($q2) use ($today, $currentTime) {
                      $q2->where('booking_date', $today)
                         ->where('end_time', '<', $currentTime);
                  });
            });

        $expiredBookings = $query->get();

        if ($expiredBookings->isEmpty()) {
            $this->info('No expired bookings found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$expiredBookings->count()} expired booking(s).");

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN - No changes will be made.');
            $this->table(
                ['Reference', 'Date', 'Time', 'Room'],
                $expiredBookings->map(function ($booking) {
                    return [
                        $booking->reference_number,
                        $booking->booking_date->format('Y-m-d'),
                        $booking->time_range,
                        $booking->room->name ?? 'N/A',
                    ];
                })
            );
            return Command::SUCCESS;
        }

        // Update bookings to completed
        $updated = 0;
        foreach ($expiredBookings as $booking) {
            $booking->update(['status' => 'completed']);
            $updated++;

            // Log to audit trail
            $auditService->log(
                'booking_auto_completed',
                'booking',
                $booking->id,
                [
                    'reference' => $booking->reference_number,
                    'completed_by' => 'system',
                    'booking_date' => $booking->booking_date->format('Y-m-d'),
                    'end_time' => $booking->end_time,
                ]
            );
        }

        Log::info('Completed expired bookings', [
            'count' => $updated,
            'timestamp' => $now->toDateTimeString(),
        ]);

        $this->info("Successfully updated {$updated} booking(s) to 'completed' status.");

        return Command::SUCCESS;
    }
}
```

---

## Task 3.10.2: Register the Scheduled Command

**File:** `routes/console.php` (Laravel 11+)

```php
use Illuminate\Support\Facades\Schedule;

// Run every 15 minutes to mark completed bookings
Schedule::command('bookings:complete-expired')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/scheduler.log'));
```

**Alternative (Laravel 10 or older):** `app/Console/Kernel.php`

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('bookings:complete-expired')
             ->everyFifteenMinutes()
             ->withoutOverlapping()
             ->runInBackground()
             ->appendOutputTo(storage_path('logs/scheduler.log'));
}
```

---

## Task 3.10.3: Set Up Task Scheduler (Production)

For **production** deployment, add the Laravel scheduler to the system's cron:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

For **Windows (Task Scheduler)**, create a scheduled task that runs every minute:
- Program: `php`
- Arguments: `artisan schedule:run`
- Start in: `D:\Herd\mrbs`

---

## Task 3.10.4: Add Manual Trigger for Testing

For testing purposes, add a route to manually trigger the command (admin only):

**File:** `routes/web.php`

```php
// Admin-only manual trigger for testing
Route::middleware(['auth', 'can:manage-bookings'])->group(function () {
    Route::post('/admin/bookings/complete-expired', function () {
        Artisan::call('bookings:complete-expired');
        return back()->with('success', 'Completed expired bookings: ' . Artisan::output());
    })->name('admin.bookings.complete-expired');
});
```

Add a button in the admin bookings page:

**File:** `resources/views/admin/bookings/index.blade.php` (add to header)

```blade
<form action="{{ route('admin.bookings.complete-expired') }}" method="POST" class="d-inline">
    @csrf
    <button type="submit" class="btn btn-outline-secondary btn-sm" 
            onclick="return confirm('Run completion check now?')">
        <i class="bx bx-check-double me-1"></i> Complete Expired
    </button>
</form>
```

---

## Task 3.10.5: Create a Service for Status Transitions

For cleaner code, create a dedicated service for booking status transitions:

```bash
php artisan make:class Services/BookingStatusService
```

**File:** `app/Services/BookingStatusService.php`

```php
<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BookingStatusService
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Find all bookings that should be marked as completed
     */
    public function getExpiredBookings(): Collection
    {
        $now = Carbon::now();
        $today = $now->toDateString();
        $currentTime = $now->format('H:i:s');

        return Booking::where('status', 'confirmed')
            ->where(function ($q) use ($today, $currentTime) {
                $q->where('booking_date', '<', $today)
                  ->orWhere(function ($q2) use ($today, $currentTime) {
                      $q2->where('booking_date', $today)
                         ->where('end_time', '<', $currentTime);
                  });
            })
            ->get();
    }

    /**
     * Mark a single booking as completed
     */
    public function completeBooking(Booking $booking): bool
    {
        if ($booking->status !== 'confirmed') {
            return false;
        }

        $booking->update(['status' => 'completed']);

        $this->auditService->log(
            'booking_auto_completed',
            'booking',
            $booking->id,
            [
                'reference' => $booking->reference_number,
                'completed_by' => 'system',
            ]
        );

        return true;
    }

    /**
     * Mark all expired bookings as completed
     */
    public function completeAllExpired(): int
    {
        $expired = $this->getExpiredBookings();
        $count = 0;

        foreach ($expired as $booking) {
            if ($this->completeBooking($booking)) {
                $count++;
            }
        }

        if ($count > 0) {
            Log::info("Auto-completed {$count} expired bookings");
        }

        return $count;
    }

    /**
     * Check if a booking should be auto-completed
     */
    public function shouldBeCompleted(Booking $booking): bool
    {
        if ($booking->status !== 'confirmed') {
            return false;
        }

        $now = Carbon::now();
        $bookingEnd = Carbon::parse($booking->booking_date->format('Y-m-d') . ' ' . $booking->end_time);

        return $now->gt($bookingEnd);
    }
}
```

---

## Task 3.10.6: Update Command to Use Service

**File:** `app/Console/Commands/CompleteExpiredBookings.php`

Update to use the service:

```php
public function handle(BookingStatusService $statusService): int
{
    $this->info('Checking for expired bookings...');

    if ($this->option('dry-run')) {
        $expired = $statusService->getExpiredBookings();
        
        if ($expired->isEmpty()) {
            $this->info('No expired bookings found.');
            return Command::SUCCESS;
        }

        $this->warn("DRY RUN - {$expired->count()} booking(s) would be updated:");
        $this->table(
            ['Reference', 'Date', 'Time', 'Room'],
            $expired->map(fn($b) => [
                $b->reference_number,
                $b->booking_date->format('Y-m-d'),
                $b->time_range,
                $b->room->name ?? 'N/A',
            ])
        );
        return Command::SUCCESS;
    }

    $count = $statusService->completeAllExpired();

    if ($count === 0) {
        $this->info('No expired bookings to complete.');
    } else {
        $this->info("Successfully completed {$count} booking(s).");
    }

    return Command::SUCCESS;
}
```

Don't forget to import:

```php
use App\Services\BookingStatusService;
```

---

## Testing Requirements

**File:** `tests/Feature/BookingStatusAutoUpdateTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Booking;
use App\Models\User;
use App\Models\Room;
use App\Services\BookingStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

class BookingStatusAutoUpdateTest extends TestCase
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

    public function test_past_booking_is_marked_as_completed()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'status' => 'confirmed',
            'booking_date' => now()->subDays(1)->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);

        Artisan::call('bookings:complete-expired');

        $booking->refresh();
        $this->assertEquals('completed', $booking->status);
    }

    public function test_future_booking_is_not_changed()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'status' => 'confirmed',
            'booking_date' => now()->addDays(1)->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);

        Artisan::call('bookings:complete-expired');

        $booking->refresh();
        $this->assertEquals('confirmed', $booking->status);
    }

    public function test_cancelled_booking_is_not_changed()
    {
        $booking = Booking::factory()->cancelled()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'booking_date' => now()->subDays(1)->format('Y-m-d'),
        ]);

        Artisan::call('bookings:complete-expired');

        $booking->refresh();
        $this->assertEquals('cancelled', $booking->status);
    }

    public function test_already_completed_booking_is_not_updated()
    {
        $booking = Booking::factory()->completed()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
        ]);

        $originalUpdatedAt = $booking->updated_at;

        // Wait a moment
        sleep(1);

        Artisan::call('bookings:complete-expired');

        $booking->refresh();
        $this->assertEquals($originalUpdatedAt->timestamp, $booking->updated_at->timestamp);
    }

    public function test_dry_run_does_not_update()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'status' => 'confirmed',
            'booking_date' => now()->subDays(1)->format('Y-m-d'),
        ]);

        Artisan::call('bookings:complete-expired', ['--dry-run' => true]);

        $booking->refresh();
        $this->assertEquals('confirmed', $booking->status);
    }

    public function test_service_correctly_identifies_expired_bookings()
    {
        // Create various bookings
        $pastConfirmed = Booking::factory()->create([
            'status' => 'confirmed',
            'booking_date' => now()->subDays(1)->format('Y-m-d'),
        ]);

        $futureConfirmed = Booking::factory()->create([
            'status' => 'confirmed',
            'booking_date' => now()->addDays(1)->format('Y-m-d'),
        ]);

        $pastCancelled = Booking::factory()->cancelled()->create([
            'booking_date' => now()->subDays(1)->format('Y-m-d'),
        ]);

        $service = app(BookingStatusService::class);
        $expired = $service->getExpiredBookings();

        $this->assertCount(1, $expired);
        $this->assertEquals($pastConfirmed->id, $expired->first()->id);
    }

    public function test_command_outputs_correct_count()
    {
        Booking::factory()->count(3)->create([
            'status' => 'confirmed',
            'booking_date' => now()->subDays(1)->format('Y-m-d'),
        ]);

        $this->artisan('bookings:complete-expired')
             ->expectsOutputToContain('3 booking(s)')
             ->assertSuccessful();
    }
}
```

---

## Acceptance Criteria

- [ ] Command `php artisan bookings:complete-expired` exists
- [ ] Running command marks past confirmed bookings as completed
- [ ] Future bookings are not affected
- [ ] Cancelled bookings are not changed
- [ ] Already completed bookings are not re-updated
- [ ] `--dry-run` option shows preview without changes
- [ ] Command is scheduled to run every 15 minutes
- [ ] Audit log entries are created for auto-completions
- [ ] Admin can manually trigger completion check
- [ ] Command outputs clear success/failure messages
- [ ] All tests pass

---

## Scheduler Verification

To verify the scheduler is working:

```bash
# List scheduled commands
php artisan schedule:list

# Run the scheduler once (for testing)
php artisan schedule:run

# Test the command manually
php artisan bookings:complete-expired --dry-run
```

---

**Next:** [Step 3.11 - Audit Logging](./step-3.11-audit-logging.md)
