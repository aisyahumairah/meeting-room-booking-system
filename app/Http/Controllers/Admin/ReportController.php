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
            'total_rooms' => Room::where('status', 'active')->count(),
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

        $rooms = Room::where('status', 'active')->get();

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
            'bookingTrends',
            'statusDistribution',
            'topRooms',
            'peakHours',
            'cancellationTrend',
            'startDate',
            'endDate',
            'groupBy'
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
        $rooms = Room::where('status', 'active')->get();

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
                'location' => $room->floor_location,
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
