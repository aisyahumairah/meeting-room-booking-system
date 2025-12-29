# Step 5.5: Performance Optimization

**Priority:** MEDIUM | **Ref:** §8.5 | **Dependencies:** None  
**Status:** TODO

---

## Objective

Optimize database queries, implement caching strategies, and ensure efficient asset delivery for production performance.

---

## Task 5.5.1: Database Index Audit

### Verify Existing Indexes

```bash
# Check existing indexes (PostgreSQL)
php artisan tinker
>>> \DB::select("SELECT indexname, indexdef FROM pg_indexes WHERE schemaname = 'public'");
```

### Add Missing Indexes

**File:** `database/migrations/xxxx_xx_xx_add_performance_indexes.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bookings table indexes
        Schema::table('bookings', function (Blueprint $table) {
            // Frequently queried columns
            $table->index(['booking_date', 'status'], 'bookings_date_status_idx');
            $table->index(['user_id', 'status'], 'bookings_user_status_idx');
            $table->index(['room_id', 'booking_date', 'status'], 'bookings_room_date_status_idx');
            
            // For calendar queries
            $table->index(['booking_date', 'start_time', 'end_time'], 'bookings_time_range_idx');
        });
        
        // Audit logs table indexes
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['actor_id', 'created_at'], 'audit_logs_actor_created_idx');
            $table->index(['event_type', 'created_at'], 'audit_logs_event_created_idx');
            $table->index(['target_type', 'target_id'], 'audit_logs_target_idx');
            $table->index('created_at', 'audit_logs_created_idx');
        });
        
        // Users table indexes
        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'status'], 'users_role_status_idx');
            $table->index('department', 'users_department_idx');
        });
        
        // Rooms table indexes
        Schema::table('rooms', function (Blueprint $table) {
            $table->index('status', 'rooms_status_idx');
            $table->index(['status', 'capacity'], 'rooms_status_capacity_idx');
        });
        
        // Amenities table index
        Schema::table('amenities', function (Blueprint $table) {
            $table->index('is_active', 'amenities_active_idx');
        });
        
        // Notification logs index
        if (Schema::hasTable('notification_logs')) {
            Schema::table('notification_logs', function (Blueprint $table) {
                $table->index(['status', 'created_at'], 'notification_logs_status_created_idx');
            });
        }
    }
    
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_date_status_idx');
            $table->dropIndex('bookings_user_status_idx');
            $table->dropIndex('bookings_room_date_status_idx');
            $table->dropIndex('bookings_time_range_idx');
        });
        
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_actor_created_idx');
            $table->dropIndex('audit_logs_event_created_idx');
            $table->dropIndex('audit_logs_target_idx');
            $table->dropIndex('audit_logs_created_idx');
        });
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_status_idx');
            $table->dropIndex('users_department_idx');
        });
        
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropIndex('rooms_status_idx');
            $table->dropIndex('rooms_status_capacity_idx');
        });
        
        Schema::table('amenities', function (Blueprint $table) {
            $table->dropIndex('amenities_active_idx');
        });
    }
};
```

Run the migration:
```bash
php artisan migrate
```

---

## Task 5.5.2: Eager Loading Audit (N+1 Prevention)

### Controllers to Review

**BookingController:**
```php
// ❌ N+1 Problem
$bookings = Booking::where('user_id', auth()->id())->get();
foreach ($bookings as $booking) {
    echo $booking->room->name; // Queries for each booking!
}

// ✅ Eager Loading
$bookings = Booking::where('user_id', auth()->id())
    ->with(['room', 'user', 'series'])
    ->get();
```

**DashboardController:**
```php
// Admin dashboard - upcoming bookings
$upcomingBookings = Booking::with(['room', 'user'])
    ->where('booking_date', '>=', now()->toDateString())
    ->where('status', 'confirmed')
    ->orderBy('booking_date')
    ->orderBy('start_time')
    ->limit(10)
    ->get();
```

**Admin\RoomController:**
```php
// Room list with amenities and image count
$rooms = Room::with(['amenities', 'images'])
    ->withCount('bookings')
    ->orderBy('name')
    ->paginate(20);
```

**Admin\UserController:**
```php
// User list with booking count
$users = User::withCount('bookings')
    ->orderBy('name')
    ->paginate(20);
```

**ReportController:**
```php
// Room utilization report
$rooms = Room::with(['bookings' => function ($query) use ($startDate, $endDate) {
    $query->whereBetween('booking_date', [$startDate, $endDate])
          ->where('status', 'confirmed');
}])->get();
```

### Eager Loading Checklist

```
□ Booking queries include:
  ├── room (for room name)
  ├── user (for booker name)
  └── series (for recurring info)

□ Room queries include:
  ├── amenities (for amenity list)
  ├── images (for gallery)
  └── withCount('bookings')

□ User queries include:
  └── withCount('bookings') where needed

□ Audit log queries include:
  └── actor (if displaying actor details)
```

---

## Task 5.5.3: Query Optimization for Reports

### Daily Bookings Report

```php
// Optimized query
$bookings = Booking::query()
    ->select([
        'bookings.id',
        'bookings.reference_number',
        'bookings.booking_date',
        'bookings.start_time',
        'bookings.end_time',
        'bookings.purpose',
        'bookings.status',
        'rooms.name as room_name',
        'rooms.capacity as room_capacity',
        'users.name as user_name',
        'users.department as user_department',
    ])
    ->join('rooms', 'bookings.room_id', '=', 'rooms.id')
    ->join('users', 'bookings.user_id', '=', 'users.id')
    ->where('bookings.booking_date', $date)
    ->orderBy('rooms.name')
    ->orderBy('bookings.start_time')
    ->get();
```

### Room Utilization Report

```php
// Use aggregation instead of loading all bookings
$roomStats = DB::table('bookings')
    ->select([
        'room_id',
        DB::raw('COUNT(*) as booking_count'),
        DB::raw('SUM(EXTRACT(EPOCH FROM (end_time::time - start_time::time)) / 3600) as total_hours'),
    ])
    ->whereBetween('booking_date', [$startDate, $endDate])
    ->where('status', 'confirmed')
    ->groupBy('room_id')
    ->get()
    ->keyBy('room_id');
```

---

## Task 5.5.4: System Settings Caching

**File:** `app/Models/SystemSetting.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value', 'type'];
    
    /**
     * Cache duration in seconds (1 hour)
     */
    protected static int $cacheDuration = 3600;
    
    /**
     * Get setting value with caching
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        return Cache::remember("system_setting.{$key}", static::$cacheDuration, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? static::castValue($setting->value, $setting->type) : $default;
        });
    }
    
    /**
     * Set setting value and clear cache
     */
    public static function setValue(string $key, mixed $value, string $type = 'string'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type]
        );
        
        Cache::forget("system_setting.{$key}");
        Cache::forget('system_settings.all');
    }
    
    /**
     * Get all settings with caching
     */
    public static function getAllCached(): array
    {
        return Cache::remember('system_settings.all', static::$cacheDuration, function () {
            return static::all()->pluck('value', 'key')->toArray();
        });
    }
    
    /**
     * Clear all settings cache
     */
    public static function clearCache(): void
    {
        $settings = static::all();
        foreach ($settings as $setting) {
            Cache::forget("system_setting.{$setting->key}");
        }
        Cache::forget('system_settings.all');
    }
    
    /**
     * Cast value based on type
     */
    protected static function castValue(string $value, string $type): mixed
    {
        return match($type) {
            'int', 'integer' => (int) $value,
            'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json', 'array' => json_decode($value, true),
            default => $value,
        };
    }
}
```

---

## Task 5.5.5: Amenities Caching

**File:** `app/Models/Amenity.php`

```php
/**
 * Get active amenities (cached)
 */
public static function getActiveCached(): Collection
{
    return Cache::remember('amenities.active', 3600, function () {
        return static::active()->orderBy('name')->get();
    });
}

/**
 * Clear amenities cache (call after create/update/delete)
 */
public static function clearCache(): void
{
    Cache::forget('amenities.active');
}
```

Update `AmenityController` to clear cache:

```php
public function store(StoreAmenityRequest $request): RedirectResponse
{
    $amenity = Amenity::create($request->validated());
    
    Amenity::clearCache(); // Clear cache
    
    // ... rest of method
}

public function update(UpdateAmenityRequest $request, Amenity $amenity): RedirectResponse
{
    $amenity->update($request->validated());
    
    Amenity::clearCache(); // Clear cache
    
    // ... rest of method
}

public function destroy(Amenity $amenity): RedirectResponse
{
    // ... deletion logic
    
    Amenity::clearCache(); // Clear cache
    
    // ... rest of method
}
```

---

## Task 5.5.6: Route Caching (Production)

Create deployment script to cache routes:

**File:** `scripts/optimize.sh` (or PowerShell equivalent)

```bash
#!/bin/bash

echo "Optimizing Laravel application..."

# Clear old cache
php artisan cache:clear
php artisan route:clear
php artisan config:clear
php artisan view:clear

# Generate new cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev

echo "Optimization complete!"
```

**PowerShell version:**
```powershell
Write-Host "Optimizing Laravel application..."

php artisan cache:clear
php artisan route:clear
php artisan config:clear
php artisan view:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache

composer install --optimize-autoloader --no-dev

Write-Host "Optimization complete!"
```

---

## Task 5.5.7: Query Logging for Development

**File:** `app/Providers/AppServiceProvider.php`

```php
public function boot(): void
{
    // Log slow queries in development
    if (config('app.debug')) {
        \DB::listen(function ($query) {
            if ($query->time > 100) { // Queries over 100ms
                \Log::warning('Slow Query Detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time . 'ms',
                ]);
            }
        });
    }
}
```

---

## Task 5.5.8: Pagination Optimization

Ensure all list views use pagination:

```php
// Standard pagination
$bookings = Booking::with(['room', 'user'])
    ->orderByDesc('booking_date')
    ->paginate(20);

// Simple pagination (more efficient for large datasets)
$auditLogs = AuditLog::orderByDesc('created_at')
    ->simplePaginate(50);

// Cursor pagination (most efficient for infinite scroll)
$logs = AuditLog::orderByDesc('created_at')
    ->cursorPaginate(50);
```

### Pagination Settings

| Page | Items Per Page | Pagination Type |
|------|----------------|-----------------|
| My Bookings | 20 | Standard |
| Admin Bookings | 50 | Standard |
| Audit Logs | 50 | Standard |
| User List | 20 | Standard |
| Room List | 20 | Standard |
| Amenity List | 15 | Standard |
| Report Tables | 100 | Standard |

---

## Task 5.5.9: Asset Optimization

### Image Optimization

For room images, consider lazy loading:

```blade
<img src="{{ $room->primaryImageUrl }}" 
     alt="{{ $room->name }}"
     loading="lazy"
     class="img-fluid">
```

### CSS/JS Bundling (if using Vite)

**File:** `vite.config.js`

```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        // Minify for production
        minify: 'terser',
        // Split chunks
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['jquery', 'bootstrap'],
                },
            },
        },
    },
});
```

Build for production:
```bash
npm run build
```

---

## Task 5.5.10: Database Connection Pooling

**File:** `config/database.php`

```php
'pgsql' => [
    'driver' => 'pgsql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '5432'),
    'database' => env('DB_DATABASE', 'mrbs'),
    'username' => env('DB_USERNAME', 'postgres'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8',
    'prefix' => '',
    'prefix_indexes' => true,
    'search_path' => 'public',
    'sslmode' => 'prefer',
    
    // Connection pooling settings
    'options' => [
        PDO::ATTR_PERSISTENT => true,
    ],
],
```

---

## Performance Verification

### Run Query Analysis

```bash
# Enable query logging
php artisan tinker
>>> \DB::enableQueryLog();
>>> \App\Models\Booking::with(['room', 'user'])->where('user_id', 1)->get();
>>> dd(\DB::getQueryLog());
```

### Check for N+1 Problems

```bash
# Install Laravel Debugbar (dev only)
composer require barryvdh/laravel-debugbar --dev

# Check Queries tab for duplicate queries
```

---

## Acceptance Criteria

- [ ] Performance indexes created for frequent queries
- [ ] All list queries use eager loading (no N+1)
- [ ] Report queries are optimized with aggregation
- [ ] System settings are cached
- [ ] Amenities list is cached
- [ ] All list views use pagination
- [ ] Slow queries (>100ms) are logged in development
- [ ] Production optimization script created
- [ ] Images use lazy loading
- [ ] No queries detected taking over 500ms in normal operations

---

## Performance Benchmarks (Target)

| Operation | Target Time |
|-----------|-------------|
| Dashboard load | < 500ms |
| Booking list (20 items) | < 300ms |
| Room list with amenities | < 300ms |
| Daily report generation | < 2s |
| Monthly report generation | < 5s |
| Audit log list (50 items) | < 500ms |

---

**Next:** [Step 5.6 - Testing](./step-5.6-testing.md)
