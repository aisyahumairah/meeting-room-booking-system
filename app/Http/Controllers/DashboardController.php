<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Redirect based on role
        if ($user->canManageBookings()) {
            return $this->adminDashboard();
        }

        return $this->userDashboard();
    }

    public function userDashboard()
    {
        $user = Auth::user();

        // Placeholder data - will be populated in Phase 3
        $upcomingBookings = collect(); // Booking::where('user_id', $user->id)->upcoming()->take(5)->get();
        $pastBookings = collect(); // Booking::where('user_id', $user->id)->past()->take(5)->get();

        $stats = [
            'total_bookings' => 0,
            'this_month' => 0,
            'pending' => 0,
            'cancelled' => 0,
        ];

        return view('dashboard.user', compact('upcomingBookings', 'pastBookings', 'stats'));
    }

    public function adminDashboard()
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
