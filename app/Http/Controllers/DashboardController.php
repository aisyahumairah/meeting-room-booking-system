<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Redirect based on role
        if ($user->canManageBookings()) {
            return $this->adminDashboard($request);
        }

        return $this->userDashboard($request);
    }

    public function userDashboard(Request $request)
    {
        $user = Auth::user();
        $year = $request->get('year', now()->year);

        // Fetch user's upcoming bookings (confirmed today onwards)
        $upcomingBookings = \App\Models\Booking::where('user_id', $user->id)
            ->with('room')
            ->where('booking_date', '>=', now()->toDateString())
            ->where('status', 'confirmed')
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->get();

        // Specific count for THIS WEEK (Monday to Sunday)
        $startOfWeek = now()->startOfWeek()->toDateString();
        $endOfWeek = now()->endOfWeek()->toDateString();
        $upcomingThisWeekCount = \App\Models\Booking::where('user_id', $user->id)
            ->whereBetween('booking_date', [$startOfWeek, $endOfWeek])
            ->where('status', 'confirmed')
            ->count();

        // Personal Statistics
        $stats = [
            'total_bookings' => \App\Models\Booking::where('user_id', $user->id)->count(),
            'this_month' => \App\Models\Booking::where('user_id', $user->id)
                ->whereMonth('booking_date', now()->month)
                ->whereYear('booking_date', now()->year)
                ->count(),
            'confirmed' => \App\Models\Booking::where('user_id', $user->id)
                ->where('status', 'confirmed')
                ->count(),
            'completed' => \App\Models\Booking::where('user_id', $user->id)
                ->where('status', 'completed')
                ->count(),
            'cancelled' => \App\Models\Booking::where('user_id', $user->id)
                ->where('status', 'cancelled')
                ->count(),
        ];

        // Monthly data for the chart
        $monthlyData = \App\Models\Booking::where('user_id', $user->id)
            ->whereYear('booking_date', $year)
            ->selectRaw('EXTRACT(MONTH FROM booking_date) as month, count(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('count', 'month')
            ->toArray();

        // Ensure all 12 months are present
        $chartData = [];
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        foreach (range(1, 12) as $m) {
            $chartData[] = $monthlyData[$m] ?? 0;
        }

        return view('dashboard.user', compact(
            'upcomingBookings',
            'upcomingThisWeekCount',
            'stats',
            'chartData',
            'months',
            'year'
        ));
    }

    public function adminDashboard(Request $request)
    {
        $now = now();
        $today = $now->toDateString();
        $year = $request->get('year', $now->year);
        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek = $now->copy()->endOfWeek();
        $startOfLastWeek = $now->copy()->subWeek()->startOfWeek();
        $endOfLastWeek = $now->copy()->subWeek()->endOfWeek();

        // 1. Today's Bookings List
        $todaysBookings = \App\Models\Booking::with(['room', 'user'])
            ->where('booking_date', $today)
            ->whereIn('status', ['confirmed', 'completed'])
            ->orderBy('start_time')
            ->get();

        // 2. Statistics
        $todayCount = \App\Models\Booking::where('booking_date', $today)
            ->whereIn('status', ['confirmed', 'completed'])
            ->count();

        $weekTotal = \App\Models\Booking::whereBetween('booking_date', [$startOfWeek, $endOfWeek])
            ->whereIn('status', ['confirmed', 'completed'])
            ->count();

        $lastWeekTotal = \App\Models\Booking::whereBetween('booking_date', [$startOfLastWeek, $endOfLastWeek])
            ->whereIn('status', ['confirmed', 'completed'])
            ->count();

        $monthTotal = \App\Models\Booking::whereMonth('booking_date', $now->month)
            ->whereYear('booking_date', $now->year)
            ->whereIn('status', ['confirmed', 'completed'])
            ->count();

        // Calculate week-over-week change
        $weekChange = 0;
        if ($lastWeekTotal > 0) {
            $weekChange = (($weekTotal - $lastWeekTotal) / $lastWeekTotal) * 100;
        } elseif ($weekTotal > 0) {
            $weekChange = 100;
        }
        $weekChangeFormatted = ($weekChange >= 0 ? '+' : '') . number_format($weekChange, 1) . '%';

        $stats = [
            'today_count' => $todayCount,
            'week_total' => $weekTotal,
            'month_total' => $monthTotal,
            'week_change' => $weekChangeFormatted,
            'week_change_value' => $weekChange,
        ];

        // 3. Room Utilization (Weekly Chart Data)
        // Get top 5 rooms by usage this week
        $topRooms = \App\Models\Room::active()
            ->withCount(['bookings' => function ($q) use ($startOfWeek, $endOfWeek) {
                $q->whereBetween('booking_date', [$startOfWeek, $endOfWeek])
                    ->whereIn('status', ['confirmed', 'completed']);
            }])
            ->orderBy('bookings_count', 'desc')
            ->take(5)
            ->get();

        $chartData = [];
        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

        foreach ($topRooms as $room) {
            $roomDailyHours = [];
            for ($i = 0; $i < 7; $i++) {
                $date = $startOfWeek->copy()->addDays($i)->toDateString();

                $totalMinutes = \App\Models\Booking::where('room_id', $room->id)
                    ->where('booking_date', $date)
                    ->whereIn('status', ['confirmed', 'completed'])
                    ->get()
                    ->sum('duration_minutes');

                $roomDailyHours[] = round($totalMinutes / 60, 1);
            }

            $chartData[] = [
                'name' => $room->name,
                'data' => $roomDailyHours
            ];
        }

        // 4. Overall Utilization Percentage for the SELECTED YEAR
        // Calculation: hours booked in the year vs possible hours (8h/day, 5 days/week, 52 weeks)
        $activeRoomsCount = \App\Models\Room::active()->count();
        $totalPossibleMinutesYear = $activeRoomsCount * 8 * 60 * 5 * 52;

        $totalBookedMinutesYear = \App\Models\Booking::whereYear('booking_date', $year)
            ->whereIn('status', ['confirmed', 'completed'])
            ->get()
            ->sum('duration_minutes');

        $avgUtilization = $totalPossibleMinutesYear > 0 ? round(($totalBookedMinutesYear / $totalPossibleMinutesYear) * 100) : 0;

        return view('dashboard.admin', compact(
            'todaysBookings',
            'stats',
            'chartData',
            'days',
            'avgUtilization',
            'year'
        ));
    }
}
