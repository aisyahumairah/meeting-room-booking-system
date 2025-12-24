<x-mail::message>
    # Booking Confirmed

    Your meeting room booking has been confirmed.

    **Booking Details:**
    - **Reference:** {{ $booking->reference_number }}
    - **Room:** {{ $booking->room->name }}
    - **Date:** {{ $booking->booking_date->format('l, F j, Y') }}
    - **Time:** {{ \Carbon\Carbon::parse($booking->start_time)->format('g:i A') }} -
    {{ \Carbon\Carbon::parse($booking->end_time)->format('g:i A') }}
    - **Purpose:** {{ $booking->purpose }}

    @if ($booking->attendees)
        **Attendees:** {{ $booking->attendees }}
    @endif

    <x-mail::button :url="$viewUrl">
        View Booking Details
    </x-mail::button>

    If you need to cancel this booking, please do so at least 24 hours in advance.

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>
