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
    ) {
    }

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
                // Past dates - definitely completed
                $q->where('booking_date', '<', $today)
                    // Or today but end time has passed
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
