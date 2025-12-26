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
        // Placeholder data - will be populated in Phase 3
        $todaysBookings = collect();

        $stats = [
            'today_count' => 0,
            'week_total' => 0,
            'month_total' => 0,
            'week_change' => '+0%',
        ];

        $topRooms = collect(); // For utilization chart

        return view('dashboard.admin', compact('todaysBookings', 'stats', 'topRooms'));
    }
}
