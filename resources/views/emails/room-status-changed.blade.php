<x-mail::message>
    # Room Status Changed

    The status of **{{ $room->name }}** has been updated.

    **Status Change:**
    - **Previous Status:** {{ ucfirst($oldStatus) }}
    - **New Status:** {{ ucfirst($newStatus) }}

    @if (count($affectedBookings) > 0)
        **Your Affected Bookings:**

        <x-mail::table>
            | Date | Time | Reference |
            |:-----|:-----|:----------|
            @foreach ($affectedBookings as $booking)
                | {{ \Carbon\Carbon::parse($booking['booking_date'])->format('M d, Y') }} |
                {{ \Carbon\Carbon::parse($booking['start_time'])->format('g:i A') }} |
                {{ $booking['reference_number'] }} |
            @endforeach
        </x-mail::table>

        @if ($newStatus === 'maintenance')
            Your bookings in this room may be affected. Please check your booking status or contact an administrator for
            assistance.
        @endif
    @endif

    <x-mail::button :url="route('rooms.show', $room)">
        View Room Details
    </x-mail::button>

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>
