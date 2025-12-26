<x-mail::message>
    # Booking Cancelled

    Your meeting room booking has been cancelled.

    **Cancelled Booking Details:**
    - **Reference:** {{ $booking->reference_number }}
    - **Room:** {{ $booking->room->name }}
    - **Date:** {{ $booking->booking_date->format('l, F j, Y') }}
    - **Time:** {{ \Carbon\Carbon::parse($booking->start_time)->format('g:i A') }} -
    {{ \Carbon\Carbon::parse($booking->end_time)->format('g:i A') }}
    - **Purpose:** {{ $booking->purpose }}

    @if ($reason)
        **Cancellation Reason:** {{ $reason }}
    @endif

    If you need to book a room again, please visit the booking system.

    <x-mail::button :url="route('rooms.index')">
        Browse Rooms
    </x-mail::button>

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>
