# Step 4.6: Reporting System

**Priority:** MEDIUM | **Ref:** §7.5 | **Dependencies:** Phase 3 Bookings, Step 4.2 Audit  
**Status:** TODO

---

## Objective

Implement a comprehensive reporting system that generates utilization reports, booking statistics, and user activity summaries. Reports can be exported to PDF and Excel formats using `barryvdh/laravel-dompdf` and `maatwebsite/excel` packages.

---

## Task 4.6.1: Install Required Packages

```bash
composer require maatwebsite/excel barryvdh/laravel-dompdf
```

Publish configuration (optional):

```bash
php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider" --tag=config
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

---

## Task 4.6.2: Create ReportController

```bash
php artisan make:controller Admin/ReportController
```

**File:** `app/Http/Controllers/Admin/ReportController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Services\AuditService;
use App\Exports\RoomUtilizationExport;
use App\Exports\BookingReportExport;
use App\Exports\UserActivityReportExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Report dashboard with quick stats.
     */
    public function index()
    {
        // Quick stats for dashboard
        $stats = [
            'total_bookings_this_month' => Booking::whereMonth('booking_date', now()->month)
                ->whereYear('booking_date', now()->year)->count(),
            'total_rooms' => Room::where('is_active', true)->count(),
            'total_users' => User::where('status', 'active')->count(),
            'cancellation_rate' => $this->calculateCancellationRate(),
        ];

        return view('admin.reports.index', compact('stats'));
    }

    /**
     * Room Utilization Report.
     */
    public function roomUtilization(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $rooms = Room::where('is_active', true)->get();

        $reportData = $rooms->map(function ($room) use ($startDate, $endDate) {
            $bookings = $room->bookings()
                ->whereBetween('booking_date', [$startDate, $endDate])
                ->where('status', '!=', 'cancelled')
                ->get();

            $totalBookings = $bookings->count();
            $totalHours = $bookings->sum(function ($booking) {
                return Carbon::parse($booking->start_time)->diffInMinutes(Carbon::parse($booking->end_time)) / 60;
            });

            // Calculate utilization % (assuming 10 operating hours per day)
            $operatingHoursPerDay = 10;
            $totalDays = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
            $weekends = $this->countWeekends($startDate, $endDate);
            $workingDays = $totalDays - $weekends;
            $availableHours = $workingDays * $operatingHoursPerDay;
            $utilizationRate = $availableHours > 0 ? round(($totalHours / $availableHours) * 100, 1) : 0;

            return [
                'room' => $room,
                'total_bookings' => $totalBookings,
                'total_hours' => round($totalHours, 1),
                'available_hours' => $availableHours,
                'utilization_rate' => min($utilizationRate, 100), // Cap at 100%
                'avg_duration' => $totalBookings > 0 ? round($totalHours / $totalBookings, 1) : 0,
            ];
        });

        // Sort by utilization rate desc
        $reportData = $reportData->sortByDesc('utilization_rate')->values();

        return view('admin.reports.room-utilization', compact('reportData', 'startDate', 'endDate'));
    }

    /**
     * Booking Statistics Report.
     */
    public function bookingStatistics(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());
        $groupBy = $request->input('group_by', 'day'); // day, week, month

        // Booking trends
        $bookingTrends = $this->getBookingTrends($startDate, $endDate, $groupBy);

        // Status distribution
        $statusDistribution = Booking::whereBetween('booking_date', [$startDate, $endDate])
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Top rooms
        $topRooms = Booking::whereBetween('booking_date', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->select('room_id', DB::raw('count(*) as booking_count'))
            ->groupBy('room_id')
            ->orderByDesc('booking_count')
            ->with('room:id,name')
            ->limit(5)
            ->get();

        // Peak hours
        $peakHours = Booking::whereBetween('booking_date', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->selectRaw("EXTRACT(HOUR FROM start_time) as hour, count(*) as count")
            ->groupBy('hour')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // Cancellation rate trend
        $cancellationTrend = $this->getCancellationTrend($startDate, $endDate);

        return view('admin.reports.booking-statistics', compact(
            'bookingTrends', 'statusDistribution', 'topRooms', 'peakHours', 
            'cancellationTrend', 'startDate', 'endDate', 'groupBy'
        ));
    }

    /**
     * User Activity Summary Report.
     */
    public function userActivity(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $users = User::with(['bookings' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('booking_date', [$startDate, $endDate]);
        }])
        ->withCount(['bookings as total_bookings' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('booking_date', [$startDate, $endDate]);
        }])
        ->withCount(['bookings as cancelled_bookings' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('booking_date', [$startDate, $endDate])->where('status', 'cancelled');
        }])
        ->orderByDesc('total_bookings')
        ->limit(50)
        ->get();

        $reportData = $users->map(function ($user) {
            $cancellationRate = $user->total_bookings > 0 
                ? round(($user->cancelled_bookings / $user->total_bookings) * 100, 1)
                : 0;

            return [
                'user' => $user,
                'total_bookings' => $user->total_bookings,
                'cancelled_bookings' => $user->cancelled_bookings,
                'cancellation_rate' => $cancellationRate,
            ];
        });

        // Department breakdown
        $departmentStats = User::select('department', DB::raw('count(*) as user_count'))
            ->whereNotNull('department')
            ->groupBy('department')
            ->get();

        return view('admin.reports.user-activity', compact('reportData', 'departmentStats', 'startDate', 'endDate'));
    }

    /**
     * Export Room Utilization Report.
     */
    public function exportRoomUtilization(Request $request)
    {
        $format = $request->input('format', 'xlsx');
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $this->logReportExport('room_utilization', $format, $startDate, $endDate);

        $filename = "RoomUtilization_{$startDate}_to_{$endDate}";

        if ($format === 'pdf') {
            $reportData = $this->getRoomUtilizationData($startDate, $endDate);
            $pdf = Pdf::loadView('admin.reports.pdf.room-utilization', compact('reportData', 'startDate', 'endDate'));
            return $pdf->download("{$filename}.pdf");
        }

        return Excel::download(
            new RoomUtilizationExport($startDate, $endDate),
            "{$filename}.{$format}",
            $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
        );
    }

    /**
     * Export Booking Statistics Report.
     */
    public function exportBookingStatistics(Request $request)
    {
        $format = $request->input('format', 'xlsx');
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $this->logReportExport('booking_statistics', $format, $startDate, $endDate);

        $filename = "BookingStatistics_{$startDate}_to_{$endDate}";

        if ($format === 'pdf') {
            $data = $this->getBookingStatisticsData($startDate, $endDate);
            $pdf = Pdf::loadView('admin.reports.pdf.booking-statistics', $data);
            return $pdf->download("{$filename}.pdf");
        }

        return Excel::download(
            new BookingReportExport($startDate, $endDate),
            "{$filename}.{$format}",
            $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
        );
    }

    /**
     * Export User Activity Report.
     */
    public function exportUserActivity(Request $request)
    {
        $format = $request->input('format', 'xlsx');
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $this->logReportExport('user_activity', $format, $startDate, $endDate);

        $filename = "UserActivity_{$startDate}_to_{$endDate}";

        if ($format === 'pdf') {
            $data = $this->getUserActivityData($startDate, $endDate);
            $pdf = Pdf::loadView('admin.reports.pdf.user-activity', $data);
            return $pdf->download("{$filename}.pdf");
        }

        return Excel::download(
            new UserActivityReportExport($startDate, $endDate),
            "{$filename}.{$format}",
            $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
        );
    }

    // --- Helper Methods ---

    private function calculateCancellationRate(): float
    {
        $total = Booking::whereMonth('booking_date', now()->month)->count();
        $cancelled = Booking::whereMonth('booking_date', now()->month)->where('status', 'cancelled')->count();
        return $total > 0 ? round(($cancelled / $total) * 100, 1) : 0;
    }

    private function countWeekends(string $startDate, string $endDate): int
    {
        $count = 0;
        $current = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        while ($current <= $end) {
            if ($current->isWeekend()) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }

    private function getBookingTrends(string $startDate, string $endDate, string $groupBy): array
    {
        $format = match ($groupBy) {
            'week' => 'YYYY-IW',
            'month' => 'YYYY-MM',
            default => 'YYYY-MM-DD',
        };

        return Booking::whereBetween('booking_date', [$startDate, $endDate])
            ->selectRaw("TO_CHAR(booking_date, '{$format}') as period, count(*) as count")
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('count', 'period')
            ->toArray();
    }

    private function getCancellationTrend(string $startDate, string $endDate): array
    {
        $data = Booking::whereBetween('booking_date', [$startDate, $endDate])
            ->selectRaw("TO_CHAR(booking_date, 'YYYY-MM-DD') as date, 
                         count(*) as total,
                         sum(case when status = 'cancelled' then 1 else 0 end) as cancelled")
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $data->map(function ($item) {
            return [
                'date' => $item->date,
                'rate' => $item->total > 0 ? round(($item->cancelled / $item->total) * 100, 1) : 0,
            ];
        })->toArray();
    }

    private function getRoomUtilizationData(string $startDate, string $endDate): array
    {
        // Same logic as roomUtilization() but returns array
        $rooms = Room::where('is_active', true)->get();
        
        return $rooms->map(function ($room) use ($startDate, $endDate) {
            $bookings = $room->bookings()
                ->whereBetween('booking_date', [$startDate, $endDate])
                ->where('status', '!=', 'cancelled')
                ->get();

            $totalHours = $bookings->sum(function ($booking) {
                return Carbon::parse($booking->start_time)->diffInMinutes(Carbon::parse($booking->end_time)) / 60;
            });

            $totalDays = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
            $weekends = $this->countWeekends($startDate, $endDate);
            $workingDays = $totalDays - $weekends;
            $availableHours = $workingDays * 10;

            return [
                'room_name' => $room->name,
                'location' => $room->location,
                'capacity' => $room->capacity,
                'total_bookings' => $bookings->count(),
                'total_hours' => round($totalHours, 1),
                'utilization_rate' => $availableHours > 0 ? min(round(($totalHours / $availableHours) * 100, 1), 100) : 0,
            ];
        })->sortByDesc('utilization_rate')->values()->toArray();
    }

    private function getBookingStatisticsData(string $startDate, string $endDate): array
    {
        return [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'statusDistribution' => Booking::whereBetween('booking_date', [$startDate, $endDate])
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray(),
            'topRooms' => Booking::whereBetween('booking_date', [$startDate, $endDate])
                ->where('status', '!=', 'cancelled')
                ->select('room_id', DB::raw('count(*) as booking_count'))
                ->groupBy('room_id')
                ->orderByDesc('booking_count')
                ->with('room:id,name')
                ->limit(10)
                ->get()
                ->toArray(),
        ];
    }

    private function getUserActivityData(string $startDate, string $endDate): array
    {
        $users = User::withCount(['bookings as total_bookings' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('booking_date', [$startDate, $endDate]);
        }])
        ->withCount(['bookings as cancelled_bookings' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('booking_date', [$startDate, $endDate])->where('status', 'cancelled');
        }])
        ->orderByDesc('total_bookings')
        ->limit(50)
        ->get();

        return [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'users' => $users->map(function ($user) {
                return [
                    'name' => $user->name,
                    'email' => $user->email,
                    'department' => $user->department,
                    'total_bookings' => $user->total_bookings,
                    'cancelled_bookings' => $user->cancelled_bookings,
                    'cancellation_rate' => $user->total_bookings > 0 
                        ? round(($user->cancelled_bookings / $user->total_bookings) * 100, 1) : 0,
                ];
            })->toArray(),
        ];
    }

    private function logReportExport(string $reportType, string $format, string $startDate, string $endDate): void
    {
        AuditService::log(
            AuditService::EVENT_REPORT_GENERATED,
            null,
            null,
            [
                'report_type' => $reportType,
                'format' => $format,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]
        );
    }
}
```

---

## Task 4.6.3: Create Export Classes

```bash
php artisan make:export RoomUtilizationExport
php artisan make:export BookingReportExport
php artisan make:export UserActivityReportExport
```

**File:** `app/Exports/RoomUtilizationExport.php`

```php
<?php

namespace App\Exports;

use App\Models\Room;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RoomUtilizationExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    protected string $startDate;
    protected string $endDate;

    public function __construct(string $startDate, string $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        $rooms = Room::where('is_active', true)->get();

        return $rooms->map(function ($room) {
            $bookings = $room->bookings()
                ->whereBetween('booking_date', [$this->startDate, $this->endDate])
                ->where('status', '!=', 'cancelled')
                ->get();

            $totalHours = $bookings->sum(function ($booking) {
                return Carbon::parse($booking->start_time)->diffInMinutes(Carbon::parse($booking->end_time)) / 60;
            });

            $totalDays = Carbon::parse($this->startDate)->diffInDays(Carbon::parse($this->endDate)) + 1;
            $workingDays = $this->countWorkingDays($this->startDate, $this->endDate);
            $availableHours = $workingDays * 10;
            $utilizationRate = $availableHours > 0 ? min(round(($totalHours / $availableHours) * 100, 1), 100) : 0;

            return [
                'room_name' => $room->name,
                'location' => $room->location,
                'capacity' => $room->capacity,
                'total_bookings' => $bookings->count(),
                'total_hours' => round($totalHours, 1),
                'available_hours' => $availableHours,
                'utilization_rate' => $utilizationRate . '%',
            ];
        })->sortByDesc('total_bookings');
    }

    public function headings(): array
    {
        return [
            'Room Name',
            'Location',
            'Capacity',
            'Total Bookings',
            'Hours Used',
            'Available Hours',
            'Utilization Rate',
        ];
    }

    public function title(): string
    {
        return 'Room Utilization';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    private function countWorkingDays(string $startDate, string $endDate): int
    {
        $count = 0;
        $current = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        while ($current <= $end) {
            if (!$current->isWeekend()) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }
}
```

**File:** `app/Exports/BookingReportExport.php`

```php
<?php

namespace App\Exports;

use App\Models\Booking;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class BookingReportExport implements FromQuery, WithHeadings, WithMapping, WithTitle, ShouldAutoSize
{
    protected string $startDate;
    protected string $endDate;

    public function __construct(string $startDate, string $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function query()
    {
        return Booking::query()
            ->whereBetween('booking_date', [$this->startDate, $this->endDate])
            ->with(['user:id,name,department', 'room:id,name'])
            ->orderBy('booking_date')
            ->orderBy('start_time');
    }

    public function headings(): array
    {
        return [
            'Reference',
            'Date',
            'Start Time',
            'End Time',
            'Room',
            'Booked By',
            'Department',
            'Purpose',
            'Attendees',
            'Status',
            'Created At',
        ];
    }

    public function map($booking): array
    {
        return [
            $booking->reference_number,
            $booking->booking_date->format('Y-m-d'),
            $booking->start_time->format('H:i'),
            $booking->end_time->format('H:i'),
            $booking->room->name,
            $booking->user->name,
            $booking->user->department ?? '-',
            $booking->purpose,
            $booking->attendees ?? '-',
            ucfirst($booking->status),
            $booking->created_at->format('Y-m-d H:i'),
        ];
    }

    public function title(): string
    {
        return 'Booking Report';
    }
}
```

**File:** `app/Exports/UserActivityReportExport.php`

```php
<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class UserActivityReportExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    protected string $startDate;
    protected string $endDate;

    public function __construct(string $startDate, string $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        return User::withCount(['bookings as total_bookings' => function ($query) {
            $query->whereBetween('booking_date', [$this->startDate, $this->endDate]);
        }])
        ->withCount(['bookings as cancelled_bookings' => function ($query) {
            $query->whereBetween('booking_date', [$this->startDate, $this->endDate])->where('status', 'cancelled');
        }])
        ->orderByDesc('total_bookings')
        ->get()
        ->map(function ($user) {
            $cancellationRate = $user->total_bookings > 0 
                ? round(($user->cancelled_bookings / $user->total_bookings) * 100, 1) : 0;

            return [
                'name' => $user->name,
                'email' => $user->email,
                'department' => $user->department ?? '-',
                'role' => ucfirst(str_replace('_', ' ', $user->role)),
                'total_bookings' => $user->total_bookings,
                'cancelled_bookings' => $user->cancelled_bookings,
                'cancellation_rate' => $cancellationRate . '%',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Name',
            'Email',
            'Department',
            'Role',
            'Total Bookings',
            'Cancelled',
            'Cancellation Rate',
        ];
    }

    public function title(): string
    {
        return 'User Activity';
    }
}
```

---

## Task 4.6.4: Define Report Routes

**File:** `routes/web.php`

Add to Director/SysAdmin routes:

```php
// Reports (Director/SysAdmin only)
Route::prefix('admin/reports')->name('admin.reports.')->middleware(['auth', 'check.active', 'check.role:director,system_admin'])->group(function () {
    Route::get('/', [Admin\ReportController::class, 'index'])->name('index');
    
    // View Reports
    Route::get('room-utilization', [Admin\ReportController::class, 'roomUtilization'])->name('room-utilization');
    Route::get('booking-statistics', [Admin\ReportController::class, 'bookingStatistics'])->name('booking-statistics');
    Route::get('user-activity', [Admin\ReportController::class, 'userActivity'])->name('user-activity');
    
    // Export Reports
    Route::get('room-utilization/export', [Admin\ReportController::class, 'exportRoomUtilization'])->name('room-utilization.export');
    Route::get('booking-statistics/export', [Admin\ReportController::class, 'exportBookingStatistics'])->name('booking-statistics.export');
    Route::get('user-activity/export', [Admin\ReportController::class, 'exportUserActivity'])->name('user-activity.export');
});
```

---

## Task 4.6.5: Create Report Views

**File:** `resources/views/admin/reports/index.blade.php`

```blade
@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">
            <i class='bx bx-bar-chart-alt-2 me-2'></i>Reports & Analytics
        </h4>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0">{{ $stats['total_bookings_this_month'] }}</h3>
                            <small class="text-muted">Bookings This Month</small>
                        </div>
                        <div class="avatar bg-label-primary">
                            <i class='bx bx-calendar fs-4'></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0">{{ $stats['total_rooms'] }}</h3>
                            <small class="text-muted">Active Rooms</small>
                        </div>
                        <div class="avatar bg-label-info">
                            <i class='bx bx-door-open fs-4'></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0">{{ $stats['total_users'] }}</h3>
                            <small class="text-muted">Active Users</small>
                        </div>
                        <div class="avatar bg-label-success">
                            <i class='bx bx-user fs-4'></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0 {{ $stats['cancellation_rate'] > 15 ? 'text-warning' : '' }}">
                                {{ $stats['cancellation_rate'] }}%
                            </h3>
                            <small class="text-muted">Cancellation Rate</small>
                        </div>
                        <div class="avatar bg-label-{{ $stats['cancellation_rate'] > 15 ? 'warning' : 'secondary' }}">
                            <i class='bx bx-x-circle fs-4'></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Available Reports -->
    <div class="row">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="avatar avatar-lg bg-label-primary mx-auto mb-3">
                        <i class='bx bx-building-house fs-2'></i>
                    </div>
                    <h5>Room Utilization</h5>
                    <p class="text-muted">
                        Analyze how each room is being used. See booking counts, hours used, and utilization rates.
                    </p>
                    <a href="{{ route('admin.reports.room-utilization') }}" class="btn btn-primary">
                        View Report
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="avatar avatar-lg bg-label-info mx-auto mb-3">
                        <i class='bx bx-line-chart fs-2'></i>
                    </div>
                    <h5>Booking Statistics</h5>
                    <p class="text-muted">
                        View booking trends, status distribution, peak hours, and top rooms.
                    </p>
                    <a href="{{ route('admin.reports.booking-statistics') }}" class="btn btn-info">
                        View Report
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="avatar avatar-lg bg-label-success mx-auto mb-3">
                        <i class='bx bx-user-check fs-2'></i>
                    </div>
                    <h5>User Activity</h5>
                    <p class="text-muted">
                        See user booking activity, cancellation rates, and department breakdown.
                    </p>
                    <a href="{{ route('admin.reports.user-activity') }}" class="btn btn-success">
                        View Report
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

**File:** `resources/views/admin/reports/room-utilization.blade.php`

```blade
@extends('layouts.app')

@section('title', 'Room Utilization Report')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('admin.reports.index') }}" class="text-muted text-decoration-none">
                <i class='bx bx-arrow-back me-1'></i> Back to Reports
            </a>
            <h4 class="mb-0 mt-2">Room Utilization Report</h4>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                <i class='bx bx-download me-1'></i> Export
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="{{ route('admin.reports.room-utilization.export', array_merge(request()->all(), ['format' => 'xlsx'])) }}">
                    <i class='bx bx-spreadsheet me-2'></i> Excel (.xlsx)
                </a></li>
                <li><a class="dropdown-item" href="{{ route('admin.reports.room-utilization.export', array_merge(request()->all(), ['format' => 'csv'])) }}">
                    <i class='bx bx-file me-2'></i> CSV
                </a></li>
                <li><a class="dropdown-item" href="{{ route('admin.reports.room-utilization.export', array_merge(request()->all(), ['format' => 'pdf'])) }}">
                    <i class='bx bxs-file-pdf me-2'></i> PDF
                </a></li>
            </ul>
        </div>
    </div>

    <!-- Date Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports.room-utilization') }}" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bx-refresh me-1'></i> Update Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Table -->
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">Report Period: {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Room</th>
                        <th>Location</th>
                        <th class="text-center">Capacity</th>
                        <th class="text-center">Total Bookings</th>
                        <th class="text-center">Hours Used</th>
                        <th class="text-center">Available Hours</th>
                        <th class="text-center">Utilization</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportData as $data)
                    <tr>
                        <td>
                            <a href="{{ route('rooms.show', $data['room']->id) }}" class="text-decoration-none">
                                {{ $data['room']->name }}
                            </a>
                        </td>
                        <td>{{ $data['room']->location }}</td>
                        <td class="text-center">{{ $data['room']->capacity }}</td>
                        <td class="text-center">{{ $data['total_bookings'] }}</td>
                        <td class="text-center">{{ $data['total_hours'] }}h</td>
                        <td class="text-center">{{ $data['available_hours'] }}h</td>
                        <td class="text-center">
                            <div class="d-flex align-items-center justify-content-center">
                                <div class="progress me-2" style="width: 100px; height: 8px;">
                                    <div class="progress-bar bg-{{ $data['utilization_rate'] > 70 ? 'success' : ($data['utilization_rate'] > 40 ? 'warning' : 'danger') }}" 
                                         style="width: {{ $data['utilization_rate'] }}%"></div>
                                </div>
                                <span class="fw-semibold">{{ $data['utilization_rate'] }}%</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <i class='bx bx-info-circle fs-1 text-muted'></i>
                            <p class="text-muted mt-2">No data available for the selected period.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
```

**File:** `resources/views/admin/reports/booking-statistics.blade.php`

```blade
@extends('layouts.app')

@section('title', 'Booking Statistics Report')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('admin.reports.index') }}" class="text-muted text-decoration-none">
                <i class='bx bx-arrow-back me-1'></i> Back to Reports
            </a>
            <h4 class="mb-0 mt-2">Booking Statistics Report</h4>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                <i class='bx bx-download me-1'></i> Export
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="{{ route('admin.reports.booking-statistics.export', array_merge(request()->all(), ['format' => 'xlsx'])) }}">
                    <i class='bx bx-spreadsheet me-2'></i> Excel
                </a></li>
                <li><a class="dropdown-item" href="{{ route('admin.reports.booking-statistics.export', array_merge(request()->all(), ['format' => 'pdf'])) }}">
                    <i class='bx bxs-file-pdf me-2'></i> PDF
                </a></li>
            </ul>
        </div>
    </div>

    <!-- Date Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports.booking-statistics') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Group By</label>
                    <select name="group_by" class="form-select">
                        <option value="day" {{ $groupBy == 'day' ? 'selected' : '' }}>Day</option>
                        <option value="week" {{ $groupBy == 'week' ? 'selected' : '' }}>Week</option>
                        <option value="month" {{ $groupBy == 'month' ? 'selected' : '' }}>Month</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bx-refresh me-1'></i> Update
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <!-- Status Distribution -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">Status Distribution</h6>
                </div>
                <div class="card-body">
                    @foreach($statusDistribution as $status => $count)
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-{{ $status == 'confirmed' ? 'success' : ($status == 'cancelled' ? 'danger' : 'secondary') }}">
                            {{ ucfirst($status) }}
                        </span>
                        <span class="fw-semibold">{{ $count }}</span>
                    </div>
                    @endforeach
                    @if(empty($statusDistribution))
                    <p class="text-muted text-center">No data available</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Top Rooms -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">Top 5 Most Booked Rooms</h6>
                </div>
                <div class="card-body">
                    @foreach($topRooms as $item)
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>{{ $item->room->name ?? 'Unknown' }}</span>
                        <span class="badge bg-primary">{{ $item->booking_count }} bookings</span>
                    </div>
                    @endforeach
                    @if($topRooms->isEmpty())
                    <p class="text-muted text-center">No data available</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Peak Hours -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">Peak Booking Hours</h6>
                </div>
                <div class="card-body">
                    @foreach($peakHours as $hour)
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>{{ sprintf('%02d:00', $hour->hour) }} - {{ sprintf('%02d:00', $hour->hour + 1) }}</span>
                        <span class="badge bg-info">{{ $hour->count }} bookings</span>
                    </div>
                    @endforeach
                    @if($peakHours->isEmpty())
                    <p class="text-muted text-center">No data available</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Booking Trends -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">Booking Trend</h6>
                </div>
                <div class="card-body">
                    @if(count($bookingTrends) > 0)
                    <div style="max-height: 200px; overflow-y: auto;">
                        @foreach($bookingTrends as $period => $count)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small>{{ $period }}</small>
                            <span class="badge bg-secondary">{{ $count }}</span>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-muted text-center">No data available</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

**File:** `resources/views/admin/reports/user-activity.blade.php`

```blade
@extends('layouts.app')

@section('title', 'User Activity Report')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('admin.reports.index') }}" class="text-muted text-decoration-none">
                <i class='bx bx-arrow-back me-1'></i> Back to Reports
            </a>
            <h4 class="mb-0 mt-2">User Activity Report</h4>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                <i class='bx bx-download me-1'></i> Export
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="{{ route('admin.reports.user-activity.export', array_merge(request()->all(), ['format' => 'xlsx'])) }}">
                    <i class='bx bx-spreadsheet me-2'></i> Excel
                </a></li>
                <li><a class="dropdown-item" href="{{ route('admin.reports.user-activity.export', array_merge(request()->all(), ['format' => 'pdf'])) }}">
                    <i class='bx bxs-file-pdf me-2'></i> PDF
                </a></li>
            </ul>
        </div>
    </div>

    <!-- Date Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports.user-activity') }}" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bx-refresh me-1'></i> Update Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <!-- Department Stats -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">Users by Department</h6>
                </div>
                <div class="card-body">
                    @foreach($departmentStats as $dept)
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>{{ $dept->department }}</span>
                        <span class="badge bg-primary">{{ $dept->user_count }}</span>
                    </div>
                    @endforeach
                    @if($departmentStats->isEmpty())
                    <p class="text-muted text-center">No department data</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- User Table -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Top Users by Booking Activity</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Department</th>
                                <th class="text-center">Bookings</th>
                                <th class="text-center">Cancelled</th>
                                <th class="text-center">Cancel Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData as $data)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm me-2 bg-label-primary">
                                            <span class="avatar-initial rounded-circle">{{ substr($data['user']->name, 0, 1) }}</span>
                                        </div>
                                        <div>
                                            <span>{{ $data['user']->name }}</span>
                                            <small class="d-block text-muted">{{ $data['user']->email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $data['user']->department ?? '-' }}</td>
                                <td class="text-center">{{ $data['total_bookings'] }}</td>
                                <td class="text-center">{{ $data['cancelled_bookings'] }}</td>
                                <td class="text-center">
                                    <span class="{{ $data['cancellation_rate'] > 20 ? 'text-warning fw-semibold' : '' }}">
                                        {{ $data['cancellation_rate'] }}%
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <p class="text-muted">No activity data for the selected period.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

---

## Task 4.6.6: Create PDF Templates

**File:** `resources/views/admin/reports/pdf/room-utilization.blade.php`

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Room Utilization Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; margin-bottom: 5px; }
        .header { margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .period { color: #666; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f5f5f5; font-weight: bold; }
        .text-center { text-align: center; }
        .footer { margin-top: 30px; font-size: 10px; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Room Utilization Report</h1>
        <div class="period">Period: {{ $startDate }} to {{ $endDate }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Room Name</th>
                <th>Location</th>
                <th class="text-center">Capacity</th>
                <th class="text-center">Total Bookings</th>
                <th class="text-center">Hours Used</th>
                <th class="text-center">Utilization Rate</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reportData as $data)
            <tr>
                <td>{{ $data['room_name'] }}</td>
                <td>{{ $data['location'] }}</td>
                <td class="text-center">{{ $data['capacity'] }}</td>
                <td class="text-center">{{ $data['total_bookings'] }}</td>
                <td class="text-center">{{ $data['total_hours'] }}h</td>
                <td class="text-center">{{ $data['utilization_rate'] }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generated on {{ now()->format('Y-m-d H:i:s') }} | MRBS - Meeting Room Booking System
    </div>
</body>
</html>
```

---

## Task 4.6.7: Update Sidebar Navigation

**File:** `resources/views/layouts/partials/sidebar.blade.php`

Add Reports menu item (for Director/SysAdmin):

```blade
@if(auth()->user()->canAccessReports())
<li class="menu-item {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
    <a href="{{ route('admin.reports.index') }}" class="menu-link">
        <i class="menu-icon tf-icons bx bx-bar-chart-alt-2"></i>
        <div>Reports</div>
    </a>
</li>
@endif
```

---

## Testing Requirements

> **Note:** When creating test users with the factory, use `->passwordChanged()` to set `must_change_password` to `false`. Otherwise, the `MustChangePassword` middleware will redirect users causing tests to receive 302 responses instead of 200.

**File:** `tests/Feature/ReportingSystemTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReportingSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $sysAdmin;
    protected User $director;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->sysAdmin = User::factory()->passwordChanged()->create(['role' => 'system_admin']);
        $this->director = User::factory()->passwordChanged()->create(['role' => 'director']);
        $this->regularUser = User::factory()->passwordChanged()->create(['role' => 'regular_user']);

        // Create test data
        $room = Room::factory()->create();
        Booking::factory()->count(5)->create(['room_id' => $room->id, 'status' => 'confirmed']);
        Booking::factory()->count(2)->create(['room_id' => $room->id, 'status' => 'cancelled']);
    }

    public function test_sysadmin_can_access_reports_dashboard(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.reports.index'));
        $response->assertStatus(200);
        $response->assertSee('Reports & Analytics');
    }

    public function test_director_can_access_reports(): void
    {
        $response = $this->actingAs($this->director)->get(route('admin.reports.index'));
        $response->assertStatus(200);
    }

    public function test_regular_user_cannot_access_reports(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.reports.index'));
        $response->assertStatus(403);
    }

    public function test_room_utilization_report_loads(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.reports.room-utilization'));
        $response->assertStatus(200);
        $response->assertSee('Room Utilization Report');
    }

    public function test_booking_statistics_report_loads(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.reports.booking-statistics'));
        $response->assertStatus(200);
        $response->assertSee('Booking Statistics Report');
    }

    public function test_user_activity_report_loads(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.reports.user-activity'));
        $response->assertStatus(200);
        $response->assertSee('User Activity Report');
    }

    public function test_can_export_room_utilization_xlsx(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.reports.room-utilization.export', ['format' => 'xlsx']));
        $response->assertStatus(200);
    }

    public function test_can_export_room_utilization_csv(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.reports.room-utilization.export', ['format' => 'csv']));
        $response->assertStatus(200);
    }

    public function test_can_export_room_utilization_pdf(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.reports.room-utilization.export', ['format' => 'pdf']));
        $response->assertStatus(200);
    }

    public function test_date_filter_works_for_reports(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.reports.room-utilization', [
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->toDateString(),
        ]));

        $response->assertStatus(200);
    }

    public function test_report_export_is_logged(): void
    {
        $this->actingAs($this->sysAdmin)->get(route('admin.reports.room-utilization.export', ['format' => 'xlsx']));

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'report.generated',
            'actor_id' => $this->sysAdmin->id,
        ]);
    }

    public function test_quick_stats_displayed_on_dashboard(): void
    {
        $response = $this->actingAs($this->sysAdmin)->get(route('admin.reports.index'));

        $response->assertSee('Bookings This Month');
        $response->assertSee('Active Rooms');
        $response->assertSee('Active Users');
        $response->assertSee('Cancellation Rate');
    }
}
```

---

## Acceptance Criteria

- [x] Reports dashboard displays quick stats
- [x] Room Utilization report shows: room name, bookings, hours, utilization %
- [x] Booking Statistics report shows: trends, status distribution, top rooms, peak hours
- [x] User Activity report shows: user list with bookings and cancellation rate
- [x] Date range filters work for all reports
- [x] Group by selector works for booking statistics (day/week/month)
- [x] Export to Excel (.xlsx) works
- [x] Export to CSV works
- [x] Export to PDF works
- [x] Export actions are logged to audit trail
- [x] Utilization percentage calculated correctly (based on working days)
- [x] Only Director/SysAdmin can access reports
- [x] Sidebar navigation updated
- [x] All tests pass: `php artisan test --filter=ReportingSystemTest`

---

**Phase 4 Complete!** 

Return to [Phase 4 README](./README.md) for the full overview.
