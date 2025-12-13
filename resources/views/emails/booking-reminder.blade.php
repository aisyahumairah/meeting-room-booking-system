<x-mail::message>
    # Upcoming Meeting Reminder

    This is a reminder that you have a meeting room booking tomorrow.

    **Booking Details:**
    - **Reference:** {{ $booking->reference_number }}
    - **Room:** {{ $booking->room->name }}
    - **Location:** {{ $booking->room->location }}
    - **Date:** {{ $booking->booking_date->format('l, F j, Y') }}
    - **Time:** {{ \Carbon\Carbon::parse($booking->start_time)->format('g:i A') }} -
    {{ \Carbon\Carbon::parse($booking->end_time)->format('g:i A') }}
    - **Purpose:** {{ $booking->purpose }}

    @if ($booking->room->amenities->isNotEmpty())
        **Room Amenities:** {{ $booking->room->amenities->pluck('name')->join(', ') }}
    @endif

    <x-mail::button :url="$viewUrl">
        View Booking Details
    </x-mail::button>

    If you need to cancel this booking, please do so before the meeting time.

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>
